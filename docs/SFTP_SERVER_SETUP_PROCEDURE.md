# SFTP Server Setup Procedure for Payslip Validator

This document describes how to set up an SFTP server so that the Ciblerh **SFTP Payslip Validator** can connect, list, and download payslip PDF files. The application then creates matching proposals for admin validation and processes them through the existing payslip pipeline.

**Code reference**: Connection and behaviour are implemented in `app/Services/SftpPayslipService.php`, settings in `app/Livewire/Portal/Settings/Index.php` (Feature Configuration), fetch job `app/Jobs/FetchSftpPayslipsJob.php`, and schedule in `app/Console/Kernel.php`.

---

## 1. Prerequisites

- A Linux server (or VM) with SSH/SFTP access
- Root or sudo access to create users and directories
- (Optional) Dedicated hostname, e.g. `sftp.payslips.yourcompany.com`

---

## 2. SFTP User (Required) and Optional Lockdown

You need **some** user account the application can use to connect over SFTP and read the payslip directory. The application does not require a special “SFTP-only” user; any user that can log in via SFTP and read the target directory is sufficient.

### 2.0 Generate credentials from the application (recommended flow)

You can create the SFTP user credentials from the Ciblerh admin UI and then create the same user on your SFTP server with those credentials.

1. In Ciblerh: go to **Settings → Feature Configuration** and enable **SFTP Synchronization**.
2. In the **SFTP User Credentials** section, click **Generate SFTP User Credentials**.
3. The app generates a username (e.g. `ciblerh_payslip_xxxxxxxx`) and a strong password, saves them to settings, and **shows the password once** in the page.
4. Copy the username and password (use the **Copy** buttons). Click **I've copied the credentials** when done—the password will no longer be displayed (it remains stored for the connection).
5. On your SFTP server, create a system user with that exact username and password (see sections 2.1–2.3 below). Use the same username and password you copied.
6. Back in Ciblerh, enter **SFTP Host** (and optionally **Root Directory**, **Port**), then run **Test SFTP Connection** and **Save**.

The connection form is already filled with the generated username and password; you only need to add host (and root/port if needed) and create the user on the server with the same credentials.

**Option A – Use an existing user**  
If you already have a user (e.g. a shared “payslips” or “upload” account) that can SFTP in and read the folder where you put payslip PDFs, you can use that. Set that username and password (or SSH key) in the app’s SFTP settings. No extra user creation needed.

**Option B – Create a dedicated SFTP-only user (recommended for security)**  
Creating a dedicated user with no shell and chrooted to the payslip directory limits damage if credentials leak: the account cannot get a shell or access the rest of the filesystem. This is a security best practice, not an application requirement.

### 2.1 Create the system user (Option B or after generating credentials in UI)

If you generated credentials in the app (section 2.0), use the **exact username** (e.g. `ciblerh_payslip_xxxxxxxx`) and **password** you copied. Otherwise choose a username (e.g. `sftp_payslips`).

```bash
# Create user with no login shell (SFTP only). Replace USERNAME with the app-generated username or e.g. sftp_payslips
sudo useradd -m -s /usr/sbin/nologin USERNAME

# Set the password (use the one from the app if you generated credentials there)
sudo passwd USERNAME
```

### 2.2 Create directory structure for payslips

Choose a root directory that the application will use (e.g. `/payslips` or `/home/USERNAME/payslips`). Replace `USERNAME` with your SFTP user (e.g. `ciblerh_payslip_xxxxxxxx` or `sftp_payslips`).

**Option A – User home as root**

```bash
sudo mkdir -p /home/USERNAME/payslips
sudo chown root:root /home/USERNAME
sudo chmod 755 /home/USERNAME
sudo chown USERNAME:USERNAME /home/USERNAME/payslips
sudo chmod 750 /home/USERNAME/payslips
```

Then in the application, set **Root Directory** to `/payslips` (relative to this user’s home).

**Option B – Shared path (e.g. /var/sftp/payslips)**

```bash
sudo mkdir -p /var/sftp/payslips
sudo chown root:root /var/sftp
sudo chmod 755 /var/sftp
sudo chown USERNAME:USERNAME /var/sftp/payslips
sudo chmod 750 /var/sftp/payslips
```

In the application, set **Root Directory** to `/payslips` (or `/` if the SFTP user’s root is already `/var/sftp`).

### 2.3 Restrict user to SFTP (OpenSSH chroot) – optional

If you created a dedicated user and want to confine them to one directory:

```bash
sudo nano /etc/ssh/sshd_config
```

Add at the **end** of the file (replace USERNAME and path if needed):

