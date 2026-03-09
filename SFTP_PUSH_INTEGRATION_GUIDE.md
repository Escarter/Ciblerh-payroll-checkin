# SFTP PUSH Model - Integration Guide (v2.0)

**Last Updated:** March 6, 2026  
**Model:** PUSH (external systems → our application)

---

## Quick Start

### For Admins

1. Go to **Settings → SFTP Configuration**
2. Click **"Generate Push Credentials"** 
3. Share credentials with external systems
4. Enable **"SFTP Sync"**
5. Test with **"Test Configuration"** button

### For Integrators

```bash
# Upload payslip
curl -F "file=@payslip.pdf" \
  -u "push_abc12345:sEcureP@ssw0rd123" \
  https://your-app.com/api/sftp-push/upload

# Check pending files
curl -u "push_abc12345:sEcureP@ssw0rd123" \
  https://your-app.com/api/sftp-push/pending

# Test connection
curl -u "push_abc12345:sEcureP@ssw0rd123" \
  https://your-app.com/api/sftp-push/test
```

---

## Architecture

**PUSH Model:** External systems push PDFs → Our HTTP API → Local Storage → Auto-process

```
External System (Legacy/Modern)
    ↓ (HTTP POST with credentials)
CibleRh HTTP API (/api/sftp-push/upload)
    ↓ (Validates & stores)
Local Storage (storage/app/sftp-push/)
    ↓ (Queue processes)
FetchSftpPayslipsJob
    ↓ (Matches to employees)
PayslipMatchingProposal (pending)
    ↓ (Admin reviews)
Send to Employees
```

---

## Setup

### 1. Generate Credentials in Admin Panel

**Settings > SFTP Configuration:**

- Click **"Generate Push Credentials"**
  - `Username`: auto-generated (e.g., `push_abc12345`)
  - `Password`: auto-generated  (e.g., `sEcureP@ssw0rd123`)

- Click **"Save Credentials"**

- Share with external systems

- Can **"Regenerate"** at any time (old credentials immediately invalid)

### 2. Configure Storage Path

- Default: `storage/app/sftp-push`
- Customize if needed
- Must be writable by web server

### 3. Set Matching Strategies

Choose how files are matched:
- ✅ **Employee ID** - Extract from filename: `emp_123`
- ✅ **Department Code** - Extract from filename: `dept_HR`
- ✅ **Company Code** - Extract from filename: `co_ACME`
- ✅ **Folder Structure** - Use first folder name
- ✅ **Timestamp** - Match by file

### 4. Enable Sync

Toggle **"Enable SFTP Sync"** to start receiving files

---

## HTTP API Endpoints

### Upload File

**POST** `/api/sftp-push/upload`

**Auth:** HTTP Basic Auth (username:password)

**Content:** multipart/form-data with `file` field

**Only PDF files accepted**

```bash
curl -X POST \
  -F "file=@payslip_2025_12.pdf" \
  -u "push_abc12345:sEcureP@ssw0rd123" \
  https://your-app.com/api/sftp-push/upload
```

**Success (201):**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "filename": "payslip_2025_12_1709750445.pdf",
  "original_name": "payslip_2025_12.pdf",
  "size": 1048576,
  "stored_at": "2026-03-06T13:34:05Z"
}
```

**Errors:**
- `400` - No file provided
- `401` - Invalid credentials
- `403` - SFTP sync disabled
- `415` - Not a PDF file
- `500` - Server error

---

### List Pending Files

**GET** `/api/sftp-push/pending`

**Auth:** HTTP Basic Auth

```bash
curl -u "push_abc12345:sEcureP@ssw0rd123" \
  https://your-app.com/api/sftp-push/pending
```

**Response:**
```json
{
  "success": true,
  "count": 3,
  "files": [
    {
      "name": "payslip_2025_12_1709750445.pdf",
      "size": 1048576,
      "uploaded_at": "2026-03-06 13:34:05"
    }
  ]
}
```

---

### Test Connection

**GET** `/api/sftp-push/test`

**Auth:** HTTP Basic Auth

```bash
curl -u "push_abc12345:sEcureP@ssw0rd123" \
  https://your-app.com/api/sftp-push/test
```

**Response:**
```json
{
  "success": true,
  "path": "/var/www/app/storage/app/sftp-push",
  "accessible": true,
  "writable": true,
  "enabled": true
}
```

---

## Integration Examples

### Python

```python
import requests
from requests.auth import HTTPBasicAuth

def upload_payslip(file_path, username, password, api_url):
    auth = HTTPBasicAuth(username, password)
    with open(file_path, 'rb') as f:
        files = {'file': f}
        response = requests.post(
            f"{api_url}/api/sftp-push/upload",
            files=files,
            auth=auth
        )
    return response.json()

# Usage
result = upload_payslip(
    'payslip.pdf',
    'push_abc12345',
    'sEcureP@ssw0rd123',
    'https://your-app.com'
)
print(result)
```

### Node.js

```javascript
const FormData = require('form-data');
const fs = require('fs');
const axios = require('axios');

async function uploadPayslip(filePath, username, password, apiUrl) {
    const form = new FormData();
    form.append('file', fs.createReadStream(filePath));
    
    const auth = {
        username: username,
        password: password
    };
    
    const response = await axios.post(
        `${apiUrl}/api/sftp-push/upload`,
        form,
        { headers: form.getHeaders(), auth }
    );
    
    return response.data;
}

