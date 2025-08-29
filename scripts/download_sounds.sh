#!/bin/bash

# Create sounds directory if it doesn't exist
mkdir -p public/sounds

# Download sound files from a reliable source
# Using short, optimized sounds for better user experience

# Perfect score sound (short success jingle)
curl -L "https://assets.mixkit.co/active_storage/sfx/2018/success-1-6297.wav" -o "public/sounds/quiz-perfect.mp3"

# Partial score sound (neutral notification)
curl -L "https://assets.mixkit.co/active_storage/sfx/2017/ding-2-7060.wav" -o "public/sounds/quiz-partial.mp3"

# Wrong score sound (gentle error)
curl -L "https://assets.mixkit.co/active_storage/sfx/947/error-1-7060.wav" -o "public/sounds/quiz-wrong.mp3"

# Convert WAV to optimized MP3 if ffmpeg is available
if command -v ffmpeg >/dev/null 2>&1; then
    echo "Optimizing sound files with ffmpeg..."
    for file in public/sounds/*.mp3; do
        ffmpeg -i "$file" -codec:a libmp3lame -qscale:a 2 -ar 44100 -ac 1 "${file}.temp" && mv "${file}.temp" "$file"
    done
    echo "Sound optimization complete!"
else
    echo "ffmpeg not found - skipping sound optimization"
fi

# Set correct permissions
chmod 644 public/sounds/*.mp3

echo "Sound files downloaded and processed successfully!" 