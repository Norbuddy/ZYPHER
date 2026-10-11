#!/bin/bash

# Copy Nginx config pointing to public/ directory and reload
if [ -f /home/site/wwwroot/default ]; then
    cp /home/site/wwwroot/default /etc/nginx/sites-available/default
    service nginx reload
fi

# Ensure storage directories and permissions
mkdir -p /home/site/wwwroot/storage/framework/sessions
mkdir -p /home/site/wwwroot/storage/framework/views
mkdir -p /home/site/wwwroot/storage/framework/cache
mkdir -p /home/site/wwwroot/storage/app/public/profile-photos
chmod -R 777 /home/site/wwwroot/storage
chmod -R 777 /home/site/wwwroot/bootstrap/cache

cd /home/site/wwwroot
php artisan storage:link || true
php artisan optimize:clear || true
