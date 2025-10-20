@php
    $status_filters = [
        'all' => [
            'label' => __('All'),
        ],
        'new' => [
            'label' => __('New'),
        ],
        'closed' => [
            'label' => __('Closed'),
        ],
    ];

    $sort_filters = [
        'newest' => [
            'label' => __('Newest'),
        ],
        'oldest' => [
            'label' => __('Oldest'),
        ],
    ];

    $agent_filters = [
        'all' => [
            'label' => __('All'),
        ],
        'ai' => [
            'label' => __('AI Agent'),
        ],
        'human' => [
            'label' => __('Human Agent'),
        ],
    ];

    $channel_filters = [
        'all' => [
            'label' => __('All Channel'),
        ],
        'frame' => [
            'label' => __('Live Chat'),
        ],
    ];

    if (\App\Helpers\Classes\MarketplaceHelper::isRegistered('chatbot-telegram')) {
        $channel_filters['telegram'] = [
            'label' => __('Telegram'),
        ];
    }

    if (\App\Helpers\Classes\MarketplaceHelper::isRegistered('chatbot-whatsapp')) {
        $channel_filters['whatsapp'] = [
            'label' => __('Whatsapp'),
        ];
    }
@endphp
<div
    class="lqd-ext-chatbot-history invisible fixed end-0 start-0 top-0 z-10 flex h-screen bg-background opacity-0 transition-all max-sm:block lg:start-[--navbar-width] lg:group-[&.focus-mode]/body:start-0 [&.lqd-open]:visible [&.lqd-open]:opacity-100"
    x-data="externalChatbotHistory"
    :class="{ 'lqd-open': open }"
    @keydown.window.escape="setOpen(false)"
>
    <div
        class="lqd-ext-chatbot-history-sidebar group/history-sidebar relative flex shrink-0 flex-col bg-foreground/[3%] lg:w-[clamp(250px,27%,400px)]"
        :class="{ 'mobile-dropdown-open': mobileDropdownOpen }"
    >
        @includeIf('chatbot::home.chats-history.particles.conversations-filter')

        <div
            class="transition-all max-lg:fixed max-lg:bottom-0 max-lg:top-[calc(var(--header-height)+3.5rem)] max-lg:z-10 max-lg:flex max-lg:h-0 max-lg:w-full max-lg:flex-col max-lg:overflow-hidden max-lg:bg-background/90 max-lg:backdrop-blur-lg lg:contents max-lg:[&.active]:h-full"
            :class="{ 'active': mobile.filtersVisible }"
        >
            @includeIf('chatbot-agent::particles.conversations-sort')

            @includeIf('chatbot::home.chats-history.particles.conversations-channel-filter')

            @include('chatbot::home.chats-history.particles.conversations-list')
        </div>
    </div>

    <div
        class="lqd-ext-chatbot-history-content-wrap flex h-full grow flex-col overflow-y-auto lg:w-1/2"
        x-ref="historyContentWrap"
    >
        @include('chatbot::home.chats-history.particles.messages-header')

        <div
            class="lqd-ext-chatbot-history-messages relative flex h-full flex-col gap-2 py-10"
            x-ref="historyMessages"
        >
            <div class="mt-auto space-y-2 px-4 xl:px-10">
                @include('chatbot::home.chats-history.particles.messages-list')
            </div>
        </div>
    </div>

    <div
        class="lqd-ext-chatbot-contact-info flex flex-col border-s transition-all max-lg:fixed max-lg:bottom-0 max-lg:top-[calc(var(--header-height)+3.5rem)] max-lg:z-10 max-lg:h-0 max-lg:max-h-[calc(100%-var(--header-height)-3.5rem)] max-lg:w-full max-lg:overflow-hidden max-lg:bg-background/90 max-lg:backdrop-blur-lg lg:w-[clamp(250px,27%,400px)] max-lg:[&.active]:h-full"
        :class="{ 'active': mobile.contactInfoVisible }"
    >
        @include('chatbot::home.chats-history.particles.contact-info-head')

        <div class="grid grow grid-cols-1 place-items-start overflow-y-auto">
            @include('chatbot::home.chats-history.particles.contact-info-tab-details')
            {{-- @include('chatbot::home.chats-history.particles.contact-info-tab-history') --}}
        </div>
    </div>
</div>

