<div
    class="max-h-96 w-full overflow-auto p-2"
    x-show="activeTab == '{{ \App\Enums\AiInfluencer\CompositionEditTabEnum::CAPTIONS->value }}'"
>
    <div class="grid grid-cols-1 gap-x-6 gap-y-4 rounded-xl md:grid-cols-2 lg:grid-cols-3">
        <template x-for="caption in resources.captions">
            <div
                class="col-span-1 flex h-16 cursor-pointer items-center justify-between overflow-hidden rounded-2xl"
                :class="selectedResources.captionId == caption.captionId ? 'outline-[3px] outline-accent outline' : ''"
                @click.prevent="selectedResources.captionId = caption.captionId"
            >
                <img
                    class="h-full w-full object-cover"
                    :src="caption.thumbnail"
                    alt=""
                >
            </div>
        </template>
    </div>
</div>
