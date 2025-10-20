/**
 * Trigger Manager
 * Manages trigger queue, priorities, and display logic
 */
class TriggerManager {
    constructor(engine) {
        this.engine = engine;
        this.activeQueue = [];
        this.displayedTriggers = new Set();
        this.triggerCooldowns = new Map();
        this.maxConcurrentTriggers = 1;
        this.defaultCooldown = 60000; // 1 minute
    }

    /**
     * Add trigger to queue
     */
    addToQueue(triggerData) {
        // Check if trigger is already displayed or in cooldown
        if (this.isInCooldown(triggerData.trigger_type)) {
            return false;
        }

        if (this.displayedTriggers.has(triggerData.trigger_id)) {
            return false;
        }

        // Add to queue with priority sorting
        this.activeQueue.push(triggerData);
        this.activeQueue.sort((a, b) => (a.priority || 5) - (b.priority || 5));

        // Process queue
        this.processQueue();
        
        return true;
    }

    /**
     * Process trigger queue
     */
    processQueue() {
        // Check if we can display more triggers
        if (this.displayedTriggers.size >= this.maxConcurrentTriggers) {
            return;
        }

        // Get next trigger from queue
        const nextTrigger = this.activeQueue.shift();
        if (!nextTrigger) {
            return;
        }

        // Display trigger
        this.displayTrigger(nextTrigger);
    }

    /**
     * Display trigger
     */
    displayTrigger(triggerData) {
        // Mark as displayed
        this.displayedTriggers.add(triggerData.trigger_id);
        
        // Set cooldown
        this.setCooldown(triggerData.trigger_type, triggerData.cooldown_minutes || 1);
        
        // Create and show trigger element
        const element = this.createTriggerElement(triggerData);
        this.showTriggerElement(element, triggerData);
        
        // Track display
        this.engine.analytics.trackTriggerDisplay(triggerData);
    }

    /**
     * Create trigger element
     */
    createTriggerElement(triggerData) {
        const element = document.createElement('div');
        element.className = 'chatbot-proactive-trigger';
        element.setAttribute('data-trigger-id', triggerData.trigger_id);
        element.setAttribute('data-trigger-type', triggerData.trigger_type);
        
        const displayConfig = triggerData.display_config || {};
        const position = displayConfig.position || 'bottom-right';
        const theme = displayConfig.theme || 'default';
        
        element.innerHTML = this.getTriggerHTML(triggerData);
        element.className += ` trigger-position-${position} trigger-theme-${theme}`;
        
        // Add event listeners
        this.addTriggerEventListeners(element, triggerData);
        
        return element;
    }

    /**
     * Get trigger HTML based on type and action
     */
    getTriggerHTML(triggerData) {
        const action = triggerData.action || 'show_message';
        
        switch (action) {
            case 'show_discount':
                return this.getDiscountTriggerHTML(triggerData);
            case 'show_product_recommendations':
                return this.getProductRecommendationHTML(triggerData);
            case 'collect_email':
                return this.getEmailCollectionHTML(triggerData);
            default:
                return this.getDefaultTriggerHTML(triggerData);
        }
    }

    /**
     * Get default trigger HTML
     */
    getDefaultTriggerHTML(triggerData) {
        return `
            <div class="trigger-content">
                <div class="trigger-avatar">
                    <div class="trigger-avatar-icon">💬</div>
                </div>
                <div class="trigger-body">
                    <div class="trigger-message">${triggerData.message}</div>
                    <div class="trigger-actions">
                        <button class="trigger-btn trigger-btn-primary" data-action="accept">
                            ${this.getActionText(triggerData.action)}
                        </button>
                        <button class="trigger-btn trigger-btn-secondary" data-action="dismiss">
                            Cerrar
                        </button>
                    </div>
                </div>
                <button class="trigger-close" data-action="close">&times;</button>
            </div>
        `;
    }

