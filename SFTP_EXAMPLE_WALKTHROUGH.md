# SFTP Integration - Step-by-Step Example

## Real-World Scenario

**Setup**:
- SFTP Server: `sftp.company.com`
- Root Folder: `/payslips/`
- Companies: ACME Corp (Default), Widgets Inc
- Departments: Sales, Marketing, HR
- Sync Frequency: Daily at 02:00 AM

**Files on SFTP Server**:
```
/payslips/
├── 2024/02/
│   ├── SALES_202402.pdf          (120 pages - Sales payslips)
│   ├── MARKETING_202402.pdf      (45 pages - Marketing payslips)
│   └── HR_202402.pdf             (30 pages - HR payslips)
└── 2024/03/
    └── SALES_202403.pdf          (125 pages - Feb payslips)
```

---

## Step 1: Scheduled Fetch (02:00 AM, Daily)

**Kernel Schedule Triggers**:
```php
$schedule->job(new FetchSftpPayslipsJob())
    ->dailyAt('02:00')
    ->name('Fetch SFTP Payslips');
```

### What Happens

**FetchSftpPayslipsJob**:
```
1. Check if SFTP sync enabled → YES
2. Get matching strategies from settings:
   - department_code ✓
   - folder_structure ✓
   - fuzzy_timestamp ✓
3. Connect to SFTP
4. Recursively list all files in /payslips/
5. Find: SALES_202402.pdf, MARKETING_202402.pdf, HR_202402.pdf, SALES_202403.pdf
6. For each file:
   ├─ Check if already has pending/validated proposal → NO
   ├─ Parse metadata:
   │  └─ filename: SALES_202402.pdf
   │     ├─ department_code strategy → Finds "SALES" in filename
   │     │                               ├─ Searches DB for dept matching "SALES"
   │     │                               ├─ Finds: Sales Department
   │     │                               └─ Confidence: 0.95
   │     ├─ folder_structure strategy → /2024/02/
   │     │                               └─ No department code in folder
   │     └─ fuzzy_timestamp strategy → File timestamp=2024-02-XX
   │                                   └─ Suggests month=02, year=2024
   │
   ├─ Match to entities:
   │  └─ Returns ranked candidates:
   │     [
   │       { strategy: 'department_code', dept: 'Sales', confidence: 0.95 },
   │       { strategy: 'fuzzy_timestamp', month: 2, year: 2024, ... }
   │     ]
   │
   └─ Create PayslipMatchingProposal record:
      {
        id: 'uuid-001',
        file_path: '/2024/02/SALES_202402.pdf',
        file_name: 'SALES_202402.pdf',
        file_size: 125000000,
        file_timestamp: 2024-02-20 14:30:00,
        proposed_match: {
          candidates: [
            {
              type: 'department_code',
              strategy: 'department_code',
              confidence: 0.95,
              department_id: 'uuid-sales',
              company_id: 'uuid-acme',
              department_name: 'Sales',
              company_name: 'ACME Corp'
            },
            ... more candidates
          ],
          best_match: { /* highest confidence */ }
        },
        status: 'pending'
      }

7. Repeat for MARKETING_202402.pdf, HR_202402.pdf, SALES_202403.pdf
8. Create 4 PayslipMatchingProposal records total
9. All 4 files now in state: PENDING
10. Send notification to admins: "4 new SFTP payslip files ready for validation"
11. Log: "Created 4 new payslip file proposals for admin validation"
```

**Database After Step 1**:
```
payslip_matching_proposals:
├── id: uuid-001
│   ├── file_name: SALES_202402.pdf
│   ├── status: pending
│   ├── best_match: {dept: Sales, confidence: 0.95, ...}
│   └── created_at: 2024-02-21 02:05:00
├── id: uuid-002
│   ├── file_name: MARKETING_202402.pdf
│   ├── status: pending
│   └── ...
├── id: uuid-003
│   ├── file_name: HR_202402.pdf
│   ├── status: pending
│   └── ...
└── id: uuid-004
    ├── file_name: SALES_202403.pdf
    ├── status: pending
    └── ...
```

---

## Step 2: Admin Validation (09:30 AM)

