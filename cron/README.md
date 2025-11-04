# Automated Import Cron Jobs

This directory contains automated cron job scripts for periodic data imports.

## Available Scripts

### 1. import-evolution.php

Imports cases from Evolution Portal for specified location and date range.

**Usage:**
```bash
php cron/import-evolution.php [location] [days]
```

**Parameters:**
- `location`: Location to import (HV or NYC), default: HV
- `days`: Number of days back to import (1-60), default: 1

**Examples:**
```bash
# Import HV cases from yesterday (default)
php cron/import-evolution.php

# Import HV cases from yesterday
php cron/import-evolution.php HV 1

# Import NYC cases from last 7 days
php cron/import-evolution.php NYC 7

# Import HV cases from last 30 days
php cron/import-evolution.php HV 30
```

**Output:**
```
========================================
CREODENT Evolution Import
========================================
Started: 2025-11-04 08:00:00
Location: HV
Date Range: 2025-11-03 to 2025-11-04
----------------------------------------

[✓] Database connected
[✓] Services initialized

Fetching cases from Evolution Portal...
[✓] Found 15 cases

[1/15] Imported: 2025-48801
[2/15] Updated: 2025-48802
[3/15] Imported: 2025-48803
...

========================================
Import Summary
========================================
Total Cases:    15
Imported:       10
Updated:        4
Errors:         1
----------------------------------------

Errors:
  - 2025-48815: Case number is required

Completed: 2025-11-04 08:02:15
========================================
```

## Crontab Configuration

### For Linux/Unix

Edit your crontab:
```bash
crontab -e
```

Add the following entries:

```cron
# CREODENT Evolution Portal Imports
# Format: minute hour day month weekday command

# Import HV location cases every day at 6:00 AM (yesterday's cases)
0 6 * * * cd /path/to/creodent && /usr/bin/php cron/import-evolution.php HV 1 >> storage/logs/cron-hv.log 2>&1

# Import NYC location cases every day at 6:30 AM (yesterday's cases)
30 6 * * * cd /path/to/creodent && /usr/bin/php cron/import-evolution.php NYC 1 >> storage/logs/cron-nyc.log 2>&1

# Weekly full sync: Import last 7 days every Sunday at 2:00 AM
0 2 * * 0 cd /path/to/creodent && /usr/bin/php cron/import-evolution.php HV 7 >> storage/logs/cron-hv-weekly.log 2>&1
0 2 * * 0 cd /path/to/creodent && /usr/bin/php cron/import-evolution.php NYC 7 >> storage/logs/cron-nyc-weekly.log 2>&1

# Monthly full sync: Import last 30 days on 1st of every month at 3:00 AM
0 3 1 * * cd /path/to/creodent && /usr/bin/php cron/import-evolution.php HV 30 >> storage/logs/cron-hv-monthly.log 2>&1
0 3 1 * * cd /path/to/creodent && /usr/bin/php cron/import-evolution.php NYC 30 >> storage/logs/cron-nyc-monthly.log 2>&1
```

### For Windows (Task Scheduler)

1. Open Task Scheduler
2. Click "Create Basic Task"
3. Name: "CREODENT HV Daily Import"
4. Trigger: Daily at 6:00 AM
5. Action: Start a program
6. Program/script: `C:\php\php.exe`
7. Arguments: `C:\path\to\creodent\cron\import-evolution.php HV 1`
8. Start in: `C:\path\to\creodent`
9. Repeat for NYC location

### For cPanel (Hostinger)

1. Log in to cPanel
2. Go to "Cron Jobs"
3. Add new cron job:
   - **Minute:** 0
   - **Hour:** 6
   - **Day:** *
   - **Month:** *
   - **Weekday:** *
   - **Command:** `/usr/bin/php /home/username/public_html/cron/import-evolution.php HV 1 >> /home/username/storage/logs/cron-hv.log 2>&1`

4. Add another for NYC:
   - **Minute:** 30
   - **Hour:** 6
   - **Day:** *
   - **Month:** *
   - **Weekday:** *
   - **Command:** `/usr/bin/php /home/username/public_html/cron/import-evolution.php NYC 1 >> /home/username/storage/logs/cron-nyc.log 2>&1`

## Recommended Schedule

### Daily Import
Run once per day in the early morning before work starts:
- **HV:** 6:00 AM - Import yesterday's cases
- **NYC:** 6:30 AM - Import yesterday's cases

