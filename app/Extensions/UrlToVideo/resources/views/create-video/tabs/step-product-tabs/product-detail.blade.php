<div
    class="flex flex-col gap-9"
    x-data="assetsDetail"
    x-show="assets.length > 0"
    x-init="$watch('createVideoWindowKey', () => initialize())"
>
    <div class="flex flex-col gap-3">
        <div class="flex w-full justify-between">
            <span class="text-sm font-medium text-heading-foreground">
                {{ __('Choose Assets') }}
            </span>
            <span
                class="cursor-pointer text-sm font-medium text-heading-foreground"
                @click.prevent="toggleSelect"
                x-text="isSelectedAll ? '{{ __('Deselect All') }}' : '{{ __('Select All') }}'"
            >
            </span>
        </div>
        <div
            class="grid max-h-96 grid-cols-1 gap-x-5 gap-y-4 overflow-y-auto rounded-lg border p-2.5 md:grid-cols-2 lg:grid-cols-3">
            <template x-for="asset in assets">
                <div
                    class="relative col-span-1 h-[120px] cursor-pointer overflow-hidden rounded-lg bg-foreground/20"
                    @click.prevent="selectAsset(asset.id)"
                >
                    <template x-if="asset.fileType == 'video'">
                        <video
                            class="h-full w-full object-cover"
                            :src="asset.fileUrl"
                            preload="metadata"
                            @loadedmetadata="asset.duration = $event.target.duration"
                        ></video>
                    </template>
                    <template x-if="asset.fileType == 'image'">
                        <img
                            class="h-full w-full object-cover"
                            :src="asset.fileUrl ?? asset.url"
                            alt=""
                        >
                    </template>
                    <span
                        class="absolute end-2 top-2 flex items-center justify-center rounded-full bg-background/70 p-2 shadow-lg"
                        x-show="asset.checked"
                    >
                        <x-tabler-check class="size-4" />
                    </span>
                    <template x-if="asset.fileType == 'video'">
                        <div class="absolute bottom-2 start-2 flex gap-1 rounded-md bg-background p-1">
                            <x-tabler-camera class="size-4" />
                            <span
                                class="text-4xs font-medium leading-[18px] text-heading-foreground"
                                x-text="timeConvert(asset.duration)"
                            ></span>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <div>
        <x-forms.input
            class:label="text-heading-foreground text-xs"
            label="{{ __('Product/Service Name') }}"
            placeholder="{{ __('MagicBot') }}"
            name="title"
            tooltip="Product/Service Name"
            size="lg"
            x-model="formData.title"
        ></x-forms.input>
    </div>
    <div>
        <x-forms.input
            class="min-h-32"
            class:container="flex flex-col gap-2"
            class:label="text-heading-foreground text-xs w-fit items-center"
            type="textarea"
            label="{{ __('Product/Service Information') }}"
            placeholder="{{ __('Describe the features and advantages your product or service') }}"
            name="description"
            tooltip="Product/Service Information"
            size="lg"
            x-model="formData.description"
        >
            <x-slot:label-extra>
                <x-button
                    class="absolute end-0 hidden -translate-y-1/2 hover:-translate-y-1/2"
                    variant="link"
                    hoverVariant="none"
                    @click.prevent="generateProductDescription"
                >
                    <img
                        class="shrink-0"
                        src="{{ custom_theme_url('assets/img/logo/logo-without-bg.svg') }}"
                        alt=""
                    >
                </x-button>
            </x-slot:label-extra>
        </x-forms.input>
    </div>

    @if (setting('default_ai_influencer_tool', 'creatify') == 'creatify')
        <div>
            <x-forms.input
                class="min-h-32"
                class:container="flex flex-col gap-2"
                class:label="text-heading-foreground text-xs w-fit items-center"
                type="textarea"
                label="{{ __('Target Audience') }}"
                placeholder="{{ __('Please provide details about their demographics, interests and pain-points.') }}"
                name="target_audience"
                tooltip="Target Audience"
                x-model="videoConfigData.target_audience"
                size="lg"
            >
                <x-slot:label-extra>
                    <x-button
                        class="absolute end-0 hidden -translate-y-1/2 hover:-translate-y-1/2"
                        variant="link"
                        hoverVariant="none"
                        @click.prevent="generateTargetAudience"
                    >
                        <img
                            class="shrink-0"
                            src="{{ custom_theme_url('assets/img/logo/logo-without-bg.svg') }}"
                            alt=""
                        >
                    </x-button>
                </x-slot:label-extra>
            </x-forms.input>
        </div>
    @endif
</div>
