#!/bin/bash
###############################################################################
# Creodent AoX Slack Sync Cron Job
#
# This script runs the Slack file synchronization
# Add to crontab to run every 5 minutes:
# */5 * * * * /path/to/creodent-aox-dashboard/cron-sync.sh
###############################################################################

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Path to sync API
SYNC_SCRIPT="$SCRIPT_DIR/api/sync.php"

# Log file
LOG_FILE="$SCRIPT_DIR/logs/sync.log"

# Create logs directory if it doesn't exist
mkdir -p "$SCRIPT_DIR/logs"

# Run sync
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting Slack sync..." >> "$LOG_FILE"

# Execute PHP script
php "$SYNC_SCRIPT" >> "$LOG_FILE" 2>&1

# Check exit status
if [ $? -eq 0 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Sync completed successfully" >> "$LOG_FILE"
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Sync failed with error code: $?" >> "$LOG_FILE"
fi

echo "" >> "$LOG_FILE"

# Optional: Keep only last 1000 lines of log
tail -n 1000 "$LOG_FILE" > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
