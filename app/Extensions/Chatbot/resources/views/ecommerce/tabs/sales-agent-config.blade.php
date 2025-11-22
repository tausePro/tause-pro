{{-- Sales Agent Configuration Tab --}}
<div class="space-y-8">
    {{-- Sección 1: Comportamiento del Agente --}}
    <div class="space-y-4">
        <h3 class="font-semibold text-lg">{{ __('Agent Behavior') }}</h3>
        
        {{-- Agent Name --}}
        <div>
            <label class="block text-sm font-medium mb-2">{{ __('Agent Name') }}</label>
            <input 
                type="text" 
                name="agent_name"
                value="{{ $salesAgentConfig->agent_name ?? 'Vendedor' }}"
                placeholder="Ej: Ali, Tu Vendedor"
                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p class="text-xs text-gray-500 mt-1">{{ __('How the agent will introduce itself') }}</p>
        </div>

        {{-- Agent Description --}}
        <div>
            <label class="block text-sm font-medium mb-2">{{ __('Agent Description') }}</label>
            <textarea 
                name="agent_description"
                rows="3"
                placeholder="Ej: Soy tu vendedor personal, aquí para ayudarte..."
                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >{{ $salesAgentConfig->agent_description ?? '' }}</textarea>
        </div>

        {{-- Tone Selection --}}
        <div>
            <label class="block text-sm font-medium mb-3">{{ __('Conversation Tone') }}</label>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input 
                        type="radio" 
                        name="tone" 
                        value="formal"
                        {{ ($salesAgentConfig->tone ?? 'friendly') === 'formal' ? 'checked' : '' }}
                        class="w-4 h-4"
                    />
                    <span class="ml-2 text-sm">{{ __('Formal and Professional') }}</span>
                </label>
                <label class="flex items-center">
                    <input 
                        type="radio" 
                        name="tone" 
                        value="casual"
                        {{ ($salesAgentConfig->tone ?? 'friendly') === 'casual' ? 'checked' : '' }}
                        class="w-4 h-4"
                    />
                    <span class="ml-2 text-sm">{{ __('Casual and Relaxed') }}</span>
                </label>
                <label class="flex items-center">
                    <input 
                        type="radio" 
                        name="tone" 
                        value="friendly"
                        {{ ($salesAgentConfig->tone ?? 'friendly') === 'friendly' ? 'checked' : '' }}
                        class="w-4 h-4"
                    />
                    <span class="ml-2 text-sm">{{ __('Friendly and Warm') }}</span>
                </label>
            </div>
        </div>

        {{-- Sales Strategy --}}
        <div>
            <label class="block text-sm font-medium mb-2">{{ __('Sales Strategy') }}</label>
            <select 
                name="sales_strategy"
                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="consultative" {{ ($salesAgentConfig->sales_strategy ?? 'helpful') === 'consultative' ? 'selected' : '' }}>
                    {{ __('Consultative (Ask needs)') }}
                </option>
                <option value="aggressive" {{ ($salesAgentConfig->sales_strategy ?? 'helpful') === 'aggressive' ? 'selected' : '' }}>
                    {{ __('Aggressive (Sell more)') }}
                </option>
                <option value="helpful" {{ ($salesAgentConfig->sales_strategy ?? 'helpful') === 'helpful' ? 'selected' : '' }}>
                    {{ __('Helpful (No pressure)') }}
                </option>
            </select>
        </div>

        {{-- Search Strategy --}}
        <div>
            <label class="block text-sm font-medium mb-2">{{ __('Search Strategy') }}</label>
            <select 
                name="search_strategy"
                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="keyword" {{ ($salesAgentConfig->search_strategy ?? 'semantic') === 'keyword' ? 'selected' : '' }}>
                    {{ __('By Keywords') }}
                </option>
                <option value="semantic" {{ ($salesAgentConfig->search_strategy ?? 'semantic') === 'semantic' ? 'selected' : '' }}>
                    {{ __('Semantic (AI)') }}
                </option>
                <option value="hybrid" {{ ($salesAgentConfig->search_strategy ?? 'semantic') === 'hybrid' ? 'selected' : '' }}>
                    {{ __('Hybrid (Both)') }}
                </option>
            </select>
        </div>

        {{-- Product Display Mode --}}
        <div>
            <label class="block text-sm font-medium mb-2">{{ __('Product Display') }}</label>
            <select 
                name="product_display_mode"
                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="conversational" {{ ($salesAgentConfig->product_display_mode ?? 'both') === 'conversational' ? 'selected' : '' }}>
                    {{ __('Natural Conversation Only') }}
                </option>
                <option value="cards" {{ ($salesAgentConfig->product_display_mode ?? 'both') === 'cards' ? 'selected' : '' }}>
                    {{ __('Cards Only') }}
                </option>
                <option value="both" {{ ($salesAgentConfig->product_display_mode ?? 'both') === 'both' ? 'selected' : '' }}>
                    {{ __('Conversation + Cards') }}
                </option>
            </select>
        </div>
    </div>

    <hr class="my-6">

    {{-- Sección 2: Configuración de Tarjetas de Productos --}}
    <div class="space-y-4">
        <h3 class="font-semibold text-lg">{{ __('Product Card Configuration') }}</h3>
        
        @php
            $cardConfig = $salesAgentConfig->getProductCardConfig();
        @endphp

        {{-- Button Color --}}
        <div>
            <label class="block text-sm font-medium mb-2">{{ __('Buy Button Color') }}</label>
            <div class="flex items-center gap-3">
                <div 
                    class="w-12 h-12 rounded-lg border-2 border-gray-300 cursor-pointer"
                    id="buttonColorPreview"
                    style="background-color: {{ $cardConfig['button_color'] }}"
                ></div>
                <input 
                    type="color"
                    name="product_card_config[button_color]"
                    value="{{ $cardConfig['button_color'] }}"
                    id="buttonColorInput"
                    class="w-16 h-10 cursor-pointer"
                />
                <input 
                    type="text"
                    name="product_card_config[button_color]"
                    value="{{ $cardConfig['button_color'] }}"
                    placeholder="#10b981"
                    class="flex-1 px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    pattern="^#[0-9A-Fa-f]{6}$"
                />
            </div>
        </div>

        {{-- Button Style --}}
        <div>
            <label class="block text-sm font-medium mb-2">{{ __('Button Style') }}</label>
            <select 
                name="product_card_config[button_style]"
                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="solid" {{ ($cardConfig['button_style'] ?? 'solid') === 'solid' ? 'selected' : '' }}>
                    {{ __('Solid') }}
                </option>
                <option value="gradient" {{ ($cardConfig['button_style'] ?? 'solid') === 'gradient' ? 'selected' : '' }}>
                    {{ __('Gradient') }}
                </option>
                <option value="outline" {{ ($cardConfig['button_style'] ?? 'solid') === 'outline' ? 'selected' : '' }}>
                    {{ __('Outline') }}
                </option>
            </select>
        </div>

        {{-- Card Shadow --}}
        <div>
            <label class="block text-sm font-medium mb-2">{{ __('Card Shadow') }}</label>
            <select 
                name="product_card_config[card_shadow]"
                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="none" {{ ($cardConfig['card_shadow'] ?? 'md') === 'none' ? 'selected' : '' }}>
                    {{ __('None') }}
                </option>
                <option value="sm" {{ ($cardConfig['card_shadow'] ?? 'md') === 'sm' ? 'selected' : '' }}>
                    {{ __('Small') }}
                </option>
                <option value="md" {{ ($cardConfig['card_shadow'] ?? 'md') === 'md' ? 'selected' : '' }}>
                    {{ __('Medium') }}
                </option>
                <option value="lg" {{ ($cardConfig['card_shadow'] ?? 'md') === 'lg' ? 'selected' : '' }}>
                    {{ __('Large') }}
                </option>
            </select>
        </div>

        {{-- Price Color --}}
        <div>
            <label class="block text-sm font-medium mb-2">{{ __('Price Color') }}</label>
            <div class="flex items-center gap-3">
                <div 
                    class="w-12 h-12 rounded-lg border-2 border-gray-300 cursor-pointer"
                    id="priceColorPreview"
                    style="background-color: {{ $cardConfig['price_color'] }}"
                ></div>
                <input 
                    type="color"
                    name="product_card_config[price_color]"
                    value="{{ $cardConfig['price_color'] }}"
                    id="priceColorInput"
                    class="w-16 h-10 cursor-pointer"
                />
            </div>
        </div>

        {{-- Toggles --}}
        <div class="space-y-2">
            <label class="flex items-center">
                <input 
                    type="checkbox" 
                    name="product_card_config[show_stock_indicator]"
                    value="1"
                    {{ ($cardConfig['show_stock_indicator'] ?? true) ? 'checked' : '' }}
                    class="w-4 h-4"
                />
                <span class="ml-2 text-sm">{{ __('Show stock indicator') }}</span>
            </label>
            <label class="flex items-center">
                <input 
                    type="checkbox" 
                    name="product_card_config[show_discount_badge]"
                    value="1"
                    {{ ($cardConfig['show_discount_badge'] ?? true) ? 'checked' : '' }}
                    class="w-4 h-4"
                />
                <span class="ml-2 text-sm">{{ __('Show discount badge') }}</span>
            </label>
        </div>
    </div>

    <hr class="my-6">

    {{-- Sección 3: Preview en Vivo --}}
    <div class="space-y-4">
        <h3 class="font-semibold text-lg">{{ __('Live Preview') }}</h3>
        
        <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
            @include('chatbot::ecommerce.partials.product-card-preview', [
                'cardConfig' => $cardConfig
            ])
        </div>
    </div>

    <hr class="my-6">

    {{-- Sección 4: Negociación y Cupones Dinámicos --}}
    <div class="space-y-4">
        <h3 class="font-semibold text-lg">{{ __('Dynamic Negotiation & Coupons') }}</h3>
        
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
            <p class="text-sm text-blue-800">
                💡 <strong>{{ __('How it works') }}:</strong> {{ __('When a customer hesitates about price, the AI can automatically generate a discount coupon to close the sale.') }}
            </p>
        </div>
        
        {{-- Enable Negotiation --}}
        <div>
            <label class="flex items-center">
                <input 
                    type="checkbox" 
                    name="negotiation_enabled"
                    value="1"
                    {{ old('negotiation_enabled', $chatbot->negotiation_enabled ?? false) ? 'checked' : '' }}
                    class="w-4 h-4"
                    x-model="negotiationEnabled"
                />
                <span class="ml-2 text-sm font-medium">{{ __('Enable Dynamic Negotiation') }}</span>
            </label>
            <p class="text-xs text-gray-500 mt-1">
                {{ __('Allow the AI to offer discounts when customers show price resistance') }}
            </p>
        </div>
        
        <div x-show="negotiationEnabled" class="space-y-4 mt-4">
            {{-- Max Discount --}}
            <div>
                <label class="block text-sm font-medium mb-2">{{ __('Maximum Discount (%)') }}</label>
                <input 
                    type="number" 
                    name="negotiation_max_discount"
                    value="{{ old('negotiation_max_discount', $chatbot->negotiation_max_discount ?? 10) }}"
                    min="5"
                    max="50"
                    step="5"
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('Maximum discount the AI can offer (recommended: 10-15%)') }}
                </p>
            </div>
            
            {{-- Min Cart Value --}}
            <div>
                <label class="block text-sm font-medium mb-2">{{ __('Minimum Cart Value') }}</label>
                <input 
                    type="number" 
                    name="negotiation_min_cart_value"
                    value="{{ old('negotiation_min_cart_value', $chatbot->negotiation_min_cart_value ?? 50000) }}"
                    min="0"
                    step="10000"
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('Minimum cart value to enable negotiation (in COP)') }}
                </p>
            </div>
            
            {{-- Coupon Duration --}}
            <div>
                <label class="block text-sm font-medium mb-2">{{ __('Coupon Duration (minutes)') }}</label>
                <select 
                    name="negotiation_coupon_duration"
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="15" {{ old('negotiation_coupon_duration', $chatbot->negotiation_coupon_duration ?? 30) == 15 ? 'selected' : '' }}>15 {{ __('minutes') }}</option>
                    <option value="30" {{ old('negotiation_coupon_duration', $chatbot->negotiation_coupon_duration ?? 30) == 30 ? 'selected' : '' }}>30 {{ __('minutes') }}</option>
                    <option value="60" {{ old('negotiation_coupon_duration', $chatbot->negotiation_coupon_duration ?? 30) == 60 ? 'selected' : '' }}>1 {{ __('hour') }}</option>
                    <option value="120" {{ old('negotiation_coupon_duration', $chatbot->negotiation_coupon_duration ?? 30) == 120 ? 'selected' : '' }}>2 {{ __('hours') }}</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('How long the coupon will be valid after generation') }}
                </p>
            </div>
            
            {{-- Trigger Keywords --}}
            <div x-data="{
                triggers: {{ json_encode(old('negotiation_triggers', $chatbot->negotiation_triggers ?? ['caro', 'costoso', 'descuento', 'rebaja', 'oferta'])) }},
                newTrigger: '',
                addTrigger() {
                    const trigger = this.newTrigger.trim().toLowerCase();
                    if (trigger && !this.triggers.includes(trigger)) {
                        this.triggers.push(trigger);
                        this.newTrigger = '';
                    }
                },
                removeTrigger(index) {
                    this.triggers.splice(index, 1);
                }
            }">
                <label class="block text-sm font-medium mb-2">{{ __('Negotiation Trigger Words') }}</label>
                
                <div class="flex gap-2 mb-2">
                    <input 
                        type="text"
                        x-model="newTrigger"
                        @keydown.enter.prevent="addTrigger"
                        placeholder="{{ __('e.g., expensive, discount, cheaper...') }}"
                        class="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <button 
                        type="button"
                        @click="addTrigger"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                    >
                        {{ __('Add') }}
                    </button>
                </div>
                
                <div class="flex flex-wrap gap-2">
                    <template x-for="(trigger, index) in triggers" :key="index">
                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                            <span x-text="trigger"></span>
                            <button 
                                type="button"
                                @click="removeTrigger(index)"
                                class="ml-1 text-blue-600 hover:text-blue-800"
                            >
                                ✕
                            </button>
                            <input type="hidden" name="negotiation_triggers[]" :value="trigger">
                        </span>
                    </template>
                </div>
                
                <p class="text-xs text-gray-500 mt-2">
                    {{ __('Words that trigger the negotiation mode (e.g., "expensive", "discount", "cheaper")') }}
                </p>
            </div>
            
            {{-- Warning --}}
            <div class="bg-amber-50 p-4 rounded-lg border border-amber-200">
                <p class="text-sm text-amber-800">
                    ⚠️ <strong>{{ __('Important') }}:</strong> {{ __('Coupons are generated automatically in WooCommerce. Make sure your store has the WooCommerce REST API enabled.') }}
                </p>
            </div>
        </div>
    </div>

    <hr class="my-6">

    {{-- Sección 5: Prompt Personalizado --}}
    <div class="space-y-4">
        <h3 class="font-semibold text-lg">{{ __('Custom Prompt') }}</h3>
        
        <textarea 
            name="custom_prompt"
            rows="6"
            placeholder="{{ __('Instructions to train how the agent responds...') }}"
            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >{{ $salesAgentConfig->custom_prompt ?? '' }}</textarea>
        
        <p class="text-xs text-gray-500">
            {{ __('Use variables like {agent_name}, {tone}, {strategy} in your prompt') }}
        </p>
    </div>
