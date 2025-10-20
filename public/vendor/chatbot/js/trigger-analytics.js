/**
 * Trigger Analytics
 * Tracks trigger performance and user interactions
 */
class TriggerAnalytics {
    constructor(engine) {
        this.engine = engine;
        this.pendingEvents = [];
        this.batchSize = 10;
        this.flushInterval = 30000; // 30 seconds
        
        this.startBatchProcessing();
    }

    /**
     * Track trigger display
     */
    trackTriggerDisplay(triggerData) {
        this.addEvent('trigger_display', {
            trigger_id: triggerData.trigger_id,
            trigger_type: triggerData.trigger_type,
            session_id: this.engine.userSession.getSessionId(),
            context: this.sanitizeContext(triggerData.context || {}),
            timestamp: new Date().toISOString()
        });
    }

    /**
     * Track trigger response
     */
    trackTriggerResponse(triggerData, responseType, conversion = false, conversionValue = null) {
        this.addEvent('trigger_response', {
            trigger_id: triggerData.trigger_id,
            trigger_type: triggerData.trigger_type,
            session_id: this.engine.userSession.getSessionId(),
            response_type: responseType,
            conversion_achieved: conversion,
            conversion_value: conversionValue,
            timestamp: new Date().toISOString()
        });

        // Send immediately for important events
        if (conversion || responseType === 'accepted') {
            this.flushEvents();
        }
    }

    /**
     * Track trigger interaction
     */
    trackTriggerInteraction(triggerData, interactionType, details = {}) {
        this.addEvent('trigger_interaction', {
            trigger_id: triggerData.trigger_id,
            trigger_type: triggerData.trigger_type,
            session_id: this.engine.userSession.getSessionId(),
            interaction_type: interactionType,
            details: details,
            timestamp: new Date().toISOString()
        });
    }

    /**
     * Track conversion
     */
    trackConversion(triggerData, conversionType, value = null, details = {}) {
        this.addEvent('trigger_conversion', {
            trigger_id: triggerData.trigger_id,
            trigger_type: triggerData.trigger_type,
            session_id: this.engine.userSession.getSessionId(),
            conversion_type: conversionType,
            conversion_value: value,
            details: details,
            timestamp: new Date().toISOString()
        });

        // Send immediately
        this.flushEvents();
    }

    /**
     * Add event to pending queue
     */
    addEvent(eventType, data) {
        this.pendingEvents.push({
            event_type: eventType,
            chatbot_id: this.engine.chatbotId,
            ...data
        });

        // Flush if batch is full
        if (this.pendingEvents.length >= this.batchSize) {
            this.flushEvents();
        }
    }

