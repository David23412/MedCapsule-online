# 🌐 MedCapsule Environment Switching Guide

## Quick Commands

### Switch to Local Environment
```bash
./switch-env.sh local
```

### Switch to Ngrok Environment  
```bash
./switch-env.sh ngrok
```

### Switch to Ngrok with Custom URL
```bash
./switch-env.sh ngrok https://your-custom-url.ngrok-free.app
```

### Test Tutor Mode
```bash
./test-tutor-mode.sh
```

## 🚀 Getting Started

### 1. Start Local Development
```bash
# Switch to local environment
./switch-env.sh local

# Start the server
php artisan serve

# Test tutor mode
./test-tutor-mode.sh
```

### 2. Switch to Ngrok for External Access
```bash
# Switch to ngrok environment
./switch-env.sh ngrok

# Your ngrok URL will be configured automatically
# Test the tutor mode URL:
# https://d54130044c29.ngrok-free.app/quiz/1/1/tutor?sources[]=Assuit&sources[]=Cairo
```

## 🔧 Manual Commands

### Laravel Artisan Commands
```bash
# Switch environment using Laravel command
php artisan env:switch local
php artisan env:switch ngrok
php artisan env:switch ngrok --url=https://your-url.ngrok-free.app

# Check current environment
php artisan env

# Clear caches after switching
php artisan config:clear
php artisan cache:clear
```

## 🧪 Testing Tutor Mode

### Local Testing
1. **Start server:** `php artisan serve`
2. **Visit:** `http://localhost:8000/quiz/1/1/tutor?sources[]=Assuit&sources[]=Cairo`
3. **Note:** You'll need to be logged in (302 redirect is normal)

### Ngrok Testing
1. **Switch environment:** `./switch-env.sh ngrok`
2. **Visit:** `https://d54130044c29.ngrok-free.app/quiz/1/1/tutor?sources[]=Assuit&sources[]=Cairo`
3. **Note:** Same login requirement applies

## 📊 Environment Status

### Current Configuration
- **Environment:** `local`
- **Debug Mode:** `ON` (for local development)
- **App URL:** `http://localhost:8000`
- **Cache Driver:** `file`
- **Session Driver:** `file`

### Expected Behavior
- ✅ **Local:** Fast development, no internet required
- ✅ **Ngrok:** External access, requires internet
- ✅ **Tutor Mode:** Working correctly (requires authentication)

## 🐛 Troubleshooting

### If Tutor Mode Returns 500 Error
1. **Check logs:** `tail -f storage/logs/laravel.log`
2. **Clear caches:** `php artisan config:clear && php artisan cache:clear`
3. **Restart server:** Stop and restart `php artisan serve`

### If Environment Switch Fails
1. **Check .env file:** Ensure it exists and is writable
2. **Manual switch:** Edit `.env` file directly
3. **Clear caches:** Run cache clear commands

### If Server Won't Start
1. **Check port:** Ensure port 8000 is available
2. **Check PHP:** Ensure PHP is installed and accessible
3. **Check dependencies:** Run `composer install`

## 📝 Recent Fixes Applied

### ✅ Tutor Mode 500 Error Fix
- **Issue:** Missing CacheService methods
- **Fix:** Added `get()`, `put()`, `forget()`, `remember()` methods
- **Status:** ✅ RESOLVED

### ✅ Environment Switching
- **Issue:** Manual environment configuration
- **Fix:** Created automated switching commands
- **Status:** ✅ RESOLVED

### ✅ Query Optimization
- **Issue:** Inefficient database queries
- **Fix:** Optimized all tutor mode queries
- **Status:** ✅ RESOLVED

## 🎯 Next Steps

1. **Test locally:** Use `./test-tutor-mode.sh`
2. **Switch to ngrok:** Use `./switch-env.sh ngrok` when ready
3. **Monitor logs:** Watch for any new errors
4. **Report issues:** If problems persist, check logs first

---

**✅ Environment switching is now fully automated and working!** 