<script>
    (() => {
        document.addEventListener('alpine:init', () => {
            Alpine.data('externalChatbot', (isEditor = false) => ({
                conversations: [],
                activeConversationData: null,
                showConnectButtons: true,
                showConnectButtonsStepTwo: false,
                showConnectButtonsStepOne: true,
                connectingToAgent: false,
                connect_agent_at: null,
                messages: [],
                activeConversation: null,
                fetching: false,
                windowState: 'close',
                assistantMessageBubbles: {},
                prevViews: [],
                currentView: '{{ $is_editor ? 'conversation-messages' : 'welcome' }}',
                ablyInstance: null,
                ablyChannel: null,
                articles: {!! $is_editor ? '[]' : $chatbot->articles()->toJson() !!},
                showEmojiPicker: false,
                uploading: false,
                widgetStatus: {
                    type: null,
                    message: null
                },
                collectingEmail: false,
                searchingArticles: false,
                showingArticle: null,
                showOptionsDropdown: false,
                soundEnabled: {{ isset($chatbot) ? $chatbot->getAttribute('enable_sound') ?? 'true' : 'true' }},
                showGdprModal: false,
                gdprProcessing: false,
                gdprConsented: false,
                pendingConversationStart: false,

                init() {
                    this.windowState = this.$el.getAttribute('data-window-state');

                    this.handleWindowMessages = this.handleWindowMessages.bind(this);
                    this.toggleWindowState = this.toggleWindowState.bind(this);
                    this.onSendMessage = this.onSendMessage.bind(this);
                    this.scrollMessagesToBottom = this.scrollMessagesToBottom.bind(this);
                    this.doNotConnectToAgent = this.doNotConnectToAgent.bind(this);
                    this.onTypingDone = this.onTypingDone.bind(this);

                    @if ($is_editor)
                        this.$data.externalChatbot = this;

                        this.fillDemoConversations();
                        this.fillDemoMessages();
                        this.fillDemoArticles();
                    @else
                        this.getSession();
                        if (window.self !== window.top) {
                            window.addEventListener("message", this.handleWindowMessages);
                            document.documentElement.classList.add('lqd-ext-chatbot-embedded');
                        }
                    @endif

                    this.initEmojiPicker();
                    this.initAudioContext();
                },
                @if ($is_editor)
                    fillDemoConversations() {
                            const demoConversations = [{
                                    id: 0,
                                    last_message: '{{ __('I noted some aspects of the platform that need...') }}',
                                    created_at: new Date(Date.now() - 3600000).toISOString(),
                                },
                                {
                                    id: 1,
                                    last_message: '{{ __('I noted some aspects of the platform that need...') }}',
                                    created_at: new Date(Date.now() - 3600000).toISOString(),
                                },
                                {
                                    id: 2,
                                    last_message: '{{ __('I noted some aspects of the platform that need...') }}',
                                    created_at: new Date(Date.now() - 3600000).toISOString(),
                                },
                                {
                                    id: 3,
                                    last_message: '{{ __('I noted some aspects of the platform that need...') }}',
                                    created_at: new Date(Date.now() - 3600000).toISOString(),
                                },
                                {
                                    id: 4,
                                    last_message: '{{ __('I noted some aspects of the platform that need...') }}',
                                    created_at: new Date(Date.now() - 3600000).toISOString(),
                                },
                                {
                                    id: 5,
                                    last_message: '{{ __('I noted some aspects of the platform that need...') }}',
                                    created_at: new Date(Date.now() - 3600000).toISOString(),
                                },
                            ];

                            this.conversations = demoConversations;
                        },
                        fillDemoMessages() {
                            const demoMessages = [{
                                    id: 0,
                                    message: '{{ __('Hi, how can I help you?') }}',
                                    role: 'assistant',
                                    created_at: new Date(Date.now() - 3600000).toISOString(),
                                },
                                {
                                    id: 1,
                                    message: '{{ __('I need to make a refund.') }}',
                                    role: 'user',
                                    created_at: new Date(Date.now() - 3500000).toISOString(),
                                },
                                {
                                    id: 2,
                                    message: '{{ __('A refund will be provided after we process your return item at our facilities. It may take additional time for your financial institution to  process the refund.') }}',
                                    role: 'assistant',
                                    created_at: new Date(Date.now() - 3400000).toISOString(),
                                },
                                {
                                    id: 3,
                                    message: '{{ __('🤔') }}',
                                    role: 'user',
                                    created_at: new Date(Date.now()).toISOString(),
                                },
                            ];

                            this.messages = demoMessages;
                        },
                        fillDemoArticles() {
                            const demoArticles = [{
                                    title: '{{ __('Social Media Suite') }}',
                                    excerpt: '{{ __('Hey there, Welcome to our support center. I’ll be happy to assist you...') }}',
                                    link: '#',
                                },
                                {
                                    title: '{{ __('Marketing Bot') }}',
                                    excerpt: '{{ __('If you need further assistance, feel free to reach out via our contact form.') }}',
                                    link: '#',
                                },
                                {
                                    title: '{{ __('LMS') }}',
                                    excerpt: '{{ __('We have a collection of guides and tutorials to help you navigate our services.') }}',
                                    link: '#',
                                },
                            ];

                            this.articles = demoArticles;
                        },
                @endif
                initEmojiPicker() {
                    const picker = picmo.createPicker({
                        rootElement: this.$refs.emojiPicker
                    });

                    picker.addEventListener('emoji:select', event => {
                        this.$refs.message.value += event.emoji;
                        this.showEmojiPicker = false;

                        this.$refs.message.focus();
                        this.$refs.message.dispatchEvent(new Event('input'));
                    });
                },

                initAudioContext() {
                    try {
                        this.audioContext = new(window.AudioContext || window.webkitAudioContext)();
                    } catch (e) {
                        console.warn('Web Audio API not supported');
                    }
                },

                playBubbleSound() {
                    if (!this.audioContext || !this.soundEnabled) return;

                    try {
                        // Resume context if suspended (required for user interaction)
                        if (this.audioContext.state === 'suspended') {
                            this.audioContext.resume();
                        }

                        const now = this.audioContext.currentTime;

                        // Create oscillators for iMessage-style sound
                        const osc1 = this.audioContext.createOscillator();
                        const osc2 = this.audioContext.createOscillator();

                        // Create gain nodes
                        const gain1 = this.audioContext.createGain();
                        const gain2 = this.audioContext.createGain();
                        const masterGain = this.audioContext.createGain();

                        // Create a subtle reverb effect using delay
                        const delay = this.audioContext.createDelay();
                        const delayGain = this.audioContext.createGain();

                        // High-pass filter for crisp sound
                        const filter = this.audioContext.createBiquadFilter();
                        filter.type = 'highpass';
                        filter.frequency.setValueAtTime(200, now);
                        filter.Q.setValueAtTime(0.7, now);

                        // Connect the audio graph
                        osc1.connect(gain1);
                        osc2.connect(gain2);

                        gain1.connect(filter);
                        gain2.connect(filter);

                        filter.connect(masterGain);
                        filter.connect(delay);

                        delay.connect(delayGain);
                        delayGain.connect(masterGain);

                        masterGain.connect(this.audioContext.destination);

                        // Configure oscillators for iMessage-like sound
                        osc1.type = 'sine';
                        osc2.type = 'sine';

                        // First tone - bright and clear
                        osc1.frequency.setValueAtTime(1046.5, now); // C6
                        osc1.frequency.exponentialRampToValueAtTime(1318.5, now + 0.05); // E6

                        // Second tone - harmonic
                        osc2.frequency.setValueAtTime(1318.5, now); // E6
                        osc2.frequency.exponentialRampToValueAtTime(1568, now + 0.05); // G6

                        // Configure delay for subtle reverb
                        delay.delayTime.setValueAtTime(0.02, now);
                        delayGain.gain.setValueAtTime(0.1, now);

                        // Create sharp attack and quick decay (iMessage characteristic)
                        // Main oscillator envelope
                        gain1.gain.setValueAtTime(0, now);
                        gain1.gain.linearRampToValueAtTime(0.3, now + 0.005);
                        gain1.gain.exponentialRampToValueAtTime(0.15, now + 0.05);
                        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.15);

                        // Harmonic oscillator envelope
                        gain2.gain.setValueAtTime(0, now);
                        gain2.gain.linearRampToValueAtTime(0.2, now + 0.01);
                        gain2.gain.exponentialRampToValueAtTime(0.1, now + 0.06);
                        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.18);

                        // Master gain envelope
                        masterGain.gain.setValueAtTime(0.8, now);
                        masterGain.gain.exponentialRampToValueAtTime(0.001, now + 0.2);

                        // Start oscillators
                        osc1.start(now);
                        osc2.start(now + 0.002);

                        // Stop oscillators
                        osc1.stop(now + 0.2);
                        osc2.stop(now + 0.22);

                    } catch (e) {
                        console.warn('Could not play bubble sound:', e);
                    }
                },

                async initAbly() {
                    @if (isset($session) && setting('ably_public_key') && setting('ably_public_key') !== '' && is_string(setting('ably_public_key')) && strlen(setting('ably_public_key')) > 8)
                        if (!this.ablyInstance) {
                            this.ablyInstance = new Ably.Realtime.Promise(
                                "{{ setting('ably_public_key') }}");
                        }

                        let channelName = 'conversation-session-{{ $session }}';

                        this.ablyChannel = this.ablyInstance.channels.get(channelName);

                        await this.ablyChannel.subscribe('new-message', (message) => {
                            let data = message.data;

                            let incomingConversationId = data.conversationId;

                            if (incomingConversationId === this.activeConversation) {
                                this.messages.push(data.history);

                                this.scrollMessagesToBottom();
                            }

                            this.conversations.map((conversation) => {
                                if (conversation.id ===
                                    incomingConversationId) {
                                    conversation.last_message = data.history.message;
                                }
                                return conversation;
                            });
                        });
                    @endif
                },
                connectToAgent() {
                    @if (isset($chatbot) && isset($session) && \App\Helpers\Classes\MarketplaceHelper::isRegistered('chatbot-agent'))
                        let route = '{{ route('api.v2.chatbot.conversion.connect.support', [$chatbot->uuid, $session]) }}';

                        this.connectingToAgent = true;

                        this.messages.push({
                            id: 'connecting-to-agent',
                            role: 'connecting-to-agent',
                            created_at: Date.now()
                        });

                        this.scrollMessagesToBottom(true);

                        fetch(route, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-timezone': Intl.DateTimeFormat().resolvedOptions().timeZone
                            },
                            body: JSON.stringify({
                                conversation_id: this.activeConversation,
                            })
                        }).then((res) => {
                            return res.json();
                        }).then((data) => {
                            if (data.history) {
                                this.messages.push(data.history);
                            }

                            localStorage.setItem('connectToAgentStore:' + this.activeConversationData?.id, 'on');

                            this.connect_agent_at = data.data.connect_agent_at;

                            this.activeConversationData = data.data;

                            this.conversations = this.conversations.map(conversation => {
                                if (conversation.id === data.data.id) {
                                    conversation = data.data;
                                }

                                return conversation;
                            });

                            this.showConnectButtonsStepOne = false;
                            this.showConnectButtonsStepTwo = false;

                            this.connectingToAgent = false;

                            this.messages.forEach(message => {
                                message.showConnectButtons = false;
                            });

                            this.scrollMessagesToBottom(true);
                        }).catch((err) => {
                            this.connectingToAgent = false;
                        });
                    @endif
                },
                doNotConnectToAgent() {
                    localStorage.setItem('connectToAgentStore:' + this.activeConversationData?.id, 'off');

                    this.messages.forEach(message => {
                        message.showConnectButtons = false;
                    });

                    this.showConnectButtonsStepOne = true;
                    this.showConnectButtonsStepTwo = false;

                    this.showConnectButtons = false;
                },
                handleWindowMessages(event) {
                    switch (event.data.type) {
                        case 'lqd-ext-chatbot-request-styling':
                            this.handleStylingResponse(event);
                            break;
                        case 'lqd-ext-chatbot-inject-trigger':
                            this.handleProactiveTrigger(event);
                            break;
                    }
                },

                handleProactiveTrigger(event) {
                    console.log('[Chatbot] Received proactive trigger via postMessage:', event.data.data);
                    
                    const triggerMessage = event.data.data;
                    
                    // Ensure we have an active conversation
                    if (!this.activeConversation) {
                        console.log('[Chatbot] No active conversation, starting new one...');
                        this.startNewConversation().then(() => {
                            // Inject trigger after conversation is created
                            if (this.messages && Array.isArray(this.messages)) {
                                this.messages.push(triggerMessage);
                                console.log('[Chatbot] ✅ Trigger message injected into chat');
                                this.scrollMessagesToBottom();
                            }
                        });
                        return;
                    }
                    
                    if (this.messages && Array.isArray(this.messages)) {
                        this.messages.push(triggerMessage);
                        console.log('[Chatbot] ✅ Trigger message injected into chat');
                        
                        // Open chatbot if closed
                        if (this.windowState === 'close') {
                            console.log('[Chatbot] Opening chatbot window for trigger');
                            this.toggleWindowState('open');
                        }
                        
                        // Switch to conversation view
                        if (this.currentView !== 'conversation-messages') {
                            this.toggleView('conversation-messages');
                        }
                        
                        setTimeout(() => {
                            this.scrollMessagesToBottom();
                        }, 100);
                    } else {
                        console.error('[Chatbot] Cannot inject trigger - messages array not found');
                    }
                },

                handleStylingResponse(event) {
                    const chatbotElStyles = getComputedStyle(this.$el);
                    const styles = {};
                    const attrs = {};
                    [
                        '--lqd-ext-chat-font-family',
                        '--lqd-ext-chat-offset-y',
                        '--lqd-ext-chat-offset-x',
                        '--lqd-ext-chat-trigger-w',
                        '--lqd-ext-chat-trigger-h',
                        '--lqd-ext-chat-window-w',
                        '--lqd-ext-chat-window-h',
                        '--lqd-ext-chat-window-y-offset',
                        '--lqd-ext-chat-primary',
                        '--lqd-ext-chat-primary-foreground',
                    ].forEach(attr => styles[attr] = chatbotElStyles.getPropertyValue(attr) || '');

                    ['data-pos-x', 'data-pos-y'].forEach(attr => attrs[attr] = this.$el.getAttribute(attr));

                    event.source.postMessage({
                            type: 'lqd-ext-chatbot-response-styling',
                            data: {
                                styles,
                                attrs
                            },
                        },
                        event.origin,
                    );
                },
                toggleWindowState(state) {
                    if (state === this.windowState) return;

                    this.windowState = state ? state : (this.windowState === 'open' ? 'close' : 'open');
                    this.$el.setAttribute('data-window-state', this.windowState);
                },
                toggleView(view) {
                    if (view === this.currentView) return;

                    if (view === '<') {
                        const lastView = this.prevViews.pop();

                        this.currentView = lastView || 'welcome';
                    } else {
                        this.prevViews = this.prevViews.filter(v => v !== view);

                        if (this.prevViews.at(-1) !== this.currentView) {
                            this.prevViews.push(this.currentView);
                        }

                        this.currentView = view;
                    }

                    if (this.currentView === 'conversations-list' && !this.conversations.length) {
                        this.startNewConversation();
                    }
                },
                onMessageFieldHitEnter(event) {
                    if (!event.shiftKey) {
                        this.onSendMessage();
                    } else {
                        event.target.value += '\n';
                        event.target.scrollTop = event.target.scrollHeight
                    };
                },
                async getSession() {
                    this.fetching = true;

                    const res = await fetch('{{ isset($routes) ? $routes['getSession'] : '' }}');
                    const data = await res.json();

                    if (!res.ok) {
                        console.error(data);
                        return this.fetching = false;
                    }

                    this.conversations = data.conversations;

                    this.activeChatbot = data.data;

                    if (this.conversations.length) {
                        @if (\App\Helpers\Classes\MarketplaceHelper::isRegistered('chatbot-agent') && isset($chatbot) && isset($session))
                            await this.initAbly();
                        @endif
                    }

                    this.fetching = false;
                },
                async startNewConversation() {
                    // Check GDPR consent first
                    @if (!$is_editor)
                        if (this.activeChatbot?.gdpr_enabled && !this.gdprConsented) {
                            this.pendingConversationStart = true;
                            this.showGdprModal = true;
                            return;
                        }
                    @endif

                    this.showConnectButtonsStepOne = true;
                    this.showConnectButtonsStepTwo = false;

                    this.showConnectButtons = true;
                    this.connectingToAgent = false;

                    @if ($is_editor)
                        return this.toggleView('conversation-messages');
                    @else

                        this.fetching = true;

                        this.assistantMessageBubbles = {};

                        const res = await fetch(`{{ isset($routes) ? $routes['conversations'] : '' }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                prompt: this.activeChatbot.bubble_message
                            })
                        });

                        const data = await res.json();

                        if (!res.ok) {
                            if (this.ablyChannel && this.ablyInstance) {
                                this.ablyChannel.unsubscribe();
                                this.ablyChannel = null;
                            }

                            @if (\App\Helpers\Classes\MarketplaceHelper::isRegistered('chatbot-agent') && isset($chatbot) && isset($session))
                                await this.initAbly();
                            @endif

                            return this.fetching = false;
                        }

                        this.conversations.push(data.data);

                        await this.$nextTick();

                        this.openConversation(data.data.id);

                        this.toggleView('conversation-messages');

                        this.fetching = false;

                        @if (\App\Helpers\Classes\MarketplaceHelper::isRegistered('chatbot-agent') && isset($chatbot) && isset($session))
                            if (this.ablyChannel && this.ablyInstance) {
                                this.ablyChannel.unsubscribe();
                                // this.ablyInstance.close();
                                this.ablyChannel = null;
                            }
                            await this.initAbly();
                        @endif
                    @endif
                },
                async openConversation(conversationId) {

                    @if ($is_editor)
                        return this.toggleView('conversation-messages');
                    @else
                        this.fetching = true;

                        this.assistantMessageBubbles = {};

                        this.activeConversationData = this.conversations.find(conversation => conversation.id === conversationId);

                        // this.connect_agent_at = this.activeConversationData?.connect_agent_at;

                        this.connect_agent_at = null;

                        this.activeConversation = this.activeConversationData?.id;

                        let connectToAgentStore = localStorage.getItem('connectToAgentStore:' + this.activeConversationData?.id) ?? null;

                        if (connectToAgentStore === 'on') {
                            this.showConnectButtonsStepOne = false;
                            this.showConnectButtonsStepTwo = false;

                            this.showConnectButtons = false;
                        } else {
                            this.showConnectButtonsStepOne = true;
                            this.showConnectButtonsStepTwo = false;

                            this.showConnectButtons = true;
                        }

                        if (!this.activeConversation) {
                            console.error('{{ __('Conversation not found') }}');
                            return this.fetching = false;
                        }

                        const res = await fetch(`{{ isset($routes) ? $routes['conversations'] : '' }}/${conversationId}/messages`, {
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-timezone': Intl.DateTimeFormat().resolvedOptions().timeZone
                            },
                        });
                        const data = await res.json();

                        if (!res.ok) {
                            console.error(data);
                            return this.fetching = false;
                        }

                        this.messages = data.data.reverse();

                        await this.$nextTick();

                        this.toggleView('conversation-messages');

                        this.scrollMessagesToBottom();

                        this.fetching = false;
                    @endif
                },
                async onFileSelect(event) {
                    @if ($app_is_demo)
                        return this.setWidgetStatus({
                            type: 'error',
                            message: '{{ __('This feature is disabled in demo version.') }}'
                        });
                    @else
                        const input = event.target;
                        const files = input.files;
                        const formData = new FormData();

                        formData.append("media", files[0]);

                        this.uploading = true;

                        const response = await fetch(`{{ isset($routes) ? $routes['conversations'] : '' }}/${this.activeConversation}/file`, {
                            method: "POST",
                            headers: {
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        this.uploading = false;

                        const data = await response.json();
                        const mediaData = data.data;

                        if (this.$refs.mediaInput) {
                            this.$refs.mediaInput.value = null;
                        }

                        if (!mediaData?.media_name && !mediaData?.media_url) {
                            this.setWidgetStatus({
                                type: 'error',
                                message: data.message ?? '{{ __('Something went wrong. Please try again.') }}'
                            });

                            return console.error('{{ __('Something went wrong. Please try again.') }}', data);
                        }

                        const newUserMessage = {
                            id: new Date().getTime(),
                            message: mediaData.message,
                            media_name: mediaData.media_name,
                            media_url: mediaData.media_url,
                            role: 'user',
                            created_at: new Date().toISOString()
                        };

                        this.messages.push(newUserMessage);

                        this.scrollMessagesToBottom();
                    @endif
                },
                async onSendMessage() {
                    const messageString = this.$refs.message.value.trim();
                    const mediaFiles = this.$refs.mediaInput?.files;

                    if (!messageString && !mediaFiles?.length) return;

                    @if (!$is_editor)
                        // Check if Sales Agent should handle this message (during purchase flow)
                        if (window.SalesAgent && window.SalesAgent.handlePurchaseMessage && window.SalesAgent.handlePurchaseMessage(messageString)) {
                            // Message was handled by Sales Agent, don't send to AI
                            this.$refs.message.value = '';
                            this.$refs.mediaInput && (this.$refs.mediaInput.value = null);
                            this.$refs.sendBtn.classList.remove('active');
                            return;
                        }
                    @endif

                    this.$refs.message.value = '';
                    this.$refs.mediaInput && (this.$refs.mediaInput.value = null);

                    this.$refs.sendBtn.classList.remove('active');

                    // reset show connect agent buttons on previous messages
                    this.messages.forEach(message => message.showConnectButtons = false);

                    @if ($is_editor)
                        const newUserMessage = {
                            id: new Date().getTime(),
                            message: messageString,
                            role: 'user',
                            created_at: new Date().toISOString()
                        };

                        this.messages.push(newUserMessage);

                        const loaderMessage = {
                            role: 'loader',
                            id: `response-for-${newUserMessage.id}`,
                            created_at: new Date().toISOString()
                        };

                        this.messages.push(loaderMessage);

                        this.scrollMessagesToBottom();

                        // echo the message in editor
                        const timeout = setTimeout(() => {
                            this.onReceiveMessage({
                                data: {
                                    id: new Date().getTime(),
                                    message: messageString,
                                    role: 'assistant',
                                    created_at: new Date().toISOString()
                                }
                            }, loaderMessage);

                            clearTimeout(timeout);
                        }, 300);
                    @else
                        if (!this.activeConversation) {
                            console.error('No active conversation');
                            return;
                        }

                        const conversation = this.conversations.find(conversation => conversation.id === this.activeConversation);

                        const newUserMessage = {
                            id: new Date().getTime(),
                            message: messageString,
                            role: 'user',
                            created_at: new Date().toISOString()
                        };

                        this.messages.push(newUserMessage);

                        let loaderMessage = null;

                        if (conversation.connect_agent_at == null) {
                            loaderMessage = {
                                role: 'loader',
                                id: `response-for-${newUserMessage.id}`,
                                created_at: new Date().toISOString()
                            };

                            this.messages.push(loaderMessage);
                        }

                        this.scrollMessagesToBottom();

                        const res = await fetch(`{{ isset($routes) ? $routes['conversations'] : '' }}/${this.activeConversation}/messages`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-timezone': Intl.DateTimeFormat().resolvedOptions().timeZone
                            },
                            body: JSON.stringify({
                                prompt: messageString,
                            })
                        });

                        if (!res.ok) {
                            if (loaderMessage == null) {
                                const loaderMessage = {
                                    role: 'loader',
                                    id: `response-for-${newUserMessage.id}`,
                                    created_at: new Date().toISOString()
                                };

                                this.messages.push(loaderMessage);
                            }

                            const errorData = await res.json();

                            this.messages = this.messages.filter(msg => msg.id !== loaderMessage.id);
                            this.messages.push({
                                id: new Date().getTime(),
                                message: errorData.message ||
                                    '{{ __('Sorry, I could not process your request at the moment. Please try again later.') }}',
                                role: 'assistant',
                                created_at: new Date().toISOString()
                            });

                            this.scrollMessagesToBottom();

                            return;
                        }

                        const data = await res.json();

                        conversation.last_message = newUserMessage.message;
                        if (conversation.updated_at) {
                            conversation.updated_at = new Date().toISOString();
                        } else if (conversation.created_at) {
                            conversation.created_at = new Date().toISOString();
                        }

                        if (loaderMessage != null) {
                            this.onReceiveMessage(data, loaderMessage);
                        }

                        // Handle negotiation if triggered
                        if (data.negotiation_triggered && window.NegotiationHandler) {
                            const couponHtml = await window.NegotiationHandler.handleNegotiationTrigger(data);
                            if (couponHtml) {
                                // Add coupon message after AI response
                                this.messages.push({
                                    id: 'coupon-' + new Date().getTime(),
                                    message: couponHtml,
                                    role: 'assistant',
                                    created_at: new Date().toISOString(),
                                    is_coupon: true
                                });
                                this.scrollMessagesToBottom();
                            }
                        }

                        @if (isset($chatbot))
                            let condition = '{{ $chatbot->interaction_type->value }}';

                            if (
                                conversation.connect_agent_at == null &&
                                condition === '{{ \App\Extensions\Chatbot\System\Enums\InteractionType::SMART_SWITCH }}'
                            ) {
                                this.showConnectButtons = true;
                                document.addEventListener('typing-done', this.onTypingDone)
                            }
                        @endif
                    @endif
                },
                onTypingDone(event) {
                    const {
                        messageObj
                    } = event.detail;

                    let existingConnectAgentMessage = null;
                    let existingCollectEmailMessage = null;
                    let userMessages = [];

                    this.messages.forEach(message => {
                        if (message.showConnectButtons) {
                            return existingConnectAgentMessage = message;
                        }

                        if (message.role === 'collect-email') {
                            return existingCollectEmailMessage = message;
                        }

                        if (message.role === 'user') {
                            return userMessages.push(message)
                        }
                    });

                    // Show collect email message first
                    if (
                        !existingConnectAgentMessage &&
                        !existingCollectEmailMessage &&
                        userMessages.length &&
                        this.activeChatbot.showCollectEmail
                    ) {
                        this.messages.push({
                            id: 'collect-email',
                            role: 'collect-email',
                            created_at: Date.now()
                        });

                        this.activeChatbot.showCollectEmail = false;

                        this.$nextTick(() => {
                            this.$refs.routesView.scrollTo({
                                top: this.$refs.routesView.scrollTop + 44,
                                behavior: 'smooth'
                            });
                        });
                    }

                    // Show connect agent message
                    if (
                        userMessages.length &&
                        messageObj.showConnectButtonsWhenTypingDone &&
                        localStorage.getItem('connectToAgentStore:' + this.activeConversationData?.id) !== 'off'
                    ) {
                        messageObj.showConnectButtons = true;

                        this.$nextTick(() => {
                            this.$refs.routesView.scrollTo({
                                top: this.$refs.routesView.scrollTop + 44,
                                behavior: 'smooth'
                            });
                        });
                    }

                    // Connect agent immediately if needed
                    if (
                        userMessages.length &&
                        messageObj.connectToHumanAgentDirectlyWhenTypingDone
                    ) {
                        this.connectToAgent();
                    }

                    document.removeEventListener('typing-done', this.onTypingDone)
                },
                async onReceiveMessage(data, loaderMessage) {
                    const message = data.data;
                    const messageToReplace = this.messages.find(msg => msg.id === loaderMessage.id);

                    messageToReplace.role = 'assistant';
                    messageToReplace.message = message.message;
                    messageToReplace.created_at = new Date().toISOString();
                    messageToReplace.isNew = true;
                    messageToReplace.showConnectButtonsWhenTypingDone = data.needs_human;
                    messageToReplace.connectToHumanAgentDirectlyWhenTypingDone = data.needs_human_direct;

                    if (data.collect_email) {
                        this.activeChatbot.showCollectEmail = true;
                    }

                    this.playBubbleSound();

                    this.scrollMessagesToBottom();
                    
                    @if (!$is_editor)
                        // ✨ ORQUESTADOR: Integrar con Sales Agent Component
                        if (data.orchestration && data.orchestration.agents_activated && window.SalesAgent) {
                            setTimeout(() => {
                                console.log('🎯 Orquestador Backend: Procesando agentes activados...');
                                
                                // Buscar Sales Agent en los agentes activados
                                const salesAgent = data.orchestration.agents_activated.find(
                                    agent => agent.agent_type === 'sales'
                                );
                                
                                if (salesAgent && salesAgent.data && salesAgent.data.show_product_grid && salesAgent.data.products) {
                                    console.log('✅ Sales Agent activado con', salesAgent.data.total_products, 'productos del backend');
                                    console.log('   Productos:', salesAgent.data.products);
                                    
                                    // Inyectar productos del backend en el Sales Agent Component
                                    window.SalesAgent.products = salesAgent.data.products;
                                    window.SalesAgent.productsLoaded = true;
                                    
                                    const assistantMessages = document.querySelectorAll('.lqd-ext-chatbot-window-conversation-message[data-type="assistant"]');
                                    if (assistantMessages.length > 0) {
                                        const lastMessageEl = assistantMessages[assistantMessages.length - 1];
                                        const contentWrap = lastMessageEl.querySelector('.lqd-ext-chatbot-window-conversation-message-content-wrap');
                                        
                                        if (contentWrap) {
                                            // Usar el método del Sales Agent Component para renderizar con botones de compra
                                            window.SalesAgent.enhanceMessageWithProducts(contentWrap, messageToReplace.message);
                                            console.log('✅ Productos renderizados con flujo de compra integrado');
                                            
                                            // Asegurar que los event listeners estén registrados
                                            setTimeout(() => {
                                                const buyButtons = contentWrap.querySelectorAll('.product-buy-btn');
                                                console.log('🔍 Botones de compra encontrados:', buyButtons.length);
                                                buyButtons.forEach((btn, idx) => {
                                                    const productId = parseInt(btn.dataset.productId);
                                                    console.log(`   Botón ${idx + 1}: Product ID ${productId}`);
                                                    
                                                    // Verificar que el producto existe
                                                    const product = window.SalesAgent.products.find(p => p.id === productId);
                                                    if (product) {
                                                        console.log(`   ✅ Producto encontrado: ${product.name}`);
                                                    } else {
                                                        console.error(`   ❌ Producto ${productId} NO encontrado en array`);
                                                        console.log('   IDs disponibles:', window.SalesAgent.products.map(p => p.id));
                                                    }
                                                });
                                            }, 500);
                                        }
                                    }
                                } else {
                                    console.log('ℹ️ Sales Agent no activado o sin productos para mostrar');
                                }
                            }, 1500);
                        }
                        
                        // Sales Agent: Make product links functional (inline purchase flow)
                        if (window.SalesAgent && window.SalesAgent.enabled) {
                            // Wait for message to be rendered
                            setTimeout(() => {
                                console.log('🔍 Looking for product links in assistant messages...');
                                const assistantMessages = document.querySelectorAll('.lqd-ext-chatbot-window-conversation-message[data-type="assistant"]');
                                console.log(`   - Found ${assistantMessages.length} assistant messages`);
                                
                                if (assistantMessages.length > 0) {
                                    const lastMessage = assistantMessages[assistantMessages.length - 1];
                                    
                                    // Try multiple selectors to find product links
                                    let productLinks = lastMessage.querySelectorAll('a[href*="/producto"]');
                                    if (productLinks.length === 0) {
                                        productLinks = lastMessage.querySelectorAll('a[href*="aliviate"]');
                                    }
                                    if (productLinks.length === 0) {
                                        // Find all links in the message
                                        productLinks = lastMessage.querySelectorAll('a[href]');
                                    }
                                    
                                    console.log(`   - Found ${productLinks.length} links total`);
                                    
                                    productLinks.forEach((link, idx) => {
                                        console.log(`   - Link ${idx + 1}: ${link.textContent} -> ${link.href}`);
                                        
                                        // Store original href and remove it to prevent navigation
                                        const originalHref = link.href;
                                        link.dataset.originalHref = originalHref;
                                        link.removeAttribute('href');
                                        
                                        // Make it look like a link still
                                        link.style.color = 'inherit';
                                        link.style.textDecoration = 'underline';
                                        link.style.cursor = 'pointer';
                                        
                                        // Add multiple event listeners for maximum compatibility
                                        const handleClick = (e) => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            e.stopImmediatePropagation();
                                            console.log('🛒 Product link clicked!');
                                            console.log('   - Text:', link.textContent);
                                            console.log('   - Original URL:', originalHref);
                                            window.SalesAgent.startPurchaseFromLink(link);
                                            return false;
                                        };
                                        
                                        link.addEventListener('click', handleClick, true); // Capture phase
                                        link.addEventListener('touchstart', handleClick, { passive: false });
                                        link.onclick = handleClick; // Fallback
                                    });
                                    
                                    if (productLinks.length > 0) {
                                        console.log(`✅ Made ${productLinks.length} product links functional`);
                                    } else {
                                        console.log('⚠️ No product links found in message');
                                    }
                                }
                            }, 2000); // Increased timeout
                        }
                    @endif
                },
                scrollMessagesToBottom(smooth = false) {
                    this.$nextTick(() => {
                        this.$refs.routesView.scrollTo({
                            top: this.$refs.routesView.scrollHeight,
                            behavior: smooth ? 'smooth' : 'auto'
                        });
                    })
                },
                addMessage(message, el) {
                    const formattedMessage = this.getFormattedString(message);
                    const index = el.getAttribute('data-index');
                    const useTypeEffect = this.messages.find((msg, i) => i == index && msg.role === 'assistant' && msg.isNew);

                    this.assistantMessageBubbles[index] = el;

                    if (useTypeEffect) {
                        return this.typeMessage(this.messages[index], index);
                    }

                    return formattedMessage;
                },
                typeMessage(messageObj, bubbleMessageIndex) {
                    const messageEl = this.assistantMessageBubbles[bubbleMessageIndex];
                    const messageString = messageObj.message;

                    if (!messageEl || !messageString) return '';

                    const formattedMessage = this.getFormattedString(messageString);

                    let i = 0;
                    const speed = 25; // typing speed in milliseconds
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = formattedMessage;
                    const textContent = tempDiv.innerHTML || '';
                    messageObj.isNew = false;

                    const splitUnit = this.isJP(textContent) ? '' : ' ';
                    const units = textContent.split(splitUnit);

                    if (units.length === 1) {
                        messageEl.innerHTML = tempDiv.innerHTML;

                        messageObj.isTyping = false;

                        this.$dispatch('typing-done', {
                            messageObj,
                            bubbleMessageIndex
                        });

                        return tempDiv.innerHTML;
                    }

                    const typeWriter = () => {
                        if (i < units.length) {
                            messageEl.innerHTML = units.slice(0, i + 1).join(splitUnit) + (splitUnit);
                            i++;
                            setTimeout(typeWriter, speed);
                            this.scrollMessagesToBottom();
                        } else {
                            messageObj.isTyping = false;

                            this.$dispatch('typing-done', {
                                messageObj,
                                bubbleMessageIndex
                            });
                        }
                    };

                    messageObj.isTyping = true;

                    typeWriter();
                },
                getFormattedString(string) {
                    if (!('markdownit' in window) || !string) return '';

                    string
                        .replace(/>(\s*\r?\n\s*)</g, '><')
                        .replace(/\n(?!.*\n)/, '');

                    const renderer = window.markdownit({
                        html: true,
                        breaks: true,
                        highlight: (str, lang) => {
                            const language = lang && lang !== '' ? lang : 'md';
                            const codeString = str;

                            if (Prism.languages[language]) {
                                const highlighted = Prism.highlight(codeString, Prism.languages[language], language);
                                return `<pre class="language-${language}"><code data-lang="${language}" class="language-${language}">${highlighted}</code></pre>`;
                            }

                            return codeString;
                        }
                    });

                    renderer.use(function(md) {
                        md.core.ruler.after('inline', 'convert_elements', function(
                            state) {
                            state.tokens.forEach(function(blockToken) {
                                if (blockToken.type !== 'inline') return;

                                let fullContent = '';

                                blockToken.children.forEach(token => {
                                    let {
                                        content,
                                        type
                                    } = token;

                                    switch (type) {
                                        case 'link_open':
                                            token.attrs?.push(['target', '_blank']);
                                            content =
                                                `<a ${token.attrs.map(([key, value]) => `${key}="${value}"`).join(' ')}>`;
                                            break;
                                        case 'link_close':
                                            content = '</a>';
                                            break;
                                    }

                                    fullContent += content;
                                });

                                if (fullContent.includes('<ol>') ||
                                    fullContent.includes('<ul>')) {
                                    const listToken = new state.Token('html_inline', '', 0);
                                    listToken.content = fullContent.trim();
                                    listToken.markup = 'html';
                                    listToken.type = 'html_inline';

                                    blockToken.children = [listToken];
                                }
                            });
                        });

                        md.core.ruler.after('inline', 'convert_links', function(state) {
                            state.tokens.forEach(function(blockToken) {
                                if (blockToken.type !== 'inline') return;
                                blockToken.children.forEach(function(token, idx) {
                                    const {
                                        content
                                    } = token;
                                    if (content.includes('<a ')) {
                                        const linkRegex = /(.*)(<a\s+[^>]*\s+href="([^"]+)"[^>]*>([^<]*)<\/a>?)(.*)/;
                                        const linkMatch = content.match(linkRegex);

                                        if (linkMatch) {
                                            const [, before, , href, text, after] = linkMatch;

                                            const beforeToken = new state.Token('text', '', 0);
                                            beforeToken.content = before;

                                            const newToken = new state.Token('link_open', 'a', 1);
                                            newToken.attrs = [
                                                ['href', href],
                                                ['target', '_blank']
                                            ];
                                            const textToken = new state.Token('text', '', 0);
                                            textToken.content = text;
                                            const closingToken = new state.Token('link_close', 'a', -1);

                                            const afterToken = new state.Token('text', '', 0);
                                            afterToken.content = after;

                                            blockToken.children
                                                .splice(idx, 1, beforeToken, newToken, textToken, closingToken, afterToken);
                                        }
                                    }
                                });
                            });
                        });
                    });

                    return renderer.render(renderer.utils.unescapeAll(string));
                },
                isJP(string) {
                    return /[\u3040-\u30ff\u3400-\u4dbf\u4e00-\u9fff\uF900-\uFAFF]/.test(string);
                },
                getTimeLabel(time) {
                    const diff = Math.floor((new Date() - new Date(time ?? Date.now())) / 1000);

                    return (
                        diff < 60 ? '{{ __('just now') }}' :
                        diff < 3600 ? (Math.floor(diff / 60) === 1 ? '1 {{ __('minute ago') }}' : Math.floor(diff / 60) +
                            ' {{ __('minutes ago') }}') :
                        diff < 86400 ? (Math.floor(diff / 3600) === 1 ? '1 {{ __('hour ago') }}' : Math.floor(diff / 3600) +
                            ' {{ __('hours ago') }}') :
                        Math.floor(diff / 86400) === 1 ? '1 {{ __('day ago') }}' : Math.floor(diff / 86400) + ' {{ __('days ago') }}'
                    )
                },
                getViewLabel() {
                    let viewName;

                    switch (this.currentView) {
                        case 'conversations-list':
                            viewName = 'Recent Messages'
                            break;
                        case 'contact-form':
                            viewName = 'Send email'
                            break;
                        case 'thanks':
                            viewName = 'Thanks!'
                            break;
                        case 'articles-list':
                        case 'article-show':
                            viewName = 'Help Center'
                            break;
                    }

                    return viewName;
                },
                async onArticlesSearch(searchString) {
                    const string = searchString.trim();

                    if (!this.originalArticles) {
                        this.originalArticles = this.articles;
                    }

                    this.searchingArticles = true;

                    if (!string) {
                        this.searchingArticles = false;
                        this.articles = this.originalArticles;

                        return;
                    };

                    @if ($is_editor)
                        const filteredArticles = this.originalArticles.filter(article =>
                            article.title.toLowerCase().includes(string.toLowerCase()) ||
                            article.excerpt.toLowerCase().includes(string.toLowerCase())
                        );

                        this.articles = filteredArticles;

                        this.searchingArticles = false;
                    @else
                        const res = await fetch(`{{ isset($routes) ? $routes['articles'] : '' }}?search=${string}`, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();

                        this.articles = data;

                        this.searchingArticles = false;
                    @endif
                },
                async showArticle(articleId) {
                    if (articleId == null) return;

                    this.fetching = true;

                    const res = await fetch(`{{ isset($routes) ? $routes['articles'] : '' }}/${articleId}/show`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();

                    this.fetching = false;

                    const article = data[0];

                    if (!article?.id) {
                        this.setWidgetStatus({
                            type: 'error',
                            message: '{{ __('Could not find the article') }}'
                        });

                        this.toggleView('welcome');

                        return;
                    }

                    this.showingArticle = article;

                    this.$nextTick(() => this.toggleView('article-show'))
                },
                onContactFormSubmit(event) {
                    const form = event.target;
                    const formData = new FormData(form);

                    fetch(`{{ isset($routes) ? $routes['send-email'] : '' }}`, {
                        method: 'POST',
                        body: formData
                    });

                    this.toggleView('thanks');
                },
                setWidgetStatus(opts = {}) {
                    this.widgetStatusTimeout && clearTimeout(this.widgetStatusTimeout);

                    const options = {
                        type: 'success',
                        message: '',
                        timeout: 4000,
                        ...opts,
                    };

                    this.widgetStatus = {
                        type: options.type,
                        message: options.message,
                    };

                    this.widgetStatusTimeout = setTimeout(() => {
                        this.widgetStatus = {
                            type: null,
                            message: null
                        };
                        this.widgetStatusTimeout && clearTimeout(this.widgetStatusTimeout);
                    }, options.timeout);
                },
                async collectEmail(event) {
                    const form = event.target;
                    const formData = new FormData(form);

                    if (this.collectingEmail) return;

                    this.collectingEmail = true;

                    const response = await fetch(`{{ isset($routes) ? $routes['collect-email'] : '' }}`, {
                        method: "POST",
                        headers: {
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    this.collectingEmail = false;

                    const data = await response.json();

                    if (!data.email) {
                        return this.setWidgetStatus({
                            type: 'error',
                            message: '{{ __('Something went wrong. Please try again.') }}'
                        });
                    }

                    this.setWidgetStatus({
                        type: 'success',
                        message: data.message ?? '{{ __('Email collected successfully.') }}'
                    });

                    this.messages = this.messages.filter(message => message.role === 'collect-email');
                },

                async toggleSound(event) {
                    const checkboxEl = event.currentTarget;
                    const soundEnabled = checkboxEl.checked;

                    @if ($is_editor)
                        this.soundEnabled = soundEnabled;
                    @elseif (isset($chatbot))
                        const formData = new FormData();

                        formData.append('enabled_sound', soundEnabled);

                        const res = await fetch('{{ $routes['enable-sound'] }}', {
                            method: "POST",
                            headers: {
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const data = await res.json();

                        this.soundEnabled = soundEnabled;
                    @endif
                },

                async acceptGdprConsent() {
                    @if ($is_editor)
                        this.gdprConsented = true;
                        this.showGdprModal = false;
                        if (this.pendingConversationStart) {
                            this.pendingConversationStart = false;
                            this.startNewConversation();
                        }
                    @else
                        this.gdprProcessing = true;

                        try {
                            const res = await fetch('{{ isset($routes) ? $routes['gdpr-consent'] : '' }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    consent: true
                                })
                            });

                            const data = await res.json();

                            if (res.ok) {
                                this.gdprConsented = true;
                                this.showGdprModal = false;

                                if (this.pendingConversationStart) {
                                    this.pendingConversationStart = false;
                                    await this.startNewConversation();
                                }
                            } else {
                                console.error('GDPR consent error:', data);
                                this.setWidgetStatus({
                                    type: 'error',
                                    message: data.message || '{{ __('Error saving consent. Please try again.') }}'
                                });
                            }
                        } catch (error) {
                            console.error('GDPR consent error:', error);
                            this.setWidgetStatus({
                                type: 'error',
                                message: '{{ __('Error saving consent. Please try again.') }}'
                            });
                        } finally {
                            this.gdprProcessing = false;
                        }
                    @endif
                },

                async rejectGdprConsent() {
                    @if ($is_editor)
                        this.showGdprModal = false;
                        this.pendingConversationStart = false;
                    @else
                        if (this.activeChatbot?.gdpr_required) {
                            // If consent is required, just close and don't proceed
                            this.showGdprModal = false;
                            this.pendingConversationStart = false;
                            return;
                        }

                        this.gdprProcessing = true;

                        try {
                            const res = await fetch('{{ isset($routes) ? $routes['gdpr-consent'] : '' }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    consent: false
                                })
                            });

                            this.showGdprModal = false;
                            this.pendingConversationStart = false;
                        } catch (error) {
                            console.error('GDPR consent error:', error);
                        } finally {
                            this.gdprProcessing = false;
                        }
                    @endif
                },

                closeGdprModal() {
                    if (!this.activeChatbot?.gdpr_required) {
                        this.showGdprModal = false;
                        this.pendingConversationStart = false;
                    }
                }
            }));
        });
    })();
</script>