    /**
     * Start batch processing
     */
    startBatchProcessing() {
        setInterval(() => {
            if (this.pendingEvents.length > 0) {
                this.flushEvents();
            }
        }, this.flushInterval);

        // Flush on page unload
        window.addEventListener('beforeunload', () => {
            this.flushEvents();
        });

        // Flush on visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && this.pendingEvents.length > 0) {
                this.flushEvents();
            }
        });
    }

    /**
     * Flush pending events to server
     */
    async flushEvents() {
        if (this.pendingEvents.length === 0) {
            return;
        }

        const events = [...this.pendingEvents];
        this.pendingEvents = [];

        try {
            const response = await fetch('/api/v2/chatbot/triggers/analytics', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.engine.getCSRFToken()
                },
                body: JSON.stringify({
                    events: events
                })
            });

            if (!response.ok) {
                // Re-add events to queue if failed
                this.pendingEvents.unshift(...events);
                console.warn('Failed to send trigger analytics');
            }

        } catch (error) {
            // Re-add events to queue if failed
            this.pendingEvents.unshift(...events);
            console.error('Error sending trigger analytics:', error);
        }
    }

    /**
     * Sanitize context data
     */
    sanitizeContext(context) {
        const sanitized = {};
        const allowedKeys = [
            'page_type', 'page_url', 'session_time', 'is_new_visitor',
            'cart_value', 'product_count', 'category', 'search_term',
            'user_tier', 'device_type', 'referrer', 'scroll_depth'
        ];

        allowedKeys.forEach(key => {
            if (context[key] !== undefined) {
                let value = context[key];
                
                // Truncate long strings
                if (typeof value === 'string' && value.length > 255) {
                    value = value.substring(0, 255);
                }
                
                sanitized[key] = value;
            }
        });

        return sanitized;
    }

    /**
     * Get local analytics summary
     */
    getLocalAnalytics() {
        const sessionData = this.engine.userSession.sessionData;
        
        return {
            session_id: this.engine.userSession.getSessionId(),
            session_duration: this.engine.userSession.getSessionTime(),
            page_views: sessionData.pageViews.length,
            interactions: sessionData.interactions.length,
            triggers_activated: Array.from(sessionData.triggersActivated),
            cart_actions: sessionData.cartActions.length,
            search_queries: sessionData.searchQueries.length,
            engagement_level: this.engine.userSession.getBehaviorPatterns().engagement_level,
            purchase_intent: this.engine.userSession.getBehaviorPatterns().purchase_intent
        };
    }

    /**
     * Track page performance metrics
     */
    trackPageMetrics() {
        if ('performance' in window) {
            const navigation = performance.getEntriesByType('navigation')[0];
            const paint = performance.getEntriesByType('paint');
            
            const metrics = {
                page_load_time: navigation ? navigation.loadEventEnd - navigation.loadEventStart : null,
                dom_content_loaded: navigation ? navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart : null,
                first_paint: paint.find(p => p.name === 'first-paint')?.startTime || null,
                first_contentful_paint: paint.find(p => p.name === 'first-contentful-paint')?.startTime || null
            };

            this.addEvent('page_metrics', {
                session_id: this.engine.userSession.getSessionId(),
                page_url: window.location.href,
                metrics: metrics,
                timestamp: new Date().toISOString()
            });
        }
    }

    /**
     * Track user engagement score
     */
    trackEngagementScore() {
        const patterns = this.engine.userSession.getBehaviorPatterns();
        
        this.addEvent('engagement_score', {
            session_id: this.engine.userSession.getSessionId(),
            engagement_level: patterns.engagement_level,
            browsing_pattern: patterns.browsing_pattern,
            interest_indicators: patterns.interest_indicators,
            purchase_intent: patterns.purchase_intent,
            timestamp: new Date().toISOString()
        });
    }

    /**
     * Track A/B test data
     */
    trackABTest(testName, variant, triggerData = null) {
        this.addEvent('ab_test', {
            test_name: testName,
            variant: variant,
            trigger_id: triggerData?.trigger_id || null,
            session_id: this.engine.userSession.getSessionId(),
            timestamp: new Date().toISOString()
        });
    }

    /**
     * Track error events
     */
    trackError(error, context = {}) {
        this.addEvent('error', {
            error_message: error.message,
            error_stack: error.stack?.substring(0, 500), // Limit stack trace
            context: this.sanitizeContext(context),
            session_id: this.engine.userSession.getSessionId(),
            page_url: window.location.href,
            user_agent: navigator.userAgent,
            timestamp: new Date().toISOString()
        });
    }

    /**
     * Track custom event
     */
    trackCustomEvent(eventName, data = {}) {
        this.addEvent('custom_event', {
            event_name: eventName,
            data: this.sanitizeContext(data),
            session_id: this.engine.userSession.getSessionId(),
            timestamp: new Date().toISOString()
        });
    }

    /**
     * Get trigger effectiveness metrics
     */
    getTriggerEffectiveness(triggerType) {
        const sessionData = this.engine.userSession.sessionData;
        const triggerInteractions = sessionData.interactions.filter(i => 
            i.type === 'trigger_response' && i.data.trigger_type === triggerType
        );

        if (triggerInteractions.length === 0) {
            return null;
        }

        const responses = triggerInteractions.filter(i => i.data.response_type !== 'dismissed');
        const conversions = triggerInteractions.filter(i => i.data.conversion_achieved);

        return {
            trigger_type: triggerType,
            total_displays: triggerInteractions.length,
            total_responses: responses.length,
            total_conversions: conversions.length,
            response_rate: triggerInteractions.length > 0 ? (responses.length / triggerInteractions.length) * 100 : 0,
            conversion_rate: triggerInteractions.length > 0 ? (conversions.length / triggerInteractions.length) * 100 : 0
        };
    }

    /**
     * Generate analytics report
     */
    generateReport() {
        const sessionSummary = this.engine.userSession.getSessionSummary();
        const behaviorPatterns = this.engine.userSession.getBehaviorPatterns();
        const localAnalytics = this.getLocalAnalytics();

        return {
            session: sessionSummary,
            behavior: behaviorPatterns,
            analytics: localAnalytics,
            triggers: Array.from(this.engine.userSession.sessionData.triggersActivated).map(type => 
                this.getTriggerEffectiveness(type)
            ).filter(Boolean),
            timestamp: new Date().toISOString()
        };
    }

    /**
     * Export analytics data
     */
    exportData() {
        const report = this.generateReport();
        const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `chatbot-analytics-${this.engine.userSession.getSessionId()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        
        URL.revokeObjectURL(url);
    }

    /**
     * Debug analytics
     */
    debug() {
        console.group('Trigger Analytics Debug');
        console.log('Pending Events:', this.pendingEvents);
        console.log('Session Summary:', this.engine.userSession.getSessionSummary());
        console.log('Behavior Patterns:', this.engine.userSession.getBehaviorPatterns());
        console.log('Local Analytics:', this.getLocalAnalytics());
        console.groupEnd();
    }
}

// Export to global scope
window.TriggerAnalytics = TriggerAnalytics;