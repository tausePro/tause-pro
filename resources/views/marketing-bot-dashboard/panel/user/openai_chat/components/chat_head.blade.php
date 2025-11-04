<div
    class="lqd-chat-head sticky -top-px z-30 flex min-h-20 items-center justify-between gap-2 rounded-se-[inherit] border-b bg-background/80 px-5 py-3 backdrop-blur-lg backdrop-saturate-150 max-md:bg-background/95 max-md:px-4">
    <div class="flex flex-col items-start justify-center text-sm">
        @include('panel.user.openai_chat.components.chat_category_dropdown')
    </div>

    <div class="flex grow items-center justify-end gap-4">
        <div class="flex gap-2 md:gap-3">
            @if (view()->hasSection('chat_head_actions'))
                @yield('chat_head_actions')
            @else
                @php
                    $realtimeHiddenIn = ['ai_pdf', 'ai_vision', 'ai_chat_image'];
                @endphp
                @auth
                    <x-forms.input
                        class="max-md:hidden"
                        class:label="flex-row-reverse"
                        id="realtime"
                        container-class="{{ in_array($category->slug, $realtimeHiddenIn, true) ? 'hidden' : 'flex' }} max-md:size-8 max-md:inline-flex max-md:items-center max-md:justify-center max-md:overflow-hidden max-md:shadow-md max-md:rounded-full max-md:shrink-0 max-md:[&_.lqd-input-label-txt]:hidden"
                        label="{{ __('Real-Time Data') }}"
                        type="checkbox"
                        name="realtime"
                        onchange="const checked = document.querySelector('#realtime').checked; if ( checked ) { toastr.success('Real-Time data activated') } else { toastr.warning('Real-Time data deactivated') }"
                        switcher
                        size="sm"
                    >
                        <span
                            class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-background indent-0 text-heading-foreground transition-colors peer-checked:bg-primary peer-checked:text-primary-foreground md:hidden"
                        >
                            <x-tabler-world-download
                                class="size-5"
                                stroke-width="1.5"
                            />
                        </span>
                    </x-forms.input>
                @else
                    <x-forms.input
                        class="max-md:hidden"
                        class:label="flex-row-reverse"
                        id="realtime"
                        size="sm"
                        container-class="{{ in_array($category->slug, $realtimeHiddenIn, true) ? 'hidden' : 'flex' }} max-md:size-8 max-md:inline-flex max-md:items-center max-md:justify-center max-md:overflow-hidden max-md:shadow-md max-md:rounded-full max-md:shrink-0 max-md:[&_.lqd-input-label-txt]:hidden"
                        label="{{ __('Real-Time Data') }}"
                        type="checkbox"
                        name="realtime"
                        onchange="toastr.warning('{{ __('Login to use Real-Time search') }}'); document.querySelector('#realtime').checked = false; return false;"
                        switcher
                    >
                        <span
                            class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-background indent-0 text-heading-foreground transition-colors peer-checked:bg-primary peer-checked:text-primary-foreground md:hidden"
                        >
                            <x-tabler-world-download
                                class="size-5"
                                stroke-width="1.5"
                            />
                        </span>
                    </x-forms.input>
                @endauth
            @endif

            <span class="hidden h-6 w-px self-center bg-border md:inline-block"></span>

            <div
                class="md:relative md:inline-grid md:size-[42px] md:place-items-center md:self-center md:rounded-full md:border md:[&_.lqd-chat-share-modal-trigger]:absolute md:[&_.lqd-chat-share-modal-trigger]:z-10 md:[&_.lqd-chat-share-modal-trigger]:size-full md:[&_.lqd-chat-share-modal-trigger]:opacity-0 [&_.lqd-modal]:size-full md:[&_.lqd-modal]:absolute">
                <x-tabler-share class="hidden size-5 md:block" />
                @includeFirst(['chat-share::share-button-include', 'panel.user.openai_chat.includes.share-button-include', 'vendor.empty'])
            </div>

            <div
                class="group relative inline-flex flex-row items-center justify-center self-center max-md:-order-1"
                id="show_export_btns"
            >
                <button
                    class="max-md:inline-flex max-md:size-8 max-md:items-center max-md:justify-center max-md:rounded-full max-md:shadow-md md:inline-grid md:size-[42px] md:place-items-center md:rounded-full md:border"
                >
                    <x-tabler-download class="size-5" />
                </button>
                <div
                    class="invisible absolute -end-1 top-full mt-2 flex min-w-36 translate-y-2 scale-95 flex-col gap-1 rounded-lg border bg-dropdown-background p-2 opacity-0 transition-all before:inset-x-0 before:-top-2 before:bottom-full group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:scale-100 group-focus-within:opacity-100 group-hover:visible group-hover:translate-y-0 group-hover:scale-100 group-hover:opacity-100"
                    id="export_btns"
                >
                    <button
                        class="chat-download flex items-center gap-2 px-2 py-1 text-2xs font-medium hover:underline"
                        id="export_pdf"
                        data-doc-type="pdf"
                    >
                        <x-tabler-file-type-pdf class="size-6 shrink-0" />
                        {{ __('PDF') }}
                    </button>
                    <button
                        class="chat-download flex items-center gap-2 px-2 py-1 text-2xs font-medium hover:underline"
                        id="export_word"
                        data-doc-type="doc"
                    >
                        {{-- blade-formatter-disable --}}
						<svg class="size-6 shrink-0" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" > <path d="M14 3v4a1 1 0 0 0 1 1h4" /> <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2" /> <path d="M9 12l1.333 5l1.667 -4l1.667 4l1.333 -5" /> </svg>
						{{-- blade-formatter-enable --}}
                        {{ __('Word') }}
                    </button>
                    <button
                        class="chat-download flex items-center gap-2 px-2 py-1 text-2xs font-medium hover:underline"
                        id="export_txt"
                        data-doc-type="txt"
                    >
                        <x-tabler-file-text class="size-6 shrink-0" />
                        {{ __('Text') }}
                    </button>
                </div>
            </div>

            @if (view()->hasSection('chat_sidebar_actions'))
                @yield('chat_sidebar_actions')
            @else
                @if (isset($category) && $category->slug == 'ai_pdf')
                    {{-- #selectDocInput is present in chat_sidebar component. no need to duplicate it here --}}
                    <x-button
                        class="lqd-upload-doc-trigger group size-8 shrink-0 grid-flow-row place-items-center rounded-full shadow-md max-md:grid md:hidden"
                        variant="none"
                        size="none"
                        href="javascript:void(0);"
                        onclick="return $('#selectDocInput').click();"
                    >
                        <x-tabler-plus class="size-5" />
                        <span class="sr-only">
                            {{ __('Upload Document') }}
                        </span>
                    </x-button>
                @else
                    <x-button
                        class="lqd-new-chat-trigger group size-8 shrink-0 grid-flow-row place-items-center rounded-full shadow-md max-md:grid md:hidden"
                        variant="none"
                        size="none"
                        href="javascript:void(0);"
                        onclick="{!! $disable_actions
                            ? 'return toastr.info(\'{{ __('This feature is disabled in Demo version.') }}\')'
                            : (auth()->check()
                                ? 'return startNewChat(\'{{ $category->id }}\', \'{{ LaravelLocalization::getCurrentLocale() }}\', \'chatpro\')'
                                : 'return window.location.reload();') !!}"
                    >
                        <x-tabler-plus class="size-5" />
                        <span class="sr-only">
                            {{ __('New Conversation') }}
                        </span>
                    </x-button>
                @endif

                <div class="lqd-chat-mobile-sidebar-trigger self-center">
                    <button
                        class="group size-8 shrink-0 grid-flow-row place-items-center rounded-full shadow-md max-md:grid md:hidden"
                        :class="{ 'active': mobileSidebarShow }"
                        @click.prevent="toggleMobileSidebar()"
                        type="button"
                    >
                        <x-tabler-dots class="col-start-1 row-start-1 size-5 transition-all group-[&.active]:rotate-45 group-[&.active]:scale-75 group-[&.active]:opacity-0" />
                        <x-tabler-x class="col-start-1 row-start-1 size-4 -rotate-45 opacity-0 transition-all group-[&.active]:rotate-0 group-[&.active]:!opacity-100" />
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
