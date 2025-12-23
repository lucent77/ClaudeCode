# SwissTurn CNC Parser - Development Guidelines

## Project Overview
Web-based SwissTurn CNC programming platform for parsing, analyzing,
and visually editing CNC programs. Supports CINCOM and HANWHA machines.

## Technology Stack
- **Backend**: PHP 7.4+ (Hostinger compatible)
- **Database**: MySQL 5.7+
- **Frontend**: HTML, Tailwind CSS, Vanilla JavaScript
- **No frameworks**: Pure PHP, no Laravel/Symfony

## Code Style Rules

### PHP
- Use PSR-12 coding standard
- All classes use PascalCase
- All methods use camelCase
- All database columns use snake_case
- Type hints required for all function parameters and returns
- Document all public methods with PHPDoc

### JavaScript
- Use ES6+ features (const/let, arrow functions, etc.)
- Use camelCase for variables and functions
- Use PascalCase for classes
- No jQuery - vanilla JS only
- Document public functions with JSDoc

### SQL
- Use snake_case for all identifiers
- Use singular table names (machine, not machines)
- Always include created_at, updated_at timestamps

### CSS
- Tailwind CSS only - no custom CSS unless absolutely necessary
- Use utility-first approach
- Extract components for repeated patterns

## Architecture Rules

### Parser Engine
- All parsing must be machine-profile-driven
- Never hardcode G/M code behaviors
- Always track modal state
- Generate IR for every parsed program

### Knowledge Base
- G/M codes are stored in database, not code
- Each code can be machine-specific or universal
- Explanation templates use {VARIABLE} placeholders

### Templates
- All variable names must be English
- Variable names must be safe identifiers (alphanumeric + underscore)
- Templates store CNC structure, not raw text

## File Organization
- One class per file
- File name matches class name
- Group by feature, not by type

## Security Rules
- Sanitize all CNC file uploads
- Validate file extensions (.nc, .cnc, .txt, .prg)
- Escape all output in views
- Use prepared statements for all queries

## Prohibited Patterns
- No inline SQL queries (use repositories)
- No mixing HTML with PHP logic
- No global variables
- No eval() or similar dynamic execution
- No external API calls without timeout

## Supported Machines
- CINCOM L20E-2M10
- HANWHA XD20
- HANWHA XD26II-V
- HANWHA XM20