```
Match User USERNAME
    ChrootDirectory /home/USERNAME
    ForceCommand internal-sftp
    AllowTcpForwarding no
    X11Forwarding no
```

If you used Option B, set:

- `ChrootDirectory /var/sftp`

Then restart SSH:

```bash
sudo systemctl restart sshd
# or
sudo service ssh restart
```

**Important:** The chroot directory must be owned by `root` and not writable by the SFTP user. Only subdirectories (e.g. `payslips`) should be owned by the SFTP user so they can read (and optionally upload) files there.

**Summary:** You do **not** need an SFTP-only user. You need a user the app can use to connect and read the payslip path; using a chrooted, no-shell user is optional and recommended for security.

---

## 3. Authentication: Password or SSH Key

The application supports both.

### 3.1 Password authentication

- Already set with `sudo passwd sftp_payslips`.
- In Ciblerh: **Settings → Feature Configuration → SFTP** → Auth type **Password**, then set **Username** and **Password**.

### 3.2 SSH key authentication (recommended)

On the **server** (as the user that will run the Laravel app, or on your dev machine):

```bash
# Generate key pair if you don’t have one
ssh-keygen -t ed25519 -f ~/.ssh/sftp_payslips -N ""
```

Copy the **public** key to the SFTP server:

```bash
ssh-copy-id -i ~/.ssh/sftp_payslips.pub sftp_payslips@sftp.example.com
```

If the user is chrooted, the server needs the public key in the chroot. For example, for `ChrootDirectory /home/sftp_payslips`:

```bash
# On the server
sudo mkdir -p /home/sftp_payslips/.ssh
sudo nano /home/sftp_payslips/.ssh/authorized_keys
# Paste the content of sftp_payslips.pub (one line)
sudo chown -R sftp_payslips:sftp_payslips /home/sftp_payslips/.ssh
sudo chmod 700 /home/sftp_payslips/.ssh
sudo chmod 600 /home/sftp_payslips/.ssh/authorized_keys
```

On the **Laravel server**, the app will use the **private** key file. Copy the private key there (e.g. `storage/app/sftp_payslips` or a path outside the web root) and restrict permissions:

```bash
chmod 600 /path/to/sftp_payslips
```

In Ciblerh: **Auth type** → **SSH Key**, **Username** = `sftp_payslips`, **Private Key Path** = full path to that file; set **Passphrase** only if the key has one.

---

## 4. Recommended Directory and Naming on SFTP

The app lists all files under the configured root via `listContents('/', true)` and only processes **`.pdf`** files (see `FetchSftpPayslipsJob`: `str_ends_with(strtolower($file['basename']), '.pdf')`). Matching is done in `SftpPayslipService::parsePayslipMetadata()` and `matchPayslipToEntities()` using the strategies enabled in Settings.

### 4.1 Example layout

```
/payslips/                    ← Root directory (sftp_root in Settings; default /payslips)
├── SALES/                    ← First path segment used by "Folder Structure" strategy
│   ├── DEPT_SALES_202401.pdf
│   └── DEPT_SALES_202402.pdf
├── MARKETING/
│   └── DEPT_MARKETING_202402.pdf
└── 2024/
    └── 02/
        └── D_HR_202402.pdf
```

### 4.2 Naming and path rules (from code)

- **Department code in filename** (`department_code` strategy)  
  The service looks for a prefix **`dept`**, **`department`**, or **`d`** followed by underscore or hyphen, then the code.  
  Examples: `DEPT_SALES_202402.pdf`, `D_HR_202401.pdf`, `department_MARKETING.pdf`.  
  Plain `SALES_202402.pdf` does **not** match; use e.g. `D_SALES_202402.pdf`.

- **Company code in filename** (`company_code` strategy)  
  Prefix **`comp`**, **`company`**, or **`c`** + separator + code.  
  Example: `COMP_ACME_202402.pdf`, `c_ACME.pdf`.

- **Employee ID in filename** (`employee_id` strategy)  
  Prefix **`emp`**, **`employee`**, or **`e`** + optional separator + digits.  
  Example: `EMP_12345.pdf`, `e_12345.pdf`.

- **Folder structure** (`folder_structure` strategy)  
  The **first path segment** under the root is used as the department identifier (e.g. path `SALES/202402.pdf` → folder_department = `SALES`). So organising files in folders named by department (or code) works well.

- **File timestamps** (`fuzzy` strategy)  
  Uses the file’s last-modified timestamp to suggest month and year.

Consistent use of these patterns gives the best automatic suggestions in the SFTP Validator.

---

## 5. Application Configuration (Ciblerh)

Configured in **Settings → Feature Configuration** (SFTP Payslip Integration). Values are stored on the `settings` table and read by `SftpPayslipService` and `FetchSftpPayslipsJob`.

