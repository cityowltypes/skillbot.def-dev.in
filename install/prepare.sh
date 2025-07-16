#!/usr/bin/env bash

# This script downloads uploads and db archives and extracts them

UPLOADS_URL="http://skillbot.def-dev.in/skillbot.def-dev.in/uploads/uploads.zip"
DB_URL="http://skillbot.def-dev.in/skillbot.def-dev.in/uploads/mysql-backups/2025/06-June/16-Mon/backup-684fea44a2616-1750067780-skillbot_def.sql.7z"

curl -L -o uploads.zip $UPLOADS_URL
curl -L -o db.7z $DB_URL

unzip uploads.zip -d uploads
7za x db.7z
