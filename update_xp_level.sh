#!/bin/bash
echo "Updating XP level threshold to 1000 XP..."
php artisan users:update-xp
echo "Done! XP levels now require 1000 XP each."
