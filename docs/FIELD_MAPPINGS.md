# Field Mappings Documentation

This document describes how data from Google Sheets (JSON format) maps to the database structure.

## Overview

The system preserves original Google Sheets data in department-specific tables (`solidex_orders`, `print3d_orders`, `cocr_orders`) as JSON, while also extracting common fields to the main `cases` and `case_items` tables.

## Solidex Department

### Original Google Sheets Fields

```json
{
  "ID": "1",
  "Timestamp": "11/1/2025 13:30:00",
  "DUE": "11/5/2025",
  "SEND": "11/4/2025",
  "LAB": "ABC Dental Lab",
  "COUNT": "2",
  "TEETH": "14, 15",
  "IMPLANT SYSTEM": "Straumann",
  "INSTRUCTION": "Custom abutment needed",
  "PREFERENCES": "Rush order"
}
```

### Database Mapping

**Table: cases**
- `external_case_no` ← "ID" or derived from case number
- `lab_name` ← "LAB"
- `due_date` ← "DUE"
- `source` ← 'google_sheets' or 'evolution_web_portal'

**Table: case_items**
- `work_type` ← 'SOLIDEX'
- `count` ← "COUNT"
- `tooth_no` ← "TEETH"
- `instruction` ← "INSTRUCTION"
- `preferences` ← "PREFERENCES"

**Table: solidex_orders**
- `payload_json` ← Entire original JSON record

### PHP Mapping Array

```php
$solidexMap = [
    'ID' => 'id',
    'Timestamp' => 'timestamp',
    'DUE' => 'due_date',
    'SEND' => 'send_date',
    'LAB' => 'lab_name',
    'COUNT' => 'count',
    'TEETH' => 'tooth_no',
    'IMPLANT SYSTEM' => 'implant_system',
    'INSTRUCTION' => 'instruction',
    'PREFERENCES' => 'preferences',
];
```

## 3D Print Department

### Original Google Sheets Fields

```json
{
  "CASE #": "2025-70665",
  "TIME STAMP": "11/1/2025 14:15:00",
  "DATE": "11/5/2025",
  "TYPE": "Model",
  "LAB #": "ABC Dental",
  "PATIENT #": "John Doe",
  "TOOTH #": "14",
  "COUNT": "1",
  "HV/NYC": "HV",
  "INSTRUCTIONS": "Print in high resolution",
  "uploaded Korea": "Yes"
}
```

### Database Mapping

**Table: cases**
- `external_case_no` ← "CASE #"
- `lab_name` ← "LAB #"
- `patient_name` ← "PATIENT #"
- `due_date` ← "DATE"
- `location` ← "HV/NYC"

**Table: case_items**
- `work_type` ← '3DPRINT'
- `tooth_no` ← "TOOTH #"
- `count` ← "COUNT"
- `instruction` ← "INSTRUCTIONS"
- `preferences` ← "uploaded Korea"

**Table: print3d_orders**
- `payload_json` ← Entire original JSON record

### PHP Mapping Array

```php
$print3dMap = [
    'CASE #' => 'external_case_no',
    'TIME STAMP' => 'timestamp',
    'DATE' => 'due_date',
    'TYPE' => 'work_type',
    'LAB #' => 'lab_name',
    'PATIENT #' => 'patient_name',
    'TOOTH #' => 'tooth_no',
    'COUNT' => 'count',
    'HV/NYC' => 'location',
    'INSTRUCTIONS' => 'instruction',
    'uploaded Korea' => 'uploaded_korea',
];
```

## CoCr/ZEST Department

### Original Google Sheets Fields

```json
{
  "ID": "150",
  "DATE": "11/5/2025",
  "TYPE": "CoCr Framework",
  "DISK Material": "CoCr",
  "COMBO": "Yes",
  "MC IO": "MC",
  "L/D": "L",
  "LAB #": "XYZ Lab",
  "PATIENT #": "Jane Smith",
  "IMPLANT TYPE": "Nobel",
  "INSTRUCTIONS": "Polish finish",
  "PREFERENCES": "Rush",
  "DESIGN": "Dr. Kim",
  "CAM": "John",
  "CNC": "Completed"
}
```

### Database Mapping

**Table: cases**
- `external_case_no` ← "ID" or derived
- `lab_name` ← "LAB #"
- `patient_name` ← "PATIENT #"
- `due_date` ← "DATE"

**Table: case_items**
- `work_type` ← 'COCR'
- `material` ← "DISK Material"
- `instruction` ← "INSTRUCTIONS"
- `preferences` ← "PREFERENCES"
- `design_notes` ← "DESIGN", "CAM", "CNC" combined

**Table: cocr_orders**
- `payload_json` ← Entire original JSON record

### PHP Mapping Array

```php
$cocrMap = [
    'ID' => 'id',
    'DATE' => 'due_date',
    'TYPE' => 'work_type',
    'DISK Material' => 'material',
    'COMBO' => 'combo',
    'MC IO' => 'mc_io',
    'L/D' => 'l_d',
    'LAB #' => 'lab_name',
    'PATIENT #' => 'patient_name',
    'IMPLANT TYPE' => 'implant_type',
    'INSTRUCTIONS' => 'instruction',
    'PREFERENCES' => 'preferences',
    'DESIGN' => 'design',
    'CAM' => 'cam',
    'CNC' => 'cnc',
];
```

