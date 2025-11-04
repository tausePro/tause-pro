{{-- @if (setting('announcement_active', 0) && !auth()->user()?->dash_notify_seen) --}}
<div
    class="lqd-announcement relative z-0 col-span-full mb-5 flex flex-wrap items-center justify-center gap-1 rounded-xl border bg-cover bg-center px-4 py-4 lg:justify-between lg:rounded-full lg:py-3"
    data-name="{{ \App\Enums\Introduction::DASHBOARD_FIRST }}"
    x-data="{ show: true }"
    x-ref="announcement"
>
    <script>
        const announcementDismissed = localStorage.getItem('lqd-announcement-dismissed');
        if (announcementDismissed) {
            document.querySelector('.lqd-announcement').style.display = 'none';
        }
    </script>

    <div class="max-lg:mb-4">
        {{-- @if (setting('announcement_image_dark'))
			<img
				class="announcement-img announcement-img-dark peer hidden w-28 shrink-0 dark:block"
				src="{{ setting('announcement_image_dark', '/upload/images/speaker.png') }}"
				alt="@lang(setting('announcement_title', 'Welcome to MagicAI!'))"
			>
		@endif
		<img
			class="announcement-img announcement-img-light w-28 shrink-0 dark:peer-[&.announcement-img-dark]:hidden"
			src="{{ setting('announcement_image', '/upload/images/speaker.png') }}"
			alt="@lang(setting('announcement_title', 'Welcome to MagicAI!'))"
		> --}}
        <svg
            width="14"
            height="16"
            viewBox="0 0 14 16"
            fill="currentColor"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path d="M3.11111 16L6.22222 10L0 9.2L9.33333 0H10.8889L7.77778 6L14 6.8L4.66667 16H3.11111Z" />
        </svg>
    </div>

    <div class="flex grow flex-col items-center justify-center gap-2 text-center lg:flex-row">
        <h3 class="m-0 text-xs">
            @lang(setting('announcement_title', 'Welcome'))
        </h3>

        <p class="text-xs lg:mb-0">
            @lang(setting('announcement_description', 'We are excited to have you here. Explore the marketplace to find the best AI models for your needs.'))
        </p>

        <x-button
            class="bg-[linear-gradient(to_right,var(--gradient-stops))] bg-clip-text font-medium text-transparent"
            variant="link"
            href="{{ setting('announcement_url', '#') }}"
        >
            {{ setting('announcement_button_text', 'Try it') }}
        </x-button>
    </div>

    <x-button
        class="size-5 font-medium max-lg:absolute max-lg:-end-2 max-lg:-top-2 max-lg:size-8 max-lg:rounded-full max-lg:bg-surface-background max-lg:shadow-xl max-lg:shadow-black/5"
        href="javascript:void(0)"
        variant="link"
        size="none"
        @click.prevent="{{ $app_is_demo ? 'toastr.info(\'This feature is disabled in Demo version.\')' : ' dismiss()' }}"
        title="{{ __('Dismiss') }}"
    >
        <x-tabler-x
            class="size-4"
            stroke-width="2"
        />
    </x-button>
</div>
{{-- @endif --}}
