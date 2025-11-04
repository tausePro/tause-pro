<div
    class="relative col-span-full mb-14"
    id="search-anything"
    data-name="{{ \App\Enums\Introduction::DASHBOARD_TWO }}"
>
    {{-- blade-formatter-disable --}}
	<svg class="absolute -top-24 start-1/2 -z-1 -translate-x-1/2" width="1011" height="1080" viewBox="0 0 1011 1080" fill="none" xmlns="http://www.w3.org/2000/svg" > <path d="M897.437 794.115C730.586 1051.22 420.385 1142.17 205.52 1002.73C-9.34523 863.29 -52.6379 542.945 114.213 285.837C281.064 28.7301 591.266 -62.2142 806.131 77.2238C1021 216.662 1064.29 537.008 897.437 794.115Z" stroke="url(#paint0_linear_187_438)" stroke-width="23" /> <defs> <linearGradient id="paint0_linear_187_438" x1="-42.0001" y1="313.027" x2="1041.17" y2="275.278" gradientUnits="userSpaceOnUse" > <stop stop-color="#E385FF" /> <stop offset="0.115385" stop-color="#B694DD" /> <stop offset="0.167583" stop-color="#F1F0F4" stop-opacity="0" /> <stop offset="0.518135" stop-color="#F1F0F4" stop-opacity="0" /> <stop offset="0.817308" stop-color="#F1F0F4" stop-opacity="0" /> <stop offset="1" stop-color="#65D572" /> </linearGradient> </defs> </svg>
	{{-- blade-formatter-enable --}}
    <div class="relative z-1 mx-auto lg:w-7/12">
        <p class="mb-8 text-center font-heading text-[21px] font-bold leading-none -tracking-wide">
            <span class="opacity-50">
                {{ __('Hello') }} {{ auth()->user()?->name }},
            </span>
            <span class="block text-[51px]">
                {{ __('Create anything') }}
            </span>
        </p>

        <div class="relative mb-8">
            <x-header-search
                class="w-full rounded-full [--input-rounded:9999px] [&_.header-search-border-play-inner]:[background:conic-gradient(from_0deg,transparent_0%,var(--gradient-stops),transparent_40%)] [&_.header-search-border]:-inset-1.5"
                class:input="h-20 rounded-full border-none bg-background bg-card-background ps-16 sm:ps-20 text-heading-foreground shadow-[0_4px_8px_rgba(0,0,0,0.05)] placeholder:text-heading-foreground sm:text-xs placeholder-shown:text-ellipsis max-sm:pe-16"
                class:recent-search="max-lg:bottom-auto max-lg:top-full max-lg:mt-3"
                size="lg"
                :show-arrow=false
                :show-icon=false
                :show-kbd=false
                :outline-glow=true
            />
            <x-button
                class="group absolute start-3 top-1/2 size-10 -translate-y-1/2 rounded-full text-foreground hover:-translate-y-1/2 hover:scale-105 sm:start-4 sm:size-11"
                variant="outline"
                size="none"
                href="{{ $setting->feature_ai_advanced_editor ? LaravelLocalization::localizeUrl(route('dashboard.user.generator.index')) : LaravelLocalization::localizeUrl(route('dashboard.user.openai.list')) }}"
            >
                <x-tabler-plus class="size-4" />
            </x-button>

            <span
                class="roudned-full pointer-events-none absolute end-3 top-1/2 inline-grid size-10 -translate-y-1/2 place-items-center rounded-full bg-foreground/5 backdrop-blur sm:end-4 sm:size-11"
            >
                {{-- blade-formatter-disable --}}
				<svg width="17" height="16" viewBox="0 0 17 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.3327 0.167384C16.4923 0.327064 16.543 0.565806 16.4619 0.776576L10.7476 15.6337C10.665 15.8486 10.4614 15.9929 10.2313 15.9998C10.0011 16.0066 9.78936 15.8746 9.69408 15.665L7.30441 10.4078L10.8204 6.89181C11.1551 6.55707 11.1551 6.01435 10.8204 5.67962C10.4856 5.34489 9.94293 5.34489 9.60819 5.67962L6.09222 9.1956L0.834971 6.80594C0.625361 6.71066 0.493411 6.49889 0.500254 6.26874C0.507098 6.03861 0.651399 5.83504 0.866299 5.75239L15.7234 0.0381047C15.9342 -0.0429607 16.1729 0.00770375 16.3327 0.167384Z"/></svg>
				{{-- blade-formatter-enable --}}
            </span>
        </div>

        @php

		$socials = [];

		if (\App\Helpers\Classes\MarketplaceHelper::isRegistered('marketing-bot')) {
			$socials = [
				[
                    'label' => __('WhatsApp'),
                    'icon' =>
                        '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12.003 0H11.997C5.3805 0 0 5.382 0 12C0 14.625 0.846 17.058 2.2845 19.0335L0.789 23.4915L5.4015 22.017C7.299 23.274 9.5625 24 12.003 24C18.6195 24 24 18.6165 24 12C24 5.3835 18.6195 0 12.003 0ZM18.9855 16.9455C18.696 17.763 17.547 18.441 16.6305 18.639C16.0035 18.7725 15.1845 18.879 12.4275 17.736C8.901 16.275 6.63 12.6915 6.453 12.459C6.2835 12.2265 5.028 10.5615 5.028 8.8395C5.028 7.1175 5.9025 6.279 6.255 5.919C6.5445 5.6235 7.023 5.4885 7.482 5.4885C7.6305 5.4885 7.764 5.496 7.884 5.502C8.2365 5.517 8.4135 5.538 8.646 6.0945C8.9355 6.792 9.6405 8.514 9.7245 8.691C9.81 8.868 9.8955 9.108 9.7755 9.3405C9.663 9.5805 9.564 9.687 9.387 9.891C9.21 10.095 9.042 10.251 8.865 10.47C8.703 10.6605 8.52 10.8645 8.724 11.217C8.928 11.562 9.633 12.7125 10.671 13.6365C12.0105 14.829 13.0965 15.21 13.485 15.372C13.7745 15.492 14.1195 15.4635 14.331 15.2385C14.5995 14.949 14.931 14.469 15.2685 13.9965C15.5085 13.6575 15.8115 13.6155 16.1295 13.7355C16.4535 13.848 18.168 14.6955 18.5205 14.871C18.873 15.048 19.1055 15.132 19.191 15.2805C19.275 15.429 19.275 16.1265 18.9855 16.9455Z"/></svg>',
                    'link' => route('dashboard.user.marketing-bot.whatsapp-campaign.index') ,
                ],
                [
                    'label' => __('Telegram'),
                    'icon' =>
                        '<svg width="25" height="21" viewBox="0 0 25 21" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.64565 10.0843C8.05306 6.79002 12.3405 4.65061 14.4677 3.59942C20.5765 0.603739 21.8521 0.0633191 22.6999 0.00403786C22.8765 -0.00831239 23.3028 -0.00341211 23.5999 0.184071C23.8239 0.341954 23.9114 0.578798 23.9588 0.749031C24.0062 0.919264 24.0681 1.29674 24.0545 1.61008C23.9452 5.12338 23.0936 13.7562 22.5894 17.7136C22.3874 19.3938 21.7902 19.9909 21.1946 20.102C19.8949 20.297 18.8028 19.4015 17.4757 18.696C15.4082 17.5563 14.2223 16.8409 12.1925 15.7333C9.86573 14.4382 11.2584 13.5425 12.4407 12.1756C12.7345 11.8079 18.2029 6.25382 18.2751 5.76284C18.2703 5.69376 18.2911 5.48405 18.1426 5.39031C17.994 5.29656 17.8198 5.34346 17.6809 5.38788C17.4714 5.43724 14.4372 7.73198 8.54065 12.24C7.66572 12.926 6.8726 13.2591 6.13081 13.311C5.31838 13.3678 3.73275 13.0275 2.54051 12.729C1.09618 12.3788 -0.0510789 12.2161 0.00175421 11.4487C0.0787405 11.0269 0.61493 10.5728 1.64565 10.0843Z"/></svg>',
                    'link' => route('dashboard.user.marketing-bot.telegram-campaign.index'),

                ]
			];
		}

		if (\App\Helpers\Classes\MarketplaceHelper::isRegistered('social-media')) {
			$socials = array_merge($socials, [
			[
                    'label' => __('Facebook'),
                    'icon' =>
                        '<svg width="25" height="24" viewBox="0 0 25 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M24.0564 12.073C24.0564 18.1095 19.6532 23.1237 13.8952 24V15.5781H16.7016L17.2339 12.073H13.8952V9.83367C13.8952 8.86004 14.379 7.93509 15.879 7.93509H17.379V4.96552C17.379 4.96552 16.0242 4.72211 14.6693 4.72211C11.9597 4.72211 10.1693 6.42596 10.1693 9.44422V12.073H7.12096V15.5781H10.1693V24C4.41128 23.1237 0.0564423 18.1095 0.0564423 12.073C0.0564423 5.40365 5.42741 0 12.0564 0C18.6855 0 24.0564 5.40365 24.0564 12.073Z"/></svg>',
                    'link' => \Illuminate\Support\Facades\Route::has('dashboard.user.social-media.post.index') ? route('dashboard.user.social-media.post.index') : '#',
                ],
                [
                    'label' => __('Instagram'),
                    'icon' =>
                        '<svg width="21" height="20" viewBox="0 0 21 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M15.0564 0H5.05644C2.29644 0 0.0564423 2.24 0.0564423 5V15C0.0564423 17.76 2.29644 20 5.05644 20H15.0564C17.8164 20 20.0564 17.76 20.0564 15V5C20.0564 2.24 17.8164 0 15.0564 0ZM10.0564 15C7.29644 15 5.05644 12.76 5.05644 10C5.05644 7.24 7.29644 5 10.0564 5C12.8164 5 15.0564 7.24 15.0564 10C15.0564 12.76 12.8164 15 10.0564 15ZM15.4064 5.62C14.8564 5.62 14.4064 5.17 14.4064 4.62C14.4064 4.07 14.8564 3.62 15.4064 3.62C15.9564 3.62 16.4064 4.07 16.4064 4.62C16.4064 5.17 15.9564 5.62 15.4064 5.62Z"/><path d="M10.0564 13C11.7133 13 13.0564 11.6569 13.0564 10C13.0564 8.34315 11.7133 7 10.0564 7C8.39959 7 7.05644 8.34315 7.05644 10C7.05644 11.6569 8.39959 13 10.0564 13Z"/></svg>',
                    'link' => \Illuminate\Support\Facades\Route::has('dashboard.user.social-media.post.index') ? route('dashboard.user.social-media.post.index') : '#',
                ],
                [
                    'label' => __('Linkedin'),
                    'icon' =>
                        '<svg width="25" height="24" viewBox="0 0 25 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12.0564 0C5.43004 0 0.0564423 5.3736 0.0564423 12C0.0564423 18.6264 5.43004 24 12.0564 24C18.6828 24 24.0564 18.6264 24.0564 12C24.0564 5.3736 18.6828 0 12.0564 0ZM8.56938 18.1406H5.64684V9.34808H8.56938V18.1406ZM7.1082 8.14746H7.08916C6.10844 8.14746 5.47417 7.47235 5.47417 6.6286C5.47417 5.76581 6.12785 5.10938 7.12761 5.10938C8.12737 5.10938 8.7426 5.76581 8.76164 6.6286C8.76164 7.47235 8.12737 8.14746 7.1082 8.14746ZM19.1075 18.1406H16.1853V13.4368C16.1853 12.2547 15.7621 11.4485 14.7047 11.4485C13.8974 11.4485 13.4165 11.9923 13.2052 12.5173C13.128 12.7051 13.1091 12.9677 13.1091 13.2305V18.1406H10.1868C10.1868 18.1406 10.225 10.173 10.1868 9.34808H13.1091V10.593C13.4975 9.9939 14.1924 9.14172 15.7429 9.14172C17.6657 9.14172 19.1075 10.3984 19.1075 13.099V18.1406Z"/></svg>',
                    'link' => \Illuminate\Support\Facades\Route::has('dashboard.user.social-media.post.index') ? route('dashboard.user.social-media.post.index') : '#',
                ],
			]);
		}
        @endphp

		@if(count($socials))
			<div class="flex flex-wrap justify-center gap-4 sm:justify-between">
				@foreach ($socials as $item)
					<a
						class="group flex flex-col items-center gap-2 text-center text-xs font-medium transition hover:scale-105"
						href="{{ $item['link'] }}"
					>
                    <span class="size-6 opacity-40 transition group-hover:scale-125">
                        {!! $item['icon'] !!}
                    </span>
						{{ $item['label'] }}
					</a>
				@endforeach
			</div>
		@endif

    </div>
</div>
