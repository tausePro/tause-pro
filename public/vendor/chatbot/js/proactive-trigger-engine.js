/**
 * Proactive Trigger Engine
 * Detects user behavior and activates chatbot triggers proactively
 */
class ProactiveTriggerEngine {
    constructor(chatbotId, config = {}) {
        this.chatbotId = chatbotId;
        this.config = {
            apiEndpoint: '/api/v2/chatbot/triggers/evaluate',
            debug: false,
            ...config
        };
        
        this.userSession = new UserSessionTracker();
        this.triggers = new TriggerManager(this);
        this.analytics = new TriggerAnalytics(this);
        
        this.isInitialized = false;
        this.activeTriggers = [];
        this.triggerQueue = [];
        
        this.init();
    }

    /**
     * Initialize the trigger engine
     */
    async init() {
        if (this.isInitialized) return;
        
        try {
            // Load active triggers from server
            await this.loadActiveTriggers();
            
            // Initialize behavior detectors
            this.initializeBehaviorDetectors();
            
            // Start session tracking
            this.userSession.startTracking();
            
            // Integrate with product detection system
            await this.integrateWithProductDetection();
            
            this.isInitialized = true;
            this.log('Proactive Trigger Engine initialized with product integration');
            
        } catch (error) {
            console.error('Failed to initialize Proactive Trigger Engine:', error);
        }
    }

    /**
     * Load active triggers from server
     */
    async loadActiveTriggers() {
        try {
            const response = await fetch(`${this.config.apiEndpoint}/${this.chatbotId}/active`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                this.activeTriggers = data.triggers || [];
                this.log('Loaded active triggers:', this.activeTriggers);
            }
        } catch (error) {
            console.error('Failed to load active triggers:', error);
        }
    }

    /**
     * Initialize all behavior detectors
     */
    initializeBehaviorDetectors() {
        this.setupTimeBasedTriggers();
        this.setupExitIntentDetection();
        this.setupPageContextTriggers();
        this.setupNavigationTriggers();
        this.setupProductContextTriggers();
        this.setupScrollTriggers();
    }