    /**
     * Get discount trigger HTML
     */
    getDiscountTriggerHTML(triggerData) {
        const discount = triggerData.context?.discount || {};
        const amount = discount.amount || '10%';
        const code = discount.code || '';
        
        return `
            <div class="trigger-content trigger-discount">
                <div class="trigger-avatar">
                    <div class="trigger-avatar-icon">🎁</div>
                </div>
                <div class="trigger-body">
                    <div class="trigger-discount-badge">${amount} OFF</div>
                    <div class="trigger-message">${triggerData.message}</div>
                    ${code ? `<div class="trigger-discount-code">Código: <strong>${code}</strong></div>` : ''}
                    <div class="trigger-actions">
                        <button class="trigger-btn trigger-btn-primary" data-action="accept">
                            Usar Descuento
                        </button>
                        <button class="trigger-btn trigger-btn-secondary" data-action="dismiss">
                            No, gracias
                        </button>
                    </div>
                </div>
                <button class="trigger-close" data-action="close">&times;</button>
            </div>
        `;
    }

    /**
     * Get product recommendation HTML
     */
    getProductRecommendationHTML(triggerData) {
        const products = triggerData.context?.products || [];
        const productHTML = products.slice(0, 2).map(product => `
            <div class="trigger-product">
                <img src="${product.image_url || ''}" alt="${product.name}" class="trigger-product-image">
                <div class="trigger-product-info">
                    <div class="trigger-product-name">${product.name}</div>
                    <div class="trigger-product-price">${product.price}</div>
                </div>
            </div>
        `).join('');
        
        return `
            <div class="trigger-content trigger-products">
                <div class="trigger-avatar">
                    <div class="trigger-avatar-icon">🛍️</div>
                </div>
                <div class="trigger-body">
                    <div class="trigger-message">${triggerData.message}</div>
                    <div class="trigger-products-list">
                        ${productHTML}
                    </div>
                    <div class="trigger-actions">
                        <button class="trigger-btn trigger-btn-primary" data-action="accept">
                            Ver Productos
                        </button>
                        <button class="trigger-btn trigger-btn-secondary" data-action="dismiss">
                            Cerrar
                        </button>
                    </div>
                </div>
                <button class="trigger-close" data-action="close">&times;</button>
            </div>
        `;
    }

    /**
     * Get email collection HTML
     */
    getEmailCollectionHTML(triggerData) {
        return `
            <div class="trigger-content trigger-email">
                <div class="trigger-avatar">
                    <div class="trigger-avatar-icon">📧</div>
                </div>
                <div class="trigger-body">
                    <div class="trigger-message">${triggerData.message}</div>
                    <div class="trigger-email-form">
                        <input type="email" class="trigger-email-input" placeholder="tu@email.com" required>
                        <button class="trigger-btn trigger-btn-primary" data-action="submit-email">
                            Suscribirse
                        </button>
                    </div>
                    <div class="trigger-actions">
                        <button class="trigger-btn trigger-btn-secondary" data-action="dismiss">
                            No, gracias
                        </button>
                    </div>
                </div>
                <button class="trigger-close" data-action="close">&times;</button>
            </div>
        `;
    }

    /**
     * Get action text
     */
    getActionText(action) {
        const actionTexts = {
            'show_message': 'Chatear',
            'show_discount': 'Ver Oferta',
            'show_product_recommendations': 'Ver Productos',
            'collect_email': 'Suscribirse',
            'start_consultation': 'Comenzar'
        };
        
        return actionTexts[action] || 'Continuar';
    }

    /**
     * Show trigger element with animation
     */
    showTriggerElement(element, triggerData) {
        document.body.appendChild(element);
        
        // Apply initial styles
        element.style.opacity = '0';
        element.style.transform = this.getInitialTransform(triggerData.display_config);
        
        // Trigger animation
        requestAnimationFrame(() => {
            element.style.transition = 'all 0.3s ease-out';
            element.style.opacity = '1';
            element.style.transform = 'none';
        });
        
        // Auto-hide if configured
        const displayConfig = triggerData.display_config || {};
        if (displayConfig.auto_hide_delay) {
            setTimeout(() => {
                this.hideTrigger(triggerData.trigger_id);
            }, displayConfig.auto_hide_delay * 1000);
        }
    }

