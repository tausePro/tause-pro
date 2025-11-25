{{-- Sales Agent Component - Enhanced Product Display with Cart --}}
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
        
        // Cart system (acumulativo)
        cart: [],
        
        // Purchase flow
        purchaseMode: false,
        pendingProduct: null, // Producto esperando cantidad
        customerData: {},
        currentStep: null,
        
        // Threshold for free shipping suggestion
        freeShippingThreshold: {{ (int) ($chatbot->negotiation_min_cart_value ?? 50000) }},
        
        // Initialize - load products once
        async init() {
            if (!this.enabled || this.productsLoaded) return;
            
            try {
                console.log('🛍️ Sales Agent: Loading products database...');
                
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
        
        // ==================== CART METHODS ====================
        
        getCartTotal() {
            return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        },
        
        getCartItemCount() {
            return this.cart.reduce((sum, item) => sum + item.quantity, 0);
        },
        
        addToCart(product, quantity = 1) {
            const existingIndex = this.cart.findIndex(item => item.id === product.id);
            
            if (existingIndex >= 0) {
                this.cart[existingIndex].quantity += quantity;
            } else {
                this.cart.push({
                    id: product.id,
                    woocommerce_id: product.woocommerce_id,
                    name: product.name,
                    price: product.price,
                    formatted_price: product.formatted_price,
                    image_url: product.image_url,
                    quantity: quantity
                });
            }
            
            console.log('🛒 Cart updated:', this.cart);
            return this.cart;
        },
        
        clearCart() {
            this.cart = [];
            console.log('🗑️ Cart cleared');
        },
        
        // ==================== PRODUCT DETECTION ====================
        
        shouldEnhanceResponse(message) {
            if (!this.enabled || !this.productsLoaded) return false;
            
            const lowerMessage = message.toLowerCase();
            
            const hasKeyword = this.keywords.some(keyword => 
                lowerMessage.includes(keyword.toLowerCase())
            );
            
            const mentionsProduct = this.products.some(product => 
                lowerMessage.includes(product.name.toLowerCase().substring(0, 15))
            );
            
            const commercialPhrases = [
                'te recomiendo', 'tenemos disponible', 'contamos con',
                'puedes adquirir', 'puedes comprar', 'está en', 'cuesta',
                'precio de', 'valor de', 'te ofrecemos', 'ideal para', 'perfecto para'
            ];
            
            const hasCommercialContext = commercialPhrases.some(phrase => 
                lowerMessage.includes(phrase)
            );
            
            return (hasKeyword && mentionsProduct) || (hasCommercialContext && mentionsProduct);
        },
        
        findMentionedProducts(message) {
            const lowerMessage = message.toLowerCase();
            const mentioned = [];
            const significantWords = lowerMessage.match(/\b[a-záéíóúñ]{5,}\b/g) || [];
            
            this.products.forEach(product => {
                const productName = product.name.toLowerCase();
                let relevanceScore = 0;
                
                significantWords.forEach(word => {
                    if (productName.includes(word)) {
                        relevanceScore += word.length > 7 ? 3 : (word.length > 5 ? 2 : 1);
                    }
                });
                
                const productWords = productName.split(/\s+/).filter(w => w.length > 4);
                productWords.forEach(word => {
                    if (lowerMessage.includes(word)) {
                        relevanceScore += 5;
                    }
                });
                
                if (relevanceScore > 0 && product.in_stock) {
                    mentioned.push({ ...product, relevanceScore });
                }
            });
            
            mentioned.sort((a, b) => b.relevanceScore - a.relevanceScore);
            return mentioned.slice(0, 4);
        },
        
        // ==================== PRODUCT CARDS (REDESIGNED) ====================
        
        enhanceMessageWithProducts(contentWrap, message) {
            const mentionedProducts = this.findMentionedProducts(message);
            
            if (mentionedProducts.length === 0) return;
            if (contentWrap.querySelector('.sales-agent-products-grid')) return;
            
            const cardsHTML = this.createProductCardsHTML(mentionedProducts);
            
            const cardsContainer = document.createElement('div');
            cardsContainer.className = 'enhanced-products-display';
            cardsContainer.innerHTML = cardsHTML;
            contentWrap.appendChild(cardsContainer);
            
            // Add event listeners
            setTimeout(() => {
                cardsContainer.querySelectorAll('.product-buy-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const productId = parseInt(btn.getAttribute('data-product-id'));
                        this.startPurchase(productId);
                    });
                });
            }, 100);
            
            // Scroll
            setTimeout(() => {
                const chatbot = this.getChatbotInstance();
                if (chatbot?.scrollMessagesToBottom) chatbot.scrollMessagesToBottom(true);
            }, 200);
        },
        
        createProductCardsHTML(products) {
            let html = `
                <div class="sales-agent-products-grid" style="
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                    gap: 12px;
                    margin-top: 16px;
                    padding: 4px;
                ">
            `;
            
            products.forEach(product => {
                const hasDiscount = product.has_discount && product.discount_percentage > 0;
                
                html += `
                    <div class="sales-agent-product-card" style="
                        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
                        border-radius: 16px;
                        overflow: hidden;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        position: relative;
                    " onmouseover="this.style.transform='translateY(-4px) scale(1.02)'; this.style.boxShadow='0 12px 32px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.08)';"
                       onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 4px 20px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05)';">
                        
                        ${hasDiscount ? `
                            <div style="
                                position: absolute;
                                top: 8px;
                                right: 8px;
                                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                                color: white;
                                padding: 4px 8px;
                                border-radius: 20px;
                                font-size: 11px;
                                font-weight: 700;
                                z-index: 2;
                                box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
                            ">-${product.discount_percentage}%</div>
                        ` : ''}
                        
                        <div style="
                            position: relative;
                            width: 100%;
                            padding-top: 100%;
                            overflow: hidden;
                            background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
                        ">
                            <img src="${product.image_url}" 
                                 alt="${this.escapeHtml(product.name)}" 
                                 style="
                                     position: absolute;
                                     top: 0;
                                     left: 0;
                                     width: 100%;
                                     height: 100%;
                                     object-fit: cover;
                                     transition: transform 0.4s ease;
                                 "
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27200%27 height=%27200%27%3E%3Crect fill=%27%23e2e8f0%27 width=%27200%27 height=%27200%27/%3E%3Ctext fill=%27%2394a3b8%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27 dy=%27.3em%27 font-size=%2714%27%3E📦%3C/text%3E%3C/svg%3E'">
                        </div>
                        
                        <div style="padding: 12px;">
                            <h4 style="
                                font-size: 13px;
                                font-weight: 600;
                                color: #1e293b;
                                margin: 0 0 8px 0;
                                line-height: 1.3;
                                display: -webkit-box;
                                -webkit-line-clamp: 2;
                                -webkit-box-orient: vertical;
                                overflow: hidden;
                                height: 34px;
                            ">${this.escapeHtml(product.name)}</h4>
                            
                            <div style="
                                display: flex;
                                align-items: baseline;
                                gap: 6px;
                                margin-bottom: 10px;
                            ">
                                <span style="
                                    font-size: 16px;
                                    font-weight: 700;
                                    color: #059669;
                                ">${product.formatted_price}</span>
                                ${hasDiscount && product.regular_price ? `
                                    <span style="
                                        font-size: 12px;
                                        color: #94a3b8;
                                        text-decoration: line-through;
                                    ">$${new Intl.NumberFormat('es-CO').format(product.regular_price)}</span>
                                ` : ''}
                            </div>
                            
                            <button class="product-buy-btn" data-product-id="${product.id}" style="
                                width: 100%;
                                background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 50%, #6d28d9 100%);
                                color: white;
                                border: none;
                                padding: 10px 16px;
                                border-radius: 10px;
                                font-weight: 600;
                                font-size: 13px;
                                cursor: pointer;
                                transition: all 0.2s ease;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                gap: 6px;
                                box-shadow: 0 4px 12px rgba(139, 92, 246, 0.35);
                            " onmouseover="this.style.transform='scale(1.03)'; this.style.boxShadow='0 6px 16px rgba(139, 92, 246, 0.45)';"
                               onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 12px rgba(139, 92, 246, 0.35)';">
                                <span>🛒</span>
                                <span>Comprar</span>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            // Responsive styles
            html += `
                <style>
                    @media (max-width: 480px) {
                        .sales-agent-products-grid {
                            grid-template-columns: repeat(2, 1fr) !important;
                            gap: 10px !important;
                        }
                    }
                </style>
            `;
            
            return html;
        },
        
        // ==================== PURCHASE FLOW ====================
        
        startPurchase(productId) {
            const product = this.products.find(p => p.id === productId);
            if (!product) {
                console.error('❌ Product not found:', productId);
                return;
            }
            
            console.log('🛒 Starting purchase for:', product.name, 'productId:', productId);
            
            this.purchaseMode = true;
            this.pendingProduct = { ...product };
            this.currentStep = 'quantity';
            
            console.log('🛒 State after startPurchase:', { purchaseMode: this.purchaseMode, currentStep: this.currentStep, pendingProduct: this.pendingProduct?.name });
            
            this.askQuantity();
        },
        
        askQuantity() {
            const product = this.pendingProduct;
            this.addAssistantMessage(
                `¡Excelente elección! **${product.name}** por ${product.formatted_price}.\n\n¿Cuántas unidades necesitas?`
            );
        },
        
        // Handle user message during purchase flow
        handlePurchaseMessage(message) {
            console.log('🛒 handlePurchaseMessage called:', { message, purchaseMode: this.purchaseMode, currentStep: this.currentStep });
            if (!this.purchaseMode) {
                console.log('🛒 purchaseMode is false, returning false');
                return false;
            }
            
            const input = message.trim().toLowerCase();
            console.log('🛒 Processing input:', input, 'for step:', this.currentStep);
            
            switch (this.currentStep) {
                case 'quantity':
                    return this.handleQuantityInput(input);
                case 'post_quantity':
                    return this.handlePostQuantityChoice(input);
                case 'first_name':
                    return this.handleFirstNameInput(input);
                case 'last_name':
                    return this.handleLastNameInput(input);
                case 'email':
                    return this.handleEmailInput(input);
                case 'phone':
                    return this.handlePhoneInput(input);
                case 'department':
                    return this.handleDepartmentInput(input);
                case 'city':
                    return this.handleCityInput(input);
                case 'address':
                    return this.handleAddressInput(input);
                case 'address_type':
                    return this.handleAddressTypeInput(input);
                case 'address_complement':
                    return this.handleAddressComplementInput(input);
                case 'notes':
                    return this.handleNotesInput(input);
                case 'confirmation':
                    return this.handleConfirmationInput(input);
                default:
                    return false;
            }
        },
        
        handleQuantityInput(input) {
            const qty = parseInt(input);
            if (isNaN(qty) || qty < 1 || qty > 100) {
                this.addAssistantMessage('Por favor escribe un número válido (entre 1 y 100).');
                return true;
            }
            
            // Add to cart
            this.addToCart(this.pendingProduct, qty);
            this.pendingProduct = null;
            
            const cartTotal = this.getCartTotal();
            const cartTotalFormatted = new Intl.NumberFormat('es-CO').format(cartTotal);
            
            let responseMsg = `Perfecto, **${qty} ${qty === 1 ? 'unidad' : 'unidades'}** agregadas al carrito.\n\n`;
            responseMsg += `🛒 **Tu carrito:**\n`;
            
            this.cart.forEach((item, idx) => {
                const itemTotal = item.price * item.quantity;
                responseMsg += `${idx + 1}. ${item.name} x${item.quantity} = $${new Intl.NumberFormat('es-CO').format(itemTotal)}\n`;
            });
            
            responseMsg += `\n💰 **Subtotal: $${cartTotalFormatted} COP**\n`;
            responseMsg += `_(El costo de envío se coordinará por WhatsApp)_\n\n`;
            
            // Check threshold
            if (cartTotal >= this.freeShippingThreshold) {
                responseMsg += `✅ ¡Excelente! Ya alcanzaste el valor sugerido (~$${new Intl.NumberFormat('es-CO').format(this.freeShippingThreshold)} COP).\n\n`;
            } else {
                const remaining = this.freeShippingThreshold - cartTotal;
                responseMsg += `💡 Te faltan ~$${new Intl.NumberFormat('es-CO').format(remaining)} para alcanzar el valor sugerido.\n\n`;
            }
            
            responseMsg += `¿Quieres **seguir** comprando o **pagar**?\n`;
            responseMsg += `Escribe "seguir" para ver más productos o "pagar" para continuar con el pedido.`;
            
            this.addAssistantMessage(responseMsg);
            this.currentStep = 'post_quantity';
            
            return true;
        },
        
        handlePostQuantityChoice(input) {
            if (input.includes('seguir') || input.includes('mas') || input.includes('más') || input.includes('otro')) {
                this.purchaseMode = false;
                this.currentStep = null;
                this.addAssistantMessage(
                    `Perfecto, seguimos viendo opciones. 🛍️\n\nDime qué otro producto te interesa, o cuando quieras avanzar solo dime "pagar".`
                );
                return true;
            }
            
            if (input.includes('pagar') || input.includes('checkout') || input.includes('finalizar') || input.includes('listo')) {
                if (this.cart.length === 0) {
                    this.addAssistantMessage('Tu carrito está vacío. Primero agrega algunos productos.');
                    return true;
                }
                
                this.currentStep = 'first_name';
                this.addAssistantMessage('Perfecto, sigamos con tus datos para coordinar el envío. 🙂\n\n¿Cuál es tu nombre? _(solo el primer nombre)_');
                return true;
            }
            
            // Check if user is trying to add another product
            const product = this.detectSpecificProductInMessage(input);
            if (product) {
                this.pendingProduct = { ...product };
                this.currentStep = 'quantity';
                this.askQuantity();
                return true;
            }
            
            this.addAssistantMessage('Por favor escribe "seguir" para ver más productos o "pagar" para continuar con el pedido.');
            return true;
        },
        
        handleFirstNameInput(input) {
            if (input.length < 2) {
                this.addAssistantMessage('Por favor ingresa un nombre válido.');
                return true;
            }
            
            this.customerData.first_name = this.capitalize(input);
            this.currentStep = 'last_name';
            this.addAssistantMessage(`Hola ${this.customerData.first_name}! 👋\n\n¿Y tu apellido?`);
            return true;
        },
        
        handleLastNameInput(input) {
            if (input.length < 2) {
                this.addAssistantMessage('Por favor ingresa un apellido válido.');
                return true;
            }
            
            this.customerData.last_name = this.capitalize(input);
            this.currentStep = 'email';
            this.addAssistantMessage('Perfecto! ¿Cuál es tu correo electrónico?');
            return true;
        },
        
        handleEmailInput(input) {
            if (!input.includes('@') || !input.includes('.')) {
                this.addAssistantMessage('Por favor ingresa un correo válido (ejemplo: nombre@correo.com)');
                return true;
            }
            
            this.customerData.email = input.trim();
            this.currentStep = 'phone';
            this.addAssistantMessage('Genial! ¿Cuál es tu número de WhatsApp? _(incluye indicativo si quieres)_');
            return true;
        },
        
        handlePhoneInput(input) {
            const phone = input.replace(/\D/g, '');
            if (phone.length < 7) {
                this.addAssistantMessage('Por favor ingresa un número de teléfono válido.');
                return true;
            }
            
            this.customerData.phone = phone;
            this.currentStep = 'department';
            this.addAssistantMessage('¿En qué departamento estás? _(ejemplo: Bogotá, Antioquia, Valle, etc.)_');
            return true;
        },
        
        handleDepartmentInput(input) {
            if (input.length < 3) {
                this.addAssistantMessage('Por favor ingresa un departamento válido.');
                return true;
            }
            
            this.customerData.department = this.capitalize(input);
            this.currentStep = 'city';
            this.addAssistantMessage('¿Y en qué ciudad?');
            return true;
        },
        
        handleCityInput(input) {
            if (input.length < 2) {
                this.addAssistantMessage('Por favor ingresa una ciudad válida.');
                return true;
            }
            
            this.customerData.city = this.capitalize(input);
            this.currentStep = 'address';
            this.addAssistantMessage('¿Cuál es tu dirección completa? _(calle, carrera, número)_');
            return true;
        },
        
        handleAddressInput(input) {
            if (input.length < 5) {
                this.addAssistantMessage('Por favor ingresa una dirección más completa.');
                return true;
            }
            
            this.customerData.address = input;
            this.currentStep = 'address_type';
            this.addAssistantMessage('¿Es una **Casa**, **Apartamento** u **Oficina**?\n_(Solo escribe una de esas tres opciones)_');
            return true;
        },
        
        handleAddressTypeInput(input) {
            const types = ['casa', 'apartamento', 'oficina'];
            const found = types.find(t => input.includes(t));
            
            if (!found) {
                this.addAssistantMessage('Por favor escribe: Casa, Apartamento u Oficina');
                return true;
            }
            
            this.customerData.address_type = this.capitalize(found);
            
            if (found === 'apartamento' || found === 'oficina') {
                this.currentStep = 'address_complement';
                this.addAssistantMessage(`¿Cuál es el número de ${found}? _(ejemplo: 201, 5B, etc.)_\n_(Si no aplica, escribe "no")_`);
            } else {
                this.currentStep = 'notes';
                this.addAssistantMessage('¿Tienes alguna nota adicional para el domiciliario?\n_(Si no, escribe "no")_');
            }
            return true;
        },
        
        handleAddressComplementInput(input) {
            if (input !== 'no' && input.length > 0) {
                this.customerData.address_complement = input;
            }
            this.currentStep = 'notes';
            this.addAssistantMessage('¿Tienes alguna nota adicional para el domiciliario?\n_(Si no, escribe "no")_');
            return true;
        },
        
        handleNotesInput(input) {
            if (input !== 'no' && input.length > 0) {
                this.customerData.notes = input;
            }
            
            this.currentStep = 'confirmation';
            this.showOrderSummary();
            return true;
        },
        
        showOrderSummary() {
            const cartTotal = this.getCartTotal();
            
            let summary = `📋 **Resumen de tu pedido:**\n\n`;
            summary += `🛒 **Productos:**\n`;
            
            this.cart.forEach((item, idx) => {
                const itemTotal = item.price * item.quantity;
                summary += `${idx + 1}. ${item.name} x${item.quantity} = $${new Intl.NumberFormat('es-CO').format(itemTotal)}\n`;
            });
            
            summary += `\n💰 **Total: $${new Intl.NumberFormat('es-CO').format(cartTotal)} COP**\n`;
            summary += `_(+ envío a coordinar)_\n\n`;
            
            summary += `📍 **Envío a:**\n`;
            summary += `👤 ${this.customerData.first_name} ${this.customerData.last_name}\n`;
            summary += `📧 ${this.customerData.email}\n`;
            summary += `📱 ${this.customerData.phone}\n`;
            summary += `🏠 ${this.customerData.address}`;
            if (this.customerData.address_complement) {
                summary += ` - ${this.customerData.address_type} ${this.customerData.address_complement}`;
            }
            summary += `\n📍 ${this.customerData.city}, ${this.customerData.department}\n`;
            if (this.customerData.notes) {
                summary += `📝 Nota: ${this.customerData.notes}\n`;
            }
            
            summary += `\n¿Todo está correcto? Escribe **"sí"** para confirmar o **"no"** para cancelar.`;
            
            this.addAssistantMessage(summary);
        },
        
        handleConfirmationInput(input) {
            if (input.includes('si') || input.includes('sí') || input === 'yes' || input === 'confirmar') {
                this.createOrder();
                return true;
            }
            
            if (input.includes('no') || input === 'cancelar') {
                this.addAssistantMessage('Pedido cancelado. Si quieres empezar de nuevo, solo dime qué producto te interesa. 😊');
                this.resetPurchase();
                return true;
            }
            
            this.addAssistantMessage('Por favor responde "sí" para confirmar o "no" para cancelar');
            return true;
        },
        
        async createOrder() {
            this.addAssistantMessage('✅ Perfecto! Estoy creando tu orden y generando el link de pago seguro... 💳');
            
            try {
                // Prepare cart items for API
                const items = this.cart.map(item => ({
                    product_id: item.id,
                    woocommerce_id: item.woocommerce_id,
                    quantity: item.quantity
                }));
                
                const response = await fetch('{{ isset($routes) ? $routes['createOrder'] ?? '' : '' }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        items: items,
                        // Legacy single product support
                        product_id: this.cart[0]?.id,
                        quantity: this.cart[0]?.quantity,
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
                    this.showPaymentSuccess(data);
                } else {
                    this.addAssistantMessage(`❌ Lo siento, hubo un error: ${data.message || 'No se pudo crear la orden'}. ¿Quieres intentarlo de nuevo?`);
                }
            } catch (error) {
                console.error('Error creating order:', error);
                this.addAssistantMessage('❌ Hubo un problema técnico. Por favor intenta de nuevo más tarde.');
            }
        },
        
        showPaymentSuccess(data) {
            const cartTotal = this.getCartTotal();
            
            let successMsg = `🎉 **¡Orden Creada Exitosamente!**\n\n`;
            successMsg += `📦 **Orden #${data.order_id || 'N/A'}**\n\n`;
            
            successMsg += `🛒 **Productos:**\n`;
            this.cart.forEach((item, idx) => {
                successMsg += `• ${item.name} x${item.quantity}\n`;
            });
            
            successMsg += `\n💰 **Total: $${new Intl.NumberFormat('es-CO').format(cartTotal)} COP**\n\n`;
            
            successMsg += `📍 **Envío a:** ${this.customerData.first_name} ${this.customerData.last_name}\n`;
            successMsg += `${this.customerData.address}, ${this.customerData.city}\n\n`;
            
            successMsg += `💳 **Paga de forma segura aquí:**\n${data.payment_link}\n\n`;
            
            successMsg += `⏰ _Link válido por 24 horas_\n`;
            successMsg += `📦 _El costo de envío se coordinará por WhatsApp_\n\n`;
            
            successMsg += `¡Gracias por tu compra! 🙏✨`;
            
            this.addAssistantMessage(successMsg);
            
            // Inject clickable payment button
            setTimeout(() => {
                this.injectPaymentButton(data.payment_link, data.order_id, cartTotal);
            }, 500);
            
            this.resetPurchase();
        },
        
        injectPaymentButton(paymentLink, orderId, total) {
            const messagesContainer = document.querySelector('.lqd-ext-chatbot-window-conversation-messages');
            if (!messagesContainer) return;
            
            const chatbot = this.getChatbotInstance();
            const avatarSrc = chatbot?.activeChatbot?.avatar || '';
            
            const buttonHTML = `
                <div style="
                    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
                    border: 2px solid #10b981;
                    border-radius: 16px;
                    padding: 20px;
                    margin-top: 12px;
                    text-align: center;
                ">
                    <div style="font-size: 24px; margin-bottom: 8px;">🎉</div>
                    <div style="font-weight: 700; font-size: 16px; color: #065f46; margin-bottom: 4px;">
                        Orden #${orderId || 'N/A'}
                    </div>
                    <div style="font-size: 20px; font-weight: 700; color: #059669; margin-bottom: 16px;">
                        $${new Intl.NumberFormat('es-CO').format(total)} COP
                    </div>
                    <a href="${paymentLink}" 
                       target="_blank"
                       style="
                           display: inline-block;
                           background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                           color: white;
                           text-decoration: none;
                           padding: 14px 32px;
                           border-radius: 12px;
                           font-weight: 700;
                           font-size: 15px;
                           box-shadow: 0 4px 16px rgba(16, 185, 129, 0.4);
                           transition: all 0.2s ease;
                       "
                       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(16, 185, 129, 0.5)';"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 16px rgba(16, 185, 129, 0.4)';">
                        💳 Pagar Ahora
                    </a>
                    <div style="margin-top: 12px; font-size: 12px; color: #065f46; opacity: 0.8;">
                        🔒 Pago 100% seguro con Wompi
                    </div>
                </div>
            `;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'lqd-ext-chatbot-window-conversation-message assistant';
            wrapper.innerHTML = `
                <figure class="lqd-ext-chatbot-window-conversation-message-avatar">
                    <img src="${avatarSrc}" width="27" height="27" />
                </figure>
                <div class="lqd-ext-chatbot-window-conversation-message-content-wrap">
                    <div class="lqd-ext-chatbot-window-conversation-message-content text-xs/5">
                        ${buttonHTML}
                    </div>
                </div>
            `;
            
            messagesContainer.appendChild(wrapper);
            
            setTimeout(() => {
                if (chatbot?.scrollMessagesToBottom) chatbot.scrollMessagesToBottom();
            }, 100);
        },
        
        // ==================== UTILITY METHODS ====================
        
        detectSpecificProductInMessage(message) {
            if (!this.productsLoaded || this.products.length === 0) return null;
            
            const lowerMessage = message.toLowerCase().trim();
            
            // Detect by number
            const numberMatch = lowerMessage.match(/^(\d+)$/);
            if (numberMatch) {
                const idx = parseInt(numberMatch[1]) - 1;
                if (idx >= 0 && idx < this.products.length) {
                    return this.products[idx];
                }
            }
            
            // Detect by name
            const significantWords = lowerMessage.match(/\b[a-záéíóúñ]{4,}\b/g) || [];
            if (significantWords.length === 0) return null;
            
            const matches = this.products
                .map(product => {
                    const productName = product.name.toLowerCase();
                    let score = 0;
                    significantWords.forEach(word => {
                        if (productName.includes(word)) score += word.length;
                    });
                    return { product, score };
                })
                .filter(m => m.score > 0)
                .sort((a, b) => b.score - a.score);
            
            return matches.length > 0 ? matches[0].product : null;
        },
        
        resetPurchase() {
            this.purchaseMode = false;
            this.pendingProduct = null;
            this.customerData = {};
            this.currentStep = null;
            this.clearCart();
        },
        
        capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
        },
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        },
        
        // ==================== ALPINE/DOM INTEGRATION ====================
        
        getChatbotInstance(retryCount = 0) {
            const maxRetries = 30;
            const retryDelay = 200;
            
            if (window.ExternalChatbot && Array.isArray(window.ExternalChatbot.messages)) {
                this.alpineReady = true;
                return window.ExternalChatbot;
            }
            
            const chatbotEl = document.querySelector('[x-data="externalChatbot"]');
            
            if (chatbotEl?.__x?.$data && Array.isArray(chatbotEl.__x.$data.messages)) {
                this.alpineReady = true;
                return chatbotEl.__x.$data;
            }
            
            if (retryCount < maxRetries) {
                return new Promise(resolve => {
                    setTimeout(() => resolve(this.getChatbotInstance(retryCount + 1)), retryDelay);
                });
            }
            
            return null;
        },
        
        async addAssistantMessage(message) {
            const chatbot = await this.getChatbotInstance();
            if (chatbot?.messages) {
                chatbot.messages.push({
                    id: Date.now(),
                    message: message,
                    role: 'assistant',
                    created_at: new Date().toISOString()
                });
                setTimeout(() => {
                    if (chatbot.scrollMessagesToBottom) chatbot.scrollMessagesToBottom();
                }, 100);
                return;
            }
            
            // Fallback: inject into DOM
            this.injectMessageIntoDOM(message, 'assistant');
        },
        
        injectMessageIntoDOM(message, role = 'assistant') {
            let messagesContainer = document.querySelector('.lqd-ext-chatbot-window-conversation-messages');
            
            if (!messagesContainer) {
                const lastMessage = document.querySelector('.lqd-ext-chatbot-window-conversation-message:last-of-type');
                if (lastMessage?.parentElement) {
                    messagesContainer = lastMessage.parentElement;
                }
            }
            
            if (!messagesContainer) return;
            
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
            setTimeout(() => { messagesContainer.scrollTop = messagesContainer.scrollHeight; }, 100);
        },
        
        formatMessage(text) {
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>')
                .replace(/_(.*?)_/g, '<em>$1</em>');
        }
    };
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => SalesAgent.init());
    } else {
        SalesAgent.init();
    }
</script>
