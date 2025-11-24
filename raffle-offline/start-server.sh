#!/bin/bash

# Offline Prize Drawing System - Quick Start Script

echo "🎰 Starting Offline Prize Drawing System..."
echo ""

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ Error: PHP is not installed"
    echo "Please install PHP 7.4 or higher"
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "✓ PHP version: $PHP_VERSION"

# Set file permissions
echo "✓ Setting file permissions..."
chmod 755 data/
chmod 666 data/*.json

# Check if data files exist
if [ ! -f "data/prizes.json" ]; then
    echo "❌ Error: data/prizes.json not found"
    exit 1
fi

if [ ! -f "data/special_prize_settings.json" ]; then
    echo "❌ Error: data/special_prize_settings.json not found"
    exit 1
fi

echo "✓ Data files found"
echo ""
echo "=========================================="
echo "  Server starting on http://localhost:8000"
echo "=========================================="
echo ""
echo "Main Page:  http://localhost:8000/"
echo "Admin Page: http://localhost:8000/admin/"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

# Start PHP built-in server
php -S localhost:8000
