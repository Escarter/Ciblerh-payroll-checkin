# Browser UI Tests

This directory contains browser-based UI tests using Laravel Dusk. These tests simulate real user interactions with the application in a browser environment.

## Setup

### Prerequisites

1. **ChromeDriver**: Dusk requires ChromeDriver to be installed and running.
   - On macOS: `brew install chromedriver`
   - Or download from: https://chromedriver.chromium.org/
   - Or use: `php artisan dusk:chrome-driver`

2. **Chrome Browser**: Ensure Google Chrome is installed.

3. **Environment**: Make sure your `.env` file has the correct database configuration for testing.

### Running Tests

```bash
# Run all browser tests
php artisan dusk

# Run specific test file
php artisan dusk tests/Browser/Payslips/DetailsUITest.php

# Run tests with specific filter
php artisan dusk --filter="user can view payslips"

# Run tests in non-headless mode (see browser)
php artisan dusk --no-headless

# Run tests with screenshots on failure
php artisan dusk --screenshots
```

## Test Structure

### Page Objects (`Pages/`)

Page Objects encapsulate page-specific logic and selectors:

- `PayslipsDetailsPage.php` - Page object for the Payslips Details page

### Test Files

- `Payslips/DetailsUITest.php` - UI tests for Payslips Details page

### Helpers

- `BrowserTestHelpers` trait (in `tests/Helpers/`) - Common helper methods for browser tests:
  - `loginAs()` - Login as a user with a specific role
  - `createPayslipProcessWithPayslips()` - Create test data
  - `createPayslipFiles()` - Create payslip files in storage
  - `waitForLivewire()` - Wait for Livewire to finish loading
  - `assertFlashMessage()` - Assert flash messages appear

## Writing UI Tests

### Basic Test Structure

```php
test('user can perform action', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        // Your test code here
    });
});
```

### Using Page Objects

```php
use Tests\Browser\Pages\PayslipsDetailsPage;

test('user can navigate to page', function () {
    $this->browse(function (Browser $browser) {
        $process = $this->createPayslipProcessWithPayslips(5);
        $page = new PayslipsDetailsPage($process->id);
        
        $browser->visit($page)
            ->assertSee('Payslips Details');
    });
});
```

### Common Browser Actions

```php
// Type in input
$browser->type('selector', 'value');

// Click button
$browser->click('button:contains("Text")');

// Select dropdown
$browser->select('#selectId', 'value');

// Check checkbox
$browser->check('#checkboxId');

// Assert text exists
$browser->assertSee('Text');

// Wait for element
$browser->waitFor('#elementId', 5);

// Pause (useful for Livewire)
$browser->pause(500);
```

## Test Coverage

### Payslips Details Page (`DetailsUITest.php`)

Tests cover:
- ✅ Page loading and navigation
- ✅ Search functionality
- ✅ Tab switching (Active/Deleted)
- ✅ Pagination and sorting
- ✅ Bulk operations (select all, bulk resend)
- ✅ Unmatched employees toggle
- ✅ Status badges display
- ✅ Empty states
- ✅ Role-based access (supervisor filtering)

## Best Practices

1. **Use Page Objects**: Encapsulate page-specific logic in Page Objects for reusability.

2. **Wait for Livewire**: Always pause after Livewire actions to allow time for updates:
   ```php
   $browser->type('@searchInput', 'query')
       ->pause(500); // Wait for Livewire
   ```

3. **Use Descriptive Selectors**: Prefer data attributes or semantic selectors over CSS classes.

4. **Mock External Services**: Use `Mail::fake()`, `Storage::fake()`, etc. in `beforeEach()`.

5. **Clean State**: Tests use `RefreshDatabase` to ensure clean state between tests.

6. **Test User Flows**: Focus on testing complete user workflows, not just individual actions.

## Troubleshooting

### ChromeDriver Issues

If ChromeDriver fails to start:
```bash
# Check if ChromeDriver is running
chromedriver --version

# Start ChromeDriver manually
chromedriver --port=9515
```

### Tests Timing Out

- Increase wait times for slow operations
- Use `waitFor()` instead of `pause()` when possible
- Check that Livewire has finished loading

### Screenshots

Screenshots are automatically saved to `tests/Browser/screenshots/` on failure.

### Debug Mode

Run tests without headless mode to see what's happening:
```bash
php artisan dusk --no-headless
```

## CI/CD Integration

For CI/CD pipelines, ensure:
1. ChromeDriver is installed
2. Chrome/Chromium is available
3. Tests run in headless mode (default)
4. Screenshots are captured on failure

Example GitHub Actions:
```yaml
- name: Install ChromeDriver
  run: |
    wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | apt-key add -
    echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google.list
    apt-get update
    apt-get install -y google-chrome-stable chromedriver

- name: Run Dusk Tests
  run: php artisan dusk
```




