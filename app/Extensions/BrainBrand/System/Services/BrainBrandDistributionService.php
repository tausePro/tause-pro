<?php

namespace App\Extensions\BrainBrand\System\Services;

use App\Extensions\BrainBrand\System\Models\BrainBrand;
use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotEmbedding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BrainBrandDistributionService
{
    /**
     * Distribute Brain Brand embeddings to all user's chatbots
     */
    public function distributeToChatbots(BrainBrand $brainBrand): void
    {
        try {
            $userChatbots = Chatbot::where('user_id', $brainBrand->user_id)->get();
            
            foreach ($userChatbots as $chatbot) {
                $this->distributeToChatbot($brainBrand, $chatbot);
            }

            Log::info('Brain Brand embeddings distributed successfully', [
                'brain_brand_id' => $brainBrand->id,
                'user_id' => $brainBrand->user_id,
                'chatbots_count' => $userChatbots->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to distribute Brain Brand embeddings', [
                'brain_brand_id' => $brainBrand->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Distribute Brain Brand embeddings to specific chatbot
     */
    public function distributeToChatbot(BrainBrand $brainBrand, Chatbot $chatbot): void
    {
        $embeddings = $brainBrand->embeddings()->get();

        foreach ($embeddings as $embedding) {
            // Check if embedding already exists for this chatbot
            $existingEmbedding = ChatbotEmbedding::where('chatbot_id', $chatbot->id)
                ->where('content', $embedding->content)
                ->where('type', $embedding->type)
                ->first();

            if (!$existingEmbedding) {
                // Create new embedding for this chatbot
                ChatbotEmbedding::create([
                    'chatbot_id' => $chatbot->id,
                    'brain_brand_id' => $brainBrand->id,
                    'engine' => $embedding->engine,
                    'title' => $embedding->title,
                    'file' => $embedding->file,
                    'url' => $embedding->url,
                    'content' => $embedding->content,
                    'embedding' => $embedding->embedding,
                    'type' => $embedding->type,
                    'trained_at' => $embedding->trained_at,
                ]);
            }
        }
    }

    /**
     * Sync Brain Brand embeddings with specific chatbot
     */
    public function syncWithChatbot(BrainBrand $brainBrand, Chatbot $chatbot): void
    {
        DB::transaction(function () use ($brainBrand, $chatbot) {
            // Remove old Brain Brand embeddings from this chatbot
            ChatbotEmbedding::where('chatbot_id', $chatbot->id)
                ->where('brain_brand_id', $brainBrand->id)
                ->delete();

            // Add current Brain Brand embeddings to this chatbot
            $this->distributeToChatbot($brainBrand, $chatbot);
        });
    }

    /**
     * Get Brain Brand embeddings that are not yet distributed to chatbot
     */
    public function getUndistributedEmbeddings(BrainBrand $brainBrand, Chatbot $chatbot): array
    {
        $brainBrandEmbeddings = $brainBrand->embeddings()->pluck('id');
        $chatbotEmbeddings = ChatbotEmbedding::where('chatbot_id', $chatbot->id)
            ->where('brain_brand_id', $brainBrand->id)
            ->pluck('id');

        return $brainBrandEmbeddings->diff($chatbotEmbeddings)->toArray();
    }

    /**
     * Update all chatbots when Brain Brand embeddings change
     */
    public function updateAllChatbots(BrainBrand $brainBrand): void
    {
        $userChatbots = Chatbot::where('user_id', $brainBrand->user_id)->get();
        
        foreach ($userChatbots as $chatbot) {
            $this->syncWithChatbot($brainBrand, $chatbot);
        }
    }
}
