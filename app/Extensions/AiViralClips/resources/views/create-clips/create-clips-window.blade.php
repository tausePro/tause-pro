{{-- Edit Window --}}
<template x-teleport="body">
    <div
        class="lqd-chatbot-edit-window fixed bottom-0 end-0 start-0 top-0 z-[100] overflow-y-auto bg-background lg:start-[--navbar-width]"
        x-data="createAiClipsData"
        x-show="{{ Route::currentRouteNamed('dashboard.user.viral-clips.index') ? 'true' : 'openAiClipsWindow' }}"
        x-init="$nextTick(() => { $watch('aiClipsWindowKey', () => initialize()) })"
    >
        {{-- Edit Window Header --}}
        <div
            class="lqd-chatbot-edit-window-header sticky top-0 z-2 flex flex-wrap items-center justify-between border-b bg-background/60 px-3 py-6 backdrop-blur-lg backdrop-saturate-150">
            <div class="flex grow items-center gap-6 pb-5 lg:hidden">
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
            <div class="flex flex-col items-start gap-3 py-3 lg:h-[--header-height] lg:px-12">
                <h1 class="lqd-titlebar-title m-0">
                    {{ __('AI Clips') }}
                </h1>
                <span class="text-2xs font-medium text-foreground">
                    {{ __('Generate viral clips from long video content.') }}
                </span>
            </div>
            <x-button
                href="#"
                @click.prevent="aiClipsWindowKey++"
            >
                <x-tabler-plus class="size-4" />
                @lang('Create Video')
            </x-button>
        </div>

        <div class="lqd-chatbot-edit-window-content mt-3 py-8 max-lg:px-3">
            <div class="mx-auto flex max-w-[786px] flex-col flex-wrap justify-center gap-y-7 lg:w-[430px]">
                @if (setting('default_ai_clip_tool', 'vizard') == 'klap')
                    @include('ai-viral-clips::create-clips.steps.generate-clips')
                    @include('ai-viral-clips::create-clips.steps.preview-clips')
                @else
                    @include('ai-viral-clips::create-clips.steps.vizard.generate-clips')
                @endif
            </div>
        </div>
    </div>
</template>

@if (setting('default_ai_clip_tool', 'vizard') == 'klap')
    @include('ai-viral-clips::scripts.klap')
@else
    @include('ai-viral-clips::scripts.vizard')
@endif
