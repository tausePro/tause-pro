<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Models;

use Exception;
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
     * Verificar si una columna existe (con cache y manejo ultra-seguro de errores)
     * Este método nunca debe lanzar excepciones que rompan el external chatbot
     */
    protected static function hasColumnCached(string $column): bool
    {
        // Si el cache ya está inicializado, usarlo
        if (static::$columnCache !== null) {
            return isset(static::$columnCache[$column]);
        }

        // Intentar inicializar el cache de forma ultra-segura
        // Usar @ para suprimir cualquier warning/error que pueda romper el external chatbot
        try {
            // Verificar que la aplicación esté completamente inicializada
            if (! function_exists('app') || ! app()->bound('db')) {
                // Si no hay conexión DB disponible, asumir que las columnas existen
                // Esto es seguro porque en staging las columnas SÍ existen
                static::$columnCache = ['is_active' => true, 'in_stock' => true];

                return true;
            }

            // Intentar obtener las columnas de forma segura
            $columns = @Schema::getColumnListing((new static)->getTable());

            if (is_array($columns) && ! empty($columns)) {
                static::$columnCache = array_flip($columns);

                return isset(static::$columnCache[$column]);
            }

            // Si no se pudieron obtener las columnas, asumir que existen
            static::$columnCache = ['is_active' => true, 'in_stock' => true];

            return true;
        } catch (Throwable $e) {
            // Si falla por CUALQUIER razón, asumir que las columnas existen
            // Esto es seguro porque en staging las columnas SÍ existen
            // Si no existen, la query fallará pero no romperá el external chatbot
            static::$columnCache = ['is_active' => true, 'in_stock' => true];

            return true;
        } catch (Exception $e) {
            // Catch adicional por si acaso
            static::$columnCache = ['is_active' => true, 'in_stock' => true];

            return true;
        }
    }

    /**
     * Scope para productos activos
     * Este scope nunca debe lanzar excepciones que rompan el external chatbot
     */
    public function scopeActive($query)
    {
        // Usar try-catch ultra-defensivo para evitar cualquier error
        try {
            if (static::hasColumnCached('is_active')) {
                return $query->where('is_active', true);
            }
        } catch (Throwable $e) {
            // Si falla, simplemente retornar el query sin filtrar
            // Esto evita romper el external chatbot
        } catch (Exception $e) {
            // Catch adicional
        }

        return $query;
    }

    /**
     * Scope para productos en stock
     * Este scope nunca debe lanzar excepciones que rompan el external chatbot
     */
    public function scopeInStock($query)
    {
        // Usar try-catch ultra-defensivo para evitar cualquier error
        try {
            if (static::hasColumnCached('in_stock')) {
                return $query->where('in_stock', true);
            }
        } catch (Throwable $e) {
            // Si falla, simplemente retornar el query sin filtrar
            // Esto evita romper el external chatbot
        } catch (Exception $e) {
            // Catch adicional
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
