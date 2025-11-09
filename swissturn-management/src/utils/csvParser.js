/**
 * Parse CSV text to array of objects
 * Handles different data formats and whitespace properly
 */
export const parseCSV = (csvText) => {
  const lines = csvText.split('\n').filter(line => line.trim());

  if (lines.length === 0) return [];

  const headers = parseCSVLine(lines[0]);
  const data = [];

  for (let i = 1; i < lines.length; i++) {
    const values = parseCSVLine(lines[i]);

    if (values.length === 0) continue;

    const row = {};
    headers.forEach((header, index) => {
      // Clean header and value
      const cleanHeader = header.trim();
      const value = values[index] ? values[index].trim() : '';
      row[cleanHeader] = value;
    });

    data.push(row);
  }

  return data;
};

/**
 * Parse a single CSV line handling commas within quotes
 */
const parseCSVLine = (line) => {
  const result = [];
  let current = '';
  let inQuotes = false;

  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    const nextChar = line[i + 1];

    if (char === '"') {
      if (inQuotes && nextChar === '"') {
        // Escaped quote
        current += '"';
        i++;
      } else {
        // Toggle quote state
        inQuotes = !inQuotes;
      }
    } else if (char === ',' && !inQuotes) {
      // Field separator
      result.push(current);
      current = '';
    } else {
      current += char;
    }
  }

  // Push the last field
  result.push(current);

  return result;
};

/**
 * Parse Swiss production CSV data
 */
export const parseSwissCSV = (csvText) => {
  const data = parseCSV(csvText);

  return data.map(row => ({
    month: row.MONTH || '',
    week: row.WEEK || '',
    date: row.DATE || '',
    modelName: row['MODEL NAME'] || '',
    sku: row.SKU || '',
    lotNo: row['LOT. NO'] || '',
    partLength: parseFloat(row['PART LENGTH']) || 0,
    worker: row.WORKER || '',
    cnc: row.CNC || '',
    diameter: row.DIAMETER || '',
    length: row.LENGTH || '',
    lot: row.LOT || '',
    unit: parseFloat(row.UNIT) || 0,
    expedCount: parseFloat(row['EXPED COUNT']) || 0,
    expedCount24h: parseFloat(row['EXPED COUNT 24H']) || 0,
    plan: parseFloat(row.PLAN) || 0,
    am: row.AM || '',
    pm: row.PM || '',
    percent: row['%'] || '',
    unitTotal: parseFloat(row['UNIT TOTAL']) || 0,
    m: parseFloat(row.M) || 0,
    s: parseFloat(row.S) || 0,
    cncRunTime: row['CNC Run Time'] || '',
    workingTime: row['Working Time'] || '',
    setting: row.SETTING || '',
    cncTotal: row['CNC TOTAL'] || '',
    testUnit: row['TEST UNIT'] || '',
    note: row.NOTE || '',
    achievement1Day: parseFloat(row['1DAY Achievement(%)']) || 0,
    achievement24h: parseFloat(row['24H Achievement(%)']) || 0,
    achievementTotal: parseFloat(row['TOTAL Achievement(%)']) || 0,
    millingDaysRemaining: parseFloat(row['Milling days remaining']) || 0,
    settingQty: row['Setting Qty'] || '',
    toolBrokenFailQty: row['Tool-Broken-Fail OTY'] || '',
    dentFailedQty: row['Dent-Failed Qty'] || '',
    dimensionFailQty: row['Dimension-Fail QTY'] || '',
    overnightFailQty: row['Overnight-Fail QTY'] || '',
    etc: row.ETC || '',
    description: row['DESCRIPTION (SETTING TOOL CHANGE STOP ETC.)'] || row['DESCRIPTION (SETTING, TOOL CHANGE, STOP, ETC.)'] || '',
    inspectedBy: row['INSPECTED BY (Initial)'] || ''
  }));
};

/**
 * Parse Tool CSV data
 */
export const parseToolCSV = (csvText) => {
  const data = parseCSV(csvText);

  return data.map((row, index) => ({
    id: `TOOL-${Date.now()}-${index}`,
    code: row['Tool Code'] || '',
    name: row['Tool Name'] || '',
    category: row['Category Name'] || '',
    size: row['Tool Size'] || '',
    supplier: row['Supplier Name'] || '',
    supplierModel: row['Supplier Model Number'] || '',
    currentStock: parseInt(row['Current Stock']) || 0,
    minStock: parseInt(row['Minimum Stock']) || 0,
    lifespanType: row['Lifespan Type'] || 'time',
    lifespanLimit: parseFloat(row['Lifespan Limit']) || 0,
    currentUsage: 0,
    status: 'available',
    description: row.Description || '',
    machineId: null
  }));
};

/**
 * Convert array of objects to CSV text
 */
export const toCSV = (data, headers) => {
  if (data.length === 0) return '';

  const csvHeaders = headers || Object.keys(data[0]);
  const csvRows = [csvHeaders.join(',')];

  data.forEach(row => {
    const values = csvHeaders.map(header => {
      const value = row[header] !== undefined ? row[header] : '';
      // Escape quotes and wrap in quotes if contains comma or quote
      const stringValue = String(value);
      if (stringValue.includes(',') || stringValue.includes('"') || stringValue.includes('\n')) {
        return `"${stringValue.replace(/"/g, '""')}"`;
      }
      return stringValue;
    });
    csvRows.push(values.join(','));
  });

  return csvRows.join('\n');
};

/**
 * Download CSV file
 */
export const downloadCSV = (csvText, filename) => {
  const blob = new Blob([csvText], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);

  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';

  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};
