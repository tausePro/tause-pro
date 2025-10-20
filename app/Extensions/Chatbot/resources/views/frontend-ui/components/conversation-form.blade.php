<form
    class="lqd-ext-chatbot-window-form relative min-h-full w-full"
    @submit.prevent="onSendMessage"
>
    <textarea
        class="min-h-full w-full resize-none border-none bg-transparent pe-14 ps-2 pt-4 text-base font-normal focus:shadow-none focus:outline-none"
        id="message"
        name="message"
        cols="30"
        rows="1"
        placeholder="{{ __('Message...') }}"
        @keydown.enter.prevent="onMessageFieldHitEnter"
        @input.throttle.50ms="$el.scrollTop = $el.scrollHeight; $refs.sendBtn.classList.toggle('active', $el.value.trim())"
        x-ref="message"
    ></textarea>

    <div class="pointer-events-none absolute end-2 top-[18px] flex gap-3">
        @if ($is_editor || (isset($chatbot) && $chatbot->is_attachment))
            <template x-if="@if ($is_editor) true @else activeConversationData?.connect_agent_at @endif">
                <div
                    class="pointer-events-auto relative inline-grid size-[18px] cursor-pointer place-items-center overflow-hidden p-0"
                    @if ($is_editor) x-show="activeChatbot.is_attachment" @endif
                >
                    @if (isset($chatbot))
                        <input
                            class="absolute start-0 top-0 z-10 h-full w-full cursor-pointer appearance-none border-none p-0 opacity-0 file:size-full file:cursor-pointer"
                            type="file"
                            @change.prevent="onFileSelect"
                            x-ref="mediaInput"
                            x-show="!uploading"
                        >
                    @endif
                    <x-tabler-paperclip
                        class="size-full"
                        x-show="!uploading"
                    />
                    <x-tabler-loader-2
                        class="size-full animate-spin"
                        x-show="uploading"
                    />
                </div>
            </template>
        @endif
        @if ($is_editor || (isset($chatbot) && $chatbot->is_emoji))
            <button
                class="pointer-events-auto inline-grid size-[18px] place-items-center p-0"
                @if ($is_editor) x-show="activeChatbot.is_emoji" @endif
                @click.prevent="showEmojiPicker = !showEmojiPicker"
                type="button"
            >
                <x-tabler-mood-smile class="size-full" />
            </button>
        @endif

        <button
            class="pointer-events-auto hidden size-[18px] cursor-pointer place-items-center [&.active]:inline-grid"
            type="submit"
            x-ref="sendBtn"
        >
            <svg
                width="16"
                height="13.5"
                viewBox="0 0 19 16"
                fill="currentColor"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path d="M0 16V10L8 8L0 6V0L19 8L0 16Z" />
            </svg>
        </button>
    </div>

    @if ($is_editor || (isset($chatbot) && $chatbot->is_emoji))
        <div
            class="lqd-ext-chatbot-emoji-picker pointer-events-auto absolute -inset-x-4 bottom-full"
            x-ref="emojiPicker"
            x-show="showEmojiPicker"
            @click.outside="showEmojiPicker = false"
        ></div>
    @endif
</form>
