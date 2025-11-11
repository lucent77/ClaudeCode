# Installation Guide - Production Management System

## Quick Start for Hostinger

### Step 1: Upload Files
1. Download/export all project files
2. Log in to Hostinger File Manager
3. Upload all files to `public_html/` or your domain directory
4. Ensure the following structure is maintained:
   - `api/` directory with all API files
   - `config/` directory with database.php
   - `includes/`, `logs/`, `uploads/`, `samples/` directories

### Step 2: Database Setup
1. Go to Hostinger Control Panel → MySQL Databases
2. Click "Create Database"
3. Database name: `production_db` (or your choice)
4. Create a database user with a strong password
5. Assign the user to the database with ALL PRIVILEGES
6. Note down:
   - Database name
   - Database username
   - Database password
   - Database host (usually `localhost`)

### Step 3: Import Database
1. Go to Hostinger Control Panel → phpMyAdmin
2. Select your database from the left sidebar
3. Click "Import" tab
4. Choose file: `database.sql`
5. Click "Go" to execute
6. Verify all 7 tables are created:
   - users
   - equipment
   - tools
   - equipment_tool_settings
   - production_records
   - tool_usage_history
   - tool_changes

### Step 4: Configure Database Connection
1. Open `config/database.php` in File Manager or FTP
2. Update these lines:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'production_db');     // Your database name
   define('DB_USER', 'your_db_user');      // Your database username
   define('DB_PASS', 'your_db_password');  // Your database password
   ```
3. Save the file

### Step 5: Set Permissions
Using File Manager or FTP:
1. Set `logs/` directory to 755 or 775 (writable)
2. Set `uploads/` directory to 755 or 775 (writable)

In File Manager:
- Right-click folder → Permissions → Set to 755

### Step 6: Test Installation
1. Visit your website: `https://yourdomain.com`
2. You should see a redirect to `login.php`
3. Try logging in with default credentials:
   - **Admin**: username `admin`, password `admin123`
   - **Operator**: username `operator`, password `admin123`

### Step 7: First Login Steps
1. Log in as admin
2. **IMMEDIATELY change the password**:
   - Note: Password change feature should be added or change directly in database
3. Go to Dashboard - you should see:
   - 8 equipment (MP1-MP3, HW1-HW5)
   - 0 tools (you'll add these next)
   - 0 production records

### Step 8: Import Sample Data (Optional)
1. Go to "Upload Data"
2. Upload Tools CSV:
   - Use `samples/tools_sample.csv` as reference
   - Or create your own following the format
3. Upload Production CSV:
   - Use `samples/production_sample.csv` as reference

## Verification Checklist

- [ ] Database created and accessible
- [ ] Database schema imported (7 tables exist)
- [ ] config/database.php configured correctly
- [ ] Can access login page
- [ ] Can log in with default credentials
- [ ] Dashboard displays correctly
- [ ] 8 equipment visible in Equipment page
- [ ] logs/ directory is writable
- [ ] uploads/ directory is writable

## Common Issues

### "Database connection failed"
- Check `config/database.php` credentials
- Verify database exists in phpMyAdmin
- Ensure database user has proper permissions
- Check if DB_HOST is correct (usually `localhost`)

### "Permission denied" errors
- Set logs/ and uploads/ to 755 or 775
- Check if PHP has write access

### Login not working
- Clear browser cookies
- Check if users table has data:
  ```sql
  SELECT * FROM users;
  ```
- Verify password hashing is working

### CSS not loading
- Check if Tailwind CSS CDN is accessible
- Verify .htaccess is working
- Check browser console for errors

### File upload not working
- Check upload directory permissions
- Verify PHP upload settings:
  - upload_max_filesize = 10M
  - post_max_size = 10M
- Check .htaccess in uploads/

## Post-Installation

### Security
1. Change all default passwords
2. Enable HTTPS (SSL) via Hostinger
3. Set DEBUG_MODE to false in config/database.php
4. Remove or protect samples/ directory

### Customization
1. Add your company logo (edit includes/header.php)
2. Customize color scheme in Tailwind CSS
3. Add more equipment if needed (via database)
4. Configure email notifications (future enhancement)

### Regular Maintenance
1. Backup database weekly
2. Monitor logs/ directory size
3. Clear old upload files
4. Review tool stock levels
5. Update production data daily

## Support Resources

- README.md - Full system documentation
- database.sql - Database schema reference
- samples/ - Sample CSV files for import

## Hostinger-Specific Notes

### PHP Version
- Ensure PHP 7.4+ is selected
- Go to: Hostinger → Advanced → PHP Configuration → Select PHP 7.4 or higher

### mod_rewrite
- Usually enabled by default on Hostinger
- If .htaccess not working, contact Hostinger support

### Database Host
- Almost always `localhost` for Hostinger
- If unsure, check Hostinger database panel

### File Manager Tips
- Use "Edit" to modify config files
- Use "Upload" for CSV files
- Use "Permissions" to set directory access
- Use "Extract" if uploading ZIP files

## Next Steps

After successful installation:
1. Read the README.md for full feature documentation
2. Explore the Dashboard
3. Add your tools via Upload or manual entry
4. Configure tool settings for each equipment
5. Start entering daily production data
6. Monitor tool usage and replacement alerts

## Getting Help

If you encounter issues:
1. Check the troubleshooting section in README.md
2. Review Hostinger error logs
3. Check application logs in logs/ directory
4. Verify all installation steps were completed
5. Contact your system administrator

---

**Congratulations!** Your Production Management System is now ready to use.
