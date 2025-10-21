<?php

namespace App\Extensions\Chatbot\System\Http\Requests;

use App\Extensions\Chatbot\System\Enums\ColorModeEnum;
use App\Extensions\Chatbot\System\Enums\HeaderBgEnum;
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
            'do_not_go_beyond_instructions' => ['sometimes', 'nullable'],
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
            'header_bg_type'                => ['string', Rule::enum(HeaderBgEnum::class)],
            'header_bg_color'               => ['sometimes', 'nullable', 'string'],
            'header_bg_gradient'            => ['sometimes', 'nullable', 'string'],
            'header_bg_image_blob'          => ['sometimes', 'nullable'],
            'human_agent_conditions'        => ['sometimes', 'nullable', 'array'],
            // GDPR
            'gdpr_enabled'                  => ['sometimes', 'nullable', 'boolean'],
            'gdpr_message'                  => ['sometimes', 'nullable', 'string'],
            'gdpr_required'                 => ['sometimes', 'nullable', 'boolean'],
            // WooCommerce
            'woocommerce_url'               => ['sometimes', 'nullable', 'url'],
            'woocommerce_key'               => ['sometimes', 'nullable', 'string'],
            'woocommerce_secret'            => ['sometimes', 'nullable', 'string'],
            'woocommerce_enabled'           => ['sometimes', 'nullable', 'boolean'],
            // Wompi
            'wompi_public_key'              => ['sometimes', 'nullable', 'string'],
            'wompi_private_key'             => ['sometimes', 'nullable', 'string'],
            'wompi_enabled'                 => ['sometimes', 'nullable', 'boolean'],
            'wompi_environment'             => ['sometimes', 'nullable', 'string', Rule::in(['test', 'production'])],
            // Sales Agent
            'sales_agent_enabled'           => ['sometimes', 'nullable', 'boolean'],
            'sales_agent_keywords'          => ['sometimes', 'nullable', 'array'],
            // Advanced Customization
            'custom_bg_image_url'           => ['sometimes', 'nullable', 'string', 'url'],
            'welcome_text'                  => ['sometimes', 'nullable', 'string'],
            'initial_prompt_text'           => ['sometimes', 'nullable', 'string'],
            'cta_button_text'               => ['sometimes', 'nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active'                        => true,
            'trigger_avatar_size'           => $this->get('trigger_avatar_size') ?? '60px',
        ]);
    }
}
