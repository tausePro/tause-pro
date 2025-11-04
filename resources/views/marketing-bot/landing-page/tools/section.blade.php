@php
    $tools = $tools ?? [];
    $colors = ['#E6FCFF', '#FFF1E6', '#ECE6FF', '#CCFFD9', '#FFE4DE'];
    $i = 0;

    // TODO: impelement a background color option for tool items
    foreach ($tools as $key => $item) {
        if (!isset($item['bg_color'])) {
            $tools[$key]['bg_color'] = $colors[$i] ?? '#E6FCFF';
        }
        $i++;
    }
@endphp

{!! adsense_tools_728x90() !!}
<section
    class="site-section"
    id="tools"
    x-data="landingPageTools"
>
    <div
        class="tools-nav-wrap relative z-10 hidden lg:block"
        x-ref="toolsNavWrap"
    >
        <x-progressive-blur class="-top-8 h-auto lg:rounded-xl" />

        <div class="container">
            <div
                class="flex gap-3 py-8"
                x-ref="toolsNav"
            >
                @for ($i = 0; $i < count($tools); $i++)
                    <a
                        class="tool-nav-link relative inline-flex h-1 grow overflow-hidden rounded-full bg-black/10 before:absolute before:-inset-y-3 before:z-10 before:w-full"
                        href="#tool-item-{{ $i }}"
                        x-ref="itemProgressBar_{{ $i }}"
                    >
                        <span class="absolute inset-0 inline-block origin-left scale-x-0 bg-current"></span>
                    </a>
                @endfor
            </div>
        </div>
    </div>

    <div>
        @foreach ($tools as $item)
            @php
                $bg_from = $loop->first ? '#F9F9F9' : (isset($bg_to) ? $bg_to : $tools[$loop->index - 1]['bg_color']);
                $bg_via = $item['bg_color'];
                $bg_to = $loop->last ? '#F9F9F9' : $tools[$loop->index + 1]['bg_color'];

                // Get the image dimensions
                $imagePath = public_path(str_replace(url('/'), '', $item['image']));
                $imageSize = [0, 0];
                if (file_exists($imagePath)) {
                    $imageSize = getimagesize($imagePath);
                }
                $width = $imageSize[0] ?? 'auto';
                $height = $imageSize[1] ?? 'auto';
            @endphp

            <div
                class="tool-item relative grid min-h-screen grid-cols-1 place-items-center py-20"
                id="tool-item-{{ $loop->index }}"
                x-ref="item_{{ $loop->index }}"
            >
                <div
                    class="tool-item-bg absolute inset-0 z-0"
                    style="background: linear-gradient(to bottom, {{ $bg_from }} 0%, {{ $bg_via }}, {{ $bg_via }}, {{ $bg_to }} 100%)"
                ></div>

                <div class="container">
                    <div
                        class="tool-item-card relative z-1 grid min-h-[max(600px,40vh)] origin-top grid-cols-12 items-center gap-5 rounded-3xl p-7 max-md:place-content-start max-md:gap-y-10 sm:p-10 md:p-16"
                        style="background-color: {{ lightenColor($item['bg_color'], 40) }}"
                        x-ref="itemCard_{{ $loop->index }}"
                    >
                        <figure
                            class="col-start-1 col-end-12 md:col-end-6"
                            aria-hidden="true"
                        >
                            <img
                                class="w-full rounded-[20px] border-[8px] border-white/50 shadow-[0_33px_44px_hsl(0_0%_0%/5%)]"
                                src="{{ custom_theme_url($item->image, true) }}"
                                width="{{ $width }}"
                                height="{{ $height }}"
                            >
                        </figure>

                        <div class="col-span-12 col-start-1 md:col-span-6 md:col-start-7">
                            <h2 class="mb-5">
                                {!! __($item->title) !!}
                            </h2>
                            <p class="mb-12 opacity-80">
                                {!! __($item->description) !!}
                            </p>

                            {{-- TODO: need a button url option in backend --}}
                            <a
                                class="flex items-center gap-2 text-base font-semibold -tracking-wide underline"
                                href="#"
                            >
                                {{ __('See it in action') }}
                                {{-- blade-formatter-disable --}}
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" > <path d="M9.99997 0.387695C5.61613 0.387695 1.70509 3.53866 0.788054 7.82938C0.329894 9.97329 0.638534 12.2531 1.66069 14.193C2.64445 16.0599 4.25725 17.5688 6.18685 18.4239C8.19397 19.3136 10.4982 19.4687 12.6078 18.8619C14.643 18.2768 16.4476 16.9926 17.6761 15.2687C20.2449 11.6646 19.9014 6.60202 16.8793 3.37258C15.1081 1.47994 12.5925 0.387695 9.99997 0.387695ZM14.5189 10.311L11.9509 12.9409C11.3008 13.6069 10.2731 12.5979 10.9206 11.9351L12.2164 10.6081H6.07573C5.63965 10.6081 5.27581 10.244 5.27581 9.80817C5.27581 9.37233 5.63989 9.00825 6.07573 9.00825H12.1857L10.8642 7.68706C10.2076 7.03042 11.2257 6.0121 11.8823 6.66874L14.5129 9.29914C14.7918 9.57778 14.7945 10.029 14.5189 10.311Z" /> </svg>
								{{-- blade-formatter-enable --}}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

