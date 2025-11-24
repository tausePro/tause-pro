<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotProduct extends Model
{
    protected $table = 'ext_chatbot_products';

    protected $fillable = [
        'chatbot_id',
        'user_id',
        'woocommerce_id',
        'sku',
        'name',
        'description',
        'short_description',
        'price',
        'regular_price',
        'sale_price',
        'image_url',
        'gallery_urls',
        'in_stock',
        'stock_quantity',
        'categories',
        'tags',
        'product_url',
        'metadata',
        'last_synced_at',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'gallery_urls' => 'json',
        'categories' => 'json',
        'tags' => 'json',
        'metadata' => 'json',
        'in_stock' => 'boolean',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Atributos calculados que deben estar disponibles en JSON
     * para el Sales Agent (frontend).
     *
     * - formatted_price: precio formateado en COP
     * - has_discount: si el producto tiene descuento
     * - discount_percentage: porcentaje de descuento aplicado
     */
    protected $appends = [
        'formatted_price',
        'has_discount',
        'discount_percentage',
    ];

    /**
     * Relación con Chatbot
     */
    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class);
    }

    /**
     * Scope para productos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para productos en stock
     */
    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    /**
     * Obtener precio formateado
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format((float) $this->price, 0, ',', '.') . ' COP';
    }

    /**
     * Verificar si tiene descuento
     */
    public function getHasDiscountAttribute(): bool
    {
        return $this->sale_price && $this->sale_price < $this->regular_price;
    }

    /**
     * Calcular porcentaje de descuento
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->has_discount) {
            return null;
        }

        return (int) round((($this->regular_price - $this->sale_price) / $this->regular_price) * 100);
    }

    /**
     * Obtener imagen principal o placeholder
     */
    public function getImageAttribute(): string
    {
        return $this->image_url ?? asset('images/product-placeholder.png');
    }
}

