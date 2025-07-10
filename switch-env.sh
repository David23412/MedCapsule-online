#!/bin/bash

# MedCapsule Environment Switcher
# Usage: ./switch-env.sh [local|ngrok|production] [custom-url]

echo "🔄 MedCapsule Environment Switcher"
echo "=================================="

if [ -z "$1" ]; then
    echo "❌ Please specify an environment: local, ngrok, or production"
    echo "Usage: ./switch-env.sh [local|ngrok|production] [custom-url]"
    echo ""
    echo "Examples:"
    echo "  ./switch-env.sh local"
    echo "  ./switch-env.sh ngrok"
    echo "  ./switch-env.sh ngrok https://your-ngrok-url.ngrok-free.app"
    exit 1
fi

ENVIRONMENT=$1
CUSTOM_URL=$2

echo "🎯 Switching to: $ENVIRONMENT"

# Run the Laravel command
if [ -z "$CUSTOM_URL" ]; then
    php artisan env:switch $ENVIRONMENT
else
    php artisan env:switch $ENVIRONMENT --url=$CUSTOM_URL
fi

echo ""
echo "✅ Environment switch completed!"
echo ""

# Show helpful commands
case $ENVIRONMENT in
    "local")
        echo "🌐 Local server should be running at: http://localhost:8000"
        echo "🚀 To start server: php artisan serve"
        echo "🔍 Test tutor mode: http://localhost:8000/quiz/1/1/tutor?sources[]=Assuit&sources[]=Cairo"
        ;;
    "ngrok")
        echo "🌐 Ngrok server should be accessible via your ngrok URL"
        echo "🔍 Test tutor mode: [your-ngrok-url]/quiz/1/1/tutor?sources[]=Assuit&sources[]=Cairo"
        ;;
    "production")
        echo "🚀 Production environment configured"
        echo "⚠️  Remember to update APP_URL in .env for your production domain"
        ;;
esac

echo ""
echo "📝 Current environment: $(php artisan env)"
echo "🔗 App URL: $(php artisan tinker --execute="echo config('app.url');" 2>/dev/null)" 