@push('script')
    <script>
        (() => {
            document.addEventListener('alpine:init', () => {
                Alpine.data('landingPageTools', () => ({
                    toolItems: document.querySelectorAll('.tool-item'),
                    toolCards: document.querySelectorAll('.tool-item-card'),
                    init() {
                        this.initNav();
                        this.pinCards();
                        this.pinItems();
                    },
                    initNav() {
                        const links = document.querySelectorAll('.tool-nav-link');

                        links.forEach(link => {
                            link.addEventListener('click', event => {
                                event.preventDefault();

                                gsap.to(window, {
                                    duration: 1,
                                    scrollTo: event.currentTarget.getAttribute('href')
                                })
                            })
                        })

                        ScrollTrigger.create({
                            trigger: this.$refs.toolsNavWrap,
                            endTrigger: '#tools',
                            pin: true,
                            start: 'bottom bottom',
                            end: 'bottom bottom',
                            pinSpacing: false,
                            onEnter: () => {
                                gsap.to(this.$refs.toolsNav, {
                                    opacity: 1
                                });
                            },
                            onLeave: () => {
                                gsap.to(this.$refs.toolsNav, {
                                    opacity: 0
                                });
                            },
                            onEnterBack: () => {
                                gsap.to(this.$refs.toolsNav, {
                                    opacity: 1
                                });
                            },
                            onLeaveBack: () => {
                                gsap.to(this.$refs.toolsNav, {
                                    opacity: 0
                                });
                            },
                        })
                    },
                    pinItems() {
                        this.toolCards.forEach((item, index) => {
                            const progressBar = this.$refs[`itemProgressBar_${index}`];
                            const progressBarInner = progressBar.querySelector('span');

                            ScrollTrigger.create({
                                trigger: item,
                                animation: gsap.to(progressBarInner, {
                                    scaleX: 1
                                }),
                                scrub: true,
                                start: `top+=${10 * index} bottom`,
                                end: `bottom bottom`
                            })
                        });
                    },
                    pinCards() {
                        this.toolCards.forEach((card, index) => {
                            const scaleAmount = 0.85 + (0.15 * index / (this.toolCards.length - 1));

                            ScrollTrigger.create({
                                trigger: card,
                                endTrigger: '#tools',
                                animation: gsap.to(card, {
                                    scale: scaleAmount
                                }),
                                scrub: true,
                                pin: true,
                                pinSpacing: false,
                                start: `top-=${10 * index} top+=100`,
                                end: `bottom-=${10 * index} bottom+=${(10 * index)+100}`,
                                onEnter() {
                                    card.style.willChange = 'transform';
                                },
                                onLeave() {
                                    card.style.willChange = '';
                                },
                                onEnterBack() {
                                    card.style.willChange = 'transform';
                                },
                                onLeaveBack() {
                                    card.style.willChange = '';
                                },
                            })
                        });
                    }
                }))
            })
        })();
    </script>
@endpush
