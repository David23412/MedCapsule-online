# 🚨 IMMEDIATE FIX for 505 Error

## Your Problem: Missing .env + Database Connection

You're getting a 505 error because:
- ❌ No `.env` file exists
- ❌ Not connected to your "medcapsule" database

## 🚀 QUICK SOLUTION (2 minutes)

### **Option 1: Automated Fix**
```bash
./quick-fix.sh
```

### **Option 2: Manual Fix**
```bash
# 1. Copy the medcapsule template
cp .env.medcapsule .env

# 2. Generate app key
php artisan key:generate

# 3. Edit .env file if your database settings are different:
#    - DB_HOST (if not localhost)
#    - DB_PORT (if not 3306)  
#    - DB_USERNAME (if not root)
#    - DB_PASSWORD (if you have one)

# 4. Test connection
php artisan migrate:status

# 5. Start server
php artisan serve
```

## 🔧 If Database Connection Fails

Edit your `.env` file and update:
```env
DB_HOST=127.0.0.1        # Your MySQL host
DB_PORT=3306             # Your MySQL port
DB_DATABASE=medcapsule   # Your database name
DB_USERNAME=root         # Your MySQL username
DB_PASSWORD=             # Your MySQL password
```

## 🎯 After Fix

1. Visit: http://localhost:8000
2. ✅ Login should work
3. ✅ Quizzes should work
4. ✅ All database features should work

## 🌐 For Ngrok Setup

Once local is working, set up ngrok for external access:

### **Option 1: Automated Ngrok Setup**
```bash
./setup-ngrok.sh
```

### **Option 2: Manual Ngrok Setup**
```bash
# 1. Start ngrok tunnel
ngrok http 8000

# 2. Copy your ngrok URL (e.g., https://abc123.ngrok-free.app)

# 3. Update .env with ngrok URL
sed -i '' "s|APP_URL=.*|APP_URL=https://YOUR_NGROK_URL|g" .env
sed -i '' "s|ASSET_URL=.*|ASSET_URL=https://YOUR_NGROK_URL|g" .env

# 4. Configure for ngrok
sed -i '' "s|APP_ENV=.*|APP_ENV=local|g" .env
sed -i '' "s|SESSION_DRIVER=.*|SESSION_DRIVER=database|g" .env
sed -i '' "s|SESSION_DOMAIN=.*|SESSION_DOMAIN=|g" .env
sed -i '' "s|SESSION_SAME_SITE=.*|SESSION_SAME_SITE=none|g" .env
sed -i '' "s|SESSION_SECURE=.*|SESSION_SECURE=false|g" .env

# 5. Clear caches
php artisan config:clear
php artisan cache:clear
```

## 🆘 Still Having Issues?

```bash
# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check migration status  
php artisan migrate:status

# Check environment
php artisan env:status
```

## 📝 Common Ngrok Issues

If you encounter issues with ngrok:

1. **419 CSRF Errors**: Make sure `SESSION_SAME_SITE=none` and `SESSION_SECURE=false`
2. **Asset Loading**: Ensure `ASSET_URL` matches your `APP_URL`
3. **Session Issues**: Use `SESSION_DRIVER=database` instead of `file`
4. **Cookie Problems**: Set `SESSION_HTTP_ONLY=false` for ngrok

## 🎯 Environment Files Available

- `.env.medcapsule` - For local development with medcapsule database
- `.env.ngrok` - Pre-configured for ngrok usage
- `.env.development.example` - Template for development
- `.env.production.example` - Template for production