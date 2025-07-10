#!/bin/bash

echo "🧪 Testing Tutor Mode Functionality"
echo "=================================="

# Check if server is running
echo "🔍 Checking if server is running..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000)

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Server is running (HTTP $HTTP_CODE)"
else
    echo "❌ Server is not responding properly (HTTP $HTTP_CODE)"
    echo "🚀 Starting server..."
    php artisan serve --host=0.0.0.0 --port=8000 &
    sleep 3
fi

# Test tutor mode endpoint
echo ""
echo "🔍 Testing tutor mode endpoint..."
TUTOR_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:8000/quiz/1/1/tutor?sources[]=Assuit&sources[]=Cairo")

echo "📊 Tutor mode response code: $TUTOR_CODE"

case $TUTOR_CODE in
    "200")
        echo "✅ Tutor mode is working correctly!"
        ;;
    "302")
        echo "⚠️  Tutor mode is redirecting (likely authentication required)"
        echo "💡 This is normal - you need to be logged in to access tutor mode"
        ;;
    "404")
        echo "❌ Tutor mode route not found"
        ;;
    "500")
        echo "❌ Tutor mode has a server error"
        echo "🔍 Check the logs: tail -f storage/logs/laravel.log"
        ;;
    *)
        echo "❓ Unexpected response: $TUTOR_CODE"
        ;;
esac

echo ""
echo "🌐 You can now test tutor mode in your browser:"
echo "   http://localhost:8000/quiz/1/1/tutor?sources[]=Assuit&sources[]=Cairo"
echo ""
echo "🔧 To switch back to ngrok:"
echo "   ./switch-env.sh ngrok"
echo ""
echo "📝 Current environment:"
php artisan env 