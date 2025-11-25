@extends('panel.layout.app', ['disable_tblr' => true])

@section('title', __('Leads Dashboard'))

@section('titlebar_title')
    <div class="flex items-center gap-3">
        <span class="flex size-10 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-white">
            <x-tabler-target class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold text-heading-foreground">{{ __('Leads Dashboard') }}</h1>
            <p class="text-sm text-foreground/60">{{ __('Manage and track your sales leads') }}</p>
        </div>
    </div>
@endsection

@section('titlebar_actions')
    <div class="flex items-center gap-2">
        <a
            href="{{ route('dashboard.chatbot-agent.leads.export') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-border/60 bg-background px-4 py-2 text-sm font-medium text-foreground/70 transition-all hover:border-primary hover:text-primary"
        >
            <x-tabler-download class="size-4" />
            {{ __('Export CSV') }}
        </a>
        <a
            href="{{ route('dashboard.chatbot-agent.index') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-all hover:bg-primary/90"
        >
            <x-tabler-messages class="size-4" />
            {{ __('Go to Inbox') }}
        </a>
    </div>
@endsection

@section('content')
    <div
        class="py-6"
        x-data="leadsDashboard()"
        x-init="loadLeads()"
    >
        {{-- Metrics Cards --}}
        <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6">
            {{-- Total Leads --}}
            <div class="rounded-xl border bg-background p-4">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                        <x-tabler-users class="size-5" />
                    </span>
                    <div>
                        <p class="text-2xl font-bold text-heading-foreground">{{ number_format($metrics['total_leads']) }}</p>
                        <p class="text-xs text-foreground/60">{{ __('Total Leads') }}</p>
                    </div>
                </div>
            </div>

            {{-- Total Value --}}
            <div class="rounded-xl border bg-background p-4">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-green-100 text-green-600">
                        <x-tabler-currency-dollar class="size-5" />
                    </span>
                    <div>
                        <p class="text-2xl font-bold text-heading-foreground">${{ number_format($metrics['total_value'], 0, ',', '.') }}</p>
                        <p class="text-xs text-foreground/60">{{ __('Pipeline Value') }}</p>
                    </div>
                </div>
            </div>

            {{-- High Priority --}}
            <div class="rounded-xl border bg-background p-4">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-orange-100 text-orange-600">
                        <x-tabler-flame class="size-5" />
                    </span>
                    <div>
                        <p class="text-2xl font-bold text-heading-foreground">{{ $metrics['high_priority'] }}</p>
                        <p class="text-xs text-foreground/60">{{ __('Hot Leads') }}</p>
                    </div>
                </div>
            </div>

            {{-- Overdue --}}
            <div class="rounded-xl border bg-background p-4 {{ $metrics['overdue_actions'] > 0 ? 'border-red-200 bg-red-50' : '' }}">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-lg {{ $metrics['overdue_actions'] > 0 ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600' }}">
                        <x-tabler-alert-circle class="size-5" />
                    </span>
                    <div>
                        <p class="text-2xl font-bold {{ $metrics['overdue_actions'] > 0 ? 'text-red-600' : 'text-heading-foreground' }}">{{ $metrics['overdue_actions'] }}</p>
                        <p class="text-xs {{ $metrics['overdue_actions'] > 0 ? 'text-red-600/70' : 'text-foreground/60' }}">{{ __('Overdue') }}</p>
                    </div>
                </div>
            </div>

            {{-- Today --}}
            <div class="rounded-xl border bg-background p-4">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                        <x-tabler-calendar-event class="size-5" />
                    </span>
                    <div>
                        <p class="text-2xl font-bold text-heading-foreground">{{ $metrics['today_actions'] }}</p>
                        <p class="text-xs text-foreground/60">{{ __('Today') }}</p>
                    </div>
                </div>
            </div>

            {{-- This Week --}}
            <div class="rounded-xl border bg-background p-4">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                        <x-tabler-calendar-week class="size-5" />
                    </span>
                    <div>
                        <p class="text-2xl font-bold text-heading-foreground">{{ $metrics['week_actions'] }}</p>
                        <p class="text-xs text-foreground/60">{{ __('This Week') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="mb-6 rounded-xl border bg-background p-4">
            <div class="flex flex-wrap items-center gap-3">
                {{-- Search --}}
                <div class="relative flex-1 min-w-[200px]">
                    <x-tabler-search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-foreground/40" />
                    <input
                        type="text"
                        class="w-full rounded-lg border border-border/60 bg-background py-2 pe-3 ps-10 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        placeholder="{{ __('Search by name, email, phone...') }}"
                        x-model="filters.search"
                        @input.debounce.300ms="loadLeads()"
                    >
                </div>

                {{-- Chatbot Filter --}}
                <select
                    class="rounded-lg border border-border/60 bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none"
                    x-model="filters.chatbot_id"
                    @change="loadLeads()"
                >
                    <option value="">{{ __('All Chatbots') }}</option>
                    @foreach($chatbots as $chatbot)
                        <option value="{{ $chatbot->id }}">{{ $chatbot->title }}</option>
                    @endforeach
                </select>

                {{-- Priority Filter --}}
                <select
                    class="rounded-lg border border-border/60 bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none"
                    x-model="filters.priority"
                    @change="loadLeads()"
                >
                    <option value="">{{ __('All Priorities') }}</option>
                    <option value="4">🔴 {{ __('Urgent') }}</option>
                    <option value="3">🟠 {{ __('High') }}</option>
                    <option value="2">🟡 {{ __('Medium') }}</option>
                    <option value="1">🔵 {{ __('Low') }}</option>
                    <option value="0">⚪ {{ __('Not Set') }}</option>
                </select>

                {{-- Channel Filter --}}
                <select
                    class="rounded-lg border border-border/60 bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none"
                    x-model="filters.channel"
                    @change="loadLeads()"
                >
                    <option value="">{{ __('All Channels') }}</option>
                    <option value="whatsapp">📱 WhatsApp</option>
                    <option value="frame">💬 Livechat</option>
                    <option value="telegram">✈️ Telegram</option>
                    <option value="messenger">💙 Messenger</option>
                </select>

                {{-- Next Action Filter --}}
                <select
                    class="rounded-lg border border-border/60 bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none"
                    x-model="filters.next_action"
                    @change="loadLeads()"
                >
                    <option value="">{{ __('All Follow-ups') }}</option>
                    <option value="overdue">⚠️ {{ __('Overdue') }}</option>
                    <option value="today">📅 {{ __('Today') }}</option>
                    <option value="week">📆 {{ __('This Week') }}</option>
                </select>

                {{-- Clear Filters --}}
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-border/60 px-3 py-2 text-sm text-foreground/60 transition-all hover:border-red-300 hover:text-red-500"
                    @click="clearFilters()"
                    x-show="hasActiveFilters"
                >
                    <x-tabler-x class="size-4" />
                    {{ __('Clear') }}
                </button>
            </div>
        </div>

        {{-- Leads Table --}}
        <div class="rounded-xl border bg-background">
            {{-- Loading State --}}
            <div
                x-show="loading"
                class="flex items-center justify-center py-12"
            >
                <x-tabler-loader-2 class="size-8 animate-spin text-primary" />
            </div>

            {{-- Empty State --}}
            <div
                x-show="!loading && leads.length === 0"
                class="py-12 text-center"
            >
                <x-tabler-users-minus class="mx-auto mb-3 size-12 text-foreground/20" />
                <p class="text-lg font-medium text-foreground/60">{{ __('No leads found') }}</p>
                <p class="text-sm text-foreground/40">{{ __('Try adjusting your filters') }}</p>
            </div>

            {{-- Table --}}
            <div
                x-show="!loading && leads.length > 0"
                class="overflow-x-auto"
            >
                <table class="w-full">
                    <thead class="border-b bg-background/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-foreground/60">
                                {{ __('Contact') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-foreground/60">
                                {{ __('Channel') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-foreground/60 cursor-pointer hover:text-primary" @click="toggleSort('lead_value')">
                                {{ __('Value') }}
                                <x-tabler-arrows-sort class="inline size-3" />
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-foreground/60 cursor-pointer hover:text-primary" @click="toggleSort('lead_priority')">
                                {{ __('Priority') }}
                                <x-tabler-arrows-sort class="inline size-3" />
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-foreground/60 cursor-pointer hover:text-primary" @click="toggleSort('next_action_at')">
                                {{ __('Next Action') }}
                                <x-tabler-arrows-sort class="inline size-3" />
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-foreground/60">
                                {{ __('Tags') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-foreground/60">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/40">
                        <template x-for="lead in leads" :key="lead.id">
                            <tr class="transition-colors hover:bg-background/50">
                                {{-- Contact --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex size-10 items-center justify-center rounded-full bg-gradient-to-br from-primary/20 to-primary/10 text-sm font-semibold text-primary">
                                            <span x-text="lead.name ? lead.name.charAt(0).toUpperCase() : 'A'"></span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-heading-foreground" x-text="lead.name || 'Anonymous'"></p>
                                            <p class="text-xs text-foreground/50" x-text="lead.email || lead.phone || '—'"></p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Channel --}}
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium"
                                        :class="{
                                            'bg-green-100 text-green-700': lead.channel === 'whatsapp',
                                            'bg-blue-100 text-blue-700': lead.channel === 'frame',
                                            'bg-sky-100 text-sky-700': lead.channel === 'telegram',
                                            'bg-indigo-100 text-indigo-700': lead.channel === 'messenger'
                                        }"
                                    >
                                        <template x-if="lead.channel === 'whatsapp'"><x-tabler-brand-whatsapp class="size-3" /></template>
                                        <template x-if="lead.channel === 'frame'"><x-tabler-message class="size-3" /></template>
                                        <template x-if="lead.channel === 'telegram'"><x-tabler-brand-telegram class="size-3" /></template>
                                        <template x-if="lead.channel === 'messenger'"><x-tabler-brand-messenger class="size-3" /></template>
                                        <span x-text="lead.channel === 'frame' ? 'Livechat' : lead.channel"></span>
                                    </span>
                                </td>

                                {{-- Value --}}
                                <td class="px-4 py-3">
                                    <span
                                        class="font-semibold"
                                        :class="lead.lead_value > 0 ? 'text-green-600' : 'text-foreground/40'"
                                        x-text="lead.lead_value ? '$' + Number(lead.lead_value).toLocaleString('es-CO') : '—'"
                                    ></span>
                                </td>

                                {{-- Priority --}}
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium"
                                        :class="{
                                            'bg-red-100 text-red-700': lead.lead_priority === 4,
                                            'bg-orange-100 text-orange-700': lead.lead_priority === 3,
                                            'bg-yellow-100 text-yellow-700': lead.lead_priority === 2,
                                            'bg-blue-100 text-blue-700': lead.lead_priority === 1,
                                            'bg-gray-100 text-gray-600': !lead.lead_priority || lead.lead_priority === 0
                                        }"
                                    >
                                        <span x-text="priorityLabels[lead.lead_priority] || '—'"></span>
                                    </span>
                                </td>

                                {{-- Next Action --}}
                                <td class="px-4 py-3">
                                    <template x-if="lead.next_action_at">
                                        <div :class="lead.is_overdue ? 'text-red-600' : 'text-foreground/70'">
                                            <p class="text-sm font-medium" x-text="lead.next_action_human"></p>
                                            <p class="text-xs opacity-60" x-text="lead.next_action_at"></p>
                                        </div>
                                    </template>
                                    <template x-if="!lead.next_action_at">
                                        <span class="text-foreground/40">—</span>
                                    </template>
                                </td>

                                {{-- Tags --}}
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="tag in (lead.tags || []).slice(0, 3)" :key="tag">
                                            <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary" x-text="tag"></span>
                                        </template>
                                        <template x-if="lead.tags && lead.tags.length > 3">
                                            <span class="text-xs text-foreground/40" x-text="'+' + (lead.tags.length - 3)"></span>
                                        </template>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a
                                            :href="'{{ route('dashboard.chatbot-agent.index') }}?customer=' + lead.id"
                                            class="inline-flex size-8 items-center justify-center rounded-lg text-foreground/60 transition-all hover:bg-primary/10 hover:text-primary"
                                            title="{{ __('Open Conversation') }}"
                                        >
                                            <x-tabler-message class="size-4" />
                                        </a>
                                        <button
                                            type="button"
                                            class="inline-flex size-8 items-center justify-center rounded-lg text-foreground/60 transition-all hover:bg-green-100 hover:text-green-600"
                                            title="{{ __('Open WhatsApp') }}"
                                            x-show="lead.phone"
                                            @click="window.open('https://wa.me/' + lead.phone, '_blank')"
                                        >
                                            <x-tabler-brand-whatsapp class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div
                x-show="!loading && pagination.last_page > 1"
                class="flex items-center justify-between border-t px-4 py-3"
            >
                <p class="text-sm text-foreground/60">
                    {{ __('Showing') }} <span x-text="pagination.from"></span> - <span x-text="pagination.to"></span> {{ __('of') }} <span x-text="pagination.total"></span>
                </p>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-lg border border-border/60 text-foreground/60 transition-all hover:border-primary hover:text-primary disabled:opacity-50"
                        :disabled="pagination.current_page === 1"
                        @click="goToPage(pagination.current_page - 1)"
                    >
                        <x-tabler-chevron-left class="size-4" />
                    </button>
                    <span class="px-3 text-sm text-foreground/60" x-text="pagination.current_page + ' / ' + pagination.last_page"></span>
                    <button
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-lg border border-border/60 text-foreground/60 transition-all hover:border-primary hover:text-primary disabled:opacity-50"
                        :disabled="pagination.current_page === pagination.last_page"
                        @click="goToPage(pagination.current_page + 1)"
                    >
                        <x-tabler-chevron-right class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    function leadsDashboard() {
        return {
            loading: false,
            leads: [],
            pagination: {},
            filters: {
                search: '',
                chatbot_id: '',
                priority: '',
                channel: '',
                next_action: '',
                sort: 'updated_at',
                dir: 'desc',
                page: 1
            },
            priorityLabels: {
                0: '{{ __("Not Set") }}',
                1: '{{ __("Low") }}',
                2: '{{ __("Medium") }}',
                3: '{{ __("High") }}',
                4: '{{ __("Urgent") }}'
            },

            get hasActiveFilters() {
                return this.filters.search || this.filters.chatbot_id || this.filters.priority || this.filters.channel || this.filters.next_action;
            },

            async loadLeads() {
                this.loading = true;
                
                try {
                    const params = new URLSearchParams();
                    Object.entries(this.filters).forEach(([key, value]) => {
                        if (value) params.append(key, value);
                    });

                    const response = await fetch(`{{ route('dashboard.chatbot-agent.leads.data') }}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.leads = data.leads.data;
                        this.pagination = {
                            current_page: data.leads.current_page,
                            last_page: data.leads.last_page,
                            from: data.leads.from,
                            to: data.leads.to,
                            total: data.leads.total
                        };
                    }
                } catch (error) {
                    console.error('Error loading leads:', error);
                } finally {
                    this.loading = false;
                }
            },

            toggleSort(field) {
                if (this.filters.sort === field) {
                    this.filters.dir = this.filters.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.filters.sort = field;
                    this.filters.dir = 'desc';
                }
                this.loadLeads();
            },

            clearFilters() {
                this.filters = {
                    search: '',
                    chatbot_id: '',
                    priority: '',
                    channel: '',
                    next_action: '',
                    sort: 'updated_at',
                    dir: 'desc',
                    page: 1
                };
                this.loadLeads();
            },

            goToPage(page) {
                this.filters.page = page;
                this.loadLeads();
            }
        };
    }
</script>
@endpush