</div>

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del preview
    const previewCard = document.querySelector('.enhanced-product-card');
    const previewButton = previewCard?.querySelector('button');
    const previewPrice = previewCard?.querySelector('[style*="font-size: 1.1rem"]');
    
    // Color picker sync - Button Color
    const buttonColorInput = document.getElementById('buttonColorInput');
    const buttonColorPreview = document.getElementById('buttonColorPreview');
    const buttonColorText = document.querySelector('input[name="product_card_config[button_color]"]:last-of-type');
    
    if (buttonColorInput) {
        buttonColorInput.addEventListener('input', function() {
            const color = this.value;
            if (buttonColorPreview) buttonColorPreview.style.backgroundColor = color;
            if (buttonColorText) buttonColorText.value = color;
            
            // Actualizar preview del botón
            if (previewButton) {
                const buttonStyle = document.querySelector('select[name="product_card_config[button_style]"]')?.value || 'solid';
                updateButtonStyle(previewButton, color, buttonStyle);
            }
        });
    }
    
    if (buttonColorText) {
        buttonColorText.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                if (buttonColorInput) buttonColorInput.value = this.value;
                if (buttonColorPreview) buttonColorPreview.style.backgroundColor = this.value;
                
                // Actualizar preview del botón
                if (previewButton) {
                    const buttonStyle = document.querySelector('select[name="product_card_config[button_style]"]')?.value || 'solid';
                    updateButtonStyle(previewButton, this.value, buttonStyle);
                }
            }
        });
    }
    
    // Color picker sync - Price Color
    const priceColorInput = document.getElementById('priceColorInput');
    const priceColorPreview = document.getElementById('priceColorPreview');
    
    if (priceColorInput) {
        priceColorInput.addEventListener('input', function() {
            const color = this.value;
            if (priceColorPreview) priceColorPreview.style.backgroundColor = color;
            
            // Actualizar preview del precio
            if (previewPrice) {
                previewPrice.style.color = color;
            }
        });
    }
    
    // Button Style change
    const buttonStyleSelect = document.querySelector('select[name="product_card_config[button_style]"]');
    if (buttonStyleSelect) {
        buttonStyleSelect.addEventListener('change', function() {
            const color = buttonColorInput?.value || '#10b981';
            updateButtonStyle(previewButton, color, this.value);
        });
    }
    
    // Card Shadow change
    const cardShadowSelect = document.querySelector('select[name="product_card_config[card_shadow]"]');
    if (cardShadowSelect && previewCard) {
        cardShadowSelect.addEventListener('change', function() {
            const shadows = {
                'none': 'none',
                'sm': '0 1px 2px rgba(0,0,0,0.05)',
                'md': '0 4px 6px rgba(0,0,0,0.1)',
                'lg': '0 10px 15px rgba(0,0,0,0.1)'
            };
            previewCard.style.boxShadow = shadows[this.value] || shadows.md;
        });
    }
    
    // Stock indicator toggle
    const stockCheckbox = document.querySelector('input[name="product_card_config[show_stock_indicator]"]');
    if (stockCheckbox) {
        stockCheckbox.addEventListener('change', function() {
            const stockIndicator = previewCard?.querySelector('[style*="color: #10b981"]');
            if (stockIndicator) {
                stockIndicator.style.display = this.checked ? 'block' : 'none';
            }
        });
    }
    
    // Discount badge toggle
    const discountCheckbox = document.querySelector('input[name="product_card_config[show_discount_badge]"]');
    if (discountCheckbox) {
        discountCheckbox.addEventListener('change', function() {
            const discountBadge = previewCard?.querySelector('[style*="background-color: #ef4444"]');
            if (discountBadge) {
                discountBadge.style.display = this.checked ? 'inline-block' : 'none';
            }
        });
    }
    
    // Función para actualizar estilo del botón
    function updateButtonStyle(button, color, style) {
        if (!button) return;
        
        button.style.transition = 'all 0.2s';
        
        if (style === 'solid') {
            button.style.backgroundColor = color;
            button.style.color = '#ffffff';
            button.style.border = 'none';
        } else if (style === 'outline') {
            button.style.backgroundColor = 'transparent';
            button.style.color = color;
            button.style.border = `2px solid ${color}`;
        } else if (style === 'gradient') {
            button.style.background = `linear-gradient(135deg, ${color} 0%, ${color}dd 100%)`;
            button.style.color = '#ffffff';
            button.style.border = 'none';
        }
    }
});
</script>
@endpush
