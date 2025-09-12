<?php

namespace App\Extensions\Chatbot\System\Http\Requests;

use App\Extensions\Chatbot\System\Enums\ColorModeEnum;
use App\Extensions\Chatbot\System\Enums\PositionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChatbotCustomizeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id'                            => ['required', 'integer', 'exists:ext_chatbots,id'],
            'interaction_type'              => ['sometimes', 'nullable', 'string'],
            'uuid'                          => ['required', 'string'],
            'user_id'                       => ['required', 'integer', 'exists:users,id'],
            'title'                         => ['required', 'string'],
            'bubble_message'                => ['required', 'string'],
            'welcome_message'               => ['required', 'string'],
            'connect_message'               => ['sometimes', 'nullable', 'string'],
            'instructions'                  => ['required', 'string'],
            'do_not_go_beyond_instructions' => ['required', 'boolean'],
            'language'                      => ['sometimes', 'nullable', 'string'],
            'ai_model'                      => ['required', 'string'],
            'logo'                          => ['sometimes', 'nullable', 'string'],
            'avatar'                        => ['sometimes', 'nullable', 'string'],
            'trigger_avatar_size'           => ['sometimes', 'nullable', 'string'],
            'trigger_background'            => ['sometimes', 'nullable', 'string'],
            'trigger_foreground'            => ['sometimes', 'nullable', 'string'],
            'color_mode'                    => ['string', Rule::enum(ColorModeEnum::class)],
            'color'                         => ['sometimes', 'nullable', 'string'],
            'show_logo'                     => ['sometimes', 'boolean'],
            'show_date_and_time'            => ['sometimes', 'boolean'],
            'show_average_response_time'    => ['sometimes', 'boolean'],
            'welcome_background'            => ['sometimes', 'nullable', 'string'],
            'welcome_greeting'              => ['sometimes', 'nullable', 'string'],
            'welcome_subtitle'              => ['sometimes', 'nullable', 'string'],
            'welcome_button_text'           => ['sometimes', 'nullable', 'string'],
            'welcome_button_subtitle'       => ['sometimes', 'nullable', 'string'],
            'active'                        => ['sometimes', 'boolean'],
            'position'                      => ['string', Rule::enum(PositionEnum::class)],
            'footer_link'                   => ['sometimes', 'nullable', 'string'],
            'whatsapp_link'                 => ['sometimes', 'nullable', 'string'],
            'telegram_link'                 => ['sometimes', 'nullable', 'string'],
            'watch_product_tour_link'       => ['sometimes', 'nullable', 'string'],
            'is_email_collect'              => ['sometimes', 'nullable', 'boolean'],
            'is_contact'                    => ['sometimes', 'nullable', 'boolean'],
            'is_attachment'                 => ['sometimes', 'nullable', 'boolean'],
            'is_emoji'                      => ['sometimes', 'nullable', 'boolean'],
            'is_articles'                   => ['sometimes', 'nullable', 'boolean'],
            'is_links'                      => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'trigger_avatar_size'        => $this->get('trigger_avatar_size') ?? '60px',
        ]);
    }
}
