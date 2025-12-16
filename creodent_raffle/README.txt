================================================================================
                     CREODENT HV Raffle 2025
                  Year-End Party Gift Raffle System
================================================================================

QUICK START GUIDE
================================================================================

1. REQUIREMENTS
   - PHP 7.4 or higher
   - Web server (Apache/Nginx) or PHP built-in server
   - Write permissions on the 'data/' folder

2. INSTALLATION
   a) Upload all files to your web server
   b) Set permissions: chmod 775 data/
   c) Edit config.php and change the ADMIN_PIN (default: 2025)
   d) Access index.php in your browser

3. INITIAL SETUP
   a) Login to admin.php with your PIN
   b) Configure the 3 jackpot prizes in order
   c) Set unlock thresholds (default: 25, 50, 75 spins)

================================================================================

HOW TO USE
================================================================================

PHASE 1: Employee Selection
---------------------------
1. Employees access the main page (index.php)
2. Click "Employee Selection" tab
3. Find and click their name
4. Select exactly 5 desired prizes
5. Click "Save Selections"

PHASE 2: Raffle Execution
-------------------------
1. Admin logs in (admin.php)
2. Go to "Available Prizes" tab
3. Click on a prize to raffle
4. Press "SPIN!" to draw a winner
5. Winner is announced with celebration effects
6. Repeat for all prizes

PHASE 3: Jackpot Draws
----------------------
- Jackpots unlock at configured spin counts (25, 50, 75)
- When unlocked, jackpots appear at the top of the prize list
- All remaining employees are eligible for jackpots
- Special effects for jackpot reveals

================================================================================

FILE STRUCTURE
================================================================================

creodent_raffle/
├── index.php           Main page with tab navigation
├── choose.php          Employee gift selection page
├── spin.php            Raffle animation page
├── admin.php           Admin dashboard
├── config.php          Configuration (CHANGE ADMIN_PIN!)
├── README.txt          This file
│
├── api/                API endpoints
│   ├── state.php       Get current state
│   ├── select.php      Save employee selections
│   ├── spin.php        Execute raffle
│   ├── undo.php        Undo last spin
│   ├── reset.php       Reset all data
│   ├── settings.php    Update settings
│   ├── export.php      Export CSV
│   ├── login.php       Admin login
│   └── logout.php      Admin logout
│
├── assets/             Frontend assets
│   ├── style.css       Main stylesheet (TV-optimized)
│   ├── confetti.css    Celebration effects
│   └── app.js          JavaScript application
│
├── lib/                PHP libraries
│   ├── storage.php     Data storage functions
│   ├── auth.php        Authentication
│   └── utils.php       Utility functions
│
└── data/               Data storage (needs write permission!)
    ├── seed.json       Initial employee/prize data
    └── state.json      Current state (auto-generated)

================================================================================

ADMIN FEATURES
================================================================================

- Jackpot Configuration: Set 3 jackpot prizes and unlock thresholds
- Undo Last Spin: Revert the most recent raffle
- Export CSV: Download all raffle results
- Reset Data: Start fresh (requires typing "RESET")
- View Statistics: See all winners and selection status

================================================================================

TROUBLESHOOTING
================================================================================

"Permission denied" error:
  - Run: chmod 775 data/
  - Ensure web server can write to data folder

"System busy" error:
  - Wait a moment and try again
  - File locking prevents concurrent modifications

Selections not saving:
  - Check that exactly 5 prizes are selected
  - Ensure the employee hasn't already won

Raffle not working:
  - Verify admin is logged in
  - Check that prize is still available
  - For jackpots, verify spin count threshold is met

================================================================================

SECURITY NOTES
================================================================================

1. CHANGE THE DEFAULT ADMIN PIN in config.php!
2. Consider using .htaccess to protect the data/ folder
3. Use HTTPS in production
4. The system uses file locking for concurrent access safety

================================================================================

SUPPORT
================================================================================

For issues or questions, contact your IT administrator.

================================================================================
                     Have a great Year-End Party!
================================================================================
