# Installing Docker Application on Ubuntu 24.04

```
rm install.sh; curl -s https://raw.githubusercontent.com/cityowltypes/skillbot.def-dev.in/master/install/install.sh -o install.sh; chmod +x install.sh; bash install.sh;
```

## **Application Setup**

```bash
# Clone or create your project directory
cd /var/www
sudo git clone https://github.com/cityowltypes/skillbot.def-dev.in.git def-skillbot
cd def-skillbot
cp sample.env .env
```

Edit .env

## **Build and Run**

```bash
# Build and start the application
docker compose up --build -d

# Check status
docker compose ps

# View logs
docker compose logs -f tribe

# Check health
curl http://localhost:8080/health.php
```

## **Access Your Application**

- **Main App**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8080/phpmyadmin
- **Health Check**: http://localhost:8080/health.php

## **Useful Commands**

```bash
# Stop application
docker compose down

# Restart application
docker compose restart

# View logs
docker compose logs tribe
docker compose logs db

# Access container shell
docker compose exec tribe bash

# Update application
docker compose down
docker compose up --build -d

# Clean up (removes all data!)
docker compose down -v
docker system prune -a
```

## **Firewall Setup (if needed)**

```bash
# Allow the application port
sudo ufw allow 8080/tcp
sudo ufw reload
```

## **File Permissions Check**

```bash
# Ensure proper ownership
sudo chown -R $USER:$USER .
chmod +x scripts/docker-entrypoint.sh
```

That's it! Your application should be running at http://localhost:8080 (or your server's IP address).