Admin logs in and sees notification:
```
📬 New SFTP Payslip Files Ready for Validation
   4 new files are waiting for your approval
   [View Validator →]
```

Admin clicks link → Goes to `/portal/payslips/sftp-validator`

### Validator UI Shows

**Pending Tab** (4 items):
| File Name | Best Match | Confidence | Status | Actions |
|-----------|-----------|---------|--------|---------|
| SALES_202402.pdf | Sales Dept | 95% | pending | [Edit] [Reject] |
| MARKETING_202402.pdf | No Match | — | pending | [Edit] [Reject] |
| HR_202402.pdf | HR Dept | 88% | pending | [Edit] [Reject] |
| SALES_202403.pdf | Sales Dept | 95% | pending | [Edit] [Reject] |

### Admin Actions

**File 1: SALES_202402.pdf**
- Auto-matched to Sales department ✓
- Admin reviews candidates list
- Confirms: Department=Sales, Company=ACME Corp, Month=02, Year=2024
- Clicks: [Validate] → Proposal saved with status='validated'

**File 2: MARKETING_202402.pdf**
- No auto-match found
- Admin manually selects: Department=Marketing, Company=ACME Corp
- Enters: Month=02, Year=2024
- Clicks: [Validate] → Proposal saved with status='validated'

**File 3: HR_202402.pdf**
- Auto-matched to HR department ✓
- Admin confirms → status='validated'

**File 4: SALES_202403.pdf**
- Looks like a duplicate month (SALES already has Feb)
- Admin clicks: [Reject]
- Enters reason: "Duplicate file - Sales already processed for Feb 2024"
- Clicks: [Reject] → status='rejected', rejection_reason set

**Database After Step 2**:
```
payslip_matching_proposals:
├── uuid-001: status=validated, matched_to_department_id='uuid-sales', 
│             matched_month=2, matched_year=2024, matched_at=2024-02-21 09:35:00
├── uuid-002: status=validated, matched_to_department_id='uuid-marketing',
│             matched_month=2, matched_year=2024, matched_at=2024-02-21 09:36:00
├── uuid-003: status=validated, matched_to_department_id='uuid-hr',
│             matched_month=2, matched_year=2024, matched_at=2024-02-21 09:37:00
└── uuid-004: status=rejected, rejection_reason='Duplicate file - ...',
              matched_at=2024-02-21 09:38:00
```

---

## Step 3: Automatic Processing (09:35 AM - 09:40 AM)

### Queue Processing Begins

When admin saves first validation, `ProcessValidatedPayslipsJob` is dispatched:

**For uuid-001 (SALES_202402.pdf)**:

```
ProcessValidatedPayslipsJob({
  id: uuid-001,
  file_path: '/2024/02/SALES_202402.pdf',
  matched_to_department_id: 'uuid-sales',
  matched_month: 2,
  matched_year: 2024
})

1. Verify proposal status = 'validated' ✓
2. Download file from SFTP:
   ├─ Connect to SFTP
   ├─ Download /2024/02/SALES_202402.pdf
   ├─ Save to: /var/www/storage/app/processor/raw/sftp_sales_02_2024_1708512900.pdf
   └─ Size: ~125 MB, took 3.2 seconds
3. Create SendPayslipProcess record:
   {
     id: 'uuid-process-001',
     user_id: 'admin-user-id',  // Who validated
     department_id: 'uuid-sales',
     company_id: 'uuid-acme',
     month: 2,
     year: 2024,
     raw_file: '/var/www/storage/app/processor/raw/sftp_sales_02_2024_1708512900.pdf',
     destination_directory: 'dept_uuid-sales_20240221093505',
     status: 'processing',
     percentage_completion: 0,
     created_at: 2024-02-21 09:35:00
   }
4. Log to audit_logs:
   {
     event: 'sftp_payslip_processing_started',
     description: 'SFTP payslip SALES_202402.pdf queued for processing',
     auditable: SendPayslipProcess (uuid-process-001),
     metadata: {
       sftp_proposal_id: uuid-001,
       sftp_file_path: /2024/02/SALES_202402.pdf
     }
   }
5. Update proposal:
   {
     id: uuid-001,
     status: 'processed',  // ← Changed!
     processed_at: 2024-02-21 09:35:15
   }
6. Dispatch the standard pipeline:
   PayslipSendingPlan::start(SendPayslipProcess with uuid-process-001)
```

