{{-- Step 1 - Product --}}
<div
    class="col-start-1 col-end-1 row-start-1 row-end-1 transition-all lg:w-[430px]"
    data-step="1"
    x-data="productBasicInfo"
    x-show="currentStep === 1"
    x-init="$watch('createVideoWindowKey', () => initialize())"
    x-transition:enter-start="opacity-0 -translate-x-3"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-3"
>
    <h2 class="mb-3.5">
        @lang('Add Product Information')
    </h2>
    <p class="mb-3 text-xs/5 opacity-60 lg:max-w-[360px]">
        @lang('Enter your product URL or provide details about your product or service')
    </p>
    <div class="lqd-ext-chatbot-training mt-9 flex flex-col justify-center gap-9">
        <ul
            class="flex w-full flex-wrap justify-between gap-3 rounded-3xl bg-foreground/5 p-1 text-xs font-medium sm:flex-nowrap sm:rounded-full">
            @foreach (\App\Enums\AiInfluencer\ProductTabEnum::cases() as $tab)
                <li class="w-full grow">
                    <button
                        @class([
                            'px-6 py-2.5 grow leading-tight rounded-full transition-all hover:bg-background/80 [&.lqd-is-active]:bg-background [&.lqd-is-active]:shadow-[0_2px_12px_hsl(0_0%_0%/10%)] w-full',
                            'lqd-is-active' => $loop->first,
                        ])
                        @click="setActiveTab('{{ $tab->value }}')"
                        :class="{ 'lqd-is-active': activeTab === '{{ $tab->value }}' }"
                        :disabled="submitting || uploading"
                    >
                        @lang($tab->label())
                    </button>
                </li>
            @endforeach
        </ul>
        <div class="lqd-ext-chatbot-training-content grid">
            @include('url-to-video::create-video.tabs.step-product-tabs.url-tab')
            @include('url-to-video::create-video.tabs.step-product-tabs.manual-upload-tab')
        </div>
    </div>

    <x-button
        class="mt-9 w-full"
        variant="secondary"
        @click.prevent="nextStep()"
        size="lg"
        type="button"
        ::disabled="submitting || uploading"
    >
        @lang('Next')
    </x-button>
</div>
