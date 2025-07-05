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

## 🌐 For Ngrok Later

Once local is working:
```bash
./setup-ngrok.sh
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