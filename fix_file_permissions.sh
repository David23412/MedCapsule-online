#!/bin/bash

# FIXED: File Permission Settings - Set secure file permissions

echo "🔧 Setting secure file permissions..."

# Set secure permissions for sensitive files
chmod 600 .env
chmod 600 .env.example
chmod 644 composer.json
chmod 644 package.json

# Set secure permissions for directories
chmod 755 storage/
chmod 755 storage/app/
chmod 755 storage/framework/
chmod 755 storage/logs/
chmod 755 bootstrap/cache/

# Set writable permissions for Laravel directories
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

# Set secure permissions for configuration files
chmod 644 config/*.php

# Set executable permissions for artisan
chmod +x artisan

# Set secure permissions for public directory
chmod 755 public/
chmod 644 public/index.php
chmod 644 public/.htaccess

# Create .htaccess files for sensitive directories
echo "Deny from all" > storage/.htaccess
echo "Deny from all" > bootstrap/cache/.htaccess
echo "Deny from all" > vendor/.htaccess

echo "✅ File permissions set securely"