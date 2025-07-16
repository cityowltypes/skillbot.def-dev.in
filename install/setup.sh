#!/usr/bin/env bash

# This scripts creates a new instance for skillbot docker container

random_password() {
    return $(tr -dc A-Za-z0-9 </dev/urandom | head -c 13; echo)
}

random_name() {
    adjectives=(silent brave clever wild lucky)
    nouns=(tiger panda eagle shark fox)
    rand_adjective=${adjectives[$RANDOM % ${#adjectives[@]}]}
    rand_noun=${nouns[$RANDOM % ${#nouns[@]}]}
    rand_number=$((RANDOM % 10000))

    random_name="${rand_adjective}_${rand_noun}_${rand_number}"
    return "$random_name"
}

# Generate values
JUNCTION_PASS=$(random_password)

read -p "Website URL: " WEB_BARE_URL
read -p "Bot name: " APP_NAME
read -p "Junction URL: " JUNCTION_URL
read -p "Enter a port number for Junction: " JUNCTION_PORT
read -p "Enter a port number for Tribe: " TRIBE_PORT

# remove http(s):// from WEB_BARE_URL
WEB_BARE_URL=${WEB_BARE_URL#https://}
WEB_BARE_URL=${WEB_BARE_URL#http://}

APP_UID=$(random_name)

DB_NAME=$APP_UID
DB_USER="skillbot"
DB_PASS=$(random_password)
DB_HOST="${APP_UID}_db"

TRIBE_API_SECRET_KEY=$(random_password)

# start filling the .env file
[[ ! -f ./.env ]] && cp sample.env .env # create .env file if it doesn't exist already

sed -i "s/\$JUNCTION_PASS/$JUNCTION_PASS/g" .env

sed -i "s/\$WEB_BARE_URL/$WEB_BARE_URL/g" .env
sed -i "s/\$JUNCTION_URL/$JUNCTION_URL/g" .env

sed -i "s/\$TRIBE_PORT/$TRIBE_PORT/g" .env
sed -i "s/\$JUNCTION_PORT/$JUNCTION_PORT/g" .env

sed -i "s/\$DB_NAME/$DB_NAME/g" .env
sed -i "s/\$DB_USER/$DB_USER/g" .env
sed -i "s/\$DB_PASS/$DB_PASS/g" .env
sed -i "s/\$DB_HOST/$DB_HOST/g" .env
sed -i "s/\$TRIBE_API_SECRET_KEY/$TRIBE_API_SECRET_KEY/g" .env
