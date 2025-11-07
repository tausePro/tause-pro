<?php

namespace App\Extensions\Chatbot\System\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatbotProduct extends Model
{
    use HasFactory;
    
    protected $table = 'ext_chatbot_products';

    protected $fillable = [
        'user_id',
        'chatbot_id',
        'name',
        'description',
        'price',
        'sku',
        'category_id',
        'image_url',
        'purchase_url',
        'availability',
        'stock_quantity',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the product.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the chatbot that owns the product.
     */
    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class, 'chatbot_id');
    }

    /**
     * Get the category that the product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ChatbotProductCategory::class, 'category_id');
    }

    /**
     * Get the knowledge base articles that reference this product.
     */
    public function knowledgeBaseArticles(): BelongsToMany
    {
        return $this->belongsToMany(
            ChatbotKnowledgeBaseArticle::class,
            'ext_chatbot_knowledge_base_article_products',
            'product_id',
            'article_id'
        );
    }

    /**
     * Scope to filter products by availability.
     */
    public function scopeAvailable($query)
    {
        return $query->where('availability', 'in_stock')
                    ->where('stock_quantity', '>', 0);
    }

    /**
     * Scope to filter products by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to search products by name or description.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    /**
     * Get the formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Check if the product is in stock.
     */
    public function isInStock(): bool
    {
        return $this->availability === 'in_stock' && $this->stock_quantity > 0;
    }

    /**
     * Check if the product is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->availability === 'out_of_stock' || $this->stock_quantity <= 0;
    }

    /**
     * Get the availability status with human-readable format.
     */
    public function getAvailabilityStatusAttribute(): string
    {
        return match($this->availability) {
            'in_stock' => $this->stock_quantity > 0 ? 'In Stock (' . $this->stock_quantity . ')' : 'Out of Stock',
            'out_of_stock' => 'Out of Stock',
            'discontinued' => 'Discontinued',
            default => 'Unknown'
        };
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Extensions\Chatbot\ChatbotProductFactory::new();
    }
}