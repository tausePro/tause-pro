{{-- Editing Step 6 - Agents --}}
<div
    class="col-start-1 col-end-1 row-start-1 row-end-1 transition-all"
    data-step="6"
    x-show="editingStep === 6"
    x-transition:enter-start="opacity-0 -translate-x-3"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-3"
>
    {{-- Registrar componente Alpine ANTES de usarlo --}}
    @push('script')
    <script>
        const registerHumanAgentComponents = () => {
            Alpine.data('agentsManager', () => ({
                agents: [],
                loading: false,

                async loadAgents() {
                    this.loading = true;
                    
                    // Obtener ID del chatbot desde el store o input hidden
                    let chatbotId = null;
                    try {
                        const store = Alpine.store('externalChatbotEditor');
                        chatbotId = store?.activeChatbot?.id;
                    } catch (_) {}

                    if (!chatbotId || chatbotId === 'new_chatbot') {
                        this.agents = [];
                        this.loading = false;
                        return;
                    }

                    try {
                        const response = await fetch(`/dashboard/chatbot/${chatbotId}/agents`, {
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                            }
                        });

                        if (response.ok) {
                            const data = await response.json();
                            this.agents = data.agents || [];
                        } else {
                            this.agents = [];
                        }
                    } catch (error) {
                        console.error('Failed to load agents:', error);
                        this.agents = [];
                    } finally {
                        this.loading = false;
                    }
                }
            }));

            Alpine.data('humanAgentScheduleCard', (dayLabels, defaultSchedule) => ({
                dayLabels,
                defaultSchedule,
                store: null,

                init() {
                    this.store = Alpine.store('externalChatbotEditor');
                    this.ensureSchedule(true);

                    this.$watch(
                        () => this.store?.activeChatbot?.id,
                        () => this.ensureSchedule(true)
                    );
                },

                get chatbot() {
                    return this.store?.activeChatbot ?? null;
                },

                ensureSchedule(force = false) {
                    if (! this.chatbot) {
                        return;
                    }

                    const schedule = this.chatbot.human_agent_schedule;

                    if (
                        force ||
                        ! Array.isArray(schedule) ||
                        schedule.length === 0
                    ) {
                        this.chatbot.human_agent_schedule = JSON.parse(JSON.stringify(this.defaultSchedule));
                    }
                },

                resetSchedule() {
                    if (! this.chatbot) {
                        return;
                    }

                    this.chatbot.human_agent_schedule = JSON.parse(JSON.stringify(this.defaultSchedule));
                    submitData();
                }
            }));
        };

        if (typeof Alpine !== 'undefined' && Alpine.data) {
            registerHumanAgentComponents();
        } else {
            document.addEventListener('alpine:init', registerHumanAgentComponents);
        }
    </script>
    @endpush

    @php
        $human_agent_timezone_options = $human_agent_timezone_options ?? ['UTC' => 'UTC'];
        $default_human_agent_schedule = $default_human_agent_schedule ?? [];
        $human_agent_schedule_day_labels = $human_agent_schedule_day_labels ?? [];
    @endphp

    <h2 class="mb-3.5">
        @lang('Agents Hub')
    </h2>
    <p class="text-xs/5 opacity-60 lg:max-w-[360px]">
        @lang('Configure AI agents to handle specific tasks and improve customer interactions.')
    </p>

    {{-- Agentes Configurados (Nueva Arquitectura) --}}
    <div class="mt-6 mb-4 rounded-lg border border-blue-200 bg-blue-50/50 p-4" x-data="agentsManager()" x-init="loadAgents()">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-heading-foreground flex items-center gap-2">
                    <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    @lang('Agentes Activos')
                </h3>
                <p class="mt-1 text-2xs text-heading-foreground/60">
                    @lang('Agentes configurados con el nuevo sistema de orquestación')
                </p>
            </div>
            <button 
                @click="loadAgents()" 
                class="rounded-lg border border-border bg-background px-3 py-1.5 text-xs hover:bg-primary/5"
                :disabled="loading"
            >
                <span x-show="!loading">@lang('Actualizar')</span>
                <span x-show="loading">@lang('Cargando...')</span>
            </button>
        </div>

        <div x-show="agents.length === 0 && !loading" class="rounded-lg border border-dashed border-border p-4 text-center">
            <p class="text-xs text-heading-foreground/60">
                @lang('No hay agentes configurados aún. Los agentes se crearán automáticamente cuando se active el Sales Agent.')
            </p>
        </div>

        <div x-show="loading" class="rounded-lg border border-border p-4 text-center">
            <p class="text-xs text-heading-foreground/60">@lang('Cargando agentes...')</p>
        </div>

        <div class="space-y-2" x-show="agents.length > 0 && !loading">
            <template x-for="agent in agents" :key="agent.id">
                <div class="flex items-center justify-between rounded-lg border border-border bg-background p-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-heading-foreground" x-text="agent.name"></span>
                            <span 
                                class="rounded-full px-2 py-0.5 text-2xs font-medium"
                                :class="{
                                    'bg-green-100 text-green-700': agent.is_enabled,
                                    'bg-gray-100 text-gray-700': !agent.is_enabled
                                }"
                                x-text="agent.is_enabled ? '@lang('Activo')' : '@lang('Inactivo')'"
                            ></span>
                            <span 
                                class="rounded-full px-2 py-0.5 text-2xs font-medium bg-blue-100 text-blue-700"
                                x-text="agent.agent_type"
                            ></span>
                        </div>
                        <p class="mt-1 text-2xs text-heading-foreground/60" x-text="agent.description || ''"></p>
                        <div class="mt-1 flex items-center gap-3 text-2xs text-heading-foreground/50">
                            <span>@lang('Prioridad'): <span class="font-medium" x-text="agent.priority"></span></span>
                            <span x-show="agent.triggers && agent.triggers.keywords">
                                @lang('Keywords'): <span class="font-medium" x-text="(agent.triggers.keywords || []).join(', ')"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="flex flex-col gap-5 pt-9">
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
                            x-model="activeChatbot.sales_agent_enabled"
                            @change="submitData()"
                        >
                        <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/50"></div>
                    </label>
                </div>
            </div>

            {{-- Agent Settings (shown when enabled) --}}
            <div x-show="activeChatbot.sales_agent_enabled" x-collapse>
                <div class="space-y-3 border-t border-border pt-3">
                    {{-- Keywords --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                            @lang('Keywords')
                        </label>
                        <input
                            type="text"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            x-model="activeChatbot.sales_agent_keywords"
                            @input="submitData()"
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
                            x-model="activeChatbot.sales_agent_priority"
                            @change="submitData()"
                        >
                            <option value="10">@lang('High')</option>
                            <option value="5">@lang('Medium')</option>
                            <option value="1">@lang('Low')</option>
                        </select>
                    </div>

                    {{-- WooCommerce, Wompi & ePayco Config --}}
                    <div x-show="activeChatbot.woocommerce_enabled || activeChatbot.wompi_enabled || activeChatbot.epayco_enabled">
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
                                        <span x-show="activeChatbot.woocommerce_enabled">@lang('WooCommerce connected')</span>
                                        <span x-show="activeChatbot.wompi_enabled">@lang('Wompi payments enabled')</span>
                                        <span x-show="activeChatbot.epayco_enabled">@lang('ePayco payments enabled')</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div x-show="!activeChatbot.woocommerce_enabled && !activeChatbot.wompi_enabled && !activeChatbot.epayco_enabled">
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
                    </div>
                </div>
            </div>
        </div>

        {{-- Human Agent --}}
        <div class="rounded-xl border border-border p-4 transition-all hover:border-primary/30">
            {{-- Agent Header --}}
            <div class="mb-3 flex items-start justify-between gap-3">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">👤</span>
                        <h3 class="font-semibold text-heading-foreground">@lang('Human Agent')</h3>
                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-2xs font-medium text-green-700">
                            @lang('Free')
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-heading-foreground/60">
                        @lang('Allow users to connect with a live support agent')
                    </p>
                </div>
            </div>

            {{-- Agent Settings --}}
            <div class="space-y-3 border-t border-border pt-3">
                {{-- Command --}}
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                        @lang('Comando de activación')
                    </label>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-heading-foreground">#</span>
                        <input
                            type="text"
                            class="flex-1 rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            x-model="activeChatbot.human_agent_command"
                            @input="submitData()"
                            placeholder="agente"
                        >
                    </div>
                    <p class="mt-1 text-2xs opacity-60">
                        @lang('Los usuarios escribirán #comando para hablar con un agente humano')
                    </p>
                </div>

                {{-- Tip Message --}}
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                        @lang('Mensaje informativo')
                    </label>
                    <textarea
                        rows="3"
                        class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        x-model="activeChatbot.human_agent_tip_message"
                        @input="submitData()"
                        placeholder="💡 **Tip:** En cualquier momento puedes escribir #agente para ser atendido por un asesor humano."
                    ></textarea>
                    <p class="mt-1 text-2xs opacity-60">
                        @lang('Este mensaje se mostrará una sola vez al inicio de la conversación')
                    </p>
                </div>

                {{-- Connect Message --}}
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                        @lang('Mensaje de conexión')
                    </label>
                    <textarea
                        rows="2"
                        class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        x-model="activeChatbot.connect_message"
                        @input="submitData()"
                        placeholder="He transferido tu solicitud a un agente humano. Un agente se conectará contigo lo antes posible."
                    ></textarea>
                    <p class="mt-1 text-2xs opacity-60">
                        @lang('Mensaje que se envía cuando el usuario se conecta con un agente')
                    </p>
                </div>

                <div class="rounded-lg border border-border/60 p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium text-heading-foreground">
                                @lang('Atención con IA')
                            </p>
                            <p class="text-2xs text-heading-foreground/60">
                                @lang('Desactiva esta opción para que todas las conversaciones pasen directo a agentes humanos.')
                            </p>
                        </div>
                        <label class="ml-auto inline-flex items-center gap-2 text-xs font-semibold text-heading-foreground">
                            <input
                                type="checkbox"
                                class="peer sr-only"
                                x-model="activeChatbot.ai_handling_enabled"
                                @change="submitData()"
                            >
                            <span
                                class="flex h-6 w-11 items-center rounded-full border border-border px-1 transition peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/30"
                                :class="activeChatbot.ai_handling_enabled ? 'bg-primary text-primary-foreground' : 'bg-background'"
                            >
                                <span
                                    class="h-4 w-4 rounded-full bg-white shadow transition"
                                    :class="activeChatbot.ai_handling_enabled ? 'translate-x-4' : 'translate-x-0'"
                                ></span>
                            </span>
                            <span x-text="activeChatbot.ai_handling_enabled ? '{{ __('IA activa') }}' : '{{ __('IA en pausa') }}'"></span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                        @lang('Mensaje fuera de horario')
                    </label>
                    <textarea
                        rows="2"
                        class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        x-model="activeChatbot.human_agent_offline_message"
                        @input="submitData()"
                        placeholder="@lang('Nuestro equipo humano responderá tan pronto volvamos al horario laboral.')"
                    ></textarea>
                    <p class="mt-1 text-2xs opacity-60">
                        @lang('Se envía automáticamente cuando la conversación llega fuera del horario configurado.')
                    </p>
                </div>

                <div
                    class="rounded-lg border border-border/60 p-3"
                    x-data='humanAgentScheduleCard(@json($human_agent_schedule_day_labels), @json($default_human_agent_schedule))'
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium text-heading-foreground">
                                @lang('Horario de agentes humanos')
                            </p>
                            <p class="text-2xs text-heading-foreground/60">
                                @lang('Define la disponibilidad semanal para redirigir conversaciones a personas.')
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="rounded-full border border-border px-3 py-1.5 text-2xs font-semibold text-heading-foreground transition hover:border-primary hover:text-primary"
                                @click.prevent="resetSchedule()"
                            >
                                @lang('Usar horario estándar')
                            </button>
                            <label class="inline-flex items-center gap-2 text-2xs font-semibold text-heading-foreground">
                                <input
                                    type="checkbox"
                                    class="rounded border-input text-primary focus:ring-primary/20"
                                    x-model="chatbot.human_agent_schedule_enabled"
                                    @change="submitData()"
                                >
                                <span>@lang('Activar')</span>
                            </label>
                        </div>
                    </div>

                    <div
                        class="mt-4 space-y-4"
                        x-cloak
                        x-show="activeChatbot.human_agent_schedule_enabled"
                    >
                        <div>
                            <label class="mb-1.5 block text-2xs font-medium uppercase tracking-wide text-heading-foreground/60">
                                @lang('Zona horaria')
                            </label>
                            <select
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                x-model="chatbot.human_agent_timezone"
                                @change="submitData()"
                            >
                                @foreach ($human_agent_timezone_options as $value => $label)
                                    <option value="{{ $value }}">
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-2">
                            <template
                                x-for="(slot, index) in chatbot.human_agent_schedule"
                                :key="slot.day"
                            >
                                <div class="flex flex-wrap items-center gap-3 rounded-lg border border-border/60 bg-background/40 px-3 py-2">
                                    <label class="flex items-center gap-2 text-sm font-medium text-heading-foreground">
                                        <input
                                            type="checkbox"
                                            class="rounded border-input text-primary focus:ring-primary/20"
                                            x-model="chatbot.human_agent_schedule[index].enabled"
                                            @change="submitData()"
                                        >
                                        <span x-text="dayLabels[slot.day] ?? slot.day"></span>
                                    </label>
                                    <div class="ms-auto flex flex-wrap items-center gap-2 text-xs font-medium">
                                        <input
                                            type="time"
                                            class="rounded-lg border border-input bg-background px-2 py-1 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                            x-model="chatbot.human_agent_schedule[index].start"
                                            @change="submitData()"
                                        >
                                        <span>—</span>
                                        <input
                                            type="time"
                                            class="rounded-lg border border-input bg-background px-2 py-1 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                            x-model="chatbot.human_agent_schedule[index].end"
                                            @change="submitData()"
                                        >
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <p
                        class="mt-3 text-2xs text-heading-foreground/60"
                        x-show="!(chatbot && chatbot.human_agent_schedule_enabled)"
                    >
                        @lang('Si está desactivado, la IA seguirá disponible las 24 horas.')
                    </p>
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
