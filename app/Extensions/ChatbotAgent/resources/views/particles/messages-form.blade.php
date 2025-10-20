<div class="sticky bottom-0 lg:px-8 lg:py-7">
    <x-progressive-blur class="max-lg:hidden" />

    <form
        class="lqd-chat-form gap-y-1 border-t bg-foreground/5 backdrop-blur-2xl backdrop-contrast-[105%] lg:rounded-xl lg:border-t-0"
        id="chat_form"
        @submit.prevent="onSendMessage"
        x-transition
    >
        <textarea
            class="min-h-12 w-full resize-none border-none bg-transparent px-6 pt-4 placeholder:text-foreground/70 focus:outline-none sm:text-xs"
            id="message"
            rows="2"
            name="message"
            @keydown.enter.prevent="onMessageFieldHitEnter"
            @input="onMessageFieldInput"
            @input.throttle.50ms="$el.scrollTop = $el.scrollHeight"
            x-ref="message"
            placeholder="{{ __('Message') }}"
        ></textarea>

        <div class="flex w-full items-center justify-between gap-2 px-6 pb-4">
            <div class="flex items-center gap-1">
                <span
                    class="relative inline-grid size-7 cursor-pointer place-items-center rounded-full text-foreground/80 transition hover:bg-background hover:text-foreground hover:shadow-lg"
                    type="button"
                    title="{{ __('Attach Files') }}"
                >
                    <x-tabler-plus class="size-[18px]" />
                    <input
                        class="absolute inset-0 z-10 cursor-pointer opacity-0 file:w-full file:cursor-pointer file:p-0"
                        type="file"
                        x-ref="media"
                        name="media"
                        @change="setAttachmentsPreview"
                    />
                </span>

                <div class="relative">
                    <button
                        class="inline-grid size-7 place-items-center rounded-full text-foreground/80 transition hover:bg-background hover:text-foreground hover:shadow-lg"
                        type="button"
                        title="{{ __('Emojis') }}"
                        x-ref="emojiTrigger"
                        @click.prevent="showEmojiPicker = !showEmojiPicker"
                    >
                        <x-tabler-mood-smile class="size-[18px]" />
                    </button>
                    <div
                        class="lqd-chatbot-emoji pointer-events-auto absolute -start-8 bottom-full mb-2 sm:-start-4"
                        x-ref="emojiPicker"
                        x-show="showEmojiPicker"
                        @click.outside="showEmojiPicker = false"
                    ></div>
                </div>

                <template x-if="attachmentsPreview.length">
                    <div class="relative flex flex-wrap gap-1">
                        <template x-for="attachment in attachmentsPreview">
                            <template x-if="attachment.type.startsWith('image/')">
                                <img
                                    class="size-10 shrink-0 rounded-md object-cover object-center shadow-sm shadow-black/5 md:size-14"
                                    :src="attachment.url"
                                >
                            </template>
                        </template>
                    </div>
                </template>
            </div>

            <x-button
                class="bg-background text-[12px] font-medium text-foreground shadow-lg disabled:shadow-none max-lg:!bg-transparent max-lg:shadow-none max-lg:hover:text-foreground"
                hover-variant="primary"
                type="submit"
                x-ref="submitBtn"
                disabled
                ::disabled="!$refs.message.value.trim() && !attachmentsPreview.length"
            >
                <span class="max-lg:hidden">
                    {{ __('Send') }}
                </span>
                <svg
                    class="fill-current lg:hidden"
                    width="19"
                    height="16"
                    viewBox="0 0 19 16"
                    fill="currentColor"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path d="M0 16V10L8 8L0 6V0L19 8L0 16Z" />
                </svg>
            </x-button>
        </div>
    </form>
</div>
