<?php

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\ChatbotProductCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ChatbotCategoryService
{
    /**
     * Get all categories in a hierarchical structure.
     */
    public function getCategoryTree(?int $parentId = null): Collection
    {
        $cacheKey = "category_tree_{$parentId}";
        
        return Cache::remember($cacheKey, 600, function () use ($parentId) { // Cache for 10 minutes
            return ChatbotProductCategory::with(['children.children.children']) // Load 3 levels deep
                ->where('parent_id', $parentId)
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get root categories (categories without parent).
     */
    public function getRootCategories(): Collection
    {
        return $this->getCategoryTree(null);
    }

    /**
     * Get category breadcrumb path.
     */
    public function getCategoryBreadcrumb(int $categoryId): array
    {
        $category = ChatbotProductCategory::find($categoryId);
        
        if (!$category) {
            return [];
        }

        $breadcrumb = [];
        $current = $category;
        
        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => str_replace(' ', '-', strtolower($current->name))
            ]);
            $current = $current->parent;
        }
        
        return $breadcrumb;
    }

    /**
     * Search categories by name or description.
     */
    public function searchCategories(string $query, int $limit = 20): Collection
    {
        return ChatbotProductCategory::search($query)
            ->withCount('products')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Create a new category.
     */
    public function createCategory(array $data): ChatbotProductCategory
    {
        DB::beginTransaction();
        
        try {
            // Validate parent category exists if provided
            if (!empty($data['parent_id'])) {
                $parent = ChatbotProductCategory::find($data['parent_id']);
                if (!$parent) {
                    throw new \InvalidArgumentException('Parent category not found');
                }
                
                // Prevent circular references
                if ($this->wouldCreateCircularReference($data['parent_id'], null)) {
                    throw new \InvalidArgumentException('Cannot create circular reference in category hierarchy');
                }
            }

            $category = ChatbotProductCategory::create([
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'parent_id' => $data['parent_id'] ?? null,
            ]);

            // Clear cache
            $this->clearCategoryCache();

            DB::commit();
            
            Log::info('Category created', [
                'category_id' => $category->id,
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'parent_id' => $data['parent_id'] ?? null
            ]);

            return $category;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create category', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(int $categoryId, array $data): ChatbotProductCategory
    {
        DB::beginTransaction();
        
        try {
            $category = ChatbotProductCategory::findOrFail($categoryId);
            
            // Validate parent category if being changed
            if (isset($data['parent_id']) && $data['parent_id'] !== $category->parent_id) {
                if (!empty($data['parent_id'])) {
                    $parent = ChatbotProductCategory::find($data['parent_id']);
                    if (!$parent) {
                        throw new \InvalidArgumentException('Parent category not found');
                    }
                    
                    // Prevent circular references
                    if ($this->wouldCreateCircularReference($data['parent_id'], $categoryId)) {
                        throw new \InvalidArgumentException('Cannot create circular reference in category hierarchy');
                    }
                }
            }
            
            $category->update([
                'name' => $data['name'] ?? $category->name,
                'description' => $data['description'] ?? $category->description,
                'parent_id' => $data['parent_id'] ?? $category->parent_id,
            ]);

            // Clear cache
            $this->clearCategoryCache();

            DB::commit();
            
            Log::info('Category updated', [
                'category_id' => $category->id,
                'changes' => $category->getChanges()
            ]);

            return $category->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update category', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Get categories with their product counts.
     */
    public function getCategoriesWithProductCounts(): Collection
    {
        return ChatbotProductCategory::withCount('products')
            ->orderBy('name')
            ->get();
    }

    /**
     * Check if moving a category would create a circular reference.
     */
    private function wouldCreateCircularReference(int $newParentId, ?int $categoryId): bool
    {
        if (!$categoryId || $newParentId === $categoryId) {
            return true;
        }
        
        $parent = ChatbotProductCategory::find($newParentId);
        
        while ($parent) {
            if ($parent->id === $categoryId) {
                return true;
            }
            $parent = $parent->parent;
        }
        
        return false;
    }

    /**
     * Clear all category-related cache.
     */
    private function clearCategoryCache(): void
    {
        // Clear category tree cache for all possible parent IDs
        $parentIds = ChatbotProductCategory::distinct()->pluck('parent_id')->filter();
        $parentIds->push(null); // Include root categories
        
        foreach ($parentIds as $parentId) {
            Cache::forget("category_tree_{$parentId}");
        }
    }
}