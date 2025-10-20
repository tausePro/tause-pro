<?php

namespace App\Http\Controllers\Chatbot;

use App\Helpers\Classes\Helper;
use App\Helpers\Classes\OpenAiHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chatbot\ChatbotTrainingRequest;
use App\Models\Chatbot\Chatbot;
use App\Models\Chatbot\ChatbotData;
use App\Models\Chatbot\ChatbotDataVector;
use App\Services\Chatbot\LinkCrawler;
use App\Services\Chatbot\ParserExcelService;
use App\Services\Chatbot\ParserService;
use Illuminate\Http\Request;

class ChatbotTrainingController extends Controller
{
    public function qa(Request $request, Chatbot $chatbot)
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
                'content' => view('panel.admin.chatbot.particles.qa.list', [
                    'items' => $chatbot->data()->where('type', 'qa')->get(),
                ])->render(),
            ]);
        }

        $request->validate([
            'question' => 'required|string|max:255',
            'answer'   => 'required|string',
        ]);

        $id = $request->get('qa_id');

        $chatBotData = ChatBotData::query()->where('id', $id)->first();

        if ($chatBotData) {
            $chatBotData->update([
                'type_value' => $request->get('question'),
                'content'    => $request->get('answer'),
                'status'     => 'waiting',
            ]);

            ChatbotDataVector::query()->where('chatbot_data_id', $id)->delete();
        } else {
            ChatBotData::query()->firstOrCreate([
                'chatbot_id' => $chatbot->getAttribute('id'),
                'type'       => 'qa',
                'type_value' => $request->get('question'),
            ], [
                'content' => $request->get('answer'),
                'status'  => 'waiting',
            ]);
        }

        return response()->json([
            'content' => view('panel.admin.chatbot.particles.qa.list', [
                'items' => $chatbot->data()->where('type', 'qa')->get(),
            ])->render(),
            'message' => trans('Qa uploaded successfully.'),
            'count'   => $chatbot->data()->where('type', 'qa')->count(),
        ]);
    }

    public function text(Request $request, Chatbot $chatbot)
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
                'content' => view('panel.admin.chatbot.particles.text.list', [
                    'items' => $chatbot->data()->where('type', 'text')->get(),
                ])->render(),
            ]);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'text'  => 'required|string',
        ]);

        $id = $request->get('text_id');

        $chatBotData = ChatBotData::query()->where('id', $id)->first();

        if ($chatBotData) {
            $chatBotData->update([
                'type_value' => $request->get('title'),
                'content'    => $request->get('text'),
                'status'     => 'waiting',
            ]);

            ChatbotDataVector::query()->where('chatbot_data_id', $id)->delete();
        } else {
            ChatBotData::query()->firstOrCreate([
                'chatbot_id' => $chatbot->getAttribute('id'),
                'type'       => 'text',
                'type_value' => $request->get('title'),
            ], [
                'content' => $request->get('text'),
                'status'  => 'waiting',
            ]);
        }

        return response()->json([
            'content' => view('panel.admin.chatbot.particles.text.list', [
                'items' => $chatbot->data()->where('type', 'text')->get(),
            ])->render(),
            'message' => trans('Text uploaded successfully.'),
            'count'   => $chatbot->data()->where('type', 'text')->count(),
        ]);
    }

    public function uploadPdf(Request $request, Chatbot $chatbot)
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
                'content' => view('panel.admin.chatbot.particles.pdf.list', [
                    'items' => $chatbot->data()->where('type', 'pdf')->get(),
                ])->render(),
            ]);
        }

        $request->validate([
            'file' => 'required|mimes:pdf,xls,xlsx,csv',
        ]);

        $file = $request->file('file');

        $extension = $file->guessExtension();

        $defaultDisk = 'public';

        $path = $file->store('chatbot', ['disk' => $defaultDisk]);

        $name = $file->getClientOriginalName();

        $storagePath = config('filesystems.disks.' . $defaultDisk . '.root') . '/' . $path;

        if ($extension === 'xls' || $extension === 'xlsx' || $extension === 'csv') {
            $parser = app(ParserExcelService::class);

            $parser->setPath($storagePath)->parse();

        } else {
            $parser = app(ParserService::class);

            $parser->setPdfPath($storagePath)->parse();
        }

        ChatBotData::query()->firstOrCreate([
            'chatbot_id' => $chatbot->getAttribute('id'),
            'type'       => 'pdf',
            'type_value' => $name,
        ], [
            'content' => $parser->getText(),
            'status'  => 'waiting',
            'path'    => $path,
        ]);

        return response()->json([
            'content' => view('panel.admin.chatbot.particles.pdf.list', [
                'items' => $chatbot->data()->where('type', 'pdf')->get(),
            ])->render(),
            'message' => trans('Pdf file uploaded successfully.'),
        ]);
    }

    public function getWebSites(Request $request, Chatbot $chatbot)
    {
        return response()->json([
            'content' => view('panel.admin.chatbot.particles.web-site.crawler', [
                'items' => $chatbot->data()->where('type', 'url')->get(),
            ])->render(),
        ]);
    }

    public function postWebSites(Request $request, Chatbot $chatbot)
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
                'content' => view('panel.admin.chatbot.particles.web-site.crawler', [
                    'items' => $chatbot->data()->where('type', 'url')->get(),
                ])->render(),
            ]);
        }
        $request->validate([
            'url' => 'required|url',
        ]);

        $single = $request->input('type') == 'single';
        $detectProducts = $request->input('detect_products', false);

        $crawler = new LinkCrawler($request->input('url'));

        $crawler->crawl($single);

        $content = $crawler->getContents();

        if (! mb_check_encoding($content, 'UTF-8')) {
            // Convert the content to UTF-8 encoding if needed
            $content = mb_convert_encoding($content, 'UTF-8');
        }

        $detectedProducts = [];
        $savedProductsCount = 0;

        // Product detection if enabled
        if ($detectProducts) {
            $productExtractor = new \App\Extensions\Chatbot\System\Parsers\ProductExtractor();
            
            foreach ($content as $url => $data) {
                $productData = $productExtractor->extractProductData($data, $url);
                if ($productData) {
                    $detectedProducts[] = $productData;
                    
                    // Save product to database
                    $savedProduct = $this->saveDetectedProduct($chatbot, $productData);
                    if ($savedProduct) {
                        $savedProductsCount++;
                    }
                }
            }
        }

        foreach ($content as $url => $data) {
            ChatBotData::query()->firstOrCreate([
                'chatbot_id' => $chatbot->getAttribute('id'),
                'type'       => 'url',
                'type_value' => $url,
            ], [
                'content' => $data,
                'status'  => 'waiting',
            ]);
        }

        $response = [
            'content' => view('panel.admin.chatbot.particles.web-site.crawler', [
                'items' => $chatbot->data()->where('type', 'url')->get(),
            ])->render(),
            'message' => trans('Web sites added successfully.'),
        ];

        // Add detected products to response if any
        if (!empty($detectedProducts)) {
            $response['detected_products'] = $detectedProducts;
            $response['products_count'] = count($detectedProducts);
            $response['products_saved'] = $savedProductsCount;
            
            if ($savedProductsCount > 0) {
                $response['message'] = trans('Web sites added successfully. :saved/:total products detected and saved.', [
                    'saved' => $savedProductsCount,
                    'total' => count($detectedProducts)
                ]);
            } else {
                $response['message'] = trans('Web sites added successfully. :count products detected (already exist).', [
                    'count' => count($detectedProducts)
                ]);
            }
        }

        return response()->json($response);
    }

    public function training(
        ChatbotTrainingRequest $request,
        Chatbot $chatbot
    ) {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $selected = $request->input('chatbot_data') ?: [];

        $data = $this->chatbotData($chatbot, $selected)->toArray();

        OpenAiHelper::embeddingData(
            $chatbot->getAttribute('id'),
            $data,
            $chatbot->trainingData()->pluck('id')->toArray()
        );

        $type = $request->get('type');

        $matchView = match ($type) {
            'url'  => 'panel.admin.chatbot.particles.web-site.crawler',
            'pdf'  => 'panel.admin.chatbot.particles.pdf.list',
            'text' => 'panel.admin.chatbot.particles.text.list',
            'qa'   => 'panel.admin.chatbot.particles.qa.list',
        };

        $chatbot->update([
            'status' => 'trained',
        ]);

        return response()->json([
            'content' => view($matchView, [
                'items' => $chatbot->data()->where('type', $request->get('type'))->get(),
            ])->render(),
            'message' => trans('Training Completed Successfully.'),
        ]);
    }

    public function deleteItem(Chatbot $chatbot, $id)
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $chatbot->data()->where('id', $id)->delete();

        ChatbotDataVector::query()->where('chatbot_data_id', $id)->delete();

        return response()->json([
            'message' => trans('Item deleted successfully.'),
        ]);
    }

    public function saveProducts(Request $request, Chatbot $chatbot)
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $request->validate([
            'products' => 'required|array',
            'products.*.name' => 'required|string',
            'products.*.price' => 'required|numeric',
            'products.*.url' => 'required|url',
        ]);

        $products = $request->input('products');
        $savedCount = 0;

        foreach ($products as $productData) {
            // Check if ChatbotProduct model exists, if not use a simple approach
            if (class_exists('\App\Extensions\Chatbot\System\Models\ChatbotProduct')) {
                $product = \App\Extensions\Chatbot\System\Models\ChatbotProduct::firstOrCreate([
                    'user_id' => $chatbot->user_id,
                    'name' => $productData['name'],
                    'purchase_url' => $productData['url'],
                ], [
                    'description' => $productData['description'] ?? '',
                    'price' => $productData['price'],
                    'currency' => $productData['currency'] ?? 'USD',
                    'image_url' => $productData['image'] ?? null,
                    'sku' => $productData['sku'] ?? null,
                    'availability' => $productData['availability'] ?? 'in_stock',
                    'platform' => $productData['platform'] ?? 'generic',
                ]);
                
                // Associate with chatbot if not already associated
                if (!$product->chatbots()->where('chatbot_id', $chatbot->id)->exists()) {
                    $product->chatbots()->attach($chatbot->id);
                }
                
                $savedCount++;
            } else {
                // Fallback: save as training data
                ChatBotData::query()->firstOrCreate([
                    'chatbot_id' => $chatbot->getAttribute('id'),
                    'type' => 'product',
                    'type_value' => $productData['name'],
                ], [
                    'content' => json_encode($productData),
                    'status' => 'waiting',
                ]);
                
                $savedCount++;
            }
        }

        return response()->json([
            'message' => trans(':count products saved successfully.', ['count' => $savedCount]),
            'products_saved' => $savedCount,
        ]);
    }

    /**
     * Save a detected product to the database with image download
     */
    private function saveDetectedProduct(Chatbot $chatbot, array $productData): ?object
    {
        try {
            // Check if ChatbotProduct model exists
            if (!class_exists('\App\Extensions\Chatbot\System\Models\ChatbotProduct')) {
                return null;
            }

            // Download and save image if available
            $localImagePath = null;
            if (!empty($productData['image'])) {
                $localImagePath = $this->downloadProductImage($productData['image'], $productData['name']);
            }

            // Check if product already exists
            $existingProduct = \App\Extensions\Chatbot\System\Models\ChatbotProduct::where('user_id', $chatbot->user_id)
                ->where('name', $productData['name'])
                ->where('purchase_url', $productData['url'])
                ->first();

            if ($existingProduct) {
                // Product exists, just associate with chatbot if not already associated
                if (!$existingProduct->chatbots()->where('chatbot_id', $chatbot->id)->exists()) {
                    $existingProduct->chatbots()->attach($chatbot->id);
                }
                return $existingProduct;
            }

            // Create new product
            $product = \App\Extensions\Chatbot\System\Models\ChatbotProduct::create([
                'user_id' => $chatbot->user_id,
                'name' => $productData['name'],
                'description' => $productData['description'] ?? '',
                'price' => $productData['price'] ?? 0,
                'currency' => $productData['currency'] ?? 'COP',
                'image_url' => $productData['image'] ?? null,
                'local_image_path' => $localImagePath,
                'sku' => $productData['sku'] ?? null,
                'availability' => $productData['availability'] ?? 'in_stock',
                'platform' => $productData['platform'] ?? 'generic',
                'category' => $this->extractCategory($productData),
                'purchase_url' => $productData['url'],
                'auto_detected' => true,
            ]);

            // Associate with chatbot
            $product->chatbots()->attach($chatbot->id);

            return $product;

        } catch (\Exception $e) {
            \Log::error('Failed to save detected product', [
                'product' => $productData,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Download product image and save locally
     */
    private function downloadProductImage(string $imageUrl, string $productName): ?string
    {
        try {
            // Create directory if it doesn't exist
            $directory = 'products/images/' . date('Y/m');
            $fullDirectory = storage_path('app/public/' . $directory);
            
            if (!file_exists($fullDirectory)) {
                mkdir($fullDirectory, 0755, true);
            }

            // Generate filename
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = \Str::slug($productName) . '_' . time() . '.' . $extension;
            $filePath = $directory . '/' . $filename;
            $fullPath = storage_path('app/public/' . $filePath);

            // Download image
            $imageContent = @file_get_contents($imageUrl);
            if ($imageContent === false) {
                return null;
            }

            // Save image
            if (file_put_contents($fullPath, $imageContent)) {
                return $filePath;
            }

        } catch (\Exception $e) {
            \Log::warning('Failed to download product image', [
                'url' => $imageUrl,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Extract category from product data
     */
    private function extractCategory(array $productData): ?string
    {
        // Try to extract category from URL or product name
        $url = $productData['url'] ?? '';
        $name = $productData['name'] ?? '';
        
        // Common category patterns in URLs
        $categoryPatterns = [
            '/\/categoria\/([^\/]+)/i',
            '/\/category\/([^\/]+)/i',
            '/\/cat\/([^\/]+)/i',
            '/\/c\/([^\/]+)/i',
        ];
        
        foreach ($categoryPatterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return ucfirst(str_replace(['-', '_'], ' ', $matches[1]));
            }
        }
        
        // Try to infer category from product name
        $skinCareKeywords = ['facial', 'skin', 'piel', 'crema', 'mascarilla', 'serum', 'retinol'];
        $techKeywords = ['iphone', 'macbook', 'laptop', 'phone', 'computer'];
        
        $lowerName = strtolower($name);
        
        foreach ($skinCareKeywords as $keyword) {
            if (strpos($lowerName, $keyword) !== false) {
                return 'Skin Care';
            }
        }
        
        foreach ($techKeywords as $keyword) {
            if (strpos($lowerName, $keyword) !== false) {
                return 'Technology';
            }
        }
        
        return 'General';
    }

    public function chatbotData(Chatbot $chatbot, ?array $data = null)
    {
        return ChatBotData::query()
            ->where('chatbot_id', $chatbot->getAttribute('id'))
            ->where('type', request('type'))
            ->when($data, function ($query) use ($data) {
                return $query->whereIn('id', $data);
            })
            ->get()
            ->map(function ($item) {

                $content = $item->getAttribute('type') == 'qa'
                    ? "When you receive the following question or a similar one, answer it like this: '" . $item->getAttribute('content') . "' \n Question: '" . $item->getAttribute('type_value') . "'"
                    : $item->getAttribute('content');

                return [
                    'id'      => $item->getAttribute('id'),
                    'content' => $content,
                ];
            })
            ->pluck('content', 'id');
    }
}
