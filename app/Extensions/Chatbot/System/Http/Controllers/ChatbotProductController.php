<?php

namespace App\Extensions\Chatbot\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use App\Extensions\Chatbot\System\Models\Chatbot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ChatbotProductController extends Controller
{
    /**
     * Display a listing of products for a specific chatbot
     */
    public function index(Request $request): JsonResponse|View
    {
        $chatbotId = $request->get('chatbot_id');
        
        if (!$chatbotId) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Chatbot ID is required'], 400);
            }
            return view('chatbot::products.index', ['products' => collect(), 'error' => 'Chatbot ID is required']);
        }

        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->first();
            
        if (!$chatbot) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Chatbot not found'], 404);
            }
            return view('chatbot::products.index', ['products' => collect(), 'error' => 'Chatbot not found']);
        }

        // Get products associated with this chatbot using the pivot table
        $products = ChatbotProduct::where('user_id', $chatbot->user_id)
            ->whereHas('chatbots', function ($query) use ($chatbotId) {
                $query->where('chatbot_id', $chatbotId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'products' => $products->map(function ($product) {
                    return $this->formatProductForApi($product);
                }),
                'total' => $products->count(),
            ]);
        }

        return view('chatbot::products.index', [
            'products' => $products,
            'chatbot' => $chatbot,
        ]);
    }

    /**
     * Get product statistics for a chatbot
     */
    public function stats(Request $request): JsonResponse
    {
        $chatbotId = $request->get('chatbot_id');
        
        if (!$chatbotId) {
            return response()->json(['error' => 'Chatbot ID is required'], 400);
        }

        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->first();
            
        if (!$chatbot) {
            return response()->json(['error' => 'Chatbot not found'], 404);
        }

        $products = ChatbotProduct::where('user_id', $chatbot->user_id)
            ->whereHas('chatbots', function ($query) use ($chatbotId) {
                $query->where('chatbot_id', $chatbotId);
            });

        $stats = [
            'total_products' => $products->count(),
            'auto_detected' => $products->where('auto_detected', true)->count(),
            'manual_added' => $products->where('auto_detected', false)->count(),
            'in_stock' => $products->where('availability', 'in_stock')->count(),
            'out_of_stock' => $products->where('availability', 'out_of_stock')->count(),
            'categories' => $products->whereNotNull('category')->distinct('category')->pluck('category'),
            'platforms' => $products->whereNotNull('platform')->distinct('platform')->pluck('platform'),
            'currencies' => $products->whereNotNull('currency')->distinct('currency')->pluck('currency'),
        ];

        return response()->json($stats);
    }

    /**
     * Update a product
     */
    public function update(Request $request, $id): JsonResponse
    {
        $product = ChatbotProduct::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();
        
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|max:10',
            'category' => 'sometimes|nullable|string|max:100',
            'availability' => 'sometimes|required|in:in_stock,out_of_stock,discontinued',
        ]);

        $product->update($request->only([
            'name', 'description', 'price', 'currency', 'category', 'availability'
        ]));

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $this->formatProductForApi($product->fresh())
        ]);
    }

    /**
     * Delete a product
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $product = ChatbotProduct::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();
        
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Delete local image if exists
        if ($product->local_image_path && file_exists(storage_path('app/public/' . $product->local_image_path))) {
            unlink(storage_path('app/public/' . $product->local_image_path));
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    /**
     * Format product data for API response
     */
    private function formatProductForApi(ChatbotProduct $product): array
    {
        // Get the correct image URL
        $imageUrl = null;
        if ($product->local_image_path && file_exists(storage_path('app/public/' . $product->local_image_path))) {
            $imageUrl = asset('storage/' . $product->local_image_path);
        } elseif ($product->image_url) {
            $imageUrl = $product->image_url;
        }
        
        return [
            'id' => $product->id,
            'name' => html_entity_decode($product->name, ENT_QUOTES, 'UTF-8'),
            'description' => html_entity_decode($product->description ?? '', ENT_QUOTES, 'UTF-8'),
            'price' => $product->price,
            'currency' => $product->currency ?? 'COP',
            'formatted_price' => $product->formatted_price,
            'image_url' => $imageUrl,
            'local_image_path' => $product->local_image_path,
            'url' => $product->url,
            'purchase_url' => $product->purchase_url ?? $product->url,
            'platform' => $product->platform ?? 'generic',
            'category' => $product->category,
            'availability' => $product->availability ?? 'in_stock',
            'auto_detected' => (bool) $product->auto_detected,
            'sku' => $product->sku,
            'created_at' => $product->created_at->toISOString(),
        ];
    }
}