    /**
     * Setup time-based triggers
     */
    setupTimeBasedTriggers() {
        // Welcome trigger (30s)
        setTimeout(() => {
            if (!this.userSession.hasInteracted()) {
                this.evaluateTrigger('welcome_30s');
            }
        }, 30000);

        // Page dwell trigger (2min)
        setTimeout(() => {
            if (this.userSession.getCurrentPageTime() > 120000) {
                this.evaluateTrigger('page_dwell_2min');
            }
        }, 120000);

        // Inactivity trigger (5min)
        let inactivityTimer;
        const resetInactivityTimer = () => {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                this.evaluateTrigger('inactivity_5min');
            }, 300000); // 5 minutes
        };

        // Reset timer on user activity
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetInactivityTimer, { passive: true });
        });
        
        resetInactivityTimer();
    }

    /**
     * Setup exit intent detection
     */
    setupExitIntentDetection() {
        let exitIntentTriggered = false;
        
        // Mouse leave detection
        document.addEventListener('mouseleave', (e) => {
            if (e.clientY <= 0 && !exitIntentTriggered) {
                exitIntentTriggered = true;
                this.evaluateTrigger('exit_intent');
                
                // Reset after 30 seconds
                setTimeout(() => {
                    exitIntentTriggered = false;
                }, 30000);
            }
        });

        // Beforeunload detection
        window.addEventListener('beforeunload', () => {
            if (!exitIntentTriggered) {
                this.evaluateTrigger('exit_intent');
            }
        });

        // Cart abandonment detection
        this.detectCartAbandonment();
    }

    /**
     * Detect cart abandonment
     */
    detectCartAbandonment() {
        let cartTimer;
        
        const checkCartAbandonment = () => {
            const cartItems = this.getCartItems();
            if (cartItems.length > 0) {
                clearTimeout(cartTimer);
                cartTimer = setTimeout(() => {
                    this.evaluateTrigger('cart_abandonment', {
                        cart_items: cartItems,
                        cart_value: this.getCartValue()
                    });
                }, 300000); // 5 minutes of cart inactivity
            }
        };

        // Monitor cart changes
        const observer = new MutationObserver(() => {
            checkCartAbandonment();
        });

        // Observe cart elements if they exist
        const cartElements = document.querySelectorAll('[data-cart], .cart, #cart');
        cartElements.forEach(element => {
            observer.observe(element, { childList: true, subtree: true });
        });

        checkCartAbandonment();
    }

    /**
     * Setup page context triggers
     */
    setupPageContextTriggers() {
        const pageType = this.detectPageType();
        const products = this.detectPageProducts();
        const pageContext = this.analyzePageContext();
        
        // Delay to allow page to fully load
        setTimeout(() => {
            this.evaluateTrigger(`${pageType}_page`, {
                products: products,
                page_url: window.location.href,
                page_type: pageType,
                page_context: pageContext
            });
            
            // Specific context-based triggers
            this.setupContextSpecificTriggers(pageType, pageContext, products);
        }, 15000);
    }

    /**
     * Setup context-specific triggers based on page analysis
     */
    setupContextSpecificTriggers(pageType, pageContext, products) {
        // Product page specific triggers
        if (pageType === 'product' && products.length > 0) {
            this.setupProductPageTriggers(products[0], pageContext);
        }
        
        // Category page triggers
        if (pageType === 'category') {
            this.setupCategoryPageTriggers(pageContext);
        }
        
        // Checkout page triggers
        if (pageType === 'checkout') {
            this.setupCheckoutPageTriggers(pageContext);
        }
        
        // Contact page triggers
        if (pageType === 'contact') {
            this.setupContactPageTriggers();
        }
    }

    /**
     * Setup product page specific triggers
     */
    setupProductPageTriggers(product, pageContext) {
        // High-value product financing trigger
        if (pageContext.isHighValueProduct) {
            setTimeout(() => {
                this.evaluateTrigger('high_value_product_financing', {
                    product: product,
                    estimated_value: pageContext.estimatedValue
                });
            }, 45000);
        }
        
        // Product comparison trigger
        if (pageContext.hasRelatedProducts) {
            setTimeout(() => {
                this.evaluateTrigger('product_comparison_available', {
                    product: product,
                    related_products: pageContext.relatedProducts
                });
            }, 60000);
        }
        
        // Stock urgency trigger
        if (pageContext.lowStock) {
            setTimeout(() => {
                this.evaluateTrigger('low_stock_urgency', {
                    product: product,
                    stock_level: pageContext.stockLevel
                });
            }, 30000);
        }
        
        // Size/variant selection help
        if (pageContext.hasVariants && !pageContext.variantSelected) {
            setTimeout(() => {
                this.evaluateTrigger('variant_selection_help', {
                    product: product,
                    available_variants: pageContext.variants
                });
            }, 90000);
        }
    }

    /**
     * Setup category page triggers
     */
    setupCategoryPageTriggers(pageContext) {
        // Too many options trigger
        if (pageContext.productCount > 20) {
            setTimeout(() => {
                this.evaluateTrigger('category_too_many_options', {
                    category: pageContext.category,
                    product_count: pageContext.productCount
                });
            }, 45000);
        }
        
        // Filter assistance trigger
        if (pageContext.hasFilters && !pageContext.filtersUsed) {
            setTimeout(() => {
                this.evaluateTrigger('filter_assistance', {
                    category: pageContext.category,
                    available_filters: pageContext.availableFilters
                });
            }, 60000);
        }
        
        // Seasonal recommendations
        if (pageContext.isSeasonalCategory) {
            setTimeout(() => {
                this.evaluateTrigger('seasonal_recommendations', {
                    category: pageContext.category,
                    season: pageContext.currentSeason
                });
            }, 30000);
        }
    }

    /**
     * Setup checkout page triggers
     */
    setupCheckoutPageTriggers(pageContext) {
        // Checkout assistance
        setTimeout(() => {
            this.evaluateTrigger('checkout_assistance', {
                cart_value: pageContext.cartValue,
                items_count: pageContext.itemsCount,
                checkout_step: pageContext.checkoutStep
            });
        }, 20000);
        
        // Shipping options help
        if (pageContext.hasShippingOptions && !pageContext.shippingSelected) {
            setTimeout(() => {
                this.evaluateTrigger('shipping_options_help', {
                    available_options: pageContext.shippingOptions
                });
            }, 45000);
        }
        
        // Payment security assurance
        if (pageContext.isPaymentStep) {
            setTimeout(() => {
                this.evaluateTrigger('payment_security_assurance', {
                    payment_methods: pageContext.paymentMethods
                });
            }, 30000);
        }
    }

    /**
     * Setup contact page triggers
     */
    setupContactPageTriggers() {
        // Immediate chat offer
        setTimeout(() => {
            this.evaluateTrigger('contact_immediate_chat', {
                page_type: 'contact'
            });
        }, 5000);
    }

    /**
     * Analyze page context for detailed information
     */
    analyzePageContext() {
        const context = {
            // Basic page info
            title: document.title,
            description: this.getMetaDescription(),
            keywords: this.getMetaKeywords(),
            
            // Product context
            isHighValueProduct: false,
            estimatedValue: 0,
            hasRelatedProducts: false,
            relatedProducts: [],
            lowStock: false,
            stockLevel: null,
            hasVariants: false,
            variantSelected: false,
            variants: [],
            
            // Category context
            category: this.detectCategory(),
            productCount: this.countCategoryProducts(),
            hasFilters: this.detectFilters(),
            filtersUsed: this.checkFiltersUsed(),
            availableFilters: this.getAvailableFilters(),
            isSeasonalCategory: this.isSeasonalCategory(),
            currentSeason: this.getCurrentSeason(),
            
            // Checkout context
            cartValue: this.getCartValue(),
            itemsCount: this.getCartItemsCount(),
            checkoutStep: this.detectCheckoutStep(),
            hasShippingOptions: this.detectShippingOptions(),
            shippingSelected: this.checkShippingSelected(),
            shippingOptions: this.getShippingOptions(),
            isPaymentStep: this.isPaymentStep(),
            paymentMethods: this.getPaymentMethods(),
            
            // Navigation context
            searchQuery: this.getCurrentSearchQuery(),
            searchResults: this.getSearchResultsCount(),
            breadcrumbs: this.getBreadcrumbs(),
            
            // User behavior context
            timeOnPage: Date.now() - this.userSession.pageStartTime,
            scrollDepth: this.userSession.getScrollPercent(),
            clicksOnPage: this.getPageClicks(),
            
            // Technical context
            pageLoadTime: this.getPageLoadTime(),
            hasErrors: this.detectPageErrors()
        };
        
        // Analyze product value
        if (context.estimatedValue > 500) {
            context.isHighValueProduct = true;
        }
        
        // Detect related products
        context.relatedProducts = this.detectRelatedProducts();
        context.hasRelatedProducts = context.relatedProducts.length > 0;
        
        // Check stock status
        const stockInfo = this.detectStockStatus();
        context.lowStock = stockInfo.isLow;
        context.stockLevel = stockInfo.level;
        
        // Check variants
        const variantInfo = this.detectProductVariants();
        context.hasVariants = variantInfo.hasVariants;
        context.variantSelected = variantInfo.selected;
        context.variants = variantInfo.variants;
        
        return context;
    }

    /**
     * Setup navigation triggers
     */
    setupNavigationTriggers() {
        // Search with no results
        this.monitorSearchResults();
        
        // Product comparison behavior
        this.monitorProductComparison();
        
        // Multiple page visits
        this.monitorPageVisits();
        
        // Cart abandonment patterns
        this.monitorCartBehavior();
        
        // Navigation frustration detection
        this.monitorNavigationFrustration();
        
        // Product browsing patterns
        this.monitorProductBrowsingPatterns();
        
        // Discount and promotion triggers
        this.setupDiscountPromotionTriggers();
    }

    /**
     * Setup discount and promotion trigger system
     */
    setupDiscountPromotionTriggers() {
        const userContext = this.getUserContext();
        const cartValue = this.getCartValue();
        const products = this.detectPageProducts();
        
        // New visitor discount trigger
        this.setupNewVisitorDiscountTrigger(userContext);
        
        // Cart abandonment recovery triggers
        this.setupCartAbandonmentRecoveryTriggers(userContext, cartValue);
        
        // VIP customer exclusive access triggers
        this.setupVIPExclusiveAccessTriggers(userContext, products);
        
        // High-value product financing and volume discount triggers
        this.setupHighValueProductTriggers(products, userContext);
        
        // Time-sensitive promotion triggers
        this.setupTimeSensitivePromotionTriggers(userContext);
        
        // Milestone and achievement triggers
        this.setupMilestoneDiscountTriggers(userContext);
    }

    /**
     * Setup new visitor discount trigger with automatic code generation
     */
    setupNewVisitorDiscountTrigger(userContext) {
        if (userContext.isNewVisitor && !this.hasShownWelcomeDiscount()) {
            setTimeout(() => {
                const discountCode = this.generateDiscountCode('WELCOME', 15);
                
                this.evaluateTrigger('new_visitor_welcome_discount', {
                    discount_percentage: 15,
                    discount_code: discountCode,
                    expiry_hours: 24,
                    minimum_purchase: 50,
                    user_context: userContext,
                    trigger_reason: 'new_visitor_welcome'
                });
                
                this.markWelcomeDiscountShown();
            }, 45000); // 45 seconds delay
        }
    }

    /**
     * Setup cart abandonment recovery triggers with incremental discounts
     */
    setupCartAbandonmentRecoveryTriggers(userContext, cartValue) {
        if (cartValue > 0) {
            // First abandonment trigger (5% discount)
            setTimeout(() => {
                if (this.getCartValue() > 0 && !this.hasStartedCheckout()) {
                    const discountCode = this.generateDiscountCode('SAVE5', 5);
                    
                    this.evaluateTrigger('cart_abandonment_recovery_level1', {
                        discount_percentage: 5,
                        discount_code: discountCode,
                        cart_value: this.getCartValue(),
                        urgency_level: 'low',
                        expiry_minutes: 30
                    });
                }
            }, 300000); // 5 minutes
            
            // Second abandonment trigger (10% discount)
            setTimeout(() => {
                if (this.getCartValue() > 0 && !this.hasStartedCheckout()) {
                    const discountCode = this.generateDiscountCode('SAVE10', 10);
                    
                    this.evaluateTrigger('cart_abandonment_recovery_level2', {
                        discount_percentage: 10,
                        discount_code: discountCode,
                        cart_value: this.getCartValue(),
                        urgency_level: 'medium',
                        expiry_minutes: 60,
                        previous_offer_ignored: true
                    });
                }
            }, 900000); // 15 minutes
            
            // Final abandonment trigger (15% discount + free shipping)
            setTimeout(() => {
                if (this.getCartValue() > 0 && !this.hasStartedCheckout()) {
                    const discountCode = this.generateDiscountCode('FINAL15', 15);
                    
                    this.evaluateTrigger('cart_abandonment_recovery_final', {
                        discount_percentage: 15,
                        discount_code: discountCode,
                        free_shipping: true,
                        cart_value: this.getCartValue(),
                        urgency_level: 'high',
                        expiry_minutes: 120,
                        final_offer: true
                    });
                }
            }, 1800000); // 30 minutes
        }
    }

    /**
     * Setup VIP customer exclusive access triggers
     */
    setupVIPExclusiveAccessTriggers(userContext, products) {
        if (userContext.customerTier === 'vip' || userContext.customerTier === 'premium') {
            // VIP early access to new products
            setTimeout(() => {
                this.evaluateTrigger('vip_early_access_exclusive', {
                    user_context: userContext,
                    products: products,
                    access_type: 'early_access',
                    exclusive_discount: 20,
                    vip_perks: this.getVIPPerks()
                });
            }, 20000);
            
            // VIP exclusive product access
            const exclusiveProducts = this.getVIPExclusiveProducts();
            if (exclusiveProducts.length > 0) {
                setTimeout(() => {
                    this.evaluateTrigger('vip_exclusive_products_access', {
                        exclusive_products: exclusiveProducts,
                        user_context: userContext,
                        special_pricing: true
                    });
                }, 35000);
            }
            
            // VIP birthday month special
            if (this.isUserBirthdayMonth(userContext)) {
                setTimeout(() => {
                    const birthdayCode = this.generateDiscountCode('BIRTHDAY', 25);
                    
                    this.evaluateTrigger('vip_birthday_special', {
                        discount_percentage: 25,
                        discount_code: birthdayCode,
                        free_gift: true,
                        user_context: userContext,
                        expiry_days: 30
                    });
                }, 15000);
            }
        }
    }

    /**
     * Setup high-value product financing and volume discount triggers
     */
    setupHighValueProductTriggers(products, userContext) {
        const highValueProducts = products.filter(product => {
            const price = parseFloat(product.price?.replace(/[^0-9.]/g, '')) || 0;
            return price > 200;
        });
        
        if (highValueProducts.length > 0) {
            // Financing options trigger
            setTimeout(() => {
                this.evaluateTrigger('high_value_product_financing', {
                    products: highValueProducts,
                    financing_options: this.getFinancingOptions(),
                    user_context: userContext
                });
            }, 60000);
            
            // Volume discount trigger
            if (products.length >= 3) {
                setTimeout(() => {
                    const volumeDiscount = this.calculateVolumeDiscount(products.length);
                    const discountCode = this.generateDiscountCode('VOLUME', volumeDiscount);
                    
                    this.evaluateTrigger('volume_discount_offer', {
                        discount_percentage: volumeDiscount,
                        discount_code: discountCode,
                        products_count: products.length,
                        total_value: this.calculateProductsValue(products),
                        savings_amount: this.calculateSavingsAmount(products, volumeDiscount)
                    });
                }, 90000);
            }
        }
        
        // Bundle discount trigger
        const bundleOpportunities = this.detectBundleOpportunities(products);
        if (bundleOpportunities.length > 0) {
            setTimeout(() => {
                this.evaluateTrigger('bundle_discount_opportunity', {
                    bundle_opportunities: bundleOpportunities,
                    bundle_savings: this.calculateBundleSavings(bundleOpportunities),
                    products: products
                });
            }, 75000);
        }
    }

    /**
     * Setup time-sensitive promotion triggers
     */
    setupTimeSensitivePromotionTriggers(userContext) {
        const timeContext = this.getTimeContext();
        
        // Flash sale trigger
        if (this.isFlashSaleActive()) {
            setTimeout(() => {
                const flashSaleCode = this.generateDiscountCode('FLASH', 20);
                
                this.evaluateTrigger('flash_sale_alert', {
                    discount_percentage: 20,
                    discount_code: flashSaleCode,
                    time_remaining: this.getFlashSaleTimeRemaining(),
                    urgency_level: 'critical',
                    limited_time: true
                });
            }, 10000);
        }
        
        // Weekend special trigger
        if (timeContext.isWeekend && !this.hasShownWeekendSpecial()) {
            setTimeout(() => {
                const weekendCode = this.generateDiscountCode('WEEKEND', 12);
                
                this.evaluateTrigger('weekend_special_offer', {
                    discount_percentage: 12,
                    discount_code: weekendCode,
                    weekend_exclusive: true,
                    expiry_end_of_weekend: true
                });
                
                this.markWeekendSpecialShown();
            }, 30000);
        }
        
        // Holiday promotion trigger
        const upcomingHolidays = this.detectUpcomingHolidays(timeContext);
        upcomingHolidays.forEach(holiday => {
            if (holiday.daysUntil <= 7) {
                setTimeout(() => {
                    const holidayCode = this.generateDiscountCode(holiday.name.toUpperCase().replace(/\s+/g, ''), 18);
                    
                    this.evaluateTrigger('holiday_promotion_alert', {
                        holiday: holiday,
                        discount_percentage: 18,
                        discount_code: holidayCode,
                        gift_wrapping_available: true,
                        express_shipping_discount: true
                    });
                }, 25000);
            }
        });
    }

    /**
     * Setup milestone and achievement discount triggers
     */
    setupMilestoneDiscountTriggers(userContext) {
        // First purchase milestone
        if (userContext.isNewVisitor && this.getCartValue() > 0) {
            setTimeout(() => {
                const firstPurchaseCode = this.generateDiscountCode('FIRST', 20);
                
                this.evaluateTrigger('first_purchase_milestone', {
                    discount_percentage: 20,
                    discount_code: firstPurchaseCode,
                    milestone_type: 'first_purchase',
                    welcome_bonus: true,
                    user_context: userContext
                });
            }, 120000); // 2 minutes
        }
        
        // Loyalty milestone trigger
        if (userContext.loyaltyPoints && userContext.loyaltyPoints >= 1000) {
            setTimeout(() => {
                this.evaluateTrigger('loyalty_milestone_reward', {
                    loyalty_points: userContext.loyaltyPoints,
                    milestone_reached: 1000,
                    reward_type: 'discount_upgrade',
                    special_recognition: true
                });
            }, 40000);
        }
        
        // Session engagement milestone
        if (userContext.sessionTime > 600000 && userContext.pageViews > 5) { // 10+ minutes, 5+ pages
            setTimeout(() => {
                const engagementCode = this.generateDiscountCode('ENGAGED', 8);
                
                this.evaluateTrigger('engagement_milestone_reward', {
                    discount_percentage: 8,
                    discount_code: engagementCode,
                    session_time: userContext.sessionTime,
                    page_views: userContext.pageViews,
                    engagement_level: 'high'
                });
            }, 50000);
        }
    }

    /**
     * Monitor cart behavior for abandonment and assistance triggers
     */
    monitorCartBehavior() {
        // Monitor add to cart actions
        this.monitorAddToCartActions();
        
        // Monitor cart page visits without checkout
        this.monitorCartPageBehavior();
        
        // Monitor checkout abandonment
        this.monitorCheckoutAbandonment();
    }

    /**
     * Monitor add to cart actions
     */
    monitorAddToCartActions() {
        const addToCartButtons = document.querySelectorAll(
            '.add-to-cart, [data-add-to-cart], .btn-add-cart, .add-cart-btn'
        );
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const productElement = button.closest('.product-item, .product-card, [data-product-id]');
                const productId = productElement ? 
                    productElement.getAttribute('data-product-id') : null;
                
                // Track the action
                this.userSession.trackCartAction('add', productId);
                
                // Set timer for cart abandonment detection
                setTimeout(() => {
                    const cartValue = this.getCartValue();
                    if (cartValue > 0 && !this.hasVisitedCheckout()) {
                        this.evaluateTrigger('cart_abandonment_after_add', {
                            product_id: productId,
                            cart_value: cartValue,
                            time_since_add: 300000 // 5 minutes
                        });
                    }
                }, 300000); // 5 minutes
            });
        });
    }

    /**
     * Monitor cart page behavior
     */
    monitorCartPageBehavior() {
        if (this.detectPageType() === 'cart') {
            // User is on cart page, monitor for checkout progression
            setTimeout(() => {
                if (!this.hasStartedCheckout()) {
                    this.evaluateTrigger('cart_page_hesitation', {
                        cart_value: this.getCartValue(),
                        items_count: this.getCartItemsCount(),
                        time_on_cart: 120000 // 2 minutes
                    });
                }
            }, 120000); // 2 minutes
        }
    }

    /**
     * Monitor checkout abandonment
     */
    monitorCheckoutAbandonment() {
        const checkoutStep = this.detectCheckoutStep();
        
        if (['checkout', 'shipping', 'payment'].includes(checkoutStep)) {
            // Monitor for checkout abandonment
            setTimeout(() => {
                if (!this.hasCompletedPurchase()) {
                    this.evaluateTrigger('checkout_abandonment', {
                        checkout_step: checkoutStep,
                        cart_value: this.getCartValue(),
                        time_in_checkout: 300000 // 5 minutes
                    });
                }
            }, 300000); // 5 minutes
        }
    }

    /**
     * Monitor navigation frustration patterns
     */
    monitorNavigationFrustration() {
        let rapidNavigationCount = 0;
        let lastNavigationTime = Date.now();
        
        const trackNavigation = () => {
            const now = Date.now();
            const timeSinceLastNav = now - lastNavigationTime;
            
            if (timeSinceLastNav < 3000) { // Less than 3 seconds
                rapidNavigationCount++;
                
                if (rapidNavigationCount >= 3) {
                    this.evaluateTrigger('navigation_frustration', {
                        rapid_navigation_count: rapidNavigationCount,
                        pattern: 'rapid_page_changes'
                    });
                    rapidNavigationCount = 0; // Reset counter
                }
            } else {
                rapidNavigationCount = 0; // Reset if navigation is slower
            }
            
            lastNavigationTime = now;
        };

        // Track various navigation events
        window.addEventListener('beforeunload', trackNavigation);
        window.addEventListener('hashchange', trackNavigation);
        window.addEventListener('popstate', trackNavigation);
        
        // Track back button usage
        let backButtonCount = 0;
        window.addEventListener('popstate', () => {
            backButtonCount++;
            if (backButtonCount >= 3) {
                this.evaluateTrigger('excessive_back_button_use', {
                    back_button_count: backButtonCount
                });
            }
        });
    }

    /**
     * Monitor product browsing patterns
     */
    monitorProductBrowsingPatterns() {
        let productViewCount = 0;
        let categoryViewCount = 0;
        let searchCount = 0;
        
        // Track product views
        if (this.detectPageType() === 'product') {
            productViewCount++;
            this.userSession.trackInteraction('product_view', {
                product_id: this.getCurrentProductId(),
                view_count: productViewCount
            });
            
            // Trigger for excessive product browsing without action
            if (productViewCount >= 5) {
                setTimeout(() => {
                    if (this.getCartValue() === 0) {
                        this.evaluateTrigger('excessive_browsing_no_action', {
                            products_viewed: productViewCount,
                            pattern: 'browsing_without_purchasing'
                        });
                    }
                }, 60000); // 1 minute delay
            }
        }
        
        // Track category browsing
        if (this.detectPageType() === 'category') {
            categoryViewCount++;
            
            if (categoryViewCount >= 3) {
                this.evaluateTrigger('category_browsing_assistance', {
                    categories_viewed: categoryViewCount,
                    current_category: this.detectCategory()
                });
            }
        }
        
        // Track search behavior
        const searchQuery = this.getCurrentSearchQuery();
        if (searchQuery) {
            searchCount++;
            this.userSession.trackSearchQuery(searchQuery, this.getSearchResultsCount());
            
            if (searchCount >= 3) {
                this.evaluateTrigger('multiple_searches_assistance', {
                    search_count: searchCount,
                    last_query: searchQuery
                });
            }
        }
    }

    /**
     * Monitor search results
     */
    monitorSearchResults() {
        const searchForms = document.querySelectorAll('form[role="search"], .search-form, #search-form');
        
        searchForms.forEach(form => {
            form.addEventListener('submit', async (e) => {
                const formData = new FormData(form);
                const searchTerm = formData.get('q') || formData.get('search') || formData.get('query');
                
                if (searchTerm) {
                    // Wait a bit for results to load
                    setTimeout(() => {
                        const hasResults = this.checkSearchResults();
                        if (!hasResults) {
                            this.evaluateTrigger('search_no_results', {
                                search_term: searchTerm
                            });
                        }
                    }, 2000);
                }
            });
        });
    }

    /**
     * Check if search has results
     */
    checkSearchResults() {
        const noResultsIndicators = [
            '.no-results',
            '.search-no-results',
            '[data-no-results]',
            '.empty-results'
        ];
        
        const hasNoResultsIndicator = noResultsIndicators.some(selector => 
            document.querySelector(selector)
        );
        
        if (hasNoResultsIndicator) return false;
        
        // Check for common result containers
        const resultContainers = [
            '.search-results',
            '.product-list',
            '.results',
            '[data-search-results]'
        ];
        
        for (const selector of resultContainers) {
            const container = document.querySelector(selector);
            if (container && container.children.length === 0) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Monitor product comparison behavior
     */
    monitorProductComparison() {
        let viewedProducts = new Set();
        
        const trackProductView = () => {
            const productId = this.getCurrentProductId();
            if (productId) {
                viewedProducts.add(productId);
                
                if (viewedProducts.size >= 3) {
                    this.evaluateTrigger('product_comparison', {
                        viewed_products: Array.from(viewedProducts)
                    });
                }
            }
        };

        // Track on page load and navigation
        trackProductView();
        
        // Track on hash changes (for SPAs)
        window.addEventListener('hashchange', trackProductView);
        window.addEventListener('popstate', trackProductView);
    }

    /**
     * Monitor page visits
     */
    monitorPageVisits() {
        const currentUrl = window.location.href;
        const visitCount = this.userSession.getPageVisitCount(currentUrl);
        
        if (visitCount >= 3) {
            this.evaluateTrigger('multiple_page_visits', {
                page_url: currentUrl,
                visit_count: visitCount
            });
        }
    }

    /**
     * Setup product context triggers
     */
    setupProductContextTriggers() {
        const products = this.detectPageProducts();
        
        if (products.length > 0) {
            // Initialize intelligent contextual triggers
            this.setupIntelligentContextualTriggers(products);
        }
    }

    /**
     * Setup intelligent contextual trigger system
     */
    setupIntelligentContextualTriggers(products) {
        const userContext = this.getUserContext();
        const timeContext = this.getTimeContext();
        
        // Category-specific triggers
        this.setupCategorySpecificTriggers(products, userContext);
        
        // Seasonal and time-based triggers
        this.setupSeasonalTriggers(products, timeContext);
        
        // Customer tier-based triggers
        this.setupCustomerTierTriggers(products, userContext);
        
        // Smart product recommendation triggers
        this.setupSmartRecommendationTriggers(products, userContext);
    }

    /**
     * Setup category-specific trigger logic
     */
    setupCategorySpecificTriggers(products, userContext) {
        // Skincare category triggers
        const skincareProducts = products.filter(p => this.isSkincareProduct(p));
        if (skincareProducts.length > 0) {
            this.setupSkincareSpecificTriggers(skincareProducts, userContext);
        }
        
        // Anti-aging category triggers
        const antiAgingProducts = products.filter(p => this.isAntiAgingProduct(p));
        if (antiAgingProducts.length > 0) {
            this.setupAntiAgingSpecificTriggers(antiAgingProducts, userContext);
        }
        
        // Luxury/Premium category triggers
        const luxuryProducts = products.filter(p => this.isLuxuryProduct(p));
        if (luxuryProducts.length > 0) {
            this.setupLuxurySpecificTriggers(luxuryProducts, userContext);
        }
        
        // Makeup category triggers
        const makeupProducts = products.filter(p => this.isMakeupProduct(p));
        if (makeupProducts.length > 0) {
            this.setupMakeupSpecificTriggers(makeupProducts, userContext);
        }
        
        // Hair care category triggers
        const hairProducts = products.filter(p => this.isHairCareProduct(p));
        if (hairProducts.length > 0) {
            this.setupHairCareSpecificTriggers(hairProducts, userContext);
        }
    }

    /**
     * Setup skincare-specific triggers
     */
    setupSkincareSpecificTriggers(products, userContext) {
        // Routine consultation trigger
        setTimeout(() => {
            this.evaluateTrigger('skincare_routine_consultation', {
                products: products,
                user_context: userContext,
                trigger_reason: 'skincare_category_detected'
            });
        }, 45000);
        
        // Skin type assessment trigger
        if (userContext.isNewVisitor) {
            setTimeout(() => {
                this.evaluateTrigger('skin_type_assessment', {
                    products: products,
                    user_context: userContext
                });
            }, 60000);
        }
        
        // Product combination advice
        if (products.length > 1) {
            setTimeout(() => {
                this.evaluateTrigger('skincare_combination_advice', {
                    products: products,
                    combination_count: products.length
                });
            }, 90000);
        }
    }

    /**
     * Setup anti-aging specific triggers
     */
    setupAntiAgingSpecificTriggers(products, userContext) {
        // Age-appropriate recommendations
        setTimeout(() => {
            this.evaluateTrigger('anti_aging_consultation', {
                products: products,
                user_context: userContext,
                age_category: this.estimateAgeCategory(userContext)
            });
        }, 50000);
        
        // Prevention vs treatment advice
        setTimeout(() => {
            this.evaluateTrigger('prevention_vs_treatment_advice', {
                products: products,
                user_context: userContext
            });
        }, 75000);
    }

    /**
     * Setup luxury product specific triggers
     */
    setupLuxurySpecificTriggers(products, userContext) {
        // VIP treatment trigger
        if (userContext.customerTier === 'vip' || userContext.customerTier === 'premium') {
            setTimeout(() => {
                this.evaluateTrigger('vip_luxury_consultation', {
                    products: products,
                    user_context: userContext
                });
            }, 30000);
        }
        
        // Investment value explanation
        setTimeout(() => {
            this.evaluateTrigger('luxury_investment_explanation', {
                products: products,
                total_value: this.calculateProductsValue(products)
            });
        }, 60000);
        
        // Exclusive access trigger
        setTimeout(() => {
            this.evaluateTrigger('luxury_exclusive_access', {
                products: products,
                user_context: userContext
            });
        }, 45000);
    }

    /**
     * Setup makeup specific triggers
     */
    setupMakeupSpecificTriggers(products, userContext) {
        // Color matching assistance
        setTimeout(() => {
            this.evaluateTrigger('makeup_color_matching', {
                products: products,
                user_context: userContext
            });
        }, 40000);
        
        // Look creation assistance
        setTimeout(() => {
            this.evaluateTrigger('makeup_look_creation', {
                products: products,
                occasion: this.detectOccasionContext()
            });
        }, 70000);
    }

    /**
     * Setup hair care specific triggers
     */
    setupHairCareSpecificTriggers(products, userContext) {
        // Hair type consultation
        setTimeout(() => {
            this.evaluateTrigger('hair_type_consultation', {
                products: products,
                user_context: userContext
            });
        }, 50000);
        
        // Hair routine building
        setTimeout(() => {
            this.evaluateTrigger('hair_routine_building', {
                products: products,
                user_context: userContext
            });
        }, 80000);
    }

    /**
     * Setup seasonal and time-based contextual triggers
     */
    setupSeasonalTriggers(products, timeContext) {
        const currentSeason = timeContext.season;
        const currentMonth = timeContext.month;
        const timeOfDay = timeContext.timeOfDay;
        
        // Seasonal skincare adjustments
        if (this.hasSkincareProducts(products)) {
            this.setupSeasonalSkincareTrigers(products, currentSeason, timeOfDay);
        }
        
        // Holiday and special occasion triggers
        this.setupHolidayTriggers(products, timeContext);
        
        // Time-of-day specific triggers
        this.setupTimeOfDayTriggers(products, timeOfDay);
        
        // Weather-based triggers (if available)
        this.setupWeatherBasedTriggers(products, timeContext);
    }

    /**
     * Setup seasonal skincare triggers
     */
    setupSeasonalSkincareTrigers(products, season, timeOfDay) {
        const seasonalAdvice = {
            'winter': {
                focus: 'hydration',
                message: 'winter_skincare_hydration',
                delay: 40000
            },
            'summer': {
                focus: 'protection',
                message: 'summer_skincare_protection',
                delay: 35000
            },
            'spring': {
                focus: 'renewal',
                message: 'spring_skincare_renewal',
                delay: 45000
            },
            'autumn': {
                focus: 'preparation',
                message: 'autumn_skincare_preparation',
                delay: 50000
            }
        };
        
        const advice = seasonalAdvice[season];
        if (advice) {
            setTimeout(() => {
                this.evaluateTrigger(advice.message, {
                    products: products,
                    season: season,
                    focus: advice.focus,
                    time_of_day: timeOfDay
                });
            }, advice.delay);
        }
    }

    /**
     * Setup holiday and special occasion triggers
     */
    setupHolidayTriggers(products, timeContext) {
        const holidays = this.detectUpcomingHolidays(timeContext);
        
        holidays.forEach(holiday => {
            setTimeout(() => {
                this.evaluateTrigger('holiday_preparation_trigger', {
                    products: products,
                    holiday: holiday.name,
                    days_until: holiday.daysUntil,
                    occasion_type: holiday.type
                });
            }, 25000);
        });
        
        // Gift-giving occasions
        if (this.isGiftGivingSeason(timeContext)) {
            setTimeout(() => {
                this.evaluateTrigger('gift_giving_assistance', {
                    products: products,
                    occasion: this.getCurrentGiftOccasion(timeContext)
                });
            }, 35000);
        }
    }

    /**
     * Setup time-of-day specific triggers
     */
    setupTimeOfDayTriggers(products, timeOfDay) {
        const timeBasedTriggers = {
            'morning': {
                message: 'morning_routine_optimization',
                focus: 'energizing',
                delay: 30000
            },
            'afternoon': {
                message: 'midday_touch_up_tips',
                focus: 'maintenance',
                delay: 40000
            },
            'evening': {
                message: 'evening_routine_enhancement',
                focus: 'recovery',
                delay: 35000
            },
            'night': {
                message: 'nighttime_skincare_routine',
                focus: 'repair',
                delay: 25000
            }
        };
        
        const trigger = timeBasedTriggers[timeOfDay];
        if (trigger) {
            setTimeout(() => {
                this.evaluateTrigger(trigger.message, {
                    products: products,
                    time_of_day: timeOfDay,
                    focus: trigger.focus
                });
            }, trigger.delay);
        }
    }

    /**
     * Setup weather-based triggers
     */
    setupWeatherBasedTriggers(products, timeContext) {
        // This would integrate with weather API if available
        const weatherCondition = timeContext.weather || this.estimateWeatherFromSeason(timeContext.season);
        
        if (weatherCondition) {
            setTimeout(() => {
                this.evaluateTrigger('weather_based_skincare_advice', {
                    products: products,
                    weather: weatherCondition,
                    season: timeContext.season
                });
            }, 55000);
        }
    }

    /**
     * Setup customer tier-based trigger personalization
     */
    setupCustomerTierTriggers(products, userContext) {
        const customerTier = userContext.customerTier;
        const purchaseHistory = userContext.purchaseHistory || {};
        
        switch (customerTier) {
            case 'vip':
            case 'premium':
                this.setupVIPTriggers(products, userContext);
                break;
                
            case 'loyal':
                this.setupLoyalCustomerTriggers(products, userContext);
                break;
                
            case 'new':
                this.setupNewCustomerTriggers(products, userContext);
                break;
                
            default:
                this.setupRegularCustomerTriggers(products, userContext);
        }
    }

    /**
     * Setup VIP customer triggers
     */
    setupVIPTriggers(products, userContext) {
        // Exclusive early access
        setTimeout(() => {
            this.evaluateTrigger('vip_exclusive_early_access', {
                products: products,
                user_context: userContext,
                tier: 'vip'
            });
        }, 20000);
        
        // Personal shopper service
        setTimeout(() => {
            this.evaluateTrigger('vip_personal_shopper_service', {
                products: products,
                user_context: userContext
            });
        }, 45000);
        
        // Complimentary services
        setTimeout(() => {
            this.evaluateTrigger('vip_complimentary_services', {
                products: products,
                services_available: this.getAvailableVIPServices()
            });
        }, 60000);
    }

    /**
     * Setup loyal customer triggers
     */
    setupLoyalCustomerTriggers(products, userContext) {
        // Loyalty rewards reminder
        setTimeout(() => {
            this.evaluateTrigger('loyalty_rewards_available', {
                products: products,
                points_available: userContext.loyaltyPoints || 0,
                user_context: userContext
            });
        }, 35000);
        
        // Replenishment reminders
        if (userContext.purchaseHistory && userContext.purchaseHistory.length > 0) {
            setTimeout(() => {
                this.evaluateTrigger('product_replenishment_reminder', {
                    products: products,
                    previous_purchases: userContext.purchaseHistory
                });
            }, 50000);
        }
    }

    /**
     * Setup new customer triggers
     */
    setupNewCustomerTriggers(products, userContext) {
        // Welcome and education
        setTimeout(() => {
            this.evaluateTrigger('new_customer_welcome_education', {
                products: products,
                user_context: userContext
            });
        }, 25000);
        
        // First purchase incentive
        setTimeout(() => {
            this.evaluateTrigger('new_customer_first_purchase_incentive', {
                products: products,
                incentive_type: 'welcome_discount'
            });
        }, 40000);
        
        // Brand introduction
        setTimeout(() => {
            this.evaluateTrigger('brand_introduction_trigger', {
                products: products,
                brand_values: this.getBrandValues()
            });
        }, 70000);
    }

    /**
     * Setup regular customer triggers
     */
    setupRegularCustomerTriggers(products, userContext) {
        // Value proposition
        setTimeout(() => {
            this.evaluateTrigger('value_proposition_trigger', {
                products: products,
                user_context: userContext
            });
        }, 45000);
        
        // Educational content
        setTimeout(() => {
            this.evaluateTrigger('educational_content_trigger', {
                products: products,
                content_type: this.getRelevantContentType(products)
            });
        }, 65000);
    }

    /**
     * Setup smart product recommendation triggers
     */
    setupSmartRecommendationTriggers(products, userContext) {
        // Complementary product recommendations
        this.setupComplementaryRecommendations(products, userContext);
        
        // Upgrade recommendations
        this.setupUpgradeRecommendations(products, userContext);
        
        // Cross-category recommendations
        this.setupCrossCategoryRecommendations(products, userContext);
        
        // Personalized recommendations based on behavior
        this.setupBehaviorBasedRecommendations(products, userContext);
    }

    /**
     * Setup complementary product recommendations
     */
    setupComplementaryRecommendations(products, userContext) {
        const complementaryProducts = this.findComplementaryProducts(products);
        
        if (complementaryProducts.length > 0) {
            setTimeout(() => {
                this.evaluateTrigger('complementary_products_recommendation', {
                    current_products: products,
                    recommended_products: complementaryProducts,
                    recommendation_reason: 'complementary_routine'
                });
            }, 55000);
        }
    }

    /**
     * Setup upgrade recommendations
     */
    setupUpgradeRecommendations(products, userContext) {
        const upgradeOptions = this.findUpgradeOptions(products, userContext);
        
        if (upgradeOptions.length > 0) {
            setTimeout(() => {
                this.evaluateTrigger('product_upgrade_recommendation', {
                    current_products: products,
                    upgrade_options: upgradeOptions,
                    upgrade_benefits: this.getUpgradeBenefits(upgradeOptions)
                });
            }, 75000);
        }
    }

    /**
     * Setup cross-category recommendations
     */
    setupCrossCategoryRecommendations(products, userContext) {
        const crossCategoryProducts = this.findCrossCategoryProducts(products, userContext);
        
        if (crossCategoryProducts.length > 0) {
            setTimeout(() => {
                this.evaluateTrigger('cross_category_recommendation', {
                    current_products: products,
                    recommended_products: crossCategoryProducts,
                    cross_sell_reason: this.getCrossSellReason(products, crossCategoryProducts)
                });
            }, 85000);
        }
    }

    /**
     * Setup behavior-based recommendations
     */
    setupBehaviorBasedRecommendations(products, userContext) {
        const behaviorPattern = this.userSession.getBehaviorPatterns();
        const recommendedProducts = this.getRecommendationsFromBehavior(behaviorPattern, products);
        
        if (recommendedProducts.length > 0) {
            setTimeout(() => {
                this.evaluateTrigger('behavior_based_recommendation', {
                    current_products: products,
                    recommended_products: recommendedProducts,
                    behavior_pattern: behaviorPattern,
                    confidence_score: this.calculateRecommendationConfidence(behaviorPattern)
                });
            }, 90000);
        }
    }

    /**
     * Setup scroll-based triggers
     */
    setupScrollTriggers() {
        let scrollDepth = 0;
        let maxScrollDepth = 0;
        
        const updateScrollDepth = () => {
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            scrollDepth = Math.round((scrollTop + windowHeight) / documentHeight * 100);
            maxScrollDepth = Math.max(maxScrollDepth, scrollDepth);
            
            // Trigger at 75% scroll depth
            if (maxScrollDepth >= 75 && !this.userSession.hasTriggered('scroll_depth_75')) {
                this.evaluateTrigger('scroll_depth_75', {
                    scroll_depth: maxScrollDepth
                });
                this.userSession.markTriggered('scroll_depth_75');
            }
        };

        window.addEventListener('scroll', this.debounce(updateScrollDepth, 250), { passive: true });
    }

    /**
     * Evaluate a trigger
     */
    async evaluateTrigger(triggerType, context = {}) {
        try {
            // Check if trigger is active
            const trigger = this.activeTriggers.find(t => t.trigger_type === triggerType);
            if (!trigger || !trigger.is_active) {
                return;
            }

            // Build context
            const fullContext = {
                ...context,
                session_id: this.userSession.getSessionId(),
                user_id: this.userSession.getUserId(),
                page_type: this.detectPageType(),
                page_url: window.location.href,
                session_time: this.userSession.getSessionTime(),
                is_new_visitor: this.userSession.isNewVisitor(),
                cart_value: this.getCartValue(),
                device_type: this.getDeviceType(),
                referrer: document.referrer,
                timestamp: new Date().toISOString()
            };

            this.log('Evaluating trigger:', triggerType, fullContext);

            // Send to server for evaluation
            const response = await fetch(this.config.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify({
                    chatbot_id: this.chatbotId,
                    trigger_type: triggerType,
                    context: fullContext
                })
            });

            if (response.ok) {
                const result = await response.json();
                if (result.trigger) {
                    this.displayTrigger(result.trigger);
                }
            }

        } catch (error) {
            console.error('Failed to evaluate trigger:', triggerType, error);
        }
    }

    /**
     * Display trigger to user
     */
    displayTrigger(triggerData) {
        // Add to queue if chatbot is not ready
        if (!this.isChatbotReady()) {
            this.triggerQueue.push(triggerData);
            return;
        }

        this.log('Displaying trigger:', triggerData);

        // Create trigger message element
        const triggerElement = this.createTriggerElement(triggerData);
        
        // Show trigger with animation
        this.showTriggerWithAnimation(triggerElement, triggerData);
        
        // Track display
        this.analytics.trackTriggerDisplay(triggerData);
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
        
        element.innerHTML = `
            <div class="trigger-content">
                <div class="trigger-message">${triggerData.message}</div>
                <div class="trigger-actions">
                    <button class="trigger-btn trigger-btn-primary" data-action="accept">
                        ${this.getTriggerActionText(triggerData.action)}
                    </button>
                    <button class="trigger-btn trigger-btn-secondary" data-action="dismiss">
                        Cerrar
                    </button>
                </div>
            </div>
            <button class="trigger-close" data-action="close">&times;</button>
        `;
        
        // Apply display configuration
        this.applyDisplayConfig(element, displayConfig);
        
        // Add event listeners
        this.addTriggerEventListeners(element, triggerData);
        
        return element;
    }

    /**
     * Get action text for trigger
     */
    getTriggerActionText(action) {
        return {
            'show_message': 'Chatear',
            'show_discount': 'Ver Oferta',
            'show_product_recommendations': 'Ver Productos',
            'collect_email': 'Suscribirse',
            'start_consultation': 'Comenzar'
        }[action] || 'Continuar';
    }

    /**
     * Apply display configuration
     */
    applyDisplayConfig(element, config) {
        const position = config.position || 'bottom-right';
        const animation = config.animation || 'slide-up';
        
        element.classList.add(`trigger-position-${position}`);
        element.classList.add(`trigger-animation-${animation}`);
        
        if (config.theme) {
            element.classList.add(`trigger-theme-${config.theme}`);
        }
    }

    /**
     * Add event listeners to trigger element
     */
    addTriggerEventListeners(element, triggerData) {
        element.addEventListener('click', (e) => {
            const action = e.target.getAttribute('data-action');
            
            if (action) {
                this.handleTriggerAction(action, triggerData, element);
            }
        });
    }

    /**
     * Handle trigger action
     */
    handleTriggerAction(action, triggerData, element) {
        this.log('Trigger action:', action, triggerData);
        
        switch (action) {
            case 'accept':
                this.analytics.trackTriggerResponse(triggerData, 'accepted');
                this.executeTriggerAction(triggerData);
                break;
                
            case 'dismiss':
                this.analytics.trackTriggerResponse(triggerData, 'dismissed');
                break;
                
            case 'close':
                this.analytics.trackTriggerResponse(triggerData, 'closed');
                break;
        }
        
        // Remove trigger element
        this.removeTriggerElement(element);
    }

    /**
     * Execute trigger action
     */
    executeTriggerAction(triggerData) {
        switch (triggerData.action) {
            case 'show_message':
                this.openChatbot(triggerData.message);
                break;
                
            case 'show_discount':
                this.showDiscountModal(triggerData.context.discount);
                break;
                
            case 'show_product_recommendations':
                this.showProductRecommendations(triggerData.context.products);
                break;
                
            case 'collect_email':
                this.showEmailCollectionModal();
                break;
                
            case 'start_consultation':
                this.startConsultation(triggerData);
                break;
        }
    }

    /**
     * Show trigger with animation
     */
    showTriggerWithAnimation(element, triggerData) {
        document.body.appendChild(element);
        
        // Trigger animation
        requestAnimationFrame(() => {
            element.classList.add('trigger-show');
        });
        
        // Auto-hide after delay if configured
        const displayConfig = triggerData.display_config || {};
        if (displayConfig.auto_hide_delay) {
            setTimeout(() => {
                this.removeTriggerElement(element);
            }, displayConfig.auto_hide_delay * 1000);
        }
    }

    /**
     * Remove trigger element
     */
    removeTriggerElement(element) {
        element.classList.add('trigger-hide');
        
        setTimeout(() => {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        }, 300);
    }

    // Utility methods
    detectPageType() {
        const url = window.location.pathname.toLowerCase();
        
        if (url.includes('/product') || url.includes('/item')) return 'product';
        if (url.includes('/category') || url.includes('/collection')) return 'category';
        if (url.includes('/cart') || url.includes('/basket')) return 'cart';
        if (url.includes('/checkout') || url.includes('/payment')) return 'checkout';
        if (url.includes('/contact')) return 'contact';
        if (url === '/' || url === '/home') return 'home';
        
        return 'other';
    }

    detectPageProducts() {
        // This would be customized based on the website structure
        const products = [];
        
        // Try to detect products from common selectors
        const productElements = document.querySelectorAll(
            '[data-product-id], .product-item, .product-card, [itemtype*="Product"]'
        );
        
        productElements.forEach(element => {
            const product = this.extractProductInfo(element);
            if (product) {
                products.push(product);
            }
        });
        
        return products;
    }

    extractProductInfo(element) {
        // Extract product information from DOM element
        return {
            id: element.getAttribute('data-product-id') || element.getAttribute('data-id'),
            name: this.getTextContent(element, '.product-name, .product-title, h1, h2'),
            price: this.getTextContent(element, '.price, .product-price, [data-price]'),
            category: this.getTextContent(element, '.category, .product-category, [data-category]'),
            image: this.getImageSrc(element, '.product-image img, img'),
            url: this.getHref(element, 'a') || window.location.href
        };
    }

    getTextContent(parent, selector) {
        const element = parent.querySelector(selector);
        return element ? element.textContent.trim() : '';
    }

    getImageSrc(parent, selector) {
        const element = parent.querySelector(selector);
        return element ? element.src : '';
    }

    getHref(parent, selector) {
        const element = parent.querySelector(selector);
        return element ? element.href : '';
    }

    getCurrentProductId() {
        const productElement = document.querySelector('[data-product-id]');
        return productElement ? productElement.getAttribute('data-product-id') : null;
    }

    isSkincareProduct(product) {
        const skincareKeywords = ['crema', 'serum', 'limpiador', 'hidratante', 'protector', 'facial', 'skincare'];
        const text = (product.name + ' ' + product.category).toLowerCase();
        return skincareKeywords.some(keyword => text.includes(keyword));
    }

    getCartItems() {
        // This would be customized based on the website's cart implementation
        const cartItems = [];
        const cartElements = document.querySelectorAll('.cart-item, [data-cart-item]');
        
        cartElements.forEach(element => {
            const item = {
                id: element.getAttribute('data-product-id'),
                name: this.getTextContent(element, '.item-name, .product-name'),
                quantity: parseInt(this.getTextContent(element, '.quantity, [data-quantity]')) || 1,
                price: parseFloat(this.getTextContent(element, '.price, [data-price]').replace(/[^0-9.]/g, '')) || 0
            };
            cartItems.push(item);
        });
        
        return cartItems;
    }

    getCartValue() {
        const cartItems = this.getCartItems();
        return cartItems.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    getDeviceType() {
        const width = window.innerWidth;
        if (width <= 768) return 'mobile';
        if (width <= 1024) return 'tablet';
        return 'desktop';
    }

    isChatbotReady() {
        // Check if chatbot widget is loaded and ready
        return window.chatbotWidget && window.chatbotWidget.isReady;
    }

    openChatbot(message) {
        if (window.chatbotWidget) {
            window.chatbotWidget.open();
            if (message) {
                window.chatbotWidget.sendMessage(message);
            }
        }
    }

    showDiscountModal(discount) {
        // Implementation would depend on the website's modal system
        console.log('Show discount modal:', discount);
    }

    showProductRecommendations(products) {
        // Implementation would depend on the website's product display system
        console.log('Show product recommendations:', products);
    }

    showEmailCollectionModal() {
        // Implementation would depend on the website's modal system
        console.log('Show email collection modal');
    }

    startConsultation(triggerData) {
        this.openChatbot('Me gustaría una consulta personalizada');
    }

    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Enhanced detection methods for page context
    
    getMetaDescription() {
        const meta = document.querySelector('meta[name="description"]');
        return meta ? meta.getAttribute('content') : '';
    }
    
    getMetaKeywords() {
        const meta = document.querySelector('meta[name="keywords"]');
        return meta ? meta.getAttribute('content') : '';
    }
    
    detectCategory() {
        // Try multiple methods to detect category
        const breadcrumbs = this.getBreadcrumbs();
        if (breadcrumbs.length > 1) {
            return breadcrumbs[breadcrumbs.length - 2];
        }
        
        const categoryMeta = document.querySelector('meta[name="category"], [data-category]');
        if (categoryMeta) {
            return categoryMeta.getAttribute('content') || categoryMeta.getAttribute('data-category');
        }
        
        // Try to extract from URL
        const pathParts = window.location.pathname.split('/').filter(part => part);
        if (pathParts.includes('category') && pathParts.length > 1) {
            const categoryIndex = pathParts.indexOf('category');
            return pathParts[categoryIndex + 1];
        }
        
        return 'unknown';
    }
    
    countCategoryProducts() {
        const productElements = document.querySelectorAll(
            '.product-item, .product-card, [data-product-id], [itemtype*="Product"]'
        );
        return productElements.length;
    }
    
    detectFilters() {
        const filterElements = document.querySelectorAll(
            '.filter, .filters, [data-filter], .sidebar-filter, .product-filter'
        );
        return filterElements.length > 0;
    }
    
    checkFiltersUsed() {
        // Check if any filters are active
        const activeFilters = document.querySelectorAll(
            '.filter.active, .filter-active, [data-filter-active], .selected-filter'
        );
        return activeFilters.length > 0;
    }
    
    getAvailableFilters() {
        const filters = [];
        const filterElements = document.querySelectorAll('.filter, [data-filter]');
        
        filterElements.forEach(element => {
            const filterName = element.getAttribute('data-filter-name') || 
                              element.textContent.trim();
            if (filterName) {
                filters.push(filterName);
            }
        });
        
        return filters;
    }
    
    isSeasonalCategory() {
        const seasonalKeywords = ['navidad', 'verano', 'invierno', 'primavera', 'otoño', 'halloween', 'san valentin'];
        const categoryText = this.detectCategory().toLowerCase();
        const pageText = (document.title + ' ' + this.getMetaDescription()).toLowerCase();
        
        return seasonalKeywords.some(keyword => 
            categoryText.includes(keyword) || pageText.includes(keyword)
        );
    }
    
    getCurrentSeason() {
        const month = new Date().getMonth();
        if (month >= 2 && month <= 4) return 'primavera';
        if (month >= 5 && month <= 7) return 'verano';
        if (month >= 8 && month <= 10) return 'otoño';
        return 'invierno';
    }
    
    getCartItemsCount() {
        const cartItems = this.getCartItems();
        return cartItems.reduce((total, item) => total + item.quantity, 0);
    }
    
    detectCheckoutStep() {
        const url = window.location.pathname.toLowerCase();
        
        if (url.includes('payment') || url.includes('billing')) return 'payment';
        if (url.includes('shipping') || url.includes('delivery')) return 'shipping';
        if (url.includes('review') || url.includes('confirm')) return 'review';
        if (url.includes('checkout')) return 'checkout';
        
        // Try to detect from page elements
        if (document.querySelector('.payment-form, #payment-form')) return 'payment';
        if (document.querySelector('.shipping-form, #shipping-form')) return 'shipping';
        if (document.querySelector('.order-review, .checkout-review')) return 'review';
        
        return 'cart';
    }
    
    detectShippingOptions() {
        const shippingElements = document.querySelectorAll(
            '.shipping-option, [name="shipping"], .delivery-option'
        );
        return shippingElements.length > 0;
    }
    
    checkShippingSelected() {
        const selectedShipping = document.querySelector(
            '.shipping-option:checked, [name="shipping"]:checked, .shipping-selected'
        );
        return selectedShipping !== null;
    }
    
    getShippingOptions() {
        const options = [];
        const shippingElements = document.querySelectorAll('.shipping-option, [name="shipping"]');
        
        shippingElements.forEach(element => {
            const label = element.nextElementSibling || element.parentElement;
            const optionText = label ? label.textContent.trim() : element.value;
            if (optionText) {
                options.push(optionText);
            }
        });
        
        return options;
    }
    
    isPaymentStep() {
        const paymentElements = document.querySelectorAll(
            '.payment-form, #payment-form, [name="payment"], .credit-card-form'
        );
        return paymentElements.length > 0;
    }
    
    getPaymentMethods() {
        const methods = [];
        const paymentElements = document.querySelectorAll('[name="payment"], .payment-method');
        
        paymentElements.forEach(element => {
            const method = element.value || element.getAttribute('data-method');
            if (method) {
                methods.push(method);
            }
        });
        
        return methods;
    }
    
    getCurrentSearchQuery() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('q') || urlParams.get('search') || urlParams.get('query') || '';
    }
    
    getSearchResultsCount() {
        // Try to find results count in common locations
        const countElements = document.querySelectorAll(
            '.results-count, .search-count, [data-results-count]'
        );
        
        for (const element of countElements) {
            const text = element.textContent;
            const match = text.match(/(\d+)/);
            if (match) {
                return parseInt(match[1]);
            }
        }
        
        // Fallback: count product elements
        return this.countCategoryProducts();
    }
    
    getBreadcrumbs() {
        const breadcrumbs = [];
        const breadcrumbElements = document.querySelectorAll(
            '.breadcrumb a, .breadcrumbs a, [data-breadcrumb]'
        );
        
        breadcrumbElements.forEach(element => {
            const text = element.textContent.trim();
            if (text && text !== 'Home' && text !== 'Inicio') {
                breadcrumbs.push(text);
            }
        });
        
        return breadcrumbs;
    }
    
    getPageClicks() {
        return this.userSession.sessionData.interactions.filter(
            interaction => interaction.type === 'click'
        ).length;
    }
    
    getPageLoadTime() {
        if (window.performance && window.performance.timing) {
            return window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
        }
        return 0;
    }
    
    detectPageErrors() {
        // Check for common error indicators
        const errorElements = document.querySelectorAll(
            '.error, .alert-error, .error-message, [data-error]'
        );
        return errorElements.length > 0;
    }
    
    detectRelatedProducts() {
        const relatedProducts = [];
        const relatedElements = document.querySelectorAll(
            '.related-products .product-item, .recommended-products .product-item, .similar-products .product-item'
        );
        
        relatedElements.forEach(element => {
            const product = this.extractProductInfo(element);
            if (product) {
                relatedProducts.push(product);
            }
        });
        
        return relatedProducts;
    }
    
    detectStockStatus() {
        const stockElements = document.querySelectorAll(
            '.stock-status, [data-stock], .inventory-status'
        );
        
        for (const element of stockElements) {
            const text = element.textContent.toLowerCase();
            const stockValue = element.getAttribute('data-stock');
            
            if (text.includes('pocas unidades') || text.includes('últimas unidades') || 
                text.includes('stock bajo') || (stockValue && parseInt(stockValue) < 5)) {
                return { isLow: true, level: stockValue ? parseInt(stockValue) : null };
            }
            
            if (text.includes('sin stock') || text.includes('agotado')) {
                return { isLow: true, level: 0 };
            }
        }
        
        return { isLow: false, level: null };
    }
    
    detectProductVariants() {
        const variantSelectors = document.querySelectorAll(
            '.product-variants select, .size-selector, .color-selector, [data-variant]'
        );
        
        const variants = [];
        let hasSelection = false;
        
        variantSelectors.forEach(selector => {
            const options = selector.querySelectorAll('option, .variant-option');
            const variantType = selector.getAttribute('data-variant-type') || 
                              selector.className.includes('size') ? 'size' : 
                              selector.className.includes('color') ? 'color' : 'variant';
            
            const variantOptions = [];
            options.forEach(option => {
                const value = option.value || option.getAttribute('data-value');
                const text = option.textContent.trim();
                if (value && text) {
                    variantOptions.push({ value, text });
                }
            });
            
            if (variantOptions.length > 0) {
                variants.push({
                    type: variantType,
                    options: variantOptions
                });
            }
            
            // Check if variant is selected
            if (selector.value || selector.querySelector('.selected, .active')) {
                hasSelection = true;
            }
        });
        
        return {
            hasVariants: variants.length > 0,
            selected: hasSelection,
            variants: variants
        };
    }

    /**
     * Check if user has visited checkout
     */
    hasVisitedCheckout() {
        return this.userSession.sessionData.pageViews.some(pv => 
            pv.url.includes('/checkout') || pv.url.includes('/payment')
        );
    }

    /**
     * Check if user has started checkout process
     */
    hasStartedCheckout() {
        const checkoutElements = document.querySelectorAll(
            '.checkout-btn, .proceed-checkout, [data-checkout], .btn-checkout'
        );
        
        // Check if any checkout buttons have been clicked
        return this.userSession.sessionData.interactions.some(interaction => 
            interaction.type === 'click' && 
            checkoutElements.some(btn => 
                btn.contains(interaction.data.element) || 
                btn.className.includes(interaction.data.className)
            )
        );
    }

    /**
     * Check if user has completed purchase
     */
    hasCompletedPurchase() {
        // Check for success/confirmation page indicators
        const successIndicators = [
            '/success', '/confirmation', '/thank-you', '/order-complete',
            '.order-success', '.purchase-complete', '.thank-you-message'
        ];
        
        const currentUrl = window.location.pathname.toLowerCase();
        const hasSuccessUrl = successIndicators.some(indicator => 
            currentUrl.includes(indicator)
        );
        
        if (hasSuccessUrl) return true;
        
        // Check for success elements on page
        const successElements = document.querySelectorAll(
            '.order-success, .purchase-complete, .confirmation-message, [data-order-success]'
        );
        
        return successElements.length > 0;
    }

    /**
     * Enhanced product detection with integration to existing system
     */
    async integrateWithProductDetection() {
        try {
            // Get detected products from the existing system
            const response = await fetch(`/api/v2/chatbot/${this.chatbotId}/products`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                const detectedProducts = data.products || [];
                
                // Enhance page context with detected products
                this.enhancePageContextWithProducts(detectedProducts);
                
                return detectedProducts;
            }
        } catch (error) {
            console.warn('Failed to integrate with product detection:', error);
        }
        
        return [];
    }

    /**
     * Enhance page context with detected products
     */
    enhancePageContextWithProducts(detectedProducts) {
        const currentUrl = window.location.href;
        const currentPageProducts = detectedProducts.filter(product => 
            currentUrl.includes(product.url) || 
            product.url.includes(window.location.pathname)
        );
        
        if (currentPageProducts.length > 0) {
            // Update page context with product information
            const product = currentPageProducts[0];
            
            // Estimate product value for high-value triggers
            const price = parseFloat(product.price?.replace(/[^0-9.]/g, '')) || 0;
            if (price > 500) {
                setTimeout(() => {
                    this.evaluateTrigger('high_value_product_detected', {
                        product: product,
                        price: price,
                        source: 'automatic_detection'
                    });
                }, 30000);
            }
            
            // Category-specific triggers
            if (product.category) {
                this.setupCategorySpecificTriggers(product.category, product);
            }
        }
    }

    /**
     * Setup category-specific triggers based on detected products
     */
    setupCategorySpecificTriggers(category, product) {
        const categoryLower = category.toLowerCase();
        
        // Skincare specific triggers
        if (categoryLower.includes('skincare') || categoryLower.includes('facial') || 
            categoryLower.includes('crema') || categoryLower.includes('serum')) {
            
            setTimeout(() => {
                this.evaluateTrigger('skincare_routine_consultation', {
                    product: product,
                    category: category,
                    trigger_reason: 'skincare_product_detected'
                });
            }, 45000);
        }
        
        // Anti-aging specific triggers
        if (categoryLower.includes('anti-age') || categoryLower.includes('antiarrugas') || 
            categoryLower.includes('rejuvenecedor')) {
            
            setTimeout(() => {
                this.evaluateTrigger('anti_aging_consultation', {
                    product: product,
                    category: category,
                    trigger_reason: 'anti_aging_product_detected'
                });
            }, 60000);
        }
        
        // High-end/luxury triggers
        if (categoryLower.includes('luxury') || categoryLower.includes('premium') || 
            categoryLower.includes('professional')) {
            
            setTimeout(() => {
                this.evaluateTrigger('luxury_product_consultation', {
                    product: product,
                    category: category,
                    trigger_reason: 'luxury_product_detected'
                });
            }, 30000);
        }
    }

    // Intelligent contextual system helper methods
    
    /**
     * Get comprehensive user context
     */
    getUserContext() {
        return {
            customerTier: this.userSession.getCustomerTier(),
            isNewVisitor: this.userSession.isNewVisitor(),
            purchaseHistory: this.getPurchaseHistory(),
            loyaltyPoints: this.getLoyaltyPoints(),
            preferences: this.getUserPreferences(),
            behaviorPattern: this.userSession.getBehaviorPatterns(),
            sessionTime: this.userSession.getSessionTime(),
            pageViews: this.userSession.sessionData.pageViews.length,
            deviceType: this.userSession.getDeviceType()
        };
    }

    /**
     * Get time and seasonal context
     */
    getTimeContext() {
        const now = new Date();
        const hour = now.getHours();
        
        return {
            season: this.getCurrentSeason(),
            month: now.getMonth() + 1,
            dayOfWeek: now.getDay(),
            hour: hour,
            timeOfDay: this.getTimeOfDay(hour),
            isWeekend: now.getDay() === 0 || now.getDay() === 6,
            isHoliday: this.isHoliday(now),
            weather: this.getWeatherContext()
        };
    }

    /**
     * Get time of day category
     */
    getTimeOfDay(hour) {
        if (hour >= 5 && hour < 12) return 'morning';
        if (hour >= 12 && hour < 17) return 'afternoon';
        if (hour >= 17 && hour < 22) return 'evening';
        return 'night';
    }

    /**
     * Product category detection methods
     */
    isAntiAgingProduct(product) {
        const antiAgingKeywords = [
            'anti-age', 'antiarrugas', 'anti-aging', 'rejuvenecedor', 'reafirmante',
            'lifting', 'retinol', 'collagen', 'peptides', 'wrinkle', 'firming'
        ];
        const text = (product.name + ' ' + product.category + ' ' + product.description).toLowerCase();
        return antiAgingKeywords.some(keyword => text.includes(keyword));
    }

    isLuxuryProduct(product) {
        const luxuryKeywords = [
            'luxury', 'premium', 'professional', 'exclusive', 'limited edition',
            'haute couture', 'prestige', 'elite', 'platinum', 'gold'
        ];
        const text = (product.name + ' ' + product.category + ' ' + product.description).toLowerCase();
        const price = parseFloat(product.price?.replace(/[^0-9.]/g, '')) || 0;
        
        return luxuryKeywords.some(keyword => text.includes(keyword)) || price > 200;
    }

    isMakeupProduct(product) {
        const makeupKeywords = [
            'makeup', 'maquillaje', 'foundation', 'concealer', 'lipstick', 'eyeshadow',
            'mascara', 'blush', 'bronzer', 'highlighter', 'primer', 'powder'
        ];
        const text = (product.name + ' ' + product.category + ' ' + product.description).toLowerCase();
        return makeupKeywords.some(keyword => text.includes(keyword));
    }

    isHairCareProduct(product) {
        const hairKeywords = [
            'hair', 'cabello', 'shampoo', 'conditioner', 'acondicionador', 'mask',
            'treatment', 'serum', 'oil', 'styling', 'curl', 'straight'
        ];
        const text = (product.name + ' ' + product.category + ' ' + product.description).toLowerCase();
        return hairKeywords.some(keyword => text.includes(keyword));
    }

    hasSkincareProducts(products) {
        return products.some(product => this.isSkincareProduct(product));
    }

    /**
     * Estimate age category from user context
     */
    estimateAgeCategory(userContext) {
        // This would use various signals to estimate age category
        // For now, return a default based on product preferences
        if (userContext.behaviorPattern?.interest_indicators?.includes('anti_aging')) {
            return 'mature';
        }
        return 'general';
    }

    /**
     * Calculate total value of products
     */
    calculateProductsValue(products) {
        return products.reduce((total, product) => {
            const price = parseFloat(product.price?.replace(/[^0-9.]/g, '')) || 0;
            return total + price;
        }, 0);
    }

    /**
     * Detect occasion context
     */
    detectOccasionContext() {
        const timeContext = this.getTimeContext();
        
        if (timeContext.isWeekend) return 'weekend';
        if (timeContext.timeOfDay === 'evening') return 'evening_out';
        if (timeContext.isHoliday) return 'special_occasion';
        
        return 'everyday';
    }

    /**
     * Detect upcoming holidays
     */
    detectUpcomingHolidays(timeContext) {
        const holidays = [];
        const now = new Date();
        const currentMonth = now.getMonth() + 1;
        const currentDay = now.getDate();
        
        // Define major holidays and beauty-relevant occasions
        const holidayCalendar = [
            { name: 'Valentine\'s Day', month: 2, day: 14, type: 'romantic' },
            { name: 'Mother\'s Day', month: 5, day: 10, type: 'gift_giving' }, // Approximate
            { name: 'Christmas', month: 12, day: 25, type: 'gift_giving' },
            { name: 'New Year', month: 1, day: 1, type: 'celebration' }
        ];
        
        holidayCalendar.forEach(holiday => {
            const holidayDate = new Date(now.getFullYear(), holiday.month - 1, holiday.day);
            const daysUntil = Math.ceil((holidayDate - now) / (1000 * 60 * 60 * 24));
            
            if (daysUntil > 0 && daysUntil <= 30) {
                holidays.push({
                    ...holiday,
                    daysUntil: daysUntil
                });
            }
        });
        
        return holidays;
    }

    /**
     * Check if it's gift-giving season
     */
    isGiftGivingSeason(timeContext) {
        const giftMonths = [2, 5, 11, 12]; // Feb, May, Nov, Dec
        return giftMonths.includes(timeContext.month);
    }

    /**
     * Get current gift occasion
     */
    getCurrentGiftOccasion(timeContext) {
        const month = timeContext.month;
        
        if (month === 2) return 'Valentine\'s Day';
        if (month === 5) return 'Mother\'s Day';
        if (month === 11 || month === 12) return 'Holiday Season';
        
        return 'Special Occasion';
    }

    /**
     * Estimate weather from season
     */
    estimateWeatherFromSeason(season) {
        const weatherMap = {
            'winter': 'cold_dry',
            'spring': 'mild_humid',
            'summer': 'hot_humid',
            'autumn': 'cool_dry'
        };
        
        return weatherMap[season] || 'moderate';
    }

    /**
     * Get available VIP services
     */
    getAvailableVIPServices() {
        return [
            'personal_consultation',
            'free_shipping',
            'priority_support',
            'exclusive_products',
            'birthday_gifts'
        ];
    }

    /**
     * Get brand values
     */
    getBrandValues() {
        return [
            'quality',
            'innovation',
            'sustainability',
            'customer_care',
            'expertise'
        ];
    }

    /**
     * Get relevant content type for products
     */
    getRelevantContentType(products) {
        if (products.some(p => this.isSkincareProduct(p))) return 'skincare_education';
        if (products.some(p => this.isMakeupProduct(p))) return 'makeup_tutorials';
        if (products.some(p => this.isHairCareProduct(p))) return 'hair_care_tips';
        
        return 'general_beauty_tips';
    }

    /**
     * Find complementary products
     */
    findComplementaryProducts(products) {
        // This would integrate with a recommendation engine
        // For now, return mock complementary products
        const complementary = [];
        
        products.forEach(product => {
            if (this.isSkincareProduct(product)) {
                complementary.push({
                    name: 'Complementary Serum',
                    reason: 'Works well with your current selection'
                });
            }
        });
        
        return complementary;
    }

    /**
     * Find upgrade options
     */
    findUpgradeOptions(products, userContext) {
        const upgrades = [];
        
        products.forEach(product => {
            const price = parseFloat(product.price?.replace(/[^0-9.]/g, '')) || 0;
            
            if (price < 100 && userContext.customerTier !== 'new') {
                upgrades.push({
                    name: 'Premium Version of ' + product.name,
                    benefits: ['Higher concentration', 'Better results', 'Luxury packaging']
                });
            }
        });
        
        return upgrades;
    }

    /**
     * Get upgrade benefits
     */
    getUpgradeBenefits(upgradeOptions) {
        return upgradeOptions.reduce((benefits, option) => {
            return benefits.concat(option.benefits || []);
        }, []);
    }

    /**
     * Find cross-category products
     */
    findCrossCategoryProducts(products, userContext) {
        const crossCategory = [];
        
        if (products.some(p => this.isSkincareProduct(p)) && 
            !products.some(p => this.isMakeupProduct(p))) {
            crossCategory.push({
                name: 'Complementary Makeup',
                category: 'makeup',
                reason: 'Complete your beauty routine'
            });
        }
        
        return crossCategory;
    }

    /**
     * Get cross-sell reason
     */
    getCrossSellReason(currentProducts, crossProducts) {
        return 'Complete your beauty routine with complementary products';
    }

    /**
     * Get recommendations from behavior
     */
    getRecommendationsFromBehavior(behaviorPattern, currentProducts) {
        const recommendations = [];
        
        if (behaviorPattern.engagement_level === 'high') {
            recommendations.push({
                name: 'Advanced Product Line',
                reason: 'Based on your high engagement'
            });
        }
        
        return recommendations;
    }

    /**
     * Calculate recommendation confidence
     */
    calculateRecommendationConfidence(behaviorPattern) {
        let confidence = 0.5; // Base confidence
        
        if (behaviorPattern.engagement_level === 'high') confidence += 0.2;
        if (behaviorPattern.purchase_intent === 'high') confidence += 0.3;
        
        return Math.min(confidence, 1.0);
    }

    /**
     * Get purchase history
     */
    getPurchaseHistory() {
        // This would integrate with the user's purchase history
        return [];
    }

    /**
     * Get loyalty points
     */
    getLoyaltyPoints() {
        // This would integrate with the loyalty system
        return 0;
    }

    /**
     * Get user preferences
     */
    getUserPreferences() {
        // This would integrate with user preference system
        return {};
    }

    /**
     * Get weather context
     */
    getWeatherContext() {
        // This would integrate with weather API
        return null;
    }

    /**
     * Check if date is holiday
     */
    isHoliday(date) {
        // Simple holiday detection
        const month = date.getMonth() + 1;
        const day = date.getDate();
        
        // Major holidays
        if (month === 12 && day === 25) return true; // Christmas
        if (month === 1 && day === 1) return true; // New Year
        if (month === 2 && day === 14) return true; // Valentine's
        
        return false;
    }

    // Discount and promotion system helper methods
    
    /**
     * Generate discount code
     */
    generateDiscountCode(prefix, percentage) {
        const timestamp = Date.now().toString().slice(-6);
        const randomSuffix = Math.random().toString(36).substr(2, 3).toUpperCase();
        return `${prefix}${percentage}_${timestamp}${randomSuffix}`;
    }

    /**
     * Check if welcome discount has been shown
     */
    hasShownWelcomeDiscount() {
        return localStorage.getItem('chatbot_welcome_discount_shown') === 'true';
    }

    /**
     * Mark welcome discount as shown
     */
    markWelcomeDiscountShown() {
        localStorage.setItem('chatbot_welcome_discount_shown', 'true');
    }

    /**
     * Check if weekend special has been shown
     */
    hasShownWeekendSpecial() {
        const lastShown = localStorage.getItem('chatbot_weekend_special_shown');
        if (!lastShown) return false;
        
        const lastShownDate = new Date(lastShown);
        const now = new Date();
        const daysDiff = Math.floor((now - lastShownDate) / (1000 * 60 * 60 * 24));
        
        return daysDiff < 7; // Show once per week
    }

    /**
     * Mark weekend special as shown
     */
    markWeekendSpecialShown() {
        localStorage.setItem('chatbot_weekend_special_shown', new Date().toISOString());
    }

    /**
     * Get VIP perks
     */
    getVIPPerks() {
        return [
            'free_shipping',
            'priority_support',
            'exclusive_products',
            'early_access',
            'birthday_gifts',
            'personal_consultation'
        ];
    }

    /**
     * Get VIP exclusive products
     */
    getVIPExclusiveProducts() {
        // This would integrate with product catalog
        return [
            {
                name: 'VIP Exclusive Serum',
                price: '$150',
                exclusive: true
            },
            {
                name: 'Limited Edition Set',
                price: '$200',
                exclusive: true
            }
        ];
    }

    /**
     * Check if it's user's birthday month
     */
    isUserBirthdayMonth(userContext) {
        // This would integrate with user profile data
        // For now, return false as we don't have birthday data
        return false;
    }

    /**
     * Get financing options
     */
    getFinancingOptions() {
        return [
            {
                name: '3 Monthly Payments',
                interest_rate: 0,
                minimum_amount: 150
            },
            {
                name: '6 Monthly Payments',
                interest_rate: 2.9,
                minimum_amount: 300
            }
        ];
    }

    /**
     * Calculate volume discount based on quantity
     */
    calculateVolumeDiscount(quantity) {
        if (quantity >= 5) return 20;
        if (quantity >= 4) return 15;
        if (quantity >= 3) return 10;
        return 0;
    }

    /**
     * Calculate savings amount
     */
    calculateSavingsAmount(products, discountPercentage) {
        const totalValue = this.calculateProductsValue(products);
        return (totalValue * discountPercentage) / 100;
    }

    /**
     * Detect bundle opportunities
     */
    detectBundleOpportunities(products) {
        const bundles = [];
        
        // Skincare routine bundle
        const skincareProducts = products.filter(p => this.isSkincareProduct(p));
        if (skincareProducts.length >= 2) {
            bundles.push({
                name: 'Complete Skincare Routine',
                products: skincareProducts,
                discount_percentage: 15,
                savings: this.calculateSavingsAmount(skincareProducts, 15)
            });
        }
        
        // Makeup look bundle
        const makeupProducts = products.filter(p => this.isMakeupProduct(p));
        if (makeupProducts.length >= 2) {
            bundles.push({
                name: 'Complete Makeup Look',
                products: makeupProducts,
                discount_percentage: 12,
                savings: this.calculateSavingsAmount(makeupProducts, 12)
            });
        }
        
        return bundles;
    }

    /**
     * Calculate bundle savings
     */
    calculateBundleSavings(bundleOpportunities) {
        return bundleOpportunities.reduce((total, bundle) => {
            return total + bundle.savings;
        }, 0);
    }

    /**
     * Check if flash sale is active
     */
    isFlashSaleActive() {
        // This would integrate with promotion management system
        // For demo purposes, randomly return true 10% of the time
        return Math.random() < 0.1;
    }

    /**
     * Get flash sale time remaining
     */
    getFlashSaleTimeRemaining() {
        // Mock flash sale ending in 2 hours
        return {
            hours: 2,
            minutes: Math.floor(Math.random() * 60),
            total_minutes: 120 + Math.floor(Math.random() * 60)
        };
    }

    /**
     * Enhanced trigger display for discount triggers
     */
    displayDiscountTrigger(triggerData) {
        // Create enhanced discount trigger element
        const element = this.createDiscountTriggerElement(triggerData);
        
        // Show with special discount animation
        this.showDiscountTriggerWithAnimation(element, triggerData);
        
        // Track discount trigger display
        this.analytics.trackDiscountTriggerDisplay(triggerData);
    }

    /**
     * Create discount trigger element
     */
    createDiscountTriggerElement(triggerData) {
        const element = document.createElement('div');
        element.className = 'chatbot-proactive-trigger trigger-discount';
        element.setAttribute('data-trigger-id', triggerData.trigger_id);
        element.setAttribute('data-trigger-type', triggerData.trigger_type);
        
        const discountPercentage = triggerData.context.discount_percentage || 0;
        const discountCode = triggerData.context.discount_code || '';
        const urgencyLevel = triggerData.context.urgency_level || 'low';
        
        // Add urgency class
        if (urgencyLevel === 'high' || urgencyLevel === 'critical') {
            element.classList.add('trigger-urgency-high');
        } else if (urgencyLevel === 'medium') {
            element.classList.add('trigger-urgency-medium');
        }
        
        element.innerHTML = `
            <div class="trigger-content">
                <div class="trigger-header">
                    <div class="trigger-icon icon-discount">%</div>
                    <div class="trigger-title">¡Oferta Especial!</div>
                </div>
                <div class="trigger-message has-discount">
                    ${triggerData.message}
                    ${discountCode ? `<div class="trigger-discount-code">Código: <strong>${discountCode}</strong></div>` : ''}
                </div>
                <div class="trigger-actions">
                    <button class="trigger-btn trigger-btn-success" data-action="accept">
                        Aplicar Descuento
                    </button>
                    <button class="trigger-btn trigger-btn-secondary" data-action="dismiss">
                        Más Tarde
                    </button>
                </div>
            </div>
            <button class="trigger-close" data-action="close">&times;</button>
        `;
        
        // Add event listeners
        this.addDiscountTriggerEventListeners(element, triggerData);
        
        return element;
    }

    /**
     * Add event listeners for discount triggers
     */
    addDiscountTriggerEventListeners(element, triggerData) {
        element.addEventListener('click', (e) => {
            const action = e.target.getAttribute('data-action');
            
            if (action === 'accept') {
                this.applyDiscountCode(triggerData.context.discount_code);
                this.analytics.trackDiscountTriggerResponse(triggerData, 'accepted');
            } else if (action === 'dismiss') {
                this.analytics.trackDiscountTriggerResponse(triggerData, 'dismissed');
            } else if (action === 'close') {
                this.analytics.trackDiscountTriggerResponse(triggerData, 'closed');
            }
            
            if (action) {
                this.removeTriggerElement(element);
            }
        });
    }

    /**
     * Apply discount code
     */
    applyDiscountCode(discountCode) {
        if (!discountCode) return;
        
        // Try to find discount input field and apply code
        const discountInputs = document.querySelectorAll(
            'input[name*="discount"], input[name*="coupon"], input[name*="promo"], .discount-code-input'
        );
        
        if (discountInputs.length > 0) {
            const input = discountInputs[0];
            input.value = discountCode;
            input.focus();
            
            // Try to trigger apply button
            const applyButton = input.parentElement.querySelector('button, .apply-btn, .btn-apply');
            if (applyButton) {
                applyButton.click();
            }
            
            // Show success notification
            this.showDiscountAppliedNotification(discountCode);
        } else {
            // Copy to clipboard as fallback
            this.copyDiscountCodeToClipboard(discountCode);
        }
    }

    /**
     * Show discount applied notification
     */
    showDiscountAppliedNotification(discountCode) {
        const notification = document.createElement('div');
        notification.className = 'trigger-notification';
        notification.textContent = `¡Código ${discountCode} aplicado!`;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    }

    /**
     * Copy discount code to clipboard
     */
    copyDiscountCodeToClipboard(discountCode) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(discountCode).then(() => {
                this.showDiscountCopiedNotification(discountCode);
            });
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = discountCode;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            this.showDiscountCopiedNotification(discountCode);
        }
    }

    /**
     * Show discount copied notification
     */
    showDiscountCopiedNotification(discountCode) {
        const notification = document.createElement('div');
        notification.className = 'trigger-notification';
        notification.textContent = `Código ${discountCode} copiado al portapapeles`;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    }

    /**
     * Show discount trigger with special animation
     */
    showDiscountTriggerWithAnimation(element, triggerData) {
        document.body.appendChild(element);
        
        // Add special discount animation
        element.classList.add('trigger-animation-bounce');
        
        // Trigger show animation
        requestAnimationFrame(() => {
            element.classList.add('trigger-show');
        });
        
        // Auto-hide after delay if configured
        const displayConfig = triggerData.display_config || {};
        if (displayConfig.auto_hide_delay) {
            setTimeout(() => {
                this.removeTriggerElement(element);
            }, displayConfig.auto_hide_delay * 1000);
        }
    }

    log(...args) {
        if (this.config.debug) {
            console.log('[ProactiveTriggerEngine]', ...args);
        }
    }
}

// Export to global scope
window.ProactiveTriggerEngine = ProactiveTriggerEngine;