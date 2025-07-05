#!/bin/bash
echo "Updating all user XP values to use the new formula..."
php artisan users:update-xp
echo "Done! XP values have been reduced."
