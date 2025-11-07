{{-- Step 2 - Video detail setting --}}
<div
    class="col-start-1 col-end-1 row-start-1 row-end-1 transition-all lg:w-[430px]"
    data-step="2"
    x-show="currentStep === 2"
    x-transition:enter-start="opacity-0 -translate-x-3"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-3"
>
    <h2 class="mb-3.5">
        @lang('Choose Video Details')
    </h2>
    <p class="mb-3 text-xs/5 opacity-60 lg:max-w-[360px]">
        @lang('Share more about your preferences so we can customize your video to fit your needs.')
    </p>

    <div class="mt-9 flex flex-col gap-9">
        {{-- Lanugage dropdown --}}
        <div>
            <x-forms.input
                class:label="text-heading-foreground"
                label="{{ __('Language') }}"
                tooltip="{{ __('Select language for your video') }}"
                name="language"
                size="lg"
                type="select"
                x-model="videoConfigData.language"
            >
                @php
                    $languages =
                        setting('default_ai_influencer_tool', 'creatify') == 'creatify'
                            ? \App\Packages\Creatify\Enums\Language::cases()
                            : \App\Packages\Topview\Enums\Language::cases();
                @endphp
                @foreach ($languages as $language)
                    <option
                        value="{{ $language->value }}"
                        {{ $language->value == 'en' ? 'selected' : '' }}
                    >
                        {{ $language->label() }}
                    </option>
                @endforeach
            </x-forms.input>
        </div>

        {{-- Duration dropdown --}}
        <div>
            <x-forms.input
                class:label="text-heading-foreground"
                label="{{ __('Duration') }}"
                tooltip="{{ __('Select your video duration') }}"
                name="video_length"
                x-model="{{ setting('default_ai_influencer_tool', 'creatify') == 'creatify' ? 'videoConfigData.video_length' : 'videoConfigData.videoLengthType' }}"
                size="lg"
                type="select"
            >
                @php
                    $durations =
                        setting('default_ai_influencer_tool', 'creatify') == 'creatify'
                            ? \App\Packages\Creatify\Enums\VideoLength::cases()
                            : \App\Packages\Topview\Enums\VideoLength::cases();
                @endphp
                @foreach ($durations as $duration)
                    <option
                        value="{{ $duration->value }}"
                        {{ $loop->first ? 'selected' : '' }}
                    >
                        {{ $duration->label() }}
                    </option>
                @endforeach
            </x-forms.input>
        </div>

        {{-- Aspect Ratio dropdown --}}
        <div>
            <x-forms.input
                class:label="text-heading-foreground"
                label="{{ __('Aspect Ratio') }}"
                tooltip="{{ __('Select your video Aspect Ratio') }}"
                name="aspect_ratio"
                size="lg"
                type="select"
                x-model="{{ setting('default_ai_influencer_tool', 'creatify') == 'creatify' ? 'videoConfigData.aspect_ratio' : 'videoConfigData.aspectRatio' }}"
            >
                @php
                    $ratios =
                        setting('default_ai_influencer_tool', 'creatify') == 'creatify'
                            ? \App\Packages\Creatify\Enums\AspectRatio::cases()
                            : \App\Packages\Topview\Enums\AspectRatio::cases();
                @endphp
                @foreach ($ratios as $ratio)
                    <option
                        value="{{ $ratio->value }}"
                        {{ $loop->first ? 'selected' : '' }}
                    >
                        {{ $ratio->label() }}
                    </option>
                @endforeach
            </x-forms.input>
        </div>
    </div>

    <x-button
        class="mt-9 w-full"
        variant="secondary"
        @click.prevent="changeStep('next')"
        size="lg"
        type="button"
    >
        @lang('Next')
    </x-button>
</div>
