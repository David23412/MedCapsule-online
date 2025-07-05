#!/bin/bash
echo "Updating user study streaks..."
php artisan users:update-streaks
echo "Update completed!" 