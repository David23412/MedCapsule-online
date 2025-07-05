#!/bin/bash
echo "Migrating users to the accumulative XP system..."
php artisan xp:migrate-to-accumulative
echo "Migration completed!" 