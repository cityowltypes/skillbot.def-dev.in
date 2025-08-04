# Installing Application on Ubuntu 24.04

1. Copy 
```
rm server.sh; curl -s https://raw.githubusercontent.com/cityowltypes/skillbot.def-dev.in/master/install/server.sh -o server.sh; chmod +x server.sh; bash server.sh;
```

2. Replace the \<URL\> in the following code and save this file to /etc/nginx/sites-available/skillbot.def-dev.in
```
server {
    server_name <URL>;
    client_max_body_size 128M;

    access_log  /var/www/html/skillbot.def-dev.in/logs/access.log;
    error_log  /var/www/html/skillbot.def-dev.in/logs/error.log;

    root /var/www/html/skillbot.def-dev.in;

    index index.html index.htm index.php;

    #disable .env and other hidden files, except .well-known
    location ~ /\.(?!well-known).* {
        deny all;
        access_log off;
        log_not_found off;
    }

    #uploads - if the file exists, use it, otherwise pass it on to uploads.php
    location /uploads/ {
        include /etc/nginx/mime.types;
        try_files $uri /uploads.php$is_args$args;
    }

    location /api.php {
        include /etc/nginx/mime.types;
        try_files $uri $uri.html $uri/ @extensionless-php;
    }

    location @extensionless-php {
        rewrite ^(.*)$ $1.php last;
    }

    #for php
    #if the file exists, use it, otherwise pass it on to index.php
    location / {
        include /etc/nginx/mime.types;
        try_files $uri $uri/ /index.php$is_args$args;
    }

    #all .php files to use php fpm
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
     }

    #deny access to .htaccess file
    location ~ /\.ht {
        deny all;
    }
}
```

3. Prepare nginx
```
sudo ln -s /etc/nginx/sites-available/skillbot.def-dev.in /etc/nginx/sites-enabled/skillbot.def-dev.in;
```

4. Replace the \<URL\> in the following code and install SSL certbot
```
sudo certbot --agree-tos --no-eff-email --email azeem@defindia.org --nginx -d <URL>;
```