    /**
     * Get initial transform for animation
     */
    getInitialTransform(displayConfig = {}) {
        const position = displayConfig.position || 'bottom-right';
        const animation = displayConfig.animation || 'slide-up';
        
        switch (animation) {
            case 'slide-up':
                return 'translateY(100%)';
            case 'slide-down':
                return 'translateY(-100%)';
            case 'slide-left':
                return 'translateX(100%)';
            case 'slide-right':
                return 'translateX(-100%)';
            case 'fade':
                return 'scale(0.8)';
            default:
                return 'translateY(100%)';
        }
    }

    /**
     * Add event listeners to trigger element
     */
    addTriggerEventListeners(element, triggerData) {
        element.addEventListener('click', (e) => {
            const action = e.target.getAttribute('data-action');
            
            if (action) {
                e.preventDefault();
                e.stopPropagation();
                this.handleTriggerAction(action, triggerData, element);
            }
        });

        // Handle email form submission
        const emailInput = element.querySelector('.trigger-email-input');
        if (emailInput) {
            emailInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.handleTriggerAction('submit-email', triggerData, element);
                }
            });
        }
    }

    /**
     * Handle trigger action
     */
    handleTriggerAction(action, triggerData, element) {
        switch (action) {
            case 'accept':
                this.engine.analytics.trackTriggerResponse(triggerData, 'accepted');
                this.executeTriggerAction(triggerData);
                break;
                
            case 'dismiss':
                this.engine.analytics.trackTriggerResponse(triggerData, 'dismissed');
                break;
                
            case 'close':
                this.engine.analytics.trackTriggerResponse(triggerData, 'closed');
                break;
                
            case 'submit-email':
                this.handleEmailSubmission(triggerData, element);
                return; // Don't hide trigger yet
        }
        
        // Hide trigger
        this.hideTrigger(triggerData.trigger_id, element);
    }

    /**
     * Handle email submission
     */
    handleEmailSubmission(triggerData, element) {
        const emailInput = element.querySelector('.trigger-email-input');
        const email = emailInput?.value?.trim();
        
        if (!email || !this.isValidEmail(email)) {
            this.showEmailError(element, 'Por favor ingresa un email válido');
            return;
        }
        
        // Submit email
        this.submitEmail(email, triggerData)
            .then(() => {
                this.engine.analytics.trackTriggerResponse(triggerData, 'email_submitted', true);
                this.showEmailSuccess(element);
                setTimeout(() => {
                    this.hideTrigger(triggerData.trigger_id, element);
                }, 2000);
            })
            .catch(() => {
                this.showEmailError(element, 'Error al suscribirse. Inténtalo de nuevo.');
            });
    }

    /**
     * Validate email
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Submit email to server
     */
    async submitEmail(email, triggerData) {
        const response = await fetch('/api/v2/chatbot/email-subscription', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.engine.getCSRFToken()
            },
            body: JSON.stringify({
                email: email,
                chatbot_id: this.engine.chatbotId,
                trigger_id: triggerData.trigger_id,
                source: 'proactive_trigger'
            })
        });
        
        if (!response.ok) {
            throw new Error('Failed to submit email');
        }
        
        return response.json();
    }

    /**
     * Show email error
     */
    showEmailError(element, message) {
        let errorElement = element.querySelector('.trigger-email-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'trigger-email-error';
            element.querySelector('.trigger-email-form').appendChild(errorElement);
        }
        
        errorElement.textContent = message;
        errorElement.style.color = '#e74c3c';
        errorElement.style.fontSize = '12px';
        errorElement.style.marginTop = '5px';
    }

    /**
     * Show email success
     */
    showEmailSuccess(element) {
        const form = element.querySelector('.trigger-email-form');
        form.innerHTML = `
            <div class="trigger-email-success" style="color: #27ae60; text-align: center; padding: 10px;">
                ✓ ¡Suscripción exitosa! Gracias por unirte.
            </div>
        `;
    }

    /**
     * Execute trigger action
     */
    executeTriggerAction(triggerData) {
        switch (triggerData.action) {
            case 'show_message':
                this.engine.openChatbot(triggerData.message);
                break;
                
            case 'show_discount':
                this.showDiscountModal(triggerData.context?.discount);
                break;
                
            case 'show_product_recommendations':
                this.showProductRecommendations(triggerData.context?.products);
                break;
                
            case 'start_consultation':
                this.engine.openChatbot('Me gustaría una consulta personalizada');
                break;
        }
    }

    /**
     * Show discount modal
     */
    showDiscountModal(discount) {
        if (discount && discount.code) {
            // Copy discount code to clipboard
            navigator.clipboard?.writeText(discount.code);
            
            // Show notification
            this.showNotification(`Código ${discount.code} copiado al portapapeles`);
        }
        
        // Open chatbot with discount message
        this.engine.openChatbot(`Quiero usar mi descuento de ${discount?.amount || '10%'}`);
    }

    /**
     * Show product recommendations
     */
    showProductRecommendations(products) {
        if (products && products.length > 0) {
            const productNames = products.slice(0, 2).map(p => p.name).join(' y ');
            this.engine.openChatbot(`Me interesan estos productos: ${productNames}`);
        } else {
            this.engine.openChatbot('Me gustaría ver recomendaciones de productos');
        }
    }

    /**
     * Show notification
     */
    showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'trigger-notification';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #27ae60;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            z-index: 10001;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    /**
     * Hide trigger
     */
    hideTrigger(triggerId, element = null) {
        if (!element) {
            element = document.querySelector(`[data-trigger-id="${triggerId}"]`);
        }
        
        if (element) {
            element.style.transition = 'all 0.3s ease-in';
            element.style.opacity = '0';
            element.style.transform = this.getHideTransform();
            
            setTimeout(() => {
                if (element.parentNode) {
                    element.parentNode.removeChild(element);
                }
            }, 300);
        }
        
        // Remove from displayed set
        this.displayedTriggers.delete(triggerId);
        
        // Process queue for next trigger
        setTimeout(() => {
            this.processQueue();
        }, 500);
    }

    /**
     * Get hide transform
     */
    getHideTransform() {
        return 'translateY(100%) scale(0.8)';
    }

    /**
     * Check if trigger type is in cooldown
     */
    isInCooldown(triggerType) {
        const cooldownEnd = this.triggerCooldowns.get(triggerType);
        return cooldownEnd && Date.now() < cooldownEnd;
    }

    /**
     * Set cooldown for trigger type
     */
    setCooldown(triggerType, minutes) {
        const cooldownEnd = Date.now() + (minutes * 60 * 1000);
        this.triggerCooldowns.set(triggerType, cooldownEnd);
    }

    /**
     * Clear all triggers
     */
    clearAllTriggers() {
        const triggers = document.querySelectorAll('.chatbot-proactive-trigger');
        triggers.forEach(trigger => {
            const triggerId = trigger.getAttribute('data-trigger-id');
            this.hideTrigger(triggerId, trigger);
        });
        
        this.activeQueue = [];
        this.displayedTriggers.clear();
    }

    /**
     * Get queue status
     */
    getQueueStatus() {
        return {
            queue_length: this.activeQueue.length,
            displayed_count: this.displayedTriggers.size,
            cooldowns: Array.from(this.triggerCooldowns.entries()).map(([type, end]) => ({
                type,
                remaining: Math.max(0, end - Date.now())
            }))
        };
    }
}

// Export to global scope
window.TriggerManager = TriggerManager;