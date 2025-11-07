<?php

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\ChatbotHistory;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use App\Extensions\Chatbot\System\Models\ChatbotAnalytics;
use App\Extensions\Chatbot\System\Models\Chatbot;
use Illuminate\Support\Facades\Log;

class ProductIntegrationService
{
    /**
     * Process a chatbot response and track product interactions.
     */
    public function processProductInteractions(
        ChatbotHistory $history,
        string $response,
        array $toolCalls = []
    ): void {
        try {
            // Track product search tool calls
            foreach ($toolCalls as $toolCall) {
                if ($toolCall['function']['name'] === 'product_search') {
                    $this->trackProductSearch($history, $toolCall);
                } elseif ($toolCall['function']['name'] === 'product_recommendations') {
                    $this->trackProductRecommendations($history, $toolCall);
                }
            }

            // Extract product mentions from response
            $productMentions = $this->extractProductMentions($response, $history->chatbot_id);
            
            if (!empty($productMentions)) {
                foreach ($productMentions as $mention) {
                    $history->trackProductInteraction([
                        'action' => 'mentioned',
                        'product_id' => $mention['id'],
                        'product_name' => $mention['name'],
                        'context' => 'response_mention'
                    ]);
                }
            }

            // Track multimedia content if present
            $multimediaContent = $this->extractMultimediaContent($response);
            
            if (!empty($multimediaContent)) {
                foreach ($multimediaContent as $media) {
                    $history->trackMultimediaContent($media);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to process product interactions', [
                'history_id' => $history->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Track product search interactions.
     */
    protected function trackProductSearch(ChatbotHistory $history, array $toolCall): void
    {
        $arguments = json_decode($toolCall['function']['arguments'], true);
        
        $history->trackProductInteraction([
            'action' => 'search',
            'context' => 'tool_call',
            'search_query' => $arguments['query'] ?? null,
            'category_filter' => $arguments['category'] ?? null,
            'price_filter' => $arguments['max_price'] ?? null,
        ]);

        // Log analytics
        ChatbotAnalytics::create([
            'chatbot_id' => $history->chatbot_id,
            'query' => $arguments['query'] ?? 'product_search',
            'channel' => 'product_search',
            'metadata' => [
                'search_query' => $arguments['query'] ?? null,
                'category' => $arguments['category'] ?? null,
                'max_price' => $arguments['max_price'] ?? null,
            ]
        ]);
    }

    /**
     * Track product recommendation interactions.
     */
    protected function trackProductRecommendations(ChatbotHistory $history, array $toolCall): void
    {
        $arguments = json_decode($toolCall['function']['arguments'], true);
        
        $history->trackProductInteraction([
            'action' => 'recommendations_requested',
            'context' => 'tool_call',
            'recommendation_context' => $arguments['context'] ?? null,
            'limit' => $arguments['limit'] ?? 3,
        ]);

        // Log analytics
        ChatbotAnalytics::create([
            'chatbot_id' => $history->chatbot_id,
            'query' => 'product_recommendations',
            'channel' => 'product_recommendations',
            'metadata' => [
                'context' => $arguments['context'] ?? null,
                'limit' => $arguments['limit'] ?? 3,
            ]
        ]);
    }

    /**
     * Extract product mentions from response text.
     */
    protected function extractProductMentions(string $response, int $chatbotId): array
    {
        $mentions = [];
        
        try {
            $chatbot = Chatbot::find($chatbotId);
            if (!$chatbot) {
                return $mentions;
            }

            // Get products for this chatbot's user
            $products = ChatbotProduct::where('user_id', $chatbot->user_id)
                ->get(['id', 'name']);

            foreach ($products as $product) {
                // Check if product name is mentioned in the response
                if (stripos($response, $product->name) !== false) {
                    $mentions[] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'position' => stripos($response, $product->name)
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::warning('Failed to extract product mentions', [
                'error' => $e->getMessage()
            ]);
        }

        return $mentions;
    }

    /**
     * Extract multimedia content references from response.
     */
    protected function extractMultimediaContent(string $response): array
    {
        $multimedia = [];
        
        // Extract image tags
        preg_match_all('/<img[^>]+src="([^"]+)"[^>]*alt="([^"]*)"[^>]*>/i', $response, $imageMatches);
        for ($i = 0; $i < count($imageMatches[0]); $i++) {
            $multimedia[] = [
                'type' => 'image',
                'url' => $imageMatches[1][$i],
                'title' => $imageMatches[2][$i] ?? 'Image',
            ];
        }

        // Extract video tags
        preg_match_all('/<video[^>]*>.*?<source[^>]+src="([^"]+)"[^>]*>.*?<\/video>/is', $response, $videoMatches);
        foreach ($videoMatches[1] as $videoUrl) {
            $multimedia[] = [
                'type' => 'video',
                'url' => $videoUrl,
                'title' => 'Video Content',
            ];
        }

        // Extract audio tags
        preg_match_all('/<audio[^>]*>.*?<source[^>]+src="([^"]+)"[^>]*>.*?<\/audio>/is', $response, $audioMatches);
        foreach ($audioMatches[1] as $audioUrl) {
            $multimedia[] = [
                'type' => 'audio',
                'url' => $audioUrl,
                'title' => 'Audio Content',
            ];
        }

        return $multimedia;
    }

    /**
     * Get product interaction analytics for a chatbot.
     */
    public function getProductAnalytics(int $chatbotId, int $days = 30): array
    {
        $histories = ChatbotHistory::where('chatbot_id', $chatbotId)
            ->where('created_at', '>=', now()->subDays($days))
            ->withProductInteractions()
            ->get();

        $analytics = [
            'total_product_interactions' => 0,
            'product_searches' => 0,
            'product_recommendations' => 0,
            'product_mentions' => 0,
            'top_searched_products' => [],
            'interaction_timeline' => [],
        ];

        foreach ($histories as $history) {
            $interactions = $history->product_interactions ?? [];
            $analytics['total_product_interactions'] += count($interactions);

            foreach ($interactions as $interaction) {
                switch ($interaction['action']) {
                    case 'search':
                        $analytics['product_searches']++;
                        break;
                    case 'recommendations_requested':
                        $analytics['product_recommendations']++;
                        break;
                    case 'mentioned':
                        $analytics['product_mentions']++;
                        if (isset($interaction['product_name'])) {
                            $productName = $interaction['product_name'];
                            $analytics['top_searched_products'][$productName] = 
                                ($analytics['top_searched_products'][$productName] ?? 0) + 1;
                        }
                        break;
                }

                // Add to timeline
                $date = date('Y-m-d', strtotime($interaction['timestamp']));
                $analytics['interaction_timeline'][$date] = 
                    ($analytics['interaction_timeline'][$date] ?? 0) + 1;
            }
        }

        // Sort top products by frequency
        arsort($analytics['top_searched_products']);
        $analytics['top_searched_products'] = array_slice($analytics['top_searched_products'], 0, 10, true);

        return $analytics;
    }

    /**
     * Generate product suggestions based on conversation history.
     */
    public function generateProductSuggestions(int $chatbotId, int $limit = 5): array
    {
        try {
            $chatbot = Chatbot::find($chatbotId);
            if (!$chatbot) {
                return [];
            }

            // Get recent conversation context
            $recentHistories = ChatbotHistory::where('chatbot_id', $chatbotId)
                ->where('created_at', '>=', now()->subHours(24))
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            // Extract keywords from recent messages
            $keywords = [];
            foreach ($recentHistories as $history) {
                if ($history->role === 'user') {
                    $words = str_word_count(strtolower($history->message), 1);
                    $keywords = array_merge($keywords, array_filter($words, function($word) {
                        return strlen($word) > 3; // Only words longer than 3 characters
                    }));
                }
            }

            // Get products that match keywords
            $products = ChatbotProduct::where('user_id', $chatbot->user_id)
                ->where('availability', 'in_stock')
                ->where(function ($query) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $query->orWhere('name', 'like', "%{$keyword}%")
                              ->orWhere('description', 'like', "%{$keyword}%");
                    }
                })
                ->with('category')
                ->limit($limit)
                ->get();

            return $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->formatted_price,
                    'category' => $product->category->name ?? 'Uncategorized',
                    'image_url' => $product->image_url,
                    'purchase_url' => $product->purchase_url,
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to generate product suggestions', [
                'chatbot_id' => $chatbotId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}