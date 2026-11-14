#! /bin/bash

php-fpm8.4

sed -i 's|\.\./|/var/www/|g' /var/www/web/*.php

nginx -g "daemon off;"
