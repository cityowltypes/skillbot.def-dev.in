#!/bin/bash

# Exit on any error and enable debug mode if needed
set -e
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
    local db_host="${DB_HOST:-db}"
    local db_user="${DB_USER:-root}"
    local db_pass="${DB_PASS}"
    
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
        if [[ ! -d "./$dir" ]]; then
            print_debug "Creating directory: $dir"
            mkdir -p "./$dir"
        fi
        chown -R www-data:www-data "./$dir"
        chmod -R 755 "./$dir"
    done
    
    # Set proper permissions for configuration files
    if [[ -f "./.env" ]]; then
        chmod 600 "./.env"
        print_debug "Set .env file permissions"
    fi
}

# Function to validate environment
validate_environment() {
    print_status "Validating environment..."
    
    # Check required environment variables
    local required_vars=("DB_HOST" "DB_USER" "DB_PASS" "DB_NAME")
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
    
    # Check if .env file exists
    if [[ ! -f "./.env" ]]; then
        print_warning ".env file not found. Application may not work correctly."
    fi
    
    return 0
}

# Function to start PHP-FPM
start_php_fpm() {
    print_status "Starting PHP-FPM..."
    
    # Test PHP-FPM configuration
    if ! php-fpm7.4 -t; then
        print_error "PHP-FPM configuration test failed!"
        return 1
    fi
    
    # Start PHP-FPM
    service php7.4-fpm start
    
    # Wait for socket to be created
    local counter=0
    while [[ ! -S "$PHP_FPM_SOCKET" && $counter -lt 30 ]]; do
        sleep 1
        counter=$((counter + 1))
    done
    
    if [[ ! -S "$PHP_FPM_SOCKET" ]]; then
        print_error "PHP-FPM socket not created!"
        return 1
    fi
    
    # Get PHP-FPM PID
    PHP_FPM_PID=$(pgrep -f "php-fpm: master process" || echo "")
    
    if [[ -z "$PHP_FPM_PID" ]]; then
        print_error "Failed to get PHP-FPM PID!"
        return 1
    fi
    
    print_status "PHP-FPM started successfully (PID: $PHP_FPM_PID)"
    return 0
}

# Function to setup nginx configuration
setup_nginx_config() {
    if [[ ! -f "/etc/nginx/conf.d/default.conf" ]]; then
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
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Increase client max body size for file uploads
    client_max_body_size 1024M;
    client_body_timeout 300s;
    client_header_timeout 300s;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    # Health check endpoint
    location /health.php {
        access_log off;
        try_files $uri =404;
        fastcgi_pass unix:/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

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
        fastcgi_connect_timeout 300;
    }

    # Handle static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
        access_log off;
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

    # Security: Deny access to sensitive files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ /(composer\.(json|lock)|package\.(json|lock)|\.env|\.git)$ {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Error and access logs
    error_log /var/www/logs/nginx_error.log warn;
    access_log /var/www/logs/nginx_access.log;
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
    cat > /var/www/health.php << 'EOF'
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'services' => []
];

// Check database connection
try {
    $db_host = $_ENV['DB_HOST'] ?? 'db';
    $db_name = $_ENV['DB_NAME'] ?? '';
    $db_user = $_ENV['DB_USER'] ?? '';
    $db_pass = $_ENV['DB_PASS'] ?? '';
    
    if ($db_name && $db_user && $db_pass) {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $health['services']['database'] = 'ok';
    } else {
        $health['services']['database'] = 'config_missing';
    }
} catch (Exception $e) {
    $health['services']['database'] = 'error';
    $health['status'] = 'degraded';
}

// Check PHP-FPM
$health['services']['php_fpm'] = 'ok';

// Check file permissions
$dirs = ['uploads', 'logs', 'cache', 'sessions'];
foreach ($dirs as $dir) {
    if (!is_writable("/var/www/$dir")) {
        $health['services']['filesystem'] = 'error';
        $health['status'] = 'degraded';
        break;
    }
}
if (!isset($health['services']['filesystem'])) {
    $health['services']['filesystem'] = 'ok';
}

http_response_code($health['status'] === 'ok' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);
EOF
    chown www-data:www-data /var/www/health.php
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
    service php7.4-fpm stop || true
    
    print_status "Shutdown complete"
    exit 0
}

# Main execution
main() {
    print_status "Starting Tribe application container..."
    
    # Set up signal handlers
    trap shutdown SIGTERM SIGINT SIGQUIT
    
    # Validate environment
    if ! validate_environment; then
        exit 1
    fi
    
    # Set up file permissions
    setup_permissions
    
    # Wait for database
    if ! wait_for_database; then
        exit 1
    fi
    
    # Create health check endpoint
    create_health_check
    
    # Start PHP-FPM
    if ! start_php_fpm; then
        exit 1
    fi
    
    # Start Nginx
    if ! start_nginx; then
        exit 1
    fi
    
    print_status "✅ Tribe application is ready!"
    print_status "Services running:"
    print_status "  - PHP-FPM: PID $PHP_FPM_PID"
    print_status "  - Nginx: PID $NGINX_PID"
    print_status "  - Application: http://localhost:${TRIBE_PORT:-8080}"
    print_status "  - Health check: http://localhost:${TRIBE_PORT:-8080}/health.php"
    
    # Start monitoring
    monitor_services
}

# Run main function
main "$@"