================================================================================
                    CREODENT HV RAFFLE 2025
                   Year-End Party Gift Drawing System
================================================================================

TABLE OF CONTENTS
-----------------
1. Overview
2. Requirements
3. Installation
4. Configuration
5. Usage Guide
6. Admin Functions
7. Troubleshooting
8. File Structure

================================================================================
1. OVERVIEW
================================================================================

The CREODENT HV Raffle 2025 is an interactive gift drawing system designed for
the year-end company party. Features include:

* Employee Prize Selection - Employees choose up to 5 prizes they want
* Smart Raffle System - Winners drawn from those who selected each prize
* Jackpot System - Special prizes unlock at 25, 50, and 75 spins
* TV-Optimized UI - Large fonts and buttons for display on TV screens
* Admin Panel - Statistics, undo, export, and settings management

================================================================================
2. REQUIREMENTS
================================================================================

* PHP 7.4 or higher
* Web server (Apache, Nginx, etc.)
* Write permissions for the /data directory

No database required - all data stored in JSON files.

================================================================================
3. INSTALLATION
================================================================================

1. Upload all files to your web server:
   Upload the entire 'creodent_raffle' folder to your web root or a subdirectory.

   Example: /public_html/creodent_raffle/

2. Set folder permissions:
   chmod 775 data/

   The data folder needs write permissions for saving state.

3. Configure admin PIN:
   Edit config.php and change the ADMIN_PIN value:

   define('ADMIN_PIN', 'your_secure_pin');

4. Access the system:
   Navigate to: https://your-domain.com/creodent_raffle/

================================================================================
4. CONFIGURATION
================================================================================

All configuration is in config.php:

ADMIN_PIN           - PIN code for admin access (CHANGE THIS!)
MAX_SELECTIONS      - Maximum prizes each employee can select (default: 5)
DEFAULT_UNLOCK_SPINS - Spin counts when jackpots unlock (default: [25, 50, 75])
JACKPOT_LIMIT       - Number of jackpot prizes (default: 3)

Data files in /data:
* seed.json  - Initial employee and prize data
* state.json - Current state (auto-generated, do not edit manually)

================================================================================
5. USAGE GUIDE
================================================================================

PHASE 1: PREPARATION (Admin)
----------------------------
1. Log in to admin panel (admin.php) with PIN
2. Verify employee list is correct in seed.json
3. Verify prize list is correct in seed.json
4. Configure jackpot order and unlock spins if needed
5. Ensure "Hide jackpot from picks" is enabled if desired

PHASE 2: EMPLOYEE SELECTION (Before/During Event)
-------------------------------------------------
1. Share the main URL with employees
2. Employees click their name in "Employee Selection" tab
3. They select up to 5 prizes they want to win
4. Click "Save My Selections"
5. Status changes from "Not Selected" to "Selections Made"

PHASE 3: PRIZE DRAWING (During Event)
-------------------------------------
1. Display on TV/projector screen
2. Go to "Available Prizes" tab
3. Click on a prize to draw a winner
4. System shows eligible candidates (those who selected this prize)
5. Click "DRAW WINNER" to animate and select winner
6. Winner announced with celebration effects
7. Continue to next prize

PHASE 4: JACKPOT DRAWING
------------------------
1. Jackpots unlock automatically at 25, 50, 75 spins
2. Go to "Jackpot Prizes" tab when unlocked
3. Click "Draw Jackpot Winner"
4. Special effects for jackpot winners

================================================================================
6. ADMIN FUNCTIONS
================================================================================

Access: admin.php (requires PIN)

DASHBOARD
---------
* View real-time statistics
* Monitor selection progress
* See recent spins

QUICK ACTIONS
-------------
* Undo Last Spin - Reverses the most recent draw
* Export Results - Downloads CSV of all results
* Refresh Statistics - Updates stats display

JACKPOT SETTINGS
----------------
* Unlock Spins - When each jackpot becomes available
* Jackpot Order - Drag to reorder the 3 jackpot prizes
* Hide from Picks - Hide jackpots from employee selection

DANGER ZONE
-----------
* Reset All Data - Clears all selections and results
  (Requires typing "RESET" to confirm)

================================================================================
7. TROUBLESHOOTING
================================================================================

"Permission denied" error:
--------------------------
Run: chmod 775 data/
Ensure the web server has write access to /data folder.

Blank page or PHP errors:
-------------------------
Check PHP version (7.4+ required)
Check error logs: /var/log/apache2/error.log or similar

Data not saving:
----------------
Check if data/state.json exists and is writable
Check disk space
Try refreshing the page

Admin login fails:
------------------
Verify PIN in config.php
Clear browser cookies
Check for typos (PIN is case-sensitive)

Animations not working:
-----------------------
Ensure JavaScript is enabled
Check browser console for errors
Try a different browser

================================================================================
8. FILE STRUCTURE
================================================================================

creodent_raffle/
|
|-- index.php          Main page with tabs
|-- choose.php         Employee prize selection
|-- spin.php           Prize drawing page
|-- admin.php          Admin panel
|-- config.php         Configuration settings
|-- README.txt         This file
|
|-- assets/
|   |-- style.css      Main stylesheet
|   |-- confetti.css   Celebration animations
|   |-- app.js         JavaScript functionality
|
|-- lib/
|   |-- auth.php       Authentication functions
|   |-- storage.php    Data storage functions
|   |-- utils.php      Utility functions
|
|-- data/
    |-- seed.json      Initial employee/prize data
    |-- state.json     Current state (auto-generated)
    |-- .lock          Lock file for concurrency

================================================================================

For support or questions, contact your IT administrator.

Happy Raffle! Enjoy your year-end party!

================================================================================
