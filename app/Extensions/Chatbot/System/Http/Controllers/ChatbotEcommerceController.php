<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Services\WooCommerceService;
use App\Extensions\Chatbot\System\Services\WompiService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

        $products = $chatbot->products()
            ->active()
            ->latest('last_synced_at')
            ->paginate(20);

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
            'woocommerce_url' => 'required|url',
            'woocommerce_key' => 'required|string',
            'woocommerce_secret' => 'required|string',
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

        if (!$testResult['success']) {
            return back()->with('error', $testResult['message'])->withInput();
        }

        // Guardar configuración
        $chatbot->update([
            'woocommerce_url' => $request->woocommerce_url,
            'woocommerce_key' => $request->woocommerce_key,
            'woocommerce_secret' => $request->woocommerce_secret,
            'woocommerce_enabled' => $request->boolean('woocommerce_enabled'),
        ]);

        return back()->with('success', '✅ Configuración de WooCommerce guardada exitosamente');
    }

    /**
     * Sincronizar productos de WooCommerce
     */
    public function syncProducts(Chatbot $chatbot)
    {
        if ($chatbot->user_id !== auth()->id()) {
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
            'wompi_public_key' => 'required|string',
            'wompi_private_key' => 'required|string',
            'wompi_environment' => 'required|in:test,production',
            'wompi_enabled' => 'boolean',
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

        if (!$testResult['success']) {
            return back()->with('error', $testResult['message'])->withInput();
        }

        // Guardar configuración
        $chatbot->update([
            'wompi_public_key' => $request->wompi_public_key,
            'wompi_private_key' => $request->wompi_private_key,
            'wompi_environment' => $request->wompi_environment,
            'wompi_enabled' => $request->boolean('wompi_enabled'),
        ]);

        return back()->with('success', '✅ Configuración de Wompi guardada exitosamente');
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
            'enabled' => 'boolean',
            'agent_name' => 'required|string|max:100',
            'agent_description' => 'nullable|string|max:500',
            'tone' => 'required|in:formal,casual,friendly',
            'sales_strategy' => 'required|in:consultative,aggressive,helpful',
            'search_strategy' => 'required|in:keyword,semantic,hybrid',
            'product_display_mode' => 'required|in:conversational,cards,both',
            'custom_prompt' => 'nullable|string|max:2000',
            'auto_activate' => 'boolean',
            'activation_keywords' => 'nullable|array',
            'activation_keywords.*' => 'string',
            'product_card_config' => 'nullable|array',
            'product_card_config.button_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'product_card_config.button_text_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'product_card_config.button_style' => 'nullable|in:solid,gradient,outline',
            'product_card_config.card_border_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'product_card_config.card_shadow' => 'nullable|in:none,sm,md,lg',
            'product_card_config.price_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'product_card_config.discount_badge_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'product_card_config.show_stock_indicator' => 'nullable|boolean',
            'product_card_config.show_discount_badge' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Guardar configuración en SalesAgentConfig
        $chatbot->salesAgentConfig()->updateOrCreate(
            ['chatbot_id' => $chatbot->id],
            $validator->validated()
        );

        return redirect()
            ->route('dashboard.chatbot.ecommerce.index', $chatbot)
            ->with('success', '✅ Configuración del Agente de Ventas guardada exitosamente');
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
        $product->update(['is_active' => !$product->is_active]);

        $status = $product->is_active ? 'activado' : 'desactivado';
        return back()->with('success', "✅ Producto {$status}");
    }
}

