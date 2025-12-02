#!/bin/bash

#===============================================================================
# DentalFlow Deployment Script
# Optimized for AlmaLinux 9 + CyberPanel + OpenLiteSpeed
#
# Usage:
#   chmod +x scripts/deploy.sh
#   ./scripts/deploy.sh
#
# This script performs:
#   1. Git pull from repository
#   2. Install dependencies
#   3. Build Next.js application
#   4. Build Socket.io server
#   5. Run database migrations
#   6. Restart PM2 processes
#===============================================================================

set -e  # Exit on error

# Configuration
APP_DIR="/home/dentalflow"
LOG_DIR="$APP_DIR/logs"
BRANCH="${1:-main}"  # Default to main branch

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
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

# Check if running as root or with sudo
check_permissions() {
    if [ "$EUID" -ne 0 ]; then
        log_warning "Not running as root. Some operations may fail."
    fi
}

# Navigate to application directory
cd_app_dir() {
    if [ -d "$APP_DIR" ]; then
        cd "$APP_DIR"
        log_info "Changed to directory: $APP_DIR"
    else
        log_error "Application directory not found: $APP_DIR"
        exit 1
    fi
}

# Create logs directory if it doesn't exist
create_logs_dir() {
    if [ ! -d "$LOG_DIR" ]; then
        mkdir -p "$LOG_DIR"
        log_info "Created logs directory: $LOG_DIR"
    fi
}

# Pull latest changes from git
git_pull() {
    log_info "Pulling latest changes from $BRANCH branch..."
    git fetch origin
    git checkout "$BRANCH"
    git pull origin "$BRANCH"
    log_success "Git pull completed"
}

# Install dependencies
install_dependencies() {
    log_info "Installing dependencies..."
    npm ci --production=false
    log_success "Dependencies installed"
}

# Build Next.js application
build_nextjs() {
    log_info "Building Next.js application..."
    npm run build
    log_success "Next.js build completed"
}

# Build Socket.io server
build_socket() {
    log_info "Building Socket.io server..."
    npm run socket:build
    log_success "Socket.io server build completed"
}

# Run database migrations
run_migrations() {
    log_info "Running database migrations..."
    npx prisma migrate deploy
    log_success "Database migrations completed"
}

# Restart PM2 processes
restart_pm2() {
    log_info "Restarting PM2 processes..."

    # Check if PM2 is running
    if pm2 list | grep -q "dentalflow"; then
        pm2 restart all
    else
        pm2 start ecosystem.config.js --env production
    fi

    pm2 save
    log_success "PM2 processes restarted"
}

# Display status
show_status() {
    log_info "Current PM2 status:"
    pm2 status
    echo ""
    log_info "Application URLs:"
    echo "  - Web:    http://localhost:3000"
    echo "  - Socket: http://localhost:3001"
    echo "  - Health: http://localhost:3001/health"
}

# Main deployment function
main() {
    echo ""
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║                                                            ║"
    echo "║   DentalFlow Deployment Script                             ║"
    echo "║   AlmaLinux 9 + CyberPanel + OpenLiteSpeed                 ║"
    echo "║                                                            ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo ""

    check_permissions
    cd_app_dir
    create_logs_dir

    log_info "Starting deployment process..."
    echo ""

    git_pull
    echo ""

    install_dependencies
    echo ""

    build_nextjs
    echo ""

    build_socket
    echo ""

    run_migrations
    echo ""

    restart_pm2
    echo ""

    show_status
    echo ""

    log_success "Deployment completed successfully!"
    echo ""
}

# Run main function
main "$@"
