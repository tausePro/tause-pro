<div
    class="col-start-1 col-end-1 row-start-1 row-end-1"
    x-show="activeTab === '{{ \App\Enums\AiInfluencer\ProductTabEnum::URL->value }}'"
    x-transition.opacity.150ms
>
    <form
        class="flex flex-col gap-5"
        @submit.prevent="nextStep()"
    >
        @csrf
        <div class="flex h-7 w-full items-center justify-between">
            <h4 class="m-0 flex items-center gap-1 text-sm font-medium">
                {{ __('Product URL') }}
                <x-info-tooltip text="{{ __('Product URL to analyze the information.') }}" />
            </h4>
            @if (setting('default_ai_influencer_tool', 'creatify') == 'creatify')
                <div class="flex items-center gap-2">
                    <label
                        class="cursor-pointer text-sm font-medium text-heading-foreground"
                        for="submit-form"
                        x-text="submitting ? '{{ __('Analyzing') }}' : '{{ __('Analyze') }}'"
                    >
                    </label>

                    <x-button
                        class="group size-5 text-primary"
                        id="submit-form"
                        variant="link"
                        size="none"
                        type="submit"
                        ::disabled="submitting"
                    >
                        <x-tabler-refresh
                            class="size-5"
                            stroke-width="2.2"
                            ::class="{ 'animate-spin': submitting }"
                        />
                        <span class="sr-only">
                            @lang('Analyzing')
                        </span>
                    </x-button>
                </div>
            @endif
        </div>

        <div class="relative">
            <x-forms.input
                id="url"
                size="lg"
                name="url"
                x-model="formData.url"
                placeholder="https://example.com"
            />
        </div>
    </form>

    <div class="my-9 flex w-full items-center justify-between">
        @foreach (\App\Enums\AiInfluencer\BrandEnum::cases() as $brand)
            <span>
                {!! $brand->image() !!}
            </span>
        @endforeach
    </div>
</div>
