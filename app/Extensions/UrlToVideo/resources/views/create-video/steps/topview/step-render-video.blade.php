{{-- Step 5 - Render Video --}}
<div
    class="col-start-1 col-end-1 row-start-1 row-end-1 transition-all lg:max-w-[786px]"
    data-step="4"
    x-data="previewVideoData"
    x-show="currentStep === 4"
    x-init="$watch('createVideoWindowKey', () => initialize())"
    x-transition:enter-start="opacity-0 -translate-x-3"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-3"
>
    <h2 class="mb-3.5">
        @lang('Choose an Preview Video')
    </h2>
    <p class="mb-3 text-xs/5 opacity-60">
        @lang('Choose an preview video that you want to render.')
    </p>

    <div class="mt-9 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
        <template x-for="preview in previews">
            <div
                class="flex cursor-pointer flex-col overflow-hidden rounded-xl"
                @click.prevent="selectPreview(preview.scriptId)"
            >
                <div class="flex h-full w-full justify-center">
                    <div
                        class="relative flex h-[213px] w-full justify-center bg-foreground/80"
                        x-init="preview.playing = false"
                    >
                        <video
                            class="h-full object-cover"
                            x-show="preview.loaded"
                            :id="'preview-video-item-' + preview.scriptId"
                            :src="preview.videoUrl"
                            loop
                            @loadedmetadata="preview.loaded = $event.target.duration"
                        ></video>
                        <div
                            class="flex h-full w-full flex-col items-center justify-center gap-2.5"
                            x-show="!preview.loaded"
                        >
                            <x-tabler-loader-2
                                class="text-gradient size-6 animate-spin"
                                stroke="url(#icon-gradient)"
                            />
                            <span
                                class="bg-gradient-to-r from-gradient-from via-gradient-via to-gradient-to bg-clip-text text-sm font-semibold text-transparent"
                            >{{ __('Loading') }}</span>
                        </div>
                        <span
                            class="absolute end-1 top-1.5 flex items-center justify-center rounded-full bg-background/40 p-2 shadow-lg"
                            x-show="preview.scriptId === scriptId"
                        >
                            <x-tabler-check class="size-4" />
                        </span>
                    </div>
                </div>
                <div class="flex w-full flex-shrink-0 flex-col items-center justify-center gap-2 py-5">
                    <div class="flex max-w-56 flex-col gap-2">
                        <span
                            class="text-center text-sm font-semibold text-heading-foreground"
                            x-text="preview.title"
                        ></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="flex size-9 cursor-pointer items-center justify-center"
                            :class="!preview.loaded ? 'pointer-events-none' : ''"
                            @click="playVideo(preview)"
                        >
                            <x-tabler-player-play
                                class="size-6"
                                x-show="!preview.playing"
                            />
                            <x-tabler-player-pause
                                class="size-6"
                                x-show="preview.playing"
                            />
                        </span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <x-button
        class="mt-9 w-full"
        variant="secondary"
        @click.prevent="renderVideo()"
        size="lg"
        ::disabled="fetching"
        type="button"
    >
        @lang('Render Video')
    </x-button>
</div>
