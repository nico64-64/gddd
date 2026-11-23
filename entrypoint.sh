#! /bin/bash

chown -R www-data:www-data /var/www/lockfiles

php-fpm8.4

nginx -g "daemon off;"
