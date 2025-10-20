<?php

namespace App\Extensions\BrainBrand\System\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrainBrand extends Model
{
    protected $table = 'ext_brain_brands';

    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'description',
        'brand_voice',
        'tone',
        'personality',
        'active',
        'is_favorite',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_favorite' => 'boolean',
        'brand_voice' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function embeddings(): HasMany
    {
        return $this->hasMany(\App\Extensions\Chatbot\System\Models\ChatbotEmbedding::class, 'brain_brand_id');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
