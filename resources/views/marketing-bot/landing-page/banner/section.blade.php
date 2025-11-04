<section
    class="site-section relative grid min-h-screen place-content-center pb-20 pt-40 lg:pb-28 lg:pt-44"
    id="banner"
>
    <div class="pointer-events-none absolute left-0 top-0 h-full w-full overflow-hidden opacity-0 transition-opacity group-[.page-loaded]/body:opacity-100">
        <svg
            class="absolute -top-[650px] end-0 hidden lg:block"
            width="835"
            height="1300"
            viewBox="0 0 835 1300"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M25.5 1300H280C586.518 1300 835 1051.52 835 745V555C835 257.032 600.186 13.9068 305.509 0.575824C291.44 -0.0606409 280 11.4167 280 25.5C280 39.5833 291.429 50.9328 305.495 51.6336C572 64.9114 784 285.198 784 555V745C784 1023.35 558.352 1249 280 1249H25.5C11.4167 1249 0 1260.42 0 1274.5C0 1288.58 11.4167 1300 25.5 1300Z"
                fill="url(#paint0_linear_276_8)"
            />
            <defs>
                <linearGradient
                    id="paint0_linear_276_8"
                    x1="685.041"
                    y1="427.325"
                    x2="26.082"
                    y2="1224.07"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop stop-color="#43CC3E" />
                    <stop
                        offset="0.663713"
                        stop-color="#3BB4FF"
                    />
                    <stop
                        offset="0.935625"
                        stop-color="#D9A1FF"
                    />
                    <stop
                        offset="1"
                        stop-color="white"
                        stop-opacity="0"
                    />
                </linearGradient>
            </defs>
        </svg>
    </div>

    <div class="container relative">
        <div class="flex grid-cols-12 flex-col gap-8 max-lg:gap-14 lg:grid">
            <div class="col-span-11 col-start-1 lg:col-span-6">
                <h6
                    class="relative mb-4 inline-flex translate-y-6 items-center gap-3.5 overflow-hidden rounded-full border px-2.5 py-1.5 font-body text-[12px] font-medium leading-4 text-foreground opacity-0 transition-all group-[.page-loaded]/body:translate-y-0 group-[.page-loaded]/body:opacity-100">
                    <span
                        class="relative rounded-full border bg-gradient-to-r from-[--gradient-1] via-[--gradient-3] to-[--gradient-5] bg-clip-text px-2 py-0.5 text-transparent">{!! __($setting->site_name) !!}</span>
                    <span class="relative">{!! __($fSetting->hero_subtitle) !!}</span>
                </h6>

                <h1
                    class="banner-title mb-8 translate-y-7 opacity-0 transition-all delay-150 ease-out group-[.page-loaded]/body:translate-y-0 group-[.page-loaded]/body:opacity-100 max-sm:text-[54px]">
                    {!! __($fSetting->hero_title) !!}
                    @if ($fSetting->hero_title_text_rotator != null)
                        <span class="lqd-text-rotator inline-grid grid-cols-1 grid-rows-1 transition-[width] duration-200">
                            @foreach (explode(',', __($fSetting->hero_title_text_rotator)) as $keyword)
                                <span
                                    class="lqd-text-rotator-item {{ $loop->first ? 'lqd-is-active' : '' }} col-start-1 row-start-1 inline-flex translate-x-3 opacity-0 blur-sm transition-all duration-300 [&.lqd-is-active]:translate-x-0 [&.lqd-is-active]:opacity-100 [&.lqd-is-active]:blur-0"
                                >
                                    <span>{!! $keyword !!}</span>
                                </span>
                            @endforeach
                        </span>
                    @endif
                </h1>

                <p
                    class="mb-8 translate-y-3 text-base/[1.4em] opacity-0 transition-all delay-300 ease-out group-[.page-loaded]/body:translate-y-0 group-[.page-loaded]/body:opacity-100 lg:w-9/12">
                    {!! __($fSetting->hero_description) !!}
                </p>

                <div
                    class="flex translate-y-3 flex-wrap items-center gap-5 opacity-0 transition-all delay-[450ms] group-[.page-loaded]/body:translate-y-0 group-[.page-loaded]/body:opacity-100">
                    @if ($fSetting->hero_button_type == 1)
                        <a
                            class="group relative inline-flex items-center gap-4 rounded-xl bg-foreground px-7 py-5 text-base font-semibold text-background transition-all after:pointer-events-none after:absolute after:-bottom-[5px] after:start-0 after:-z-1 after:h-full after:w-full after:rounded-xl after:transition-transform after:[background:linear-gradient(to_right,var(--gradient-stops))] hover:bg-background hover:text-foreground hover:shadow-lg hover:shadow-black/10 hover:after:-translate-y-[5px]"
                            href="{{ !empty($fSetting->hero_button_url) ? $fSetting->hero_button_url : '#' }}"
                        >
                            {!! __($fSetting->hero_button) !!}
                            <svg
                                class="transition-transform group-hover:translate-x-1"
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                width="17"
                                height="12"
                                viewBox="0 0 17 12"
                                fill="currentColor"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M15.2643 5.10208C13.0752 5.10208 11.0801 3.10784 11.0801 0.917858V0.0199585H9.28428V0.917858C9.28428 2.51073 9.98285 4.00484 11.0792 5.10208H0V6.89788H11.0792C9.98285 7.99511 9.28428 9.48922 9.28428 11.0821V11.98H11.0801V11.0821C11.0801 8.89211 13.0752 6.89788 15.2643 6.89788H16.1622V5.10208H15.2643Z"
                                />
                            </svg>
                        </a>
                    @else
                        <a
                            class="relative inline-flex items-center gap-4 rounded-xl bg-foreground px-7 py-3 text-base font-semibold text-background transition-all after:pointer-events-none after:absolute after:-bottom-[5px] after:start-0 after:-z-1 after:h-full after:w-full after:rounded-xl after:transition-transform after:[background:linear-gradient(to_right,var(--gradient-stops))] hover:bg-background hover:text-foreground hover:shadow-lg hover:shadow-black/10 hover:after:-translate-y-[5px]"
                            data-fslightbox="video-gallery"
                            href="{{ !empty($fSetting->hero_button_url) ? $fSetting->hero_button_url : '#' }}"
                        >
                            <span class="inline-grid size-10 place-items-center rounded-full bg-background">
                                <x-tabler-player-play class="size-5 fill-foreground" />
                            </span>
                            {!! __($fSetting->hero_button) !!} &nbsp;
                        </a>
                    @endif
                    <div class="translate-y-3 opacity-0 transition-all delay-[500ms] group-[.page-loaded]/body:translate-y-0 group-[.page-loaded]/body:opacity-100">
                        <a
                            class="opacity-50 transition-opacity hover:opacity-100"
                            href="#features"
                        >
                            {!! __($fSetting->hero_scroll_text) !!}
                        </a>
                    </div>
                </div>

                <p class="mt-8 translate-y-4 text-3xs opacity-0 transition-all delay-[550ms] group-[.page-loaded]/body:translate-y-0 group-[.page-loaded]/body:opacity-100">
                    @lang('Try for free. No credit card required.')
                </p>
            </div>

            <div class="relative col-span-full flex lg:col-start-7 lg:col-end-13 lg:justify-end">
                <figure
                    class="inline-flex translate-y-7 opacity-0 transition-all delay-[550ms] ease-out group-[.page-loaded]/body:translate-y-0 group-[.page-loaded]/body:opacity-100 max-md:mx-auto lg:ms-auto"
                    aria-hidden="true"
                >
                    <img
                        width="340"
                        height="489"
                        src="{{ custom_theme_url('/assets/landing-page/img/banner-img.png') }}"
                        alt="{{ __('Marketing Bot Screenshot') }}"
                    >
                </figure>
            </div>
        </div>
    </div>
</section>
