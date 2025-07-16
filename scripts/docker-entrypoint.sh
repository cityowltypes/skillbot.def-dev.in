#!/usr/bin/env bash

set -e

service php8.3-fpm restart
service nginx start

touch /var/www/logs/access.log

exec tail --follow /var/www/logs/access.log /var/www/logs/error.log
