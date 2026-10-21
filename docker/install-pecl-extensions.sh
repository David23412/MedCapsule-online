#!/bin/sh
set -eu

for extension in "$@"; do
    attempt=1
    until pecl install "$extension"; do
        if [ "$attempt" -ge 3 ]; then
            echo "Unable to install PHP extension $extension after $attempt attempts." >&2
            exit 1
        fi
        attempt=$((attempt + 1))
        sleep 2
    done
done
