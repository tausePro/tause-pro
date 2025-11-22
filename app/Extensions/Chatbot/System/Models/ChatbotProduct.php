<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ChatbotProduct extends Model
{
    protected $table = 'ext_chatbot_products';

    /**
     * Cache para verificación de columnas
     */
    protected static ?array $columnCache = null;

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
        'price'          => 'decimal:2',
        'regular_price'  => 'decimal:2',
        'sale_price'     => 'decimal:2',
        'gallery_urls'   => 'json',
        'categories'     => 'json',
        'tags'           => 'json',
        'metadata'       => 'json',
        'in_stock'       => 'boolean',
        'is_active'      => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Relación con Chatbot
     */
    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class);
    }

    /**
     * Verificar si una columna existe (con cache y manejo seguro de errores)
     */
    protected static function hasColumnCached(string $column): bool
    {
        // Si el cache ya está inicializado, usarlo
        if (static::$columnCache !== null) {
            return isset(static::$columnCache[$column]);
        }

        // Intentar inicializar el cache de forma segura
        try {
            // Verificar que la conexión a BD esté disponible
            if (! app()->bound('db')) {
                // Si no hay conexión DB, asumir que las columnas existen (comportamiento seguro)
                return true;
            }

            $columns = Schema::getColumnListing((new static)->getTable());
            static::$columnCache = array_flip($columns);

            return isset(static::$columnCache[$column]);
        } catch (Throwable $e) {
            // Si falla por cualquier razón, asumir que las columnas existen
            // Esto es seguro porque si la columna no existe, la query simplemente fallará
            // pero no romperá el external chatbot
            static::$columnCache = ['is_active' => true, 'in_stock' => true];

            return true;
        }
    }

    /**
     * Scope para productos activos
     */
    public function scopeActive($query)
    {
        try {
            if (static::hasColumnCached('is_active')) {
                return $query->where('is_active', true);
            }
        } catch (Throwable $e) {
            // Si falla, simplemente retornar el query sin filtrar
            // Esto evita romper el external chatbot
        }

        return $query;
    }

    /**
     * Scope para productos en stock
     */
    public function scopeInStock($query)
    {
        try {
            if (static::hasColumnCached('in_stock')) {
                return $query->where('in_stock', true);
            }
        } catch (Throwable $e) {
            // Si falla, simplemente retornar el query sin filtrar
            // Esto evita romper el external chatbot
        }

        return $query;
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
        if (! $this->has_discount) {
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
