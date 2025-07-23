#!/bin/bash

# Exit on any error
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_status "Starting Tribe application container..."

# Set proper ownership and permissions
print_status "Setting up file permissions..."
chown -R www-data:www-data /var/www/uploads /var/www/logs
chmod -R 755 /var/www/uploads
chmod -R 755 /var/www/logs

# Create necessary directories if they don't exist
mkdir -p /var/www/uploads /var/www/logs /var/www/cache /var/www/sessions
chown -R www-data:www-data /var/www/uploads /var/www/logs /var/www/cache /var/www/sessions

# Check if .env file exists
if [ ! -f /var/www/.env ]; then
    print_warning ".env file not found. Application may not work correctly."
fi

# Wait for database to be ready
print_status "Waiting for database connection..."
timeout=60
counter=0

while ! mysqladmin ping -h"${DB_HOST:-db}" -u"${DB_USER:-root}" -p"${DB_PASS}" --silent; do
    counter=$((counter + 1))
    if [ $counter -gt $timeout ]; then
        print_error "Database connection timeout after ${timeout} seconds"
        exit 1
    fi
    print_status "Waiting for database... (${counter}/${timeout})"
    sleep 1
done

print_status "Database connection established!"

# Run composer install if vendor directory doesn't exist or is empty
if [ ! -d "/var/www/vendor" ] || [ -z "$(ls -A /var/www/vendor)" ]; then
    print_status "Installing PHP dependencies..."
    cd /var/www
    composer install --no-dev --optimize-autoloader --no-interaction
else
    print_status "PHP dependencies already installed"
fi

# Run npm install if node_modules doesn't exist or is empty
if [ ! -d "/var/www/node_modules" ] || [ -z "$(ls -A /var/www/node_modules)" ]; then
    print_status "Installing Node.js dependencies..."
    cd /var/www
    npm install --production
else
    print_status "Node.js dependencies already installed"
fi

# Set up PHP-FPM configuration
print_status "Configuring PHP-FPM..."
PHP_FPM_CONF="/etc/php/7.4/fpm/pool.d/www.conf"

# Ensure PHP-FPM listens on socket
sed -i 's/listen = .*/listen = \/run\/php\/php7.4-fpm.sock/' "$PHP_FPM_CONF"
sed -i 's/;listen.owner = .*/listen.owner = www-data/' "$PHP_FPM_CONF"
sed -i 's/;listen.group = .*/listen.group = www-data/' "$PHP_FPM_CONF"
sed -i 's/;listen.mode = .*/listen.mode = 0660/' "$PHP_FPM_CONF"

# Create PHP-FPM run directory
mkdir -p /run/php
chown www-data:www-data /run/php

# Set up Nginx default configuration if custom config doesn't exist
if [ ! -f "/etc/nginx/conf.d/default.conf" ]; then
    print_status "Setting up default Nginx configuration..."
    cat > /etc/nginx/conf.d/default.conf << 'EOF'
server {
    listen 80;
    server_name _;
    root /var/www;
    index index.php index.html index.htm;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Increase client max body size for file uploads
    client_max_body_size 1024M;

    # Handle PHP files
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Increase timeouts for long-running scripts
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }

    # Handle static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # API routes
    location /api/ {
        try_files $uri $uri/ /api/index.php?$query_string;
    }

    # Admin routes  
    location /admin/ {
        try_files $uri $uri/ /admin/index.php?$query_string;
    }

    # phpMyAdmin
    location /phpmyadmin/ {
        try_files $uri $uri/ /phpmyadmin/index.php?$query_string;
    }

    # Default route handling
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /(composer\.(json|lock)|package\.(json|lock)|\.env)$ {
        deny all;
    }

    # Error and access logs
    error_log /var/www/logs/nginx_error.log;
    access_log /var/www/logs/nginx_access.log;
}
EOF
else
    print_status "Using custom Nginx configuration"
fi

# Test Nginx configuration
print_status "Testing Nginx configuration..."
if ! nginx -t; then
    print_error "Nginx configuration test failed!"
    exit 1
fi

# Start PHP-FPM
print_status "Starting PHP-FPM..."
service php7.4-fpm start

# Check if PHP-FPM is running
if ! pgrep -f "php-fpm: master process" > /dev/null; then
    print_error "Failed to start PHP-FPM!"
    exit 1
fi

# Start Nginx
print_status "Starting Nginx..."
nginx -g "daemon off;" &
NGINX_PID=$!

# Check if Nginx started successfully
sleep 2
if ! kill -0 $NGINX_PID 2>/dev/null; then
    print_error "Failed to start Nginx!"
    exit 1
fi

print_status "✅ Tribe application is ready!"
print_status "PHP-FPM: Running"
print_status "Nginx: Running on port 80"
print_status "Application: http://localhost:${TRIBE_PORT:-80}"

# Function to handle shutdown
shutdown() {
    print_status "Shutting down services..."
    print_status "Stopping Nginx..."
    kill $NGINX_PID 2>/dev/null || true
    print_status "Stopping PHP-FPM..."
    service php7.4-fpm stop
    print_status "Shutdown complete"
    exit 0
}

# Set up signal handlers
trap shutdown SIGTERM SIGINT

# Keep the script running and monitor services
while kill -0 $NGINX_PID 2>/dev/null; do
    sleep 10
    
    # Check if PHP-FPM is still running
    if ! pgrep -f "php-fpm: master process" > /dev/null; then
        print_error "PHP-FPM has stopped unexpectedly!"
        service php7.4-fpm start
        if ! pgrep -f "php-fpm: master process" > /dev/null; then
            print_error "Failed to restart PHP-FPM!"
            exit 1
        fi
        print_status "PHP-FPM restarted successfully"
    fi
done

print_error "Nginx has stopped unexpectedly!"
exit 1