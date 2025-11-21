<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Services\WompiService;
use App\Extensions\Chatbot\System\Services\WooCommerceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChatbotEcommerceController extends Controller
{
    public function __construct(
        protected WooCommerceService $wooCommerceService,
        protected WompiService $wompiService
    ) {}

    /**
     * Mostrar dashboard de E-commerce
     */
    public function index(Chatbot $chatbot)
    {
        // Verificar que el usuario sea dueño del chatbot
        if ($chatbot->user_id !== auth()->id()) {
            abort(403, 'This action is unauthorized.');
        }

        // Cargar productos con paginación, incluso si no hay productos
        $products = $chatbot->products()
            ->latest('last_synced_at')
            ->paginate(20);

        // Log para debugging
        Log::info('Ecommerce index loaded', [
            'chatbot_id'             => $chatbot->id,
            'woocommerce_enabled'    => $chatbot->woocommerce_enabled,
            'products_count'         => $products->count(),
            'total_products'         => $products->total(),
            'has_woocommerce_config' => ! empty($chatbot->woocommerce_url) && ! empty($chatbot->woocommerce_key),
        ]);

        return view('chatbot::ecommerce.index', compact('chatbot', 'products'));
    }

    /**
     * Guardar configuración de WooCommerce
     */
    public function saveWooCommerceConfig(Request $request, Chatbot $chatbot)
    {
        if ($chatbot->user_id !== auth()->id()) {
            abort(403, 'This action is unauthorized.');
        }

        $validator = Validator::make($request->all(), [
            'woocommerce_url'     => 'required|url',
            'woocommerce_key'     => 'required|string',
            'woocommerce_secret'  => 'required|string',
            'woocommerce_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Testear conexión antes de guardar
        $testResult = $this->wooCommerceService->testConnection(
            $request->woocommerce_url,
            $request->woocommerce_key,
            $request->woocommerce_secret
        );

        if (! $testResult['success']) {
            return back()->with('error', $testResult['message'])->withInput();
        }

        // Guardar configuración
        $chatbot->update([
            'woocommerce_url'     => $request->woocommerce_url,
            'woocommerce_key'     => $request->woocommerce_key,
            'woocommerce_secret'  => $request->woocommerce_secret,
            'woocommerce_enabled' => $request->boolean('woocommerce_enabled'),
        ]);

        return back()->with('success', '✅ Configuración de WooCommerce guardada exitosamente');
    }

    /**
     * Sincronizar productos de WooCommerce
     */
    public function syncProducts(Chatbot $chatbot)
    {
        Log::info('🔵 SYNC PRODUCTS CONTROLLER CALLED', [
            'chatbot_id'      => $chatbot->id,
            'user_id'         => auth()->id(),
            'chatbot_user_id' => $chatbot->user_id,
        ]);

        if ($chatbot->user_id !== auth()->id()) {
            Log::warning('🔴 UNAUTHORIZED SYNC ATTEMPT', [
                'chatbot_id'    => $chatbot->id,
                'auth_user'     => auth()->id(),
                'chatbot_owner' => $chatbot->user_id,
            ]);
            abort(403, 'This action is unauthorized.');
        }

        $result = $this->wooCommerceService->syncProducts($chatbot);

        if ($result['success']) {
            $chatbot->update(['woocommerce_last_sync' => now()]);

            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Guardar configuración de Wompi
     */
    public function saveWompiConfig(Request $request, Chatbot $chatbot)
    {
        if ($chatbot->user_id !== auth()->id()) {
            abort(403, 'This action is unauthorized.');
        }

        $validator = Validator::make($request->all(), [
            'wompi_public_key'  => 'required|string',
            'wompi_private_key' => 'required|string',
            'wompi_environment' => 'required|in:test,production',
            'wompi_enabled'     => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Testear conexión
        $testResult = $this->wompiService->testConnection(
            $request->wompi_public_key,
            $request->wompi_private_key,
            $request->wompi_environment
        );

        if (! $testResult['success']) {
            return back()->with('error', $testResult['message'])->withInput();
        }

        // Guardar configuración
        $chatbot->update([
            'wompi_public_key'  => $request->wompi_public_key,
            'wompi_private_key' => $request->wompi_private_key,
            'wompi_environment' => $request->wompi_environment,
            'wompi_enabled'     => $request->boolean('wompi_enabled'),
        ]);

        return back()->with('success', '✅ Configuración de Wompi guardada exitosamente');
    }

    /**
     * Guardar configuración de ePayco
     */
    public function saveEpaycoConfig(Request $request, Chatbot $chatbot)
    {
        if ($chatbot->user_id !== auth()->id()) {
            abort(403, 'This action is unauthorized.');
        }

        $validator = Validator::make($request->all(), [
            'epayco_public_key'  => 'required|string',
            'epayco_private_key' => 'required|string',
            'epayco_environment' => 'required|in:test,production',
            'epayco_enabled'     => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Guardar configuración (ePayco no requiere test de conexión por ahora)
        $chatbot->update([
            'epayco_public_key'  => $request->epayco_public_key,
            'epayco_private_key' => $request->epayco_private_key,
            'epayco_environment' => $request->epayco_environment,
            'epayco_enabled'     => $request->boolean('epayco_enabled'),
        ]);

        // Asegurar que el Sales Agent tenga la configuración actualizada
        $chatbotService = app(\App\Extensions\Chatbot\System\Services\ChatbotService::class);
        if ($chatbot->sales_agent_enabled) {
            $chatbotService->ensureSalesAgent($chatbot);
        }

        return back()->with('success', '✅ Configuración de ePayco guardada exitosamente');
    }

    /**
     * Guardar configuración del Sales Agent
     */
    public function saveSalesAgentConfig(Request $request, Chatbot $chatbot)
    {
        if ($chatbot->user_id !== auth()->id()) {
            abort(403, 'This action is unauthorized.');
        }

        $validator = Validator::make($request->all(), [
            'sales_agent_enabled'    => 'boolean',
            'sales_agent_keywords'   => 'nullable|array',
            'sales_agent_keywords.*' => 'string',
            'agent_name'             => 'nullable|string|max:255',
            'agent_description'      => 'nullable|string',
            'tone'                   => 'nullable|in:formal,casual,friendly',
            'sales_strategy'         => 'nullable|in:consultative,aggressive,helpful',
            'search_strategy'        => 'nullable|in:keyword,semantic,hybrid',
            'product_display_mode'   => 'nullable|in:conversational,cards,both',
            'custom_prompt'          => 'nullable|string',
            'product_card_config'    => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Preparar datos para actualizar
        $updateData = [
            'sales_agent_enabled'  => $request->boolean('sales_agent_enabled'),
            'sales_agent_keywords' => $request->sales_agent_keywords ?? [],
        ];

        // Agregar configuración avanzada si está presente
        if ($request->has('agent_name')) {
            $updateData['sales_agent_name'] = $request->agent_name;
        }
        if ($request->has('agent_description')) {
            $updateData['sales_agent_description'] = $request->agent_description;
        }
        if ($request->has('tone')) {
            $updateData['sales_agent_tone'] = $request->tone;
        }
        if ($request->has('sales_strategy')) {
            $updateData['sales_agent_strategy'] = $request->sales_strategy;
        }
        if ($request->has('search_strategy')) {
            $updateData['sales_agent_search_strategy'] = $request->search_strategy;
        }
        if ($request->has('product_display_mode')) {
            $updateData['sales_agent_display_mode'] = $request->product_display_mode;
        }
        if ($request->has('custom_prompt')) {
            $updateData['sales_agent_custom_prompt'] = $request->custom_prompt;
        }
        if ($request->has('product_card_config')) {
            $updateData['sales_agent_card_config'] = json_encode($request->product_card_config);
        }

        // Guardar configuración
        $chatbot->update($updateData);

        return redirect()
            ->route('dashboard.chatbot.ecommerce.index', $chatbot)
            ->with('success', '✅ Configuración del Agente de Ventas guardada');
    }

    /**
     * Eliminar producto
     */
    public function deleteProduct(Chatbot $chatbot, $productId)
    {
        if ($chatbot->user_id !== auth()->id()) {
            abort(403, 'This action is unauthorized.');
        }

        $product = $chatbot->products()->findOrFail($productId);
        $product->delete();

        return back()->with('success', '✅ Producto eliminado');
    }

    /**
     * Activar/Desactivar producto
     */
    public function toggleProduct(Chatbot $chatbot, $productId)
    {
        if ($chatbot->user_id !== auth()->id()) {
            abort(403, 'This action is unauthorized.');
        }

        $product = $chatbot->products()->findOrFail($productId);
        $product->update(['is_active' => ! $product->is_active]);

        $status = $product->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "✅ Producto {$status}");
    }

    /**
     * Generar cupón dinámico para negociación
     */
    public function generateCoupon(Request $request, Chatbot $chatbot)
    {
        // Validar que negotiation esté habilitado
        if (! $chatbot->negotiation_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Negociación no habilitada para este chatbot',
            ], 403);
        }

        // Validar datos
        $validator = Validator::make($request->all(), [
            'cart_value' => 'required|numeric|min:0',
            'reason'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $cartValue = $request->get('cart_value');

        // Verificar valor mínimo del carrito
        if ($cartValue < $chatbot->negotiation_min_cart_value) {
            return response()->json([
                'success'      => false,
                'message'      => 'El valor del carrito no alcanza el mínimo para negociar',
                'min_required' => $chatbot->negotiation_min_cart_value,
            ], 400);
        }

        // Generar cupón
        $result = $this->wooCommerceService->createDynamicCoupon($chatbot, [
            'discount'       => $chatbot->negotiation_max_discount,
            'duration'       => $chatbot->negotiation_coupon_duration,
            'min_cart_value' => $chatbot->negotiation_min_cart_value,
        ]);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'coupon'  => $result['coupon'],
                'message' => "¡Tengo algo especial para ti! 🎁\n\n" .
                            "Cupón: **{$result['coupon']['code']}**\n" .
                            "Descuento: **{$result['coupon']['discount']}%**\n" .
                            "Válido por: **{$result['coupon']['expires_in_minutes']} minutos**\n\n" .
                            '¡Aprovecha esta oferta exclusiva! ⏰',
            ]);
        }

        return response()->json($result, 500);
    }
}
