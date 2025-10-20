/**
 * User Session Tracker
 * Tracks user behavior and session data for trigger evaluation
 */
class UserSessionTracker {
    constructor() {
        this.sessionId = this.generateSessionId();
        this.userId = this.getUserId();
        this.sessionStartTime = Date.now();
        this.pageStartTime = Date.now();
        
        this.sessionData = {
            startTime: this.sessionStartTime,
            pageViews: [],
            interactions: [],
            cartActions: [],
            searchQueries: [],
            triggersActivated: new Set(),
            isNewVisitor: this.checkIfNewVisitor(),
            customerTier: this.getCustomerTier(),
            deviceType: this.getDeviceType(),
            referrer: document.referrer,
            userAgent: navigator.userAgent
        };
        
        this.isTracking = false;
    }

    /**
     * Start tracking user behavior
     */
    startTracking() {
        if (this.isTracking) return;
        
        this.isTracking = true;
        this.trackPageView();
        this.setupEventListeners();
        this.loadStoredData();
        
        // Save session data periodically
        setInterval(() => {
            this.saveSessionData();
        }, 30000); // Every 30 seconds
    }

    /**
     * Setup event listeners for user interactions
     */
    setupEventListeners() {
        // Track clicks
        document.addEventListener('click', (e) => {
            this.trackInteraction('click', {
                element: e.target.tagName,
                className: e.target.className,
                id: e.target.id,
                text: e.target.textContent?.substring(0, 100),
                timestamp: Date.now()
            });
        }, { passive: true });

        // Track form submissions
        document.addEventListener('submit', (e) => {
            this.trackInteraction('form_submit', {
                formId: e.target.id,
                formClass: e.target.className,
                action: e.target.action,
                timestamp: Date.now()
            });
        });

        // Track scroll behavior
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                this.trackInteraction('scroll', {
                    scrollY: window.scrollY,
                    scrollPercent: this.getScrollPercent(),
                    timestamp: Date.now()
                });
            }, 1000);
        }, { passive: true });

        // Track page visibility changes
        document.addEventListener('visibilitychange', () => {
            this.trackInteraction('visibility_change', {
                hidden: document.hidden,
                timestamp: Date.now()
            });
        });

        // Track beforeunload
        window.addEventListener('beforeunload', () => {
            this.saveSessionData();
        });

        // Track hash changes (for SPAs)
        window.addEventListener('hashchange', () => {
            this.trackPageView();
        });

        // Track popstate (for SPAs)
        window.addEventListener('popstate', () => {
            this.trackPageView();
        });
    }

    /**
     * Track page view
     */
    trackPageView(url = window.location.href, products = []) {
        const pageView = {
            url: url,
            title: document.title,
            timestamp: Date.now(),
            timeSpent: 0,
            products: products,
            scrollDepth: 0,
            referrer: document.referrer
        };

        // Update time spent on previous page
        if (this.sessionData.pageViews.length > 0) {
            const lastPage = this.sessionData.pageViews[this.sessionData.pageViews.length - 1];
            lastPage.timeSpent = Date.now() - lastPage.timestamp;
        }

        this.sessionData.pageViews.push(pageView);
        this.pageStartTime = Date.now();
        
        this.saveSessionData();
    }

    /**
     * Track user interaction
     */
    trackInteraction(type, data) {
        this.sessionData.interactions.push({
            type: type,
            data: data,
            timestamp: Date.now()
        });

        // Keep only last 100 interactions to prevent memory issues
        if (this.sessionData.interactions.length > 100) {
            this.sessionData.interactions = this.sessionData.interactions.slice(-100);
        }
    }

    /**
     * Track cart action
     */
    trackCartAction(action, productId, quantity = 1, price = 0) {
        this.sessionData.cartActions.push({
            action: action, // 'add', 'remove', 'update', 'view'
            productId: productId,
            quantity: quantity,
            price: price,
            timestamp: Date.now()
        });
    }

    /**
     * Track search query
     */
    trackSearchQuery(query, resultsCount = 0) {
        this.sessionData.searchQueries.push({
            query: query,
            resultsCount: resultsCount,
            timestamp: Date.now()
        });
    }

    /**
     * Mark trigger as activated
     */
    markTriggered(triggerType) {
        this.sessionData.triggersActivated.add(triggerType);
        this.saveSessionData();
    }

    /**
     * Check if trigger has been activated
     */
    hasTriggered(triggerType) {
        return this.sessionData.triggersActivated.has(triggerType);
    }

    /**
     * Check if user has interacted
     */
    hasInteracted() {
        return this.sessionData.interactions.length > 0;
    }

    /**
     * Get current page time
     */
    getCurrentPageTime() {
        return Date.now() - this.pageStartTime;
    }

    /**
     * Get total session time
     */
    getSessionTime() {
        return Date.now() - this.sessionStartTime;
    }

    /**
     * Get page visit count for URL
     */
    getPageVisitCount(url) {
        return this.sessionData.pageViews.filter(pv => pv.url === url).length;
    }

    /**
     * Get session ID
     */
    getSessionId() {
        return this.sessionId;
    }

    /**
     * Get user ID
     */
    getUserId() {
        // Try to get user ID from various sources
        const userMeta = document.querySelector('meta[name="user-id"]');
        if (userMeta) {
            return userMeta.getAttribute('content');
        }

        // Check for common user data in window object
        if (window.user && window.user.id) {
            return window.user.id;
        }

        if (window.userData && window.userData.id) {
            return window.userData.id;
        }

        return null;
    }

    /**
     * Check if user is new visitor
     */
    checkIfNewVisitor() {
        const hasVisited = localStorage.getItem('chatbot_visited');
        if (!hasVisited) {
            localStorage.setItem('chatbot_visited', 'true');
            return true;
        }
        return false;
    }

    /**
     * Get if user is new visitor
     */
    isNewVisitor() {
        return this.sessionData.isNewVisitor;
    }

    /**
     * Get customer tier
     */
    getCustomerTier() {
        // Try to get customer tier from various sources
        const tierMeta = document.querySelector('meta[name="customer-tier"]');
        if (tierMeta) {
            return tierMeta.getAttribute('content');
        }

        if (window.user && window.user.tier) {
            return window.user.tier;
        }

        return 'regular';
    }

    /**
     * Get device type
     */
    getDeviceType() {
        const width = window.innerWidth;
        if (width <= 768) return 'mobile';
        if (width <= 1024) return 'tablet';
        return 'desktop';
    }

    /**
     * Get scroll percentage
     */
    getScrollPercent() {
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        return Math.round((scrollTop + windowHeight) / documentHeight * 100);
    }

    /**
     * Generate unique session ID
     */
    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Save session data to localStorage
     */
    saveSessionData() {
        try {
            const dataToSave = {
                ...this.sessionData,
                triggersActivated: Array.from(this.sessionData.triggersActivated)
            };
            
            localStorage.setItem(`chatbot_session_${this.sessionId}`, JSON.stringify(dataToSave));
        } catch (error) {
            console.warn('Failed to save session data:', error);
        }
    }

    /**
     * Load stored session data
     */
    loadStoredData() {
        try {
            const stored = localStorage.getItem(`chatbot_session_${this.sessionId}`);
            if (stored) {
                const data = JSON.parse(stored);
                this.sessionData = {
                    ...this.sessionData,
                    ...data,
                    triggersActivated: new Set(data.triggersActivated || [])
                };
            }
        } catch (error) {
            console.warn('Failed to load session data:', error);
        }
    }

    /**
     * Get session summary for trigger evaluation
     */
    getSessionSummary() {
        return {
            session_id: this.sessionId,
            user_id: this.userId,
            session_time: this.getSessionTime(),
            page_time: this.getCurrentPageTime(),
            is_new_visitor: this.sessionData.isNewVisitor,
            customer_tier: this.sessionData.customerTier,
            device_type: this.sessionData.deviceType,
            page_views: this.sessionData.pageViews.length,
            interactions: this.sessionData.interactions.length,
            cart_actions: this.sessionData.cartActions.length,
            search_queries: this.sessionData.searchQueries.length,
            triggers_activated: Array.from(this.sessionData.triggersActivated),
            current_url: window.location.href,
            referrer: this.sessionData.referrer,
            scroll_percent: this.getScrollPercent()
        };
    }

    /**
     * Clean up old session data
     */
    cleanup() {
        const keys = Object.keys(localStorage);
        const sessionKeys = keys.filter(key => key.startsWith('chatbot_session_'));
        
        // Keep only last 5 sessions
        if (sessionKeys.length > 5) {
            sessionKeys.sort();
            const toRemove = sessionKeys.slice(0, -5);
            toRemove.forEach(key => localStorage.removeItem(key));
        }
    }

    /**
     * Get behavior patterns
     */
    getBehaviorPatterns() {
        const interactions = this.sessionData.interactions;
        const pageViews = this.sessionData.pageViews;
        
        return {
            // Engagement level
            engagement_level: this.calculateEngagementLevel(),
            
            // Browsing pattern
            browsing_pattern: this.analyzeBrowsingPattern(),
            
            // Interest indicators
            interest_indicators: this.getInterestIndicators(),
            
            // Purchase intent
            purchase_intent: this.calculatePurchaseIntent()
        };
    }

    /**
     * Calculate engagement level
     */
    calculateEngagementLevel() {
        const sessionTime = this.getSessionTime();
        const interactions = this.sessionData.interactions.length;
        const pageViews = this.sessionData.pageViews.length;
        
        let score = 0;
        
        // Time spent (max 30 points)
        score += Math.min(sessionTime / 1000 / 60 * 5, 30); // 5 points per minute, max 30
        
        // Interactions (max 40 points)
        score += Math.min(interactions * 2, 40); // 2 points per interaction, max 40
        
        // Page views (max 30 points)
        score += Math.min(pageViews * 5, 30); // 5 points per page view, max 30
        
        if (score >= 80) return 'high';
        if (score >= 50) return 'medium';
        return 'low';
    }

    /**
     * Analyze browsing pattern
     */
    analyzeBrowsingPattern() {
        const pageViews = this.sessionData.pageViews;
        
        if (pageViews.length <= 1) return 'single_page';
        if (pageViews.length <= 3) return 'focused';
        if (pageViews.length <= 6) return 'exploring';
        return 'browsing_extensively';
    }

    /**
     * Get interest indicators
     */
    getInterestIndicators() {
        const indicators = [];
        
        // Long time on product pages
        const productPages = this.sessionData.pageViews.filter(pv => 
            pv.url.includes('/product') && pv.timeSpent > 60000
        );
        if (productPages.length > 0) {
            indicators.push('product_interest');
        }
        
        // Multiple searches
        if (this.sessionData.searchQueries.length > 1) {
            indicators.push('search_active');
        }
        
        // Cart interactions
        if (this.sessionData.cartActions.length > 0) {
            indicators.push('cart_interest');
        }
        
        // High scroll depth
        const highScrollInteractions = this.sessionData.interactions.filter(i => 
            i.type === 'scroll' && i.data.scrollPercent > 75
        );
        if (highScrollInteractions.length > 0) {
            indicators.push('content_engaged');
        }
        
        return indicators;
    }

    /**
     * Calculate purchase intent
     */
    calculatePurchaseIntent() {
        let score = 0;
        
        // Cart actions
        const addToCartActions = this.sessionData.cartActions.filter(a => a.action === 'add');
        score += addToCartActions.length * 30;
        
        // Product page visits
        const productPageViews = this.sessionData.pageViews.filter(pv => 
            pv.url.includes('/product')
        );
        score += productPageViews.length * 10;
        
        // Time on product pages
        const productPageTime = productPageViews.reduce((total, pv) => total + pv.timeSpent, 0);
        score += Math.min(productPageTime / 1000 / 60 * 5, 25); // 5 points per minute, max 25
        
        // Checkout page visits
        const checkoutViews = this.sessionData.pageViews.filter(pv => 
            pv.url.includes('/checkout') || pv.url.includes('/cart')
        );
        score += checkoutViews.length * 20;
        
        if (score >= 80) return 'high';
        if (score >= 40) return 'medium';
        return 'low';
    }
}

// Export to global scope
window.UserSessionTracker = UserSessionTracker;