#!/bin/bash
git pull
composer install -vvv
php artisan migrate
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan route:cache
composer dump-autoload