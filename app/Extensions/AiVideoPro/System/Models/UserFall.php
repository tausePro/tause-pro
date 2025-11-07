<?php

namespace App\Extensions\AiVideoPro\System\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFall extends Model
{
    use HasFactory;

    protected $table = 'user_fall';

    protected $fillable = [
        'user_id',
        'is_demo',
        'prompt',
        'prompt_image_url',
        'status',
        'request_id',
        'response_url',
        'model',
        'video_url',
    ];

    // required for listing videos in documents page
    public function isFavoriteDoc(): false
    {
        return false;
    }
}
