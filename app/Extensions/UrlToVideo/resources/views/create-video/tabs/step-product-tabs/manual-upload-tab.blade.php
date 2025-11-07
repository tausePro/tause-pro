<div
    class="col-start-1 col-end-1 row-start-1 row-end-1"
    x-show="activeTab === '{{ \App\Enums\AiInfluencer\ProductTabEnum::MANUAL_UPLOAD->value }}'"
    x-transition.opacity.150ms
    x-data="fileUploadData"
    x-init="$watch('createVideoWindowKey', () => initialize())"
>
    <form
        class="mb-4 flex w-full flex-col gap-5"
        @submit.prevent="() => uploadFiles()"
    >
        @csrf

        <label
            class="group flex min-h-40 w-full cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-foreground/10 bg-background text-center text-[12px] transition-colors hover:bg-background/80"
            for="upload-file"
        >
            <div class="flex flex-col items-center justify-center py-5">
                <x-tabler-circle-plus
                    class="mb-3.5 size-11"
                    stroke-width="1"
                    x-show="!uploading"
                />
                <x-tabler-refresh
                    class="mb-3.5 size-11 animate-spin"
                    stroke-width="1"
                    x-show="uploading"
                />

                <div class="mb-2 text-center text-sm font-medium text-foreground">
                    <p
                        class="mb-0 text-foreground/30"
                        x-show="!fileSelected"
                    >
                        {{ __('Drag and drop product images or videos') }}
                    </p>
                    <span
                        x-text="fileSelected ? '{{ __('Files are selected! Click below button to upload files.') }}' : '{{ __('click here to browse your files.') }}'"
                    ></span>
                </div>

                <p
                    class="mb-0 text-4xs font-medium leading-6"
                    x-text="!uploading ? '{{ __('Max File Size: 5mb, You can upload multiple files.') }}' : '{{ __('UPLOADING...') }}'"
                >
                    {{ __('Upload a File (Max: 25Mb)') }}
                </p>
            </div>

            {{-- @todo check accepts are set correctly --}}
            <input
                class="hidden"
                id="upload-file"
                name="files[]"
                type="file"
                multiple
                :accept="accept"
                @change="$event.target?.files[0] ? setFileSelected() : setFileSelected(false)"
            />
        </label>

        <x-button
            class="group w-full"
            variant="success"
            type="submit"
            ::disabled="uploading"
        >
            <x-tabler-plus class="size-4" />
            {{ 'Upload' }}
        </x-button>
    </form>

    <div class="mt-9">
        @include('url-to-video::create-video.tabs.step-product-tabs.product-detail')
    </div>
</div>
