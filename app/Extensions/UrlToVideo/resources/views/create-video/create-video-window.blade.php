{{-- Edit Window --}}
<template x-teleport="body">
    <div
        class="lqd-chatbot-edit-window fixed bottom-0 end-0 start-0 top-0 z-[100] overflow-y-auto bg-background lg:start-[--navbar-width]"
        x-show="{{ Route::currentRouteNamed('dashboard.user.url-to-video.index') ? 'true' : 'openUrlToVideoWindow' }}"
        x-data="createVideoData"
        x-init="$watch('createVideoWindowKey', () => initialize())"
    >
        {{-- Edit Window Header --}}
        <div class="lqd-chatbot-edit-window-header sticky top-0 z-2 border-b bg-background/60 backdrop-blur-lg backdrop-saturate-150">
            <div class="flex flex-wrap items-center gap-4 px-3 py-3 lg:h-[--header-height] lg:px-12">
                {{-- Header Steps --}}
                <div class="lqd-steps hidden w-full flex-col gap-1 lg:flex">
                    <div class="lqd-steps-steps flex items-center justify-between gap-1 lg:gap-3">
                        <template x-for="step, index in steps">
                            <button
                                class="lqd-step group/step flex gap-3 rounded p-2 text-3xs font-semibold capitalize text-heading-foreground transition-colors hover:bg-heading-foreground/5 disabled:pointer-events-none disabled:opacity-50 lg:min-w-32"
                                @click.prevent="changeStep(index + 1)"
                                type="button"
                            >
                                <span
                                    class="inline-grid size-[21px] place-items-center rounded-md border border-heading-foreground/10 transition-colors group-hover/step:border-heading-foreground group-hover/step:bg-heading-foreground group-hover/step:text-heading-background"
                                >
                                    <span
                                        class="col-start-1 col-end-1 row-start-1 row-end-1"
                                        x-text="index + 1"
                                    >
                                    </span>
                                </span>
                                <span x-text="step"></span>
                            </button>
                        </template>
                    </div>
                    <div class="lqd-step-progress relative h-[3px] w-full overflow-hidden rounded-lg bg-heading-foreground/5">
                        <div
                            class="lqd-step-progress-bar absolute start-0 top-0 h-full w-0 rounded-full bg-gradient-to-r from-gradient-from to-gradient-to transition-all"
                            :style="{ width: currentStep * stepPercent + '%' }"
                        ></div>
                    </div>
                </div>
                <div class="flex grow items-center gap-6 lg:hidden">
                    <div class="inline-grid size-[36px] place-content-center overflow-hidden transition-all duration-300">
                        <x-button
                            class="size-[34px] hover:translate-y-0"
                            variant="outline"
                            hover-variant="primary"
                            size="none"
                            title="{{ __('Dashboard') }}"
                            href="{{ route('dashboard.user.index') }}"
                            {{-- ::class="{ 'hidden': currentView !== 'home' }" --}}
                        >
                            <x-tabler-chevron-left class="size-4" />
                        </x-button>
                    </div>

                    <x-header-logo />
                </div>
            </div>
        </div>

        <div class="lqd-chatbot-edit-window-content py-8 lg:py-0">
            <div class="container flex flex-wrap justify-center gap-y-5">
                <div class="lqd-chatbot-edit-window-options grid w-full lg:min-h-[calc(100vh-var(--header-height))] lg:w-auto lg:min-w-[430px] lg:py-16">

                    @include('url-to-video::create-video.steps.step-product')
                    @include('url-to-video::create-video.steps.step-video-detail')
                    @include('url-to-video::create-video.steps.step-composition')

                    @if (setting('default_ai_influencer_tool', 'creatify') == 'creatify')
                        @include('url-to-video::create-video.steps.step-script')
                        @include('url-to-video::create-video.steps.step-render-video')
                    @else
                        @include('url-to-video::create-video.steps.topview.step-render-video')
                    @endif
                </div>
            </div>
        </div>
    </div>
</template>

@if (setting('default_ai_influencer_tool', 'creatify') == 'creatify')
    @include('url-to-video::scripts.creatify-script')
@elseif (setting('default_ai_influencer_tool', 'creatify') == 'topview')
    @include('url-to-video::scripts.topview-script')
@endif
