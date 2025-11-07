<?php

namespace App\Extensions\Chatbot\System\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatbotProductCategory extends Model
{
    use HasFactory;
    
    protected $table = 'ext_chatbot_product_categories';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'parent_id',
    ];

    /**
     * Get the user that owns the category.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChatbotProductCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(ChatbotProductCategory::class, 'parent_id');
    }

    /**
     * Get all products in this category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(ChatbotProduct::class, 'category_id');
    }

    /**
     * Scope to get root categories (no parent).
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get categories with products.
     */
    public function scopeWithProducts($query)
    {
        return $query->whereHas('products');
    }

    /**
     * Scope to search categories by name.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
    }

    /**
     * Check if this category has any products.
     */
    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }

    /**
     * Get the full category path (Parent > Child > Grandchild).
     */
    public function getFullPathAttribute(): string
    {
        $path = collect([$this->name]);
        $category = $this->parent;
        
        while ($category) {
            $path->prepend($category->name);
            $category = $category->parent;
        }
        
        return $path->implode(' > ');
    }

    /**
     * Get the depth level of this category.
     */
    public function getDepthAttribute(): int
    {
        $depth = 0;
        $category = $this->parent;
        
        while ($category) {
            $depth++;
            $category = $category->parent;
        }
        
        return $depth;
    }

    /**
     * Get total products count including subcategories.
     */
    public function getTotalProductsCountAttribute(): int
    {
        $count = $this->products()->count();
        
        foreach ($this->children as $child) {
            $count += $child->total_products_count;
        }
        
        return $count;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Extensions\Chatbot\ChatbotProductCategoryFactory::new();
    }
}