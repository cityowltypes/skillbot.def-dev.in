#!/usr/bin/env bash

source /var/www/.env

mysql -u${DB_USER} -p${DB_PASS} $DB_NAME < /var/www/install/install.sql
