/**
 * Proactive Triggers for External Chatbot
 */
(function() {
    'use strict';
    
    const ProactiveTriggers = {
        triggers: [],
        shown: {},
        startTime: Date.now(),
        chatbotUuid: null,
        chatbotHost: null,

        init(chatbotUuid) {
            this.chatbotUuid = chatbotUuid;
            this.loadTriggers();
            this.startMonitoring();
        },

        async loadTriggers() {
            try {
                const apiUrl = `${this.chatbotHost}/api/v2/chatbot/${this.chatbotUuid}/triggers`;
                console.log('[Triggers] Loading from:', apiUrl);
                const response = await fetch(apiUrl);
                const data = await response.json();
                this.triggers = data.triggers || [];
                console.log('[Triggers] Loaded:', this.triggers.length);
            } catch (error) {
                console.error('[Triggers] Failed to load:', error);
            }
        },

        startMonitoring() {
            // Check for triggers every 5 seconds
            setInterval(() => this.checkTriggers(), 5000);

            // Exit intent detection
            document.addEventListener('mouseleave', (e) => {
                if (e.clientY < 10) {
                    this.evaluateTrigger('exit_intent');
                }
            });
        },

        checkTriggers() {
            const elapsed = (Date.now() - this.startTime) / 1000; // seconds

            this.triggers.forEach(trigger => {
                // Skip if already shown
                if (this.shown[trigger.id]) return;

                // Check time-based triggers
                if (trigger.type === 'welcome_30s' && elapsed >= 30) {
                    this.showTrigger(trigger);
                } else if (trigger.type === 'page_dwell_2min' && elapsed >= 120) {
                    this.showTrigger(trigger);
                } else if (trigger.type === 'first_visitor_discount' && elapsed >= 10) {
                    if (!localStorage.getItem('chatbot_visited_' + this.chatbotUuid)) {
                        this.showTrigger(trigger);
                        localStorage.setItem('chatbot_visited_' + this.chatbotUuid, 'true');
                    }
                }
            });
        },

        evaluateTrigger(triggerType) {
            const trigger = this.triggers.find(t => t.type === triggerType && !this.shown[t.id]);
            if (trigger) {
                this.showTrigger(trigger);
            }
        },

        showTrigger(trigger) {
            console.log('[Triggers] Showing:', trigger.type);
            this.shown[trigger.id] = true;

            // Check if chatbot is in iframe (external embed) or direct DOM
            const iframe = document.querySelector('iframe[name="lqd-ext-chatbot-iframe"]');
            const directChatbot = document.querySelector('.lqd-ext-chatbot');

            if (iframe) {
                // External embed - use postMessage
                console.log('[Triggers] External embed detected, using postMessage');
                this.showTriggerViaPostMessage(trigger, iframe);
            } else if (directChatbot) {
                // Direct DOM - inject directly
                console.log('[Triggers] Direct DOM detected, injecting directly');
                this.showTriggerDirectly(trigger, directChatbot);
            } else {
                // Wait for either to appear
                console.log('[Triggers] Chatbot not found, waiting...');
                setTimeout(() => this.showTrigger(trigger), 200);
            }
        },

        showTriggerViaPostMessage(trigger, iframe) {
            const triggerMessage = {
                id: 'trigger_' + Date.now(),
                message: trigger.message,
                role: 'assistant',
                created_at: new Date().toISOString(),
                isProactiveTrigger: true
            };

            // Send message to iframe
            iframe.contentWindow.postMessage({
                type: 'lqd-ext-chatbot-inject-trigger',
                data: triggerMessage
            }, '*');

            console.log('[Triggers] ✅ Trigger sent to iframe via postMessage:', trigger.type);

            // Open chatbot if closed
            setTimeout(() => {
                const chatbotWrap = document.querySelector('#lqd-ext-chatbot-wrap');
                const windowState = chatbotWrap?.getAttribute('data-window-state');
                if (windowState === 'close') {
                    const chatbotTrigger = document.querySelector('.lqd-ext-chatbot-trigger');
                    if (chatbotTrigger) {
                        console.log('[Triggers] Opening chatbot window');
                        chatbotTrigger.click();
                    }
                }
            }, 500);
        },

        showTriggerDirectly(trigger, chatbotEl) {
            // Check if Alpine is initialized
            if (!chatbotEl.__x || !chatbotEl.__x.$data) {
                console.log('[Triggers] Waiting for Alpine...');
                setTimeout(() => this.showTriggerDirectly(trigger, chatbotEl), 200);
                return;
            }

            const chatbotData = chatbotEl.__x.$data;

            // Check if messages array exists
            if (!chatbotData.messages || !Array.isArray(chatbotData.messages)) {
                console.log('[Triggers] Waiting for messages array...');
                setTimeout(() => this.showTriggerDirectly(trigger, chatbotEl), 200);
                return;
            }

            // Create and inject message
            const triggerMessage = {
                id: 'trigger_' + Date.now(),
                message: trigger.message,
                role: 'assistant',
                created_at: new Date().toISOString(),
                isProactiveTrigger: true
            };

            chatbotData.messages.push(triggerMessage);
            console.log('[Triggers] ✅ Message injected directly:', trigger.type);

            // Scroll and open
            setTimeout(() => {
                if (chatbotData.scrollMessagesToBottom) {
                    chatbotData.scrollMessagesToBottom();
                }
                if (chatbotData.toggleWindowState && chatbotEl.getAttribute('data-window-state') === 'close') {
                    chatbotData.toggleWindowState('open');
                }
            }, 100);
        }
    };

    // Expose globally for manual initialization from external-chatbot.js
    window.ChatbotProactiveTriggers = ProactiveTriggers;
})();

