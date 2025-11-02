# CREODENT Work Manager - Quick Start Guide

## 5-Minute Setup

### 1. Upload Files (2 minutes)

Upload all files to your Hostinger account via FTP or File Manager:

```
/public_html/
├── app/
├── config/
├── cron/
├── database/
├── public/
├── storage/
├── vendor/
├── views/
├── composer.json
├── install.php
└── README.md
```

### 2. Run Installer (2 minutes)

1. Open browser: `http://yourdomain.com/install.php`
2. Click "Start Installation"
3. Wait for setup to complete
4. Note the admin credentials
5. Click "Go to Login"

### 3. First Login (1 minute)

**Default credentials:**
```
Username: admin
Password: Admin@123
```

**Immediately change password:**
1. Click on profile icon (top right)
2. Go to Settings → Change Password
3. Enter new strong password

## Essential Configuration

### Configure Evolution Portal

Edit `config/config.php`:

```php
'evolution' => [
    'base_url' => 'https://your-evolution-portal.com',
    'username' => 'your_username',
    'password' => 'your_password',
],
```

### Test Connection

1. Go to Import page
2. Click "Test Connection"
3. Should see "Successfully connected"

### First Import

1. On Import page
2. Select date range (e.g., last 7 days)
3. Click "Import Now"
4. Wait for completion
5. Check Cases page for imported data

## Daily Operations

### For Workers

**Check your assigned cases:**
1. Login → Dashboard
2. See "My Assigned Cases"
3. Click case to view details
4. Update status as you work
5. Add notes if needed

**Update case status:**
1. Open case
2. Find your item
3. Change status: Pending → Working → Done
4. System auto-saves

### For Managers

**Assign cases to workers:**
1. Go to Cases
2. Open case
3. Click "Assign" button
4. Select worker
5. Click "Save Assignment"

**Monitor workload:**
1. Dashboard shows department statistics
2. "Overdue Cases" section highlights delays
3. Use filters to find specific cases

### For Admins

**Add new users:**
1. Admin → Users
2. Click "+ New User"
3. Fill in details:
   - Username
   - Password
   - Name
   - Department
   - Role
4. Click "Create User"

**Import cases:**
- Automatic: Runs every hour via cron
- Manual: Import page → Import Now

## Common Tasks

### Search for a Case

**Method 1: Quick Search**
- Use search box on Cases page
- Type case number, lab name, or patient name
- Results filter automatically

**Method 2: Advanced Filters**
- Use filter dropdowns:
  - Status
  - Location
  - Date range
- Combine multiple filters

### View Case History

1. Open any case
2. Scroll to "Audit Log" section
3. See complete history:
   - Who changed what
   - When
   - Before/after values

### Handle Concurrent Edit Conflicts

If you see: "Concurrent modification detected"

**What it means:**
Someone else edited while you were viewing

**What to do:**
1. Click "Refresh" or reload page
2. Re-apply your changes
3. Save again

**Tip:** Save frequently to avoid conflicts!

### Export Data

**Via Database:**
```sql
-- Export cases to CSV
SELECT * FROM cases INTO OUTFILE '/tmp/cases.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

**Via API:**
```bash
# Get all cases (requires API token)
curl -H "X-API-TOKEN: your-token" \
     https://yourdomain.com/api/cases
```

## Troubleshooting Quick Fixes

### Can't Login

**Check 1: Correct credentials?**
- Username: `admin`
- Password: default is `Admin@123`
- Try password reset (contact super admin)

**Check 2: Account locked?**
- After 5 failed attempts, account locks for 15 minutes
- Wait or contact admin to unlock

**Check 3: Browser issues?**
- Clear cookies and cache
- Try different browser
- Check if JavaScript is enabled

### Import Not Working

**Check 1: Evolution credentials**
```php
// In config/config.php
'evolution' => [
    'base_url' => 'https://correct-url.com',  // ← Check this
    'username' => 'correct_username',         // ← Check this
    'password' => 'correct_password',         // ← Check this
],
```

**Check 2: Test connection**
- Import page → "Test Connection" button
- Should return success message
- If fails, check credentials and URL

**Check 3: Check logs**
```bash
# View recent import logs
tail -f storage/logs/import_$(date +%Y-%m-%d).log
```

### Page Loads Slowly

**Quick fixes:**
1. Clear browser cache
2. Check internet connection
3. Refresh page (F5)

**Admin fixes:**
1. Optimize database: Run OPTIMIZE TABLE
2. Check server resources in Hostinger cPanel
3. Enable OPcache in PHP settings

### Case Not Updating

**Check 1: Version conflict?**
- Refresh page to get latest version
- Re-apply changes

**Check 2: Permissions?**
- Workers can only update their assigned cases
- Managers can update department cases
- Admins can update all cases

**Check 3: Required fields?**
- Some fields are mandatory
- Check for error messages in red

## Pro Tips

### Keyboard Shortcuts

- `Ctrl/Cmd + F`: Quick search on page
- `Esc`: Close modal dialogs
- `Tab`: Navigate between form fields

### Browser Bookmarks

Save these as bookmarks for quick access:
- `http://yourdomain.com/dashboard` - Dashboard
- `http://yourdomain.com/cases` - All Cases
- `http://yourdomain.com/import` - Import Page

