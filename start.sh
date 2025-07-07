#!/bin/bash

# Exit on error
set -e

# Kill any existing processes on port 8000 and 4040
kill_port() {
    local port=$1
    local pid=$(lsof -t -i:$port)
    if [ ! -z "$pid" ]; then
        echo "Killing process on port $port (PID: $pid)..."
        kill -9 $pid 2>/dev/null || true
    fi
}

# Function to check if Laravel is running
check_laravel() {
    curl -s http://localhost:8000 > /dev/null
    return $?
}

# Clean up function
cleanup() {
    echo "Cleaning up..."
    kill_port 8000
    kill_port 4040
    pkill -f ngrok || true
}

# Set up cleanup on script exit
trap cleanup EXIT

echo "🚀 Starting Medcapsule services..."

# Kill existing processes
echo "Cleaning up old processes..."
cleanup

# Start Laravel server in the background
echo "Starting Laravel server..."
php artisan serve &
LARAVEL_PID=$!

# Wait for Laravel to be ready
echo "Waiting for Laravel to start..."
max_attempts=30
attempt=0
while ! check_laravel; do
    attempt=$((attempt + 1))
    if [ $attempt -ge $max_attempts ]; then
        echo "❌ Laravel failed to start after $max_attempts attempts"
        exit 1
    fi
    echo "Waiting... ($attempt/$max_attempts)"
    sleep 1
done
echo "✅ Laravel is running!"

# Start ngrok in a new terminal window with specific configuration
echo "Starting ngrok..."
osascript -e '
tell application "Terminal"
    do script "cd '$(pwd)' && ngrok http --region=us --host-header=rewrite --log=stdout 8000"
end tell
'

# Print helpful information
echo "
✅ Services started successfully!
📱 Local URL: http://localhost:8000
🌍 Ngrok Dashboard: http://localhost:4040
🔄 Ngrok URL: Check the ngrok terminal window for your public URL

To stop all services, press Ctrl+C
"

# Wait for Laravel server
wait $LARAVEL_PID 