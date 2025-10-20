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

            // Create trigger bubble
            const bubble = document.createElement('div');
            bubble.className = 'lqd-ext-chatbot-proactive-trigger';
            bubble.innerHTML = `
                <div class="lqd-ext-chatbot-proactive-trigger-content">
                    <button class="lqd-ext-chatbot-proactive-trigger-close" onclick="this.parentElement.parentElement.remove()">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                            <path d="M14 1.41L12.59 0L7 5.59L1.41 0L0 1.41L5.59 7L0 12.59L1.41 14L7 8.41L12.59 14L14 12.59L8.41 7L14 1.41Z"/>
                        </svg>
                    </button>
                    <p>${trigger.message}</p>
                </div>
            `;

            // Add styles if not already added
            if (!document.getElementById('proactive-trigger-styles')) {
                const style = document.createElement('style');
                style.id = 'proactive-trigger-styles';
                style.textContent = `
                    .lqd-ext-chatbot-proactive-trigger {
                        position: fixed;
                        bottom: 130px;
                        right: 20px;
                        max-width: 300px;
                        background: white;
                        border-radius: 16px;
                        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
                        padding: 18px;
                        z-index: 999998;
                        animation: slideInUp 0.3s ease-out;
                        border: 1px solid #e5e7eb;
                    }
                    @keyframes slideInUp {
                        from {
                            transform: translateY(20px);
                            opacity: 0;
                        }
                        to {
                            transform: translateY(0);
                            opacity: 1;
                        }
                    }
                    .lqd-ext-chatbot-proactive-trigger-content {
                        position: relative;
                    }
                    .lqd-ext-chatbot-proactive-trigger p {
                        margin: 0;
                        color: #1f2937;
                        font-size: 14px;
                        line-height: 1.6;
                        padding-right: 24px;
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    }
                    .lqd-ext-chatbot-proactive-trigger-close {
                        position: absolute;
                        top: -4px;
                        right: -4px;
                        background: none;
                        border: none;
                        cursor: pointer;
                        color: #999;
                        padding: 4px;
                    }
                    .lqd-ext-chatbot-proactive-trigger-close:hover {
                        color: #333;
                    }
                `;
                document.head.appendChild(style);
            }

            document.body.appendChild(bubble);

            // Open chatbot when clicked
            bubble.addEventListener('click', (e) => {
                if (!e.target.closest('.lqd-ext-chatbot-proactive-trigger-close')) {
                    const triggers = document.querySelectorAll('.lqd-ext-chatbot-trigger');
                    if (triggers.length > 0) {
                        triggers[0].click();
                    }
                    bubble.remove();
                }
            });

            // Auto-hide after 15 seconds
            setTimeout(() => {
                if (bubble.parentElement) {
                    bubble.style.animation = 'slideInUp 0.3s ease-out reverse';
                    setTimeout(() => bubble.remove(), 300);
                }
            }, 15000);
        }
    };

    // Expose globally for manual initialization from external-chatbot.js
    window.ChatbotProactiveTriggers = ProactiveTriggers;
})();

