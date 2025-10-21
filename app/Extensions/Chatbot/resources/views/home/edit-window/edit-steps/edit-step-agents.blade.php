{{-- Editing Step 5 - Agents --}}
<div
    class="col-start-1 col-end-1 row-start-1 row-end-1 transition-all"
    data-step="5"
    x-show="editingStep === 5"
    x-transition:enter-start="opacity-0 -translate-x-3"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-3"
>
    <h2 class="mb-3.5">
        @lang('Agents Hub')
    </h2>
    <p class="text-xs/5 opacity-60 lg:max-w-[360px]">
        @lang('Configure AI agents to handle specific tasks and improve customer interactions.')
    </p>

    <div class="flex flex-col gap-5 pt-9" x-data="agentsManager()">
        {{-- Sales Agent --}}
        <div class="rounded-xl border border-border p-4 transition-all hover:border-primary/30">
            {{-- Agent Header --}}
            <div class="mb-3 flex items-start justify-between gap-3">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🤖</span>
                        <h3 class="font-semibold text-heading-foreground">@lang('Sales Agent')</h3>
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-2xs font-medium text-blue-700">
                            @lang('Starter+')
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-heading-foreground/60">
                        @lang('Helps customers find and buy products')
                    </p>
                </div>
                
                <div class="flex items-center gap-2">
                    {{-- Toggle Switch --}}
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input 
                            type="checkbox" 
                            class="peer sr-only" 
                            x-model="agents.sales.enabled"
                            @change="toggleAgent('sales')"
                        >
                        <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/50"></div>
                    </label>
                </div>
            </div>

            {{-- Agent Settings (shown when enabled) --}}
            <div x-show="agents.sales.enabled" x-collapse>
                <div class="space-y-3 border-t border-border pt-3">
                    {{-- Keywords --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                            @lang('Keywords')
                        </label>
                        <input
                            type="text"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            x-model="agents.sales.keywords"
                            placeholder="comprar, precio, producto, buy, price"
                        >
                        <p class="mt-1 text-2xs opacity-60">
                            @lang('Comma-separated keywords that trigger this agent')
                        </p>
                    </div>

                    {{-- Priority --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                            @lang('Priority')
                        </label>
                        <select
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            x-model="agents.sales.priority"
                        >
                            <option value="10">@lang('High')</option>
                            <option value="5">@lang('Medium')</option>
                            <option value="1">@lang('Low')</option>
                        </select>
                    </div>

                    {{-- WooCommerce & Wompi Config --}}
                    @if ($chatbot->woocommerce_enabled || $chatbot->wompi_enabled)
                        <div class="rounded-lg bg-green-50 p-3">
                            <div class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-green-800">
                                        @lang('E-commerce Integration Active')
                                    </p>
                                    <p class="mt-1 text-2xs text-green-700">
                                        @if ($chatbot->woocommerce_enabled)
                                            @lang('WooCommerce connected')
                                        @endif
                                        @if ($chatbot->wompi_enabled)
                                            @lang('Wompi payments enabled')
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg bg-yellow-50 p-3">
                            <div class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-yellow-800">
                                        @lang('Configure E-commerce Integration')
                                    </p>
                                    <p class="mt-1 text-2xs text-yellow-700">
                                        @lang('Go to Configure tab to set up WooCommerce or Wompi')
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Support Agent --}}
        <div class="rounded-xl border border-border p-4 transition-all hover:border-primary/30 relative">
            {{-- Pro Badge Overlay --}}
            <div class="absolute inset-0 bg-white/60 backdrop-blur-[2px] rounded-xl flex items-center justify-center z-10">
                <div class="text-center">
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-yellow-400 to-yellow-600 px-4 py-2 text-white shadow-lg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span class="font-semibold">@lang('Pro Plan Required')</span>
                    </div>
                    <p class="mt-2 text-xs text-heading-foreground/60">
                        @lang('Upgrade to unlock this agent')
                    </p>
                </div>
            </div>

            {{-- Agent Header --}}
            <div class="mb-3 flex items-start justify-between gap-3 opacity-50">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">💬</span>
                        <h3 class="font-semibold text-heading-foreground">@lang('Support Agent')</h3>
                        <span class="rounded-full bg-purple-100 px-2 py-0.5 text-2xs font-medium text-purple-700">
                            @lang('Pro')
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-heading-foreground/60">
                        @lang('Provides technical support and answers questions')
                    </p>
                </div>
            </div>
        </div>

        {{-- Appointment Agent --}}
        <div class="rounded-xl border border-border p-4 transition-all hover:border-primary/30 relative">
            {{-- Pro Badge Overlay --}}
            <div class="absolute inset-0 bg-white/60 backdrop-blur-[2px] rounded-xl flex items-center justify-center z-10">
                <div class="text-center">
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-yellow-400 to-yellow-600 px-4 py-2 text-white shadow-lg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span class="font-semibold">@lang('Pro Plan Required')</span>
                    </div>
                    <p class="mt-2 text-xs text-heading-foreground/60">
                        @lang('Upgrade to unlock this agent')
                    </p>
                </div>
            </div>

            {{-- Agent Header --}}
            <div class="mb-3 flex items-start justify-between gap-3 opacity-50">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📅</span>
                        <h3 class="font-semibold text-heading-foreground">@lang('Appointment Agent')</h3>
                        <span class="rounded-full bg-purple-100 px-2 py-0.5 text-2xs font-medium text-purple-700">
                            @lang('Pro')
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-heading-foreground/60">
                        @lang('Schedules appointments and manages calendar')
                    </p>
                </div>
            </div>
        </div>

        {{-- Lead Capture Agent --}}
        <div class="rounded-xl border border-border p-4 transition-all hover:border-primary/30 relative">
            {{-- Pro Badge Overlay --}}
            <div class="absolute inset-0 bg-white/60 backdrop-blur-[2px] rounded-xl flex items-center justify-center z-10">
                <div class="text-center">
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-yellow-400 to-yellow-600 px-4 py-2 text-white shadow-lg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span class="font-semibold">@lang('Pro Plan Required')</span>
                    </div>
                    <p class="mt-2 text-xs text-heading-foreground/60">
                        @lang('Upgrade to unlock this agent')
                    </p>
                </div>
            </div>

            {{-- Agent Header --}}
            <div class="mb-3 flex items-start justify-between gap-3 opacity-50">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📋</span>
                        <h3 class="font-semibold text-heading-foreground">@lang('Lead Capture Agent')</h3>
                        <span class="rounded-full bg-purple-100 px-2 py-0.5 text-2xs font-medium text-purple-700">
                            @lang('Pro')
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-heading-foreground/60">
                        @lang('Captures lead information and qualifies prospects')
                    </p>
                </div>
            </div>
        </div>

        {{-- Add Custom Agent Button --}}
        <button
            type="button"
            class="rounded-xl border-2 border-dashed border-border p-6 transition-all hover:border-primary/50 hover:bg-primary/5 relative"
            disabled
        >
            {{-- Pro Badge Overlay --}}
            <div class="absolute inset-0 bg-white/60 backdrop-blur-[2px] rounded-xl flex items-center justify-center z-10">
                <div class="text-center">
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-yellow-400 to-yellow-600 px-4 py-2 text-white shadow-lg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span class="font-semibold">@lang('Pro Plan Required')</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-center gap-2 opacity-50">
                <svg class="h-8 w-8 text-heading-foreground/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span class="font-semibold text-heading-foreground">@lang('Add Custom Agent')</span>
                <span class="text-xs text-heading-foreground/60">@lang('Create your own specialized agent')</span>
            </div>
        </button>
    </div>
</div>

<script>
    function agentsManager() {
        return {
            agents: {
                sales: {
                    enabled: false,
                    keywords: 'comprar, precio, producto, buy, price, product',
                    priority: 10
                }
            },

            init() {
                // Load agents configuration from chatbot
                if (this.activeChatbot && this.activeChatbot.agents) {
                    this.agents = this.activeChatbot.agents;
                }
            },

            toggleAgent(agentType) {
                console.log(`[Agents] Toggling ${agentType}:`, this.agents[agentType].enabled);
                
                // Update activeChatbot
                if (!this.activeChatbot.agents) {
                    this.activeChatbot.agents = {};
                }
                this.activeChatbot.agents[agentType] = this.agents[agentType];
                
                // Auto-save
                this.submitData();
            }
        }
    }
</script>