@push('script')
    <script src="{{ custom_theme_url('/assets/libs/fslightbox/fslightbox.js') }}"></script>
    <script>
        (() => {
            document.addEventListener('alpine:init', () => {
                Alpine.data('externalChatbotHistory', () => ({
                    filters: {
                        status: '{{ array_key_first($status_filters) }}',
                        agent: '{{ array_key_first($agent_filters) }}',
                        channel: '{{ array_key_first($channel_filters) }}',
                        sort: '{{ array_key_first($sort_filters) }}',
                        unreadsOnly: false,
                    },
                    open: false,
                    chatsList: [],
                    activeChat: null,
                    fetching: true,
                    activeSessionId: null,
                    currentPage: 1,
                    allLoaded: false,
                    /**
                     * @type {IntersectionObserver}
                     */
                    loadMoreIO: null,
                    originalLoadMoreHref: null,
                    mobileDropdownOpen: false,
                    messagesSearchFormVisible: false,
                    conversationsSearchFormVisible: false,
                    contactInfo: {
                        activeTab: 'details',
                        editMode: false,
                    },
                    mobile: {
                        filtersVisible: false,
                        contactInfoVisible: false,
                    },
                    userConversationHistory: [],

                    async init() {
                        this.setActiveChat = this.setActiveChat.bind(this);

                        this.originalLoadMoreHref = this.$refs.loadMore.href;

                        await this.fetchChats({
                            loadMore: true
                        });

                        this.setupLoadMoreIO();

                        Alpine.store('externalChatbotHistory', this);
                    },
                    async loadMore() {
                        if (this.fetching || this.allLoaded) return;

                        await this.fetchChats({
                            loadMore: true
                        });
                    },
                    setupLoadMoreIO() {
                        this.loadMoreIO = new IntersectionObserver(async ([entry], observer) => {
                            if (entry.isIntersecting && !this.fetching && !this.allLoaded) {
                                await this.loadMore();
                            }
                        });

                        this.loadMoreIO.observe(this.$refs.loadMoreWrap);
                    },
                    async setOpen(open) {
                        if (this.open === open) return;

                        const topNoticeBar = document.querySelector('.top-notice-bar');
                        const navbar = document.querySelector('.lqd-navbar');
                        const pageContentWrap = document.querySelector('.lqd-page-content-wrap');
                        const navbarExpander = document.querySelector('.lqd-navbar-expander');

                        this.open = open;

                        document.documentElement.style.overflow = this.open ? 'hidden' : '';

                        if (navbar) {
                            navbar.style.position = this.open ? 'fixed' : '';
                        }

                        if (pageContentWrap && navbar?.offsetWidth > 0) {
                            pageContentWrap.style.paddingInlineStart = this.open ? 'var(--navbar-width)' : '';
                        }

                        if (topNoticeBar) {
                            topNoticeBar.style.visibility = this.open ? 'hidden' : '';
                        }

                        if (navbarExpander) {
                            navbarExpander.style.visibility = this.open ? 'hidden' : '';
                            navbarExpander.style.opacity = this.open ? 0 : 1;
                        }
                    },
                    async fetchChats(opts = {}) {
                        const options = {
                            loadMore: false,
                            ...opts
                        };

                        if (!options.loadMore) {
                            this.$refs.loadMore.href = this.originalLoadMoreHref;
                        }

                        this.fetching = true;

                        const res = await fetch(
                            `${this.$refs.loadMore.href}&agentFilter=${this.filters.agent}&chatbot_channel=${this.filters.channel}&status=${this.filters.status}&unread=${this.filters.unreadsOnly}&sort=${this.filters.sort}`, {
                                method: 'GET',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                },
                            });
                        const data = await res.json();
                        const {
                            data: conversations
                        } = data;

                        if (!res.ok || !conversations) {
                            if (data.message) {
                                toastr.error(data.message);
                            }
                            return;
                        }

                        if (!options.loadMore) {
                            this.chatsList = conversations;
                        } else {
                            this.chatsList.push(...conversations);
                        }

                        this.allLoaded = data.meta.current_page >= data.meta.last_page;

                        this.$refs.loadMore.href = data.links.next ?? data.links.first;

                        if ((!options.loadMore || !this.activeChat) && this.chatsList.length) {
                            this.activeChat = this.chatsList[0];
                        }

                        this.fetching = false;

                        this.scrollMessagesToBottom();
                    },

                    async filterAgent(agent) {
                        if (this.filters.agent === agent) return;

                        this.filters.agent = agent;

                        await this.fetchChats();
                    },

                    async filterStatus(status) {
                        if (this.filters.status === status) return;

                        this.filters.status = status;

                        this.fetchChats();

                        toastr.success('{{ trans('Filter ticket status') }}')
                    },

                    async filterSort(sort) {
                        if (this.filters.sort === sort) return;

                        this.filters.sort = sort;

                        await this.fetchChats();
                    },

                    async filterUnread(event) {
                        const checkbox = event.target;
                        const unreadsOnly = checkbox.checked;

                        if (this.filters.unreadsOnly === unreadsOnly) return;

                        this.filters.unreadsOnly = unreadsOnly;

                        await this.fetchChats();
                    },


                    async handleConversationsSearch() {
                        const query = this.$refs.historySearchInput?.value?.trim();
                        this.fetching = true;

                        const res = await fetch('{{ route('dashboard.chatbot.conversations.search') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                search: query,
                            }),
                        });
                        const data = await res.json();
                        const {
                            data: conversations
                        } = data;

                        if (!res.ok || !conversations) {
                            if (data.message) {
                                toastr.error(data.message);
                            }
                            return;
                        }

                        this.chatsList = conversations;
                        this.allLoaded = true;

                        if (conversations.length) {
                            this.activeChat = conversations;
                        } else {
                            this.activeChat = null;
                        }

                        this.fetching = false;
                        this.scrollMessagesToBottom();
                    },

                    async setActiveChat(chatId) {
                        if (!chatId) return;

                        this.activeChat = this.chatsList.find(c => c.id === chatId);

                        this.mobile.filtersVisible = false;

                        this.scrollMessagesToBottom();
                    },

                    getUnreadMessages(chatId) {
                        if (chatId == null) return;

                        let chat = this.chatsList.find(chat => chat.id === chatId);

                        if (!chat) return;

                        return chat?.histories?.filter(history => history.role == 'user' && history.read_at == null)?.length ?? 0;
                    },

                    getAllUnreadMessages() {
                        const unreadMessages = this.chatsList?.reduce((previousValue, chat) => {
                            return previousValue + (this.getUnreadMessages(chat.id) ?? 0);
                        }, 0);

                        return unreadMessages;
                    },

                    scrollMessagesToBottom() {
                        this.$nextTick(() => {
                            this.$refs.historyContentWrap.scrollTo({
                                top: this.$refs.historyContentWrap.scrollHeight,
                                behavior: 'smooth',
                            });
                        })
                    },

                    async handleMessagesSearch(inputElement) {
                        const searchString = inputElement.value;

                        if (!searchString.trim() && this.activeChat.originalHistories) {
                            if (this.activeChat) {
                                this.activeChat.histories = this.activeChat.originalHistories;
                            }
                            return;
                        }

                        if (this.activeChat.histories) {

                            if (!this.activeChat.originalHistories) {
                                this.activeChat.originalHistories = this.activeChat.histories;
                            }

                            const filteredHistories = this.activeChat.histories.filter(message =>
                                message.message && message.message.toLowerCase().includes(searchString.toLowerCase())
                            );

                            this.activeChat.histories = filteredHistories;
                        }
                    },

                    getFormattedString(string) {
                        if (!('markdownit' in window) || !string) return '';

                        string
                            .replace(/>(\s*\r?\n\s*)</g, '><')
                            .replace(/\n(?!.*\n)/, '');

                        const renderer = window.markdownit({
                            breaks: true,
                            highlight: (str, lang) => {
                                const language = lang && lang !== '' ? lang : 'md';
                                // const codeString = str.replace(/&/g, '&amp;').replace(/</g, '&lt;');
                                const codeString = str;

                                if (Prism.languages[language]) {
                                    const highlighted = Prism.highlight(codeString, Prism.languages[language], language);
                                    return `<pre class="language-${language}"><code data-lang="${language}" class="language-${language}">${highlighted}</code></pre>`;
                                }

                                return codeString;
                            }
                        });

                        renderer.use(function(md) {
                            md.core.ruler.after('inline', 'convert_links', function(state) {
                                state.tokens.forEach(function(blockToken) {
                                    if (blockToken.type !== 'inline') return;
                                    blockToken.children.forEach(function(token, idx) {
                                        if (token.content.includes('<a ')) {
                                            const linkRegex = /(.*)(<a\s+href="([^"]+)"[^>]*>([^<]+)<\/a>)(.*)/;
                                            const linkMatch = token.content.match(linkRegex);

                                            if (linkMatch) {
                                                const [, before, , href, text, after] = linkMatch;

                                                const beforeToken = new state.Token('text', '', 0);
                                                beforeToken.content = before;

                                                const newToken = new state.Token('link_open', 'a', 1);
                                                newToken.attrs = [
                                                    ['href', href]
                                                ];
                                                const textToken = new state.Token('text', '', 0);
                                                textToken.content = text;
                                                const closingToken = new state.Token('link_close', 'a', -1);

                                                const afterToken = new state.Token('text', '', 0);
                                                afterToken.content = after;

                                                blockToken.children.splice(idx, 1, beforeToken, newToken, textToken,
                                                    closingToken, afterToken);
                                            }
                                        }
                                    });
                                });
                            });
                        });

                        return renderer.render(renderer.utils.unescapeAll(string));
                    },

                    getDiffHumanTime(time) {
                        const diff = Math.floor((new Date() - new Date(time)) / 1000);

                        return diff < 60 ? " {{ __('Just now') }}" :
                            diff < 3600 ? (Math.floor(diff / 60) === 1 ?
                                "1 {{ __('minute ago') }}" : Math.floor(diff / 60) +
                                " {{ __('minutes ago') }}") :
                            diff < 86400 ? (Math.floor(diff / 3600) === 1 ?
                                "1 {{ __('hour ago') }}" : Math.floor(diff / 3600) +
                                " {{ __('hours ago') }}") :
                            Math.floor(diff / 86400) === 1 ? "1 {{ __('day ago') }}" : Math.floor(
                                diff / 86400) + " {{ __('days ago') }}"
                    },

                    getShortDiffHumanTime(time) {
                        const diff = Math.floor((new Date() - new Date(time)) / 1000);

                        return diff < 60 ? '{{ __('Just now') }}' :
                            diff < 3600 ? Math.floor(diff / 60) + '{{ __('m') }}' :
                            diff < 86400 ? Math.floor(diff / 3600) + '{{ __('h') }}' :
                            Math.floor(diff / 86400) + '{{ __('d') }}'
                    }
                }));
            });
        })();
    </script>
@endpush
