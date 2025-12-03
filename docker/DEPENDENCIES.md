# Docker Dependencies Checklist

This document lists all dependencies required for the Ciblerh Payroll application and confirms their installation in the Docker setup.

## System Dependencies

### PDF Processing Tools
- ✅ **poppler-utils** - Provides `pdftotext` and `pdfseparate` binaries
  - Used by: `SplitPdfJob`, `RenameEncryptPdfJob`, `SinglePayslipProcessingJob`
  - Path: `/usr/bin/pdftotext`, `/usr/bin/pdfseparate`
  - Environment: `PDFTOTEXT_PATH`, `PDFSEPARATE_PATH`

- ✅ **pdftk** - PDF toolkit for encryption and manipulation
  - Used by: `RenameEncryptPdfJob`, `SendSinglePayslipJob`, `ResendFailedPayslipJob`
  - Path: `/usr/bin/pdftk`
  - Environment: `PDFTK_PATH`

### Database
- ✅ **MySQL 8.0** - Database server
  - Connection: `mysql:3306`
  - Environment: `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

### Cache & Queue
- ✅ **Redis 7** - Cache and queue backend
  - Connection: `redis:6379`
  - Environment: `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`
  - Used by: Laravel Horizon, Queue system, Cache, Sessions

### Web Server
- ✅ **Nginx** - Web server
  - Port: `80` (HTTP), `443` (HTTPS)

### Timezone
- ✅ **tzdata** - Timezone data
  - Default: `Africa/Douala`
  - Environment: `TZ`

### Network Tools
- ✅ **netcat-openbsd** - Network connectivity testing
  - Used by: Health checks in entrypoint script

## PHP Extensions

### Core Extensions
- ✅ **pdo_mysql** - MySQL database driver
- ✅ **mbstring** - Multibyte string handling (required by Laravel)
- ✅ **xml** - XML parsing (required by PhpSpreadsheet/Maatwebsite Excel)
- ✅ **fileinfo** - File type detection (required by Laravel filesystem)
- ✅ **zip** - ZIP archive support (required by PhpSpreadsheet)
- ✅ **intl** - Internationalization support
- ✅ **opcache** - OPcache for performance

### Image Processing
- ✅ **gd** - Image manipulation (required by DomPDF, Excel charts)
  - Configured with: `--with-freetype --with-jpeg`

### Additional Extensions
- ✅ **exif** - EXIF metadata reading
- ✅ **pcntl** - Process control (used by queue workers)
- ✅ **bcmath** - Arbitrary precision mathematics
- ✅ **soap** - SOAP client support (for AWS services)

### PECL Extensions
- ✅ **redis** - Redis PHP extension (alternative to Predis)
  - Note: Application uses `predis/predis` package, but extension is available

## PHP Packages (Composer)

### Core Framework
- ✅ **laravel/framework** ^10.10
- ✅ **laravel/horizon** ^5.21 - Queue monitoring
- ✅ **laravel/sanctum** ^3.2 - API authentication
- ✅ **livewire/livewire** ^3.0 - Frontend framework

### PDF Processing
- ✅ **barryvdh/laravel-dompdf** ^2.0 - PDF generation
- ✅ **escarter/poppler-wrapper-php** ^1.0 - Poppler wrapper
- ✅ **mikehaertl/php-pdftk** ^0.10.0 - PDF toolkit wrapper

### Excel Processing
- ✅ **maatwebsite/excel** ^3.1 - Excel import/export
  - Requires: PhpSpreadsheet (auto-installed)
  - Dependencies: xml, zip, gd, fileinfo extensions

### Queue & Cache
- ✅ **predis/predis** 1.0 - Redis client
- ✅ **laravel/horizon** ^5.21 - Queue dashboard

### AWS Services
- ✅ **aws/aws-sdk-php** ^3.0 - AWS SDK
  - Used for: S3 storage, SES email, SNS SMS

### Other Services
- ✅ **twilio/sdk** ^7.11 - SMS service
- ✅ **guzzlehttp/guzzle** ^7.2 - HTTP client
- ✅ **spatie/laravel-permission** ^5.11 - Permissions

## Node.js Dependencies

- ✅ **Node.js 18.x (LTS)** - JavaScript runtime
- ✅ **npm** - Package manager
- ✅ **vite** ^4.0.0 - Build tool
- ✅ **laravel-vite-plugin** ^0.8.0 - Laravel integration
- ✅ **axios** ^1.1.2 - HTTP client

## Job Dependencies

### PDF Processing Jobs
All PDF processing jobs require:
- ✅ `pdftk` binary
- ✅ `pdftotext` binary (from poppler-utils)
- ✅ `pdfseparate` binary (from poppler-utils)

**Jobs:**
- `SplitPdfJob` - Uses `pdfseparate`
- `RenameEncryptPdfJob` - Uses `pdftotext` and `pdftk`
- `SendSinglePayslipJob` - Uses `pdftk`
- `SinglePayslipProcessingJob` - Uses `pdftotext` and `pdftk`
- `ResendFailedPayslipJob` - Uses `pdftotext` and `pdftk`
- `SplitPdfSingleEmployee` - Uses `pdfseparate`

### Excel Export Jobs
All Excel export jobs require:
- ✅ `maatwebsite/excel` package
- ✅ `xml` PHP extension
- ✅ `zip` PHP extension
- ✅ `fileinfo` PHP extension

**Jobs:**
- `PayslipReportJob`
- `AbsencesExportJob`
- `AdvanceSalaryExportJob`
- `ChecklogReportJob`
- `CompanyExportJob`
- `DepartmentExportJob`
- `EmployeeExportJob`
- `OvertimeReportJob`
- `ServiceExportJob`

### Email Jobs
All email jobs require:
- ✅ Mail configuration (SMTP, Mailgun, SES, etc.)
- ✅ Storage access for PDF attachments

**Jobs:**
- `SendPayslipJob`
- `RetryPayslipEmailJob`
- `SendSinglePayslipJob`
- `ResendFailedPayslipJob`

## Queue Configuration

### Queue Driver
- ✅ **Redis** - Primary queue driver
  - Environment: `QUEUE_CONNECTION=redis`
  - Client: `predis` (via `predis/predis` package)

### Queue Workers
- ✅ **Laravel Horizon** - Primary queue worker with dashboard
  - Service: `horizon`
  - Command: `php artisan horizon`
  - Dashboard: `/horizon`

- ✅ **Standard Queue Worker** - Alternative worker
  - Service: `queue` (optional, use with `--profile queue-worker`)
  - Command: `php artisan queue:work redis`

### Scheduler
- ✅ **Laravel Scheduler** - Scheduled task runner
  - Service: `scheduler`
  - Command: `php artisan schedule:work`
  - Tasks:
    - `wima:clean-processed` - Daily at 01:30
    - `wima:leave-update-process` - Last day of month at 23:50
    - `wima:wish-happy-birthday` - Daily at 08:00

## Storage Disks

All storage disks are configured and accessible:
- ✅ `local` - Default storage
- ✅ `public` - Public storage
- ✅ `attachments` - Email attachments
- ✅ `splitted` - Split PDF files
- ✅ `modified` - Encrypted/renamed PDFs
- ✅ `raw` - Original PDF files
- ✅ `s3` - AWS S3 (if configured)

## Verification

To verify all dependencies are installed:

```bash
# Check PHP extensions
docker-compose exec app php -m

# Check system binaries
docker-compose exec app which pdftk
docker-compose exec app which pdftotext
docker-compose exec app which pdfseparate

# Check Composer packages
docker-compose exec app composer show

# Check Node.js
docker-compose exec app node --version
docker-compose exec app npm --version

# Test PDF tools
docker-compose exec app pdftk --version
docker-compose exec app pdftotext -v
docker-compose exec app pdfseparate -v
```

## Missing Dependencies

If any dependency is missing, check:
1. Dockerfile installation steps
2. Composer package requirements
3. Environment variable configuration
4. Service health checks in docker-compose.yml

