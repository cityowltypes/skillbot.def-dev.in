#!/bin/bash

# Exit on any error and enable debug mode if needed
# set -e
[[ "${DEBUG:-false}" == "true" ]] && set -x

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly NC='\033[0m' # No Color

# Configuration
readonly MAX_DB_WAIT_TIME=120
readonly HEALTH_CHECK_INTERVAL=10
readonly PHP_FPM_SOCKET="/run/php/php7.4-fpm.sock"

# Function to load environment variables from .env file
load_env_file() {
    local env_file="/var/www/.env"
    
    if [[ -f "$env_file" ]]; then
        print_status "Loading environment variables from $env_file"
        
        # Read .env file and export variables
        while IFS='=' read -r key value; do
            # Skip empty lines and comments
            [[ -z "$key" || "$key" =~ ^[[:space:]]*# ]] && continue
            
            # Remove quotes from value if present
            value=$(echo "$value" | sed 's/^"\(.*\)"$/\1/' | sed "s/^'\(.*\)'$/\1/")
            
            # Export the variable
            export "$key"="$value"
            print_debug "Loaded: $key=$value"
        done < <(grep -v '^[[:space:]]*$' "$env_file" | grep -v '^[[:space:]]*#')
        
        print_status "Environment variables loaded successfully"
    else
        print_warning ".env file not found at $env_file, using default values"
    fi
}

# Default values (will be overridden by .env file if present)
WEB_BARE_URL="${WEB_BARE_URL:-localhost}"
WEB_URL="${WEB_URL:-http://localhost:8080}"
WEBSITE_NAME="${WEBSITE_NAME:-tribe}"
APP_UID="${APP_UID:-tribe}"
TRIBE_API_SECRET_KEY="${TRIBE_API_SECRET_KEY:-default_secret}"
DB_PORT="${DB_PORT:-3306}"
TRIBE_PORT="${TRIBE_PORT:-8080}"
DOCKER_EXTERNAL_TRIBE_URL="${DOCKER_EXTERNAL_TRIBE_URL:-http://localhost:8080}"
TRIBE_SLUG="${TRIBE_SLUG:-tribe}"
DB_HOST="${DB_HOST:-db}"
DB_ROOT_PASS="${DB_ROOT_PASS:-rootpassword123}"
DB_USER="${DB_USER:-tribe_user}"
DB_PASS="${DB_PASS:-userpassword123}"
DB_NAME="${DB_NAME:-tribe_db}"

# PID files
NGINX_PID=""
PHP_FPM_PID=""

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

print_debug() {
    [[ "${DEBUG:-false}" == "true" ]] && echo -e "${BLUE}[DEBUG]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

# Function to check if a service is running
is_service_running() {
    local service_name="$1"
    local pid_file="$2"
    
    if [[ -n "$pid_file" && -f "$pid_file" ]]; then
        local pid=$(cat "$pid_file")
        kill -0 "$pid" 2>/dev/null
    else
        pgrep -f "$service_name" > /dev/null 2>&1
    fi
}

# Function to wait for database
wait_for_database() {
    print_status "Waiting for database connection..."
    
    local counter=0
    local db_host="$DB_HOST"
    local db_user="$DB_USER"
    local db_pass="$DB_PASS"
    
    if [[ -z "$db_pass" ]]; then
        print_error "Database password not provided"
        return 1
    fi
    
    while ! mysqladmin ping -h"$db_host" -u"$db_user" -p"$db_pass" --silent 2>/dev/null; do
        counter=$((counter + 1))
        if [[ $counter -gt $MAX_DB_WAIT_TIME ]]; then
            print_error "Database connection timeout after ${MAX_DB_WAIT_TIME} seconds"
            return 1
        fi
        print_debug "Waiting for database... (${counter}/${MAX_DB_WAIT_TIME})"
        sleep 1
    done
    
    print_status "Database connection established!"
    return 0
}

# Function to set up file permissions
setup_permissions() {
    print_status "Setting up file permissions..."
    
    local directories=("uploads" "logs" "cache" "sessions" "tmp")
    
    for dir in "${directories[@]}"; do
        print_debug "Processing directory: $dir"
        
        # Create directory if it doesn't exist
        if [[ ! -d "./$dir" ]]; then
            print_debug "Creating directory: $dir"
            mkdir -p "./$dir" || {
                print_warning "Failed to create directory: $dir"
                continue
            }
        fi
        
        # Try to change ownership, but don't fail if it doesn't work
        if chown -R www-data:www-data "./$dir" 2>/dev/null; then
            print_debug "Changed ownership of $dir to www-data:www-data"
        else
            print_warning "Could not change ownership of $dir (may be a volume mount)"
        fi
        
        # Try to change permissions, but don't fail if it doesn't work
        if chmod -R 755 "./$dir" 2>/dev/null; then
            print_debug "Set permissions 755 on $dir"
        else
            print_warning "Could not change permissions of $dir (may be a volume mount)"
        fi
    done
    
    # Ensure /run/php directory exists with correct permissions
    mkdir -p /run/php
    chown www-data:www-data /run/php
    chmod 755 /run/php
    
    print_status "File permissions setup completed"
}

# Function to validate environment
validate_environment() {
    print_status "Validating environment..."
    
    # Check required environment variables
    local required_vars=("DB_HOST" "DB_USER" "DB_PASS" "DB_NAME" "TRIBE_PORT")
    local missing_vars=()
    
    for var in "${required_vars[@]}"; do
        if [[ -z "${!var}" ]]; then
            missing_vars+=("$var")
        fi
    done
    
    if [[ ${#missing_vars[@]} -gt 0 ]]; then
        print_error "Missing required environment variables: ${missing_vars[*]}"
        return 1
    fi
    
    # Print loaded configuration for debugging
    print_debug "Configuration loaded:"
    print_debug "  WEB_BARE_URL: $WEB_BARE_URL"
    print_debug "  WEB_URL: $WEB_URL"
    print_debug "  WEBSITE_NAME: $WEBSITE_NAME"
    print_debug "  APP_UID: $APP_UID"
    print_debug "  DB_HOST: $DB_HOST"
    print_debug "  DB_PORT: $DB_PORT"
    print_debug "  DB_NAME: $DB_NAME"
    print_debug "  DB_USER: $DB_USER"
    print_debug "  TRIBE_PORT: $TRIBE_PORT"
    print_debug "  TRIBE_SLUG: $TRIBE_SLUG"
    
    return 0
}

# Function to start PHP-FPM - FIXED VERSION
start_php_fpm() {
    print_status "Starting PHP-FPM..."
    
    # Ensure /run/php directory exists
    mkdir -p /run/php
    chown www-data:www-data /run/php
    
    # Test PHP-FPM configuration
    if ! php-fpm7.4 -t; then
        print_error "PHP-FPM configuration test failed!"
        return 1
    fi
    
    # Kill any existing PHP-FPM processes
    pkill -f php-fpm || true
    
    # Remove old socket if it exists
    rm -f "$PHP_FPM_SOCKET"
    
    # Start PHP-FPM directly (not as a service)
    print_debug "Starting PHP-FPM process..."
    php-fpm7.4 --daemonize --fpm-config /etc/php/7.4/fpm/php-fpm.conf
    
    # Wait for socket to be created
    local counter=0
    while [[ ! -S "$PHP_FPM_SOCKET" && $counter -lt 30 ]]; do
        print_debug "Waiting for PHP-FPM socket... ($counter/30)"
        sleep 1
        counter=$((counter + 1))
    done
    
    if [[ ! -S "$PHP_FPM_SOCKET" ]]; then
        print_error "PHP-FPM socket not created at $PHP_FPM_SOCKET!"
        print_debug "Directory contents of /run/php/:"
        ls -la /run/php/ || true
        return 1
    fi
    
    # Verify socket permissions
    chown www-data:www-data "$PHP_FPM_SOCKET" 2>/dev/null || true
    chmod 660 "$PHP_FPM_SOCKET" 2>/dev/null || true
    
    # Get PHP-FPM PID
    PHP_FPM_PID=$(pgrep -f "php-fpm: master process" || echo "")
    
    if [[ -z "$PHP_FPM_PID" ]]; then
        print_error "Failed to get PHP-FPM PID!"
        return 1
    fi
    
    print_status "PHP-FPM started successfully (PID: $PHP_FPM_PID)"
    print_debug "PHP-FPM socket created at: $PHP_FPM_SOCKET"
    ls -la "$PHP_FPM_SOCKET"
    
    return 0
}

# Function to setup nginx configuration
setup_nginx_config() {
    if [[ ! -f "/etc/nginx/conf.d/default.conf" ]]; then
        print_status "Setting up default Nginx configuration..."
        cat > /etc/nginx/conf.d/default.conf << EOF
server {
    listen 80;
    server_name $WEB_BARE_URL;
    root /var/www;
    index index.html index.htm index.php;

    error_log /var/www/logs/error.log;
    access_log /var/www/logs/access.log;
    
    # Security: Disable .env and other hidden files, except .well-known (from tribe.conf)
    location ~ /\.(?!well-known).* {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Increase client max body size for file uploads (matching tribe.conf)
    client_max_body_size 128M;
    client_body_timeout 300s;
    client_header_timeout 300s;
    
    # Timeout settings (matching tribe.conf)
    proxy_read_timeout 60;
    fastcgi_read_timeout 60;
    
    # Uploads handling (from tribe.conf)
    location /uploads/ {
        include /etc/nginx/mime.types;
        try_files \$uri /uploads.php\$is_args\$args;
    }
    
    # API.php handling with extensionless PHP support (from tribe.conf)
    location /api.php {
        include /etc/nginx/mime.types;
        try_files \$uri \$uri.html \$uri/ @extensionless-php;
    }
    
    location @extensionless-php {
        rewrite ^(.*)$ $1.php last;
    }
    
    # Default route handling (from tribe.conf - more flexible than original)
    location / {
        include /etc/nginx/mime.types;
        try_files \$uri \$uri/ /index.php\$is_args\$args;
    }

    #all .php files to use php fpm
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php7.4-fpm.sock;
    }
    
    # Security: Deny access to .htaccess files (from tribe.conf)
    location ~ /\.ht {
        deny all;
    }
}
EOF
    else
        print_status "Using custom Nginx configuration"
    fi
}

# Function to start Nginx
start_nginx() {
    print_status "Starting Nginx..."
    
    # Setup nginx configuration
    setup_nginx_config
    
    # Test Nginx configuration
    if ! nginx -t; then
        print_error "Nginx configuration test failed!"
        return 1
    fi
    
    # Start Nginx in background
    nginx -g "daemon off;" &
    NGINX_PID=$!
    
    # Wait a moment and check if Nginx started successfully
    sleep 2
    if ! kill -0 $NGINX_PID 2>/dev/null; then
        print_error "Failed to start Nginx!"
        return 1
    fi
    
    print_status "Nginx started successfully (PID: $NGINX_PID)"
    return 0
}

# Function to create health check file
create_health_check() {
    print_status "Creating health check endpoint..."
    cat > /var/www/health.php << EOF
<?php
header('Content-Type: application/json');

\$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'application' => '$WEBSITE_NAME',
    'app_uid' => '$APP_UID',
    'tribe_slug' => '$TRIBE_SLUG',
    'services' => []
];

// Check database connection
try {
    \$db_host = '$DB_HOST';
    \$db_name = '$DB_NAME';
    \$db_user = '$DB_USER';
    \$db_pass = '$DB_PASS';
    
    if (\$db_name && \$db_user && \$db_pass) {
        \$pdo = new PDO("mysql:host=\$db_host;dbname=\$db_name", \$db_user, \$db_pass);
        \$health['services']['database'] = 'ok';
    } else {
        \$health['services']['database'] = 'config_missing';
    }
} catch (Exception \$e) {
    \$health['services']['database'] = 'error';
    \$health['status'] = 'degraded';
}

// Check PHP-FPM
\$health['services']['php_fpm'] = 'ok';

// Check file permissions
\$dirs = ['uploads', 'logs', 'cache', 'sessions'];
foreach (\$dirs as \$dir) {
    if (!is_writable("/var/www/\$dir")) {
        \$health['services']['filesystem'] = 'error';
        \$health['status'] = 'degraded';
        break;
    }
}
if (!isset(\$health['services']['filesystem'])) {
    \$health['services']['filesystem'] = 'ok';
}

http_response_code(\$health['status'] === 'ok' ? 200 : 503);
echo json_encode(\$health, JSON_PRETTY_PRINT);
EOF
    
    # Try to change ownership, but don't fail if it doesn't work
    chown www-data:www-data /var/www/health.php 2>/dev/null || print_warning "Could not change ownership of health.php"
}

# Function to create a simple test PHP file
create_test_files() {
    print_status "Creating test files..."
    
    # Create a simple PHP info file for testing
    cat > /var/www/phpinfo.php << 'EOF'
<?php
phpinfo();
EOF
    
    # Create a simple index.php if it doesn't exist
    if [[ ! -f "/var/www/index.php" ]]; then
        cat > /var/www/index.php << EOF
<?php
echo "<h1>$WEBSITE_NAME Application is Running!</h1>";
echo "<p>Application UID: $APP_UID</p>";
echo "<p>Tribe Slug: $TRIBE_SLUG</p>";
echo "<p>Web URL: $WEB_URL</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='/health.php'>Health Check</a></p>";
echo "<p><a href='/phpinfo.php'>PHP Info</a></p>";
echo "<p><a href='/phpmyadmin/'>phpMyAdmin</a></p>";
echo "<p><a href='/nginx-test'>Nginx Test</a></p>";
EOF
    fi
    
    # Set permissions
    chown www-data:www-data /var/www/*.php 2>/dev/null || true
}

# Function to monitor services
monitor_services() {
    print_status "Starting service monitoring..."
    
    while true; do
        sleep $HEALTH_CHECK_INTERVAL
        
        # Check Nginx
        if [[ -n "$NGINX_PID" ]] && ! kill -0 $NGINX_PID 2>/dev/null; then
            print_error "Nginx has stopped unexpectedly!"
            return 1
        fi
        
        # Check PHP-FPM
        if ! is_service_running "php-fpm: master process"; then
            print_warning "PHP-FPM has stopped, attempting restart..."
            if start_php_fpm; then
                print_status "PHP-FPM restarted successfully"
            else
                print_error "Failed to restart PHP-FPM!"
                return 1
            fi
        fi
        
        # Check PHP-FPM socket
        if [[ ! -S "$PHP_FPM_SOCKET" ]]; then
            print_warning "PHP-FPM socket missing, attempting restart..."
            if start_php_fpm; then
                print_status "PHP-FPM socket recreated successfully"
            else
                print_error "Failed to recreate PHP-FPM socket!"
                return 1
            fi
        fi
        
        print_debug "Service health check passed"
    done
}

# Function to handle shutdown
shutdown() {
    print_status "Received shutdown signal, stopping services..."
    
    # Stop Nginx
    if [[ -n "$NGINX_PID" ]]; then
        print_status "Stopping Nginx (PID: $NGINX_PID)..."
        kill -TERM $NGINX_PID 2>/dev/null || true
        wait $NGINX_PID 2>/dev/null || true
    fi
    
    # Stop PHP-FPM
    print_status "Stopping PHP-FPM..."
    pkill -f php-fpm || true
    
    print_status "Shutdown complete"
    exit 0
}

# Main execution
main() {
    print_status "Starting $WEBSITE_NAME application container..."
    
    # Load environment variables from .env file first
    load_env_file
    
    # Set up signal handlers
    trap shutdown SIGTERM SIGINT SIGQUIT
    
    # Validate environment
    if ! validate_environment; then
        print_error "Environment validation failed!"
        exit 1
    fi
    
    # Set up file permissions
    if ! setup_permissions; then
        print_error "Permission setup failed!"
        exit 1
    fi
    
    # Wait for database
    if ! wait_for_database; then
        print_error "Database connection failed!"
        exit 1
    fi
    
    # Create health check endpoint and test files
    create_health_check
    create_test_files
    
    # Start PHP-FPM
    if ! start_php_fpm; then
        print_error "PHP-FPM startup failed!"
        exit 1
    fi
    
    # Start Nginx
    if ! start_nginx; then
        print_error "Nginx startup failed!"
        exit 1
    fi
    
    print_status "✅ $WEBSITE_NAME application is ready!"
    print_status "Services running:"
    print_status "  - PHP-FPM: PID $PHP_FPM_PID"
    print_status "  - Nginx: PID $NGINX_PID"
    print_status "  - Application: $WEB_URL"
    print_status "  - Health check: $WEB_URL/health.php"
    print_status "  - PHP Info: $WEB_URL/phpinfo.php"
    print_status "  - Nginx Test: $WEB_URL/nginx-test"
    print_status "  - Local access: http://localhost:$TRIBE_PORT"
    
    # Start monitoring
    monitor_services
}

# Run main function
main "$@"