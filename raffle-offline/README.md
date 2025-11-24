# 🎰 Offline Prize Drawing System

An offline raffle/prize drawing system designed for exhibitions and events. This system works completely offline without requiring an internet connection, using JSON files for data storage and featuring a TV-optimized interface with SVG roulette wheel animation.

## ✨ Features

- **🔌 Fully Offline**: Works without internet connection
- **📺 TV-Optimized UI**: Large fonts and buttons optimized for TV/large screen displays
- **🎡 SVG Roulette Wheel**: Smooth spinning animation with probability-based drawing
- **🎯 Prize Management**: Full CRUD operations for managing prizes
- **⭐ Special Prize System**: Milestone-based special prize awards
- **📊 Probability Control**: Configure winning probabilities for each prize
- **📦 Stock Management**: Track prize inventory with automatic stock updates
- **🎨 Customizable Colors**: Each prize can have its own color on the wheel
- **💾 JSON-Based Storage**: No database required, all data stored in JSON files
- **🔒 File Locking**: Safe concurrent access with file locking mechanism

## 📋 Requirements

- **PHP 7.4 or higher**
- **Web Server** (Apache, Nginx, or PHP built-in server)
- **Modern Web Browser** (Chrome, Firefox, Safari, Edge)

## 🚀 Installation

### 1. Download and Extract

Extract the system files to your web server directory:

```bash
# Example for Apache
/var/www/html/raffle-offline/

# Example for local development
/path/to/your/project/raffle-offline/
```

### 2. Set File Permissions

The `data/` directory and JSON files must be writable by the web server:

```bash
cd raffle-offline
chmod 755 data/
chmod 666 data/*.json
```

For better security, you can use:

```bash
# Set ownership to web server user (e.g., www-data, apache, nginx)
chown -R www-data:www-data data/
chmod 755 data/
chmod 644 data/*.json
```

### 3. Start the Server

#### Option A: PHP Built-in Server (Development)

```bash
cd raffle-offline
php -S localhost:8000
```

Then open: `http://localhost:8000`

#### Option B: Apache/Nginx

Configure your web server to serve the `raffle-offline` directory.

Example Apache VirtualHost:

```apache
<VirtualHost *:80>
    ServerName raffle.local
    DocumentRoot /var/www/html/raffle-offline

    <Directory /var/www/html/raffle-offline>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 4. Access the System

- **Main Drawing Page**: `http://your-server/`
- **Admin Panel**: `http://your-server/admin/`

## 📁 File Structure

```
raffle-offline/
├── index.php                    # Main prize drawing page
├── admin/
│   ├── index.php               # Admin panel
│   └── admin.js                # Admin JavaScript
├── api/
│   ├── config.php              # Configuration and helper functions
│   ├── draw.php                # Drawing execution API
│   ├── get_prizes.php          # Get active prizes
│   ├── get_all_prizes.php      # Get all prizes (admin)
│   ├── update_prize.php        # Prize CRUD operations
│   ├── get_special_prize_settings.php
│   └── update_special_prize_settings.php
├── assets/
│   ├── css/
│   │   └── styles.css          # TV-optimized styles
│   └── js/
│       └── raffle.js           # Main raffle JavaScript
├── data/
│   ├── prizes.json             # Prize data storage
│   └── special_prize_settings.json  # Special prize configuration
└── README.md
```

## 🎮 Usage Guide

### Main Drawing Page

1. **View the Wheel**: The roulette wheel displays all active prizes with their respective colors and sizes based on probability
2. **Spin the Wheel**: Click the "SPIN THE WHEEL!" button to start the drawing
3. **View Results**: After spinning, the winning prize is displayed in a full-screen modal
4. **Spin Again**: Click "SPIN AGAIN" to reset and draw another prize

### Admin Panel

#### Prize Management

1. **Add New Prize**:
   - Click "Add New Prize" button
   - Fill in prize details:
     - Name: Prize description
     - Probability: Winning percentage (0-100)
     - Color: Color displayed on the wheel
     - Stock: Available quantity (-1 for unlimited)
     - Status: Active or Inactive
   - Click "Save Prize"

2. **Edit Prize**:
   - Click "Edit" button next to any prize
   - Modify the details
   - Click "Save Prize"

3. **Delete Prize**:
   - Click "Delete" button next to any prize
   - Confirm the deletion

4. **Probability Validation**:
   - The system automatically validates that active prizes total 100% probability
   - Invalid configurations will be highlighted in red

#### Special Prize Settings

Configure milestone-based special prizes:

1. Enable the "Enable Special Prize Feature" checkbox
2. Select the special prize from the dropdown
3. Set the milestone count (e.g., every 100 draws)
4. Set or reset the current count
5. Click "Save Settings"

When the drawing count reaches the milestone, the special prize is automatically awarded.

## ⚙️ Configuration

### JSON Data Files

#### `data/prizes.json`

```json
{
  "prizes": [
    {
      "id": 1,
      "name": "1st Prize - iPhone 15 Pro",
      "probability": 1.00,
      "color": "#EF4444",
      "stock": 2,
      "is_active": 1,
      "is_special": 0,
      "created_at": "2025-01-01 00:00:00"
    }
  ],
  "last_id": 1
}
```

