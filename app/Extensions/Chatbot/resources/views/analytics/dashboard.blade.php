@extends('panel.layout.app', ['disable_tblr' => true])

@section('title', __('Chatbot Analytics'))

@section('content')
    <div class="container-fluid px-4 py-6">
        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-heading-foreground">
                    📊 {{ __('Analytics Dashboard') }}
                </h1>
                <p class="mt-1 text-sm text-heading-foreground/60">
                    {{ __('Monitor your chatbot performance and engagement metrics') }}
                </p>
            </div>
            
            <div class="flex gap-3">
                {{-- Chatbot Selector --}}
                <select 
                    id="chatbot-selector"
                    class="rounded-lg border border-input-border bg-background px-4 py-2 text-sm"
                    onchange="window.location.href = '{{ route('dashboard.chatbot.analytics.index') }}?chatbot_id=' + this.value + '&days={{ $days }}'"
                >
                    @foreach($chatbots as $chatbot)
                        <option 
                            value="{{ $chatbot->id }}"
                            {{ $selectedChatbot && $selectedChatbot->id == $chatbot->id ? 'selected' : '' }}
                        >
                            {{ $chatbot->title }}
                        </option>
                    @endforeach
                </select>
                
                {{-- Time Range --}}
                <select 
                    id="time-range"
                    class="rounded-lg border border-input-border bg-background px-4 py-2 text-sm"
                    onchange="window.location.href = '{{ route('dashboard.chatbot.analytics.index') }}?chatbot_id={{ $selectedChatbot?->id }}&days=' + this.value"
                >
                    <option value="7" {{ $days == 7 ? 'selected' : '' }}>{{ __('Last 7 days') }}</option>
                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>{{ __('Last 30 days') }}</option>
                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>{{ __('Last 90 days') }}</option>
                </select>
            </div>
        </div>

        @if(!$selectedChatbot)
            <div class="rounded-lg bg-yellow-50 p-6 text-center dark:bg-yellow-900/20">
                <p class="text-yellow-800 dark:text-yellow-200">
                    {{ __('Please select a chatbot to view analytics') }}
                </p>
            </div>
        @else
            {{-- Overview Cards --}}
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                {{-- Total Conversations --}}
                <div class="rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">{{ __('Total Conversations') }}</p>
                            <h3 class="mt-2 text-3xl font-bold">
                                {{ number_format($analytics['overview']['total_conversations']) }}
                            </h3>
                        </div>
                        <div class="text-4xl opacity-20">💬</div>
                    </div>
                </div>
                
                {{-- Total Messages --}}
                <div class="rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">{{ __('Total Messages') }}</p>
                            <h3 class="mt-2 text-3xl font-bold">
                                {{ number_format($analytics['overview']['total_messages']) }}
                            </h3>
                        </div>
                        <div class="text-4xl opacity-20">📨</div>
                    </div>
                </div>
                
                {{-- Conversion Rate --}}
                <div class="rounded-xl bg-gradient-to-br from-green-500 to-green-600 p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">{{ __('Conversion Rate') }}</p>
                            <h3 class="mt-2 text-3xl font-bold">
                                {{ number_format($analytics['overview']['conversion_rate'], 1) }}%
                            </h3>
                        </div>
                        <div class="text-4xl opacity-20">📈</div>
                    </div>
                </div>
                
                {{-- Unique Users --}}
                <div class="rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">{{ __('Unique Users') }}</p>
                            <h3 class="mt-2 text-3xl font-bold">
                                {{ number_format($analytics['overview']['unique_users']) }}
                            </h3>
                        </div>
                        <div class="text-4xl opacity-20">👥</div>
                    </div>
                </div>
            </div>

            {{-- Charts Row --}}
            <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Trend Chart --}}
                <div class="rounded-xl bg-background p-6 shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold">{{ __('Activity Trend') }}</h3>
                    <canvas id="trendChart" style="max-height: 300px;"></canvas>
                </div>
                
                {{-- Channel Distribution --}}
                <div class="rounded-xl bg-background p-6 shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold">{{ __('Channels Distribution') }}</h3>
                    <canvas id="channelChart" style="max-height: 300px;"></canvas>
                </div>
            </div>

            {{-- Metrics Grid --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Conversation Metrics --}}
                <div class="rounded-xl bg-background p-6 shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold">{{ __('Conversation Metrics') }}</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-heading-foreground/70">{{ __('Avg Duration') }}</span>
                            <span class="font-semibold">
                                {{ number_format($analytics['conversations']['avg_duration_minutes'], 1) }} min
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-heading-foreground/70">{{ __('Completed') }}</span>
                            <span class="font-semibold">
                                {{ number_format($analytics['conversations']['completed']) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-heading-foreground/70">{{ __('Today') }}</span>
                            <span class="font-semibold">
                                {{ number_format($analytics['conversations']['new_conversations_today']) }}
                            </span>
                        </div>
                    </div>
                </div>
                
                {{-- Engagement Metrics --}}
                <div class="rounded-xl bg-background p-6 shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold">{{ __('Engagement') }}</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-heading-foreground/70">{{ __('Bounce Rate') }}</span>
                            <span class="font-semibold">
                                {{ number_format($analytics['engagement']['bounce_rate'], 1) }}%
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-heading-foreground/70">{{ __('Return Users') }}</span>
                            <span class="font-semibold">
                                {{ number_format($analytics['engagement']['return_users']) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-heading-foreground/70">{{ __('Engagement Score') }}</span>
                            <span class="font-semibold">
                                {{ number_format($analytics['engagement']['engagement_score'], 0) }}/100
                            </span>
                        </div>
                    </div>
                </div>
                
                {{-- Performance Metrics --}}
                <div class="rounded-xl bg-background p-6 shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold">{{ __('Performance') }}</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-heading-foreground/70">{{ __('Resolved by Bot') }}</span>
                            <span class="font-semibold">
                                {{ number_format($analytics['performance']['resolved_by_bot']) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-heading-foreground/70">{{ __('Human Transfers') }}</span>
                            <span class="font-semibold">
                                {{ number_format($analytics['performance']['human_agent_transfers']) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-heading-foreground/70">{{ __('Peak Hours') }}</span>
                            <span class="text-xs font-semibold">
                                @foreach($analytics['performance']['peak_hours'] as $hour)
                                    {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00{{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    @if($selectedChatbot && $analytics)
    // Trend Chart
    const trendCtx = document.getElementById('trendChart');
    const trendData = @json($analytics['trends']);
    
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(d => d.date),
            datasets: [
                {
                    label: 'Conversations',
                    data: trendData.map(d => d.conversations),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Messages',
                    data: trendData.map(d => d.messages),
                    borderColor: 'rgb(168, 85, 247)',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Channel Chart
    const channelCtx = document.getElementById('channelChart');
    const channelData = @json($analytics['channels']);
    
    new Chart(channelCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(channelData).map(k => k.charAt(0).toUpperCase() + k.slice(1)),
            datasets: [{
                data: Object.values(channelData).map(v => v.conversations),
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(251, 146, 60, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    @endif
</script>
@endpush




