# SFTP Payslip Integration - Complete Workflow

## Overview
The SFTP payslip integration is designed to fetch payslip files from an SFTP server, validate them against your department/company structure, and then process them through your existing payslip pipeline (split → encrypt → send).

## Architecture

### Key Concept: File-Based Matching (NOT Employee-Based)
- **SFTP Proposal** = One complete PDF file from SFTP (may contain multiple employee payslips)
- **Validation** = Confirming which Department, Company, Month, and Year the file belongs to
- **Processing** = Downloading the file and feeding it into `PayslipSendingPlan::start()`

---

## Workflow Phases

### Phase 1: SFTP Fetching (`FetchSftpPayslipsJob`)
**When**: Scheduled job runs on Kernel schedule (hourly, daily, or weekly per settings)

```
SFTP Server → FetchSftpPayslipsJob
  ├─ Connect to SFTP (SSH key or password auth)
  ├─ List all PDF files
  ├─ For each new file:
  │   ├─ Parse metadata from filename/path using configured strategies
  │   ├─ Generate match candidates (Department/Company suggestions)
  │   ├─ Create PayslipMatchingProposal record (status='pending')
  │   └─ Log action
  └─ When proposals found → Send notification to admins
```

**Matching Strategies** (configured in Feature Configuration settings):
- `department_code` - Extract department code from filename
- `company_code` - Extract company code from filename
- `folder_structure` - Parse department from folder path
- `fuzzy_timestamp` - Attempt to detect month/year from file timestamp

**Result**: `PayslipMatchingProposal` record with:
```php
{
  file_path: '/payslips/2024/dept_code_202502.pdf',
  file_name: 'payslips_2024_02.pdf',
  proposed_match: {
    candidates: [
      {
        type: 'department_code',
        strategy: 'department_code',
        confidence: 0.95,
        department_id: 'uuid-1',
        company_id: 'uuid-2',
        department_name: 'Sales',
        company_name: 'ACME Corp'
      },
      // ... more candidates, sorted by confidence
    ],
    best_match: { /* highest confidence candidate */ }
  },
  status: 'pending'
}
```

---

### Phase 2: Admin Validation (`SftpPayslipValidator` Livewire Component)
**Who**: Administrators with `manage-payslips` permission

**UI Features**:
- Status tabs: Pending, Validated, Processed, Rejected
- Search by filename
- Filter by department
- Bulk validation for multiple files

**Validation Process**:
1. Admin views pending proposal with:
   - File name and path
   - File size and upload timestamp
   - Ranked list of candidate Department/Company matches
   
2. Admin confirms or corrects:
   - Department (required)
   - Company (required)
   - Month (1-12, required)
   - Year (required)

3. Proposal saved with:
   ```php
   {
     status: 'validated',
     matched_to_department_id: 'user-selected-dept',
     matched_to_company_id: 'user-selected-company',
     matched_month: 2,
     matched_year: 2024,
     matched_by_user_id: 'admin-user-id',
     matched_at: 'timestamp'
   }
   ```

4. `ProcessValidatedPayslipsJob` automatically dispatched

**Alternative Action**: Admin can reject proposal with reason
```php
{
  status: 'rejected',
  rejection_reason: 'File is corrupted / duplicate / invalid format',
  matched_by_user_id: 'admin-id',
  matched_at: 'timestamp'
}
```

---

### Phase 3: Download & Process (`ProcessValidatedPayslipsJob`)
**When**: Automatically dispatched after admin validation

**Flow**:

```
ProcessValidatedPayslipsJob($proposal)
  ├─ Verify proposal status = 'validated'
  ├─ Download file from SFTP to local storage:
  │   └─ Path: storage/app/raw/sftp_{dept}_{mm}_{yyyy}_{timestamp}.pdf
  ├─ Create SendPayslipProcess record:
  │   ├─ user_id: Currently authenticated user (or system user)
  │   ├─ department_id: From validated proposal
  │   ├─ company_id: From validated proposal
  │   ├─ month: From validated proposal
  │   ├─ year: From validated proposal
  │   ├─ raw_file: /full/path/to/storage/app/raw/{filename}.pdf
  │   ├─ destination_directory: dept_{id}_{timestamp}
  │   └─ status: 'processing'
  ├─ Log action to audit_log
  ├─ Update proposal:
  │   ├─ status: 'processed'
  │   ├─ processed_at: now()
  │   └─ matched_by_user_id: current admin
  └─ Call PayslipSendingPlan::start($sendPayslipProcess)
      ├─ Dispatches SplitPdfJob (splits PDF into pages)
      ├─ Then RenameEncryptPdfJob (matches pages to employees, encrypts)
      └─ Then SendPayslipJob (sends payslips via email/SMS)
```

