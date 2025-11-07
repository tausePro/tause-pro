<div
    class="max-h-96 w-full overflow-auto py-2"
    x-show="activeTab == '{{ \App\Enums\AiInfluencer\CompositionEditTabEnum::AVATAR->value }}'"
>
    <div class="flex flex-wrap gap-y-6 rounded-xl">
        <template x-for="avatar in resources.avatars">
            <div
                class="flex w-1/2 flex-col items-center gap-3.5 md:w-1/3 lg:w-1/5"
                x-show="avatar.creator_name.includes(searchKey)"
            >
                <div
                    class="flex cursor-pointer rounded-lg p-2.5 outline"
                    :class="avatar.id == selectedResources.avatarId ? ' outline-[3px] outline-accent' :
                        'outline-1 outline-border'"
                    @click.prevent="selectedResources.avatarId = avatar.id"
                >
                    <div class="relative flex h-40 w-28 overflow-hidden rounded-xl">
                        <img
                            class="h-full w-full object-cover"
                            :src="avatar.preview_image_1_1"
                            alt=""
                        >
                        <span
                            class="absolute end-1 top-1.5 flex items-center justify-center rounded-full bg-background/40 p-2 shadow-lg"
                            x-show="avatar.id == selectedResources.avatarId"
                        >
                            <x-tabler-check class="size-4" />
                        </span>
                    </div>
                </div>
                <span
                    class="text-sm font-semibold text-heading-foreground"
                    x-text="avatar.creator_name"
                ></span>
            </div>
        </template>
    </div>
</div>