## Evolution Web Portal to Database

### Evolution XML Response Example

```xml
<response>
    <case>
        <caseno>2025-70665</caseno>
        <lab>ABC Dental Lab</lab>
        <patient>John Doe</patient>
        <duedate>2025-11-05</duedate>
        <location>HV</location>
        <status>new</status>
        <items>
            <item>
                <tooth>14</tooth>
                <type>Crown</type>
                <material>Zirconia</material>
            </item>
        </items>
    </case>
</response>
```

### Database Mapping

**Table: cases**
- `external_case_no` ← caseno
- `lab_name` ← lab
- `patient_name` ← patient
- `due_date` ← duedate
- `location` ← location
- `status` ← status
- `raw_payload` ← entire XML as JSON
- `source` ← 'evolution_web_portal'

**Table: case_items** (for each item)
- `tooth_no` ← tooth
- `work_type` ← determined by type/material
- `material` ← material
- `status` ← 'pending'

## Data Type Conversions

### Date Formats

**Input formats supported:**
- `2025-11-05` (ISO)
- `11/5/2025` (US)
- `11-5-2025`
- Unix timestamp

**Output format:**
- Database: `YYYY-MM-DD` (DATE type)
- Display: `Nov 5, 2025` (formatted by frontend)

### Status Values

**Case Statuses:**
- `new` - Newly created/imported
- `in_progress` - Work has started
- `done` - Completed
- `on_hold` - Temporarily paused
- `canceled` - Canceled
- `archived` - Archived (hidden from active lists)

**Item Statuses:**
- `pending` - Not yet assigned
- `assigned` - Assigned to worker
- `working` - Work in progress
- `done` - Completed
- `remake` - Needs to be redone
- `rejected` - Rejected/failed QC

## Handling Missing Fields

When importing data with missing fields:

1. **Required fields missing**: Import fails, logged in `import_jobs` table
2. **Optional fields missing**: Set to NULL in database
3. **Invalid data types**: Conversion attempted, falls back to NULL if fails
4. **Unknown fields**: Preserved in `raw_payload` JSON

## Querying Original Data

To retrieve original Google Sheets data:

```php
// Get Solidex data
$solidexData = $db->queryOne(
    "SELECT payload_json FROM solidex_orders WHERE case_id = ?",
    [$caseId]
);
$originalData = json_decode($solidexData['payload_json'], true);

// Access original field
$implantSystem = $originalData['IMPLANT SYSTEM'];
```

## Syncing Back to Google Sheets (Optional)

If `google_sheets.enabled` is true in config:

1. System reads from database tables
2. Converts back to original JSON format using reverse mapping
3. Updates Google Sheet via API
4. Compares timestamps to avoid overwriting newer data

**Sync direction:**
- `db_to_sheets` - Database is source of truth (recommended)
- `sheets_to_db` - Sheets is source of truth (legacy mode)
- `bidirectional` - Newest timestamp wins (advanced, use with caution)

## Custom Field Mapping

To add custom fields:

1. **Update mapping array** in `app/Services/ImportService.php`
2. **Add database column** if needed (or use JSON payload)
3. **Update views** to display new field
4. **Run migration** if schema changed

Example adding new field to Solidex:

```php
// 1. Add to mapping
$solidexMap['NEW_FIELD'] = 'new_field_name';

// 2. Add database column (optional)
ALTER TABLE case_items ADD COLUMN new_field_name VARCHAR(255);

// 3. Update ImportService to extract field
$itemData['new_field_name'] = $data['NEW_FIELD'] ?? null;

// 4. Display in view
<td x-text="item.new_field_name"></td>
```

## Best Practices

1. **Always preserve original data** in `payload_json` columns
2. **Extract commonly queried fields** to dedicated columns for performance
3. **Use consistent date formats** (ISO 8601)
4. **Validate data before import** to catch issues early
5. **Log all transformations** in audit trail
6. **Document custom mappings** in this file
7. **Test imports** with sample data before production use

## Migration Checklist

When migrating from Google Sheets to database:

- [ ] Export all Google Sheets to JSON format
- [ ] Backup original sheets (just in case)
- [ ] Run import script for each department
- [ ] Verify record counts match
- [ ] Spot-check random samples for accuracy
- [ ] Test concurrent editing with multiple users
- [ ] Configure cron job for Evolution sync
- [ ] Update user documentation
- [ ] Train staff on new system
- [ ] Keep sheets as read-only backup for 30 days

## Troubleshooting

**Problem: Fields not mapping correctly**
- Check spelling in mapping array (case-sensitive)
- Verify JSON structure matches expected format
- Review import_jobs table for error details

**Problem: Data type mismatch**
- Check date format conversions
- Verify numeric fields are actual numbers
- Look for special characters in text fields

**Problem: Duplicate imports**
- Check UNIQUE constraint on external_case_no
- Verify UPSERT logic in ImportService
- Review case creation timestamps

---

Last Updated: November 2, 2025
