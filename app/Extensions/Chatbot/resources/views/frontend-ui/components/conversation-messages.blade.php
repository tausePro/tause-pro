@php
    $connect_agent_enabled =
        isset($chatbot) &&
        $chatbot->getAttribute('interaction_type') === \App\Extensions\Chatbot\System\Enums\InteractionType::SMART_SWITCH &&
        \App\Helpers\Classes\MarketplaceHelper::isRegistered('chatbot-agent');
@endphp

<template x-for="(message, index) in messages">
    <div>
        <template x-if="message.role === 'collect-email'">
            <div class="mb-7 flex items-center gap-3 text-3xs">
                <span class="h-px grow bg-current opacity-5"></span>
                {{ __('Let’s keep you notified.') }}
                <span class="h-px grow bg-current opacity-5"></span>
            </div>
        </template>

        <template x-if="message.role !== 'connecting-to-agent'">
            <div
                class="lqd-ext-chatbot-window-conversation-message"
                :data-type="message.role"
                :data-id="message.id"
            >
                <figure
                    class="lqd-ext-chatbot-window-conversation-message-avatar"
                    x-show="message.role !== 'user'"
                >
                    <img
                        {{-- blade-formatter-disable --}}
					@if ($is_editor)
						:src="() => activeChatbot.avatar ? `${window.location.origin}/${activeChatbot.avatar}` : ''"
					@else
						src="/{{ $chatbot['avatar'] }}"
						alt="{{ $chatbot['title'] }}"
						@if (!empty($chatbot['trigger_avatar_size']))
							style="width: {{ (int) $chatbot['trigger_avatar_size'] }}px"
						@endif
					@endif
					{{-- blade-formatter-enable --}}
                        width="27"
                        height="27"
                    />
                </figure>

                <div class="lqd-ext-chatbot-window-conversation-message-content-wrap">
                    <div class="lqd-ext-chatbot-window-conversation-message-content text-xs/5">
                        <pre
                            class="peer"
                            :class="{ 'prose prose-neutral prose-sm': message.role === 'assistant' }"
                            x-ref="conversationMessage"
                            :data-index="index"
                            x-html="addMessage(message.message, $el)"
                        ></pre>

                        <template x-if="message.media_url && message.media_name">
                            <a
                                class="mt-2 block font-medium text-current underline underline-offset-2 peer-empty:mt-0"
                                :href="message.media_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                x-text="message.media_name"
                            ></a>
                        </template>

                        <template x-if="message.role === 'loader'">
                            <span class="lqd-ext-chatbot-window-conversation-message-loader inline-flex items-center gap-1">
                                <span class="inline-block size-1 rounded-full bg-current"></span>
                                <span class="inline-block size-1 rounded-full bg-current"></span>
                                <span class="inline-block size-1 rounded-full bg-current"></span>
                            </span>
                        </template>

                        <template x-if="message.role === 'collect-email'">
                            <div>
                                <p class="mb-3.5 text-balance">
                                    {{ __('In case we lose contact, may I have your email address so we can follow up?') }}
                                </p>
                                <form
                                    class="relative mb-0 w-full"
                                    @submit.prevent="collectEmail"
                                >
                                    <input
                                        class="h-[50px] w-full rounded-lg bg-white px-4 shadow-[0_4px_44px_hsl(0_0%_0%/5%)]"
                                        placeholder="{{ __('Email address') }}"
                                        type="email"
                                        name="email"
                                        :disabled="collectingEmail"
                                    >
                                    <button
                                        class="absolute end-2 top-1/2 inline-grid size-8 -translate-y-1/2 place-items-center text-black"
                                        type="submit"
                                        :class="{ 'pointer-events-none': collectingEmail }"
                                    >
                                        <svg
                                            class="col-start-1 col-end-1 row-start-1 row-end-1"
                                            width="14"
                                            height="12"
                                            viewBox="0 0 14 12"
                                            fill="currentColor"
                                            xmlns="http://www.w3.org/2000/svg"
                                            x-show="!collectingEmail"
                                        >
                                            <path
                                                d="M13.0318 5.20841L13.0274 5.20649L1.34941 0.36282C1.25119 0.321708 1.14431 0.305585 1.03833 0.315892C0.932345 0.326199 0.830572 0.362615 0.74211 0.421882C0.648647 0.483124 0.571876 0.56664 0.518704 0.664918C0.465532 0.763196 0.437627 0.87315 0.4375 0.98489V4.08266C0.437552 4.23542 0.490891 4.38337 0.588324 4.50102C0.685757 4.61867 0.821179 4.69864 0.97125 4.72716L7.34043 5.90485C7.36546 5.9096 7.38804 5.92293 7.40429 5.94255C7.42054 5.96216 7.42943 5.98684 7.42943 6.01231C7.42943 6.03779 7.42054 6.06246 7.40429 6.08208C7.38804 6.1017 7.36546 6.11503 7.34043 6.11977L0.971524 7.29747C0.821495 7.32591 0.68608 7.40578 0.588604 7.52332C0.491127 7.64086 0.437691 7.78871 0.4375 7.94141V11.0397C0.437428 11.1464 0.463847 11.2515 0.514387 11.3455C0.564928 11.4394 0.638008 11.5194 0.72707 11.5781C0.834203 11.6493 0.959931 11.6874 1.08855 11.6875C1.17797 11.6874 1.26648 11.6695 1.34887 11.6347L13.0266 6.81868L13.0318 6.81622C13.1889 6.74866 13.3229 6.63651 13.417 6.49363C13.5111 6.35074 13.5613 6.1834 13.5613 6.01231C13.5613 5.84122 13.5111 5.67388 13.417 5.531C13.3229 5.38812 13.1889 5.27596 13.0318 5.20841Z"
                                            />
                                        </svg>
                                        <x-tabler-loader-2
                                            class="col-start-1 col-end-1 row-start-1 row-end-1 size-4 animate-spin"
                                            x-cloak
                                            x-show="collectingEmail"
                                        />
                                    </button>
                                </form>
                            </div>
                        </template>

                        @if ($connect_agent_enabled)
                            <template x-if="message.showConnectButtons">
                                <div class="mt-3.5 flex w-full flex-wrap gap-2">
                                    <button
                                        class="rounded-xl bg-[#3882C20D] px-5 py-3 text-start text-xs font-normal text-[#3882C2] transition-all hover:-translate-y-0.5 hover:bg-[--lqd-ext-chat-primary] hover:text-[--lqd-ext-chat-primary-foreground]"
                                        @click.prevent="doNotConnectToAgent"
                                    >
                                        {{ __('No, Thanks! 👍') }}
                                    </button>

                                    <button
                                        class="rounded-xl bg-[#3882C20D] px-5 py-3 text-start text-xs font-normal text-[#3882C2] transition-all hover:-translate-y-0.5 hover:bg-[--lqd-ext-chat-primary] hover:text-[--lqd-ext-chat-primary-foreground]"
                                        @click.prevent="connectToAgent"
                                    >
                                        {{ __('Connect to human agent') }}
                                    </button>
                                </div>
                            </template>
                        @endif
                    </div>
                    @if (isset($chatbot) && $chatbot['show_date_and_time'])
                        <div
                            class="lqd-ext-chatbot-window-conversation-message-time"
                            x-show="message.role !== 'collect-email'"
                            x-text="getTimeLabel(message.created_at ?? new Date())"
                        ></div>
                    @endif
                    @if (!isset($chatbot))
                        <div
                            class="lqd-ext-chatbot-window-conversation-message-time"
                            x-show="activeChatbot?.show_date_and_time && message.role !== 'collect-email'"
                            x-text="getTimeLabel(message.created_at ?? new Date())"
                        ></div>
                    @endif
                </div>
            </div>
        </template>

        @if ($connect_agent_enabled)
            <template x-if="message.role === 'connecting-to-agent'">
                <div
                    class="mt-7 flex items-center gap-3 text-3xs"
                    :class="{ 'hidden': !connectingToAgent && connect_agent_at == null, 'flex': connectingToAgent || connect_agent_at != null }"
                >
                    <span class="h-px grow bg-current opacity-5"></span>
                    <span class="flex items-center gap-1">
                        <span x-text="connect_agent_at == null && connectingToAgent ? '{{ __('Connecting to Human Agent') }}' : '{{ __('Human Agent Connected') }}'"></span>
                        <template x-if="message.role === 'connect-agent' && connectingToAgent">
                            <span class="lqd-ext-chatbot-window-conversation-message-loader inline-flex items-center gap-0.5">
                                <span class="inline-block size-0.5 rounded-full bg-current"></span>
                                <span class="inline-block size-0.5 rounded-full bg-current"></span>
                                <span class="inline-block size-0.5 rounded-full bg-current"></span>
                            </span>
                        </template>
                    </span>
                    <span class="h-px grow bg-current opacity-5"></span>
                </div>
            </template>
        @endif
    </div>
</template>
