<template x-for="(message, index) in activeChat?.histories">
    <div
        class="lqd-ext-chatbot-history-message flex max-w-[430px] gap-2"
        data-sender="message.role"
        :class="{ 'flex-row-reverse ms-auto': message.role === 'assistant' }"
    >
        <template x-if="message.role === 'user'">
            <figure
                class="inline-grid size-6 shrink-0 place-items-center rounded-full bg-foreground/20 font-heading text-[12px] font-semibold uppercase text-white"
                :style="{ 'backgroundColor': activeChat.color ?? '#e633ec' }"
            >
                <img
                    class="col-start-1 col-end-1 row-start-1 row-end-1 size-full object-cover object-center"
                    :src="activeChat.avatar"
                    x-show="activeChat.avatar"
                >

                <span
                    class="col-start-1 col-end-1 row-start-1 row-end-1"
                    x-show="!activeChat.avatar"
                    x-text="(activeChat.conversation_name ?? '{{ __('Anonymous User') }}').split('')?.at(0)"
                ></span>
            </figure>
        </template>

        <div class="lqd-ext-chatbot-history-message-content-wrap space-y-1 lg:max-w-[420px]">
            <div
                class="lqd-ext-chatbot-history-message-content w-fit rounded-xl p-3"
                :class="{
                    'bg-heading-foreground/5 text-heading-foreground': message.role === 'user',
                    'bg-secondary text-secondary-foreground ms-auto dark:bg-zinc-700 dark:text-primary-foreground': message.role === 'assistant'
                }"
            >
                <pre
                    class="peer prose m-0 w-full whitespace-normal font-body text-sm text-current dark:prose-invert empty:hidden"
                    :class="{ 'text-heading-foreground': message.role === 'user', 'text-black': message.role === 'assistant' }"
                    x-html="getFormattedString(message.message)"
                ></pre>

                <template x-if="message.media_url && message.media_name">
                    <a
                        class="mt-2 block font-medium text-current underline underline-offset-2 peer-empty:mt-0"
                        :data-fslightbox="isImage ? 'gallery' : null"
                        :href="message.media_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        x-data="{ isImage(string) { return /\.(jpg|jpeg|png|gif|webp|svg|bmp|ico)$/i.test(string) } }"
                    >
                        <template x-if="message.media_url && isImage(message.media_url)">
                            <img
                                class="mt-2 max-w-full rounded-lg peer-empty:mt-0"
                                :src="message.media_url"
                                :alt="message.media_name || 'Uploaded image'"
                                x-init="if ('refreshFsLightbox' in window) { refreshFsLightbox() }"
                            />
                        </template>
                        <template x-if="!message.media_url || !isImage(message.media_url)">
                            <span x-text="message.media_name ?? message.media_url"></span>
                        </template>
                    </a>
                </template>

                <p
                    class="mb-0 mt-1 flex items-center gap-1.5 text-3xs opacity-40"
                    :class="{ 'justify-end text-end': message.role === 'assistant' }"
                    x-show="(message.media_url && message.media_name) || message.message.trim()"
                >
                    <span x-text="message.role === 'user' ? (activeChat.conversation_name ?? '{{ __('Anonymous') }}') : '{{ __('Agent') }}'"></span>
                    <span class="inline-block size-0.5 rounded-full bg-current"></span>
                    <span x-text="getDiffHumanTime(message.created_at)"></span>
                </p>
            </div>
        </div>
    </div>
</template>

<template x-if="!fetching && !activeChat?.histories?.length">
    <h4>
        {{ __('No messages found.') }}
    </h4>
</template>
