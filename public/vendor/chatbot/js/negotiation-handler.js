/**
 * Negotiation Handler for External Chatbot
 * Handles automatic coupon generation when negotiation is triggered
 */

(function() {
    'use strict';

    window.NegotiationHandler = {
        chatbotId: null,
        config: null,
        cartValue: 0,

        /**
         * Initialize negotiation handler
         */
        init(chatbotId) {
            this.chatbotId = chatbotId;
            console.log('🎁 Negotiation Handler initialized for chatbot:', chatbotId);
        },

        /**
         * Handle negotiation trigger from API response
         */
        async handleNegotiationTrigger(responseData) {
            if (!responseData.negotiation_triggered) {
                return null;
            }

            console.log('🎯 Negotiation triggered!', responseData.negotiation_config);
            this.config = responseData.negotiation_config;

            // Calcular valor del carrito (por ahora asumimos un valor mínimo)
            // TODO: Integrar con el carrito real del Sales Agent
            this.cartValue = this.config.min_cart_value;

            // Generar cupón automáticamente
            const coupon = await this.generateCoupon();
            
            if (coupon) {
                return this.formatCouponMessage(coupon);
            }

            return null;
        },

        /**
         * Generate coupon via API
         */
        async generateCoupon() {
            try {
                const response = await fetch(`/dashboard/chatbot/${this.chatbotId}/ecommerce/generate-coupon`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken()
                    },
                    body: JSON.stringify({
                        cart_value: this.cartValue,
                        reason: 'price_negotiation'
                    })
                });

                if (!response.ok) {
                    const error = await response.json();
                    console.error('❌ Failed to generate coupon:', error);
                    return null;
                }

                const data = await response.json();
                
                if (data.success) {
                    console.log('✅ Coupon generated:', data.coupon);
                    return data.coupon;
                }

                return null;
            } catch (error) {
                console.error('❌ Error generating coupon:', error);
                return null;
            }
        },

        /**
         * Format coupon message for display
         */
        formatCouponMessage(coupon) {
            return `
                <div class="coupon-message" style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 12px;
                    margin: 12px 0;
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                    animation: slideIn 0.3s ease-out;
                ">
                    <div style="font-size: 32px; margin-bottom: 12px; text-align: center;">🎁</div>
                    <div style="font-weight: 600; font-size: 18px; margin-bottom: 12px; text-align: center;">
                        ¡Cupón Especial Generado!
                    </div>
                    <div style="
                        background: rgba(255,255,255,0.2);
                        padding: 16px;
                        border-radius: 8px;
                        margin: 16px 0;
                        font-family: 'Courier New', monospace;
                        font-size: 20px;
                        font-weight: bold;
                        text-align: center;
                        letter-spacing: 3px;
                        border: 2px dashed rgba(255,255,255,0.5);
                    ">
                        ${coupon.code}
                    </div>
                    <div style="font-size: 14px; opacity: 0.95; text-align: center; margin-bottom: 12px;">
                        💰 Descuento: <strong>${coupon.discount}%</strong><br>
                        ⏰ Válido por: <strong>${coupon.expires_in_minutes} minutos</strong>
                    </div>
                    <button onclick="navigator.clipboard.writeText('${coupon.code}'); this.textContent='✅ Copiado!'; setTimeout(() => this.textContent='📋 Copiar Código', 2000)" style="
                        width: 100%;
                        margin-top: 12px;
                        padding: 12px 20px;
                        background: white;
                        color: #667eea;
                        border: none;
                        border-radius: 8px;
                        font-weight: 600;
                        font-size: 14px;
                        cursor: pointer;
                        transition: all 0.2s;
                    " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        📋 Copiar Código
                    </button>
                    <div style="font-size: 12px; opacity: 0.8; margin-top: 12px; text-align: center;">
                        Aplica este cupón en el checkout para obtener tu descuento
                    </div>
                </div>
                <style>
                    @keyframes slideIn {
                        from {
                            opacity: 0;
                            transform: translateY(20px);
                        }
                        to {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }
                </style>
            `;
        },

        /**
         * Get CSRF token from meta tag or cookie
         */
        getCsrfToken() {
            // Try meta tag first
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                return metaTag.getAttribute('content');
            }

            // Try cookie
            const cookies = document.cookie.split(';');
            for (let cookie of cookies) {
                const [name, value] = cookie.trim().split('=');
                if (name === 'XSRF-TOKEN') {
                    return decodeURIComponent(value);
                }
            }

            return '';
        },

        /**
         * Set cart value (to be called by Sales Agent)
         */
        setCartValue(value) {
            this.cartValue = value;
            console.log('🛒 Cart value updated:', value);
        }
    };

    console.log('🎁 Negotiation Handler loaded');
})();
