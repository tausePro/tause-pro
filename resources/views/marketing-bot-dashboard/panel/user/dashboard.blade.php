@php
    $plan = Auth::user()->activePlan();
    $plan_type = 'regular';
    // $team = Auth::user()->getAttribute('team');
    $teamManager = Auth::user()->getAttribute('teamManager');

    if ($plan != null) {
        $plan_type = strtolower($plan->plan_type);
    }

    $titlebar_links = [
        [
            'label' => 'All',
            'link' => '#all',
        ],
        [
            'label' => 'Create Anything',
            'link' => '#search-anything',
        ],
        [
            'label' => 'Your Plan',
            'link' => '#plan',
        ],
        [
            'label' => 'Invite a Friend',
            'link' => '#invite',
        ],
        [
            'label' => 'Campaigns',
            'link' => '#campaign-stats',
        ],
        [
            'label' => 'Recent',
            'link' => '#recent',
        ],
        [
            'label' => 'Favorite Templates',
            'link' => '#templates',
        ],
    ];

    $premium_features = \App\Models\OpenAIGenerator::query()->where('active', 1)->where('premium', 1)->limit(5)->get()->pluck('title')->toArray();
    $user_is_premium = false;
    $plan = auth()->user()?->relationPlan;
    if ($plan) {
        $planType = strtolower($plan->plan_type ?? 'all');
        if ($plan->plan_type === 'all' || $plan->plan_type === 'premium') {
            $user_is_premium = true;
        }
    }

    $referral_enabled = $app_is_demo || (($setting->feature_affilates ?? true) && \auth()->user()?->affiliate_status == 1);

    $style_string = '';

    if (setting('announcement_background_color')) {
        $style_string .= '.lqd-card.lqd-announcement-card { background-color: ' . setting('announcement_background_color') . ';}';
    }

    if (setting('announcement_background_image')) {
        $style_string .= '.lqd-card.lqd-announcement-card { background-image: url(' . setting('announcement_background_image') . '); }';
    }

    if (setting('announcement_background_color_dark')) {
        $style_string .= '.theme-dark .lqd-card.lqd-announcement-card { background-color: ' . setting('announcement_background_color_dark') . ';}';
    }

    if (setting('announcement_background_image_dark')) {
        $style_string .= '.theme-dark .lqd-card.lqd-announcement-card { background-image: url(' . setting('announcement_background_image_dark') . '); }';
    }

    $favoriteOpenAis = cache('favorite_openai');
@endphp

@if (filled($style_string))
    @push('css')
        <style>
            {{ $style_string }}
        </style>
    @endpush
@endif

@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Dashboard'))
@section('titlebar_title')
    {{ __('User Dashboard') }}
@endsection

@section('titlebar_title_after')
    <span class="inline-block h-4 w-px bg-border"></span>

    <x-dropdown.dropdown
        class:dropdown-dropdown="max-sm:-start-14"
        offsetY="20px"
    >
        <x-slot:trigger>
            <x-tabler-dots class="size-6" />
        </x-slot:trigger>

        <x-slot:dropdown
            class="min-w-52 whitespace-nowrap p-2"
        >
            <ul class="lqd-filter-list flex flex-col">
                @foreach ($titlebar_links as $link)
                    <li>
                        <x-button
                            @class([
                                'lqd-filter-btn relative w-full justify-between px-2.5 py-1.5 before:absolute before:-start-2 before:top-0 before:top-1/2 before:h-7 before:w-[3px] before:-translate-y-1/2 before:rounded-e before:bg-current before:opacity-0 [&.active]:before:opacity-100',
                                'active' => $loop->first,
                            ])
                            variant="link"
                            href="{{ $link['link'] }}"
                            @hashchange.window="$el.classList.toggle('active', window.location.hash === '{{ $link['link'] }}')"
                            ::class="{ active: window.location.hash === '{{ $link['link'] }}' }"
                        >
                            @lang($link['label'])
                        </x-button>
                    </li>
                @endforeach
            </ul>
        </x-slot:dropdown>
    </x-dropdown.dropdown>
@endsection

