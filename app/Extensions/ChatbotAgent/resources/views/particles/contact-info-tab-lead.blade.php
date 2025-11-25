<div
    class="col-start-1 col-end-1 row-start-1 row-end-1 w-full px-4 py-6"
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

