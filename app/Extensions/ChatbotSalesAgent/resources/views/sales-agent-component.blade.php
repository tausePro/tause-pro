{{-- Sales Agent Component - Enhanced Product Display --}}
<script>
    // Track Alpine initialization
    let alpineReady = false;
    document.addEventListener('alpine:initialized', () => {
        console.log('🎯 Alpine initialized - Sales Agent ready');
        alpineReady = true;
    });
    
    // Sales Agent - Enhance AI responses with visual product cards
    window.SalesAgent = {
        alpineReady: false,
        // Configuration
        keywords: @json($chatbot->sales_agent_keywords ?? []),
        enabled: {{ $chatbot->sales_agent_enabled ? 'true' : 'false' }},
        products: [],
        productsLoaded: false,
        purchaseMode: false,
        selectedProduct: null,
        customerData: {},
        currentStep: null, // quantity, post_quantity, name, phone, address, city
        // Usar negotiation_min_cart_value como umbral de ticket/envío sugerido
        freeShippingThreshold: {{ (int) ($chatbot->negotiation_min_cart_value ?? 0) }},
        
        // Initialize - load products once
        async init() {
            if (!this.enabled || this.productsLoaded) return;
            
            try {
                console.log('🛍️ Sales Agent: Loading products database...');
                
                // Load all products from all pages
                let allProducts = [];
                let currentPage = 1;
                let totalPages = 1;
                
                do {
                    const response = await fetch(`{{ isset($routes) ? $routes['getProducts'] ?? '' : '' }}?page=${currentPage}`);
                    const data = await response.json();
                    
                    if (data.success && data.products) {
                        allProducts = allProducts.concat(data.products);
                        totalPages = data.pagination?.last_page || 1;
                        console.log(`📄 Sales Agent: Loaded page ${currentPage}/${totalPages} - ${data.products.length} products`);
                        currentPage++;
                    } else {
                        break;
                    }
                } while (currentPage <= totalPages);
                
                this.products = allProducts;
                this.productsLoaded = true;
                console.log(`✅ Sales Agent: Loaded ${this.products.length} products total`);
                
            } catch (error) {
                console.error('❌ Sales Agent: Error loading products', error);
            }
        },
        
        // Detect if AI response should be enhanced with product cards
        shouldEnhanceResponse(message) {
            console.log('🔍 Sales Agent: Checking if should enhance response:', message.substring(0, 100));
            console.log('   - Enabled:', this.enabled);
            console.log('   - Products loaded:', this.productsLoaded);
            console.log('   - Keywords:', this.keywords);
            
            if (!this.enabled || !this.productsLoaded) {
                console.log('   ❌ Not enhancing (disabled or no products)');
                return false;
            }
            
            const lowerMessage = message.toLowerCase();
            
            // Check if message contains product-related keywords
            const hasKeyword = this.keywords.some(keyword => 
                lowerMessage.includes(keyword.toLowerCase())
            );
            
            // Check if message mentions product names
            const mentionsProduct = this.products.some(product => 
                lowerMessage.includes(product.name.toLowerCase().substring(0, 15))
            );
            
            // Check for commercial context phrases
            const commercialPhrases = [
                'te recomiendo',
                'tenemos disponible',
                'contamos con',
                'puedes adquirir',
                'puedes comprar',
                'está en',
                'cuesta',
                'precio de',
                'valor de',
                'te ofrecemos',
                'ideal para',
                'perfecto para'
            ];
            
            const hasCommercialContext = commercialPhrases.some(phrase => 
                lowerMessage.includes(phrase)
            );
            
            console.log('   - Has keyword:', hasKeyword);
            console.log('   - Mentions product:', mentionsProduct);
            console.log('   - Has commercial context:', hasCommercialContext);
            
            // Enhanced logic: keywords + products OR commercial context + products
            const shouldEnhance = (hasKeyword && mentionsProduct) || (hasCommercialContext && mentionsProduct);
            console.log('   ✅ Should enhance:', shouldEnhance);
            
            return shouldEnhance;
        },
        
        // Find products mentioned in AI response
        findMentionedProducts(message) {
            console.log('🔍 Sales Agent: Finding products in message:', message.substring(0, 150));
            const lowerMessage = message.toLowerCase();
            const mentioned = [];
            
            // Extract main keywords from AI response (more precise)
            const significantWords = lowerMessage.match(/\b[a-záéíóúñ]{5,}\b/g) || [];
            console.log('   - Significant words:', significantWords.slice(0, 10));
            
            this.products.forEach(product => {
                const productName = product.name.toLowerCase();
                let relevanceScore = 0;
                
                // Score based on exact word matches in product name
                significantWords.forEach(word => {
                    if (productName.includes(word)) {
                        // More weight for longer matches
                        relevanceScore += word.length > 7 ? 3 : (word.length > 5 ? 2 : 1);
                        console.log(`   📌 Match: "${word}" in "${product.name}" (score +${word.length > 7 ? 3 : (word.length > 5 ? 2 : 1)})`);
                    }
                });
                
                // Also check if AI explicitly mentions the product by name
                const productWords = productName.split(/\s+/).filter(w => w.length > 4);
                productWords.forEach(word => {
                    if (lowerMessage.includes(word)) {
                        relevanceScore += 5; // High score for direct name mentions
                    }
                });
                
                if (relevanceScore > 0 && product.in_stock) {
                    mentioned.push({
                        ...product,
                        relevanceScore
                    });
                }
            });
            
            // Sort by relevance score (highest first)
            mentioned.sort((a, b) => b.relevanceScore - a.relevanceScore);
            
            console.log(`   📦 Total products found: ${mentioned.length}`);
            if (mentioned.length > 0) {
                console.log('   🏆 Top matches:', mentioned.slice(0, 4).map(p => `${p.name} (score: ${p.relevanceScore})`));
            }
            
            // Return top 4 most relevant products
            return mentioned.slice(0, 4);
        },
        
        // Detect specific product mention in user message (e.g., "item 3", "producto 3", "quiero comprar duo revitalizante")
        detectSpecificProductInMessage(message) {
            if (!this.productsLoaded || this.products.length === 0) {
                return null;
            }
            
            const lowerMessage = message.toLowerCase().trim();
            console.log('🔍 Sales Agent: Detectando producto específico en:', lowerMessage);
            
            // Use configured keywords from chatbot settings (sales_agent_keywords)
            // These are the keywords the user configured in the dashboard
            const configuredKeywords = this.keywords || [];
            console.log('   - Keywords configuradas:', configuredKeywords);
            
            // Check if message contains any configured keyword
            const hasPurchaseIntent = configuredKeywords.length > 0 && 
                configuredKeywords.some(keyword => {
                    const keywordLower = keyword.toLowerCase().trim();
                    return lowerMessage.includes(keywordLower);
                });
            console.log('   - Tiene intención de compra (usando keywords configuradas):', hasPurchaseIntent);
            
            // Patterns to detect product references by number: "item 3", "producto 3", "el 3", "número 3", etc.
            const numberPatterns = [
                /(?:item|producto|product|artículo|artículo|el|la|número|num|#)\s*(\d+)/i,
                /(\d+)(?:\s*(?:er|do|ro|to|mo|vo|no|vo|mo|to|er|do|ro))?/i,
            ];
            
            let detectedNumber = null;
            for (const pattern of numberPatterns) {
                const match = lowerMessage.match(pattern);
                if (match && match[1]) {
                    detectedNumber = parseInt(match[1]);
                    console.log(`   ✅ Número detectado: ${detectedNumber}`);
                    break;
                }
            }
            
            // If number detected, try to find by index first
            if (detectedNumber && detectedNumber >= 1) {
                const productIndex = detectedNumber - 1;
                if (productIndex >= 0 && productIndex < this.products.length) {
                    const product = this.products[productIndex];
                    console.log(`   ✅ Producto encontrado por índice ${detectedNumber}:`, product.name);
                    return product;
                }
                
                // Also try to find by product name if it contains the number
                const productWithNumber = this.products.find(p => {
                    const nameLower = p.name.toLowerCase();
                    return nameLower.includes(detectedNumber.toString()) || 
                           nameLower.includes(`item ${detectedNumber}`) ||
                           nameLower.includes(`producto ${detectedNumber}`);
                });
                
                if (productWithNumber) {
                    console.log(`   ✅ Producto encontrado por nombre con número ${detectedNumber}:`, productWithNumber.name);
                    return productWithNumber;
                }
            }
            
            // If purchase intent detected, search for products by name in the message
            if (hasPurchaseIntent) {
                console.log('   🔍 Buscando productos por nombre en el mensaje...');
                
                // Extract significant words from message (4+ characters)
                const messageWords = lowerMessage.match(/\b[a-záéíóúñ]{4,}\b/g) || [];
                console.log('   - Palabras significativas:', messageWords);
                
                // Find products that match words in the message
                const matchingProducts = this.products
                    .map(product => {
                        const productName = product.name.toLowerCase();
                        const productWords = productName.split(/\s+/).filter(w => w.length >= 4);
                        
                        // Calculate match score based on word matches
                        let matchScore = 0;
                        messageWords.forEach(msgWord => {
                            productWords.forEach(prodWord => {
                                // Exact match gets high score
                                if (prodWord === msgWord) {
                                    matchScore += 10;
                                } 
                                // Partial match (word contains or is contained)
                                else if (prodWord.includes(msgWord) || msgWord.includes(prodWord)) {
                                    matchScore += 5;
                                }
                            });
                        });
                        
                        return { product, matchScore };
                    })
                    .filter(item => item.matchScore > 0)
                    .sort((a, b) => b.matchScore - a.matchScore);
                
                if (matchingProducts.length > 0) {
                    const bestMatch = matchingProducts[0];
                    console.log(`   ✅ Producto encontrado por nombre: "${bestMatch.product.name}" (score: ${bestMatch.matchScore})`);
                    return bestMatch.product;
                }
            }
            
            console.log('   ❌ No se detectó producto específico');
            return null;
        },
        
        // Enhance AI message with product cards
        enhanceMessageWithProducts(contentWrap, message) {
            console.log('🎨 Sales Agent: enhanceMessageWithProducts called');
            console.log('   - contentWrap:', contentWrap);
            
            const mentionedProducts = this.findMentionedProducts(message);
            
            if (mentionedProducts.length === 0) {
                console.log('   ❌ No products found, skipping enhancement');
                return;
            }
            
            console.log(`   ✅ Enhancing with ${mentionedProducts.length} products`);
            
            // Check if cards were already injected
            if (contentWrap.querySelector('.enhanced-products-display')) {
                console.log('   ⚠️ Cards already injected, skipping');
                return;
            }
            
            // Create product cards HTML
            const cardsHTML = this.createProductCardsHTML(mentionedProducts);
            console.log('   📝 Cards HTML created, length:', cardsHTML.length);
            
            // Inject directly into contentWrap
            const cardsContainer = document.createElement('div');
            cardsContainer.className = 'enhanced-products-display';
            cardsContainer.innerHTML = cardsHTML;
            contentWrap.appendChild(cardsContainer);
            console.log('   ✅ Product cards injected successfully!');
            
            // Add event listeners to buy buttons
            setTimeout(() => {
                const buyButtons = cardsContainer.querySelectorAll('.product-buy-btn');
                buyButtons.forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const productId = parseInt(btn.getAttribute('data-product-id'));
                        console.log('🛒 Buy button clicked for product:', productId);
                        this.startPurchase(productId);
                    });
                });
                console.log(`   ✅ Added ${buyButtons.length} event listeners`);
            }, 100);
            
            // Scroll to show cards
            setTimeout(() => {
                const chatbot = this.getChatbotInstance();
                if (chatbot && chatbot.scrollMessagesToBottom) {
                    chatbot.scrollMessagesToBottom(true);
                }
            }, 200);
        },
        
        // Create product cards HTML
        createProductCardsHTML(products) {
            // Responsive grid: 1 col mobile, 2 cols tablet, 3 cols desktop
            let html = '<div class="sales-agent-products-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.75rem; margin-top: 1rem;">';
            
            products.forEach(product => {
                html += `
                    <div class="enhanced-product-card" style="background: white; border: 2px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden; transition: all 0.2s;">
                        <img src="${product.image_url}" 
                             alt="${this.escapeHtml(product.name)}" 
                             style="width: 100%; height: 130px; object-fit: cover;"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27200%27 height=%27130%27%3E%3Crect fill=%27%23ddd%27 width=%27200%27 height=%27130%27/%3E%3Ctext fill=%27%23999%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27 dy=%27.3em%27 font-size=%2712%27%3ESin imagen%3C/text%3E%3C/svg%3E'">
                        <div style="padding: 0.75rem;">
                            <div style="font-weight: 600; font-size: 0.85rem; color: #1f2937; margin-bottom: 0.5rem; line-height: 1.3; height: 2.6em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                ${this.escapeHtml(product.name)}
                            </div>
                            <div style="font-size: 1.1rem; font-weight: 700; color: #10b981; margin-bottom: 0.5rem;">
                                ${product.formatted_price}
                            </div>
                            ${product.has_discount ? `<div style="display: inline-block; background: #ef4444; color: white; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.7rem; font-weight: 600; margin-bottom: 0.5rem;">-${product.discount_percentage}%</div>` : ''}
                            <button class="product-buy-btn" data-product-id="${product.id}" 
                                    style="width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 0.6rem; border-radius: 0.5rem; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: transform 0.2s;">
                                🛒 Comprar
                            </button>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            // Add hover and responsive styles
            html += `
                <style>
                    .enhanced-product-card:hover {
                        border-color: #667eea !important;
                        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3) !important;
                        transform: translateY(-2px);
                    }
                    .enhanced-product-card button:hover {
                        transform: scale(1.05);
                    }
                    .enhanced-product-card button:active {
                        transform: scale(0.98);
                    }
                    
                    /* Responsive grid */
                    @media (max-width: 480px) {
                        .sales-agent-products-grid {
                            grid-template-columns: 1fr !important;
                            gap: 1rem !important;
                        }
                        .enhanced-product-card {
                            max-width: 100%;
                        }
                    }
                    @media (min-width: 481px) and (max-width: 768px) {
                        .sales-agent-products-grid {
                            grid-template-columns: repeat(2, 1fr) !important;
                        }
                    }
                    @media (min-width: 769px) {
                        .sales-agent-products-grid {
                            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)) !important;
                            max-width: 100%;
                        }
                    }
                </style>
            `;
            
            return html;
        },
        
        // Start purchase from product link in AI message
        startPurchaseFromLink(linkElement) {
            console.log('🎯 startPurchaseFromLink CALLED!');
            console.log('   - Link element:', linkElement);
            console.log('   - Products available:', this.products.length);
            
            // Extract product info from the surrounding context
            const messageContent = linkElement.closest('.lqd-ext-chatbot-window-conversation-message-content');
            if (!messageContent) {
                console.error('   ❌ Could not find message content wrapper');
                return;
            }
            
            const messageText = messageContent.textContent;
            const productUrl = linkElement.href;
            const linkText = linkElement.textContent.trim();
            
            console.log('   - URL:', productUrl);
            console.log('   - Link text:', linkText);
            console.log('   - Message context:', messageText.substring(0, 300));
            
            // Try to find the product in our database by matching name
            let foundProduct = null;
            
            console.log('   🔍 Searching in products database...');
            for (const product of this.products) {
                const productNameLower = product.name.toLowerCase();
                const messageTextLower = messageText.toLowerCase();
                const linkTextLower = linkText.toLowerCase();
                
                console.log(`      - Checking: ${product.name}`);
                console.log(`        Message includes name? ${messageTextLower.includes(productNameLower)}`);
                console.log(`        Link includes name? ${linkTextLower.includes(productNameLower)}`);
                
                // Match by product name in message or link text
                if (messageTextLower.includes(productNameLower) || 
                    productNameLower.includes(linkTextLower) ||
                    linkTextLower.includes(productNameLower)) {
                    foundProduct = product;
                    console.log(`      ✅ MATCH FOUND: ${product.name}`);
                    break;
                }
            }
            
            if (foundProduct) {
                console.log('   ✅ Starting purchase flow for:', foundProduct.name);
                this.startPurchase(foundProduct.id);
            } else {
                console.error('   ❌ Could not find product in database');
                console.log('   Available products:', this.products.map(p => p.name));
                this.addAssistantMessage('Lo siento, no pude encontrar ese producto en el sistema. ¿Puedes intentar con otro?');
            }
        },
        
        // Start purchase flow
        startPurchase(productId) {
            const product = this.products.find(p => p.id === productId);
            if (!product) {
                console.error('❌ Product not found:', productId);
                return;
            }
            
            console.log('🛒 Starting purchase for:', product.name);
            console.log('   Product data:', product);
            
            this.purchaseMode = true;
            this.selectedProduct = { ...product, quantity: 1 };
            this.currentStep = 'quantity';
            this.customerData = {};
            
            console.log('   Purchase mode:', this.purchaseMode);
            console.log('   Current step:', this.currentStep);
            console.log('   Selected product:', this.selectedProduct);
            
            // Start purchase flow directly without simulating user message
            // This avoids dependency on Alpine.js instance
            console.log('   Starting purchase flow...');
            setTimeout(() => {
                console.log('   Calling askQuantity...');
                this.askQuantity();
            }, 300);
        },
        
        // Get chatbot instance safely with retry logic
        getChatbotInstance(retryCount = 0) {
            const maxRetries = 30; // Aumentado a 30 intentos = 6 segundos máximo
            const retryDelay = 200; // 200ms entre intentos
            
            // Method 0: Usar referencia global si está disponible
            if (window.ExternalChatbot && Array.isArray(window.ExternalChatbot.messages)) {
                console.log('   ✅ Using global ExternalChatbot instance');
                this.alpineReady = true;
                return window.ExternalChatbot;
            }
            
            // Method 1: Buscar el elemento con x-data="externalChatbot"
            const chatbotEl = document.querySelector('[x-data=\"externalChatbot\"]');
            console.log('   Looking for [x-data=\"externalChatbot\"] element... (attempt', retryCount + 1, '/', maxRetries, ')');
            
            if (chatbotEl) {
                console.log('   Element found, checking Alpine initialization...');
                console.log('   Has __x:', !!chatbotEl.__x);
                console.log('   Alpine ready flag:', alpineReady);
                
                if (chatbotEl.__x && chatbotEl.__x.$data) {
                    console.log('   ✅ Alpine initialized!');
                    console.log('   Has messages:', !!chatbotEl.__x.$data.messages);
                    console.log('   Messages count:', chatbotEl.__x.$data.messages ? chatbotEl.__x.$data.messages.length : 0);
                    
                    if (Array.isArray(chatbotEl.__x.$data.messages)) {
                        this.alpineReady = true;
                        return chatbotEl.__x.$data;
                    }
                } else if (retryCount < maxRetries) {
                    console.log('   ⏳ Alpine not ready yet, retrying in', retryDelay, 'ms...');
                    // Alpine not initialized yet, retry
                    return new Promise(resolve => {
                        setTimeout(() => {
                            resolve(this.getChatbotInstance(retryCount + 1));
                        }, retryDelay);
                    });
                }
            } else {
                console.log('   ⚠️ Element [x-data=\"externalChatbot\"] not found in DOM');
            }
            
            // Method 2: Fallback - buscar todos los elementos con x-data
            console.log('   Fallback: Searching all x-data elements...');
            const allXData = document.querySelectorAll('[x-data]');
            console.log('   Found', allXData.length, 'x-data elements');
            
            for (let el of allXData) {
                if (el.__x && el.__x.$data && Array.isArray(el.__x.$data.messages)) {
                    console.log('   ✅ Chatbot instance found via fallback! Messages:', el.__x.$data.messages.length);
                    this.alpineReady = true;
                    return el.__x.$data;
                }
            }
            
            if (retryCount < maxRetries) {
                console.log('   ⏳ No instance found, retrying in', retryDelay, 'ms...');
                return new Promise(resolve => {
                    setTimeout(() => {
                        resolve(this.getChatbotInstance(retryCount + 1));
                    }, retryDelay);
                });
            }
            
            console.error('   ❌ Chatbot instance not found after', maxRetries, 'attempts');
            console.error('   Total time waited:', (maxRetries * retryDelay / 1000), 'seconds');
            return null;
        },
        
        // Simulate user message (async to handle retry)
        async simulateUserMessage(message) {
            console.log('   simulateUserMessage called:', message);
            const chatbot = await this.getChatbotInstance();
            if (chatbot && chatbot.messages) {
                console.log('   ✅ Adding user message to chat');
                chatbot.messages.push({
                    id: Date.now(),
                    message: message,
                    role: 'user',
                    created_at: new Date().toISOString()
                });
                setTimeout(() => {
                    if (chatbot.scrollMessagesToBottom) {
                        chatbot.scrollMessagesToBottom();
                    }
                }, 100);
            } else {
                console.error('   ❌ Could not add user message - chatbot or messages not found');
            }
        },
        
        // Add assistant message (async to handle retry)
        async addAssistantMessage(message) {
            console.log('   addAssistantMessage called:', message.substring(0, 50));
            
            // Try Alpine.js first (for compatibility)
            const chatbot = await this.getChatbotInstance();
            if (chatbot && chatbot.messages) {
                console.log('   ✅ Adding assistant message via Alpine');
                chatbot.messages.push({
                    id: Date.now(),
                    message: message,
                    role: 'assistant',
                    created_at: new Date().toISOString()
                });
                setTimeout(() => {
                    if (chatbot.scrollMessagesToBottom) {
                        chatbot.scrollMessagesToBottom();
                    }
                }, 100);
                return;
            }
            
            // Fallback: Inject message directly into DOM
            console.log('   ⚠️ Alpine not available, injecting message directly into DOM');
            this.injectMessageIntoDOM(message, 'assistant');
        },
        
        // Inject message directly into DOM (fallback when Alpine is not available)
        injectMessageIntoDOM(message, role = 'assistant') {
            // Buscar un contenedor de mensajes existente y usar su padre como lista
            let messagesContainer = document.querySelector('.lqd-ext-chatbot-window-conversation-messages');
            
            if (!messagesContainer) {
                const lastMessage = document.querySelector('.lqd-ext-chatbot-window-conversation-message:last-of-type');
                if (lastMessage && lastMessage.parentElement) {
                    messagesContainer = lastMessage.parentElement;
                }
            }
            
            if (!messagesContainer) {
                console.error('   ❌ Messages container not found');
                return;
            }
            
            const avatarSrc = document.querySelector('.lqd-ext-chatbot-window-conversation-message[data-type="assistant"] img')?.src || '';
            
            const messageHTML = `
                <div class="lqd-ext-chatbot-window-conversation-message" data-type="${role}">
                    ${role === 'assistant' ? `
                        <div class="lqd-ext-chatbot-window-conversation-message-avatar">
                            <img src="${avatarSrc}" alt="Avatar">
                        </div>
                    ` : ''}
                    <div class="lqd-ext-chatbot-window-conversation-message-content-wrap" style="flex: 1;">
                        <div class="lqd-ext-chatbot-window-conversation-message-content">
                            <div class="lqd-ext-chatbot-window-conversation-message-text">
                                ${this.formatMessage(message)}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
            
            // Scroll to bottom
            setTimeout(() => {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 100);
            
            console.log('   ✅ Message injected into DOM');
        },
        
        // Format message with markdown-like syntax
        formatMessage(text) {
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>')
                .replace(/_(.*?)_/g, '<em>$1</em>');
        },
        
        // Ask for quantity
        askQuantity() {
            console.log('   askQuantity called for:', this.selectedProduct.name);
            
            // Try to add message via Alpine first
            this.addAssistantMessage(`¡Excelente elección! **${this.selectedProduct.name}** por ${this.selectedProduct.formatted_price}.\n\n¿Cuántas unidades necesitas?`);
            
            // If Alpine is not available, show inline form instead
            setTimeout(() => {
                if (!this.alpineReady) {
                    console.log('   ⚠️ Alpine not ready, showing inline quantity form');
                    this.showInlineQuantityForm();
                }
            }, 1000);
        },
        
        // Show inline quantity form when Alpine is not available
        showInlineQuantityForm() {
            const lastProductCard = document.querySelector('.enhanced-product-card:last-of-type');
            if (!lastProductCard) {
                console.error('   ❌ Product card not found');
                return;
            }
            
            const formHTML = `
                <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #0ea5e9; border-radius: 1rem; padding: 1.25rem; margin-top: 1rem; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);">
                    <div style="font-weight: 700; font-size: 1rem; color: #0c4a6e; margin-bottom: 1rem;">
                        🛒 ¡Excelente elección!
                    </div>
                    <div style="font-size: 0.9rem; color: #075985; margin-bottom: 1rem;">
                        <strong>${this.escapeHtml(this.selectedProduct.name)}</strong><br>
                        Precio: ${this.selectedProduct.formatted_price}
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; font-weight: 600; color: #0c4a6e; margin-bottom: 0.5rem; font-size: 0.9rem;">
                            ¿Cuántas unidades necesitas?
                        </label>
                        <input 
                            type="number" 
                            id="sales-agent-quantity-input"
                            min="1" 
                            value="1"
                            style="width: 100%; padding: 0.75rem; border: 2px solid #0ea5e9; border-radius: 0.5rem; font-size: 1rem; font-weight: 600; text-align: center;"
                        >
                    </div>
                    <button 
                        onclick="window.SalesAgent.handleInlineQuantitySubmit()"
                        style="width: 100%; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; padding: 0.875rem; border: none; border-radius: 0.75rem; font-weight: 700; font-size: 1rem; cursor: pointer; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4); transition: transform 0.2s;"
                        onmouseover="this.style.transform='translateY(-2px)'"
                        onmouseout="this.style.transform='translateY(0)'"
                    >
                        Continuar con la compra →
                    </button>
                </div>
            `;
            
            lastProductCard.insertAdjacentHTML('afterend', formHTML);
            
            // Scroll to form
            setTimeout(() => {
                document.getElementById('sales-agent-quantity-input')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        },
        
        // Handle inline quantity form submission
        handleInlineQuantitySubmit() {
            const input = document.getElementById('sales-agent-quantity-input');
            if (!input) return;
            
            const qty = parseInt(input.value);
            if (isNaN(qty) || qty < 1) {
                alert('Por favor ingresa una cantidad válida');
                return;
            }
            
            this.selectedProduct.quantity = qty;
            console.log('   Quantity selected:', qty);
            
            // Remove the form
            input.closest('div[style*="background: linear-gradient"]')?.remove();
            
            // Continue to next step
            this.currentStep = 'first_name';
            this.showInlineNameForm();
        },
        
        // Show inline name form
        showInlineNameForm() {
            const lastElement = document.querySelector('.enhanced-product-card:last-of-type') || 
                               document.querySelector('[style*="background: linear-gradient"]');
            if (!lastElement) return;
            
            const total = this.selectedProduct.price * this.selectedProduct.quantity;
            
            const formHTML = `
                <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #0ea5e9; border-radius: 1rem; padding: 1.25rem; margin-top: 1rem; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);">
                    <div style="font-weight: 700; font-size: 1rem; color: #0c4a6e; margin-bottom: 0.5rem;">
                        ✅ Perfecto, ${this.selectedProduct.quantity} ${this.selectedProduct.quantity === 1 ? 'unidad' : 'unidades'}
                    </div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: #0ea5e9; margin-bottom: 1rem;">
                        💰 Subtotal: $${new Intl.NumberFormat('es-CO').format(total)} COP
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 1rem; font-style: italic;">
                        (El costo de envío se coordinará por WhatsApp)
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; font-weight: 600; color: #0c4a6e; margin-bottom: 0.5rem; font-size: 0.9rem;">
                            ¿Cuál es tu nombre? (solo el primer nombre)
                        </label>
                        <input 
                            type="text" 
                            id="sales-agent-firstname-input"
                            placeholder="Ej: Juan"
                            style="width: 100%; padding: 0.75rem; border: 2px solid #0ea5e9; border-radius: 0.5rem; font-size: 1rem;"
                        >
                    </div>
                    <button 
                        onclick="window.SalesAgent.handleInlineFirstNameSubmit()"
                        style="width: 100%; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; padding: 0.875rem; border: none; border-radius: 0.75rem; font-weight: 700; font-size: 1rem; cursor: pointer; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);"
                    >
                        Continuar →
                    </button>
                </div>
            `;
            
            lastElement.insertAdjacentHTML('afterend', formHTML);
            
            setTimeout(() => {
                const input = document.getElementById('sales-agent-firstname-input');
                input?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                input?.focus();
            }, 100);
        },
        
        // Handle first name submission
        handleInlineFirstNameSubmit() {
            const input = document.getElementById('sales-agent-firstname-input');
            if (!input || !input.value.trim()) {
                alert('Por favor ingresa tu nombre');
                return;
            }
            
            this.customerData.first_name = input.value.trim();
            input.closest('div[style*="background: linear-gradient"]')?.remove();
            
            this.currentStep = 'last_name';
            this.showInlineLastNameForm();
        },
        
        // Show inline last name form
        showInlineLastNameForm() {
            this.showInlineFormField({
                id: 'lastname',
                label: '¿Cuál es tu apellido?',
                placeholder: 'Ej: Pérez',
                type: 'text',
                onSubmit: 'handleInlineLastNameSubmit'
            });
        },
        
        handleInlineLastNameSubmit() {
            const input = document.getElementById('sales-agent-lastname-input');
            if (!input || !input.value.trim()) {
                alert('Por favor ingresa tu apellido');
                return;
            }
            
            this.customerData.last_name = input.value.trim();
            input.closest('div[style*="background: linear-gradient"]')?.remove();
            
            this.currentStep = 'email';
            this.showInlineEmailForm();
        },
        
        // Show inline email form
        showInlineEmailForm() {
            this.showInlineFormField({
                id: 'email',
                label: '¿Cuál es tu correo electrónico?',
                placeholder: 'ejemplo@correo.com',
                type: 'email',
                onSubmit: 'handleInlineEmailSubmit'
            });
        },
        
        handleInlineEmailSubmit() {
            const input = document.getElementById('sales-agent-email-input');
            if (!input || !input.value.trim() || !input.value.includes('@')) {
                alert('Por favor ingresa un correo válido');
                return;
            }
            
            this.customerData.email = input.value.trim();
            input.closest('div[style*="background: linear-gradient"]')?.remove();
            
            this.currentStep = 'phone';
            this.showInlinePhoneForm();
        },
        
        // Show inline phone form
        showInlinePhoneForm() {
            this.showInlineFormField({
                id: 'phone',
                label: '¿Cuál es tu número de teléfono?',
                placeholder: 'Ej: 3001234567',
                type: 'tel',
                onSubmit: 'handleInlinePhoneSubmit'
            });
        },
        
        handleInlinePhoneSubmit() {
            const input = document.getElementById('sales-agent-phone-input');
            if (!input || !input.value.trim()) {
                alert('Por favor ingresa tu teléfono');
                return;
            }
            
            this.customerData.phone = input.value.trim();
            input.closest('div[style*="background: linear-gradient"]')?.remove();
            
            this.currentStep = 'department';
            this.showInlineDepartmentForm();
        },
        
        // Show inline department form
        showInlineDepartmentForm() {
            const lastElement = this.getLastFormElement();
            if (!lastElement) return;
            
            const formHTML = `
                <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #0ea5e9; border-radius: 1rem; padding: 1.25rem; margin-top: 1rem; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);">
                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; font-weight: 600; color: #0c4a6e; margin-bottom: 0.5rem; font-size: 0.9rem;">
                            ¿En qué departamento vives?
                        </label>
                        <select 
                            id="sales-agent-department-input"
                            style="width: 100%; padding: 0.75rem; border: 2px solid #0ea5e9; border-radius: 0.5rem; font-size: 1rem;"
                        >
                            <option value="">Selecciona...</option>
                            <option value="Amazonas">Amazonas</option>
                            <option value="Antioquia">Antioquia</option>
                            <option value="Arauca">Arauca</option>
                            <option value="Atlántico">Atlántico</option>
                            <option value="Bolívar">Bolívar</option>
                            <option value="Boyacá">Boyacá</option>
                            <option value="Caldas">Caldas</option>
                            <option value="Caquetá">Caquetá</option>
                            <option value="Casanare">Casanare</option>
                            <option value="Cauca">Cauca</option>
                            <option value="Cesar">Cesar</option>
                            <option value="Chocó">Chocó</option>
                            <option value="Córdoba">Córdoba</option>
                            <option value="Cundinamarca">Cundinamarca</option>
                            <option value="Guainía">Guainía</option>
                            <option value="Guaviare">Guaviare</option>
                            <option value="Huila">Huila</option>
                            <option value="La Guajira">La Guajira</option>
                            <option value="Magdalena">Magdalena</option>
                            <option value="Meta">Meta</option>
                            <option value="Nariño">Nariño</option>
                            <option value="Norte de Santander">Norte de Santander</option>
                            <option value="Putumayo">Putumayo</option>
                            <option value="Quindío">Quindío</option>
                            <option value="Risaralda">Risaralda</option>
                            <option value="San Andrés y Providencia">San Andrés y Providencia</option>
                            <option value="Santander">Santander</option>
                            <option value="Sucre">Sucre</option>
                            <option value="Tolima">Tolima</option>
                            <option value="Valle del Cauca">Valle del Cauca</option>
                            <option value="Vaupés">Vaupés</option>
                            <option value="Vichada">Vichada</option>
                        </select>
                    </div>
                    <button 
                        onclick="window.SalesAgent.handleInlineDepartmentSubmit()"
                        style="width: 100%; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; padding: 0.875rem; border: none; border-radius: 0.75rem; font-weight: 700; font-size: 1rem; cursor: pointer; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);"
                    >
                        Continuar →
                    </button>
                </div>
            `;
            
            lastElement.insertAdjacentHTML('afterend', formHTML);
            this.scrollToElement('sales-agent-department-input');
        },
        
        handleInlineDepartmentSubmit() {
            const select = document.getElementById('sales-agent-department-input');
            if (!select || !select.value) {
                alert('Por favor selecciona tu departamento');
                return;
            }
            
            this.customerData.department = select.value;
            select.closest('div[style*="background: linear-gradient"]')?.remove();
            
            this.currentStep = 'city';
            this.showInlineCityForm();
        },
        
        // Show inline city form
        showInlineCityForm() {
            this.showInlineFormField({
                id: 'city',
                label: '¿En qué ciudad vives?',
                placeholder: 'Ej: Bogotá',
                type: 'text',
                onSubmit: 'handleInlineCitySubmit'
            });
        },
        
        handleInlineCitySubmit() {
            const input = document.getElementById('sales-agent-city-input');
            if (!input || !input.value.trim()) {
                alert('Por favor ingresa tu ciudad');
                return;
            }
            
            this.customerData.city = input.value.trim();
            input.closest('div[style*="background: linear-gradient"]')?.remove();
            
            this.currentStep = 'address';
            this.showInlineAddressForm();
        },
        
        // Show inline address form
        showInlineAddressForm() {
            this.showInlineFormField({
                id: 'address',
                label: '¿Cuál es tu dirección completa?',
                placeholder: 'Ej: Calle 123 #45-67',
                type: 'text',
                onSubmit: 'handleInlineAddressSubmit'
            });
        },
        
        handleInlineAddressSubmit() {
            const input = document.getElementById('sales-agent-address-input');
            if (!input || !input.value.trim()) {
                alert('Por favor ingresa tu dirección');
                return;
            }
            
            this.customerData.address = input.value.trim();
            this.customerData.address_type = 'Casa'; // Default
            input.closest('div[style*="background: linear-gradient"]')?.remove();
            
            this.currentStep = 'confirmation';
            this.showInlineConfirmation();
        },
        
        // Generic form field helper
        showInlineFormField(config) {
            const lastElement = this.getLastFormElement();
            if (!lastElement) return;
            
            const formHTML = `
                <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #0ea5e9; border-radius: 1rem; padding: 1.25rem; margin-top: 1rem; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);">
                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; font-weight: 600; color: #0c4a6e; margin-bottom: 0.5rem; font-size: 0.9rem;">
                            ${config.label}
                        </label>
                        <input 
                            type="${config.type}" 
                            id="sales-agent-${config.id}-input"
                            placeholder="${config.placeholder}"
                            style="width: 100%; padding: 0.75rem; border: 2px solid #0ea5e9; border-radius: 0.5rem; font-size: 1rem;"
                        >
                    </div>
                    <button 
                        onclick="window.SalesAgent.${config.onSubmit}()"
                        style="width: 100%; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; padding: 0.875rem; border: none; border-radius: 0.75rem; font-weight: 700; font-size: 1rem; cursor: pointer; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);"
                    >
                        Continuar →
                    </button>
                </div>
            `;
            
            lastElement.insertAdjacentHTML('afterend', formHTML);
            this.scrollToElement(`sales-agent-${config.id}-input`);
        },
        
        // Helper to get last form element
        getLastFormElement() {
            return document.querySelector('.enhanced-product-card:last-of-type') || 
                   document.querySelector('[style*="background: linear-gradient"]:last-of-type');
        },
        
        // Helper to scroll to element
        scrollToElement(elementId) {
            setTimeout(() => {
                const element = document.getElementById(elementId);
                element?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                element?.focus();
            }, 100);
        },
        
        // Show inline confirmation
        showInlineConfirmation() {
            const lastElement = this.getLastFormElement();
            if (!lastElement) return;
            
            const total = this.selectedProduct.price * this.selectedProduct.quantity;
            
            const summaryHTML = `
                <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #10b981; border-radius: 1rem; padding: 1.25rem; margin-top: 1rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                    <div style="font-weight: 700; font-size: 1.1rem; color: #065f46; margin-bottom: 1rem; text-align: center;">
                        ✅ Confirma tu pedido
                    </div>
                    <div style="background: white; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem;">
                        <div style="font-weight: 600; color: #064e3b; margin-bottom: 0.75rem; border-bottom: 2px solid #10b981; padding-bottom: 0.5rem;">
                            📦 Resumen de Compra
                        </div>
                        <div style="font-size: 0.9rem; color: #064e3b; line-height: 1.8;">
                            <div><strong>Producto:</strong> ${this.escapeHtml(this.selectedProduct.name)}</div>
                            <div><strong>Cantidad:</strong> ${this.selectedProduct.quantity}</div>
                            <div><strong>Precio Unit:</strong> ${this.selectedProduct.formatted_price}</div>
                            <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #10b981;">
                                <strong style="font-size: 1.1rem;">Total:</strong> 
                                <span style="font-size: 1.2rem; font-weight: 700; color: #10b981;">$${new Intl.NumberFormat('es-CO').format(total)} COP</span>
                            </div>
                        </div>
                    </div>
                    <div style="background: white; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem;">
                        <div style="font-weight: 600; color: #064e3b; margin-bottom: 0.5rem;">📍 Datos de Envío</div>
                        <div style="font-size: 0.85rem; color: #065f46; line-height: 1.6;">
                            <div>${this.escapeHtml(this.customerData.first_name)} ${this.escapeHtml(this.customerData.last_name)}</div>
                            <div>${this.escapeHtml(this.customerData.email)}</div>
                            <div>${this.escapeHtml(this.customerData.phone)}</div>
                            <div>${this.escapeHtml(this.customerData.address)}</div>
                            <div>${this.escapeHtml(this.customerData.city)}, ${this.escapeHtml(this.customerData.department)}</div>
                        </div>
                    </div>
                    <button 
                        onclick="window.SalesAgent.handleInlineConfirmOrder()"
                        style="width: 100%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 1rem 1.5rem; border: none; border-radius: 0.75rem; font-weight: 700; font-size: 1.05rem; cursor: pointer; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); transition: transform 0.2s;"
                        onmouseover="this.style.transform='translateY(-2px)'"
                        onmouseout="this.style.transform='translateY(0)'"
                    >
                        💳 Confirmar y Pagar →
                    </button>
                    <div style="text-align: center; margin-top: 0.75rem; font-size: 0.75rem; color: #065f46; opacity: 0.8;">
                        🔒 Pago 100% seguro con Wompi
                    </div>
                </div>
            `;
            
            lastElement.insertAdjacentHTML('afterend', summaryHTML);
            this.scrollToElement(null);
        },
        
        // Handle order confirmation
        async handleInlineConfirmOrder() {
            const button = event.target;
            button.disabled = true;
            button.textContent = '⏳ Creando orden...';
            
            try {
                const response = await fetch('{{ isset($routes) ? $routes['createOrder'] ?? '' : '' }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        product_id: this.selectedProduct.id,
                        quantity: this.selectedProduct.quantity,
                        first_name: this.customerData.first_name,
                        last_name: this.customerData.last_name,
                        email: this.customerData.email,
                        phone: this.customerData.phone,
                        department: this.customerData.department,
                        city: this.customerData.city,
                        address: this.customerData.address,
                        address_type: this.customerData.address_type || 'Casa',
                        address_complement: this.customerData.address_complement || '',
                        notes: this.customerData.notes || ''
                    })
                });
                
                const data = await response.json();
                console.log('Order response:', data);
                
                if (data.success && data.payment_link) {
                    // Remove confirmation box
                    button.closest('div[style*="background: linear-gradient"]')?.remove();
                    
                    // Show payment link
                    this.showInlinePaymentLink(data.payment_link, data.order_id);
                } else {
                    button.disabled = false;
                    button.textContent = '💳 Confirmar y Pagar →';
                    alert(`Error: ${data.message || 'No se pudo crear la orden. Por favor intenta de nuevo.'}`);
                }
            } catch (error) {
                console.error('Error creating order:', error);
                button.disabled = false;
                button.textContent = '💳 Confirmar y Pagar →';
                alert('Hubo un problema técnico. Por favor intenta de nuevo.');
            }
        },
        
        // Show inline payment link
        showInlinePaymentLink(paymentLink, orderId) {
            const lastElement = this.getLastFormElement();
            if (!lastElement) return;
            
            const total = this.selectedProduct.price * this.selectedProduct.quantity;
            
            const paymentHTML = `
                <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #10b981; border-radius: 1rem; padding: 1.25rem; margin-top: 1rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                    <div style="font-weight: 700; font-size: 1.1rem; color: #065f46; margin-bottom: 1rem; text-align: center;">
                        ✅ ¡Orden Creada Exitosamente!
                    </div>
                    <div style="background: white; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem;">
                        <div style="font-weight: 600; color: #064e3b; margin-bottom: 0.75rem;">
                            📦 Orden #${orderId || 'N/A'}
                        </div>
                        <div style="font-size: 0.9rem; color: #064e3b; line-height: 1.8;">
                            <div><strong>Producto:</strong> ${this.escapeHtml(this.selectedProduct.name)}</div>
                            <div><strong>Cantidad:</strong> ${this.selectedProduct.quantity}</div>
                            <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #10b981;">
                                <strong style="font-size: 1.1rem;">Total a Pagar:</strong> 
                                <span style="font-size: 1.2rem; font-weight: 700; color: #10b981;">$${new Intl.NumberFormat('es-CO').format(total)} COP</span>
                            </div>
                        </div>
                    </div>
                    <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem;">
                        <div style="font-size: 0.85rem; color: #78350f; line-height: 1.6;">
                            <strong>📍 Envío:</strong> El costo de envío se coordinará por WhatsApp después del pago.
                        </div>
                    </div>
                    <a href="${paymentLink}" 
                       target="_blank"
                       style="display: block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-align: center; padding: 1rem 1.5rem; border-radius: 0.75rem; text-decoration: none; font-weight: 700; font-size: 1.05rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); transition: transform 0.2s; margin-bottom: 0.75rem;"
                       onmouseover="this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.transform='translateY(0)'"
                    >
                        💳 Pagar Ahora con Wompi →
                    </a>
                    <div style="text-align: center; font-size: 0.75rem; color: #065f46; opacity: 0.8;">
                        🔒 Pago 100% seguro • Wompi
                    </div>
                </div>
            `;
            
            lastElement.insertAdjacentHTML('afterend', paymentHTML);
            
            // Scroll to payment link
            setTimeout(() => {
                const paymentBox = lastElement.nextElementSibling;
                paymentBox?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
            
            // Reset purchase mode
            this.purchaseMode = false;
            this.currentStep = null;
        },
        
        // Handle user message during purchase flow
        handlePurchaseMessage(message) {
            if (!this.purchaseMode) return false;
            
            const input = message.trim();
            
            switch (this.currentStep) {
                case 'quantity': {
                    const qty = parseInt(input);
                    if (isNaN(qty) || qty < 1) {
                        this.addAssistantMessage('Por favor escribe un número válido (ejemplo: 1, 2, 3...)');
                        return true;
                    }
                    this.selectedProduct.quantity = qty;
                    const subtotal = this.selectedProduct.price * qty;
                    const subtotalLabel = new Intl.NumberFormat('es-CO').format(subtotal);
                    
                    let messageText = `Perfecto, ${qty} ${qty === 1 ? 'unidad' : 'unidades'}.\n\n💰 **Subtotal: $${subtotalLabel} COP**\n\n_(El costo de envío se coordinará por WhatsApp)_`;
                    
                    // Usar umbral configurado (negotiation_min_cart_value) como referencia de ticket/envío
                    if (this.freeShippingThreshold && this.freeShippingThreshold > 0) {
                        const thresholdLabel = new Intl.NumberFormat('es-CO').format(this.freeShippingThreshold);
                        
                        if (subtotal >= this.freeShippingThreshold) {
                            messageText += `\n\n✅ Con este pedido ya alcanzas el valor sugerido (~$${thresholdLabel} COP).`;
                        } else {
                            const diff = this.freeShippingThreshold - subtotal;
                            const diffLabel = new Intl.NumberFormat('es-CO').format(diff);
                            messageText += `\n\n💡 Si tu pedido supera aproximadamente los $${thresholdLabel} COP podrías acceder a promociones o envío preferencial.\nTe faltan alrededor de $${diffLabel} COP.`;
                        }
                    }
                    
                    messageText += `\n\n¿Quieres **seguir comprando** o **pasar al pago**?\nEscribe **\"seguir\"** para ver más productos o **\"pagar\"** para continuar con el pedido.`;
                    
                    this.addAssistantMessage(messageText);
                    this.currentStep = 'post_quantity';
                    return true;
                }
                    
                case 'first_name':
                    if (input.length < 2) {
                        this.addAssistantMessage('Por favor escribe tu nombre (mínimo 2 caracteres)');
                        return true;
                    }
                    this.customerData.first_name = input;
                    this.addAssistantMessage(`Hola ${input}! 👋\n\n¿Y tu **apellido**?`);
                    this.currentStep = 'last_name';
                    return true;
                    
                case 'last_name':
                    if (input.length < 2) {
                        this.addAssistantMessage('Por favor escribe tu apellido (mínimo 2 caracteres)');
                        return true;
                    }
                    this.customerData.last_name = input;
                    this.addAssistantMessage('Perfecto! ¿Cuál es tu **correo electrónico**?');
                    this.currentStep = 'email';
                    return true;
                    
                case 'email':
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(input)) {
                        this.addAssistantMessage('Por favor escribe un email válido (ejemplo: tu@email.com)');
                        return true;
                    }
                    this.customerData.email = input;
                    this.addAssistantMessage('Genial! ¿Cuál es tu **número de WhatsApp**? (incluye indicativo si quieres)');
                    this.currentStep = 'phone';
                    return true;
                    
                case 'phone':
                    if (input.length < 7) {
                        this.addAssistantMessage('Por favor escribe un número válido (mínimo 7 dígitos)');
                        return true;
                    }
                    this.customerData.phone = input;
                    this.addAssistantMessage('¿En qué **departamento** estás? (ejemplo: Bogotá, Antioquia, Valle, etc.)');
                    this.currentStep = 'department';
                    return true;
                    
                case 'department':
                    if (input.length < 3) {
                        this.addAssistantMessage('Por favor escribe el nombre del departamento');
                        return true;
                    }
                    this.customerData.department = input;
                    this.addAssistantMessage('¿Y en qué **ciudad**?');
                    this.currentStep = 'city';
                    return true;
                    
                case 'city':
                    if (input.length < 3) {
                        this.addAssistantMessage('Por favor escribe el nombre de la ciudad');
                        return true;
                    }
                    this.customerData.city = input;
                    this.addAssistantMessage('¿Cuál es tu **dirección completa**? (calle, carrera, número)');
                    this.currentStep = 'address';
                    return true;
                    
                case 'address':
                    if (input.length < 5) {
                        this.addAssistantMessage('Por favor escribe la dirección completa');
                        return true;
                    }
                    this.customerData.address = input;
                    this.addAssistantMessage('¿Es una **Casa**, **Apartamento** u **Oficina**?\n\n_(Solo escribe una de esas tres opciones)_');
                    this.currentStep = 'address_type';
                    return true;
                    
                case 'address_type':
                    const type = input.toLowerCase();
                    if (!['casa', 'apartamento', 'oficina'].includes(type)) {
                        this.addAssistantMessage('Por favor escribe **Casa**, **Apartamento** o **Oficina**');
                        return true;
                    }
                    this.customerData.address_type = type.charAt(0).toUpperCase() + type.slice(1);
                    
                    if (type === 'apartamento' || type === 'oficina') {
                        this.addAssistantMessage(`¿Cuál es el **número de ${type}**? (ejemplo: 201, 5B, etc.)\n\n_(Si no aplica, escribe "no")_`);
                        this.currentStep = 'address_complement';
                    } else {
                        this.customerData.address_complement = '';
                        this.addAssistantMessage('¿Tienes alguna **nota adicional** para el domiciliario?\n\n_(Si no, escribe "no")_');
                        this.currentStep = 'notes';
                    }
                    return true;
                    
                case 'address_complement':
                    this.customerData.address_complement = input.toLowerCase() === 'no' ? '' : input;
                    this.addAssistantMessage('¿Tienes alguna **nota adicional** para el domiciliario?\n\n_(Si no, escribe "no")_');
                    this.currentStep = 'notes';
                    return true;
                    
                case 'notes':
                    this.customerData.notes = input.toLowerCase() === 'no' ? '' : input;
                    this.showOrderConfirmation();
                    return true;
                    
                case 'post_quantity': {
                    const choice = input.toLowerCase();
                    
                    if (choice.includes('pagar') || choice.includes('pago') || choice.includes('checkout') || choice.includes('listo') || choice.includes('confirmar')) {
                        // Continuar al flujo de datos del cliente
                        this.addAssistantMessage('Perfecto, sigamos con tus datos para coordinar el envío. 🙂\n\n¿Cuál es tu **nombre**? (solo el primer nombre)');
                        this.currentStep = 'first_name';
                        return true;
                    }
                    
                    if (choice.includes('seguir') || choice.includes('comprando') || choice.includes('ver') || choice.includes('productos') || choice.includes('agregar')) {
                        this.addAssistantMessage('Perfecto, seguimos viendo opciones. 🛍️\nDime qué otro producto te interesa (por ejemplo: \"aceite energía\", \"mascarilla exfoliante\"), o cuando quieras avanzar solo dime **\"pagar\"**.');
                        // Cerramos este intento de compra, pero mantenemos el contexto del chat
                        this.resetPurchase();
                        return true;
                    }
                    
                    this.addAssistantMessage('Para continuar, por favor escribe **\"pagar\"** si quieres seguir al pago o **\"seguir\"** si prefieres ver más productos.');
                    return true;
                }
                    
                case 'confirmation':
                    const confirm = input.toLowerCase();
                    if (confirm === 'si' || confirm === 'sí' || confirm === 'confirmar' || confirm === 'confirmo') {
                        this.createOrder();
                    } else if (confirm === 'no' || confirm === 'cancelar') {
                        this.addAssistantMessage('Entendido, cancelé tu pedido. Si quieres comprar algo, solo dímelo! 😊');
                        this.resetPurchase();
                    } else {
                        this.addAssistantMessage('Por favor responde **"sí"** para confirmar o **"no"** para cancelar');
                    }
                    return true;
            }
            
            return false;
        },
        
        // Show order confirmation before payment
        showOrderConfirmation() {
            const total = this.selectedProduct.price * this.selectedProduct.quantity;
            
            setTimeout(() => {
                const summaryHTML = `
                    <div style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border: 2px solid #fb923c; border-radius: 1rem; padding: 1.25rem; margin: 0.75rem 0;">
                        <div style="font-weight: 700; font-size: 1.05rem; color: #9a3412; margin-bottom: 1rem; text-align: center;">
                            📋 Confirma tu Pedido
                        </div>
                        
                        <div style="background: white; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem; font-size: 0.9rem;">
                            <div style="font-weight: 600; color: #7c2d12; margin-bottom: 0.5rem; border-bottom: 2px solid #fb923c; padding-bottom: 0.5rem;">
                                🛍️ Producto
                            </div>
                            <div style="color: #431407; line-height: 1.6;">
                                <div><strong>${this.escapeHtml(this.selectedProduct.name)}</strong></div>
                                <div>Cantidad: <strong>${this.selectedProduct.quantity}</strong></div>
                                <div>Precio: <strong>${this.selectedProduct.formatted_price}</strong></div>
                                <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #fb923c; font-size: 1.1rem;">
                                    <strong>Subtotal:</strong> <span style="color: #ea580c; font-weight: 700;">$${new Intl.NumberFormat('es-CO').format(total)} COP</span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="background: white; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem; font-size: 0.85rem;">
                            <div style="font-weight: 600; color: #7c2d12; margin-bottom: 0.5rem;">👤 Tus Datos</div>
                            <div style="color: #431407; line-height: 1.6;">
                                <div><strong>Nombre:</strong> ${this.escapeHtml(this.customerData.first_name)} ${this.escapeHtml(this.customerData.last_name)}</div>
                                <div><strong>Email:</strong> ${this.escapeHtml(this.customerData.email)}</div>
                                <div><strong>WhatsApp:</strong> ${this.escapeHtml(this.customerData.phone)}</div>
                            </div>
                        </div>
                        
                        <div style="background: white; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem; font-size: 0.85rem;">
                            <div style="font-weight: 600; color: #7c2d12; margin-bottom: 0.5rem;">📍 Dirección de Envío</div>
                            <div style="color: #431407; line-height: 1.6;">
                                <div>${this.escapeHtml(this.customerData.address)}</div>
                                ${this.customerData.address_complement ? `<div>${this.escapeHtml(this.customerData.address_type)} ${this.escapeHtml(this.customerData.address_complement)}</div>` : `<div>${this.escapeHtml(this.customerData.address_type)}</div>`}
                                <div>${this.escapeHtml(this.customerData.city)}, ${this.escapeHtml(this.customerData.department)}</div>
                                ${this.customerData.notes ? `<div style="margin-top: 0.5rem; font-style: italic;">📝 ${this.escapeHtml(this.customerData.notes)}</div>` : ''}
                            </div>
                        </div>
                        
                        <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 0.5rem; padding: 0.75rem; margin-bottom: 1rem; text-align: center; font-size: 0.85rem; color: #78350f;">
                            ⚠️ <strong>Envío NO incluido</strong> - Se coordinará el costo por WhatsApp
                        </div>
                        
                        <div style="text-align: center; font-size: 0.9rem; color: #7c2d12; font-weight: 500;">
                            ¿Todo correcto? Escribe <strong>"sí"</strong> para confirmar<br>o <strong>"no"</strong> para cancelar
                        </div>
                    </div>
                `;
                
                // Inject confirmation summary
                const messagesContainer = document.querySelector('.lqd-ext-chatbot-window-conversation-messages');
                if (messagesContainer) {
                    const chatbot = this.getChatbotInstance();
                    const avatarSrc = chatbot?.activeChatbot?.avatar || '';
                    
                    const summaryContainer = document.createElement('div');
                    summaryContainer.className = 'lqd-ext-chatbot-window-conversation-message assistant';
                    summaryContainer.innerHTML = `
                        <figure class="lqd-ext-chatbot-window-conversation-message-avatar">
                            <img src="${avatarSrc}" width="27" height="27" />
                        </figure>
                        <div class="lqd-ext-chatbot-window-conversation-message-content-wrap">
                            <div class="lqd-ext-chatbot-window-conversation-message-content text-xs/5">
                                ${summaryHTML}
                            </div>
                        </div>
                    `;
                    messagesContainer.appendChild(summaryContainer);
                    
                    setTimeout(() => {
                        if (chatbot && chatbot.scrollMessagesToBottom) {
                            chatbot.scrollMessagesToBottom();
                        }
                    }, 100);
                }
            }, 500);
            
            this.currentStep = 'confirmation';
        },
        
        // Create order in WooCommerce and generate Wompi payment link
        async createOrder() {
            this.addAssistantMessage('✅ Perfecto! Estoy creando tu orden y generando el link de pago seguro... 💳');
            
            try {
                const response = await fetch('{{ isset($routes) ? $routes['createOrder'] ?? '' : '' }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        product_id: this.selectedProduct.id,
                        quantity: this.selectedProduct.quantity,
                        first_name: this.customerData.first_name,
                        last_name: this.customerData.last_name,
                        email: this.customerData.email,
                        phone: this.customerData.phone,
                        department: this.customerData.department,
                        city: this.customerData.city,
                        address: this.customerData.address,
                        address_type: this.customerData.address_type,
                        address_complement: this.customerData.address_complement || '',
                        notes: this.customerData.notes || ''
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.payment_link) {
                    this.showPaymentSummary(data.payment_link, data.order_id);
                } else {
                    this.addAssistantMessage(`❌ Lo siento, hubo un error: ${data.message || 'Error desconocido'}. ¿Quieres intentarlo de nuevo?`);
                    this.resetPurchase();
                }
            } catch (error) {
                console.error('Error creating order:', error);
                this.addAssistantMessage('❌ Hubo un problema técnico. Por favor intenta de nuevo en un momento.');
                this.resetPurchase();
            }
        },
        
        // Show payment summary with Wompi link
        showPaymentSummary(paymentLink, orderId = null) {
            const total = this.selectedProduct.price * this.selectedProduct.quantity;
            
            setTimeout(() => {
                const summaryHTML = `
                    <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #10b981; border-radius: 1rem; padding: 1.25rem; margin: 0.75rem 0; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                        <div style="font-weight: 700; font-size: 1.1rem; color: #065f46; margin-bottom: 1rem; text-align: center;">
                            ✅ ¡Listo para Pagar!
                        </div>
                        <div style="background: white; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem;">
                            <div style="font-weight: 600; color: #064e3b; margin-bottom: 0.75rem; border-bottom: 2px solid #10b981; padding-bottom: 0.5rem;">
                                📦 Resumen de Compra
                            </div>
                            <div style="font-size: 0.9rem; color: #064e3b; line-height: 1.8;">
                                <div><strong>Producto:</strong> ${this.escapeHtml(this.selectedProduct.name)}</div>
                                <div><strong>Cantidad:</strong> ${this.selectedProduct.quantity}</div>
                                <div><strong>Precio Unit:</strong> ${this.selectedProduct.formatted_price}</div>
                                <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #10b981;">
                                    <strong style="font-size: 1.1rem;">Total:</strong> 
                                    <span style="font-size: 1.2rem; font-weight: 700; color: #10b981;">$${new Intl.NumberFormat('es-CO').format(total)} COP</span>
                                </div>
                            </div>
                        </div>
                        <div style="background: white; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem;">
                            <div style="font-weight: 600; color: #064e3b; margin-bottom: 0.5rem;">📍 Datos de Envío</div>
                            <div style="font-size: 0.85rem; color: #065f46; line-height: 1.6;">
                                <div>${this.escapeHtml(this.customerData.name)}</div>
                                <div>${this.escapeHtml(this.customerData.phone)}</div>
                                <div>${this.escapeHtml(this.customerData.address)}</div>
                                <div>${this.escapeHtml(this.customerData.city)}</div>
                            </div>
                        </div>
                        <a href="${paymentLink}" 
                           target="_blank"
                           style="display: block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-align: center; padding: 1rem 1.5rem; border-radius: 0.75rem; text-decoration: none; font-weight: 700; font-size: 1.05rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); transition: transform 0.2s;"
                           onmouseover="this.style.transform='translateY(-2px)'"
                           onmouseout="this.style.transform='translateY(0)'">
                            💳 Pagar Ahora con Wompi →
                        </a>
                        <div style="text-align: center; margin-top: 0.75rem; font-size: 0.75rem; color: #065f46; opacity: 0.8;">
                            🔒 Pago 100% seguro
                        </div>
                    </div>
                `;
                
                // Inject payment summary
                const messagesContainer = document.querySelector('.lqd-ext-chatbot-window-conversation-messages');
                if (messagesContainer) {
                    const chatbot = this.getChatbotInstance();
                    const avatarSrc = chatbot?.activeChatbot?.avatar || '';
                    
                    const summaryContainer = document.createElement('div');
                    summaryContainer.className = 'lqd-ext-chatbot-window-conversation-message assistant';
                    summaryContainer.innerHTML = `
                        <figure class="lqd-ext-chatbot-window-conversation-message-avatar">
                            <img src="${avatarSrc}" width="27" height="27" />
                        </figure>
                        <div class="lqd-ext-chatbot-window-conversation-message-content-wrap">
                            <div class="lqd-ext-chatbot-window-conversation-message-content text-xs/5">
                                ${summaryHTML}
                            </div>
                        </div>
                    `;
                    messagesContainer.appendChild(summaryContainer);
                    
                    setTimeout(() => {
                        if (chatbot && chatbot.scrollMessagesToBottom) {
                            chatbot.scrollMessagesToBottom();
                        }
                    }, 100);
                }
            }, 500);
            
            setTimeout(() => {
                this.addAssistantMessage('Haz clic en el botón verde para completar tu pago de forma segura. ¡Gracias por tu compra! 😊');
                this.resetPurchase();
            }, 1000);
        },
        
        // Reset purchase flow
        resetPurchase() {
            this.purchaseMode = false;
            this.selectedProduct = null;
            this.customerData = {};
            this.currentStep = null;
        },
        
        // Escape HTML
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => SalesAgent.init());
    } else {
        SalesAgent.init();
    }
</script>
