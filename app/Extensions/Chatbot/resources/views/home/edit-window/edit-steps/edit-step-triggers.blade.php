{{-- Editing Step 4 - Triggers --}}
<div
    class="col-start-1 col-end-1 row-start-1 row-end-1 transition-all"
    data-step="4"
    x-show="editingStep === 4"
    x-transition:enter-start="opacity-0 -translate-x-3"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-3"
>
    <h2 class="mb-3.5">
        @lang('Proactive Triggers')
    </h2>
    <p class="text-xs/5 opacity-60 lg:max-w-[360px]">
        @lang('Configure proactive messages that engage visitors automatically based on their behavior.')
    </p>

    <div class="flex flex-col gap-5 pt-9" x-data="triggersManager()">
        {{-- Triggers List --}}
        <template x-for="(trigger, index) in triggers" :key="trigger.type">
            <div class="rounded-xl border border-border p-4 transition-all hover:border-primary/30">
                {{-- Trigger Header --}}
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-heading-foreground" x-text="trigger.name"></h3>
                            <span 
                                class="rounded-full px-2 py-0.5 text-2xs font-medium"
                                :class="trigger.category === 'universal' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'"
                                x-text="trigger.category === 'universal' ? 'Universal' : 'Industry'"
                            ></span>
                        </div>
                        <p class="mt-1 text-xs text-heading-foreground/60" x-text="trigger.description"></p>
                    </div>
                    
                    {{-- Toggle Switch --}}
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input 
                            type="checkbox" 
                            class="peer sr-only" 
                            :checked="trigger.enabled"
                            @change="toggleTrigger(trigger.type)"
                        >
                        <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/50"></div>
                    </label>
                </div>

                {{-- Trigger Settings (shown when enabled) --}}
                <div x-show="trigger.enabled" x-collapse>
                    <div class="space-y-3 border-t border-border pt-3">
                        {{-- Message Editor --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                                @lang('Message')
                            </label>
                            <textarea
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                rows="3"
                                :value="trigger.message"
                                @input="updateTriggerMessage(trigger.type, $event.target.value)"
                                placeholder="Enter the message to display..."
                            ></textarea>
                            <p class="mt-1 text-2xs text-heading-foreground/50">
                                @lang('Use emojis and be friendly. Keep it under 200 characters.')
                            </p>
                        </div>

                        {{-- Timing Settings --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                                    @lang('Cooldown (minutes)')
                                </label>
                                <input
                                    type="number"
                                    class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    :value="trigger.cooldown"
                                    @input="updateTriggerCooldown(trigger.type, $event.target.value)"
                                    min="1"
                                    max="1440"
                                >
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                                    @lang('Priority')
                                </label>
                                <select
                                    class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    :value="trigger.priority"
                                    @change="updateTriggerPriority(trigger.type, $event.target.value)"
                                >
                                    <option value="1">@lang('High')</option>
                                    <option value="2">@lang('Medium')</option>
                                    <option value="3">@lang('Low')</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- Save Button --}}
        <div class="flex justify-end gap-3 border-t border-border pt-5">
            <x-button
                class="px-6"
                variant="ghost-shadow"
                @click.prevent="resetTriggers()"
            >
                @lang('Reset to Defaults')
            </x-button>
            <x-button
                class="px-6"
                @click.prevent="saveTriggers()"
                ::disabled="submittingData"
            >
                <span x-show="!submittingData">@lang('Save Triggers')</span>
                <span x-show="submittingData">@lang('Saving...')</span>
            </x-button>
        </div>
    </div>
</div>

@push('script')
<script>
function triggersManager() {
    return {
        triggers: [],
        defaultTriggers: [
            {
                type: 'welcome_30s',
                name: 'Welcome After 30 Seconds',
                description: 'Greet visitors who stay on the site for 30 seconds',
                category: 'universal',
                message: '¡Hola! 👋 ¿Te puedo ayudar a encontrar algo específico?',
                enabled: true,
                cooldown: 10,
                priority: 1
            },
            {
                type: 'exit_intent',
                name: 'Exit Intent Offer',
                description: 'Show offer when user attempts to leave',
                category: 'universal',
                message: '¡Espera! ¿Te gustaría saber sobre nuestras ofertas especiales?',
                enabled: true,
                cooldown: 30,
                priority: 2
            },
            {
                type: 'page_dwell_2min',
                name: 'Long Page Dwell (2min)',
                description: 'Offer help after 2 minutes on the same page',
                category: 'universal',
                message: '¿Necesitas ayuda con algo específico?',
                enabled: false,
                cooldown: 15,
                priority: 3
            },
            {
                type: 'first_visitor_discount',
                name: 'First Visitor Welcome',
                description: 'Welcome message for first-time visitors',
                category: 'universal',
                message: '¡Bienvenido! Como nuevo visitante, quiero ayudarte a encontrar lo que necesitas.',
                enabled: false,
                cooldown: 999999,
                priority: 1
            },
            {
                type: 'cart_abandonment',
                name: 'Cart Abandonment',
                description: 'Recover abandoned carts',
                category: 'universal',
                message: '¿Necesitas ayuda para completar tu compra?',
                enabled: false,
                cooldown: 20,
                priority: 2
            }
        ],

        init() {
            this.loadTriggers();
        },

        async loadTriggers() {
            // Try to read the chatbot id reliably from the hidden input as a fallback
            let chatbotId = null;
            try {
                chatbotId = (window.Alpine && Alpine.store('chatbot') && Alpine.store('chatbot').activeChatbot?.id) || null;
            } catch (_) {}

            if (!chatbotId) {
                const idInput = document.querySelector('input[name="id"]');
                chatbotId = idInput ? idInput.value : null;
            }

            if (!chatbotId) {
                this.triggers = [...this.defaultTriggers];
                return;
            }

            try {
                const response = await fetch(`/dashboard/chatbot/${chatbotId}/triggers`, { credentials: 'same-origin' });
                if (response.ok) {
                    const data = await response.json();
                    this.triggers = (data && Array.isArray(data.triggers) && data.triggers.length)
                        ? data.triggers
                        : [...this.defaultTriggers];
                } else {
                    this.triggers = [...this.defaultTriggers];
                }
            } catch (error) {
                console.error('Failed to load triggers:', error);
                this.triggers = [...this.defaultTriggers];
            }
        },

        toggleTrigger(type) {
            const trigger = this.triggers.find(t => t.type === type);
            if (trigger) {
                trigger.enabled = !trigger.enabled;
            }
        },

        updateTriggerMessage(type, message) {
            const trigger = this.triggers.find(t => t.type === type);
            if (trigger) {
                trigger.message = message;
            }
        },

        updateTriggerCooldown(type, cooldown) {
            const trigger = this.triggers.find(t => t.type === type);
            if (trigger) {
                trigger.cooldown = parseInt(cooldown) || 5;
            }
        },

        updateTriggerPriority(type, priority) {
            const trigger = this.triggers.find(t => t.type === type);
            if (trigger) {
                trigger.priority = parseInt(priority) || 3;
            }
        },

        async saveTriggers() {
            const chatbotId = Alpine.store('chatbot').activeChatbot?.id;
            if (!chatbotId) {
                toastr.error('Please save the chatbot first');
                return;
            }

            Alpine.store('chatbot').submittingData = true;

            try {
                const response = await fetch(`/dashboard/chatbot/${chatbotId}/triggers`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        triggers: this.triggers.map(t => ({
                            type: t.type,
                            enabled: t.enabled,
                            message: t.message,
                            cooldown: t.cooldown,
                            priority: t.priority
                        }))
                    })
                });

                if (response.ok) {
                    toastr.success('Triggers saved successfully!');
                } else {
                    toastr.error('Failed to save triggers');
                }
            } catch (error) {
                console.error('Failed to save triggers:', error);
                toastr.error('Failed to save triggers');
            } finally {
                Alpine.store('chatbot').submittingData = false;
            }
        },

        resetTriggers() {
            if (confirm('Are you sure you want to reset all triggers to defaults?')) {
                this.triggers = [...this.defaultTriggers];
                toastr.success('Triggers reset to defaults');
            }
        }
    };
}
</script>
@endpush
