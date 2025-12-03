# Test Coverage Plan

This document outlines the comprehensive test coverage plan for the Ciblerh Payroll and Check-in Management System.

## Test Structure

```
tests/
├── Unit/
│   ├── Jobs/
│   │   ├── SendPayslipJobTest.php ✅
│   │   ├── RetryPayslipEmailJobTest.php ✅
│   │   ├── RenameEncryptPdfJobTest.php
│   │   ├── SplitPdfJobTest.php
│   │   ├── PayslipSendingPlanTest.php
│   │   └── SinglePayslipPlanTest.php
│   ├── Models/
│   │   ├── UserTest.php
│   │   ├── PayslipTest.php
│   │   ├── CompanyTest.php
│   │   ├── DepartmentTest.php
│   │   └── SendPayslipProcessTest.php
│   └── Utils/
│       └── HelpersTest.php
├── Feature/
│   ├── Livewire/
│   │   ├── Payslips/
│   │   │   ├── DetailsTest.php
│   │   │   └── IndexTest.php
│   │   ├── Employees/
│   │   │   ├── IndexTest.php
│   │   │   └── AllTest.php
│   │   └── Companies/
│   │       └── IndexTest.php
│   └── Jobs/
│       └── PayslipSendingIntegrationTest.php
```

## Coverage Goals

### Target: 90%+ Code Coverage

### Priority 1: Critical Business Logic (100% coverage)
- ✅ SendPayslipJob - Email sending, bounce detection, preferences
- ✅ RetryPayslipEmailJob - Retry logic, bounce handling
- PayslipSendingPlan - Process orchestration
- RenameEncryptPdfJob - PDF encryption
- Helpers - createPayslipRecord, sendSmsAndUpdateRecord, validateEmail

### Priority 2: Models (90%+ coverage)
- User - Email preferences, bounce tracking, relationships
- Payslip - Status management, scopes, relationships
- SendPayslipProcess - Process management
- Company, Department, Service - CRUD operations

### Priority 3: Livewire Components (80%+ coverage)
- Payslips/Details - Bulk resend, CRUD operations
- Payslips/Index - Process listing
- Employees/Index, All - Employee management
- Companies, Departments, Services - CRUD

### Priority 4: Jobs (85%+ coverage)
- SplitPdfJob - PDF splitting
- DownloadJobs - Export functionality
- SinglePayslipPlan - Single payslip processing

## Test Categories

### Unit Tests
- Individual class/method testing
- Mock external dependencies
- Fast execution
- Isolated testing

### Feature Tests
- Integration testing
- End-to-end workflows
- Database interactions
- Real dependencies where appropriate

## Key Test Scenarios

### Email Sending
- ✅ Email notifications disabled
- ✅ Email bounced previously
- ✅ Alternative email usage
- ✅ No email address available
- ✅ Email send success
- ✅ Email send failure with retry
- ✅ Max retries reached
- ✅ Email bounce detection
- ✅ Swift exceptions handling

### PDF Processing
- PDF splitting by matricule
- PDF encryption
- Multi-page PDF combining
- Matricule not found handling
- File existence validation

### Payslip Process
- Process creation
- Batch processing
- Reconciliation of unmatched employees
- Process status updates
- Failure handling

### Livewire Components
- Component mounting
- Data loading
- User interactions
- Form submissions
- Bulk operations
- Permissions checking

## Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run with minimum coverage threshold
php artisan test --coverage --min=90

# Run specific test file
php artisan test tests/Unit/Jobs/SendPayslipJobTest.php

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

## Test Data Setup

- Use factories for consistent test data
- Use RefreshDatabase trait for clean state
- Mock external services (Mail, Storage, SMS)
- Use fake storage and mail drivers

## Notes

- Tests should be independent and repeatable
- Use descriptive test names
- Group related tests with Pest's `describe()` or `test()` blocks
- Mock external dependencies to ensure fast execution
- Test both success and failure paths
- Test edge cases and boundary conditions


