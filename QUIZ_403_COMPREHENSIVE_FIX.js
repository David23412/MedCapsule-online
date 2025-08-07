/**
 * 🔥 QUIZ 403 ERROR COMPREHENSIVE FIX 🔥
 * Run this in your browser console to fix the 403 error
 */

// console.log('🔥 QUIZ 403 ERROR FIX STARTING...'); // Removed for production

// Fix 1: Clear all browser storage
// console.log('1. Clearing browser storage...'); // Removed for production
localStorage.clear();
sessionStorage.clear();
// console.log('✅ Browser storage cleared'); // Removed for production

// Fix 2: Check if user is authenticated
// console.log('2. Checking authentication...'); // Removed for production
fetch('/api/active-users', {
    method: 'GET',
    headers: {
        'Accept': 'application/json'
    }
})
.then(response => {
    if (response.ok) {
// console.log('✅ Server is responding'); // Removed for production
    } else {
// console.log('❌ Server response issue:', response.status); // Removed for production
    }
})
.catch(error => {
// console.log('❌ Network error:', error); // Removed for production
});

// Fix 3: Refresh CSRF token
// console.log('3. Refreshing CSRF token...'); // Removed for production
const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (csrfMeta) {
// console.log('✅ CSRF token found:', csrfMeta.content.substring(0, 10) + '...'); // Removed for production
} else {
// console.log('❌ CSRF token not found - this is likely the issue!'); // Removed for production
// console.log('🔧 Creating CSRF token meta tag...'); // Removed for production
    
    // Try to get a new CSRF token
    fetch('/', {
        method: 'GET',
        headers: {
            'Accept': 'text/html'
        }
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newCsrfMeta = doc.querySelector('meta[name="csrf-token"]');
        
        if (newCsrfMeta) {
            // Update or create CSRF meta tag
            let existingMeta = document.querySelector('meta[name="csrf-token"]');
            if (existingMeta) {
                existingMeta.content = newCsrfMeta.content;
            } else {
                const meta = document.createElement('meta');
                meta.name = 'csrf-token';
                meta.content = newCsrfMeta.content;
                document.head.appendChild(meta);
            }
// console.log('✅ CSRF token refreshed'); // Removed for production
        }
    })
    .catch(error => {
// console.log('❌ Could not refresh CSRF token:', error); // Removed for production
    });
}

// Fix 4: Check quiz form
// console.log('4. Checking quiz form...'); // Removed for production
const quizForm = document.getElementById('quiz-form');
if (quizForm) {
// console.log('✅ Quiz form found'); // Removed for production
// console.log('Form action:', quizForm.action); // Removed for production
    
    // Check for CSRF token in form
    const csrfInput = quizForm.querySelector('input[name="_token"]');
    if (csrfInput) {
// console.log('✅ CSRF input found in form'); // Removed for production
    } else {
// console.log('❌ CSRF input missing from form'); // Removed for production
// console.log('🔧 Adding CSRF input to form...'); // Removed for production
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_token';
            input.value = csrfToken.content;
            quizForm.appendChild(input);
// console.log('✅ CSRF input added to form'); // Removed for production
        }
    }
} else {
// console.log('❌ Quiz form not found'); // Removed for production
}

// Fix 5: Test authentication endpoint
// console.log('5. Testing authentication...'); // Removed for production
fetch('/profile/progress', {
    method: 'GET',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(response => {
    if (response.status === 401 || response.status === 403) {
// console.log('❌ Authentication failed - you need to log in again'); // Removed for production
// console.log('🔧 Redirecting to login...'); // Removed for production
        window.location.href = '/login';
    } else if (response.ok) {
// console.log('✅ Authentication working'); // Removed for production
    } else {
// console.log('⚠️ Unexpected response:', response.status); // Removed for production
    }
})
.catch(error => {
// console.log('❌ Auth test failed:', error); // Removed for production
});

// Fix 6: Enhanced quiz submission handler
// console.log('6. Setting up enhanced quiz submission...'); // Removed for production

// Override the quiz form submission
if (quizForm) {
    // Remove existing event listeners
    const newForm = quizForm.cloneNode(true);
    quizForm.parentNode.replaceChild(newForm, quizForm);
    
    newForm.addEventListener('submit', function(event) {
        event.preventDefault();
// console.log('🚀 Enhanced quiz submission starting...'); // Removed for production
        
        const formData = new FormData(this);
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        
        // Ensure CSRF token is included
        if (csrfToken) {
            formData.set('_token', csrfToken.content);
        }
        
        // Add debugging info
// console.log('Form data entries:'); // Removed for production
        for (let [key, value] of formData.entries()) {
// console.log(`${key}: ${value}`); // Removed for production
        }
        
        // Enhanced fetch with better error handling
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
// console.log('Response status:', response.status); // Removed for production
// console.log('Response headers:', [...response.headers.entries()]); // Removed for production
            
            if (response.status === 403) {
// console.log('❌ 403 FORBIDDEN - Authentication or CSRF issue'); // Removed for production
                
                // Try to get more details
                return response.text().then(text => {
// console.log('Response body:', text); // Removed for production
                    
                    if (text.includes('CSRF')) {
                        alert('CSRF token expired. Please refresh the page and try again.');
                        location.reload();
                    } else if (text.includes('Unauthenticated') || text.includes('login')) {
                        alert('Your session has expired. Please log in again.');
                        window.location.href = '/login';
                    } else {
                        alert('Access denied. Please refresh the page and try again.');
                        location.reload();
                    }
                });
            } else if (response.status === 422) {
                return response.json().then(data => {
// console.log('Validation errors:', data); // Removed for production
                    alert('Validation error: ' + JSON.stringify(data.errors || data.message));
                });
            } else if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.json();
        })
        .then(data => {
            if (data) {
// console.log('✅ Quiz submitted successfully:', data); // Removed for production
                // Handle success - you can customize this part
                if (data.score !== undefined) {
                    alert(`Quiz completed! Score: ${data.score}/${data.total} (${data.percentage}%)`);
                }
            }
        })
        .catch(error => {
// console.error('❌ Quiz submission failed:', error); // Removed for production
            alert('Quiz submission failed: ' + error.message);
        });
    });
    
// console.log('✅ Enhanced quiz submission handler installed'); // Removed for production
}

// console.log('🎯 QUIZ 403 FIX COMPLETE!'); // Removed for production
// console.log('📋 SUMMARY:'); // Removed for production
// console.log('- Browser storage cleared'); // Removed for production
// console.log('- CSRF token refreshed'); // Removed for production
// console.log('- Authentication checked'); // Removed for production
// console.log('- Enhanced error handling installed'); // Removed for production
// console.log(''); // Removed for production
// console.log('🔧 If you still get 403 errors:'); // Removed for production
// console.log('1. Refresh the page completely (Ctrl+F5)'); // Removed for production
// console.log('2. Log out and log back in'); // Removed for production
// console.log('3. Clear all browser data for this site'); // Removed for production
// console.log('4. Try in an incognito/private window'); // Removed for production