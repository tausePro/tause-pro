document.addEventListener('alpine:init', () => {
    Alpine.data('productStep', () => ({
        products: [],
        loading: false,
        chatbotId: 5, // Fallback ID
        
        init() {
            // Try to get chatbot ID from multiple sources
            if (this.activeChatbot && this.activeChatbot.id) {
                this.chatbotId = this.activeChatbot.id;
            } else if (window.activeChatbot && window.activeChatbot.id) {
                this.chatbotId = window.activeChatbot.id;
            } else {
                // Try to get from URL or other sources
                const urlParams = new URLSearchParams(window.location.search);
                const chatbotIdFromUrl = urlParams.get('chatbot_id');
                if (chatbotIdFromUrl) {
                    this.chatbotId = chatbotIdFromUrl;
                }
            }
            
            console.log('Product step initialized with chatbot ID:', this.chatbotId);
            console.log('Available context:', {
                activeChatbot: this.activeChatbot,
                windowActiveChatbot: window.activeChatbot,
                url: window.location.href
            });
            
            this.loadProducts();
        },
        
        async loadProducts() {
            this.loading = true;
            console.log('Loading products for chatbot:', this.chatbotId);
            
            try {
                const url = `/dashboard/chatbot/products?chatbot_id=${this.chatbotId}`;
                console.log('Fetching from:', url);
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Response data:', data);
                
                this.products = data.products || [];
                console.log('Products loaded:', this.products.length);
                
                if (this.products.length > 0) {
                    console.log('Sample product:', this.products[0]);
                    if (window.toastr) {
                        toastr.success(`✅ ${this.products.length} products loaded!`);
                    }
                } else {
                    if (window.toastr) {
                        toastr.info('No products found for this chatbot');
                    }
                }
                
            } catch (error) {
                console.error('Error loading products:', error);
                this.products = [];
                if (window.toastr) {
                    toastr.error('Failed to load products: ' + error.message);
                }
            } finally {
                this.loading = false;
            }
        }
    }));
});