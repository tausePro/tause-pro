<?php

declare(strict_types=1);

namespace App\Extensions\ChatbotSalesAgent\System\Http\Controllers;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Services\WooCommerceService;
use App\Extensions\Chatbot\System\Services\WompiService;
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
        if (!$chatbot->woocommerce_enabled) {
            return redirect()
                ->route('dashboard.chatbot.ecommerce.index', $chatbot)
                ->with('error', '⚠️ Primero debes configurar WooCommerce en el dashboard de E-commerce');
        }

        if (!$chatbot->wompi_enabled) {
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
                    'id' => $product->id,
                    'woocommerce_id' => $product->woocommerce_id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $price,
                    'formatted_price' => '$' . number_format($price, 0, ',', '.') . ' COP',
                    'image_url' => $product->image_url,
                    'sku' => $product->sku,
                    'in_stock' => (bool) $product->in_stock,
                    'stock_quantity' => $product->stock_quantity,
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
            'success' => true,
            'products' => $products->map(function ($product) {
                $price = (float) $product->price;
                return [
                    'id' => $product->id,
                    'woocommerce_id' => $product->woocommerce_id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $price,
                    'formatted_price' => '$' . number_format($price, 0, ',', '.') . ' COP',
                    'image_url' => $product->image_url,
                    'sku' => $product->sku,
                    'in_stock' => $product->in_stock,
                    'stock_quantity' => $product->stock_quantity,
                ];
            }),
        ]);
    }

    /**
     * Crear orden en WooCommerce desde el chat externo o dashboard
     */
    public function createOrder(Request $request, Chatbot $chatbot): JsonResponse
    {
        // Para API pública, no requiere auth
        // Para dashboard, verificar propiedad
        if (!$request->is('api/*') && $chatbot->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:ext_chatbot_products,id',
            'quantity' => 'required|integer|min:1',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'department' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:500',
            'address_type' => 'required|string|in:Casa,Apartamento,Oficina',
            'address_complement' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        // Obtener producto
        $product = $chatbot->products()->findOrFail($request->product_id);

        // Verificar stock
        if (!$product->in_stock || ($product->stock_quantity && $product->stock_quantity < $request->quantity)) {
            return response()->json([
                'success' => false,
                'message' => 'Producto sin stock suficiente',
            ], 400);
        }

        // Preparar dirección completa
        $fullAddress = $validated['address'];
        if (!empty($validated['address_complement'])) {
            $fullAddress .= ', ' . $validated['address_complement'];
        }

        // Crear orden en WooCommerce
        $orderResult = $this->wooCommerceService->createOrder($chatbot, [
            'product_id' => $product->woocommerce_id,
            'quantity' => $validated['quantity'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $fullAddress,
            'city' => $validated['city'],
            'state' => $validated['department'],
            'address_type' => $validated['address_type'],
            'notes' => $validated['notes'] ?? '',
        ]);

        if (!$orderResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $orderResult['message'],
            ], 500);
        }

        // Generar payment link con Wompi
        $total = $product->price * $validated['quantity'];
        
        $paymentLinkResult = $this->wompiService->generatePaymentLink(
            $chatbot,
            $product,
            [
                'email' => $validated['email'],
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'phone' => $validated['phone'],
            ],
            $validated['quantity'],
            $orderResult['order_id'] ?? null
        );

        if (!$paymentLinkResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar link de pago: ' . $paymentLinkResult['message'],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'order_id' => $orderResult['order_id'],
            'payment_link' => $paymentLinkResult['payment_link'],
            'total' => $total,
            'formatted_total' => '$' . number_format($total, 0, ',', '.') . ' COP',
        ]);
    }
}

