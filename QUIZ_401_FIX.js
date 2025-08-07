/**
 * 🔥 QUIZ 401 AUTHENTICATION FIX 🔥
 * Run this in your browser console to fix the 401 authentication error
 */

// console.log('🔥 QUIZ 401 AUTHENTICATION FIX STARTING...'); // Removed for production

// Step 1: Check current authentication status
// console.log('1. Checking authentication status...'); // Removed for production

fetch('/debug-quiz-403', {
    method: 'GET',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(response => {
// console.log('Debug endpoint status:', response.status); // Removed for production
    if (response.status === 401) {
// console.log('❌ User not authenticated - session expired'); // Removed for production
        alert('Your session has expired. Please log in again.');
        window.location.href = '/login';
        return;
    }
    return response.json();
})
.then(data => {
    if (data) {
// console.log('✅ Authentication check result:', data); // Removed for production
        
        if (!data.authenticated) {
// console.log('❌ User not authenticated according to debug endpoint'); // Removed for production
            alert('Your session has expired. Please log in again.');
            window.location.href = '/login';
            return;
        }
        
// console.log('✅ User is authenticated:', data.user_id); // Removed for production
// console.log('✅ Session ID:', data.session_id ? 'Present' : 'Missing'); // Removed for production
        
        // Step 2: If authenticated, the issue might be with the quiz form
// console.log('2. Checking quiz form and enhancing submission...'); // Removed for production
        
        const quizForm = document.getElementById('quiz-form');
        if (quizForm) {
// console.log('✅ Quiz form found'); // Removed for production
            
            // Add enhanced authentication headers to the form submission
            const originalSubmit = quizForm.onsubmit;
            
            // Override form submission with authentication checks
            quizForm.addEventListener('submit', function(event) {
                event.preventDefault();
// console.log('🚀 Enhanced quiz submission with auth check...'); // Removed for production
                
                // First, verify we're still authenticated
                fetch('/debug-quiz-403', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(authResponse => {
                    if (authResponse.status === 401) {
                        throw new Error('Authentication expired during submission');
                    }
                    return authResponse.json();
                })
                .then(authData => {
                    if (!authData.authenticated) {
                        throw new Error('User not authenticated');
                    }
                    
// console.log('✅ Authentication verified, proceeding with quiz submission'); // Removed for production
                    
                    // Proceed with the actual form submission
                    const formData = new FormData(this);
                    
                    // Add authentication-related data
                    formData.append('user_verification', authData.user_id);
                    formData.append('session_verification', authData.session_id || '');
                    
// console.log('📡 Submitting quiz with enhanced authentication...'); // Removed for production
                    
                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
// console.log('📡 Quiz response status:', response.status); // Removed for production
                        
                        if (response.status === 401) {
                            throw new Error('Authentication failed during quiz submission');
                        } else if (response.status === 403) {
                            throw new Error('Access forbidden - possible CSRF or permission issue');
                        } else if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        return response.json();
                    })
                    .then(data => {
// console.log('✅ Quiz submitted successfully:', data); // Removed for production
                        
                        if (data.score !== undefined) {
                            const percentage = Math.round((data.score / data.total) * 100);
                            alert(`🎉 Quiz completed!\n\nScore: ${data.score}/${data.total} (${percentage}%)\n\n${data.encouragement || 'Great job!'}`);
                        }
                        
                        // Trigger any existing success handlers
                        if (typeof showQuizResults === 'function') {
                            showQuizResults(data);
                        }
                    })
                    .catch(error => {
// console.error('❌ Quiz submission failed:', error); // Removed for production
                        
                        if (error.message.includes('Authentication')) {
                            alert('Your session has expired. Please log in again.');
                            window.location.href = '/login';
                        } else {
                            alert('Quiz submission failed: ' + error.message + '\n\nPlease try again or refresh the page.');
                        }
                    });
                })
                .catch(authError => {
// console.error('❌ Authentication check failed:', authError); // Removed for production
                    alert('Authentication check failed. Please log in again.');
                    window.location.href = '/login';
                });
            }, true); // Use capture to override existing handlers
            
// console.log('✅ Enhanced quiz submission handler installed'); // Removed for production
        } else {
// console.log('❌ Quiz form not found'); // Removed for production
        }
    }
})
.catch(error => {
// console.error('❌ Authentication check failed:', error); // Removed for production
    alert('Unable to verify authentication. Please refresh the page and try again.');
});

// Step 3: Add a manual authentication refresh button
// console.log('3. Adding authentication refresh button...'); // Removed for production

const refreshButton = document.createElement('button');
refreshButton.textContent = '🔄 Refresh Auth';
refreshButton.style.cssText = `
    position: fixed;
    top: 50px;
    right: 10px;
    z-index: 9999;
    background: #28a745;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
`;

refreshButton.onclick = function() {
// console.log('🔄 Manual authentication refresh triggered'); // Removed for production
    location.reload();
};

document.body.appendChild(refreshButton);

// console.log('🎯 QUIZ 401 FIX COMPLETE!'); // Removed for production
// console.log('========================'); // Removed for production
// console.log('✅ Authentication status checked'); // Removed for production
// console.log('✅ Enhanced quiz submission installed'); // Removed for production
// console.log('✅ Manual refresh button added'); // Removed for production
// console.log(''); // Removed for production
// console.log('🔧 If you still get 401 errors:'); // Removed for production
// console.log('1. Click the "Refresh Auth" button'); // Removed for production
// console.log('2. Log out and log back in'); // Removed for production
// console.log('3. Clear all browser data for this site'); // Removed for production
// console.log('4. Check if your session cookies are being sent'); // Removed for production