### Phase 3a: SplitPdfJob (09:35:15 AM)

```
Bus::chain() starts execution:

SplitPdfJob({
  raw_file: '/full/path/to/raw/sftp_sales_02_2024_1708512900.pdf',
  destination_directory: 'dept_uuid-sales_20240221093505',
  month: 2,
  year: 2024
})

1. Read raw_file (125 MB PDF with ~120 pages)
2. Use pdfseparate command:
   pdfseparate /full/path/to/raw/sftp_sales_02_2024_1708512900.pdf \
      /var/www/storage/app/processor/splitted/dept_uuid-sales_20240221093505/page_%d.pdf
3. Creates:
   ├─ storage/app/processor/splitted/dept_uuid-sales_20240221093505/page_1.pdf
   ├─ storage/app/processor/splitted/dept_uuid-sales_20240221093505/page_2.pdf
   ├─ ...
   └─ storage/app/processor/splitted/dept_uuid-sales_20240221093505/page_120.pdf
4. Update SendPayslipProcess:
   status: 'splitting'
   percentage_completion: 10%
5. Completed in: 1.5 seconds
6. Queue next job: RenameEncryptPdfJob
```

### Phase 3b: RenameEncryptPdfJob (09:35:17 AM)

```
RenameEncryptPdfJob({
  send_payslip_process: uuid-process-001,
  pages_directory: 'dept_uuid-sales_20240221093505'
})

1. For each split page (page_1.pdf → page_120.pdf):
   ├─ Extract page and look for employee matching by matricule
   │  (Assuming each page header/footer contains employee ID)
   ├─ Match page_1.pdf → Employee "EMP001" (John Smith, Sales)
   ├─ Match page_2.pdf → Employee "EMP002" (Jane Doe, Sales)
   ├─ ... (continue for all 120 pages)
   │
   ├─ For each matched page:
   │  ├─ Create Payslip record:
   │  │  {
   │  │    id: 'uuid-payslip-001',
   │  │    user_id: 'emp-001-user-id',
   │  │    department_id: 'uuid-sales',
   │  │    company_id: 'uuid-acme',
   │  │    from_month: 2,
   │  │    from_year: 2024,
   │  │    file: 'encrypted_payslips/page_1_encrypted.pdf',
   │  │    encryption_status: 'encrypted',
   │  │    email_sent_status: 'pending',
   │  │    sms_sent_status: 'pending'
   │  │  }
   │  │
   │  ├─ Encrypt page:
   │  │  ├─ Read: storage/app/processor/splitted/.../page_1.pdf
   │  │  ├─ Encrypt with employee's key
   │  │  ├─ Save to: storage/app/processor/encrypted_payslips/{emp_id}/page_1_encrypted.pdf
   │  │  └─ Delete unencrypted page
   │  │
   │  └─ Update Payslip.file field with encrypted path
   │
   └─ Loop: 120 pages → 120 Payslip records created & encrypted

2. Update SendPayslipProcess:
   status: 'encrypting'
   percentage_completion: 50%
3. Completed in: 12 seconds (encryption of 120 pages)
4. Queue next job: SendPayslipJob
```

### Phase 3c: SendPayslipJob (09:35:30 AM)

