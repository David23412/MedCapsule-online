/**
 * 🔥 BROWSER QUIZ 403 FIX 🔥
 * Run this in your browser console to fix the quiz submission
 */

// console.log('🔥 BROWSER QUIZ 403 FIX STARTING...'); // Removed for production

// Step 1: Clear browser storage
// console.log('1. Clearing browser storage...'); // Removed for production
localStorage.clear();
sessionStorage.clear();
// console.log('✅ Storage cleared'); // Removed for production

// Step 2: Test the debug endpoint
// console.log('2. Testing debug endpoint...'); // Removed for production
fetch('/debug-quiz-403', {
    method: 'POST',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ test: true })
})
.then(response => response.json())
.then(data => {
// console.log('✅ Debug endpoint response:', data); // Removed for production
    
    if (!data.authenticated) {
// console.log('❌ Not authenticated - redirecting to login'); // Removed for production
        window.location.href = '/login';
        return;
    }
    
// console.log('✅ User authenticated:', data.user_id); // Removed for production
// console.log('✅ CSRF token available:', data.csrf_token ? 'YES' : 'NO'); // Removed for production
// console.log('✅ Session active:', data.session_id ? 'YES' : 'NO'); // Removed for production
})
.catch(error => {
// console.log('⚠️ Debug endpoint failed:', error); // Removed for production
});

// Step 3: Enhanced quiz form handler
// console.log('3. Installing enhanced quiz form handler...'); // Removed for production

const quizForm = document.getElementById('quiz-form');
if (quizForm) {
// console.log('✅ Quiz form found'); // Removed for production
    
    // Remove existing event listeners by cloning the form
    const newForm = quizForm.cloneNode(true);
    quizForm.parentNode.replaceChild(newForm, quizForm);
    
    // Add enhanced submit handler
    newForm.addEventListener('submit', function(event) {
        event.preventDefault();
// console.log('🚀 Enhanced quiz submission starting...'); // Removed for production
        
        const formData = new FormData(this);
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        
        // Ensure CSRF token is included in multiple ways
        if (csrfMeta && csrfMeta.content) {
            formData.set('_token', csrfMeta.content);
// console.log('✅ CSRF token added to form data'); // Removed for production
        } else {
// console.log('❌ No CSRF token found in meta tag'); // Removed for production
        }
        
        // Log form data for debugging
// console.log('📋 Form data:'); // Removed for production
        for (let [key, value] of formData.entries()) {
            if (key === '_token') {
// console.log(`${key}: ${value.substring(0, 10)}...`); // Removed for production
            } else {
// console.log(`${key}: ${value}`); // Removed for production
            }
        }
        
        // Enhanced fetch with multiple fallbacks
        const submitQuiz = async () => {
            const headers = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            
            // Add CSRF token to headers in multiple ways
            if (csrfMeta && csrfMeta.content) {
                headers['X-CSRF-TOKEN'] = csrfMeta.content;
                headers['X-XSRF-TOKEN'] = csrfMeta.content;
            }
            
            try {
// console.log('📡 Sending request to:', this.action); // Removed for production
// console.log('📡 Headers:', headers); // Removed for production
                
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: headers
                });
                
// console.log('📡 Response status:', response.status); // Removed for production
// console.log('📡 Response headers:', [...response.headers.entries()]); // Removed for production
                
                if (response.status === 403) {
// console.log('❌ 403 FORBIDDEN ERROR'); // Removed for production
                    
                    // Try to get more details from the response
                    const responseText = await response.text();
// console.log('📄 Response body:', responseText); // Removed for production
                    
                    if (responseText.includes('CSRF') || responseText.includes('token')) {
                        alert('CSRF token issue detected. Refreshing page...');
                        location.reload();
                        return;
                    }
                    
                    if (responseText.includes('Unauthenticated') || responseText.includes('login')) {
                        alert('Session expired. Redirecting to login...');
                        window.location.href = '/login';
                        return;
                    }
                    
                    // Try the debug endpoint to check authentication
                    try {
                        const debugResponse = await fetch('/debug-quiz-403', {
                            method: 'GET',
                            headers: { 'Accept': 'application/json' }
                        });
                        const debugData = await debugResponse.json();
                        
                        if (!debugData.authenticated) {
                            alert('Authentication lost. Please log in again.');
                            window.location.href = '/login';
                        } else {
                            alert('Unknown 403 error. Please refresh the page and try again.');
                            location.reload();
                        }
                    } catch (debugError) {
// console.log('Debug check failed:', debugError); // Removed for production
                        alert('Server connection issue. Please refresh and try again.');
                        location.reload();
                    }
                    
                    return;
                }
                
                if (response.status === 422) {
                    const errorData = await response.json();
// console.log('❌ Validation errors:', errorData); // Removed for production
                    alert('Validation error: ' + JSON.stringify(errorData.errors || errorData.message));
                    return;
                }
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
// console.log('✅ Quiz submitted successfully:', data); // Removed for production
                
                // Show success message
                if (data.score !== undefined) {
                    const percentage = Math.round((data.score / data.total) * 100);
                    alert(`🎉 Quiz completed successfully!\n\nScore: ${data.score}/${data.total} (${percentage}%)\n\n${data.encouragement || 'Great job!'}`);
                }
                
                // Trigger any existing success handlers
                if (typeof showQuizResults === 'function') {
                    showQuizResults(data);
                }
                
            } catch (error) {
// console.error('❌ Quiz submission failed:', error); // Removed for production
                alert('Quiz submission failed: ' + error.message + '\n\nPlease refresh the page and try again.');
            }
        };
        
        submitQuiz();
    });
    
// console.log('✅ Enhanced quiz form handler installed'); // Removed for production
} else {
// console.log('❌ Quiz form not found'); // Removed for production
}

// Step 4: Add a manual submit button for testing
// console.log('4. Adding manual submit button...'); // Removed for production

if (quizForm || document.getElementById('quiz-form')) {
    const testButton = document.createElement('button');
    testButton.textContent = '🔧 Test Submit (Debug)';
    testButton.style.cssText = `
        position: fixed;
        top: 10px;
        right: 10px;
        z-index: 9999;
        background: #ff6b6b;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
    `;
    
    testButton.onclick = function() {
// console.log('🔧 Manual test submit triggered'); // Removed for production
        const form = document.getElementById('quiz-form');
        if (form) {
            const event = new Event('submit', { bubbles: true, cancelable: true });
            form.dispatchEvent(event);
        } else {
// console.log('❌ No quiz form found for manual submit'); // Removed for production
        }
    };
    
    document.body.appendChild(testButton);
// console.log('✅ Manual submit button added (top-right corner)'); // Removed for production
}

// console.log('🎯 BROWSER FIX COMPLETE!'); // Removed for production
// console.log('========================'); // Removed for production
// console.log('✅ Browser storage cleared'); // Removed for production
// console.log('✅ Debug endpoint tested'); // Removed for production
// console.log('✅ Enhanced form handler installed'); // Removed for production
// console.log('✅ Manual submit button added'); // Removed for production
// console.log(''); // Removed for production
// console.log('🔧 Now try submitting your quiz!'); // Removed for production
// console.log('📋 If it still fails, check the console for detailed error messages.'); // Removed for production