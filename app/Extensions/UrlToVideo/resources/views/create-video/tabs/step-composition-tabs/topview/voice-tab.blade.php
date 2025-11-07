<div
    class="max-h-96 w-full overflow-auto p-2"
    x-data="compositionVoiceData"
    x-init="$watch('createVideoWindowKey', () => initialize())"
    x-show="activeTab == '{{ \App\Enums\AiInfluencer\CompositionEditTabEnum::VOICE->value }}'"
>
    <div
        class="grid grid-cols-1 gap-x-6 gap-y-4 rounded-xl md:grid-cols-2"
        @click.outside="pausePlayingVoice()"
    >
        <template x-for="voice in resources.voices">
            <div
                class="col-span-1 flex cursor-pointer items-center justify-between rounded-lg px-5 py-6"
                :class="voice.voiceId == selectedResources.voiceId ? 'outline-[3px] outline-accent outline' : ''"
                @click.prevent="selectVoice(voice.voiceId)"
                x-show="voice.voiceName.includes(searchKey)"
            >
                <audio
                    class="hidden"
                    :id="'composition-voice-' + voice.voiceId"
                    :src="voice.demoAudioUrl"
                    loop
                    x-init="voice.playing = false"
                ></audio>
                <div class="flex max-w-44 flex-col gap-1">
                    <span
                        class="text-lg font-semibold text-heading-foreground"
                        x-text="voice.voiceName"
                    ></span>
                </div>
                <div class="flex">
                    <span
                        class="cursor-pointer gap-2 rounded-full border-2 border-accent"
                        @click.prevent="playVoice(voice)"
                    >
                        <x-tabler-caret-right-filled
                            class="size-10 text-accent"
                            x-show="!voice.playing"
                        />
                        <x-tabler-player-pause-filled
                            class="size-10 text-accent"
                            x-show="voice.playing"
                        />
                    </span>
                </div>
            </div>
        </template>
    </div>
</div>
