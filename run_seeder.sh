#!/bin/bash
cd /var/www/emarepos/pos-system
php artisan db:seed --class=SampleDataSeeder 2>&1
echo "EXIT:$?"
