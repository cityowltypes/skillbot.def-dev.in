apt update;
apt upgrade -y;
apt-get install lsb-release ca-certificates apt-transport-https software-properties-common -y;
ufw allow OpenSSH;
ufw allow Postfix;
ufw allow 80;
ufw allow 8080;
ufw allow 3000;
ufw allow 3306;
ufw allow 443;
ufw allow 587;
echo "y" | ufw enable;
yes | add-apt-repository ppa:ondrej/php;
apt-get install -y git;
apt-get install -y mysql-server;
apt-get install -y php7.4;
apt-get install -y php7.4-cli;
apt-get install -y php7.4-fpm;
apt-get install -y php7.4-mysql;
apt-get install -y php7.4-curl;
apt-get install -y php7.4-mbstring;
apt-get install -y php7.4-mysql;
apt-get install -y php7.4-curl;
apt-get install -y php7.4-gd;
apt-get install -y php7.4-zip;
apt-get install -y php7.4-xml;
apt-get install -y net-tools;
apt-get install -y zip;
apt-get install -y unzip;
apt-get install -y p7zip-full;
apt-get install -y nginx;
apt-get install -y build-essential;
apt-get install -y curl;
apt-get install -y s3cmd;
apt-get install -y htop;
apt-get install -y poppler-utils;
apt-get install -y python3-pip;
apt-get install -y imagemagick;
apt-get install -y ffmpeg;
apt-get install -y npm;
systemctl disable --now apache2;
systemctl reload nginx;
echo "ALTER USER 'mysql_root_user'@'localhost' IDENTIFIED WITH mysql_native_password BY 'mysql_root_pass'; FLUSH PRIVILEGES; exit;" | mysql;
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php;
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer;
snap install --classic certbot;
apt-get install -y python3-certbot-nginx;
echo 'post_max_size = 1024M' | tee -a /etc/php/7.4/fpm/php.ini;
echo 'upload_max_filesize = 1024M' | tee -a /etc/php/7.4/fpm/php.ini;
echo 'memory_limit = 2048M' | tee -a /etc/php/7.4/fpm/php.ini;
echo 'expire_logs_days = 3' | tee -a /etc/mysql/mysql.conf.d/mysqld.cnf;
/bin/dd if=/dev/zero of=/var/swap.1 bs=1M count=1024;
/sbin/mkswap /var/swap.1;
/sbin/swapon /var/swap.1;
service php7.4-fpm restart;
apachectl stop;
apt-get update;
cd /var/www/html
sudo git clone https://github.com/cityowltypes/skillbot.def-dev.in.git skillbot.def-dev.in
cd skillbot.def-dev.in
sudo chown www-data: uploads
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'usrpass123'; FLUSH PRIVILEGES;"
sudo mysql -u root -pusrpass123 -e "CREATE DATABASE skillbot;"
wget https://tribe.junction.express/uploads/2025/08-August/05-Tue/data-linked_689108ff85cb0.zip
unzip data-linked_689108ff85cb0.zip
sudo rm data-linked_689108ff85cb0.zip
sudo mysql -u root -pusrpass123 skillbot < data-linked.sql
wget https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.tar.gz
tar -xzf phpMyAdmin-latest-all-languages.tar.gz
mv phpMyAdmin-*-all-languages phpmyadmin
sudo rm phpMyAdmin-latest-all-languages.tar.gz
yes | composer update
npm i
sudo cp sample.env .env