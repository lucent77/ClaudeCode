# Windows App Integration API Documentation

This document describes the REST API endpoints for integrating the Windows VB.NET application with the CREODENT Work Management System.

## Base URL
```
https://your-domain.com/api
```

## Authentication

All API requests require authentication via API Key in the request header:

```
X-API-KEY: your_api_key_here
```

The API key is configured in `config/config.php` under `windows_app.api_key`.

### Optional IP Whitelisting

For additional security, you can configure allowed IP addresses in `config/config.php`:

```php
'windows_app' => [
    'enabled' => true,
    'api_key' => 'your_api_key',
    'allowed_ips' => ['192.168.1.100', '192.168.1.101']
]
```

## Endpoints

### 1. Health Check

Check if the API service is online and responding.

**Endpoint:** `GET /api/health`

**Response:**
```json
{
    "success": true,
    "message": "Service is healthy",
    "data": {
        "status": "online",
        "timestamp": "2025-11-04 10:30:00",
        "version": "2.0.0"
    }
}
```

---

### 2. Import 3D Print Work Items

Import work items for the 3D Print department.

**Endpoint:** `POST /api/import/3dprint`

**Request Body:**
```json
{
    "items": [
        {
            "case_no": "2025-48801",
            "patient_name": "John Doe",
            "lab_name": "ABC Dental Lab",
            "work_type": "3DPRINT",
            "quantity": 1,
            "due_date": "2025-11-10",
            "notes": "Special printing instructions"
        },
        {
            "case_no": "2025-48802",
            "patient_name": "Jane Smith",
            "lab_name": "XYZ Dental",
            "work_type": "3DPRINT",
            "quantity": 2,
            "due_date": "2025-11-11",
            "notes": null
        }
    ]
}
```

**Field Descriptions:**
- `case_no` (required): External case number from Evolution
- `patient_name` (optional): Patient name
- `lab_name` (optional): Laboratory name
- `work_type` (required): Must be "3DPRINT"
- `quantity` (optional): Number of items, default 1
- `due_date` (optional): Due date in YYYY-MM-DD format
- `notes` (optional): Special instructions or notes

**Response:**
```json
{
    "success": true,
    "message": "Import completed",
    "data": {
        "imported": 2,
        "updated": 0,
        "errors": [],
        "total": 2
    }
}
```

**Error Response:**
```json
{
    "success": true,
    "message": "Import completed",
    "data": {
        "imported": 1,
        "updated": 0,
        "errors": [
            {
                "case_no": "2025-48803",
                "error": "Case number is required"
            }
        ],
        "total": 2
    }
}
```

---

### 3. Import CoCr/ZEST Work Items

Import work items for the CoCr/ZEST department.

**Endpoint:** `POST /api/import/cocr`

**Request Body:**
```json
{
    "items": [
        {
            "case_no": "2025-48801",
            "patient_name": "John Doe",
            "lab_name": "ABC Dental Lab",
            "work_type": "COCR",
            "quantity": 3,
            "due_date": "2025-11-10",
            "notes": "CoCr bridge - upper right"
        }
    ]
}
```

**Field Descriptions:** Same as 3D Print endpoint, but `work_type` should be "COCR" or "ZEST"

**Response:** Same format as 3D Print endpoint

---

### 4. Import Solidex Work Items

Import work items for the Solidex department.

**Endpoint:** `POST /api/import/solidex`

**Request Body:**
```json
{
    "items": [
        {
            "case_no": "2025-48801",
            "patient_name": "John Doe",
            "lab_name": "ABC Dental Lab",
            "work_type": "SOLIDEX",
            "quantity": 1,
            "due_date": "2025-11-10",
            "notes": "Crown - tooth #14"
        }
    ]
}
```

**Field Descriptions:** Same as 3D Print endpoint, but `work_type` should be "SOLIDEX"

**Response:** Same format as 3D Print endpoint

---

### 5. Get Case Status

Retrieve the current status of a case.

**Endpoint:** `GET /api/case/status?case_no=2025-48801`

