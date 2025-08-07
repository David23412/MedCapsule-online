// Log when script loads
// console.log('PAYMENT SCRIPT LOADING'); // Removed for production

// Global error handler
window.onerror = function(msg, url, lineNo, columnNo, error) {
// console.log('ERROR:', msg, 'at', lineNo, ':', columnNo); // Removed for production
// console.log('Error object:', error); // Removed for production
    return false;
};

// Function to update payment button state
function updatePaymentButton(courseId) {
    const button = document.querySelector(`#paymentBtn_${courseId}`);
    if (button) {
        button.innerHTML = '<i class="fas fa-clock"></i><span style="color: #c2410c;">Payment Pending</span>';
        button.classList.remove('btn-primary', 'show-payment-modal');
        button.classList.add('btn-warning');
        button.disabled = true;
        button.style.cursor = 'default';
    }
}

function showSuccessMessage() {
    // Store pending state and update button
    const courseId = document.getElementById('coursePaymentForm').getAttribute('data-course-id');
    if (courseId) {
        localStorage.setItem(`payment_pending_${courseId}`, 'true');
        updatePaymentButton(courseId);
    }

    // Create success message container
    const container = document.createElement('div');
    container.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.98);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999998;
        backdrop-filter: blur(5px);
    `;

    // Add success message content
    container.innerHTML = `
        <style>
            @keyframes modal-in {
                0% { transform: scale(0.8); opacity: 0; }
                100% { transform: scale(1); opacity: 1; }
            }
            @keyframes checkmark {
                0% { transform: scale(0.5) rotate(-45deg); opacity: 0; }
                40% { transform: scale(1.2) rotate(10deg); }
                70% { transform: scale(0.9) rotate(-5deg); }
                100% { transform: scale(1) rotate(0); opacity: 1; }
            }
            @keyframes text-fade {
                0% { opacity: 0; transform: translateY(15px); }
                100% { opacity: 1; transform: translateY(0); }
            }
            @keyframes shine {
                0% { background-position: -100% 0; }
                100% { background-position: 200% 0; }
            }
            @keyframes pulse {
                0% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.05); opacity: 0.8; }
                100% { transform: scale(1); opacity: 1; }
            }
        </style>
        <div style="text-align: center; padding: 50px; background: white; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); transform: scale(0.8); opacity: 0; animation: modal-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;">
            <div style="color: #28a745; font-size: 140px; animation: checkmark 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;">
                <i class="fas fa-check-circle" style="filter: drop-shadow(0 6px 8px rgba(40, 167, 69, 0.2));"></i>
            </div>
            <h3 style="margin-top: 30px; color: #28a745; font-size: 32px; font-weight: bold; animation: text-fade 0.6s ease forwards 0.6s; opacity: 0; text-shadow: 0 2px 4px rgba(40, 167, 69, 0.1);">
                Amazing! Payment Submitted! 🎉
            </h3>
            <p style="margin-top: 15px; color: #666; font-size: 18px; animation: text-fade 0.6s ease forwards 0.8s; opacity: 0;">
                Get ready to start learning! We'll verify it shortly.
            </p>
            <div style="margin-top: 25px; animation: text-fade 0.6s ease forwards 1s; opacity: 0;">
                <span style="
                    background: linear-gradient(90deg, #e8f5e9, #c8e6c9, #e8f5e9);
                    background-size: 200% 100%;
                    color: #28a745;
                    padding: 12px 24px;
                    border-radius: 50px;
                    font-size: 18px;
                    font-weight: 500;
                    display: inline-block;
                    animation: shine 2s linear infinite, pulse 2s ease-in-out infinite;
                ">
                    <i class="fas fa-bolt"></i> Unlocking your course...
                </span>
            </div>
        </div>
    `;

    document.body.appendChild(container);

    // Hide modal if it exists
    const modal = document.querySelector('.modal');
    if (modal) modal.style.display = 'none';

    // Redirect after animations complete
    setTimeout(() => {
        window.location.href = '/courses';
    }, 2500);
}

function showErrorMessage(message) {
    // Create error message container
    const container = document.createElement('div');
    container.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.98);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999998;
        backdrop-filter: blur(5px);
    `;

    // Add error message content
    container.innerHTML = `
        <style>
            @keyframes modal-in {
                0% { transform: scale(0.8); opacity: 0; }
                100% { transform: scale(1); opacity: 1; }
            }
            @keyframes text-fade {
                0% { opacity: 0; transform: translateY(15px); }
                100% { opacity: 1; transform: translateY(0); }
            }
        </style>
        <div style="text-align: center; padding: 50px; background: white; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); transform: scale(0.8); opacity: 0; animation: modal-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;">
            <div style="color: #dc3545; font-size: 140px;">
                <i class="fas fa-exclamation-triangle" style="filter: drop-shadow(0 6px 8px rgba(220, 53, 69, 0.2));"></i>
            </div>
            <h3 style="margin-top: 30px; color: #dc3545; font-size: 32px; font-weight: bold; animation: text-fade 0.6s ease forwards 0.6s; opacity: 0;">
                Payment Submission Failed
            </h3>
            <p style="margin-top: 15px; color: #666; font-size: 18px; animation: text-fade 0.6s ease forwards 0.8s; opacity: 0;">
                ${message}
            </p>
            <div style="margin-top: 25px; animation: text-fade 0.6s ease forwards 1s; opacity: 0;">
                <button onclick="location.reload()" style="
                    background: #dc3545;
                    color: white;
                    padding: 12px 24px;
                    border: none;
                    border-radius: 50px;
                    font-size: 18px;
                    font-weight: 500;
                    cursor: pointer;
                ">
                    <i class="fas fa-redo"></i> Try Again
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(container);
}

// Check for pending payments on page load
document.addEventListener('DOMContentLoaded', function() {
    // Update buttons for any pending payments
    Object.keys(localStorage).forEach(key => {
        if (key.startsWith('payment_pending_')) {
            const courseId = key.replace('payment_pending_', '');
            updatePaymentButton(courseId);
        }
    });

    const form = document.getElementById('coursePaymentForm');
    if (!form) {
// console.log('Form not found'); // Removed for production
        return;
    }
// console.log('Form found'); // Removed for production

    form.addEventListener('submit', function(e) {
        e.preventDefault();
// console.log('Form submitted'); // Removed for production

        // Disable submit button
        const submitBtn = this.querySelector('#submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
        }

        // Submit the form data
        const formData = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
// console.log('Payment submission response:', data); // Removed for production
            
            if (data.success) {
                // Show success message
                showSuccessMessage();
            } else {
                // Show error message
                showErrorMessage(data.message || 'An error occurred while submitting your payment. Please try again.');
                
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Payment';
                }
            }
        })
        .catch(error => {
// console.error('Payment submission error:', error); // Removed for production
            
            // Show error message
            showErrorMessage('An error occurred while submitting your payment. Please try again.');
            
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Payment';
            }
        });
    });
});

// Add debug button
const debugBtn = document.createElement('button');
debugBtn.textContent = 'Force Show Success';
debugBtn.style.cssText = 'position: fixed; bottom: 10px; right: 10px; z-index: 999999; padding: 10px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;';
debugBtn.onclick = showSuccessMessage;
document.body.appendChild(debugBtn);

// console.log('PAYMENT SCRIPT LOADED'); // Removed for production