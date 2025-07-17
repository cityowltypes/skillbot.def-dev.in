#!/usr/bin/env bash

# This scripts creates a new instance for skillbot docker container

random_password() {
    echo $(tr -dc A-Za-z0-9 </dev/urandom | head -c 13; echo)
}

random_name() {
    adjectives=(silent brave clever wild lucky)
    nouns=(tiger panda eagle shark fox)
    rand_adjective=${adjectives[$RANDOM % ${#adjectives[@]}]}
    rand_noun=${nouns[$RANDOM % ${#nouns[@]}]}
    rand_number=$((RANDOM % 10000))

    random_name="${rand_adjective}_${rand_noun}_${rand_number}"
    echo "$random_name"
}

# start filling the .env file
if [[ ! -f ./.env ]]; then
    cp sample.env .env
    OVERWRITE_ENV="yes"
else
    read -p "Do you want to overwrite .env? (yes/no) " OVERWRITE_ENV
fi

if [[ "${OVERWRITE_ENV,,}" == "yes" ]]; then
    read -p "Website URL: " WEB_BARE_URL
    read -p "Application name: " APP_NAME
    read -p "Enter a port number for Tribe: " TRIBE_PORT

    # remove http(s):// from WEB_BARE_URL
    WEB_BARE_URL=${WEB_BARE_URL#https://}
    WEB_BARE_URL=${WEB_BARE_URL#http://}

    APP_UID=$(random_name)

    DB_NAME="skillbot_db"
    DB_USER="skillbot_user"
    DB_PASS=$(random_password)
    DB_HOST="${APP_UID}_db"

    TRIBE_API_SECRET_KEY=$(random_password)

    sed -i "s/\$WEB_BARE_URL/$WEB_BARE_URL/g" .env

    sed -i "s/\$TRIBE_PORT/$TRIBE_PORT/g" .env

    sed -i "s/\$DB_NAME/$DB_NAME/g" .env
    sed -i "s/\$DB_USER/$DB_USER/g" .env
    sed -i "s/\$DB_PASS/$DB_PASS/g" .env
    sed -i "s/\$DB_HOST/$DB_HOST/g" .env
    sed -i "s/\$DB_HOST/$DB_HOST/g" config/phpmyadmin/config.inc.php

    sed -i "s/\$APP_NAME/$APP_NAME/g" .env
    sed -i "s/\$TRIBE_API_SECRET_KEY/$TRIBE_API_SECRET_KEY/g" .env
fi

# bash install/prepare.sh

# ENV file is prepared, proceed with import and setups
if docker compose version >/dev/null 2>&1; then
    # start docker
    docker compose up -d
    # import db
    docker compose exec db bash /var/www/install/import.sh
elif command -v docker-compose >/dev/null 2>&1; then
    # start docker
    docker-compose up -d
    # import db
    docker-compose exec db bash /var/www/install/import.sh
else
    echo "Neither 'docker compose' nor 'docker-compose' is available"
    exit 1
fi