1. Log in as a user with access to **Settings** (e.g. admin).
2. Go to **Settings → Feature Configuration** (SFTP Payslip Integration section).
3. **Enable SFTP Synchronization** (toggle on) – corresponds to `sftp_sync_enabled`.
4. **Connection settings** (see `app/Services/SftpPayslipService` constructor):
   - **SFTP Host** (`sftp_host`): hostname or IP of the SFTP server.
   - **SFTP Port** (`sftp_port`): default `22`.
   - **Authentication** (`sftp_auth_type`): `password` or `ssh_key`.
   - **Username** (`sftp_username`), **Password** (`sftp_password`) for password auth.
   - For SSH key: **Private Key Path** (`sftp_private_key_path`) – full path on Laravel server – and optional **Passphrase** (`sftp_passphrase`).
   - **Root Directory** (`sftp_root`): remote path used as listing root (default `/payslips`). The app calls `listContents('/', true)` relative to this root.
5. **Sync frequency** (`sftp_sync_frequency`): `hourly`, `daily`, or `weekly`. Used in `app/Console/Kernel.php` to schedule `FetchSftpPayslipsJob` (no custom time for daily – Laravel’s `daily()` runs at midnight).
6. **Matching strategies** (`sftp_matching_strategies`): at least one required. Values: `employee_id`, `department_code`, `company_code`, `folder_structure`, `fuzzy`. Must match the naming/folder rules in §4.2.
7. Click **Test SFTP Connection** (uses `SftpPayslipService::testConnection()`).
8. Save the configuration.

---

## 6. Scheduler and Queue (Laravel)

- **Schedule**: In `app/Console/Kernel.php`, `FetchSftpPayslipsJob` is scheduled with `->{$this->getSftpSyncFrequency()}()` and `->when(fn() => $this->isSftpSyncEnabled())`. Frequency comes from `FeatureConfigurationService::getSftpConfig()` (`sync_frequency`: `hourly`, `daily`, or `weekly`). So the job runs hourly, daily at midnight, or weekly when SFTP sync is enabled.
- Ensure the scheduler runs (cron):

  ```bash
  * * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
  ```

- The job is queued on **`processing`** (`$this->onQueue('processing')`). Run a worker for that queue:

  ```bash
  php artisan queue:work --queue=processing
  ```

---

## 7. Validating the Setup

1. **Upload a test PDF** to the SFTP root (or a subfolder). Use a name that matches your enabled strategies (e.g. `D_SALES_202402.pdf` if using department_code, or put it in a folder like `SALES/` if using folder_structure).
2. **Trigger a fetch** (or wait for the next scheduled run):
   ```bash
   php artisan tinker
   >>> dispatch(new \App\Jobs\FetchSftpPayslipsJob());
   ```
   The job only runs when `sftp_sync_enabled` is true and at least one `sftp_matching_strategies` is set; it skips files that already have a pending/validated proposal for the same `file_path`.
3. In the app, open **Payslips → SFTP Validator** (route `portal.payslips.sftp-validator`, permission `manage-payslips`).
4. You should see a **pending** proposal for the file. Assign Department, Company, Month, Year and validate. On validate, `ProcessValidatedPayslipsJob` is dispatched; it uses the already-downloaded file (`local_file_path` on the proposal) and starts the pipeline via `PayslipSendingPlan::start()`.
5. After validation, the file is processed through the normal payslip pipeline (split → encrypt → send).

---

## 8. Security Checklist

- [ ] SFTP user has no shell (`/usr/sbin/nologin`) and is chrooted.
- [ ] Only the payslip directory is writable by the SFTP user; chroot directory is owned by root.
- [ ] Prefer SSH key auth and protect the private key (permissions `600`, path outside web root).
- [ ] Firewall allows SSH/SFTP (port 22) only from the Laravel server IP (or VPN).
- [ ] Passwords or keys are stored in Settings (or env) and not committed to version control.

---

## 9. Troubleshooting

| Issue | What to check |
|-------|----------------|
| Connection refused | Port 22 open? Correct host? |
| Permission denied (publickey) | Correct key path and ownership on Laravel server; `authorized_keys` in chroot. |
| Permission denied (password) | Password correct; `PasswordAuthentication yes` in `sshd_config` if using password. |
| No files listed | Root directory in app matches where PDFs are (e.g. `/payslips`); user has read access. |
| Proposals not created | Only `.pdf` files are processed; check sync frequency and queue worker; see `storage/logs/laravel.log`. |

For full workflow details (proposals, validation, processing), see **SFTP_INTEGRATION_WORKFLOW.md** and **SFTP_EXAMPLE_WALKTHROUGH.md** in the project root.
