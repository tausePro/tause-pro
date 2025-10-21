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

    {{-- Sección 4: Prompt Personalizado --}}
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
    // Color picker sync
    document.getElementById('buttonColorInput')?.addEventListener('input', function() {
        document.getElementById('buttonColorPreview').style.backgroundColor = this.value;
        document.querySelector('input[name="product_card_config[button_color]"]:last-of-type').value = this.value;
    });

    document.getElementById('priceColorInput')?.addEventListener('input', function() {
        document.getElementById('priceColorPreview').style.backgroundColor = this.value;
    });

    // Sync text input to color picker
    document.querySelector('input[name="product_card_config[button_color]"]:last-of-type')?.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            document.getElementById('buttonColorInput').value = this.value;
            document.getElementById('buttonColorPreview').style.backgroundColor = this.value;
        }
    });
</script>
@endpush
