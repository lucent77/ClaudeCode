================================================================================
                    CREODENT HV RAFFLE 2025
                Year-End Party Prize Drawing System
================================================================================

OVERVIEW
--------
This is an interactive raffle system designed for year-end company parties.
It features a TV-optimized UI, employee prize selection, exciting spin
animations, and a three-stage jackpot system.


FEATURES
--------
* Employee prize selection (up to 5 choices per person)
* Prize-centered raffle with animated spinning
* 3-stage jackpot system (unlocked at 25, 50, 75 spins)
* TV-optimized dark theme UI with large fonts
* Real-time statistics and progress tracking
* Admin dashboard with undo and export functions
* CSV export for results
* No database required (JSON file storage)


REQUIREMENTS
------------
* PHP 7.4 or higher
* Web server (Apache, Nginx, etc.)
* Write permissions for the /data folder


INSTALLATION
------------
1. Upload all files to your web server
   Example: public_html/creodent_raffle/

2. Set folder permissions:
   chmod 775 data/

3. Edit config.php to change the admin PIN:
   define('ADMIN_PIN', 'your-new-pin');

4. Access the application:
   https://your-domain.com/creodent_raffle/


FILE STRUCTURE
--------------
creodent_raffle/
|-- index.php          Main page with tabs
|-- choose.php         Employee prize selection
|-- spin.php           Raffle animation page
|-- admin.php          Admin dashboard
|-- config.php         Configuration settings
|-- README.txt         This file
|-- assets/
|   |-- style.css      TV-optimized styles
|   |-- app.js         JavaScript functionality
|-- lib/
|   |-- auth.php       Admin authentication
|   |-- storage.php    JSON data management
|   |-- utils.php      Utility functions
|-- data/
    |-- seed.json      Initial employee/prize data
    |-- state.json     Current raffle state (auto-generated)


USAGE GUIDE
-----------

PHASE 1: Setup (Admin)
1. Go to admin.php and login with PIN (default: 2025)
2. Configure jackpot prizes and unlock points
3. Verify employee and prize lists

PHASE 2: Employee Selection
1. Employees visit index.php
2. Click their name to go to selection page
3. Choose up to 5 prizes they want to win
4. Click "Save My Selections"

PHASE 3: Raffle
1. Admin clicks "Available Prizes" tab
2. Select a prize to raffle
3. Click "SPIN TO WIN!"
4. Confirm or cancel the winner
5. Winner's remaining selections are cleared
6. Continue with next prize

PHASE 4: Jackpot
- Jackpots unlock automatically at spin 25, 50, 75
- Alert banner shows when jackpot is available
- Jackpot prizes have special animation effects


ADMIN FUNCTIONS
---------------
* Export Results: Download all results as CSV
* Undo Last Spin: Reverse the most recent raffle
* Reset All Data: Clear everything and start over
* Jackpot Settings: Configure prizes and unlock points


DATA FILES
----------
seed.json: Contains initial employee and prize lists
           Edit this file to change participants or prizes

state.json: Stores current raffle state (auto-generated)
            Do NOT edit manually during a raffle


CUSTOMIZATION
-------------
To modify employees or prizes:
1. Edit data/seed.json
2. Delete data/state.json (if exists)
3. Refresh the application

Employee format:
{"id": "E001", "name": "Employee Name"}

Prize format:
{
  "id": "P001",
  "num": 1,
  "name": "PRIZE NAME",
  "display_name": "Prize Display Name",
  "tier": "regular" or "jackpot"
}


TROUBLESHOOTING
---------------
Q: "Permission denied" error
A: Run: chmod 775 data/ && chmod 664 data/*.json

Q: Jackpot not appearing
A: Check admin settings - jackpot order must be set

Q: Employee can't select prizes
A: Verify they haven't already won (status: done)

Q: Spin shows no candidates
A: All employees may have already won prizes


SECURITY NOTES
--------------
* Change the admin PIN before deployment!
* Keep the /data folder protected
* Don't expose seed.json to public access
* Use HTTPS in production


SUPPORT
-------
For issues or questions, contact your IT administrator.


VERSION INFO
------------
Version: 1.0
Created: December 2025
Platform: PHP 7.4+ / Vanilla JS


================================================================================
                   Have a great raffle and happy holidays!
================================================================================
