<?php

namespace App\Extensions\BrainBrand\System\Http\Controllers;

use App\Extensions\BrainBrand\System\Models\BrainBrand;
use App\Extensions\BrainBrand\System\Services\BrainBrandDistributionService;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotTrainController;
use App\Extensions\Chatbot\System\Http\Requests\Train\DataRequest;
use App\Extensions\Chatbot\System\Http\Requests\Train\EmbedingRequest;
use App\Extensions\Chatbot\System\Http\Requests\Train\FileRequest;
use App\Extensions\Chatbot\System\Http\Requests\Train\QaRequest;
use App\Extensions\Chatbot\System\Http\Requests\Train\TextRequest;
use App\Extensions\Chatbot\System\Http\Resources\Admin\ChatbotEmbeddingResource;
use App\Extensions\Chatbot\System\Parsers\LinkParser;
use App\Extensions\Chatbot\System\Services\ChatbotService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BrainBrandTrainController extends ChatbotTrainController
{
    public function __construct(
        ChatbotService $service,
        protected BrainBrandDistributionService $distributionService
    ) {
        parent::__construct($service);
    }

    /**
     * Show Brain Brand training interface
     */
    public function index(): View
    {
        try {
            // Check if table exists before querying
            if (! Schema::hasTable('ext_brain_brands')) {
                return view('brainbrand::index', [
                    'brainBrands' => collect([]),
                    'error'       => 'Database setup required. Please run Brain Brand migrations.',
                ]);
            }

            $brainBrands = BrainBrand::where('user_id', auth()->id())
                ->orderBy('is_favorite', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return view('brainbrand::index', [
                'brainBrands' => $brainBrands,
            ]);
        } catch (Exception $e) {
            Log::error('BrainBrand index failed', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            return view('brainbrand::index', [
                'brainBrands' => collect([]),
                'error'       => 'Unable to load Brain Brands. Please try again later.',
            ]);
        }
    }

    /**
     * Show training interface for specific Brain Brand
     */
    public function show(BrainBrand $brainBrand): View
    {
        return view('brainbrand::train', [
            'brainBrand' => $brainBrand,
        ]);
    }

    /**
     * Get embeddings for Brain Brand (reuses parent method)
     */
    public function trainData(DataRequest $request): AnonymousResourceCollection
    {
        $brainBrand = BrainBrand::findOrFail($request->validated('id'));

        return ChatbotEmbeddingResource::collection(
            $brainBrand->embeddings()
                ->when($request->validated('type'), fn ($query) => $query->where('type', $request->validated('type')))
                ->get()
        );
    }

    /**
     * Train URL for Brain Brand with improved error handling and logging
     */
    public function trainUrlBrainBrand(Request $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            $validated = $request->validate([
                'id'     => 'required|exists:ext_brain_brands,id',
                'url'    => 'required|url',
                'single' => 'required|in:0,1',
            ]);

            $brainBrand = BrainBrand::findOrFail($validated['id']);

            Log::info('BrainBrand URL training started', [
                'brain_brand_id'   => $brainBrand->id,
                'brain_brand_name' => $brainBrand->name,
                'url'              => $validated['url'],
                'single_page'      => (bool) $validated['single'],
            ]);

            // Create a temporary chatbot for training
            $tempChatbot = $this->service->query()->create([
                'uuid'               => \Illuminate\Support\Str::uuid(),
                'user_id'            => auth()->id(),
                'title'              => 'Temp for Brain Brand Training',
                'ai_model'           => 'gpt-3.5-turbo',
                'ai_embedding_model' => 'text-embedding-3-small',
            ]);

            $processedCount = 0;
            $errors = [];

            try {
                // Use the LinkParser with proper error handling
                $linkParser = new LinkParser;
                $contents = $linkParser->crawlUrl($validated['url'], (bool) $validated['single']);

                if (empty($contents)) {
                    throw new Exception('No content found on the URL. The page might be empty or inaccessible.');
                }

                Log::info('BrainBrand URL crawling completed', [
                    'brain_brand_id' => $brainBrand->id,
                    'pages_found'    => count($contents),
                    'url'            => $validated['url'],
                ]);

                // Process each content item with individual error handling
                foreach ($contents as $index => $content) {
                    try {
                        $this->service->createEmbedding([
                            'chatbot_id' => $tempChatbot->id,
                            'content'    => $content['content'] ?? '',
                            'title'      => $content['title'] ?? 'Crawled Content ' . ($index + 1),
                            'url'        => $content['url'] ?? $validated['url'],
                            'type'       => 'url',
                        ]);
                        $processedCount++;
                    } catch (Exception $e) {
                        $errors[] = "Failed to process content {$index}: " . $e->getMessage();
                        Log::warning('BrainBrand URL content processing failed', [
                            'brain_brand_id' => $brainBrand->id,
                            'content_index'  => $index,
                            'error'          => $e->getMessage(),
                        ]);
                    }
                }

                if ($processedCount === 0) {
                    throw new Exception('No content could be processed successfully');
                }

                // Move embeddings to Brain Brand
                $movedCount = $tempChatbot->embeddings()->update([
                    'brain_brand_id' => $brainBrand->id,
                    'chatbot_id'     => null,
                ]);

                Log::info('BrainBrand embeddings moved to Brain Brand', [
                    'brain_brand_id'   => $brainBrand->id,
                    'embeddings_moved' => $movedCount,
                ]);

                // Distribute to all user's chatbots
                $this->distributionService->distributeToChatbots($brainBrand);

                Log::info('BrainBrand URL training completed successfully', [
                    'brain_brand_id'  => $brainBrand->id,
                    'processed_count' => $processedCount,
                    'errors_count'    => count($errors),
                ]);

                return ChatbotEmbeddingResource::collection(
                    $brainBrand->embeddings()->whereNotNull('url')->get()
                );

            } catch (Exception $e) {
                Log::error('BrainBrand URL training failed', [
                    'brain_brand_id' => $brainBrand->id,
                    'url'            => $validated['url'],
                    'error'          => $e->getMessage(),
                    'trace'          => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'type'            => 'error',
                    'message'         => 'Failed to crawl URL. Please check the URL and try again.',
                    'details'         => $e->getMessage(),
                    'processed_count' => $processedCount,
                    'errors'          => $errors,
                ], 500);
            } finally {
                // Clean up temporary chatbot
                if (isset($tempChatbot) && $tempChatbot->exists) {
                    $tempChatbot->delete();
                }
            }

        } catch (Exception $e) {
            Log::error('BrainBrand URL training validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'type'    => 'error',
                'message' => 'Invalid request data',
                'details' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Train file for Brain Brand (reuses parent logic)
     */
    public function trainFileBrainBrand(FileRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $brainBrand = BrainBrand::findOrFail($request->validated('id'));

        // Create temporary chatbot for training
        $tempChatbot = $this->service->query()->create([
            'uuid'               => \Illuminate\Support\Str::uuid(),
            'user_id'            => auth()->id(),
            'title'              => 'Temp for Brain Brand Training',
            'ai_model'           => 'gpt-3.5-turbo',
            'ai_embedding_model' => 'text-embedding-3-small',
        ]);

        try {
            // Use parent method for training
            $request->merge(['id' => $tempChatbot->id]);
            parent::trainFile($request);

            // Move embeddings to Brain Brand
            $tempChatbot->embeddings()->update(['brain_brand_id' => $brainBrand->id, 'chatbot_id' => null]);

            // Distribute to all user's chatbots
            $this->distributionService->distributeToChatbots($brainBrand);

            return ChatbotEmbeddingResource::collection(
                $brainBrand->embeddings()->whereNotNull('file')->get()
            );

        } finally {
            // Clean up temporary chatbot
            $tempChatbot->delete();
        }
    }

    /**
     * Train text for Brain Brand with validation and improved error handling
     */
    public function trainTextBrainBrand(TextRequest $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            $validated = $request->validated();
            $brainBrand = BrainBrand::findOrFail($validated['id']);

            Log::info('BrainBrand text training started', [
                'brain_brand_id'   => $brainBrand->id,
                'brain_brand_name' => $brainBrand->name,
                'title_length'     => strlen($validated['title'] ?? ''),
                'content_length'   => strlen($validated['content'] ?? ''),
            ]);

            // Validate content quality
            $validationService = app(\App\Extensions\BrainBrand\System\Services\BrainBrandValidationService::class);
            $validation = $validationService->validateContent([
                'title'   => $validated['title'] ?? '',
                'content' => $validated['content'] ?? '',
            ], 'text');

            if (! $validation['is_valid']) {
                return response()->json([
                    'type'       => 'error',
                    'message'    => 'Content validation failed',
                    'validation' => $validation,
                ], 422);
            }

            // Log quality warnings
            if (! empty($validation['warnings'])) {
                Log::warning('BrainBrand text content quality issues', [
                    'brain_brand_id' => $brainBrand->id,
                    'warnings'       => $validation['warnings'],
                    'quality_score'  => $validation['quality_score'],
                ]);
            }

            // Create temporary chatbot for training
            $tempChatbot = $this->service->query()->create([
                'uuid'               => \Illuminate\Support\Str::uuid(),
                'user_id'            => auth()->id(),
                'title'              => 'Temp for Brain Brand Training',
                'ai_model'           => 'gpt-3.5-turbo',
                'ai_embedding_model' => 'text-embedding-3-small',
            ]);

            try {
                // Use parent method for training
                $request->merge(['id' => $tempChatbot->id]);
                parent::trainText($request);

                // Move embeddings to Brain Brand
                $movedCount = $tempChatbot->embeddings()->update([
                    'brain_brand_id' => $brainBrand->id,
                    'chatbot_id'     => null,
                ]);

                Log::info('BrainBrand text embeddings moved to Brain Brand', [
                    'brain_brand_id'   => $brainBrand->id,
                    'embeddings_moved' => $movedCount,
                ]);

                // Distribute to all user's chatbots
                $this->distributionService->distributeToChatbots($brainBrand);

                Log::info('BrainBrand text training completed successfully', [
                    'brain_brand_id'      => $brainBrand->id,
                    'quality_score'       => $validation['quality_score'],
                    'quality_description' => $validationService->getQualityScoreDescription($validation['quality_score']),
                    'warnings_count'      => count($validation['warnings']),
                ]);

                return ChatbotEmbeddingResource::collection(
                    $brainBrand->embeddings()->where('type', 'text')->get()
                );

            } catch (Exception $e) {
                Log::error('BrainBrand text training failed', [
                    'brain_brand_id' => $brainBrand->id,
                    'error'          => $e->getMessage(),
                    'trace'          => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'type'    => 'error',
                    'message' => 'Failed to process text content',
                    'details' => $e->getMessage(),
                ], 500);
            } finally {
                // Clean up temporary chatbot
                if (isset($tempChatbot) && $tempChatbot->exists) {
                    $tempChatbot->delete();
                }
            }

        } catch (Exception $e) {
            Log::error('BrainBrand text training validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'type'    => 'error',
                'message' => 'Invalid request data',
                'details' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Train Q&A for Brain Brand (reuses parent logic)
     */
    public function trainQaBrainBrand(QaRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $brainBrand = BrainBrand::findOrFail($request->validated('id'));

        // Create temporary chatbot for training
        $tempChatbot = $this->service->query()->create([
            'uuid'               => \Illuminate\Support\Str::uuid(),
            'user_id'            => auth()->id(),
            'title'              => 'Temp for Brain Brand Training',
            'ai_model'           => 'gpt-3.5-turbo',
            'ai_embedding_model' => 'text-embedding-3-small',
        ]);

        try {
            // Use parent method for training
            $request->merge(['id' => $tempChatbot->id]);
            parent::trainQa($request);

            // Move embeddings to Brain Brand
            $tempChatbot->embeddings()->update(['brain_brand_id' => $brainBrand->id, 'chatbot_id' => null]);

            // Distribute to all user's chatbots
            $this->distributionService->distributeToChatbots($brainBrand);

            return ChatbotEmbeddingResource::collection(
                $brainBrand->embeddings()->where('type', 'qa')->get()
            );

        } finally {
            // Clean up temporary chatbot
            $tempChatbot->delete();
        }
    }

    /**
     * Generate embeddings for Brain Brand (reuses parent logic)
     */
    public function generateEmbeddingBrainBrand(EmbedingRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $brainBrand = BrainBrand::findOrFail($request->validated('id'));

        // Create temporary chatbot for embedding generation
        $tempChatbot = $this->service->query()->create([
            'uuid'               => \Illuminate\Support\Str::uuid(),
            'user_id'            => auth()->id(),
            'title'              => 'Temp for Brain Brand Training',
            'ai_model'           => 'gpt-3.5-turbo',
            'ai_embedding_model' => 'text-embedding-3-small',
        ]);

        try {
            // Move embeddings temporarily to chatbot for processing
            $embeddings = $brainBrand->embeddings()->whereIn('id', $request->validated('data'))->get();
            $embeddings->each(function ($embedding) use ($tempChatbot) {
                $embedding->update(['chatbot_id' => $tempChatbot->id, 'brain_brand_id' => null]);
            });

            // Use parent method for embedding generation
            $request->merge(['id' => $tempChatbot->id]);
            parent::generateEmbedding($request);

            // Move embeddings back to Brain Brand
            $tempChatbot->embeddings()->update(['brain_brand_id' => $brainBrand->id, 'chatbot_id' => null]);

            return ChatbotEmbeddingResource::collection($brainBrand->embeddings()->get());

        } finally {
            // Clean up temporary chatbot
            $tempChatbot->delete();
        }
    }

    /**
     * Store a new Brain Brand with enhanced validation
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Check if BrainBrand table exists
            if (! Schema::hasTable('ext_brain_brands')) {
                Log::error('BrainBrand table does not exist', [
                    'user_id'          => auth()->id(),
                    'available_tables' => Schema::getConnection()->getDoctrineSchemaManager()->listTableNames(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Database setup required',
                    'details' => 'Brain Brand table not found. Please run migrations first.',
                    'debug'   => [
                        'table_exists'   => false,
                        'required_table' => 'ext_brain_brands',
                    ],
                ], 500);
            }

            $request->validate([
                'name'            => 'required|string|max:255|unique:ext_brain_brands,name,NULL,id,user_id,' . auth()->id(),
                'description'     => 'nullable|string|max:1000',
                'tone'            => 'nullable|string|max:255',
                'personality'     => 'nullable|string|max:255',
                'language_style'  => 'nullable|string|max:255',
                'auto_crawl'      => 'nullable|boolean',
                'auto_distribute' => 'nullable|boolean',
            ]);

            Log::info('BrainBrand creation started', [
                'user_id'      => auth()->id(),
                'name'         => $request->name,
                'request_data' => $request->all(),
            ]);

            $brainBrand = BrainBrand::create([
                'uuid'        => \Illuminate\Support\Str::uuid(),
                'user_id'     => auth()->id(),
                'name'        => $request->name,
                'description' => $request->description,
                'tone'        => $request->tone,
                'personality' => $request->personality,
                'brand_voice' => [
                    'language_style'  => $request->language_style,
                    'auto_crawl'      => $request->boolean('auto_crawl'),
                    'auto_distribute' => $request->boolean('auto_distribute'),
                ],
                'active' => true,
            ]);

            Log::info('BrainBrand created successfully', [
                'brain_brand_id'   => $brainBrand->id,
                'brain_brand_uuid' => $brainBrand->uuid,
                'user_id'          => auth()->id(),
            ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Brain Brand created successfully',
                'brainBrand' => $brainBrand,
                'redirect'   => route('dashboard.user.brain-brand.train', $brainBrand),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('BrainBrand creation validation failed', [
                'user_id'      => auth()->id(),
                'errors'       => $e->errors(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
                'debug'   => [
                    'request_method'    => $request->method(),
                    'request_url'       => $request->fullUrl(),
                    'user_id'           => auth()->id(),
                    'validation_errors' => $e->errors(),
                ],
            ], 422);
        } catch (Exception $e) {
            Log::error('BrainBrand creation failed', [
                'user_id'      => auth()->id(),
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Brain Brand',
                'details' => $e->getMessage(),
                'debug'   => [
                    'error_type'   => get_class($e),
                    'table_exists' => Schema::hasTable('ext_brain_brands'),
                    'user_id'      => auth()->id(),
                ],
            ], 500);
        }
    }

    /**
     * Distribute Brain Brand embeddings to all user's chatbots
     */
    public function distribute(BrainBrand $brainBrand): JsonResponse|RedirectResponse
    {
        try {
            // Verify ownership
            if ($brainBrand->user_id !== auth()->id()) {
                abort(403, 'Unauthorized');
            }

            // Check if auto distribution is enabled
            if ($brainBrand->auto_distribute) {
                return response()->json([
                    'success' => false,
                    'message' => 'Auto distribution is already enabled for this Brain Brand',
                ], 400);
            }

            // Distribute to all user's chatbots
            $this->distributionService->distributeToChatbots($brainBrand);

            Log::info('Brain Brand manually distributed', [
                'brain_brand_id' => $brainBrand->id,
                'user_id'        => auth()->id(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Brain Brand distributed successfully to all chatbots',
                ]);
            }

            return redirect()
                ->route('dashboard.user.brain-brand.index')
                ->with('success', __('Brain Brand distributed successfully to all chatbots'));

        } catch (Exception $e) {
            Log::error('Brain Brand distribution failed', [
                'brain_brand_id' => $brainBrand->id,
                'user_id'        => auth()->id(),
                'error'          => $e->getMessage(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to distribute Brain Brand',
                    'details' => $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->route('dashboard.user.brain-brand.index')
                ->with('error', __('Failed to distribute Brain Brand: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Get Brain Brand statistics and analytics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $brainBrand = BrainBrand::where('user_id', auth()->id())
                ->findOrFail($request->get('id'));

            $embeddings = $brainBrand->embeddings;

            $statistics = [
                'brain_brand' => [
                    'id'           => $brainBrand->id,
                    'name'         => $brainBrand->name,
                    'created_at'   => $brainBrand->created_at,
                    'last_updated' => $brainBrand->updated_at,
                ],
                'embeddings' => [
                    'total'   => $embeddings->count(),
                    'by_type' => [
                        'url'  => $embeddings->where('type', 'url')->count(),
                        'text' => $embeddings->where('type', 'text')->count(),
                        'qa'   => $embeddings->where('type', 'qa')->count(),
                        'file' => $embeddings->where('type', 'file')->count(),
                    ],
                    'trained' => $embeddings->whereNotNull('embedding')->count(),
                    'pending' => $embeddings->whereNull('embedding')->count(),
                ],
                'distribution' => [
                    'total_chatbots'    => \App\Extensions\Chatbot\System\Models\Chatbot::where('user_id', auth()->id())->count(),
                    'distributed_to'    => $embeddings->whereNotNull('brain_brand_id')->count(),
                    'last_distribution' => $brainBrand->updated_at,
                ],
                'quality_metrics' => [
                    'average_content_length' => $embeddings->avg('content') ? strlen($embeddings->avg('content')) : 0,
                    'unique_sources'         => $embeddings->whereNotNull('url')->unique('url')->count() +
                                     $embeddings->whereNotNull('file')->unique('file')->count() +
                                     $embeddings->where('type', 'text')->count() +
                                     $embeddings->where('type', 'qa')->count(),
                ],
            ];

            Log::info('BrainBrand statistics retrieved', [
                'brain_brand_id'   => $brainBrand->id,
                'total_embeddings' => $statistics['embeddings']['total'],
            ]);

            return response()->json([
                'success'    => true,
                'statistics' => $statistics,
            ]);

        } catch (Exception $e) {
            Log::error('BrainBrand statistics retrieval failed', [
                'brain_brand_id' => $request->get('id'),
                'error'          => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
