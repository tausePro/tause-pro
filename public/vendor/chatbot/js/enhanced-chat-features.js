/**
 * Enhanced Chat Features
 * Quick Replies, Product Cards, Typing Indicators
 */

(function() {
    'use strict';
    
    const EnhancedChatFeatures = {
        chatContainer: null,
        
        init(chatContainer) {
            this.chatContainer = chatContainer;
            console.log('[EnhancedChat] Initialized');
        },
        
        /**
         * QUICK REPLIES
         */
        renderQuickReplies(quickReplies, onSelect) {
            if (!quickReplies || !Array.isArray(quickReplies) || quickReplies.length === 0) {
                return '';
            }
            
            const container = document.createElement('div');
            container.className = 'quick-replies-container';
            container.style.cssText = `
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin: 12px 0;
                padding: 0 12px;
                animation: slideUp 0.3s ease-out;
            `;
            
            quickReplies.forEach(reply => {
                const button = this.createQuickReplyButton(reply, onSelect);
                container.appendChild(button);
            });
            
            return container;
        },
        
        createQuickReplyButton(reply, onSelect) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'quick-reply-btn';
            button.textContent = reply.label;
            button.dataset.value = reply.value;
            button.dataset.type = reply.type || 'text';
            
            if (reply.metadata) {
                button.dataset.metadata = JSON.stringify(reply.metadata);
            }
            
            button.style.cssText = `
                padding: 10px 16px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 20px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
                white-space: nowrap;
            `;
            
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.4)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 8px rgba(102, 126, 234, 0.3)';
            });
            
            button.addEventListener('click', function() {
                if (onSelect) {
                    onSelect(reply);
                }
                // Remover todos los quick replies después de seleccionar uno
                const container = this.closest('.quick-replies-container');
                if (container) {
                    container.style.animation = 'slideDown 0.3s ease-out';
                    setTimeout(() => container.remove(), 300);
                }
            });
            
            return button;
        },
        
        /**
         * PRODUCT CARDS
         */
        renderProductCards(productData) {
            if (!productData || !productData.products || productData.products.length === 0) {
                return null;
            }
            
            const carousel = document.createElement('div');
            carousel.className = 'product-carousel';
            carousel.style.cssText = `
                display: flex;
                gap: 12px;
                overflow-x: auto;
                padding: 12px;
                scroll-snap-type: x mandatory;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
                scrollbar-color: #667eea #f0f0f0;
            `;
            
            productData.products.forEach(product => {
                const card = this.createProductCard(product);
                carousel.appendChild(card);
            });
            
            // Añadir estilos del scrollbar
            const style = document.createElement('style');
            style.textContent = `
                .product-carousel::-webkit-scrollbar {
                    height: 6px;
                }
                .product-carousel::-webkit-scrollbar-track {
                    background: #f0f0f0;
                    border-radius: 3px;
                }
                .product-carousel::-webkit-scrollbar-thumb {
                    background: #667eea;
                    border-radius: 3px;
                }
                .product-carousel::-webkit-scrollbar-thumb:hover {
                    background: #764ba2;
                }
            `;
            document.head.appendChild(style);
            
            return carousel;
        },
        
        createProductCard(product) {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.style.cssText = `
                min-width: 280px;
                max-width: 280px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                overflow: hidden;
                scroll-snap-align: start;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                cursor: pointer;
            `;
            
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
                this.style.boxShadow = '0 8px 20px rgba(0, 0, 0, 0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
            });
            
            // Imagen del producto
            const imageContainer = document.createElement('div');
            imageContainer.style.cssText = `
                width: 100%;
                height: 200px;
                overflow: hidden;
                background: #f5f5f5;
                position: relative;
            `;
            
            const image = document.createElement('img');
            image.src = product.image;
            image.alt = product.name;
            image.style.cssText = `
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.3s ease;
            `;
            
            card.addEventListener('mouseenter', () => {
                image.style.transform = 'scale(1.05)';
            });
            
            card.addEventListener('mouseleave', () => {
                image.style.transform = 'scale(1)';
            });
            
            // Badge de stock
            if (!product.in_stock) {
                const outOfStockBadge = document.createElement('div');
                outOfStockBadge.textContent = 'Agotado';
                outOfStockBadge.style.cssText = `
                    position: absolute;
                    top: 12px;
                    right: 12px;
                    background: rgba(239, 68, 68, 0.9);
                    color: white;
                    padding: 4px 12px;
                    border-radius: 12px;
                    font-size: 12px;
                    font-weight: 600;
                `;
                imageContainer.appendChild(outOfStockBadge);
            }
            
            imageContainer.appendChild(image);
            card.appendChild(imageContainer);
            
            // Contenido del producto
            const content = document.createElement('div');
            content.style.cssText = `
                padding: 16px;
            `;
            
            // Nombre
            const name = document.createElement('h4');
            name.textContent = product.name;
            name.style.cssText = `
                margin: 0 0 8px 0;
                font-size: 16px;
                font-weight: 600;
                color: #1a1a1a;
                line-height: 1.4;
            `;
            content.appendChild(name);
            
            // Categoría
            if (product.category) {
                const category = document.createElement('span');
                category.textContent = product.category;
                category.style.cssText = `
                    display: inline-block;
                    padding: 4px 8px;
                    background: #f0f0f0;
                    color: #666;
                    border-radius: 4px;
                    font-size: 12px;
                    margin-bottom: 8px;
                `;
                content.appendChild(category);
            }
            
            // Descripción
            if (product.description) {
                const description = document.createElement('p');
                description.textContent = product.description;
                description.style.cssText = `
                    margin: 8px 0;
                    font-size: 14px;
                    color: #666;
                    line-height: 1.5;
                `;
                content.appendChild(description);
            }
            
            // Precio
            const priceContainer = document.createElement('div');
            priceContainer.style.cssText = `
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin: 12px 0;
            `;
            
            const price = document.createElement('span');
            price.textContent = product.formatted_price;
            price.style.cssText = `
                font-size: 24px;
                font-weight: 700;
                color: #667eea;
            `;
            priceContainer.appendChild(price);
            
            // Rating
            if (product.rating) {
                const rating = document.createElement('span');
                rating.textContent = '⭐ ' + product.rating;
                rating.style.cssText = `
                    font-size: 14px;
                    color: #f59e0b;
                `;
                priceContainer.appendChild(rating);
            }
            
            content.appendChild(priceContainer);
            
            // Botones
            if (product.buttons && product.buttons.length > 0) {
                const buttonsContainer = document.createElement('div');
                buttonsContainer.style.cssText = `
                    display: flex;
                    gap: 8px;
                    margin-top: 12px;
                `;
                
                product.buttons.forEach(btn => {
                    const button = this.createProductButton(btn, product);
                    buttonsContainer.appendChild(button);
                });
                
                content.appendChild(buttonsContainer);
            }
            
            card.appendChild(content);
            
            return card;
        },
        
        createProductButton(buttonConfig, product) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = buttonConfig.label;
            button.className = 'product-btn';
            
            const isPrimary = buttonConfig.type === 'link';
            button.style.cssText = `
                flex: 1;
                padding: 10px 16px;
                background: ${isPrimary ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : 'white'};
                color: ${isPrimary ? 'white' : '#667eea'};
                border: ${isPrimary ? 'none' : '2px solid #667eea'};
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
            `;
            
            button.addEventListener('mouseenter', function() {
                if (isPrimary) {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.4)';
                } else {
                    this.style.background = '#f0f0f0';
                }
            });
            
            button.addEventListener('mouseleave', function() {
                if (isPrimary) {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                } else {
                    this.style.background = 'white';
                }
            });
            
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                if (buttonConfig.type === 'link' && buttonConfig.url) {
                    window.open(buttonConfig.url, buttonConfig.target || '_blank');
                } else if (buttonConfig.type === 'quick_reply' && window.sendChatMessage) {
                    window.sendChatMessage(buttonConfig.value);
                }
            });
            
            return button;
        },
        
        /**
         * TYPING INDICATOR
         */
        showTypingIndicator() {
            const indicator = document.createElement('div');
            indicator.className = 'typing-indicator';
            indicator.id = 'typing-indicator';
            indicator.style.cssText = `
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 12px 16px;
                background: #f5f5f5;
                border-radius: 18px;
                margin: 8px 12px;
            `;
            
            for (let i = 0; i < 3; i++) {
                const dot = document.createElement('div');
                dot.style.cssText = `
                    width: 8px;
                    height: 8px;
                    background: #999;
                    border-radius: 50%;
                    animation: typingDot 1.4s infinite ease-in-out;
                    animation-delay: ${i * 0.2}s;
                `;
                indicator.appendChild(dot);
            }
            
            // Añadir animación
            if (!document.getElementById('typing-animation-style')) {
                const style = document.createElement('style');
                style.id = 'typing-animation-style';
                style.textContent = `
                    @keyframes typingDot {
                        0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
                        30% { transform: translateY(-10px); opacity: 1; }
                    }
                    @keyframes slideUp {
                        from { transform: translateY(20px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                    @keyframes slideDown {
                        from { transform: translateY(0); opacity: 1; }
                        to { transform: translateY(20px); opacity: 0; }
                    }
                `;
                document.head.appendChild(style);
            }
            
            return indicator;
        },
        
        hideTypingIndicator() {
            const indicator = document.getElementById('typing-indicator');
            if (indicator) {
                indicator.remove();
            }
        }
    };
    
    // Exportar globalmente
    window.EnhancedChatFeatures = EnhancedChatFeatures;
})();




