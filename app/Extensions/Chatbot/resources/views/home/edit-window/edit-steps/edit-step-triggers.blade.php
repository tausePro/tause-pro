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
                                :class="{
                                    'bg-blue-100 text-blue-700': trigger.category === 'universal',
                                    'bg-purple-100 text-purple-700': trigger.category === 'industry',
                                    'bg-green-100 text-green-700': trigger.category === 'custom'
                                }"
                                x-text="trigger.category === 'universal' ? 'Universal' : (trigger.category === 'custom' ? 'Custom' : 'Industry')"
                            ></span>
                        </div>
                        <p class="mt-1 text-xs text-heading-foreground/60" x-text="trigger.description"></p>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        {{-- Delete Button (only for custom triggers) --}}
                        <button
                            x-show="trigger.category === 'custom'"
                            @click.prevent="deleteTrigger(trigger.type)"
                            class="rounded-lg p-2 text-red-600 transition-colors hover:bg-red-50"
                            title="Delete trigger"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                        
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

        {{-- Add Custom Trigger Button --}}
        <div class="border-t border-border pt-5">
            <x-button
                class="w-full"
                variant="ghost-shadow"
                @click.prevent="showAddTriggerModal = true"
            >
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                @lang('Add Custom Trigger')
            </x-button>
        </div>

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

        {{-- Add Custom Trigger Modal --}}
        <div
            x-show="showAddTriggerModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="showAddTriggerModal = false"
        >
            <div class="w-full max-w-lg rounded-xl bg-background p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-heading-foreground">
                    @lang('Add Custom Trigger')
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                            @lang('Trigger Name')
                        </label>
                        <input
                            type="text"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            x-model="newTrigger.name"
                            placeholder="e.g., Special Offer Popup"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                            @lang('Description')
                        </label>
                        <input
                            type="text"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            x-model="newTrigger.description"
                            placeholder="Brief description of when this trigger should appear"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                            @lang('Message')
                        </label>
                        <textarea
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            rows="3"
                            x-model="newTrigger.message"
                            placeholder="Enter the message to display..."
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                                @lang('Trigger Type')
                            </label>
                            <select
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                x-model="newTrigger.triggerType"
                            >
                                <option value="time_based">@lang('Time Based')</option>
                                <option value="scroll_based">@lang('Scroll Based')</option>
                                <option value="click_based">@lang('Click Based')</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-heading-foreground">
                                @lang('Delay (seconds)')
                            </label>
                            <input
                                type="number"
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                x-model="newTrigger.delay"
                                min="0"
                                max="300"
                            >
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-button
                        variant="ghost-shadow"
                        @click.prevent="showAddTriggerModal = false"
                    >
                        @lang('Cancel')
                    </x-button>
                    <x-button
                        @click.prevent="addCustomTrigger()"
                    >
                        @lang('Add Trigger')
                    </x-button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
function triggersManager() {
    return {
        triggers: [],
        showAddTriggerModal: false,
        newTrigger: {
            name: '',
            description: '',
            message: '',
            triggerType: 'time_based',
            delay: 30
        },
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
        },

        addCustomTrigger() {
            // Validate
            if (!this.newTrigger.name || !this.newTrigger.message) {
                toastr.error('Please fill in all required fields');
                return;
            }

            // Generate unique type ID
            const typeId = 'custom_' + this.newTrigger.name.toLowerCase().replace(/[^a-z0-9]/g, '_') + '_' + Date.now();

            // Create new trigger
            const customTrigger = {
                type: typeId,
                name: this.newTrigger.name,
                description: this.newTrigger.description || 'Custom trigger',
                category: 'custom',
                message: this.newTrigger.message,
                enabled: true,
                cooldown: 10,
                priority: 2,
                triggerType: this.newTrigger.triggerType,
                delay: parseInt(this.newTrigger.delay) || 30
            };

            // Add to triggers list
            this.triggers.push(customTrigger);

            // Reset form
            this.newTrigger = {
                name: '',
                description: '',
                message: '',
                triggerType: 'time_based',
                delay: 30
            };

            // Close modal
            this.showAddTriggerModal = false;

            toastr.success('Custom trigger added successfully!');
        },

        deleteTrigger(type) {
            if (confirm('Are you sure you want to delete this trigger?')) {
                this.triggers = this.triggers.filter(t => t.type !== type);
                toastr.success('Trigger deleted successfully');
            }
        }
    };
}
</script>
@endpush
