{{-- Step 4 - Composition --}}
<div
    class="col-start-1 col-end-1 row-start-1 row-end-1 transition-all lg:max-w-[786px]"
    x-data="videoCompositionData"
    x-show="{{ setting('default_ai_influencer_tool', 'creatify') == 'creatify' ? 'currentStep === 4' : 'currentStep === 3' }}"
    x-init="$watch('createVideoWindowKey', () => initialize())"
    x-transition:enter-start="opacity-0 -translate-x-3"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-3"
>
    <h2 class="mb-3.5">
        @lang('Composition')
    </h2>
    <p class="mb-3 text-xs/5 opacity-60">
        @lang('Select or edit your video composition.')
    </p>

    @if (setting('default_ai_influencer_tool', 'creatify') == 'creatify')
        <div class="mt-9 flex flex-col gap-4">
            <div class="flex w-full items-center justify-between rounded-md border px-2.5 py-3">
                <div class="flex gap-3">
                    <span class="text-xs font-medium text-heading-foreground">@lang('Enable Music')</span>
                    <x-info-tooltip text="{{ __('Enable music for video') }}" />
                </div>
                <x-forms.input
                    class="checked:bg-primary"
                    type="checkbox"
                    switcher
                    x-model="videoConfigData.no_background_music"
                    x-init="videoConfigData.no_background_music"
                >
                </x-forms.input>
            </div>
            <div class="flex w-full items-center justify-between rounded-md border px-2.5 py-3">
                <div class="flex gap-3">
                    <span class="text-xs font-medium text-heading-foreground">@lang('Enable Captions')</span>
                    <x-info-tooltip text="{{ __('Enable Captions for video') }}" />
                </div>
                <x-forms.input
                    class="checked:bg-primary"
                    type="checkbox"
                    switcher
                    x-model="videoConfigData.no_caption"
                >
                </x-forms.input>
            </div>
        </div>
    @endif

    <div class="lqd-ext-chatbot-training mt-9 flex flex-col justify-center gap-9">
        <div class="flex w-full items-center justify-between gap-2 gap-y-2 max-md:flex-col md:gap-6 lg:gap-9">
            <div class="group flex flex-wrap items-center gap-2 md:gap-6">
                @foreach (\App\Enums\AiInfluencer\CompositionEditTabEnum::cases() as $tab)
                    @if (setting('default_ai_influencer_tool', 'creatify') == 'topview' &&
                            $tab == \App\Enums\AiInfluencer\CompositionEditTabEnum::MUSIC)
                        @continue
                    @endif
                    <div
                        class="flex cursor-pointer items-center gap-1 rounded-2xl px-2.5 py-2 hover:bg-secondary dark:hover:bg-primary md:gap-[5px] [&.active]:bg-secondary dark:[&.active]:bg-primary"
                        @click.prevent="setActiveTab('{{ $tab->value }}')"
                        :class="'{{ $tab->value }}' == activeTab ? 'active' : ''"
                    >
                        <x-dynamic-component
                            class="size-4 text-heading-foreground md:size-5"
                            stroke-width="1.5"
                            :component="$tab->svg()"
                        />
                        <span class="text-2xs font-medium text-foreground md:text-sm">
                            {{ __($tab->label()) }}
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="relative">
                <x-tabler-search
                    class="pointer-events-none absolute start-3 top-1/2 z-10 size-4 -translate-y-1/2 opacity-75"
                    stroke-width="1.5"
                />
                <x-forms.input
                    class="border-none bg-heading-foreground/5 ps-10 transition-colors max-lg:rounded-md"
                    id="serach-resources"
                    container-class="peer"
                    type="text"
                    x-model="searchKey"
                    placeholder="{{ __('Search') }}"
                />
            </div>
        </div>
        <div class="lqd-ext-chatbot-training-content grid">
            @if (setting('default_ai_influencer_tool', 'creatify') == 'creatify')
                @include('url-to-video::create-video.tabs.step-composition-tabs.avatar-tab')
                @include('url-to-video::create-video.tabs.step-composition-tabs.caption-tab')
                @include('url-to-video::create-video.tabs.step-composition-tabs.voice-tab')
                @include('url-to-video::create-video.tabs.step-composition-tabs.music-tab')
            @else
                @include('url-to-video::create-video.tabs.step-composition-tabs.topview.avatar-tab')
                @include('url-to-video::create-video.tabs.step-composition-tabs.topview.caption-tab')
                @include('url-to-video::create-video.tabs.step-composition-tabs.topview.voice-tab')
            @endif
        </div>
    </div>

    <x-button
        class="mt-9 w-full"
        variant="secondary"
        @click.prevent="nextStep()"
        size="lg"
        type="button"
        ::disabled="fetching"
    >
        @lang('Preview videos')
    </x-button>
</div>