@section('content')
    <div class="flex flex-col gap-2.5 py-8 md:grid md:grid-cols-2">

        <!-- start: search anything -->
        @include('panel.user.dashboard.search-anything')
        <!-- end: search anything -->

        <!-- start: ongoing payment -->
        @if ($ongoingPayments != null)
            <div class="col-span-full mb-14">
                @includeIf('panel.user.finance.ongoingPayments')
            </div>
        @endif
        <!-- end: ongoing payment -->

        <!-- start: announcement -->
        @include('panel.user.dashboard.announcement')
        <!-- end: announcement -->

        <!-- start: finance subscription status -->
        @include('panel.user.finance.subscriptionStatus')
        <!-- end: finance subscription status -->

        @php
            $steps = [
                'invite' => [
                    'title' => __('Invite a Friend'),
                ],
            ];
            if ($referral_enabled) {
                $steps['referral-link'] = [
                    'title' => __('Referral Link'),
                ];
            }
            $steps['upgrade'] = [
                'title' => __('Upgrade'),
            ];
            $steps['support'] = [
                'title' => __('Support'),
            ];
        @endphp
        <x-card
            class:body="max-sm:p-6"
            id="invite"
            size="lg"
            x-data="{
                activeStep: '{{ array_key_first($steps) }}',
                steps: {{ json_encode($steps) }},
                autoplay: {
                    enabled: true,
                    paused: false,
                    timeout: 3500
                },
                interval: null,
                init() {
                    this.initAutoplay();
                },
                initAutoplay() {
                    const keys = Object.keys(this.steps);

                    this.interval = setInterval(() => {
                        if (!this.autoplay.enabled || this.autoplay.paused) {
                            return;
                        }

                        const activeIndex = keys.findIndex(key => key === this.activeStep);

                        this.activeStep = keys.at(activeIndex === keys.length - 1 ? 0 : (activeIndex + 1))
                    }, this.autoplay.timeout);
                },
                pauseAutoplay() {
                    this.autoplay.paused = true;
                },
                resumeAutoplay() {
                    this.autoplay.paused = false;
                }
            }"
            @pointerenter="pauseAutoplay"
            @pointerleave="resumeAutoplay"
        >
            <x-slot:head
                class="flex justify-between gap-1 px-8 py-6"
            >
                <h3
                    class="m-0"
                    x-text="steps[activeStep]['title']"
                >
                    {{ $steps[array_key_first($steps)]['title'] }}
                </h3>

                <div class="flex items-center gap-3">
                    @foreach ($steps as $key => $step)
                        <button
                            @class([
                                'inline-block size-1.5 rounded-full bg-foreground/10 transition-all [&.active]:w-3 [&.active]:bg-foreground',
                                'active' => $loop->first,
                            ])
                            @click.prevent="autoplay.enabled = false; activeStep = '{{ $key }}'"
                            :class="{ 'active': activeStep === '{{ $key }}' }"
                        ></button>
                    @endforeach
                </div>
            </x-slot:head>

            <div class="grid grid-cols-1 place-items-center">
                <div
                    class="active pointer-events-none invisible col-start-1 col-end-1 row-start-1 row-end-1 w-full translate-x-1 text-center opacity-0 transition-all [&.active]:pointer-events-auto [&.active]:visible [&.active]:translate-x-0 [&.active]:opacity-100"
                    id="invite"
                    :class="{ 'active': activeStep === 'invite' }"
                >
                    @include('panel.user.dashboard.invite')
                </div>

                @if ($referral_enabled)
                    <div
                        class="pointer-events-none invisible col-start-1 col-end-1 row-start-1 row-end-1 w-full translate-x-1 text-center opacity-0 transition-all [&.active]:pointer-events-auto [&.active]:visible [&.active]:translate-x-0 [&.active]:opacity-100"
                        id="referral-link"
                        :class="{ 'active': activeStep === 'referral-link' }"
                    >
                        @include('panel.user.dashboard.referral-link')
                    </div>
                @endif

                <div
                    class="pointer-events-none invisible col-start-1 col-end-1 row-start-1 row-end-1 w-full translate-x-1 text-center opacity-0 transition-all [&.active]:pointer-events-auto [&.active]:visible [&.active]:translate-x-0 [&.active]:opacity-100"
                    id="upgrade"
                    :class="{ 'active': activeStep === 'upgrade' }"
                >
                    @include('panel.user.dashboard.upgrade')
                </div>

                <div
                    class="pointer-events-none invisible col-start-1 col-end-1 row-start-1 row-end-1 w-full translate-x-1 text-center opacity-0 transition-all [&.active]:pointer-events-auto [&.active]:visible [&.active]:translate-x-0 [&.active]:opacity-100"
                    id="support"
                    :class="{ 'active': activeStep === 'support' }"
                >
                    @include('panel.user.dashboard.support')
                </div>
            </div>
        </x-card>

        @include('panel.user.dashboard.campaigns-stats')

        @include('panel.user.dashboard.recent-campaigns')

        @include('panel.user.dashboard.recently-launched')

        @include('panel.user.dashboard.favorite-templates')

    </div>
@endsection

@push('script')
    @if ($app_is_not_demo)
        @includeFirst(['onboarding::include.introduction', 'panel.admin.onboarding.include.introduction', 'vendor.empty'])
        @includeFirst(['onboarding-pro::include.introduction', 'panel.admin.onboarding-pro.include.introduction', 'vendor.empty'])
    @endif
    @if (Route::has('dashboard.user.dash_notify_seen'))
        <script>
            function dismiss() {
                // localStorage.setItem('lqd-announcement-dismissed', true);
                document.querySelector('.lqd-announcement').style.display = 'none';
                $.ajax({
                    url: '{{ route('dashboard.user.dash_notify_seen') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        /* console.log(response); */
                    }
                });
            }
        </script>
    @endif
@endpush
