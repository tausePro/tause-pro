{{-- Sales Agent Component - Enhanced Product Display --}}
<script>
    // Sales Agent - Enhance AI responses with visual product cards
    window.SalesAgent = {
        // Configuration
        keywords: @json($chatbot->sales_agent_keywords ?? []),
        enabled: {{ $chatbot->sales_agent_enabled ? 'true' : 'false' }},
        products: [],
        productsLoaded: false,
        purchaseMode: false,
        selectedProduct: null,
        customerData: {},
        currentStep: null, // quantity, name, phone, address, city
        
        // Initialize - load products once
        async init() {
            if (!this.enabled || this.productsLoaded) return;
            
            try {
                console.log('🛍️ Sales Agent: Loading products database...');
                const response = await fetch('{{ isset($routes) ? $routes['getProducts'] ?? '' : '' }}');
                const data = await response.json();
                
                if (data.success && data.products) {
                    this.products = data.products;
                    this.productsLoaded = true;
                    console.log(`✅ Sales Agent: Loaded ${this.products.length} products`);
                }
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
            
            this.purchaseMode = true;
            this.selectedProduct = { ...product, quantity: 1 };
            this.currentStep = 'quantity';
            this.customerData = {};
            
            // Trigger a message from the user
            this.simulateUserMessage(`Quiero comprar: ${product.name}`);
            
            setTimeout(() => {
                this.askQuantity();
            }, 500);
        },
        
        // Get chatbot instance safely
        getChatbotInstance() {
            const chatbotEl = document.querySelector('[x-data]');
            if (!chatbotEl || !chatbotEl.__x) return null;
            return chatbotEl.__x.$data;
        },
        
        // Simulate user message
        simulateUserMessage(message) {
            const chatbot = this.getChatbotInstance();
            if (chatbot && chatbot.messages) {
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
            }
        },
        
        // Add assistant message
        addAssistantMessage(message) {
            const chatbot = this.getChatbotInstance();
            if (chatbot && chatbot.messages) {
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
            }
        },
        
        // Ask for quantity
        askQuantity() {
            this.addAssistantMessage(`¡Excelente elección! **${this.selectedProduct.name}** por ${this.selectedProduct.formatted_price}.\n\n¿Cuántas unidades necesitas?`);
        },
        
        // Handle user message during purchase flow
        handlePurchaseMessage(message) {
            if (!this.purchaseMode) return false;
            
            const input = message.trim();
            
            switch (this.currentStep) {
                case 'quantity':
                    const qty = parseInt(input);
                    if (isNaN(qty) || qty < 1) {
                        this.addAssistantMessage('Por favor escribe un número válido (ejemplo: 1, 2, 3...)');
                        return true;
                    }
                    this.selectedProduct.quantity = qty;
                    const total = this.selectedProduct.price * qty;
                    this.addAssistantMessage(`Perfecto, ${qty} ${qty === 1 ? 'unidad' : 'unidades'}.\n\n💰 **Subtotal: $${new Intl.NumberFormat('es-CO').format(total)} COP**\n\n_(El costo de envío se coordinará por WhatsApp)_`);
                    setTimeout(() => {
                        this.currentStep = 'first_name';
                        this.addAssistantMessage('¿Cuál es tu **nombre**? (solo el primer nombre)');
                    }, 1000);
                    return true;
                    
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
