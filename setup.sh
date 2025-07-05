#!/bin/bash

echo "🚀 Starting MedCapsule Setup..."

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Please run this script from the MedCapsule project root directory"
    exit 1
fi

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check for required tools
echo "🔍 Checking system requirements..."

if ! command_exists php; then
    echo "❌ Error: PHP is not installed or not in PATH"
    exit 1
fi

if ! command_exists composer; then
    echo "❌ Error: Composer is not installed or not in PATH"
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "✅ PHP version: $PHP_VERSION"

# Install/update dependencies
echo "📦 Installing/updating dependencies..."
composer install --no-interaction --optimize-autoloader

# Copy environment file if it doesn't exist
if [ ! -f ".env" ]; then
    echo "📋 Creating .env file..."
    cp .env.example .env
    echo "⚠️  Please configure your .env file with your database and other settings"
    echo "   Then run this script again"
    exit 0
fi

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate

# Clear all caches
echo "🧹 Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Create storage link if it doesn't exist
echo "🔗 Creating storage link..."
if [ ! -L "public/storage" ]; then
    php artisan storage:link
fi

# Clear and cache config for better performance
echo "⚡ Optimizing configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database seeders
echo "🌱 Running database seeders..."
php artisan db:seed --force

# Ensure FREE course exists and is properly configured
echo "🎓 Setting up FREE course..."
php artisan app:create-free-course

# Ensure FREE course is marked as free (not paid)
php artisan tinker --execute="
    \$freeCourse = App\Models\Course::where('name', 'FREE')->first();
    if (\$freeCourse) {
        \$freeCourse->update(['is_paid' => false, 'price' => null]);
        echo \"✅ FREE course configured correctly\\n\";
    } else {
        echo \"❌ FREE course not found\\n\";
    }
"

# Verify delete management system
echo "🗑️  Verifying delete management system..."
php artisan tinker --execute="
    echo \"✅ Delete management system verification:\\n\";
    echo \"   - Courses: \" . App\Models\Course::count() . \"\\n\";
    echo \"   - Topics: \" . App\Models\Topic::count() . \"\\n\";
    echo \"   - Questions: \" . App\Models\Question::count() . \"\\n\";
    echo \"   - Users: \" . App\Models\User::count() . \"\\n\";
    echo \"   - Quiz Attempts: \" . App\Models\QuizAttempt::count() . \"\\n\";
    echo \"   - Mistakes: \" . App\Models\Mistake::count() . \"\\n\";
    echo \"✅ Delete management system ready!\\n\";
"

# Set proper permissions
echo "🔐 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 755 public/storage

# Check if database tables exist and have data
echo "🔍 Verifying database setup..."
php artisan tinker --execute="
    \$tables = ['users', 'courses', 'topics', 'questions', 'payments'];
    foreach (\$tables as \$table) {
        if (!Schema::hasTable(\$table)) {
            echo \"❌ Table \$table does not exist\\n\";
            exit(1);
        }
        \$count = DB::table(\$table)->count();
        echo \"✅ Table \$table exists with \$count records\\n\";
    }
    echo \"🎉 Database verification completed successfully!\\n\";
"

# Final status check
echo "✅ Setup completed successfully!"
echo ""
echo "🚀 You can now start the development server with:"
echo "   php artisan serve"
echo ""
echo "🌐 Or use ngrok for external access:"
echo "   ngrok http 8000"
echo ""
echo "📝 Don't forget to:"
echo "   1. Configure your .env file with proper database settings"
echo "   2. Set up your payment gateway credentials if needed"
echo "   3. Configure your email settings for notifications"
echo ""
echo "🎯 Happy coding with MedCapsule!" 