<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Parsers;

class ProductExtractor
{
    private array $platformPatterns = [
        'woocommerce' => [
            'indicators' => ['woocommerce', 'wp-content', 'add-to-cart'],
            'selectors' => [
                'name' => '.product_title, h1.entry-title, .woocommerce-loop-product__title',
                'price' => '.price .amount, .woocommerce-Price-amount, .price',
                'description' => '.woocommerce-product-details__short-description, .product-short-description',
                'image' => '.woocommerce-product-gallery__image img, .product-image img',
                'sku' => '.sku',
                'availability' => '.stock, .availability'
            ]
        ],
        'shopify' => [
            'indicators' => ['shopify', 'cdn.shopify.com', 'product-form'],
            'selectors' => [
                'name' => '.product-title, h1.product__title, .product-single__title',
                'price' => '.price, .product__price, .money',
                'description' => '.product-description, .product__description',
                'image' => '.product__photo img, .product-single__photo img',
                'sku' => '.product__sku, .variant-sku',
                'availability' => '.product-form__buttons, .product__availability'
            ]
        ],
        'prestashop' => [
            'indicators' => ['prestashop', 'add-to-cart', 'product-actions'],
            'selectors' => [
                'name' => 'h1[itemprop="name"], .product-title',
                'price' => '.current-price, .price, [itemprop="price"]',
                'description' => '.product-description, [itemprop="description"]',
                'image' => '.product-cover img, .js-qv-product-cover img',
                'sku' => '[itemprop="sku"], .product-reference',
                'availability' => '.product-availability, [itemprop="availability"]'
            ]
        ]
    ];

    private array $genericSelectors = [
        'name' => 'h1, .product-name, .product-title, [itemprop="name"]',
        'price' => '.price, [itemprop="price"], .cost, .amount',
        'description' => '.description, .product-description, [itemprop="description"]',
        'image' => '.product-image img, .main-image img, [itemprop="image"]',
        'sku' => '.sku, .product-code, [itemprop="sku"]',
        'availability' => '.availability, .stock, [itemprop="availability"]'
    ];

