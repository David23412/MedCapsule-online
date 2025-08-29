#!/bin/bash

echo "🧹 Cleaning up missing migration references..."

# Clean up missing migration references from database
php artisan migrate:cleanup

echo ""
echo "🔄 Starting migration refresh..."

# Refresh migrations (this will drop all tables and recreate them)
php artisan migrate:refresh --force

echo "✅ Migration refresh completed!"
echo "📊 Video progress tracking is now ready!"

# Optional: Run seeders if you have any
# php artisan db:seed

echo ""
echo "🌱 Seeding test data..."

# Seed test questions for notification testing
php artisan db:seed --class=TestQuestionsSeeder

echo ""
echo "🎯 Database structure updated:"
echo "   ✅ videos table (removed description, added indexes)"
echo "   ✅ video_user_progress table (pivot table, no completed column)"
echo "   ✅ All indexes optimized for performance"
echo "   ✅ Missing migration references cleaned up"
echo "   ✅ Test questions seeded for notification testing"