<header class="lqd-header relative flex h-[--header-height] text-xs font-medium transition-colors max-lg:h-[65px]">
    <div @class([
        'lqd-header-container flex w-full grow gap-2 px-4 max-lg:w-full max-lg:max-w-none',
        'container' => !$attributes->get('layout-wide'),
        'container-fluid' => $attributes->get('layout-wide'),
        Theme::getSetting('wideLayoutPaddingX', '') =>
            filled(Theme::getSetting('wideLayoutPaddingX', '')) &&
            $attributes->get('layout-wide'),
    ])>
        <x-header-logo class="group-[&.focus-mode]/body:hidden max-lg:hidden" />

        {{-- Mobile nav toggle and logo --}}
        <div class="mobile-nav-logo flex items-center gap-3 lg:hidden">
            <button
                class="lqd-mobile-nav-toggle flex size-10 items-center justify-center"
                type="button"
                x-init
                @click.prevent="$store.mobileNav.toggleNav()"
                :class="{ 'lqd-is-active': !$store.mobileNav.navCollapse }"
            >
                <span class="lqd-mobile-nav-toggle-icon relative h-[2px] w-5 rounded-xl bg-current"></span>
            </button>
            <x-header-logo />
        </div>

        {{-- Title slot --}}
        @if ($title ?? false)
            <div class="header-title-container peer/title hidden items-center lg:flex">
                <h1 class="m-0 font-semibold">
                    {{ $title }}
                </h1>
            </div>
        @endif

        @includeFirst(['focus-mode::header', 'components.includes.ai-tools', 'vendor.empty'])

        <div class="header-actions-container flex grow justify-end gap-4 max-lg:basis-2/3 max-lg:gap-2">
            {{-- Action buttons --}}
            @if ($actions ?? false)
                {{ $actions }}
            @else
                <div class="flex items-center max-xl:gap-2 max-lg:hidden xl:gap-5">
                    @if (Auth::user()->isAdmin())
						<x-update-available />
                        <x-button
                            href="{{ route('dashboard.admin.index') }}"
                            variant="link"
                        >
                            {{ __('Admin Panel') }}
                        </x-button>
                    @endif

                    @if (Auth::user()->isAdmin())
						@if ($app_is_not_demo)
							{{-- Upgrade button --}}
							<x-modal
								class="max-lg:hidden"
								type="page"
							>
								<x-slot:trigger
									custom
								>
									<x-button
										class="lqd-header-upgrade-btn flex items-center justify-center p-0 text-current"
										href="#"
										variant="link"
										title="{{ __('Premium Membership') }}"
										@click.prevent="toggleModal()"
									>
										<x-tabler-diamond class="size-5" />
										{{ __('Upgrade') }}
									</x-button>
								</x-slot:trigger>
								<x-slot:modal>
									@includeIf('premium-support.index')
								</x-slot:modal>
							</x-modal>
						@else
							<x-button
								class="lqd-header-upgrade-btn flex items-center justify-center p-0 text-current"
								href="{{ route('dashboard.user.payment.subscription') }}"
								variant="link"
							>
								<x-tabler-diamond class="size-5" />
								{{ __('Upgrade') }}
							</x-button>
						@endif
                    @endif
                </div>
            @endif

            <span class="hidden h-4 w-px self-center bg-border transition-colors lg:inline-block"></span>

            <div class="flex items-center gap-4 max-lg:gap-2">
                @includeIf('marketing-bot::header.inbox-notification')

                <x-header-search
                    class="max-lg:hidden"
                    style="modern"
                />

                {{-- Dark/light switch --}}
                @if (Theme::getSetting('dashboard.supportedColorSchemes') === 'all')
                    <x-light-dark-switch />
                @endif

                @includeFirst(['focus-mode::ai-tools-button', 'components.includes.ai-tools-button', 'vendor.empty'])

                @if (setting('notification_active', 0))
                    {{-- Notifications --}}
                    <x-notifications />
                @endif

                {{-- Language dropdown --}}
                @if (count(explode(',', $settings_two->languages)) > 1)
                    <x-language-dropdown />
                @endif

                <x-button
                    class="size-[34px] bg-foreground/5 text-foreground max-lg:hidden"
                    href="{{ route('dashboard.user.affiliates.index') }}"
                    hover-variant="primary"
                    size="none"
                >
                    <span class="sr-only">
                        {{ __('Affiliate Program') }}
                    </span>
                    <x-tabler-gift class="size-5" />
                </x-button>

                {{-- User menu --}}
                <x-user-dropdown />
            </div>
        </div>
    </div>
</header>
