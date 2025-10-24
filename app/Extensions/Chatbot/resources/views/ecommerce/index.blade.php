@extends('panel.layout.app', ['disable_tblr' => true])

@section('title', __('E-commerce & Ventas'))

@section('content')
    <div class="py-10">
        <div class="container-fluid">
            <div class="mb-4">
                <h2 class="mb-3.5">🛍️ E-commerce & Agente de Ventas</h2>
                <p class="text-xs/5 opacity-60 lg:max-w-[360px]">
                    Configura WooCommerce, Wompi y activa el agente de ventas conversacional
                </p>
            </div>

            {{-- Mensajes de éxito/error --}}
            @if(session('success'))
                <div class="mb-4 rounded-lg bg-green-50 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="flex flex-col gap-7 pt-9">
                {{-- WooCommerce Configuration --}}
                <div class="border-t pt-7">
                    <h3 class="mb-4 text-sm font-semibold text-heading-foreground">
                        🛒 Configuración WooCommerce
                    </h3>
                    
                    <form action="{{ route('dashboard.chatbot.ecommerce.woocommerce.save', $chatbot) }}" method="POST" class="flex flex-col gap-5">           
                        @csrf
                        
                        <div>
                            <x-forms.input
                                class:label="text-heading-foreground"
                                label="{{ __('URL de tu tienda') }}"
                                name="woocommerce_url"
                                type="url"
                                size="lg"
                                placeholder="https://mitienda.com"
                                value="{{ old('woocommerce_url', $chatbot->woocommerce_url) }}"
                            />
                            <p class="mt-1 text-2xs opacity-60">
                                La URL completa de tu tienda WooCommerce
                            </p>
                            @error('woocommerce_url')
                                <div class="mt-2 text-2xs/5 font-medium text-red-500">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <x-forms.input
                                class:label="text-heading-foreground"
                                label="{{ __('Consumer Key') }}"
                                name="woocommerce_key"
                                size="lg"
                                placeholder="ck_xxxxxxxxxxxxx"
                                value="{{ old('woocommerce_key', $chatbot->woocommerce_key) }}"
                            />
                            @error('woocommerce_key')
                                <div class="mt-2 text-2xs/5 font-medium text-red-500">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <x-forms.input
                                class:label="text-heading-foreground"
                                label="{{ __('Consumer Secret') }}"
                                name="woocommerce_secret"
                                type="password"
                                size="lg"
                                placeholder="cs_xxxxxxxxxxxxx"
                                value="{{ old('woocommerce_secret', $chatbot->woocommerce_secret) }}"
                            />
                            <p class="mt-1 text-2xs opacity-60">
                                <a href="https://woocommerce.github.io/woocommerce-rest-api-docs/#rest-api-keys" target="_blank" class="underline">
                                    ¿Cómo obtener las API Keys?
                                </a>
                            </p>
                            @error('woocommerce_secret')
                                <div class="mt-2 text-2xs/5 font-medium text-red-500">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <x-forms.input
                                class="h-[18px] w-[34px] [background-size:0.625rem]"
                                class:label="text-heading-foreground flex-row-reverse justify-between"
                                label="{{ __('Habilitar WooCommerce') }}"
                                name="woocommerce_enabled"
                                size="lg"
                                type="checkbox"
                                switcher
                                value="1"
                                :checked="old('woocommerce_enabled', $chatbot->woocommerce_enabled)"
                            />
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="btn btn-primary">
                                💾 Guardar Configuración
                            </button>
                            
                            @if($chatbot->woocommerce_url && $chatbot->woocommerce_key)
                                <form action="{{ route('dashboard.chatbot.ecommerce.sync', $chatbot) }}" method="POST" class="inline" id="sync-form-{{ $chatbot->id }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success" {{ !$chatbot->woocommerce_enabled ? 'disabled' : '' }} onclick="this.disabled=true; this.textContent='⏳ Sincronizando...'; this.form.submit();">
                                        🔄 Sincronizar Productos
                                    </button>
                                </form>
                            @endif
                        </div>

                        @if($chatbot->woocommerce_url && $chatbot->woocommerce_key)
                            <div class="mt-3 rounded-lg bg-gray-50 p-3">
                                <p class="text-2xs opacity-60">
                                    Última sincronización: <strong>{{ $chatbot->woocommerce_last_sync ? $chatbot->woocommerce_last_sync->diffForHumans() : 'Nunca' }}</strong>
                                </p>
                                @if(!$chatbot->woocommerce_enabled)
                                    <p class="mt-1 text-2xs text-amber-600">
                                        ⚠️ WooCommerce está deshabilitado. Activa el toggle y guarda para poder sincronizar.
                                    </p>
                                @endif
                            </div>
                        @endif
                    </form>
                </div>

                {{-- Wompi Configuration --}}
                <div class="border-t pt-7">
                    <h3 class="mb-4 text-sm font-semibold text-heading-foreground">
                        💳 Configuración Wompi
                    </h3>
                    
                    <form action="{{ route('dashboard.chatbot.ecommerce.wompi.save', $chatbot) }}" method="POST" class="flex flex-col gap-5">                 
                        @csrf
                        
                        <div>
                            <x-forms.input
                                class:label="text-heading-foreground"
                                label="{{ __('Public Key') }}"
                                name="wompi_public_key"
                                size="lg"
                                placeholder="pub_test_xxxxxxxxxxxxx"
                                value="{{ old('wompi_public_key', $chatbot->wompi_public_key) }}"
                            />
                            @error('wompi_public_key')
                                <div class="mt-2 text-2xs/5 font-medium text-red-500">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <x-forms.input
                                class:label="text-heading-foreground"
                                label="{{ __('Private Key') }}"
                                name="wompi_private_key"
                                type="password"
                                size="lg"
                                placeholder="prv_test_xxxxxxxxxxxxx"
                                value="{{ old('wompi_private_key', $chatbot->wompi_private_key) }}"
                            />
                            <p class="mt-1 text-2xs opacity-60">
                                <a href="https://docs.wompi.co/docs/colombia/integracion-checkout-wompi/" target="_blank" class="underline">
                                    ¿Cómo obtener las llaves de Wompi?
                                </a>
                            </p>
                            @error('wompi_private_key')
                                <div class="mt-2 text-2xs/5 font-medium text-red-500">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <x-forms.input
                                class:label="text-heading-foreground"
                                label="{{ __('Entorno') }}"
                                name="wompi_environment"
                                size="lg"
                                type="select"
                                value="{{ old('wompi_environment', $chatbot->wompi_environment) }}"
                            >
                                <option value="test" {{ old('wompi_environment', $chatbot->wompi_environment) === 'test' ? 'selected' : '' }}>
                                    🧪 Pruebas (Sandbox)
                                </option>
                                <option value="production" {{ old('wompi_environment', $chatbot->wompi_environment) === 'production' ? 'selected' : '' }}>
                                    🚀 Producción
                                </option>
                            </x-forms.input>
                            @error('wompi_environment')
                                <div class="mt-2 text-2xs/5 font-medium text-red-500">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <x-forms.input
                                class="h-[18px] w-[34px] [background-size:0.625rem]"
                                class:label="text-heading-foreground flex-row-reverse justify-between"
                                label="{{ __('Habilitar Wompi') }}"
                                name="wompi_enabled"
                                size="lg"
                                type="checkbox"
                                switcher
                                value="1"
                                :checked="old('wompi_enabled', $chatbot->wompi_enabled)"
                            />
                        </div>

                        <button type="submit" class="btn btn-primary">
                            💾 Guardar Configuración
                        </button>
                    </form>
                </div>

                {{-- Sales Agent Configuration --}}
                <div class="border-t pt-7" x-data="{ showAdvanced: false }">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-sm font-semibold text-heading-foreground">
                            🤖 Configuración del Agente de Ventas
                        </h3>
                        <button 
                            type="button"
                            @click="showAdvanced = !showAdvanced"
                            class="btn btn-sm btn-outline-primary"
                        >
                            <span x-show="!showAdvanced">⚙️ Configuración Avanzada</span>
                            <span x-show="showAdvanced">📋 Configuración Básica</span>
                        </button>
                    </div>
                    
                    <form action="{{ route('dashboard.chatbot.ecommerce.sales-agent.save', $chatbot) }}" method="POST" x-data="salesAgentConfig" class="flex flex-col gap-5">                               
                        @csrf
                        
                        {{-- Configuración Básica --}}
                        <div x-show="!showAdvanced">
                        
                        <div>
                            <x-forms.input
                                class="h-[18px] w-[34px] [background-size:0.625rem]"
                                class:label="text-heading-foreground flex-row-reverse justify-between"
                                label="{{ __('Activar Agente de Ventas') }}"
                                name="sales_agent_enabled"
                                size="lg"
                                type="checkbox"
                                switcher
                                value="1"
                                :checked="old('sales_agent_enabled', $chatbot->sales_agent_enabled)"
                            />
                            <p class="mt-1 text-2xs opacity-60">
                                Cuando esté activo, el chatbot detectará automáticamente intenciones de compra y mostrará el catálogo de productos
                            </p>
                        </div>

                        <div>
                            <label class="lqd-input-label flex cursor-pointer items-center gap-2 text-2xs font-medium leading-none text-label mb-3">
                                Palabras clave para detectar intención de compra
                            </label>
                            
                            <div class="flex gap-2 mb-2">
                                <input 
                                    type="text"
                                    class="lqd-input lqd-input-lg h-11 block peer w-full px-4 py-2 border border-input-border bg-input-background text-input-foreground text-base ring-offset-0 transition-colors focus:border-secondary focus:outline-0 focus:ring focus:ring-secondary"
                                    x-model="newKeyword"
                                    @keydown.enter.prevent="addKeyword"
                                    placeholder="Ej: comprar, precio, producto..."
                                >
                                <button 
                                    type="button"
                                    class="btn btn-primary"
                                    @click="addKeyword"
                                >
                                    ➕ Agregar
                                </button>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(keyword, index) in keywords" :key="index">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-2xs font-medium text-primary">
                                        <span x-text="keyword"></span>
                                        <button 
                                            type="button"
                                            class="ml-1 text-primary/60 hover:text-primary"
                                            @click="removeKeyword(index)"
                                        >
                                            ✕
                                        </button>
                                        <input type="hidden" name="sales_agent_keywords[]" :value="keyword">
                                    </span>
                                </template>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success">
                            💾 Guardar Configuración del Agente
                        </button>
                        </div>
                        
                        {{-- Configuración Avanzada --}}
                        <div x-show="showAdvanced" style="display: none;">
                            @php
                                // Crear clase anónima con método getProductCardConfig
                                $salesAgentConfig = new class($chatbot) {
                                    private $chatbot;
                                    public $agent_name;
                                    public $agent_description;
                                    public $tone;
                                    public $sales_strategy;
                                    public $search_strategy;
                                    public $product_display_mode;
                                    public $custom_prompt;
                                    
                                    public function __construct($chatbot) {
                                        $this->chatbot = $chatbot;
                                        $this->agent_name = $chatbot->sales_agent_name ?? 'Vendedor';
                                        $this->agent_description = $chatbot->sales_agent_description ?? '';
                                        $this->tone = $chatbot->sales_agent_tone ?? 'friendly';
                                        $this->sales_strategy = $chatbot->sales_agent_strategy ?? 'helpful';
                                        $this->search_strategy = $chatbot->sales_agent_search_strategy ?? 'semantic';
                                        $this->product_display_mode = $chatbot->sales_agent_display_mode ?? 'both';
                                        $this->custom_prompt = $chatbot->sales_agent_custom_prompt ?? '';
                                    }
                                    
                                    public function getProductCardConfig() {
                                        $config = is_string($this->chatbot->sales_agent_card_config) 
                                            ? json_decode($this->chatbot->sales_agent_card_config, true) 
                                            : ($this->chatbot->sales_agent_card_config ?? []);
                                        
                                        return array_merge([
                                            'button_color' => '#10b981',
                                            'button_style' => 'solid',
                                            'card_shadow' => 'md',
                                            'price_color' => '#667eea',
                                            'show_stock_indicator' => true,
                                            'show_discount_badge' => true,
                                        ], is_array($config) ? $config : []);
                                    }
                                };
                            @endphp
                            
                            @include('chatbot::ecommerce.tabs.sales-agent-config', [
                                'salesAgentConfig' => $salesAgentConfig,
                                'chatbot' => $chatbot
                            ])
                            
                            <button type="submit" class="btn btn-success mt-6">
                                💾 Guardar Configuración Avanzada
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Products List --}}
            @if($chatbot->woocommerce_enabled && $products->count() > 0)
                <div class="border-t pt-7">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-heading-foreground">
                            📦 Productos Sincronizados ({{ $products->total() }})
                        </h3>
                        <form action="{{ route('dashboard.chatbot.ecommerce.sync', $chatbot) }}" method="POST" class="inline" id="sync-form-products-{{ $chatbot->id }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary" onclick="this.disabled=true; this.innerHTML='<svg class=\'mr-1 inline-block h-4 w-4 animate-spin\' fill=\'none\' viewBox=\'0 0 24 24\'><circle class=\'opacity-25\' cx=\'12\' cy=\'12\' r=\'10\' stroke=\'currentColor\' stroke-width=\'4\'></circle><path class=\'opacity-75\' fill=\'currentColor\' d=\'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\'></path></svg> Sincronizando...'; this.form.submit();">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 inline-block h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Sincronizar Ahora
                            </button>
                        </form>
                    </div>
                    <div class="mb-3 rounded-lg bg-blue-50 p-3">
                        <p class="text-2xs text-blue-800">
                            <strong>Última sincronización:</strong> {{ $chatbot->woocommerce_last_sync ? $chatbot->woocommerce_last_sync->diffForHumans() : 'Nunca' }}
                        </p>
                    </div>
                    
                    <div class="overflow-hidden rounded-lg border">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-2xs font-medium text-gray-500 uppercase tracking-wider">Imagen</th>
                                        <th class="px-6 py-3 text-left text-2xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                        <th class="px-6 py-3 text-left text-2xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                        <th class="px-6 py-3 text-left text-2xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                                        <th class="px-6 py-3 text-left text-2xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                        <th class="px-6 py-3 text-left text-2xs font-medium text-gray-500 uppercase tracking-wider">Última Sync</th>
                                        <th class="px-6 py-3 text-left text-2xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th class="px-6 py-3 text-left text-2xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($products as $product)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <img 
                                                    src="{{ $product->image_url }}" 
                                                    alt="{{ $product->name }}"      
                                                    class="h-12 w-12 rounded-lg object-cover"
                                                >
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                                @if($product->has_discount)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-2xs font-medium bg-red-100 text-red-800">
                                                        -{{ $product->discount_percentage }}%
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $product->sku ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $product->formatted_price }}</div>
                                                @if($product->has_discount)
                                                    <div class="text-2xs text-gray-500 line-through">
                                                        ${{ number_format($product->regular_price, 0, ',', '.') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($product->in_stock)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-2xs font-medium bg-green-100 text-green-800">
                                                        En stock
                                                    </span>
                                                    @if($product->stock_quantity)
                                                        <div class="text-2xs text-gray-500">{{ $product->stock_quantity }} unidades</div>
                                                    @endif
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-2xs font-medium bg-red-100 text-red-800">
                                                        Agotado
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $product->last_synced_at->diffForHumans() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($product->is_active)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-2xs font-medium bg-green-100 text-green-800">
                                                        Activo
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-2xs font-medium bg-gray-100 text-gray-800">
                                                        Inactivo
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-2">
                                                    <form action="{{ route('dashboard.chatbot.ecommerce.product.toggle', [$chatbot, $product]) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-{{ $product->is_active ? 'warning' : 'success' }}" title="{{ $product->is_active ? 'Desactivar' : 'Activar' }}">
                                                            {{ $product->is_active ? '⏸️' : '▶️' }}
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('dashboard.chatbot.ecommerce.product.delete', [$chatbot, $product]) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este producto?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                            🗑️
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4">
                        {{ $products->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('salesAgentConfig', () => ({
            keywords: {{ json_encode(old('sales_agent_keywords', $chatbot->sales_agent_keywords ?? ['comprar', 'precio', 'producto', 'catálogo', 'ver productos', 'busco'])) }},
            newKeyword: '',
            
            addKeyword() {
                const keyword = this.newKeyword.trim().toLowerCase();
                if (keyword && !this.keywords.includes(keyword)) {
                    this.keywords.push(keyword);
                    this.newKeyword = '';
                }
            },
            
            removeKeyword(index) {
                this.keywords.splice(index, 1);
            }
        }));
    });
</script>
@endpush