**Query Parameters:**
- `case_no` (required): External case number

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 123,
        "external_case_no": "2025-48801",
        "patient_name": "John Doe",
        "lab_name": "ABC Dental Lab",
        "status": "in_progress",
        "priority": "normal",
        "due_date": "2025-11-10",
        "created_at": "2025-11-04 08:00:00",
        "last_imported_at": "2025-11-04 10:30:00",
        "items": [
            {
                "id": 456,
                "work_type": "3DPRINT",
                "quantity": 1,
                "status": "in_progress",
                "assigned_to_user_id": 5,
                "assigned_to_name": "Mike Johnson"
            },
            {
                "id": 457,
                "work_type": "COCR",
                "quantity": 2,
                "status": "pending",
                "assigned_to_user_id": null,
                "assigned_to_name": null
            }
        ]
    }
}
```

**Error Response (Case Not Found):**
```json
{
    "success": false,
    "message": "Case not found",
    "error": "Case not found"
}
```

---

## Status Values

### Case Status
- `new` - New case, not yet started
- `in_progress` - Work in progress
- `on_hold` - Temporarily paused
- `done` - Completed
- `canceled` - Canceled
- `archived` - Archived

### Work Item Status
- `pending` - Waiting to be started
- `in_progress` - Currently being worked on
- `done` - Completed
- `rejected` - Rejected/needs revision

### Priority Levels
- `low` - Low priority
- `normal` - Normal priority
- `high` - High priority
- `urgent` - Urgent priority

---

## Error Responses

### 401 Unauthorized
```json
{
    "success": false,
    "message": "Unauthorized",
    "error": "Unauthorized"
}
```

**Causes:**
- Missing or invalid API key
- IP address not in whitelist
- Windows App integration disabled

### 400 Bad Request
```json
{
    "success": false,
    "message": "Invalid request format. Expected \"items\" array",
    "error": "Invalid request format. Expected \"items\" array"
}
```

**Causes:**
- Missing required fields
- Invalid JSON format
- Invalid data types

### 404 Not Found
```json
{
    "success": false,
    "message": "Case not found",
    "error": "Case not found"
}
```

### 500 Internal Server Error
```json
{
    "success": false,
    "message": "Database connection error",
    "error": "Database connection error"
}
```

---

## VB.NET Example Code

### Basic API Call with Authentication

```vb.net
Imports System.Net.Http
Imports System.Text
Imports Newtonsoft.Json

Public Class CreodentApiClient
    Private ReadOnly _baseUrl As String = "https://your-domain.com/api"
    Private ReadOnly _apiKey As String = "your_api_key_here"
    Private ReadOnly _httpClient As HttpClient

    Public Sub New()
        _httpClient = New HttpClient()
        _httpClient.DefaultRequestHeaders.Add("X-API-KEY", _apiKey)
    End Sub

    ' Health Check
    Public Async Function HealthCheck() As Task(Of Boolean)
        Try
            Dim response = Await _httpClient.GetAsync($"{_baseUrl}/health")
            Return response.IsSuccessStatusCode
        Catch ex As Exception
            Console.WriteLine($"Health check failed: {ex.Message}")
            Return False
        End Try
    End Function

    ' Import 3D Print Items
    Public Async Function Import3DPrint(items As List(Of WorkItem)) As Task(Of ImportResult)
        Try
            Dim requestData = New With {
                .items = items
            }

            Dim json = JsonConvert.SerializeObject(requestData)
            Dim content = New StringContent(json, Encoding.UTF8, "application/json")

            Dim response = Await _httpClient.PostAsync($"{_baseUrl}/import/3dprint", content)
            Dim responseBody = Await response.Content.ReadAsStringAsync()

            If response.IsSuccessStatusCode Then
                Dim result = JsonConvert.DeserializeObject(Of ApiResponse)(responseBody)
                Return result.Data
            Else
                Throw New Exception($"API Error: {response.StatusCode}")
            End If
        Catch ex As Exception
            Console.WriteLine($"Import failed: {ex.Message}")
            Throw
        End Try
    End Function

    ' Get Case Status
    Public Async Function GetCaseStatus(caseNo As String) As Task(Of CaseData)
        Try
            Dim response = Await _httpClient.GetAsync($"{_baseUrl}/case/status?case_no={caseNo}")
            Dim responseBody = Await response.Content.ReadAsStringAsync()

            If response.IsSuccessStatusCode Then
                Dim result = JsonConvert.DeserializeObject(Of ApiResponse)(responseBody)
                Return result.Data
            Else
                Throw New Exception($"API Error: {response.StatusCode}")
            End If
        Catch ex As Exception
            Console.WriteLine($"Get case status failed: {ex.Message}")
            Throw
        End Try
    End Function
