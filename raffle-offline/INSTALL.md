# 🚀 Quick Installation Guide

## Method 1: Quick Start (Recommended for Testing)

### Linux/Mac

1. Open terminal in the `raffle-offline` directory
2. Run the start script:
   ```bash
   ./start-server.sh
   ```
3. Open your browser to `http://localhost:8000`

### Windows

1. Open command prompt in the `raffle-offline` directory
2. Run the start script:
   ```cmd
   start-server.bat
   ```
3. Open your browser to `http://localhost:8000`

## Method 2: Apache/Nginx (Production)

### Apache

1. Copy files to web root:
   ```bash
   sudo cp -r raffle-offline /var/www/html/
   ```

2. Set permissions:
   ```bash
   cd /var/www/html/raffle-offline
   sudo chown -R www-data:www-data data/
   sudo chmod 755 data/
   sudo chmod 666 data/*.json
   ```

3. Access via: `http://your-server/raffle-offline/`

### Nginx

1. Copy files to web root:
   ```bash
   sudo cp -r raffle-offline /usr/share/nginx/html/
   ```

2. Set permissions:
   ```bash
   cd /usr/share/nginx/html/raffle-offline
   sudo chown -R nginx:nginx data/
   sudo chmod 755 data/
   sudo chmod 666 data/*.json
   ```

3. Configure Nginx to process PHP files

4. Access via: `http://your-server/raffle-offline/`

## Method 3: Docker (Optional)

Create a `docker-compose.yml`:

```yaml
version: '3.8'
services:
  raffle:
    image: php:8.1-apache
    ports:
      - "8000:80"
    volumes:
      - ./raffle-offline:/var/www/html
    command: >
      bash -c "chmod 755 /var/www/html/data &&
               chmod 666 /var/www/html/data/*.json &&
               apache2-foreground"
```

Run:
```bash
docker-compose up
```

## ✅ Verify Installation

1. **Main Page**: Should display the roulette wheel
2. **Admin Page**: Should show prize list and management interface
3. **Test Draw**: Click "SPIN THE WHEEL!" to test drawing
4. **Check Data**: Verify `data/*.json` files are being updated

## 🔧 Troubleshooting

### Permission Errors

```bash
# Fix permissions
chmod 755 data/
chmod 666 data/*.json

# Or use web server user
sudo chown -R www-data:www-data data/
```

### PHP Not Found

```bash
# Install PHP
# Ubuntu/Debian
sudo apt-get install php php-cli php-json

# CentOS/RHEL
sudo yum install php php-cli php-json

# macOS
brew install php
```

### Port Already in Use

Change port in start script or use different port:
```bash
php -S localhost:8080
```

## 📱 Display on TV

1. Connect TV to computer via HDMI
2. Open browser in full-screen mode (F11)
3. Navigate to the raffle page
4. Keep the main drawing page displayed on TV
5. Use another device for admin panel

## 🎯 First-Time Setup

1. Access admin panel: `http://localhost:8000/admin/`
2. Review default prizes
3. Adjust probabilities to total 100%
4. Set stock quantities
5. (Optional) Configure special prize settings
6. Test a few drawings
7. Reset stock/counters before actual event

## 📞 Need Help?

Check the main [README.md](README.md) for detailed documentation.