// Usage
uploadPayslip(
    'payslip.pdf',
    'push_abc12345',
    'sEcureP@ssw0rd123',
    'https://your-app.com'
).then(result => console.log(result));
```

### PHP

```php
function uploadPayslip($filePath, $username, $password, $apiUrl) {
    $ch = curl_init();
    
    $auth = $username . ':' . $password;
    $file = new CURLFile($filePath, 'application/pdf', basename($filePath));
    
    curl_setopt($ch, CURLOPT_URL, "$apiUrl/api/sftp-push/upload");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $file]);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $auth);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $statusCode,
        'response' => json_decode($response, true)
    ];
}

// Usage
$result = uploadPayslip(
    'payslip.pdf',
    'push_abc12345',
    'sEcureP@ssw0rd123',
    'https://your-app.com'
);
print_r($result);
```

### cURL (Bash)

```bash
#!/bin/bash

USERNAME="push_abc12345"
PASSWORD="sEcureP@ssw0rd123"
API_URL="https://your-app.com"
FILE_PATH="payslip.pdf"

# Upload single file
curl -X POST \
  -F "file=@$FILE_PATH" \
  -u "$USERNAME:$PASSWORD" \
  "$API_URL/api/sftp-push/upload"

# Batch upload multiple files
for file in *.pdf; do
    echo "Uploading $file..."
    curl -X POST \
      -F "file=@$file" \
      -u "$USERNAME:$PASSWORD" \
      "$API_URL/api/sftp-push/upload"
done
```

---

## File Processing

### Automatic Processing Flow

1. **File Uploaded** → Stored with timestamp `{name}_{time}.pdf`

2. **Queue Job Runs** → FetchSftpPayslipsJob processes files

3. **Metadata Extracted** → Using enabled matching strategies

4. **Candidates Generated** → Ranked by confidence score

5. **Proposal Created** → Status = **pending** (awaits admin)

6. **Admin Reviews** → Portal shows proposals

7. **Admin Validates** → Selects employee, accepts/rejects

8. **File Archived** → Moved to `processed/` directory

### Matching Strategies

| Strategy | Pattern | Example |
|----------|---------|---------|
| Employee ID | `emp_123` or `employee_456` | `PAYSLIP_emp_789.pdf` |
| Department | `dept_HR` or `department_Finance` | `2025_12_dept_HR.pdf` |
| Company | `co_ACME` or `company_XYZ` | `dispatch_co_ABC.pdf` |
| Folder | First folder name | `HR/payslips/2025_12.pdf` → `HR` |
| Timestamp | File modification date | Matched by month/year |

---

## Monitoring

### Audit Trail (`sftp_push_logs` table)

All uploads logged:

```sql
SELECT * FROM sftp_push_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY created_at DESC;
```

**Fields:**
- `username` - User account used
- `filename` - Original filename
- `stored_filename` - Filename stored locally
- `file_size` - Size in bytes
- `remote_ip` - Source IP address
- `status` - `success` | `failed` | `rejected`
- `error_message` - Error details if failed
- `rejection_reason` - Why rejected (non-PDF, etc.)
- `created_at` - Upload timestamp

### Query Examples

```php
// Successful uploads today
$logs = SftpPushLog::successful()->recentDays(1)->get();

// Failed uploads
$logs = SftpPushLog::failed()->get();

// For specific user
$logs = SftpPushLog::byUsername('push_abc12345')->get();

// Statistics (last 7 days)
$stats = SftpPushLog::selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->recentDays(7)
    ->get();
```

---

## Troubleshooting

### 401 - Unauthorized

**Cause:** Invalid credentials

**Fix:**
- Verify username/password in settings
- Check Basic Auth header format
- Regenerate credentials if unsure

### 415 - Unsupported Media Type

**Cause:** File is not PDF

**Fix:**
- Only `.pdf` files accepted
- Verify file before uploading
- Check file extension

### 500 - Server Error

**Cause:** Filesystem issue

**Fixes:**
1. Test path exists: `ls -la storage/app/sftp-push/`
2. Check permissions: `ls -ld storage/app/sftp-push/`
3. Check disk space: `df -h`
4. Check logs: `tail -f storage/logs/laravel.log`

Test endpoint:
```bash
curl -u "username:password" \
  https://your-app.com/api/sftp-push/test
```

### Files Not Processing

**Checks:**
1. Is SFTP Sync **enabled**? (Settings)
2. Is queue worker running? `php artisan queue:work`
3. Are files actually arriving? `ls storage/app/sftp-push/`
4. Check logs: `storage/logs/laravel.log`

---

## Security

### Credential Rotation

If credentials compromised:

1. Go to **Settings → SFTP Configuration**
2. Click **"Regenerate Credentials"**
3. Old credentials **immediately invalid**
4. Update external systems with new credentials
5. Review audit log for suspicious uploads

### Audit Trail

All uploads logged for security review:
- Who uploaded
- When uploaded
- What file
- Success/failure
- Source IP

Retain logs per compliance requirements.

---

## Performance Tips

- **Batch Processing**: Files processed in batches of 100 to prevent timeouts
- **Incremental Sync**: Only new files processed (skips old files)
- **Depth Limiting**: Searches max 5 folder levels (configurable)
- **Queue Workers**: Always run queue worker in production

Start queue worker:
```bash
php artisan queue:work processing --sleep=3 --tries=3
```

Recommended: Use Supervisor for production (see `supervisor-queue-workers.conf`)