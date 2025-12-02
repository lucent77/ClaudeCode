#!/bin/bash

#===============================================================================
# DentalFlow Server Setup Script
# Initial setup for AlmaLinux 9 + CyberPanel environment
#
# Prerequisites:
#   - AlmaLinux 9 with CyberPanel installed
#   - Root access
#
# Usage:
#   chmod +x scripts/setup-server.sh
#   sudo ./scripts/setup-server.sh
#
# This script performs:
#   1. Install Node.js 20.x
#   2. Install PM2
#   3. Create application directory
#   4. Configure firewall (if needed)
#   5. Display CyberPanel reverse proxy instructions
#===============================================================================

set -e  # Exit on error

# Configuration
APP_DIR="/home/dentalflow"
NODE_VERSION="20"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_step() {
    echo -e "${CYAN}[STEP]${NC} $1"
}

# Check if running as root
check_root() {
    if [ "$EUID" -ne 0 ]; then
        log_error "This script must be run as root"
        exit 1
    fi
}

# Check if AlmaLinux
check_os() {
    if [ -f /etc/almalinux-release ]; then
        log_info "Detected AlmaLinux"
    elif [ -f /etc/redhat-release ]; then
        log_warning "Detected RHEL-based system (not AlmaLinux)"
    else
        log_error "This script is designed for AlmaLinux 9"
        exit 1
    fi
}

# Install Node.js 20.x
install_nodejs() {
    log_step "Installing Node.js ${NODE_VERSION}.x..."

    # Check if Node.js is already installed
    if command -v node &> /dev/null; then
        CURRENT_VERSION=$(node -v | cut -d'v' -f2 | cut -d'.' -f1)
        if [ "$CURRENT_VERSION" -ge "$NODE_VERSION" ]; then
            log_info "Node.js $(node -v) is already installed"
            return
        fi
    fi

    # Install Node.js using NodeSource
    curl -fsSL https://rpm.nodesource.com/setup_${NODE_VERSION}.x | bash -
    dnf install -y nodejs

    log_success "Node.js $(node -v) installed"
    log_info "npm $(npm -v) installed"
}

# Install PM2
install_pm2() {
    log_step "Installing PM2..."

    if command -v pm2 &> /dev/null; then
        log_info "PM2 is already installed"
    else
        npm install -g pm2@5
        log_success "PM2 installed"
    fi

    # Setup PM2 to start on system boot
    pm2 startup systemd -u root --hp /root
    log_info "PM2 startup configured"
}

# Create application directory
create_app_directory() {
    log_step "Creating application directory..."

    if [ -d "$APP_DIR" ]; then
        log_info "Directory already exists: $APP_DIR"
    else
        mkdir -p "$APP_DIR"
        mkdir -p "$APP_DIR/logs"
        log_success "Created directory: $APP_DIR"
    fi
}

# Configure firewall (optional, CyberPanel handles this)
configure_firewall() {
    log_step "Checking firewall configuration..."

    if command -v firewall-cmd &> /dev/null; then
        # Note: CyberPanel typically manages firewall rules
        log_info "Firewall is managed by CyberPanel"
        log_info "Ensure ports 80, 443 are open for web traffic"
        log_info "Ports 3000, 3001 are used internally (no external access needed)"
    else
        log_warning "firewall-cmd not found"
    fi
}

# Display CyberPanel reverse proxy instructions
display_proxy_instructions() {
    echo ""
    echo "╔════════════════════════════════════════════════════════════════════════╗"
    echo "║                                                                        ║"
    echo "║   CyberPanel Reverse Proxy Configuration                               ║"
    echo "║                                                                        ║"
    echo "╚════════════════════════════════════════════════════════════════════════╝"
    echo ""
    echo -e "${CYAN}Follow these steps in CyberPanel:${NC}"
    echo ""
    echo "1. Log into CyberPanel (https://your-server-ip:8090)"
    echo ""
    echo "2. Create a website:"
    echo "   - Websites → Create Website"
    echo "   - Enter your domain name"
    echo ""
    echo "3. Issue SSL certificate:"
    echo "   - Websites → List Websites → Your Domain → SSL → Issue SSL"
    echo ""
    echo "4. Configure OpenLiteSpeed Reverse Proxy:"
    echo "   - SSH into server or use CyberPanel terminal"
    echo "   - Edit: /usr/local/lsws/conf/vhosts/your-domain/vhost.conf"
    echo ""
    echo "   Add the following configuration:"
    echo ""
    echo "   ${YELLOW}# Next.js Application Proxy${NC}"
    echo "   context / {"
    echo "     type                    proxy"
    echo "     handler                 127.0.0.1:3000"
    echo "     addDefaultCharset       off"
    echo "   }"
    echo ""
    echo "   ${YELLOW}# Socket.io WebSocket Proxy${NC}"
    echo "   context /socket.io {"
    echo "     type                    proxy"
    echo "     handler                 127.0.0.1:3001"
    echo "     addDefaultCharset       off"
    echo "     extraHeaders            Upgrade \$http_upgrade"
    echo "     extraHeaders            Connection \"Upgrade\""
    echo "   }"
    echo ""
    echo "5. Restart OpenLiteSpeed:"
    echo "   ${GREEN}systemctl restart lsws${NC}"
    echo ""
    echo "6. Alternative: Use CyberPanel Web Terminal:"
    echo "   - Websites → List Websites → Your Domain → Rewrite Rules"
    echo "   - Add proxy rules through the GUI"
    echo ""
    echo "═══════════════════════════════════════════════════════════════════════════"
}

# Display next steps
display_next_steps() {
    echo ""
    echo "╔════════════════════════════════════════════════════════════════════════╗"
    echo "║                                                                        ║"
    echo "║   Next Steps                                                           ║"
    echo "║                                                                        ║"
    echo "╚════════════════════════════════════════════════════════════════════════╝"
    echo ""
    echo "1. Clone your repository:"
    echo "   ${GREEN}cd $APP_DIR${NC}"
    echo "   ${GREEN}git clone https://github.com/your-repo/dentalflow.git .${NC}"
    echo ""
    echo "2. Configure environment variables:"
    echo "   ${GREEN}cp .env.example .env${NC}"
    echo "   ${GREEN}nano .env${NC}"
    echo ""
    echo "3. Create MySQL database in CyberPanel:"
    echo "   - Databases → Create Database"
    echo "   - Note the database name, username, and password"
    echo "   - Update DATABASE_URL in .env"
    echo ""
    echo "4. Run deployment script:"
    echo "   ${GREEN}chmod +x scripts/deploy.sh${NC}"
    echo "   ${GREEN}./scripts/deploy.sh${NC}"
    echo ""
    echo "5. Seed the database (first time only):"
    echo "   ${GREEN}npm run db:seed${NC}"
    echo ""
    echo "═══════════════════════════════════════════════════════════════════════════"
}

# Main function
main() {
    echo ""
    echo "╔════════════════════════════════════════════════════════════════════════╗"
    echo "║                                                                        ║"
    echo "║   DentalFlow Server Setup                                              ║"
    echo "║   AlmaLinux 9 + CyberPanel + OpenLiteSpeed                             ║"
    echo "║                                                                        ║"
    echo "╚════════════════════════════════════════════════════════════════════════╝"
    echo ""

    check_root
    check_os

    echo ""
    log_info "Starting server setup..."
    echo ""

    install_nodejs
    echo ""

    install_pm2
    echo ""

    create_app_directory
    echo ""

    configure_firewall
    echo ""

    log_success "Server setup completed!"

    display_proxy_instructions
    display_next_steps
}

# Run main function
main "$@"
