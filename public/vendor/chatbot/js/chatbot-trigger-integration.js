/**
 * Chatbot Trigger Integration
 * This file handles the integration between the chatbot widget and proactive triggers
 */

// Ensure the integration runs after the chatbot is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Alpine.js to be ready
    document.addEventListener('alpine:init', () => {
        // Extend the externalChatbot component with trigger functionality
        Alpine.data('externalChatbotWithTriggers', (baseComponent) => ({
            ...baseComponent,
            triggerEngine: null,
            
            init() {
                // Call the original init method
                if (baseComponent.init) {
                    baseComponent.init.call(this);
                }
                
                // Initialize triggers if not in editor mode
                if (!this.isEditor) {
                    this.initProactiveTriggers();
                }
            },
            
            async initProactiveTriggers() {
                try {
                    // Set global reference for trigger engine
                    window.chatbotWidget = this;
                    
                    // Load trigger dependencies
                    await this.loadTriggerDependencies();
                    
                    // Initialize trigger engine
                    if (window.ProactiveTriggerEngine && this.chatbotUuid) {
                        this.triggerEngine = new window.ProactiveTriggerEngine(this.chatbotUuid, {
                            apiEndpoint: '/api/v2/chatbot/triggers',
                            debug: window.location.hostname === 'localhost'
                        });
                        
                        console.log('✅ Proactive Trigger Engine initialized for chatbot:', this.chatbotUuid);
                    }
                } catch (error) {
                    console.warn('⚠️ Failed to initialize proactive triggers:', error);
                }
            },
            
            async loadTriggerDependencies() {
                const baseUrl = this.getBaseUrl();
                
                // CSS
                await this.loadCSS(`${baseUrl}/vendor/chatbot/css/proactive-triggers.css`);
                
                // JavaScript files in order
                const scripts = [
                    'user-session-tracker.js',
                    'trigger-manager.js',
                    'trigger-analytics.js',
                    'proactive-trigger-engine.js'
                ];
                
                for (const script of scripts) {
                    await this.loadScript(`${baseUrl}/vendor/chatbot/js/${script}`);
                }
            },
            
            getBaseUrl() {
                // Try to get base URL from various sources
                if (window.location.origin) {
                    return window.location.origin;
                }
                
                // Fallback
                return window.location.protocol + '//' + window.location.host;
            },
            
            loadCSS(href) {
                return new Promise((resolve) => {
                    if (document.querySelector(`link[href="${href}"]`)) {
                        resolve();
                        return;
                    }
                    
                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = href;
                    link.onload = resolve;
                    link.onerror = resolve; // Don't fail if CSS doesn't load
                    document.head.appendChild(link);
                });
            },
            
            loadScript(src) {
                return new Promise((resolve, reject) => {
                    if (document.querySelector(`script[src="${src}"]`)) {
                        resolve();
                        return;
                    }
                    
                    const script = document.createElement('script');
                    script.src = src;
                    script.onload = resolve;
                    script.onerror = () => {
                        console.warn(`Failed to load script: ${src}`);
                        resolve(); // Don't fail the whole process
                    };
                    document.head.appendChild(script);
                });
            },
            
            // Enhanced methods for trigger integration
            open() {
                if (this.toggleWindowState) {
                    this.toggleWindowState();
                }
            },
            
            sendMessage(message) {
                if (!message) return;
                
                // If chatbot is closed, open it first
                if (this.windowState === 'close') {
                    this.open();
                }
                
                // Wait for chatbot to be ready
                setTimeout(() => {
                    this.sendMessageWhenReady(message);
                }, 300);
            },
            
            sendMessageWhenReady(message) {
                // Try to find message input
                const messageInput = document.querySelector('.lqd-ext-chatbot-message-input input, .lqd-ext-chatbot-message-input textarea');
                
                if (messageInput && this.onSendMessage) {
                    messageInput.value = message;
                    
                    // Trigger send message
                    const event = new Event('submit', { bubbles: true, cancelable: true });
                    const form = messageInput.closest('form');
                    
                    if (form) {
                        form.dispatchEvent(event);
                    } else {
                        // Fallback: call onSendMessage directly
                        this.onSendMessage({ target: { elements: { message: { value: message } } } });
                    }
                } else if (!this.activeConversation) {
                    // Start new conversation with message
                    this.startConversationWithMessage(message);
                }
            },
            
            async startConversationWithMessage(message) {
                try {
                    // Start conversation
                    if (this.startConversation) {
                        await this.startConversation();
                    }
                    
                    // Wait and send message
                    setTimeout(() => {
                        this.sendMessageWhenReady(message);
                    }, 1000);
                } catch (error) {
                    console.error('Failed to start conversation with message:', error);
                }
            },
            
            isReady() {
                return !this.fetching && this.windowState !== undefined;
            },
            
            // Trigger-specific methods
            showDiscountModal(discount) {
                if (discount && discount.code) {
                    // Copy to clipboard if available
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(discount.code);
                    }
                    
                    // Show discount message
                    const message = `¡Tengo un código de descuento para ti! Usa el código ${discount.code} para obtener ${discount.amount} de descuento.`;
                    this.sendMessage(message);
                }
            },
            
            showProductRecommendations(products) {
                if (products && products.length > 0) {
                    const productNames = products.slice(0, 2).map(p => p.name).join(' y ');
                    const message = `Te recomiendo estos productos que podrían interesarte: ${productNames}. ¿Te gustaría saber más?`;
                    this.sendMessage(message);
                }
            },
            
            startConsultation(type = 'general') {
                const messages = {
                    skincare: 'Me gustaría una consulta personalizada de skincare',
                    general: 'Me gustaría una consulta personalizada'
                };
                
                this.sendMessage(messages[type] || messages.general);
            }
        }));
    });
});

// Auto-initialize when embedded in iframe
if (window.self !== window.top) {
    // We're in an iframe, likely embedded
    document.addEventListener('alpine:initialized', () => {
        // Find the chatbot element and enhance it
        const chatbotElement = document.querySelector('[x-data*="externalChatbot"]');
        if (chatbotElement && window.Alpine) {
            // The chatbot should already be initialized with triggers via the PHP template
            console.log('🤖 Chatbot with triggers ready in iframe');
        }
    });
}

// Global helper for external sites
window.initChatbotTriggers = function(chatbotUuid, options = {}) {
    if (window.ProactiveTriggerEngine) {
        const engine = new window.ProactiveTriggerEngine(chatbotUuid, {
            apiEndpoint: '/api/v2/chatbot/triggers',
            debug: options.debug || false,
            ...options
        });
        
        console.log('🚀 External chatbot triggers initialized');
        return engine;
    } else {
        console.warn('⚠️ ProactiveTriggerEngine not loaded');
        return null;
    }
};