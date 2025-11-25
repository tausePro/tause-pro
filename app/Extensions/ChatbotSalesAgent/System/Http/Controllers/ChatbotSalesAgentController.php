<?php

declare(strict_types=1);

namespace App\Extensions\ChatbotSalesAgent\System\Http\Controllers;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Services\WompiService;
use App\Extensions\Chatbot\System\Services\WooCommerceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotSalesAgentController extends Controller
{
    public function __construct(
        protected WooCommerceService $wooCommerceService,
        protected WompiService $wompiService
    ) {}

    /**
     * Mostrar interfaz del Sales Agent (dashboard)
     */
    public function index(Chatbot $chatbot)
    {
        // Verificar que el usuario sea dueño del chatbot
        if ($chatbot->user_id !== auth()->id()) {
            abort(403, 'This action is unauthorized.');
        }

        // Verificar que WooCommerce y Wompi estén configurados
        if (! $chatbot->woocommerce_enabled) {
            return redirect()
                ->route('dashboard.chatbot.ecommerce.index', $chatbot)
                ->with('error', '⚠️ Primero debes configurar WooCommerce en el dashboard de E-commerce');
        }

        if (! $chatbot->wompi_enabled) {
            return redirect()
                ->route('dashboard.chatbot.ecommerce.index', $chatbot)
                ->with('error', '⚠️ Primero debes configurar Wompi en el dashboard de E-commerce');
        }

        // Obtener productos activos y formatearlos
        $products = $chatbot->products()
            ->active()
            ->inStock()
            ->latest('last_synced_at')
            ->get()
            ->map(function ($product) {
                $price = (float) $product->price;

                return [
                    'id'              => $product->id,
                    'woocommerce_id'  => $product->woocommerce_id,
                    'name'            => $product->name,
                    'description'     => $product->description,
                    'price'           => $price,
                    'formatted_price' => '$' . number_format($price, 0, ',', '.') . ' COP',
                    'image_url'       => $product->image_url,
                    'sku'             => $product->sku,
                    'in_stock'        => (bool) $product->in_stock,
                    'stock_quantity'  => $product->stock_quantity,
                ];
            });

        return view('sales-agent::index', compact('chatbot', 'products'));
    }

    /**
     * Obtener productos filtrados (AJAX)
     */
    public function getProducts(Request $request, Chatbot $chatbot): JsonResponse
    {
        // Para API pública, verificar por UUID
        if ($request->is('api/*')) {
            // API endpoint - no requiere auth
        } else {
            // Dashboard endpoint - requiere auth
            if ($chatbot->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        $query = $chatbot->products()->active()->inStock();

        // Filtrar por búsqueda si existe
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filtrar por categoría si existe
        if ($request->has('category')) {
            $query->whereJsonContains('categories', ['id' => (int) $request->input('category')]);
        }

        $products = $query->latest('last_synced_at')->get();

        return response()->json([
            'success'  => true,
            'products' => $products->map(function ($product) {
                $price = (float) $product->price;

                return [
                    'id'              => $product->id,
                    'woocommerce_id'  => $product->woocommerce_id,
                    'name'            => $product->name,
                    'description'     => $product->description,
                    'price'           => $price,
                    'formatted_price' => '$' . number_format($price, 0, ',', '.') . ' COP',
                    'image_url'       => $product->image_url,
                    'sku'             => $product->sku,
                    'in_stock'        => $product->in_stock,
                    'stock_quantity'  => $product->stock_quantity,
                ];
            }),
        ]);
    }

    /**
     * Crear orden en WooCommerce desde el chat externo o dashboard
     * Soporta carrito con múltiples productos
     */
    public function createOrder(Request $request, Chatbot $chatbot): JsonResponse
    {
        // Para API pública, no requiere auth
        // Para dashboard, verificar propiedad
        if (! $request->is('api/*') && $chatbot->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Verificar que Sales Agent, WooCommerce y Wompi estén habilitados
        if (! $chatbot->sales_agent_enabled || ! $chatbot->woocommerce_enabled || ! $chatbot->wompi_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Sales Agent, WooCommerce o Wompi no están habilitados.',
            ], 400);
        }

        // Validar datos del cliente
        $validated = $request->validate([
            // Carrito con múltiples items (opcional, para compatibilidad)
            'items'              => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:ext_chatbot_products,id',
            'items.*.quantity'   => 'required_with:items|integer|min:1',
            // Legacy: producto único
            'product_id' => 'required_without:items|exists:ext_chatbot_products,id',
            'quantity'   => 'required_without:items|integer|min:1',
            // Datos del cliente
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'email'              => 'required|email|max:255',
            'phone'              => 'required|string|max:50',
            'department'         => 'required|string|max:100',
            'city'               => 'required|string|max:100',
            'address'            => 'required|string|max:500',
            'address_type'       => 'nullable|string|in:Casa,Apartamento,Oficina',
            'address_complement' => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:500',
        ]);

        // Construir lista de items del carrito
        $cartItems = [];
        $total = 0;

        if (! empty($validated['items'])) {
            // Carrito con múltiples productos
            foreach ($validated['items'] as $item) {
                $product = $chatbot->products()->find($item['product_id']);
                if (! $product) {
                    continue;
                }

                // Verificar stock
                if (! $product->in_stock || ($product->stock_quantity && $product->stock_quantity < $item['quantity'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Producto '{$product->name}' sin stock suficiente",
                    ], 400);
                }

                $cartItems[] = [
                    'product'        => $product,
                    'woocommerce_id' => $product->woocommerce_id,
                    'quantity'       => $item['quantity'],
                ];
                $total += $product->price * $item['quantity'];
            }
        } else {
            // Legacy: producto único
            $product = $chatbot->products()->findOrFail($validated['product_id']);

            if (! $product->in_stock || ($product->stock_quantity && $product->stock_quantity < $validated['quantity'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto sin stock suficiente',
                ], 400);
            }

            $cartItems[] = [
                'product'        => $product,
                'woocommerce_id' => $product->woocommerce_id,
                'quantity'       => $validated['quantity'],
            ];
            $total = $product->price * $validated['quantity'];
        }

        if (empty($cartItems)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay productos válidos en el carrito',
            ], 400);
        }

        // Preparar dirección completa
        $fullAddress = $validated['address'];
        if (! empty($validated['address_complement'])) {
            $fullAddress .= ', ' . $validated['address_complement'];
        }

        // Crear orden en WooCommerce con múltiples line items
        $lineItems = array_map(fn ($item) => [
            'product_id' => $item['woocommerce_id'],
            'quantity'   => $item['quantity'],
        ], $cartItems);

        $orderResult = $this->wooCommerceService->createOrder($chatbot, [
            'line_items' => $lineItems,
            // Legacy single product (para compatibilidad con WooCommerceService)
            'product_id'   => $cartItems[0]['woocommerce_id'],
            'quantity'     => $cartItems[0]['quantity'],
            'first_name'   => $validated['first_name'],
            'last_name'    => $validated['last_name'],
            'email'        => $validated['email'],
            'phone'        => $validated['phone'],
            'address'      => $fullAddress,
            'city'         => $validated['city'],
            'state'        => $validated['department'],
            'address_type' => $validated['address_type'] ?? 'Casa',
            'notes'        => $validated['notes'] ?? '',
        ]);

        if (! $orderResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $orderResult['message'],
            ], 500);
        }

        // Generar payment link con Wompi
        // Usamos el primer producto para el link pero con el total del carrito
        $paymentLinkResult = $this->wompiService->generatePaymentLinkForCart(
            $chatbot,
            $cartItems,
            $total,
            [
                'email' => $validated['email'],
                'name'  => $validated['first_name'] . ' ' . $validated['last_name'],
                'phone' => $validated['phone'],
            ],
            $orderResult['order_id'] ?? null
        );

        // Fallback al método legacy si no existe generatePaymentLinkForCart
        if (! $paymentLinkResult['success'] && method_exists($this->wompiService, 'generatePaymentLink')) {
            $paymentLinkResult = $this->wompiService->generatePaymentLink(
                $chatbot,
                $cartItems[0]['product'],
                [
                    'email' => $validated['email'],
                    'name'  => $validated['first_name'] . ' ' . $validated['last_name'],
                    'phone' => $validated['phone'],
                ],
                $cartItems[0]['quantity'],
                $orderResult['order_id'] ?? null
            );
        }

        if (! $paymentLinkResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar link de pago: ' . ($paymentLinkResult['message'] ?? 'Error desconocido'),
            ], 500);
        }

        return response()->json([
            'success'         => true,
            'order_id'        => $orderResult['order_id'],
            'payment_link'    => $paymentLinkResult['payment_link'],
            'total'           => $total,
            'formatted_total' => '$' . number_format($total, 0, ',', '.') . ' COP',
            'items_count'     => count($cartItems),
        ]);
    }
}
