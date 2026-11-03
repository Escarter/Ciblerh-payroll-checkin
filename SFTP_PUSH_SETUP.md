# SFTP Push Setup Guide

This guide explains how to set up the SFTP Push infrastructure for receiving payslip files from external systems.

## Overview

The SFTP Push model uses HTTP Basic Authentication for file uploads. When you generate credentials in the Settings UI or via the setup command, the system:

1. ✅ Creates the push directory structure
2. ✅ Sets proper file permissions
3. ✅ Generates random authentication credentials
4. ✅ Stores credentials securely in the database

## Setup Methods

### Method 1: Auto Setup from Settings UI (Recommended for Most Users)

1. Go to **Settings → SFTP Payslip Integration**
2. Enable **"Enable SFTP Synchronization"**
3. Scroll to **"Push Configuration"**
4. Enter or keep the default **Push Path**: `storage/app/sftp-push`
5. Click **"Generate Credentials"**
6. Copy the displayed credentials (shown only once)
7. Click **"Save Credentials"**
   - This automatically:
     - Creates the push directory
     - Creates subdirectories (`processed/`, `failed/`)
     - Sets proper permissions
8. Click **"Test Push Configuration"** to verify

### Method 2: CLI Command Setup (Recommended for Deployment)

Run the artisan command to set everything up:

```bash
php artisan setup:sftp-push
```

**Options:**

- `--force` - Regenerate credentials (replaces existing ones)
- `--path=custom/path` - Use a custom push directory

**Example: Regenerate credentials**
```bash
php artisan setup:sftp-push --force
```

**Example: Custom path**
```bash
php artisan setup:sftp-push --path=storage/payslips/push
```

### Method 3: Shell Script (Recommended for CI/CD)

A convenience script is provided at `scripts/setup-sftp-push.sh`:

```bash
# Make script executable (one time only)
chmod +x scripts/setup-sftp-push.sh

# Run setup
./scripts/setup-sftp-push.sh

# With custom path
./scripts/setup-sftp-push.sh storage/custom/push

# Force regenerate credentials
./scripts/setup-sftp-push.sh storage/app/sftp-push --force
```

## What Gets Created

When you run the setup, the following directory structure is automatically created:

```
storage/app/sftp-push/
├── README.md              # Instructions
├── processed/             # Auto-archived files
├── failed/                # Failed/rejected files
└── (incoming files here)
```

### Directory Permissions

- Main directory: `755` (read/write/execute)
- All files: Writable by Laravel process

## Using the Credentials

Once credentials are generated, your external system can upload files using:

### cURL Example

```bash
curl -F "file=@payslip.pdf" \
  -u username:password \
  https://yourdomain.com/api/sftp-push/upload
```

### Python Example

```python
import requests
from requests.auth import HTTPBasicAuth

files = {'file': open('payslip.pdf', 'rb')}
auth = HTTPBasicAuth('username', 'password')
response = requests.post(
    'https://yourdomain.com/api/sftp-push/upload',
    files=files,
    auth=auth
)
print(response.json())
```

### PowerShell Example

```powershell
$auth = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("username:password"))

$response = Invoke-RestMethod -Uri "https://yourdomain.com/api/sftp-push/upload" `
  -Method Post `
  -Headers @{Authorization = "Basic $auth"} `
  -Form @{file = Get-Item "payslip.pdf"}

$response | ConvertTo-Json
```

## API Endpoints

### Upload File

**Endpoint:** `POST /api/sftp-push/upload`

**Authentication:** HTTP Basic Auth (username:password)

**Request:**
```
Content-Type: multipart/form-data

file: <PDF file binary>
```

**Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "filename": "payslip_2026_03.pdf",
  "path": "storage/app/sftp-push/payslip_2026_03.pdf"
}
```

### Test Connection

**Endpoint:** `GET /api/sftp-push/test`

**Authentication:** HTTP Basic Auth (username:password)

**Response:**
```json
{
  "success": true,
  "message": "Push endpoint is accessible"
}
```

### List Pending Files

**Endpoint:** `GET /api/sftp-push/pending`

**Authentication:** HTTP Basic Auth (username:password)

**Response:**
```json
{
  "pending_count": 5,
  "files": [
    {
      "filename": "payslip_2026_03_john.pdf",
      "path": "storage/app/sftp-push/payslip_2026_03_john.pdf",
      "size": 245632,
      "uploaded_at": "2026-03-06 14:32:15"
    }
  ]
}
```

## Troubleshooting

### "Push path is not writable"

**Problem:** The directory exists but Laravel can't write to it.

**Solution:**
```bash
# Give ownership to web server user (www-data on Ubuntu, _www on macOS)
sudo chown -R www-data:www-data storage/app/sftp-push

# Or set permissions
chmod -R 755 storage/app/sftp-push
chmod -R u+w storage/app/sftp-push
```

### "Failed to create push path"

**Problem:** Permission denied when trying to create the directory.

**Solution:**
```bash
# Create the directory manually
mkdir -p storage/app/sftp-push/processed
mkdir -p storage/app/sftp-push/failed

# Set permissions
chmod -R 755 storage/app/sftp-push
```

### Files Not Processing

**Problem:** Files uploaded but not being processed.

**Checks:**
1. Verify the scheduled job is running: `php artisan schedule:list`
2. Check job queue: `php artisan queue:work`
3. Review logs: `storage/logs/laravel.log`
4. Check file permissions: `ls -la storage/app/sftp-push/`

### Credentials Not Saving

**Problem:** "Push credentials required" error when saving.

**Solution:**
1. Generate new credentials by clicking the button
2. Ensure both username and password are displayed
3. Then click Save Credentials

## Security Considerations

- ⚠️ **Credentials are shown only once** - save them immediately
- ⚠️ **Use HTTPS for all uploads** - credentials are transmitted as Basic Auth
- ⚠️ **Rotate credentials regularly** using `--force` flag
- ⚠️ **Restrict access to push endpoint** based on IP/firewall if possible
- ⚠️ **Monitor logs** for failed upload attempts

## Scheduled Job

Files are automatically processed by the scheduled job:

```bash
# The job runs every 5 minutes (configurable)
php artisan schedule:run
```

This job:
1. Scans the push directory for new files
2. Attempts to match payslips to employees
3. Creates PayslipMatchingProposal records
4. Moves processed files to `processed/` directory
5. Moves failed files to `failed/` directory

## Next Steps

1. ✅ Run setup command or use Settings UI to generate credentials
2. ✅ Configure your external system with the API endpoint and credentials
3. ✅ Test upload with sample file
4. ✅ Monitor the first few uploads in Settings → SFTP Integration
5. ✅ Verify files appear in `storage/app/sftp-push/`
6. ✅ Check matching proposals in the dashboard

## Support

For issues or questions, check:
- Settings page → SFTP Configuration status indicator
- Application logs: `storage/logs/laravel.log`
- Database: `sftp_push_logs` table for upload history
