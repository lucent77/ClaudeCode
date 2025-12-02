#!/bin/bash

# Realtime Chat - Installation Script for CyberPanel/AlmaLinux9
# Run as root or with sudo

set -e

echo "============================================"
echo "  Realtime Chat - Installation Script"
echo "  For CyberPanel on AlmaLinux 9"
echo "============================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Please run as root or with sudo${NC}"
    exit 1
fi

# Variables
APP_DIR="/home/realtime-chat"
APP_USER="chatapp"
NODE_VERSION="20"

echo -e "${YELLOW}Step 1: Installing Node.js ${NODE_VERSION}...${NC}"

# Check if Node.js is already installed
if command -v node &> /dev/null; then
    NODE_CURRENT=$(node --version)
    echo -e "${GREEN}Node.js is already installed: ${NODE_CURRENT}${NC}"
else
    # Install Node.js using NodeSource
    curl -fsSL https://rpm.nodesource.com/setup_${NODE_VERSION}.x | bash -
    dnf install -y nodejs
    echo -e "${GREEN}Node.js installed: $(node --version)${NC}"
fi

echo -e "${YELLOW}Step 2: Installing build tools...${NC}"
dnf groupinstall -y "Development Tools"
dnf install -y python3 gcc-c++

echo -e "${YELLOW}Step 3: Installing PM2...${NC}"
npm install -g pm2

echo -e "${YELLOW}Step 4: Creating application user...${NC}"
if id "$APP_USER" &>/dev/null; then
    echo -e "${GREEN}User ${APP_USER} already exists${NC}"
else
    useradd -m -s /bin/bash $APP_USER
    echo -e "${GREEN}User ${APP_USER} created${NC}"
fi

echo -e "${YELLOW}Step 5: Setting up application directory...${NC}"
mkdir -p $APP_DIR
mkdir -p $APP_DIR/logs
mkdir -p $APP_DIR/data

# Copy application files (assuming script is run from app directory)
if [ -f "./package.json" ]; then
    cp -r ./* $APP_DIR/
    echo -e "${GREEN}Application files copied${NC}"
else
    echo -e "${YELLOW}Note: Copy application files to $APP_DIR manually${NC}"
fi

chown -R $APP_USER:$APP_USER $APP_DIR

echo -e "${YELLOW}Step 6: Installing dependencies...${NC}"
cd $APP_DIR
sudo -u $APP_USER npm install --production

echo -e "${YELLOW}Step 7: Building CSS...${NC}"
sudo -u $APP_USER npm run build:css

echo -e "${YELLOW}Step 8: Setting up PM2...${NC}"
# Start application
sudo -u $APP_USER pm2 start ecosystem.config.js --env production

# Save PM2 process list
sudo -u $APP_USER pm2 save

# Setup PM2 startup script
pm2 startup systemd -u $APP_USER --hp /home/$APP_USER
systemctl enable pm2-$APP_USER

echo -e "${YELLOW}Step 9: Configuring firewall...${NC}"
# Open port 3000 if firewalld is running
if systemctl is-active --quiet firewalld; then
    firewall-cmd --permanent --add-port=3000/tcp
    firewall-cmd --reload
    echo -e "${GREEN}Firewall configured${NC}"
else
    echo -e "${YELLOW}Firewalld is not running, skipping firewall configuration${NC}"
fi

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  Installation Complete!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo "Application is running at: http://localhost:3000"
echo ""
echo "Next steps:"
echo "1. Configure your reverse proxy (OpenLiteSpeed/nginx)"
echo "2. Set up SSL certificate"
echo "3. Update ALLOWED_ORIGINS in .env for your domain"
echo ""
echo "Useful PM2 commands:"
echo "  pm2 status          - Check application status"
echo "  pm2 logs            - View application logs"
echo "  pm2 restart all     - Restart application"
echo "  pm2 monit           - Monitor application"
echo ""
