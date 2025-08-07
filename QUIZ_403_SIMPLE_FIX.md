# 🔥 Quiz 403 Error Fix Guide

## The Problem
You're getting a 403 Forbidden error when submitting quizzes. This is typically caused by:

1. **CSRF Token Issues** - The most common cause
2. **Authentication Problems** - User session expired
3. **Route Access Issues** - Middleware blocking the request

## Quick Fixes (Try in order)

### Fix 1: Clear Browser Data
In your browser console, run:
```javascript
localStorage.clear(); 
sessionStorage.clear(); 
location.reload();
```

### Fix 2: Check CSRF Token
The quiz form should have a CSRF token. Look for this in the page source:
```html
<meta name="csrf-token" content="...">
```

### Fix 3: Verify Login Status
Make sure you're still logged in. If your session expired, log out and log back in.

### Fix 4: Check Network Tab
1. Open browser Developer Tools (F12)
2. Go to Network tab
3. Submit the quiz
4. Look for the failed request to see the exact error

## Technical Fixes

### Fix 1: Update CSRF Middleware
The quiz grade route should be excluded from CSRF verification.