End Class

' Data Models
Public Class WorkItem
    Public Property case_no As String
    Public Property patient_name As String
    Public Property lab_name As String
    Public Property work_type As String
    Public Property quantity As Integer
    Public Property due_date As String
    Public Property notes As String
End Class

Public Class ImportResult
    Public Property imported As Integer
    Public Property updated As Integer
    Public Property errors As List(Of ImportError)
    Public Property total As Integer
End Class

Public Class ImportError
    Public Property case_no As String
    Public Property [error] As String
End Class

Public Class ApiResponse
    Public Property success As Boolean
    Public Property message As String
    Public Property data As Object
End Class

Public Class CaseData
    Public Property id As Integer
    Public Property external_case_no As String
    Public Property patient_name As String
    Public Property lab_name As String
    Public Property status As String
    Public Property priority As String
    Public Property due_date As String
    Public Property created_at As String
    Public Property last_imported_at As String
    Public Property items As List(Of CaseItem)
End Class

Public Class CaseItem
    Public Property id As Integer
    Public Property work_type As String
    Public Property quantity As Integer
    Public Property status As String
    Public Property assigned_to_user_id As Integer?
    Public Property assigned_to_name As String
End Class
```

### Usage Example

```vb.net
' Initialize API client
Dim apiClient As New CreodentApiClient()

' Test connection
Dim isHealthy = Await apiClient.HealthCheck()
If Not isHealthy Then
    MessageBox.Show("Cannot connect to CREODENT API")
    Return
End If

' Prepare work items
Dim items As New List(Of WorkItem) From {
    New WorkItem With {
        .case_no = "2025-48801",
        .patient_name = "John Doe",
        .lab_name = "ABC Dental Lab",
        .work_type = "3DPRINT",
        .quantity = 1,
        .due_date = "2025-11-10",
        .notes = "Special instructions"
    }
}

' Import items
Try
    Dim result = Await apiClient.Import3DPrint(items)
    MessageBox.Show($"Import completed: {result.imported} imported, {result.errors.Count} errors")
Catch ex As Exception
    MessageBox.Show($"Import failed: {ex.Message}")
End Try

' Get case status
Try
    Dim caseData = Await apiClient.GetCaseStatus("2025-48801")
    Console.WriteLine($"Case {caseData.external_case_no}: {caseData.status}")
Catch ex As Exception
    MessageBox.Show($"Failed to get case status: {ex.Message}")
End Try
```

---

## Best Practices

1. **Connection Testing**: Always check the health endpoint before importing data
2. **Error Handling**: Implement proper error handling for all API calls
3. **Batch Imports**: Group multiple items in a single request to reduce API calls
4. **Logging**: Log all API requests and responses for debugging
5. **Retries**: Implement retry logic for transient failures (network issues, timeouts)
6. **Rate Limiting**: Avoid overwhelming the server with too many concurrent requests

---

## Testing

### Using cURL

```bash
# Health Check
curl -X GET "https://your-domain.com/api/health" \
  -H "X-API-KEY: your_api_key"

# Import 3D Print Items
curl -X POST "https://your-domain.com/api/import/3dprint" \
  -H "X-API-KEY: your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "case_no": "2025-48801",
        "patient_name": "John Doe",
        "lab_name": "ABC Dental Lab",
        "work_type": "3DPRINT",
        "quantity": 1,
        "due_date": "2025-11-10",
        "notes": "Test import"
      }
    ]
  }'

# Get Case Status
curl -X GET "https://your-domain.com/api/case/status?case_no=2025-48801" \
  -H "X-API-KEY: your_api_key"
```

### Using Postman

1. Create a new collection named "CREODENT API"
2. Add the API key to collection variables
3. Set the header `X-API-KEY` with value `{{api_key}}`
4. Create requests for each endpoint
5. Test each endpoint with sample data

---

## Support

For technical support or questions about the API:
- Email: support@creodent.com
- Documentation: https://your-domain.com/docs
- GitHub: https://github.com/your-repo/ClaudeCode
