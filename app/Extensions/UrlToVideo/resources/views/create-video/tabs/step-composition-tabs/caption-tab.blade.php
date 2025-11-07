<div
    class="max-h-96 w-full overflow-auto p-2"
    x-show="activeTab == '{{ \App\Enums\AiInfluencer\CompositionEditTabEnum::CAPTIONS->value }}'"
>
    <div class="grid grid-cols-1 gap-x-6 gap-y-4 rounded-xl md:grid-cols-2 lg:grid-cols-3">
        @foreach (\App\Packages\Creatify\Enums\CaptionStyle::cases() as $caption)
            <div
                class="col-span-1 flex cursor-pointer items-center justify-between overflow-hidden rounded-2xl"
                :class="selectedResources.captionId == '{{ $caption->value }}' ? 'outline-[3px] outline-accent outline' : ''"
                @click.prevent="selectedResources.captionId = '{{ $caption->value }}'"
            >
                <span
                    class="text-center"
                    style="{{ $caption->style() }}"
                >{{ $caption->label() }}</span>
            </div>
        @endforeach
    </div>
</div>
