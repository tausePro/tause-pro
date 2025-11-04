@if ($app_is_demo || (($setting->feature_affilates ?? true) && \auth()->user()?->affiliate_status == 1))
    @php
        $totalEarning = cache('total_earnings') ?: 0;
    @endphp

    <div
        class="text-center"
        x-data="{}"
    >
        <div class="relative mb-6 inline-grid place-items-center rounded-full text-[32px] leading-none">
            <div class="col-start-1 col-end-1 row-start-1 row-end-1 size-[58px] rounded-full bg-[linear-gradient(to_right,var(--gradient-stops))]"></div>
            <div class="col-start-1 col-end-1 row-start-1 row-end-1 inline-grid size-12 place-items-center rounded-full bg-card-background">
                🥳
            </div>
        </div>

        <h2 class="mb-3">
            {{ __('Refer new users and earn commissions.') }}
        </h2>

        <p class="mb-5 text-balance">
            {{ __('Simply share your referral link and have your friends sign up through it.') }}
        </p>

        <div class="overflow-hidden rounded-lg border">
            <p class="m-0 flex items-center justify-between gap-1 border-b px-4 py-2.5">
                {{ __('Referral Earnings') }}

                <strong class="text-xl/none">
                    {{ currency()->symbol }}{{ $totalEarning ?? 0 }}
                </strong>
            </p>
            <p class="m-0 flex items-center justify-between gap-1 bg-foreground/5 px-4 py-3.5 text-start">
                <input
                    id="invite-url"
                    type="hidden"
                    value="{{ LaravelLocalization::localizeUrl(url('/') . '/register?aff=' . \Illuminate\Support\Facades\Auth::user()->affiliate_code) }}"
                />
                <span class="w-9/12">
                    {{ str()->limit(LaravelLocalization::localizeUrl(url('/') . '/register?aff=' . \Illuminate\Support\Facades\Auth::user()->affiliate_code), 60) }}
                </span>

                <x-button
                    size="none"
                    variant="none"
                    @click.prevent="navigator.clipboard.writeText(document.getElementById('invite-url').value); toastr.success('{{ __('Copied to clipboard!') }}');"
                >
                    <x-tabler-copy class="size-5" />
                </x-button>
            </p>
        </div>
    </div>
@endif