```
SendPayslipJob({
  send_payslip_process: uuid-process-001,
  department_id: uuid-sales
})

1. Get all payslips for this batch:
   ├─ Find all Payslip records with:
   │  ├─ department_id = uuid-sales
   │  ├─ from_month = 2
   │  ├─ from_year = 2024
   │  └─ email_sent_status = 'pending'
   │
   ├─ Found 120 Payslip records
   │
   └─ Chunk into batches (e.g., 10 per batch):
      ├─ Batch 1: emp_001, emp_002, ..., emp_010
      ├─ Batch 2: emp_011, emp_012, ..., emp_020
      └─ ... (12 batches total)

2. For each batch:
   ├─ Check email preferences per employee
   ├─ Send via email:
   │  ├─ Subject: "Your February 2024 Payslip"
   │  ├─ Attach: encrypted_payslips/{emp_id}/page_1_encrypted.pdf
   │  ├─ Recipient: emp_001@company.com
   │  └─ Queue: emails
   │
   ├─ Check SMS preferences per employee
   ├─ Send via SMS:
   │  ├─ Message: "Your payslip for Feb 2024 is ready. Download link: ..."
   │  └─ Queue: messaging
   │
   ├─ Update Payslip:
   │  ├─ email_sent_status: 'sent'
   │  └─ delivered_at: timestamp
   │
   └─ Wait slight delay before next batch (to avoid rate limits)

3. Update SendPayslipProcess:
   status: 'sent'
   percentage_completion: 100%
4. Job completed: All 120 employees notified
5. Admin can see: "120/120 payslips sent"
```

---

## Step 4: Completion (09:35:45 AM)

### Database State After Processing

```
payslip_matching_proposals:
└── uuid-001:
    ├─ status: 'processed'       ← ✓ Complete
    ├─ processed_at: 2024-02-21 09:35:15
    └─ file: SALES_202402.pdf (all handled)

send_payslip_processes:
└── uuid-process-001:
    ├─ status: 'sent'            ← ✓ Complete
    ├─ percentage_completion: 100%
    ├─ user_id: admin-user-id
    └─ created_at: 2024-02-21 09:35:00

payslips:
├─ EMP001 Feb 2024: email_sent_status='sent', sms_sent_status='sent'
├─ EMP002 Feb 2024: email_sent_status='sent', sms_sent_status='sent'
├─ ...
└─ EMP120 Feb 2024: email_sent_status='sent', sms_sent_status='sent'

audit_logs:
├─ sftp_payslip_processing_started (uuid-process-001)
├─ payslips_split (120 pages)
├─ payslips_encrypted (120 payslips)
└─ payslips_sent (120 employees)
```

### Employees Receive Payslips

```
From: payroll@company.com
To: emp_001@company.com
Subject: Your February 2024 Payslip

Hello John Smith,

Your payslip for February 2024 is attached.
Please keep it safe for your records.

[ATTACHMENT: page_1_encrypted.pdf]

---

Similarly, all 120 Sales employees receive their payslips.
```

---

## Parallel Processing

If admin validates multiple proposals quickly:

```
09:35 - Admin validates SALES_202402 → ProcessValidatedPayslipsJob dispatched
  ├─ Queue: ProcessValidatedPayslipsJob (SALES)
  
09:36 - Admin validates MARKETING_202402 → ProcessValidatedPayslipsJob dispatched
  ├─ Queue: ProcessValidatedPayslipsJob (SALES)
  └─ Queue: ProcessValidatedPayslipsJob (MARKETING)

09:37 - Admin validates HR_202402 → ProcessValidatedPayslipsJob dispatched
  ├─ Queue: ProcessValidatedPayslipsJob (SALES)
  ├─ Queue: ProcessValidatedPayslipsJob (MARKETING)
  └─ Queue: ProcessValidatedPayslipsJob (HR)
```

Queue worker processes all three jobs in parallel:
```
php artisan queue:work --queue=processing

[Processing Job 1] SplitPdfJob(SALES) → 120 pages
[Processing Job 2] SplitPdfJob(MARKETING) → 45 pages
[Processing Job 3] SplitPdfJob(HR) → 30 pages

[Chained] RenameEncryptPdfJob(SALES, MARKETING, HR) running in parallel
[Chained] SendPayslipJob(SALES, MARKETING, HR) running in parallel
```

All three departments' payslips processed independently and simultaneously.

---

## Summary

This example shows exactly what happens when:
1. **SFTP files are discovered** → Proposals created
2. **Admin validates proposals** → Files matched to department/company/month/year
3. **Processing job runs** → Files downloaded and entered into standard pipeline
4. **Standard pipeline executes** → Split → Encrypt → Send (existing workflow)
5. **Employees get payslips** → Normal delivery via email/SMS

The SFTP integration seamlessly plugs into your existing system at the perfect integration point: `PayslipSendingPlan::start()`.