    public function isProductPage(string $html, string $url): bool
    {
        // Check URL patterns
        $productUrlPatterns = [
            '/\/product\//i',
            '/\/productos\//i',
            '/\/shop\//i',
            '/\/tienda\//i',
            '/\/p\//i',
            '/\/item\//i',
            '/\/articulo\//i'
        ];

        foreach ($productUrlPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        // Check for price patterns
        $pricePatterns = [
            '/\$\d+\.?\d*/',
            '/€\d+\.?\d*/',
            '/£\d+\.?\d*/',
            '/\d+\s*USD/',
            '/\d+\s*EUR/',
            '/\d+\s*GBP/'
        ];

        foreach ($pricePatterns as $pattern) {
            if (preg_match($pattern, $html)) {
                // If we find price patterns, check for product indicators
                $productIndicators = [
                    'add-to-cart', 'buy-now', 'comprar', 'añadir-carrito',
                    'product-details', 'product-info', 'product-price'
                ];

                foreach ($productIndicators as $indicator) {
                    if (stripos($html, $indicator) !== false) {
                        return true;
                    }
                }
            }
        }

        // Check for structured data
        if (preg_match('/"@type":\s*"Product"/i', $html) || 
            preg_match('/itemtype="[^"]*Product"/i', $html)) {
            return true;
        }

        return false;
    }

    public function extractProductData(string $html, string $url): ?array
    {
        if (!$this->isProductPage($html, $url)) {
            return null;
        }

        $platform = $this->detectPlatform($html);
        $selectors = $this->platformPatterns[$platform]['selectors'] ?? $this->genericSelectors;

        $productData = [
            'name' => $this->extractBySelector($html, $selectors['name']),
            'price' => $this->extractPrice($html, $selectors['price']),
            'description' => $this->extractBySelector($html, $selectors['description']),
            'image' => $this->extractImage($html, $selectors['image'], $url),
            'sku' => $this->extractBySelector($html, $selectors['sku']),
            'availability' => $this->determineAvailability($html, $selectors['availability']),
            'url' => $url,
            'platform' => $platform
        ];

        // Only return if we have at least name and price
        if (empty($productData['name']) || empty($productData['price'])) {
            return null;
        }

        return $productData;
    }

    private function detectPlatform(string $html): string
    {
        foreach ($this->platformPatterns as $platform => $config) {
            foreach ($config['indicators'] as $indicator) {
                if (stripos($html, $indicator) !== false) {
                    return $platform;
                }
            }
        }

        return 'generic';
    }

    private function extractBySelector(string $html, string $selectors): ?string
    {
        $selectorList = explode(',', $selectors);
        
        foreach ($selectorList as $selector) {
            $selector = trim($selector);
            
            // Handle different selector types
            if (strpos($selector, '[') !== false) {
                // Attribute selector
                if (preg_match('/\[([^=]+)="([^"]+)"\]/', $selector, $matches)) {
                    $pattern = '/<[^>]*' . preg_quote($matches[1]) . '="[^"]*' . preg_quote($matches[2]) . '[^"]*"[^>]*>(.*?)<\/[^>]+>/is';
                    if (preg_match($pattern, $html, $contentMatches)) {
                        return trim(strip_tags($contentMatches[1]));
                    }
                }
            } elseif (strpos($selector, '.') === 0) {
                // Class selector
                $className = substr($selector, 1);
                $pattern = '/<[^>]*class="[^"]*' . preg_quote($className) . '[^"]*"[^>]*>(.*?)<\/[^>]+>/is';
                if (preg_match($pattern, $html, $matches)) {
                    return trim(strip_tags($matches[1]));
                }
            } elseif (strpos($selector, '#') === 0) {
                // ID selector
                $id = substr($selector, 1);
                $pattern = '/<[^>]*id="' . preg_quote($id) . '"[^>]*>(.*?)<\/[^>]+>/is';
                if (preg_match($pattern, $html, $matches)) {
                    return trim(strip_tags($matches[1]));
                }
            } else {
                // Tag selector
                $pattern = '/<' . preg_quote($selector) . '[^>]*>(.*?)<\/' . preg_quote($selector) . '>/is';
                if (preg_match($pattern, $html, $matches)) {
                    return trim(strip_tags($matches[1]));
                }
            }
        }

        return null;
    }

    private function extractPrice(string $html, string $selectors): ?float
    {
        $priceText = $this->extractBySelector($html, $selectors);
        
        if (!$priceText) {
            // Fallback: search for price patterns in the entire HTML
            $pricePatterns = [
                '/\$(\d+(?:\.\d{2})?)/i',
                '/€(\d+(?:\.\d{2})?)/i',
                '/£(\d+(?:\.\d{2})?)/i',
                '/(\d+(?:\.\d{2})?)\s*USD/i',
                '/(\d+(?:\.\d{2})?)\s*EUR/i'
            ];

            foreach ($pricePatterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    return (float) $matches[1];
                }
            }
            return null;
        }

        // Extract numeric value from price text
        if (preg_match('/(\d+(?:\.\d{2})?)/', $priceText, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    private function extractImage(string $html, string $selectors, string $baseUrl): ?string
    {
        $selectorList = explode(',', $selectors);
        
        foreach ($selectorList as $selector) {
            $selector = trim($selector);
            
            // Look for img tags with the specified selector
            if (strpos($selector, 'img') !== false) {
                $pattern = '/<img[^>]*class="[^"]*' . preg_quote(str_replace(['.', ' img'], '', $selector)) . '[^"]*"[^>]*src="([^"]+)"/i';
                if (preg_match($pattern, $html, $matches)) {
                    return $this->makeAbsoluteUrl($matches[1], $baseUrl);
                }
            }
        }

        // Fallback: look for any product-related images
        $imagePatterns = [
            '/<img[^>]*class="[^"]*product[^"]*"[^>]*src="([^"]+)"/i',
            '/<img[^>]*alt="[^"]*product[^"]*"[^>]*src="([^"]+)"/i'
        ];

        foreach ($imagePatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return $this->makeAbsoluteUrl($matches[1], $baseUrl);
            }
        }

        return null;
    }

    private function determineAvailability(string $html, string $selectors): string
    {
        $availabilityText = $this->extractBySelector($html, $selectors);
        
        if (!$availabilityText) {
            // Check for common availability indicators
            if (stripos($html, 'out of stock') !== false || 
                stripos($html, 'agotado') !== false ||
                stripos($html, 'sin stock') !== false) {
                return 'out_of_stock';
            }
            
            if (stripos($html, 'in stock') !== false || 
                stripos($html, 'disponible') !== false ||
                stripos($html, 'add to cart') !== false) {
                return 'in_stock';
            }
            
            return 'in_stock'; // Default assumption
        }

        $availabilityText = strtolower($availabilityText);
        
        if (strpos($availabilityText, 'out') !== false || 
            strpos($availabilityText, 'agotado') !== false ||
            strpos($availabilityText, 'sin stock') !== false) {
            return 'out_of_stock';
        }

        return 'in_stock';
    }

    private function makeAbsoluteUrl(string $url, string $baseUrl): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (str_starts_with($url, '/')) {
            $parsedBase = parse_url($baseUrl);
            return $parsedBase['scheme'] . '://' . $parsedBase['host'] . $url;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }
}