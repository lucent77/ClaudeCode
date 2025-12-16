# Design Confirm System

A comprehensive dental case management system with AI-powered email analysis, Google Sheets integration, and multi-channel notifications.

## Features

- **Case Management**: Track dental cases from design to confirmation
- **AI Email Analysis**: Automatically analyze incoming emails using Google Gemini AI
- **Status Tracking**: Pending, Confirmed, Action Needed, Confirmed with Action Needed, Resolved
- **Google Sheets Integration**: Automatic updates to tracking spreadsheets
- **Multi-channel Notifications**: Email, SMS (Twilio), Slack
- **ABS Integration**: Dental lab management system integration
- **Modern UI**: Responsive Tailwind CSS interface

## Requirements

- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.3+
- Composer (for dependency installation)
- PHP Extensions: PDO, IMAP, cURL, JSON, mbstring

## Quick Start

### 1. Upload Files

Upload all files from `public_html` to your Hostinger `public_html` directory.

### 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configure Environment

1. Copy `env.example.txt` to `.env`
2. Update the following settings:

```env
# Database
DB_HOST=localhost
DB_NAME=your_database
DB_USER=your_username
DB_PASS=your_password

# Email SMTP
SMTP_HOST=smtp.gmail.com
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password

# Google Gemini AI
GEMINI_API_KEY=your_api_key

# Twilio SMS (optional)
TWILIO_SID=your_sid
TWILIO_TOKEN=your_token
TWILIO_FROM_NUMBER=+1234567890

# Slack (optional)
SLACK_WEBHOOK_URL=your_webhook_url
```

### 4. Import Database

Import `database.sql` into your MySQL database:

```bash
mysql -u username -p database_name < database.sql
```

Or use phpMyAdmin to import the file.

### 5. Set Up Cron Job

Add this cron job to monitor emails (every minute):

```
* * * * * php /home/username/public_html/cron/monitor_emails.php >> /home/username/logs/cron.log 2>&1
```

### 6. Access the System

Navigate to your domain and login with:
- **Username**: admin
- **Password**: admin123 (change immediately!)

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user

### Cases
- `GET /api/cases` - List cases (with filters)
- `POST /api/cases` - Create case
- `GET /api/cases/{id}` - Get case details
- `PUT /api/cases/{id}` - Update case
- `DELETE /api/cases/{id}` - Delete case
- `GET /api/cases/stats` - Get statistics
- `POST /api/cases/{id}/send-email` - Send email for case

### Clients
- `GET /api/clients` - List clients
- `POST /api/clients` - Create client
- `GET /api/clients/{id}` - Get client
- `PUT /api/clients/{id}` - Update client
- `DELETE /api/clients/{id}` - Delete client

### Products
- `GET /api/products` - List products
- `POST /api/products` - Create product
- `PUT /api/products/{id}` - Update product
- `DELETE /api/products/{id}` - Delete product

### Templates
- `GET /api/templates` - List email templates
- `POST /api/templates` - Create template
- `PUT /api/templates/{id}` - Update template
- `DELETE /api/templates/{id}` - Delete template

### Users
- `GET /api/users` - List users
- `POST /api/users` - Create user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

## External Service Setup

### Google Gemini AI

1. Go to [Google AI Studio](https://makersuite.google.com/)
2. Create an API key
3. Add to `.env` as `GEMINI_API_KEY`

### Google Sheets

1. Create a Google Cloud project
2. Enable Google Sheets API
3. Create a service account
4. Download JSON credentials
5. Add path to `.env` as `GOOGLE_SHEETS_CREDENTIALS`
6. Share your spreadsheets with the service account email

### Gmail (SMTP/IMAP)

1. Enable 2-Factor Authentication on your Gmail account
2. Generate an App Password (Google Account > Security > App Passwords)
3. Use this password in `SMTP_PASS` and `IMAP_PASS`

### Twilio SMS

1. Sign up at [Twilio](https://www.twilio.com/)
2. Get your Account SID and Auth Token
3. Purchase a phone number
4. Add to `.env`

### Slack

1. Create a Slack App at [api.slack.com](https://api.slack.com/apps)
2. Create an Incoming Webhook
3. Add webhook URL to `.env`

## File Structure

```
public_html/
├── index.php              # Entry point
├── .htaccess              # Apache config
├── .env                   # Environment variables
├── database.sql           # Database schema
├── composer.json          # Dependencies
│
├── src/
│   ├── Controllers/       # API Controllers
│   ├── Services/          # Business logic
│   ├── Database.php       # DB connection
│   ├── Router.php         # URL routing
│   └── routes.php         # Route definitions
│
├── config/
│   └── env.php            # Environment loader
│
├── cron/
│   └── monitor_emails.php # Email monitoring
│
├── views/
│   ├── layout.php         # Main layout
│   ├── login.php          # Login page
│   └── dashboard.php      # Dashboard
│
├── logs/                  # Log files
└── vendor/                # Composer packages
```

## Security Notes

1. Change the default admin password immediately
2. Keep `.env` secure and never commit to version control
3. Use HTTPS in production
4. Regularly update dependencies
5. Monitor log files for suspicious activity

## Troubleshooting

### Database Connection Errors
- Verify credentials in `.env`
- Check MySQL/MariaDB is running
- Ensure database exists

### Email Not Sending
- Check SMTP credentials
- Verify App Password is correct
- Check spam folder

### IMAP Not Working
- Enable IMAP in Gmail settings
- Use App Password
- Check PHP IMAP extension

### Cron Not Running
- Verify file path in cron command
- Check PHP path
- Review cron logs

## Support

For issues or questions, please check the log files in the `logs/` directory.

## License

Proprietary - All rights reserved