**Fields**:
- `id`: Unique prize identifier
- `name`: Prize display name
- `probability`: Win probability (0-100)
- `color`: Hex color code for wheel segment
- `stock`: Available quantity (-1 = unlimited)
- `is_active`: 1 = active, 0 = inactive
- `is_special`: Reserved for future use
- `created_at`: Creation timestamp

#### `data/special_prize_settings.json`

```json
{
  "id": 1,
  "special_prize_id": 1,
  "milestone_count": 100,
  "current_count": 0,
  "is_active": 0,
  "created_at": "2025-01-01 00:00:00",
  "updated_at": "2025-01-01 00:00:00"
}
```

**Fields**:
- `special_prize_id`: ID of the prize to award at milestone
- `milestone_count`: Number of draws before special prize
- `current_count`: Current drawing count
- `is_active`: 1 = enabled, 0 = disabled

## 🎨 TV Display Optimization

The interface is optimized for TV screens with:

- **Large Fonts**: 6rem title, 3.5rem buttons
- **High Contrast**: Bold colors with shadows for visibility
- **Large Buttons**: Generous padding (40px 100px) for touch/remote
- **Responsive Design**: Adapts to different screen sizes
- **Simple Layout**: Clean, uncluttered interface
- **Smooth Animations**: Hardware-accelerated CSS animations

### Recommended TV Settings

- **Resolution**: 1920x1080 (Full HD) or higher
- **Display Mode**: Full screen (F11 in most browsers)
- **Zoom Level**: 100% (default)
- **Screen Mode**: PC/Game mode (for better responsiveness)

## 🔧 Troubleshooting

### Issue: "Failed to load prizes"

**Causes**:
- JSON file doesn't exist
- File permissions issue
- Invalid JSON syntax

**Solutions**:
```bash
# Check file exists
ls -la data/prizes.json

# Fix permissions
chmod 666 data/prizes.json

# Validate JSON
php -r "json_decode(file_get_contents('data/prizes.json'));"
```

### Issue: "Failed to save data"

**Causes**:
- Insufficient write permissions
- Disk full
- File locked

**Solutions**:
```bash
# Fix permissions
chmod 755 data/
chmod 666 data/*.json

# Check disk space
df -h

# Check file ownership
ls -la data/
```

### Issue: "Invalid probability configuration"

**Cause**: Total probability of active prizes ≠ 100%

**Solution**: Adjust prize probabilities in admin panel so active prizes total exactly 100%

### Issue: Wheel not spinning

**Causes**:
- JavaScript errors
- Browser compatibility
- No active prizes

**Solutions**:
- Open browser console (F12) to check for errors
- Ensure you're using a modern browser
- Verify at least one prize is active with stock

### Issue: Blank admin page

**Causes**:
- PHP errors
- File path issues

**Solutions**:
- Check PHP error logs
- Verify admin.js is loading (check Network tab in browser)
- Ensure correct file paths in admin/index.php

## 🔒 Security Considerations

### File Permissions

- `data/` directory: 755 (drwxr-xr-x)
- JSON files: 644 (rw-r--r--) or 666 (rw-rw-rw-) if needed
- PHP files: 644 (rw-r--r--)

### Backup Strategy

The system automatically creates `.backup` files before updating JSON files. Manual backups are recommended:

```bash
# Backup data directory
cp -r data/ data_backup_$(date +%Y%m%d)/

# Or create a tar archive
tar -czf raffle_backup_$(date +%Y%m%d).tar.gz data/
```

### XSS Prevention

The admin panel includes XSS prevention through HTML escaping of user input.

## 📊 Technical Details

### Drawing Algorithm

1. Generate random number 0.00 - 99.99
2. Calculate cumulative probability for each active prize
3. Select prize where random number falls within its probability range
4. Check stock availability (skip if stock = 0, unless stock = -1)
5. Update stock (if not unlimited)
6. Save to JSON file with file locking

### File Locking

The system uses PHP's `flock()` to prevent concurrent write conflicts:

```php
$fp = fopen($filepath, 'w');
if (flock($fp, LOCK_EX)) {
    fwrite($fp, $data);
    flock($fp, LOCK_UN);
}
fclose($fp);
```

## 🎯 Best Practices

1. **Regular Backups**: Backup `data/` directory daily
2. **Probability Check**: Always verify total probability = 100% before events
3. **Test Run**: Perform test draws before actual events
4. **Stock Management**: Monitor stock levels and restock as needed
5. **Browser Cache**: Clear cache if updates don't appear
6. **Offline Test**: Disconnect internet to verify offline functionality

## 📝 Changelog

### Version 1.0.0 (2025-01-24)

- Initial release
- JSON-based offline storage
- TV-optimized interface
- SVG roulette wheel animation
- Prize management system
- Special prize milestone feature
- Probability-based drawing
- Stock management
- File locking for concurrent access

## 📄 License

This project is provided as-is for exhibition and event use.

## 🤝 Support

For issues or questions:

1. Check this README's troubleshooting section
2. Verify file permissions and JSON validity
3. Check browser console for JavaScript errors
4. Review PHP error logs

## 🎉 Credits

Designed and developed for offline exhibition prize drawings with TV display optimization.
