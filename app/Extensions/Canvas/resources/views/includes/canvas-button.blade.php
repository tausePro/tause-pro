<div class="pointer-events-auto">
    <x-button
        class="lqd-chat-canvas-btn size-auto shrink-0 border-heading-foreground/5 text-heading-foreground max-md:bg-transparent max-md:hover:bg-transparent max-md:hover:shadow-none md:size-10 md:border md:hover:border-primary md:hover:bg-primary md:hover:text-primary-foreground lg:size-11 [&.active]:border-primary [&.active]:bg-primary [&.active]:text-primary-foreground"
        id="{{ $category->slug == 'ai_vision' && $app_is_demo ? '' : 'create_canvas_button' }}"
        variant="none"
        x-data="canvasButtonStatus"
        ::class="{ 'active': status }"
        size="none"
        tag="button"
        title="{{ __('Create in Canvas') }}"
        onclick="{!! $category->slug == 'ai_vision' && $app_is_demo ? 'return toastr.info(\'{{ __('This feature is disabled in Demo version.') }}\')' : '' !!}"
    >
        <svg
            width="21"
            height="18"
            viewBox="0 0 22 20"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            stroke="currentColor"
            stroke-width="1.85"
        >
            <path
                d="M4.875 14.125H11.875M15.75 15V5M4.875 10.0625H11.875M4.875 6.0625H11.875M2.875 19C2.325 19 1.85417 18.8042 1.4625 18.4125C1.07083 18.0208 0.875 17.55 0.875 17V3C0.875 2.45 1.07083 1.97917 1.4625 1.5875C1.85417 1.19583 2.325 1 2.875 1H18.875C19.425 1 19.8958 1.19583 20.2875 1.5875C20.6792 1.97917 20.875 2.45 20.875 3V17C20.875 17.55 20.6792 18.0208 20.2875 18.4125C19.8958 18.8042 19.425 19 18.875 19H2.875Z"
            />
        </svg>

        <span class="md:hidden">
            {{ __('Create in Canvas') }}
        </span>
    </x-button>
</div>

@push('script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('canvasButtonStatus', () => ({
                status: false,
                // add event listener
                addEventListener() {
                    const canvasBtn = document.getElementById('create_canvas_button');
                    if (canvasBtn) {
                        canvasBtn.addEventListener('click', () => {
                            this.toggle();
                        });
                    }
                },
                toggle() {
                    this.status = !this.status;
                },
                init() {
                    Alpine.store('canvasButtonStatus', this);
                    this.addEventListener();
                }
            }));
        });
    </script>
@endpush
