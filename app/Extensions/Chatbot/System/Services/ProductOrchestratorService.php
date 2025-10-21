<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Servicio de Orquestación entre External Chatbot y Sales Agent
 * 
 * Este servicio detecta cuando el AI menciona productos en su respuesta
 * y busca productos relevantes para activar el Sales Agent
 */
class ProductOrchestratorService
{
    /**
     * Detecta si la respuesta del AI menciona productos y retorna productos estructurados
     */
    public function orchestrate(Chatbot $chatbot, string $aiResponse, string $userQuery): array
    {
        // Si Sales Agent no está habilitado, retornar solo el mensaje
        if (!$chatbot->sales_agent_enabled) {
            return [
                'message' => $aiResponse,
                'products' => [],
                'show_sales_grid' => false,
            ];
        }

        // Detectar si debe activar Sales Agent
        $shouldActivate = $this->shouldActivateSalesAgent($aiResponse, $userQuery, $chatbot);

        if (!$shouldActivate) {
            return [
                'message' => $aiResponse,
                'products' => [],
                'show_sales_grid' => false,
            ];
        }

        // Buscar productos mencionados
        $products = $this->findMentionedProducts($chatbot, $aiResponse, $userQuery);

        return [
            'message' => $aiResponse,
            'products' => $products,
            'show_sales_grid' => $products->isNotEmpty(),
            'orchestration_metadata' => [
                'detected_keywords' => $this->extractKeywords($aiResponse, $userQuery),
                'confidence' => $this->calculateConfidence($aiResponse, $products),
            ],
        ];
    }

    /**
     * Determina si debe activar el Sales Agent
     */
    protected function shouldActivateSalesAgent(string $aiResponse, string $userQuery, Chatbot $chatbot): bool
    {
        $text = strtolower($aiResponse . ' ' . $userQuery);

        // Keywords configurables del chatbot
        $configuredKeywords = $chatbot->sales_agent_keywords ?? [];
        
        // Keywords por defecto
        $defaultKeywords = [
            'producto', 'productos', 'comprar', 'precio', 'precios', 'costo', 'costos',
            'vender', 'venta', 'disponible', 'stock', 'inventario', 'catálogo',
            'tienda', 'adquirir', 'pagar', 'pago', 'pedido', 'orden',
        ];

        $allKeywords = array_merge($defaultKeywords, $configuredKeywords);

        // Verificar si contiene algún keyword
        foreach ($allKeywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Busca productos mencionados en la respuesta del AI
     */
    protected function findMentionedProducts(Chatbot $chatbot, string $aiResponse, string $userQuery): Collection
    {
        $searchText = $aiResponse . ' ' . $userQuery;
        
        // Obtener todos los productos activos y en stock
        $allProducts = ChatbotProduct::where('chatbot_id', $chatbot->id)
            ->where('availability', 'in_stock')
            ->get();

        if ($allProducts->isEmpty()) {
            return collect([]);
        }

        $mentionedProducts = collect([]);

        // Buscar productos cuyo nombre aparece en el texto
        foreach ($allProducts as $product) {
            $productName = strtolower($product->name);
            $searchTextLower = strtolower($searchText);

            // Buscar coincidencia exacta o parcial del nombre del producto
            if (str_contains($searchTextLower, $productName)) {
                $mentionedProducts->push($this->formatProduct($product));
                continue;
            }

            // Buscar por palabras clave del nombre del producto
            $productWords = explode(' ', $productName);
            $significantWords = array_filter($productWords, function($word) {
                return strlen($word) > 3; // Solo palabras significativas
            });

            foreach ($significantWords as $word) {
                if (str_contains($searchTextLower, $word)) {
                    $mentionedProducts->push($this->formatProduct($product));
                    break;
                }
            }
        }

        // Si no se encontraron productos específicos, retornar los más relevantes
        if ($mentionedProducts->isEmpty()) {
            return $allProducts->take(6)->map(fn($p) => $this->formatProduct($p));
        }

        // Limitar a 6 productos para no saturar el UI
        return $mentionedProducts->take(6);
    }

    /**
     * Formatea un producto para el frontend
     */
    protected function formatProduct(ChatbotProduct $product): array
    {
        $price = (float) $product->price;
        
        return [
            'id' => $product->id,
            'woocommerce_id' => $product->woocommerce_id,
            'name' => $product->name,
            'description' => $product->description ? Str::limit(strip_tags($product->description), 150) : '',
            'short_description' => $product->short_description,
            'price' => $price,
            'formatted_price' => '$' . number_format($price, 0, ',', '.') . ' COP',
            'image_url' => $product->image_url,
            'sku' => $product->sku,
            'in_stock' => true,
            'stock_quantity' => $product->stock_quantity,
            'product_url' => $product->product_url ?? $product->purchase_url,
        ];
    }

    /**
     * Extrae keywords detectados
     */
    protected function extractKeywords(string $aiResponse, string $userQuery): array
    {
        $text = strtolower($aiResponse . ' ' . $userQuery);
        $keywords = [
            'producto', 'productos', 'comprar', 'precio', 'precios', 
            'disponible', 'stock', 'venta', 'catálogo'
        ];

        return array_values(array_filter($keywords, function($keyword) use ($text) {
            return str_contains($text, $keyword);
        }));
    }

    /**
     * Calcula confianza de la detección (0-100)
     */
    protected function calculateConfidence(string $aiResponse, Collection $products): int
    {
        $confidence = 0;

        // +40 si encontró productos
        if ($products->isNotEmpty()) {
            $confidence += 40;
        }

        // +20 por cada keyword comercial detectado (max 60)
        $commercialPhrases = ['tenemos', 'disponible', 'precio', 'cuesta', 'puedes comprar'];
        $detectedPhrases = 0;
        foreach ($commercialPhrases as $phrase) {
            if (str_contains(strtolower($aiResponse), $phrase)) {
                $detectedPhrases++;
            }
        }
        $confidence += min($detectedPhrases * 20, 60);

        return min($confidence, 100);
    }
}
