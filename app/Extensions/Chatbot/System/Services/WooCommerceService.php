<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Enums\EmbeddingTypeEnum;
use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotEmbedding;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WooCommerceService
{
    /**
     * Sincronizar productos desde WooCommerce
     */
    public function syncProducts(Chatbot $chatbot): array
    {
        try {
            Log::info('🟢 Starting WooCommerce sync', [
                'chatbot_id'   => $chatbot->id,
                'chatbot_name' => $chatbot->name,
                'woo_url'      => $chatbot->woocommerce_url,
                'woo_enabled'  => $chatbot->woocommerce_enabled,
                'has_key'      => ! empty($chatbot->woocommerce_key),
                'has_secret'   => ! empty($chatbot->woocommerce_secret),
            ]);

            // Validar configuración
            if (! $this->hasValidConfiguration($chatbot)) {
                Log::warning('🔴 Invalid WooCommerce configuration', [
                    'chatbot_id' => $chatbot->id,
                    'url'        => $chatbot->woocommerce_url ?? 'missing',
                    'key'        => ! empty($chatbot->woocommerce_key) ? 'present' : 'missing',
                    'secret'     => ! empty($chatbot->woocommerce_secret) ? 'present' : 'missing',
                ]);

                return [
                    'success' => false,
                    'message' => '❌ Configuración de WooCommerce incompleta. Verifica que URL, Consumer Key y Consumer Secret estén configurados.',
                ];
            }

            $config = $this->getConfiguration($chatbot);

            Log::info('🟡 Fetching products from WooCommerce', [
                'chatbot_id' => $chatbot->id,
                'api_url'    => rtrim($config['url'], '/') . '/wp-json/wc/v3/products',
            ]);

            // Obtener productos de WooCommerce
            $products = $this->fetchProductsFromWooCommerce($config);

            Log::info('🟢 Products fetched from WooCommerce', [
                'chatbot_id' => $chatbot->id,
                'count'      => count($products),
            ]);

            if (empty($products)) {
                Log::warning('🟡 No products found in WooCommerce', [
                    'chatbot_id' => $chatbot->id,
                    'woo_url'    => $config['url'],
                ]);

                return [
                    'success' => false,
                    'message' => '⚠️ No se encontraron productos publicados en WooCommerce. Asegúrate de que tu tienda tenga productos con estado "publicado".',
                ];
            }

            // Sincronizar productos
            $synced = 0;
            $errors = 0;
            $productIds = [];

            foreach ($products as $wooProduct) {
                try {
                    $product = $this->syncProduct($chatbot, $wooProduct);
                    $productIds[] = $product->id;
                    $synced++;
                } catch (Exception $e) {
                    Log::error('Error syncing product: ' . $e->getMessage(), [
                        'product_id' => $wooProduct['id'] ?? 'unknown',
                    ]);
                    $errors++;
                }
            }

            // Indexar productos para el AI (embeddings)
            $this->indexProductsForAI($chatbot, $productIds);

            return [
                'success' => true,
                'message' => "Sincronizados {$synced} productos e indexados para el AI",
                'synced'  => $synced,
                'errors'  => $errors,
                'total'   => count($products),
            ];

        } catch (Exception $e) {
            Log::error('🔴 WooCommerce sync error', [
                'chatbot_id' => $chatbot->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al sincronizar: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sincronizar un producto individual
     */
    protected function syncProduct(Chatbot $chatbot, array $wooProduct)
    {
        return ChatbotProduct::updateOrCreate(
            [
                'chatbot_id'     => $chatbot->id,
                'woocommerce_id' => (string) $wooProduct['id'],
            ],
            [
                'user_id'           => $chatbot->user_id,
                'sku'               => $wooProduct['sku'] ?? null,
                'name'              => $wooProduct['name'],
                'description'       => $wooProduct['description'] ?? null,
                'short_description' => $wooProduct['short_description'] ?? null,
                'price'             => (float) ($wooProduct['price'] ?? 0),
                'regular_price'     => ! empty($wooProduct['regular_price']) ? (float) $wooProduct['regular_price'] : null,
                'sale_price'        => ! empty($wooProduct['sale_price']) ? (float) $wooProduct['sale_price'] : null,
                'image_url'         => $wooProduct['images'][0]['src'] ?? null,
                'gallery_urls'      => $this->extractGalleryUrls($wooProduct['images'] ?? []),
                'in_stock'          => $wooProduct['stock_status'] === 'instock',
                'stock_quantity'    => $wooProduct['manage_stock'] ? ($wooProduct['stock_quantity'] ?? 0) : 100,
                'categories'        => $this->extractCategories($wooProduct['categories'] ?? []),
                'tags'              => $this->extractTags($wooProduct['tags'] ?? []),
                'product_url'       => $wooProduct['permalink'] ?? null,
                'metadata'          => [
                    'type'       => $wooProduct['type'] ?? 'simple',
                    'featured'   => $wooProduct['featured'] ?? false,
                    'attributes' => $wooProduct['attributes'] ?? [],
                ],
                'last_synced_at' => now(),
                'is_active'      => true,
            ]
        );
    }

    /**
     * Indexar productos en el vector store del AI
     */
    protected function indexProductsForAI(Chatbot $chatbot, array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        $products = ChatbotProduct::whereIn('id', $productIds)
            ->where('is_active', true)
            ->get();

        foreach ($products as $product) {
            // Crear contenido rico para indexación
            $content = $this->generateProductContent($product);

            // Crear o actualizar embedding
            ChatbotEmbedding::updateOrCreate(
                [
                    'chatbot_id' => $chatbot->id,
                    'type'       => EmbeddingTypeEnum::product,
                    'title'      => 'Producto: ' . $product->name,
                ],
                [
                    'content' => $content,
                    'url'     => $product->product_url,
                    'engine'  => 'openai',
                ]
            );
        }

        Log::info("Indexed {$products->count()} products for AI", [
            'chatbot_id' => $chatbot->id,
        ]);
    }

    /**
     * Generar contenido enriquecido del producto para mejor búsqueda
     */
    protected function generateProductContent(ChatbotProduct $product): string
    {
        $parts = [];

        // Nombre y descripción
        $parts[] = "Producto: {$product->name}";

        if ($product->sku) {
            $parts[] = "SKU/Código: {$product->sku}";
        }

        // Precio
        $price = number_format((float) $product->price, 0, ',', '.');
        $parts[] = "Precio: \${$price} COP";

        if ($product->sale_price && $product->sale_price < $product->regular_price) {
            $regularPrice = number_format((float) $product->regular_price, 0, ',', '.');
            $discount = round((((float) $product->regular_price - (float) $product->sale_price) / (float) $product->regular_price) * 100);
            $parts[] = "Precio anterior: \${$regularPrice} COP";
            $parts[] = "Descuento: {$discount}% OFF";
        }

        // Descripción completa
        if ($product->description) {
            $cleanDesc = strip_tags($product->description);
            $parts[] = "Descripción: {$cleanDesc}";
        } elseif ($product->short_description) {
            $cleanShortDesc = strip_tags($product->short_description);
            $parts[] = "Descripción: {$cleanShortDesc}";
        }

        // Categorías
        if (! empty($product->categories)) {
            $categories = collect($product->categories)->pluck('name')->implode(', ');
            $parts[] = "Categorías: {$categories}";
        }

        // Stock
        $parts[] = $product->in_stock ? 'Disponible en stock' : 'Agotado';

        if ($product->stock_quantity && $product->in_stock) {
            $parts[] = "Stock disponible: {$product->stock_quantity} unidades";
        }

        // URL para comprar
        if ($product->product_url) {
            $parts[] = "Ver producto: {$product->product_url}";
        }

        return implode("\n", $parts);
    }

    /**
     * Obtener productos desde WooCommerce API
     */
    protected function fetchProductsFromWooCommerce(array $config): array
    {
        $url = rtrim($config['url'], '/') . '/wp-json/wc/v3/products';

        Log::info('🟡 Making WooCommerce API request', [
            'url'      => $url,
            'per_page' => 100,
            'status'   => 'publish',
            'key'      => substr($config['key'], 0, 10) . '...', // Solo primeros caracteres para log
        ]);

        try {
            $response = Http::withBasicAuth($config['key'], $config['secret'])
                ->timeout(30)
                ->get($url, [
                    'per_page' => 100, // Máximo por página
                    'status'   => 'publish',
                ]);

            Log::info('🟡 WooCommerce API response received', [
                'status_code' => $response->status(),
                'headers'     => $response->headers(),
            ]);

            if (! $response->successful()) {
                $statusCode = $response->status();
                $body = $response->body();

                Log::error('🔴 WooCommerce API error', [
                    'status'     => $statusCode,
                    'body'       => substr($body, 0, 500), // Primeros 500 caracteres
                    'full_body'  => $body, // Cuerpo completo para debugging
                ]);

                if ($statusCode === 401) {
                    throw new Exception('Error de autenticación (401). Verifica que Consumer Key y Consumer Secret sean correctos y tengan permisos de lectura.');
                } elseif ($statusCode === 404) {
                    throw new Exception('URL de WooCommerce no encontrada (404). Verifica que la URL sea correcta y que WooCommerce REST API esté habilitada en tu tienda.');
                } elseif ($statusCode === 403) {
                    throw new Exception('Acceso denegado (403). Verifica que las credenciales API tengan permisos de lectura y que WooCommerce REST API esté habilitada.');
                } else {
                    $errorDetails = strlen($body) > 0 ? substr($body, 0, 500) : 'Sin detalles del servidor';
                    throw new Exception("Error al conectar con WooCommerce: HTTP {$statusCode}. {$errorDetails}");
                }
            }

            $products = $response->json();

            if (! is_array($products)) {
                Log::warning('🟡 Invalid response from WooCommerce', [
                    'response_type'    => gettype($products),
                    'response_preview' => substr(json_encode($products), 0, 500),
                    'full_response'    => json_encode($products),
                ]);

                return [];
            }

            Log::info('🟢 Products parsed successfully', [
                'count' => count($products),
            ]);

        // Si hay más páginas, obtenerlas también
        $totalPages = (int) $response->header('X-WP-TotalPages');

        if ($totalPages > 1) {
            for ($page = 2; $page <= $totalPages; $page++) {
                $pageResponse = Http::withBasicAuth($config['key'], $config['secret'])
                    ->timeout(30)
                    ->get($url, [
                        'per_page' => 100,
                        'status'   => 'publish',
                        'page'     => $page,
                    ]);

                if ($pageResponse->successful()) {
                    $products = array_merge($products, $pageResponse->json());
                }
            }
        }

        return $products;
    }

    /**
     * Extraer URLs de galería
     */
    protected function extractGalleryUrls(array $images): array
    {
        return array_map(fn ($img) => $img['src'], $images);
    }

    /**
     * Extraer categorías
     */
    protected function extractCategories(array $categories): array
    {
        return array_map(fn ($cat) => [
            'id'   => $cat['id'],
            'name' => $cat['name'],
            'slug' => $cat['slug'],
        ], $categories);
    }

    /**
     * Extraer tags
     */
    protected function extractTags(array $tags): array
    {
        return array_map(fn ($tag) => [
            'id'   => $tag['id'],
            'name' => $tag['name'],
            'slug' => $tag['slug'],
        ], $tags);
    }

    /**
     * Verificar si tiene configuración válida
     */
    protected function hasValidConfiguration(Chatbot $chatbot): bool
    {
        $config = $this->getConfiguration($chatbot);

        return ! empty($config['url'])
            && ! empty($config['key'])
            && ! empty($config['secret']);
    }

    /**
     * Obtener configuración de WooCommerce del chatbot
     */
    protected function getConfiguration(Chatbot $chatbot): array
    {
        return [
            'url'    => $chatbot->woocommerce_url ?? '',
            'key'    => $chatbot->woocommerce_key ?? '',
            'secret' => $chatbot->woocommerce_secret ?? '',
        ];
    }

    /**
     * Crear orden en WooCommerce
     */
    public function createOrder(Chatbot $chatbot, array $orderData): array
    {
        try {
            if (! $this->hasValidConfiguration($chatbot)) {
                return [
                    'success' => false,
                    'message' => 'Configuración de WooCommerce incompleta',
                ];
            }

            $config = $this->getConfiguration($chatbot);
            $url = rtrim($config['url'], '/') . '/wp-json/wc/v3/orders';

            // Preparar nota del cliente
            $customerNote = 'Orden desde Chatcommerce. Envío a coordinar por WhatsApp.';
            if (! empty($orderData['notes'])) {
                $customerNote .= "\n\nNotas del cliente: " . $orderData['notes'];
            }

            // Preparar datos de la orden
            $orderPayload = [
                'status'     => 'pending', // Pending payment
                'line_items' => [
                    [
                        'product_id' => (int) $orderData['product_id'],
                        'quantity'   => (int) $orderData['quantity'],
                    ],
                ],
                'billing' => [
                    'first_name' => $orderData['first_name'],
                    'last_name'  => $orderData['last_name'],
                    'email'      => $orderData['email'],
                    'phone'      => $orderData['phone'],
                    'address_1'  => $orderData['address'],
                    'city'       => $orderData['city'],
                    'state'      => $orderData['state'],
                    'country'    => 'CO',
                    'postcode'   => '',
                ],
                'shipping' => [
                    'first_name' => $orderData['first_name'],
                    'last_name'  => $orderData['last_name'],
                    'address_1'  => $orderData['address'],
                    'city'       => $orderData['city'],
                    'state'      => $orderData['state'],
                    'country'    => 'CO',
                    'postcode'   => '',
                ],
                'shipping_lines' => [
                    [
                        'method_id'    => 'flat_rate',
                        'method_title' => 'Envío a coordinar',
                        'total'        => '0',
                    ],
                ],
                'payment_method'       => 'wompi',
                'payment_method_title' => 'Wompi - Pago online',
                'set_paid'             => false,
                'customer_note'        => $customerNote,
                'meta_data'            => [
                    [
                        'key'   => '_chatcommerce_order',
                        'value' => 'yes',
                    ],
                    [
                        'key'   => '_address_type',
                        'value' => $orderData['address_type'],
                    ],
                    [
                        'key'   => '_shipping_pending',
                        'value' => 'yes',
                    ],
                    [
                        'key'   => '_wc_order_attribution_source_type',
                        'value' => 'organic',
                    ],
                    [
                        'key'   => '_wc_order_attribution_referrer',
                        'value' => 'TausePro - Chatbot',
                    ],
                    [
                        'key'   => '_wc_order_attribution_utm_source',
                        'value' => 'tausepro',
                    ],
                    [
                        'key'   => '_created_via',
                        'value' => 'TausePro Chatbot',
                    ],
                ],
            ];

            $response = Http::withBasicAuth($config['key'], $config['secret'])
                ->timeout(30)
                ->post($url, $orderPayload);

            if (! $response->successful()) {
                Log::error('WooCommerce order creation failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Error al crear orden en WooCommerce: ' . $response->status(),
                ];
            }

            $order = $response->json();

            return [
                'success'      => true,
                'order_id'     => $order['id'],
                'order_number' => $order['number'] ?? $order['id'],
                'message'      => 'Orden creada exitosamente',
            ];

        } catch (Exception $e) {
            Log::error('WooCommerce order creation error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al crear orden: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Crear cupón dinámico en WooCommerce
     */
    public function createDynamicCoupon(Chatbot $chatbot, array $couponData): array
    {
        try {
            if (! $this->hasValidConfiguration($chatbot)) {
                return [
                    'success' => false,
                    'message' => 'Configuración de WooCommerce incompleta',
                ];
            }

            $config = $this->getConfiguration($chatbot);
            $url = rtrim($config['url'], '/') . '/wp-json/wc/v3/coupons';

            // Generar código único
            $code = 'CHAT-' . strtoupper(substr(md5((string) time() . (string) rand()), 0, 8));

            // Calcular fecha de expiración
            $expiresAt = now()->addMinutes($couponData['duration'] ?? 30);

            $response = Http::withBasicAuth($config['key'], $config['secret'])
                ->timeout(30)
                ->post($url, [
                    'code'                   => $code,
                    'discount_type'          => 'percent',
                    'amount'                 => (string) $couponData['discount'],
                    'individual_use'         => true,
                    'usage_limit'            => 1,
                    'usage_limit_per_user'   => 1,
                    'limit_usage_to_x_items' => null,
                    'date_expires'           => $expiresAt->toIso8601String(),
                    'minimum_amount'         => (string) ($couponData['min_cart_value'] ?? 0),
                    'description'            => 'Cupón generado por chatbot - Válido por ' . ($couponData['duration'] ?? 30) . ' minutos',
                    'meta_data'              => [
                        [
                            'key'   => '_chatbot_generated',
                            'value' => 'yes',
                        ],
                        [
                            'key'   => '_chatbot_id',
                            'value' => (string) $chatbot->id,
                        ],
                        [
                            'key'   => '_generated_at',
                            'value' => now()->toDateTimeString(),
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $coupon = $response->json();

                Log::info('Dynamic coupon created', [
                    'chatbot_id' => $chatbot->id,
                    'code'       => $code,
                    'discount'   => $couponData['discount'],
                    'expires_at' => $expiresAt,
                ]);

                return [
                    'success' => true,
                    'coupon'  => [
                        'code'               => $code,
                        'discount'           => $couponData['discount'],
                        'expires_at'         => $expiresAt->toDateTimeString(),
                        'expires_in_minutes' => $couponData['duration'] ?? 30,
                        'min_cart_value'     => $couponData['min_cart_value'] ?? 0,
                    ],
                ];
            }

            Log::error('Failed to create dynamic coupon', [
                'chatbot_id' => $chatbot->id,
                'status'     => $response->status(),
                'response'   => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al crear cupón en WooCommerce: ' . $response->body(),
            ];

        } catch (Exception $e) {
            Log::error('Exception creating dynamic coupon', [
                'chatbot_id' => $chatbot->id,
                'error'      => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Testear conexión con WooCommerce
     */
    public function testConnection(string $url, string $key, string $secret): array
    {
        try {
            $testUrl = rtrim($url, '/') . '/wp-json/wc/v3/system_status';

            $response = Http::withBasicAuth($key, $secret)
                ->timeout(10)
                ->get($testUrl);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => '✅ Conexión exitosa con WooCommerce',
                ];
            }

            return [
                'success' => false,
                'message' => '❌ Error de autenticación. Verifica tus credenciales.',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '❌ No se pudo conectar: ' . $e->getMessage(),
            ];
        }
    }
}
