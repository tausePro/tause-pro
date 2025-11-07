<div x-data="aiUrlToVideoData">
    {{-- Add new video card --}}
    <x-card
        class:body="lg:p-16 p-9"
        class="max-w-[353px] text-center"
    >
        <figure class="mx-auto mb-6 inline-grid size-40 place-items-center rounded-full bg-heading-foreground/[3%]">
            <img
                class="h-[127px] w-[127px]"
                src="{{ asset('images/ai-influencer/video-create.png') }}"
                alt="Generate new video"
            >
        </figure>
        <p class="mx-auto mb-6 max-w-[370px] font-heading text-xl font-semibold leading-[1.3em] text-heading-foreground">
            @lang('Generate an ad video using product URL or uploaded assets.')
        </p>
        <x-button
            variant="ghost-shadow"
            href="#"
            @click.prevent="toggleUrlToVideoWindow()"
        >
            <x-tabler-plus class="size-4" />
            @lang('Generate New')
        </x-button>
    </x-card>

    @include('url-to-video::create-video.create-video-window')
</div>

@push('script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('aiUrlToVideoData', () => ({
                // use for refresh the data in url to video window
                createVideoWindowKey: 1,
                // status of url to video window
                openUrlToVideoWindow: false,
                init() {
                    Alpine.store('aiUrlToVideoData', this);
                },
                // toggle url to video window, open or close
                toggleUrlToVideoWindow(open = true) {
                    this.openUrlToVideoWindow = open;
                    if (open) {
                        this.createVideoWindowKey++;
                    }
                    Alpine.store('aiInflucencerData').toggleWindow(open);
                }
            }));
        });
    </script>
@endpush
