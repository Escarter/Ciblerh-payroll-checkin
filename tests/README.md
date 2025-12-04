# Test Suite Documentation

## Overview

This test suite provides comprehensive coverage for the Ciblerh Payroll and Check-in Management System, targeting **90%+ code coverage**.

## Test Structure

### Unit Tests (`tests/Unit/`)

#### Jobs
- **SendPayslipJobTest.php** - 10 tests covering:
  - Email notification preferences
  - Email bounce handling
  - Alternative email usage
  - Retry mechanism
  - Exception handling
  
- **RetryPayslipEmailJobTest.php** - 14 tests covering:
  - Retry logic for failed emails
  - Bounce detection
  - File validation
  - Exception handling
  - Max retry limits

- **PayslipSendingPlanTest.php** - 5 tests covering:
  - Unmatched employee reconciliation
  - Process failure handling
  - Duplicate record prevention

#### Models
- **PayslipTest.php** - 13 tests covering:
  - Model relationships
  - Status attributes
  - Scopes (successful, failed, search)
  - Accessor methods

- **UserTest.php** - 10 tests covering:
  - Model attributes
  - Relationships
  - Email preferences
  - Status management

- **SendPayslipProcessTest.php** - 9 tests covering:
  - Relationships
  - Soft deletes
  - Process attributes

- **CompanyTest.php** - 6 tests covering:
  - Relationships
  - Soft deletes
  - Attributes

- **DepartmentTest.php** - 6 tests covering:
  - Relationships
  - Soft deletes
  - Attributes

#### Utils
- **HelpersTest.php** - 9 tests covering:
  - Email validation
  - Payslip record creation
  - Phone number handling

### Feature Tests (`tests/Feature/`)

#### Livewire Components
- **Payslips/DetailsTest.php** - 18 tests covering:
  - Component mounting
  - Payslip CRUD operations
  - Bulk operations (resend, delete, restore)
  - File downloads
  - Tab switching
  - Unmatched employees display

#### Integration Tests
- **Jobs/PayslipSendingIntegrationTest.php** - 3 tests covering:
  - End-to-end payslip sending process
  - Multiple unmatched employees handling
  - Process failure reason updates

- **EmailBounceHandlingTest.php** - 3 tests covering:
  - Bounce detection and marking
  - Persistence across attempts
  - Prevention of future sends

- **EmailNotificationPreferencesTest.php** - 5 tests covering:
  - Notification preferences enforcement
  - Alternative email usage
  - Default behavior

## Test Statistics

- **Total Test Files**: 15
- **Total Test Cases**: 100+ tests
- **Coverage Target**: 90%+

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run with Coverage
```bash
php artisan test --coverage
```

### Run with Minimum Coverage Threshold
```bash
php artisan test --coverage --min=90
```

### Run Specific Test Suite
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature
```

### Run Specific Test File
```bash
php artisan test tests/Unit/Jobs/SendPayslipJobTest.php
```

### Using the Coverage Script
```bash
./run-tests-with-coverage.sh 90
```

## Test Data

### Factories

The test suite uses Laravel factories for consistent test data:

- **UserFactory** - Creates users with realistic data
- **PayslipFactory** - Creates payslips with various states
- **SendPayslipProcessFactory** - Creates payslip processes
- **CompanyFactory** - Creates companies
- **DepartmentFactory** - Creates departments

### Factory States

Factories include helpful states:

```php
// Payslip states
Payslip::factory()->successful()->create();
Payslip::factory()->failed()->create();
Payslip::factory()->encryptionFailed()->create();
Payslip::factory()->emailBounced()->create();

// Process states
SendPayslipProcess::factory()->successful()->create();
SendPayslipProcess::factory()->failed()->create();
```

## Test Best Practices

1. **Isolation**: Each test is independent and uses `RefreshDatabase`
2. **Mocking**: External services (Mail, Storage, SMS) are mocked
3. **Fake Storage**: Uses `Storage::fake()` for file operations
4. **Fake Mail**: Uses `Mail::fake()` for email testing
5. **Descriptive Names**: Test names clearly describe what they test
6. **Arrange-Act-Assert**: Tests follow AAA pattern

## Coverage Areas

### ✅ Fully Covered
- Email sending and retry logic
- Email bounce detection
- Email notification preferences
- Payslip model relationships and attributes
- User model relationships and attributes
- Helper functions (email validation, payslip creation)
- Livewire component interactions
- Process reconciliation

### 🔄 Partially Covered
- PDF processing (needs more tests)
- SMS sending (needs more tests)
- Download jobs (needs tests)
- Additional Livewire components

### ❌ Not Yet Covered
- Some edge cases in PDF encryption
- Advanced error scenarios
- Performance testing
- Load testing

## Continuous Improvement

The test suite is continuously expanded to:
- Cover new features as they're added
- Improve edge case coverage
- Add integration tests for complex workflows
- Maintain 90%+ coverage threshold

## Notes

- Some linter warnings about Mail mocks are false positives (Mail::send() can return various types)
- Swift exception classes exist at runtime, linter warnings are expected
- Tests use Pest PHP syntax for cleaner, more readable tests





