#!/bin/bash

echo "🚀 FIXING COURSE CACHE ISSUE - MAKING IT FAST AS FUCK!"
echo ""

# Run the PHP fix script
echo "1. Running cache fix script..."
php fix-course-cache-issue.php

echo ""
echo "2. Running Laravel cache clear commands..."

# Clear Laravel caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear

echo ""
echo "3. Running custom course cache clear command..."
php artisan courses:clear-cache --nuclear

echo ""
echo "🎉 DONE! Your course updates are now SMOOTH AND FAST AS FUCK!"
echo "💡 Changes will reflect INSTANTLY without needing to clear cookies!"
echo ""
echo "🔧 What was fixed:"
echo "   ✅ Reduced cache TTL from 1 hour to 30 seconds"
echo "   ✅ Added aggressive cache invalidation"
echo "   ✅ Added database observers for direct DB changes"
echo "   ✅ Added cache-busting middleware"
echo "   ✅ Added browser cache prevention headers"
echo ""
echo "🚀 Your courses page is now ULTRA-RESPONSIVE! 🚀"