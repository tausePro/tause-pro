<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Resources\Api;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class ChatbotResource extends JsonResource
{
    public function toArray(Request $request): array|Arrayable|JsonSerializable
    {
        return [
            'uuid'                       => $this->uuid,
            'title'                      => $this->title,
            'bubble_message'             => trans($this->bubble_message),
            'welcome_message'            => $this->welcome_message,
            'logo'                       => asset($this->logo),
            'avatar'                     => asset($this->avatar),
            'trigger_avatar_size'        => $this->trigger_avatar_size,
            'trigger_background'         => $this->trigger_background,
            'trigger_foreground'         => $this->trigger_foreground,
            'color_mode'                 => $this->color_mode,
            'color'                      => $this->color,
            'show_logo'                  => $this->show_logo,
            'show_date_and_time'         => $this->show_date_and_time,
            'show_average_response_time' => $this->show_average_response_time,
            'position'                   => $this->position,
            'active'                     => $this->active,
            'interaction_type'           => $this->interaction_type,
            'connect_message'            => $this->connect_message,
            'language'                   => app()->getLocale(),
            'is_email_collect'           => $this->is_email_collect,
            'is_contact'                 => $this->is_contact,
            'is_attachment'              => $this->is_attachment,
            'is_emoji'                   => $this->is_emoji,
            'is_articles'                => $this->is_articles,
            'is_links'                   => $this->is_links,
            'header_bg_type'             => $this->header_bg_type,
            'header_bg_color'            => $this->header_bg_color,
            'header_bg_gradient'         => $this->header_bg_gradient,
            'header_bg_image'            => asset($this->header_bg_image),
        ];
    }
}
