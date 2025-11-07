<?php

namespace App\Extensions\UrlToVideo\System\Http\Requests;

use App\Packages\Creatify\Enums\VisualStyle;
use Illuminate\Foundation\Http\FormRequest;

class GeneratePreviewVideoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'link'                 => 'required|string',
            'language'             => 'required|string',
            'override_script'      => 'required|string',
            'visual_styles'        => 'sometimes|array|in:' . implode(',', VisualStyle::getValues()),
            'target_audience'      => 'sometimes|string',
            'aspect_ratio'         => 'sometimes|string',
            'video_length'         => 'sometimes|integer',
            'override_avatar'      => 'sometimes|string',
            'override_voice'       => 'sometimes|string',
            'background_music_url' => 'sometimes|string',
            'no_background_music'  => 'sometimes',
            'no_caption' 		        => 'sometimes',
        ];
    }
}
