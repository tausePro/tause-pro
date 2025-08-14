// Frontend JavaScript for Wompi integration
// Add this to your payment page

class WompiPayment {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Handle plan selection and payment
        document.querySelectorAll('.wompi-pay-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const planId = button.dataset.planId;
                this.initiatePayment(planId);
            });
        });
    }

    async initiatePayment(planId) {
        try {
            // Show loading state
            this.showLoading();

            const response = await fetch('/payment/wompi/initiate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    plan_id: planId
                })
            });

            const data = await response.json();

            if (data.success) {
                // Redirect to Wompi payment page
                window.location.href = data.payment_url;
            } else {
                this.showError(data.message || 'Error al procesar el pago');
            }

        } catch (error) {
            console.error('Payment error:', error);
            this.showError('Error de conexión. Por favor intenta de nuevo.');
        } finally {
            this.hideLoading();
        }
    }

    async checkPaymentStatus(orderId) {
        try {
            const response = await fetch(`/payment/wompi/status?order_id=${orderId}`);
            const data = await response.json();
            
            return data;
        } catch (error) {
            console.error('Status check error:', error);
            return null;
        }
    }

    showLoading() {
        // Show loading spinner or disable buttons
        document.querySelectorAll('.wompi-pay-btn').forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        });
    }

    hideLoading() {
        // Hide loading spinner and enable buttons
        document.querySelectorAll('.wompi-pay-btn').forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = 'Pagar con Wompi';
        });
    }

    showError(message) {
        // Show error message to user
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i>
            ${message}
        `;
        
        const container = document.querySelector('.payment-container');
        container.insertBefore(errorDiv, container.firstChild);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }

    showSuccess(message) {
        // Show success message to user
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success';
        successDiv.innerHTML = `
            <i class="fas fa-check-circle"></i>
            ${message}
        `;
        
        const container = document.querySelector('.payment-container');
        container.insertBefore(successDiv, container.firstChild);
    }
}

// Initialize Wompi payment handler
document.addEventListener('DOMContentLoaded', function() {
    new WompiPayment();
});

// Handle payment callback page
if (window.location.pathname.includes('/payment/wompi/callback')) {
    // You can add additional handling here if needed
    console.log('Wompi payment callback received');
}