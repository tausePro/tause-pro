<div
    class="col-start-1 col-end-1 row-start-1 row-end-1 w-full px-4 py-6 overflow-y-auto max-h-full"
    x-show="contactInfo.activeTab === 'lead'"
    x-transition.opacity
    x-data="{
        leadData: {
            value: null,
            priority: 0,
            notes: '',
            nextActionAt: null,
            tags: []
        },
        saving: false,
        historyLoading: false,
        history: [],
        showHistory: false,
        priorityLabels: {
            0: '{{ __('Not Set') }}',
            1: '{{ __('Low') }}',
            2: '{{ __('Medium') }}',
            3: '{{ __('High') }}',
            4: '{{ __('Urgent') }}'
        },
        priorityColors: {
            0: 'bg-gray-100 text-gray-600',
            1: 'bg-blue-100 text-blue-700',
            2: 'bg-yellow-100 text-yellow-700',
            3: 'bg-orange-100 text-orange-700',
            4: 'bg-red-100 text-red-700'
        },
        eventColors: {
            'priority_changed': 'bg-orange-100 text-orange-600',
            'value_changed': 'bg-green-100 text-green-600',
            'note_added': 'bg-blue-100 text-blue-600',
            'tag_added': 'bg-purple-100 text-purple-600',
            'tag_removed': 'bg-gray-100 text-gray-600',
            'status_changed': 'bg-cyan-100 text-cyan-600',
            'follow_up_scheduled': 'bg-indigo-100 text-indigo-600',
            'quote_sent': 'bg-emerald-100 text-emerald-600',
            'conversation_started': 'bg-sky-100 text-sky-600',
            'conversation_closed': 'bg-slate-100 text-slate-600'
        },
        init() {
            this.$watch('activeChat', (chat) => {
                if (chat?.customer) {
                    this.leadData = {
                        value: chat.customer.lead_value || null,
                        priority: chat.customer.lead_priority || 0,
                        notes: chat.customer.negotiation_notes || '',
                        nextActionAt: chat.customer.next_action_at || null,
                        tags: chat.customer.crm_tags || []
                    };
                    // Reset history when chat changes
                    this.history = [];
                    this.showHistory = false;
                }
            });
        },
        async saveLead() {
            if (!activeChat?.customer?.id) return;
            
            this.saving = true;
            try {
                const response = await fetch('{{ route('dashboard.chatbot-agent.customer.update-lead') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content
                    },
                    body: JSON.stringify({
                        customer_id: activeChat.customer.id,
                        lead_value: this.leadData.value,
                        lead_priority: this.leadData.priority,
                        negotiation_notes: this.leadData.notes,
                        next_action_at: this.leadData.nextActionAt,
                        crm_tags: this.leadData.tags
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    toastr.success('{{ __('Lead updated successfully') }}');
                    // Update local customer data
                    if (activeChat.customer) {
                        activeChat.customer.lead_value = this.leadData.value;
                        activeChat.customer.lead_priority = this.leadData.priority;
                        activeChat.customer.negotiation_notes = this.leadData.notes;
                        activeChat.customer.next_action_at = this.leadData.nextActionAt;
                        activeChat.customer.crm_tags = this.leadData.tags;
                    }
                    // Refresh history if visible
                    if (this.showHistory) {
                        this.loadHistory();
                    }
                } else {
                    toastr.error(data.message || '{{ __('Error updating lead') }}');
                }
            } catch (error) {
                console.error('Error saving lead:', error);
                toastr.error('{{ __('Error updating lead') }}');
            } finally {
                this.saving = false;
            }
        },
        async loadHistory() {
            if (!activeChat?.customer?.id) return;
            
            this.historyLoading = true;
            try {
                const response = await fetch(`{{ route('dashboard.chatbot-agent.customer.lead-history') }}?customer_id=${activeChat.customer.id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.history = data.history;
                }
            } catch (error) {
                console.error('Error loading history:', error);
            } finally {
                this.historyLoading = false;
            }
        },
        toggleHistory() {
            this.showHistory = !this.showHistory;
            if (this.showHistory && this.history.length === 0) {
                this.loadHistory();
            }
        },
        formatCurrency(value) {
            if (!value) return '$0';
            return '$' + new Intl.NumberFormat('es-CO').format(value);
        }
    }"
>
    {{-- Header --}}
    <div class="mb-6 text-center">
        <div class="mx-auto mb-3 flex size-16 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-white">
            <x-tabler-target class="size-8" />
        </div>
        <h3 class="text-lg font-semibold text-heading-foreground">
            {{ __('Lead Management') }}
        </h3>
        <p class="text-sm text-foreground/60">
            {{ __('Track and manage this contact as a sales lead') }}
        </p>
    </div>

    {{-- Priority Badge --}}
    <div class="mb-4 flex items-center justify-center">
        <span
            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold"
            :class="priorityColors[leadData.priority]"
        >
            <template x-if="leadData.priority === 4">
                <x-tabler-alert-circle class="size-4" />
            </template>
            <span x-text="priorityLabels[leadData.priority]"></span>
        </span>
    </div>

    {{-- Lead Value --}}
    <div class="mb-4 rounded-xl border bg-background/50 p-4">
        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-foreground/60">
            {{ __('Lead Value (COP)') }}
        </label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-foreground/40">$</span>
            <input
                type="number"
                class="w-full rounded-lg border border-border/60 bg-background py-2.5 pe-3 ps-7 text-lg font-semibold text-heading-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                placeholder="0"
                x-model.number="leadData.value"
                @change="saveLead()"
            >
        </div>
        <p class="mt-1 text-xs text-foreground/50">
            {{ __('Estimated value of this lead') }}
        </p>
    </div>

    {{-- Priority Selector --}}
    <div class="mb-4 rounded-xl border bg-background/50 p-4">
        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-foreground/60">
            {{ __('Priority') }}
        </label>
        <div class="grid grid-cols-5 gap-1">
            <template x-for="(label, level) in priorityLabels" :key="level">
                <button
                    type="button"
                    class="rounded-lg border px-2 py-2 text-xs font-medium transition-all hover:border-primary"
                    :class="leadData.priority == level ? 'border-primary bg-primary/10 text-primary' : 'border-border/60 text-foreground/70'"
                    @click="leadData.priority = parseInt(level); saveLead()"
                >
                    <span x-text="level == 0 ? '—' : level"></span>
                </button>
            </template>
        </div>
    </div>

    {{-- Next Action Date --}}
    <div class="mb-4 rounded-xl border bg-background/50 p-4">
        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-foreground/60">
            {{ __('Next Action') }}
        </label>
        <input
            type="datetime-local"
            class="w-full rounded-lg border border-border/60 bg-background px-3 py-2.5 text-sm text-heading-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
            x-model="leadData.nextActionAt"
            @change="saveLead()"
        >
        <p class="mt-1 text-xs text-foreground/50">
            {{ __('Schedule a follow-up reminder') }}
        </p>
    </div>

    {{-- Notes --}}
    <div class="mb-4 rounded-xl border bg-background/50 p-4">
        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-foreground/60">
            {{ __('Negotiation Notes') }}
        </label>
        <textarea
            class="min-h-[100px] w-full resize-none rounded-lg border border-border/60 bg-background px-3 py-2.5 text-sm text-heading-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
            placeholder="{{ __('Add notes about this lead, negotiations, special requests...') }}"
            x-model="leadData.notes"
            @blur="saveLead()"
        ></textarea>
    </div>

    {{-- Tags --}}
    <div class="mb-4 rounded-xl border bg-background/50 p-4">
        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-foreground/60">
            {{ __('Tags') }}
        </label>
        <div class="flex flex-wrap gap-1.5">
            <template x-for="(tag, index) in leadData.tags" :key="index">
                <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                    <span x-text="tag"></span>
                    <button
                        type="button"
                        class="ml-0.5 hover:text-red-500"
                        @click="leadData.tags.splice(index, 1); saveLead()"
                    >
                        <x-tabler-x class="size-3" />
                    </button>
                </span>
            </template>
            <input
                type="text"
                class="min-w-[80px] flex-1 rounded-full border-0 bg-transparent px-2 py-1 text-xs focus:outline-none focus:ring-0"
                placeholder="{{ __('Add tag...') }}"
                @keydown.enter.prevent="
                    if ($event.target.value.trim()) {
                        leadData.tags.push($event.target.value.trim());
                        $event.target.value = '';
                        saveLead();
                    }
                "
            >
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mt-6 space-y-2">
        <p class="-mx-4 mb-3 flex items-center justify-center gap-10">
            <span class="inline-block h-px grow bg-current opacity-5"></span>
            {{ __('Quick Actions') }}
            <span class="inline-block h-px grow bg-current opacity-5"></span>
        </p>

        <button
            type="button"
            class="flex w-full items-center gap-3 rounded-lg border border-border/60 bg-background px-4 py-3 text-left text-sm font-medium text-heading-foreground transition-all hover:border-primary hover:bg-primary/5"
            @click="leadData.priority = 3; saveLead(); toastr.info('{{ __('Marked as high priority') }}')"
        >
            <span class="flex size-8 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                <x-tabler-flame class="size-4" />
            </span>
            {{ __('Mark as Hot Lead') }}
        </button>

        <button
            type="button"
            class="flex w-full items-center gap-3 rounded-lg border border-border/60 bg-background px-4 py-3 text-left text-sm font-medium text-heading-foreground transition-all hover:border-primary hover:bg-primary/5"
            @click="
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                tomorrow.setHours(10, 0, 0, 0);
                leadData.nextActionAt = tomorrow.toISOString().slice(0, 16);
                saveLead();
                toastr.info('{{ __('Follow-up scheduled for tomorrow at 10:00 AM') }}');
            "
        >
            <span class="flex size-8 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <x-tabler-calendar-event class="size-4" />
            </span>
            {{ __('Schedule Follow-up Tomorrow') }}
        </button>

        <button
            type="button"
            class="flex w-full items-center gap-3 rounded-lg border border-border/60 bg-background px-4 py-3 text-left text-sm font-medium text-heading-foreground transition-all hover:border-green-500 hover:bg-green-50"
            x-show="activeChat?.customer?.phone"
            @click="window.open('https://wa.me/' + activeChat.customer.phone, '_blank')"
        >
            <span class="flex size-8 items-center justify-center rounded-full bg-green-100 text-green-600">
                <x-tabler-brand-whatsapp class="size-4" />
            </span>
            {{ __('Open WhatsApp Chat') }}
        </button>
    </div>

    {{-- History Section --}}
    <div class="mt-6">
        <button
            type="button"
            class="flex w-full items-center justify-between rounded-xl border border-border/60 bg-background/50 px-4 py-3 text-left text-sm font-semibold text-heading-foreground transition-all hover:border-primary hover:bg-primary/5"
            @click="toggleHistory()"
        >
            <span class="flex items-center gap-2">
                <x-tabler-history class="size-5 text-primary" />
                {{ __('Activity History') }}
            </span>
            <span class="flex items-center gap-2">
                <span
                    class="text-xs text-foreground/50"
                    x-show="history.length > 0"
                    x-text="history.length + ' {{ __('events') }}'"
                ></span>
                <x-tabler-chevron-down
                    class="size-4 transition-transform"
                    ::class="showHistory ? 'rotate-180' : ''"
                />
            </span>
        </button>

        <div
            x-show="showHistory"
            x-collapse
            class="mt-3"
        >
            {{-- Loading state --}}
            <div
                x-show="historyLoading"
                class="flex items-center justify-center py-8"
            >
                <x-tabler-loader-2 class="size-6 animate-spin text-primary" />
            </div>

            {{-- Empty state --}}
            <div
                x-show="!historyLoading && history.length === 0"
                class="rounded-xl border border-dashed border-border/60 bg-background/30 px-4 py-8 text-center"
            >
                <x-tabler-history-off class="mx-auto mb-2 size-8 text-foreground/30" />
                <p class="text-sm text-foreground/50">{{ __('No activity recorded yet') }}</p>
            </div>

            {{-- History Timeline --}}
            <div
                x-show="!historyLoading && history.length > 0"
                class="relative space-y-0"
            >
                {{-- Timeline line --}}
                <div class="absolute left-4 top-0 h-full w-0.5 bg-border/40"></div>

                <template x-for="(item, index) in history" :key="item.id">
                    <div class="relative flex gap-3 pb-4">
                        {{-- Timeline dot --}}
                        <div
                            class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full"
                            :class="eventColors[item.event_type] || 'bg-gray-100 text-gray-600'"
                        >
                            <template x-if="item.event_type === 'priority_changed'">
                                <x-tabler-flag class="size-4" />
                            </template>
                            <template x-if="item.event_type === 'value_changed'">
                                <x-tabler-currency-dollar class="size-4" />
                            </template>
                            <template x-if="item.event_type === 'note_added'">
                                <x-tabler-note class="size-4" />
                            </template>
                            <template x-if="item.event_type === 'tag_added'">
                                <x-tabler-tag class="size-4" />
                            </template>
                            <template x-if="item.event_type === 'tag_removed'">
                                <x-tabler-tag-off class="size-4" />
                            </template>
                            <template x-if="item.event_type === 'follow_up_scheduled'">
                                <x-tabler-calendar-event class="size-4" />
                            </template>
                            <template x-if="item.event_type === 'quote_sent'">
                                <x-tabler-file-invoice class="size-4" />
                            </template>
                            <template x-if="!['priority_changed', 'value_changed', 'note_added', 'tag_added', 'tag_removed', 'follow_up_scheduled', 'quote_sent'].includes(item.event_type)">
                                <x-tabler-activity class="size-4" />
                            </template>
                        </div>

                        {{-- Content --}}
                        <div class="min-w-0 flex-1 rounded-lg border border-border/40 bg-background/50 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-medium text-heading-foreground" x-text="item.event_label"></p>
                                    <p
                                        class="mt-0.5 text-xs text-foreground/60"
                                        x-show="item.description"
                                        x-text="item.description"
                                    ></p>
                                </div>
                                <span class="shrink-0 text-xs text-foreground/40" x-text="item.created_at"></span>
                            </div>

                            {{-- Value changes --}}
                            <div
                                x-show="item.event_type === 'value_changed'"
                                class="mt-2 flex items-center gap-2 text-xs"
                            >
                                <span class="text-foreground/50" x-text="item.old_value ? '$' + Number(item.old_value).toLocaleString('es-CO') : '$0'"></span>
                                <x-tabler-arrow-right class="size-3 text-foreground/30" />
                                <span class="font-semibold text-green-600" x-text="item.new_value ? '$' + Number(item.new_value).toLocaleString('es-CO') : '$0'"></span>
                            </div>

                            {{-- Tag changes --}}
                            <div
                                x-show="item.event_type === 'tag_added' || item.event_type === 'tag_removed'"
                                class="mt-2"
                            >
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="item.event_type === 'tag_added' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700 line-through'"
                                    x-text="item.new_value || item.old_value"
                                ></span>
                            </div>

                            {{-- User info --}}
                            <div
                                x-show="item.user"
                                class="mt-2 flex items-center gap-1.5 text-xs text-foreground/50"
                            >
                                <x-tabler-user class="size-3" />
                                <span x-text="item.user?.name"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Refresh button --}}
            <button
                type="button"
                class="mt-2 flex w-full items-center justify-center gap-2 rounded-lg border border-border/40 bg-background/30 px-3 py-2 text-xs text-foreground/60 transition-all hover:border-primary hover:text-primary"
                @click="loadHistory()"
                :disabled="historyLoading"
            >
                <x-tabler-refresh class="size-3.5" ::class="historyLoading ? 'animate-spin' : ''" />
                {{ __('Refresh') }}
            </button>
        </div>
    </div>

    {{-- Saving indicator --}}
    <div
        class="fixed bottom-4 right-4 flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white shadow-lg"
        x-show="saving"
        x-transition
    >
        <x-tabler-loader-2 class="size-4 animate-spin" />
        {{ __('Saving...') }}
    </div>
</div>

