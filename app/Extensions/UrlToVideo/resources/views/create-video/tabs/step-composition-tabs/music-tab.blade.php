<div
    class="max-h-96 w-full overflow-auto p-2"
    x-data="compositionMusicData"
    x-init="$watch('createVideoWindowKey', () => initialize())"
    x-show="activeTab == '{{ \App\Enums\AiInfluencer\CompositionEditTabEnum::MUSIC->value }}'"
>
    <div class="grid grid-cols-1 gap-x-6 gap-y-4 rounded-xl md:grid-cols-2">
        <template x-for="music in resources.musics">
            <div
                class="col-span-1 flex cursor-pointer items-center justify-between rounded-lg px-5 py-6"
                :class="music.id == selectedResources.musicId ? 'outline-[3px] outline-accent outline' : ''"
                @click.prevent="selectMusic(music.id)"
                x-show="music.name.includes(searchKey)"
            >
                <audio
                    class="hidden"
                    :id="'composition-music-' + music.id"
                    :src="music.url"
                    loop
                    x-init="music.playing = false"
                ></audio>
                <div class="flex max-w-44 flex-col gap-1">
                    <span
                        class="text-lg font-semibold text-heading-foreground"
                        x-text="music.name"
                    ></span>
                    <span
                        class="text-2xs text-foreground"
                        x-text="getDurationForHuman(music.duration)"
                    ></span>
                </div>
                <div class="flex">
                    <span
                        class="cursor-pointer gap-2 rounded-full border-2 border-accent"
                        @click.prevent="playMusic(music)"
                    >
                        <x-tabler-caret-right-filled
                            class="size-10 text-accent"
                            x-show="!music.playing"
                        />
                        <x-tabler-player-pause-filled
                            class="size-10 text-accent"
                            x-show="music.playing"
                        />
                    </span>
                </div>
            </div>
        </template>
    </div>
</div>