**Key Points**:
- File is downloaded to `raw` disk (configurable location)
- `SendPayslipProcess` record tracks the processing batch
- Admin who validated the proposal is logged
- Job is queued to `processing` queue with 2 retries

---

### Phase 4: Standard Payslip Pipeline
**Already Implemented**: Your existing workflow

```
PayslipSendingPlan::start($sendPayslipProcess)
  ├─ [1] SplitPdfJob
  │   ├─ Takes raw_file (multi-page PDF)
  │   ├─ Splits into individual pages:
  │   │   └─ storage/app/splitted/{destination_directory}/page_1.pdf
  │   │                                                  /page_2.pdf
  │   │                                                  /page_N.pdf
  │   └─ Updates SendPayslipProcess status
  ├─ [2] RenameEncryptPdfJob
  │   ├─ For each page:
  │   │   ├─ Match page to employee by matricule
  │   │   ├─ Create Payslip record
  │   │   ├─ Encrypt page
  │   │   └─ Store encrypted copy
  │   └─ Updates SendPayslipProcess percentage_completion
  └─ [3] SendPayslipJob
      ├─ Chunk employees
      ├─ Send payslips via email
      ├─ Send notifications via SMS
      └─ Mark as sent
```

---

## Database Schema

### PayslipMatchingProposal Table

| Field | Type | Purpose |
|-------|------|---------|
| `id` | UUID | Primary key |
| `file_path` | String | Path on SFTP server |
| `file_name` | String | Original filename |
| `file_size` | Integer | File size in bytes |
| `file_timestamp` | Timestamp | File's last modified timestamp |
| `proposed_match` | JSON | Contains `candidates[]` and `best_match` |
| `matched_to_department_id` | UUID FK | Department selected by admin |
| `matched_to_company_id` | UUID FK | Company selected by admin |
| `matched_month` | Integer (1-12) | Month selected by admin |
| `matched_year` | Integer | Year selected by admin |
| `matched_by_user_id` | UUID FK | Admin who validated |
| `matched_at` | Timestamp | When validation occurred |
| `processed_at` | Timestamp | When processing job completed |
| `status` | Enum | pending/validated/processed/rejected/failed |
| `rejection_reason` | Text | Reason if rejected |
| `created_at` / `updated_at` | Timestamp | Audit |
| `deleted_at` | Timestamp | Soft delete support |

---

## Configuration Settings

Settings stored in `settings` table (company_id=1):

| Key | Type | Default | Purpose |
|-----|------|---------|---------|
| `sftp_sync_enabled` | Boolean | false | Enable/disable SFTP fetching |
| `sftp_host` | String | null | SFTP server hostname |
| `sftp_port` | Integer | 22 | SFTP port |
| `sftp_username` | String | null | SFTP username |
| `sftp_auth_type` | String | password | `password` or `ssh_key` |
| `sftp_password` | String | null | SFTP password (if password auth) |
| `sftp_private_key_path` | String | null | Path to SSH private key |
| `sftp_passphrase` | String | null | SSH key passphrase |
| `sftp_root` | String | /payslips | Root folder on SFTP server |
| `sftp_sync_frequency` | String | daily | `hourly`, `daily`, or `weekly` |
| `sftp_matching_strategies` | JSON | [] | Array of strategies to use |

---

## Multi-File Handling

When multiple SFTP files are available for different departments/companies:

