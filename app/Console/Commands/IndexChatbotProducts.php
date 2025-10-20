<?php

namespace App\Console\Commands;

use App\Extensions\Chatbot\System\Enums\EmbeddingTypeEnum;
use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotEmbedding;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IndexChatbotProducts extends Command
{
    protected $signature = 'chatbot:index-products {chatbot_id?}';
    
    protected $description = 'Indexar productos de WooCommerce en el vector store del AI';

    public function handle()
    {
        $chatbotId = $this->argument('chatbot_id');
        
        if ($chatbotId) {
            $chatbots = Chatbot::where('id', $chatbotId)->get();
        } else {
            $chatbots = Chatbot::where('woocommerce_enabled', true)
                ->where('sales_agent_enabled', true)
                ->get();
        }

        if ($chatbots->isEmpty()) {
            $this->error('No se encontraron chatbots con WooCommerce habilitado');
            return 1;
        }

        foreach ($chatbots as $chatbot) {
            $this->info("Indexando productos para chatbot: {$chatbot->title} (ID: {$chatbot->id})");
            
            $products = ChatbotProduct::where('chatbot_id', $chatbot->id)
                ->where('is_active', true)
                ->get();

            if ($products->isEmpty()) {
                $this->warn("  No hay productos para indexar");
                continue;
            }

            $indexed = 0;
            foreach ($products as $product) {
                try {
                    $content = $this->generateProductContent($product);

                    ChatbotEmbedding::updateOrCreate(
                        [
                            'chatbot_id' => $chatbot->id,
                            'type' => EmbeddingTypeEnum::product,
                            'title' => 'Producto: ' . $product->name,
                        ],
                        [
                            'content' => $content,
                            'url' => $product->product_url,
                            'engine' => 'openai',
                        ]
                    );

                    $indexed++;
                    $this->info("  ✓ {$product->name}");
                } catch (\Exception $e) {
                    $this->error("  ✗ Error con {$product->name}: {$e->getMessage()}");
                    Log::error("Error indexing product {$product->id}: {$e->getMessage()}");
                }
            }

            $this->info("✅ Indexados {$indexed} productos para el chatbot {$chatbot->title}");
        }

        return 0;
    }

    protected function generateProductContent(ChatbotProduct $product): string
    {
        $parts = [];

        $parts[] = "Producto: {$product->name}";
        
        if ($product->sku) {
            $parts[] = "SKU/Código: {$product->sku}";
        }

        $price = number_format($product->price, 0, ',', '.');
        $parts[] = "Precio: \${$price} COP";

        if ($product->sale_price && $product->sale_price < $product->regular_price) {
            $regularPrice = number_format($product->regular_price, 0, ',', '.');
            $discount = round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100);
            $parts[] = "Precio anterior: \${$regularPrice} COP";
            $parts[] = "Descuento: {$discount}% OFF";
        }

        if ($product->description) {
            $cleanDesc = strip_tags($product->description);
            $parts[] = "Descripción: {$cleanDesc}";
        } elseif ($product->short_description) {
            $cleanShortDesc = strip_tags($product->short_description);
            $parts[] = "Descripción: {$cleanShortDesc}";
        }

        if (!empty($product->categories)) {
            $categories = collect($product->categories)->pluck('name')->implode(', ');
            $parts[] = "Categorías: {$categories}";
        }

        $parts[] = $product->in_stock ? 'Disponible en stock' : 'Agotado';
        
        if ($product->stock_quantity && $product->in_stock) {
            $parts[] = "Stock disponible: {$product->stock_quantity} unidades";
        }

        if ($product->product_url) {
            $parts[] = "Ver producto: {$product->product_url}";
        }

        return implode("\n", $parts);
    }
}

