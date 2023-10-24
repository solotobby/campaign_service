#!/bin/bash
sudo cp /var/www/.base-campaign-service.env /var/www/base-campaign-service/.env
sudo chown -R ubuntu:www-data /var/www/base-campaign-service
# Set permissions to storage and bootstrap cache
sudo find /var/www/base-campaign-service -type f -exec chmod 664 {} \;
sudo find /var/www/base-campaign-service -type d -exec chmod 775 {} \;
#
cd /var/www/base-campaign-service || exit
sudo chgrp -R www-data storage
sudo chmod -R ug+rwx storage
#
# Run composer
sudo composer  install
# Run artisan commands
php /var/www/base-campaign-service/artisan migrate
php /var/www/base-campaign-service/artisan cache:clear