```
FetchSftpPayslipsJob runs
  ├─ Finds /payslips/SALES_202502.pdf
  │   └─ Creates PayslipMatchingProposal (best_match: Sales dept)
  ├─ Finds /payslips/MARKETING_202502.pdf
  │   └─ Creates PayslipMatchingProposal (best_match: Marketing dept)
  └─ Finds /payslips/HR_202502.pdf
      └─ Creates PayslipMatchingProposal (best_match: HR dept)

Admin validates each independently:
  ├─ Confirms SALES proposal → ProcessValidatedPayslipsJob dispatched
  ├─ Confirms MARKETING proposal → ProcessValidatedPayslipsJob dispatched
  └─ Confirms HR proposal → ProcessValidatedPayslipsJob dispatched

Each file processed independently:
  ├─ SALES file → SendPayslipProcess created → PayslipSendingPlan::start()
  │   (splits, encrypts, sends to Sales employees)
  ├─ MARKETING file → SendPayslipProcess created → PayslipSendingPlan::start()
  │   (splits, encrypts, sends to Marketing employees)
  └─ HR file → SendPayslipProcess created → PayslipSendingPlan::start()
      (splits, encrypts, sends to HR employees)
```

---

## CLI Commands

### Manual Deactivation (if needed)
```bash
php artisan users:deactivate-inactive [--dry-run]
```

Shows inactive users and prompts for confirmation before deactivating.

---

## Audit Logging

All actions logged to `audit_logs` table:

| Action | User | Description |
|--------|------|-------------|
| `sftp_payslip_processing_started` | Current user | When ProcessValidatedPayslipsJob starts |
| `sftp_payslip_proposal_validated` | Admin | When proposal is validated |
| Existing logs | System | SplitPdfJob, encryption, sending |

---

## Error Handling

### SFTP Connection Fails
- `FetchSftpPayslipsJob` logs error and stops
- No proposals created
- Admin can retry manually (job runs on schedule)

### File Download Fails
- `ProcessValidatedPayslipsJob` marks proposal as `failed`
- Sets `rejection_reason: "Failed to download file from SFTP server"`
- Admin notified (can re-validate later if SFTP is fixed)

### Job Processing Fails
- Job retries 2 times (configurable)
- If still fails: proposal marked as `failed`
- Logged to error logs
- Admin can review and take action

---

## Testing the Integration

1. **Enable SFTP sync** in Feature Configuration settings
2. **Configure SFTP connection** (host, auth, credentials, root folder)
3. **Set matching strategies** (select which metadata to use)
4. **Set sync frequency** (hourly/daily/weekly)
5. **Place test PDF files** on SFTP server
6. **Wait for scheduled job** OR **manually run**:
   ```bash
   php artisan queue:work
   ```
7. **Check proposals** in Payslips → SFTP Validator
8. **Validate proposals** (select dept/company/month/year)
9. **Monitor processing** in queue logs or Jobs table

---

## Key Classes & Files

| Component | Path | Purpose |
|-----------|------|---------|
| Service | `app/Services/SftpPayslipService.php` | SFTP operations & matching |
| Config Service | `app/Services/FeatureConfigurationService.php` | Settings caching |
| Model | `app/Models/PayslipMatchingProposal.php` | Proposal tracking |
| Fetch Job | `app/Jobs/FetchSftpPayslipsJob.php` | Scheduled SFTP fetch |
| Process Job | `app/Jobs/ProcessValidatedPayslipsJob.php` | Download & integrate |
| Livewire | `app/Livewire/Portal/Payslips/SftpPayslipValidator.php` | Admin validation UI |
| View | `resources/views/livewire/portal/payslips/sftp-validator.blade.php` | Validation interface |
| Migration | `database/migrations/2024_02_19_100000_create_payslip_matching_proposals_table.php` | Database schema |

---

## Integration Points

✅ **Correctly Integrated**:
- `PayslipSendingPlan::start($sendPayslipProcess)` called after validation
- Files downloaded to `storage/app/raw/` with proper naming
- `SendPayslipProcess` record created with all required fields
- Audit logging includes SFTP metadata
- Scheduler configured with dynamic frequency
- Multiple files handled independently
- Status transitions tracked: pending → validated → processed

✅ **Existing Pipeline Unchanged**:
- `SplitPdfJob` receives properly formatted `SendPayslipProcess`
- `RenameEncryptPdfJob` splits pages and creates Payslip records
- `SendPayslipJob` sends payslips via email/SMS
- All existing logic for matching, encryption, notification intact

---

## Notifications

When proposals are ready for validation:
- Subject: "New SFTP Payslip Files Ready for Validation"
- Recipients: Users with `manage-payslips` permission
- Channel: Mail + Database
- Action: Link to SFTP Validator page

When proposal processing fails:
- Logged to error logs
- Proposal marked as `failed` with reason
- Admin can take corrective action

