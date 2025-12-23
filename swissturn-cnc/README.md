# SwissTurn CNC Parser

A web-based SwissTurn CNC programming platform that parses, analyzes, and visualizes CNC programs with machine-aware intelligence.

## Features

- **Machine-Aware Parsing**: Parse CNC programs with machine-specific semantics
- **Auto-Commenting**: Generate line-by-line explanations automatically
- **G/M Code Knowledge Base**: Comprehensive database of G-codes and M-codes
- **Modal State Tracking**: Track modal states throughout program execution
- **Multiple Machine Support**: CINCOM L20E-2M10, HANWHA XD20/XD26II-V/XM20

## Requirements

- PHP 7.4+
- MySQL 5.7+
- Apache with mod_rewrite

## Installation

1. Clone the repository
2. Import database schema:
   ```bash
   mysql -u root -p your_database < database/schema.sql
   ```
3. Import seed data:
   ```bash
   mysql -u root -p your_database < database/seeds/machines.sql
   mysql -u root -p your_database < database/seeds/gcodes_universal.sql
   mysql -u root -p your_database < database/seeds/mcodes_universal.sql
   ```
4. Configure database connection in `config/database.php`
5. Upload all files to your Hostinger public_html directory

## Project Structure

```
swissturn-cnc/
├── index.php        # Application entry point
├── .htaccess        # URL rewriting
├── config/          # Configuration files
├── database/        # Schema and seed data
├── js/              # JavaScript files
├── css/             # CSS files
├── uploads/         # File uploads
├── src/
│   ├── core/        # Core classes (Database, Router, Response)
│   ├── controllers/ # HTTP controllers
│   ├── models/      # Data models
│   ├── parser/      # CNC parser engine
│   │   └── IR/      # Intermediate Representation
│   └── knowledge/   # G/M code knowledge system
├── views/           # PHP templates
└── tests/           # Unit tests
```

## Hostinger Deployment

This project is structured for Hostinger shared hosting compatibility:
- All files are at root level (no separate public directory)
- index.php serves as the single entry point
- .htaccess handles URL rewriting

## Supported Machines

| Machine | Manufacturer | Controller |
|---------|-------------|------------|
| L20E-2M10 | CINCOM | MITSUBISHI M800 |
| XD20 | HANWHA | FANUC 32i-B |
| XD26II-V | HANWHA | FANUC 32i-B |
| XM20 | HANWHA | FANUC 31i |

## Usage

1. Navigate to the web interface
2. Go to **Programs** > **Upload New Program**
3. Select the target machine
4. Upload or paste CNC code
5. View annotated program with explanations

## Development

See `CLAUDE.md` for development guidelines and coding standards.

## License

Proprietary - All rights reserved