### Weekly Backup Sync
Run once per week to catch any missed cases:
- **Sunday 2:00 AM** - Import last 7 days for both locations

### Monthly Full Sync
Run once per month for comprehensive sync:
- **1st of month 3:00 AM** - Import last 30 days for both locations

## Monitoring

### Check Cron Logs

**Linux/Unix:**
```bash
# View recent HV imports
tail -f storage/logs/cron-hv.log

# View recent NYC imports
tail -f storage/logs/cron-nyc.log

# Search for errors
grep "Error" storage/logs/cron-*.log
```

**Windows:**
Check Task Scheduler history for execution results.

### Application Logs

All imports are also logged in the application logs:
```bash
tail -f storage/logs/evolution_$(date +%Y-%m-%d).log
```

### Email Notifications (Optional)

You can add email notifications to the cron jobs:

```cron
# Send email on completion
0 6 * * * cd /path/to/creodent && /usr/bin/php cron/import-evolution.php HV 1 | mail -s "CREODENT HV Import" admin@creodent.com
```

Or create a wrapper script:

```bash
#!/bin/bash
# cron/import-with-notification.sh

LOCATION=$1
DAYS=$2
OUTPUT=$(cd /path/to/creodent && /usr/bin/php cron/import-evolution.php $LOCATION $DAYS 2>&1)
EXIT_CODE=$?

if [ $EXIT_CODE -ne 0 ]; then
    echo "$OUTPUT" | mail -s "CREODENT Import FAILED - $LOCATION" admin@creodent.com
else
    # Only send email if there were imports or errors
    if echo "$OUTPUT" | grep -q "Imported:\s*[1-9]" || echo "$OUTPUT" | grep -q "Errors:\s*[1-9]"; then
        echo "$OUTPUT" | mail -s "CREODENT Import Summary - $LOCATION" admin@creodent.com
    fi
fi
```

## Troubleshooting

### Script Not Running

1. **Check PHP path:**
   ```bash
   which php
   ```

2. **Check file permissions:**
   ```bash
   chmod +x cron/import-evolution.php
   ```

3. **Test manually:**
   ```bash
   php cron/import-evolution.php HV 1
   ```

### Database Connection Errors

1. Check database credentials in `config/config.php`
2. Ensure the cron user has database access
3. Check if database is accessible from cron environment

### Evolution Portal Connection Errors

1. Test Evolution Portal connection manually:
   ```bash
   curl -X POST "https://evolution-url/api.php" \
     -H "Content-Type: application/xml" \
     -d '<?xml version="1.0"?><request><event>account_login</event><username>user</username><password>pass</password></request>'
   ```

2. Check firewall rules for outbound connections
3. Verify SQL Server connection (if using direct connection)

### No Cases Imported

1. Check if date range has cases:
   - Log in to Evolution Portal
   - Check case list for the date range

2. Check if cases already exist:
   - Cases with same `external_case_no` are updated, not re-imported
   - Check application logs for "Updated" vs "Imported"

3. Verify location settings in Evolution Portal

## Performance Considerations

### Large Imports

For large imports (1000+ cases):
- Increase PHP memory limit: `php -d memory_limit=512M cron/import-evolution.php`
- Increase execution time: `php -d max_execution_time=600 cron/import-evolution.php`
- Run during off-peak hours (e.g., 2-4 AM)

### Optimization

1. **Incremental Imports:** Import only 1-2 days daily, not full month
2. **Stagger Schedules:** Run HV and NYC imports at different times
3. **Monitor Performance:** Check import duration in logs
4. **Database Indexing:** Ensure `external_case_no` is indexed

## Security

1. **File Permissions:**
   ```bash
   chmod 750 cron/import-evolution.php
   chown www-data:www-data cron/import-evolution.php
   ```

2. **Log Permissions:**
   ```bash
   chmod 750 storage/logs
   chmod 640 storage/logs/*.log
   ```

3. **Restrict crontab access:**
   - Only allow admin users to edit crontab
   - Review crontab regularly for unauthorized changes

## Testing

Before enabling automated cron jobs, test thoroughly:

```bash
# Test with 1 day import
php cron/import-evolution.php HV 1

# Test with longer range
php cron/import-evolution.php HV 7

# Test error handling (invalid location)
php cron/import-evolution.php INVALID 1

# Test error handling (invalid days)
php cron/import-evolution.php HV 100
```

Expected results:
- Exit code 0 on success
- Exit code 1 on errors
- Detailed output with progress
- Summary with statistics