### Mobile Access

The system works on mobile browsers:
- Responsive design adapts to screen size
- Touch-friendly buttons
- Swipe to navigate tables

### Bulk Operations

**Assign multiple cases:**
1. Select cases with checkboxes (if implemented)
2. Click "Bulk Assign"
3. Choose worker
4. Apply to all selected

**Filter and export:**
1. Apply filters (status, date, etc.)
2. Use browser print → Save as PDF
3. Or export via API endpoint

### Custom Views

**Save filter combinations:**
1. Apply your common filters
2. Bookmark the resulting URL
3. Includes all filter parameters

Example:
```
/cases?status=in_progress&location=HV&due_date_from=2025-11-01
```

## Getting Help

### In-App Help

- Hover over (?) icons for field explanations
- Error messages explain what's wrong
- Toast notifications show operation results

### Documentation

- **README.md**: Complete system documentation
- **FIELD_MAPPINGS.md**: Data structure details
- **This file**: Quick tasks and troubleshooting

### Logs

Check these when debugging:
```
storage/logs/app.log              - Application errors
storage/logs/import_YYYY-MM-DD.log - Import job logs
/var/log/apache2/error.log        - Server errors (if access)
```

### Database

Direct database access (use carefully):
```bash
mysql -u u359033001_TOOL -p u359033001_TOOL
```

Useful queries:
```sql
-- Recent cases
SELECT * FROM cases ORDER BY created_at DESC LIMIT 10;

-- Failed imports
SELECT * FROM import_jobs WHERE status = 'error' ORDER BY started_at DESC;

-- Active users
SELECT * FROM users WHERE status = 'active';

-- Audit trail for case
SELECT * FROM case_audit_logs WHERE case_id = 123 ORDER BY created_at DESC;
```

## Next Steps

After basic setup:

1. **Add more users**: Admin → Users → Create
2. **Set up departments**: Verify departments match your structure
3. **Configure cron job**: For automatic imports
4. **Train staff**: Walk through system with team
5. **Import historical data**: Use API or manual import
6. **Customize**: Adjust config for your needs
7. **Backup**: Set up regular database backups
8. **Monitor**: Check logs daily for issues

## Support Contacts

- **Technical Issues**: admin@creodent.com
- **User Training**: manager@creodent.com
- **Hosting (Hostinger)**: support.hostinger.com

## Checklist

After setup, verify these work:

- [ ] Can login as admin
- [ ] Can create new user
- [ ] Can create new case manually
- [ ] Evolution connection test passes
- [ ] Can import cases from Evolution
- [ ] Cases display on dashboard
- [ ] Can assign case to worker
- [ ] Can update case status
- [ ] Audit logs show changes
- [ ] Worker can see assigned cases
- [ ] Concurrent edit detection works
- [ ] Cron job runs (check after 1 hour)
- [ ] Mobile view works
- [ ] Can logout and login again

---

**Quick Reference Card:**

```
+----------------------------------+
| CREODENT Quick Reference         |
+----------------------------------+
| Login: /login                    |
| Dashboard: /dashboard            |
| Cases: /cases                    |
| Import: /import                  |
| Admin: /admin/users              |
+----------------------------------+
| Default Admin:                   |
|   Username: admin                |
|   Password: Admin@123            |
|   (CHANGE THIS!)                 |
+----------------------------------+
| Support: admin@creodent.com      |
+----------------------------------+
```

Print this and keep it handy!

---

Last Updated: November 2, 2025
