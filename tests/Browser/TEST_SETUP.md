# Dusk Test Setup and Execution Guide

## Prerequisites

1. **Chrome/Chromium Browser** - Must be installed
2. **ChromeDriver** - Will be installed automatically or manually
3. **PHP 8.1+** - Required for Laravel 10
4. **Composer** - For dependencies
5. **Node.js & NPM** - For asset compilation

## Quick Start

### Option 1: Using the Test Runner Script (Recommended)

```bash
# Make script executable
chmod +x run-dusk-tests.sh

# Run all tests
./run-dusk-tests.sh

# Run specific test file
./run-dusk-tests.sh tests/Browser/Dashboard/DashboardUITest.php
```

### Option 2: Manual Setup

```bash
# 1. Install ChromeDriver (if not already installed)
# macOS:
brew install chromedriver

# Linux:
# Download from https://chromedriver.chromium.org/
# Or use: php artisan dusk:chrome-driver

# 2. Start ChromeDriver
chromedriver --port=9515 &

# 3. Start Laravel server
php artisan serve --host=127.0.0.1 --port=8000 &

# 4. Run tests
php artisan dusk
```

## Environment Setup

### .env Configuration

Ensure your `.env` file has:

```env
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
# OR use MySQL for testing
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=testing
DB_USERNAME=root
DB_PASSWORD=
```

### Database Setup

```bash
# Create testing database
php artisan migrate --env=testing --force

# Seed if needed
php artisan db:seed --env=testing
```

## Running Tests

### Run All Tests

```bash
php artisan dusk
```

### Run Specific Test File

```bash
php artisan dusk tests/Browser/Dashboard/DashboardUITest.php
```

### Run Tests with Filter

```bash
php artisan dusk --filter="user can view dashboard"
```

### Run in Visible Mode (See Browser)

```bash
php artisan dusk --no-headless
```

### Run with Screenshots on Failure

```bash
php artisan dusk --screenshots
```

### Run Specific Test Suite

```bash
# Dashboard tests only
php artisan dusk tests/Browser/Dashboard

# Companies tests only
php artisan dusk tests/Browser/Companies
```

## Troubleshooting

### ChromeDriver Issues

**Problem**: `Invalid path to Chromedriver`

**Solutions**:
1. Install ChromeDriver:
   ```bash
   # macOS
   brew install chromedriver
   
   # Or use Laravel Dusk installer
   php artisan dusk:chrome-driver
   ```

2. Check ChromeDriver version matches Chrome:
   ```bash
   chromedriver --version
   chrome --version
   ```

3. Start ChromeDriver manually:
   ```bash
   chromedriver --port=9515
   ```

### Port Already in Use

**Problem**: `Port 8000 already in use`

**Solution**:
```bash
# Kill process on port 8000
lsof -ti:8000 | xargs kill -9

# Or use different port
php artisan serve --port=8001
# Update APP_URL in .env
```

### Tests Timing Out

**Problem**: Tests fail with timeout errors

**Solutions**:
1. Increase wait times in tests
2. Check server is running: `curl http://127.0.0.1:8000`
3. Check ChromeDriver is running: `curl http://localhost:9515/status`
4. Run in visible mode to see what's happening:
   ```bash
   php artisan dusk --no-headless
   ```

### Selector Not Found

**Problem**: `Element not found` errors

**Solutions**:
1. Check actual HTML structure in browser dev tools
2. Update selectors in test files
3. Use more specific selectors (IDs, data attributes)
4. Add wait times for Livewire updates:
   ```php
   ->pause(1000) // Wait for Livewire
   ```

### Database Issues

**Problem**: Database connection errors

**Solutions**:
1. Ensure database exists:
   ```bash
   php artisan migrate --env=testing --force
   ```

2. Use SQLite for faster tests:
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=:memory:
   ```

3. Clear test database:
   ```bash
   php artisan migrate:fresh --env=testing
   ```

## CI/CD Integration

### GitHub Actions

The `.github/workflows/dusk-tests.yml` file is configured to:
- Install ChromeDriver automatically
- Set up MySQL database
- Run all Dusk tests
- Upload screenshots and logs on failure

### GitLab CI

Example `.gitlab-ci.yml`:

```yaml
dusk-tests:
  image: php:8.1
  services:
    - mysql:8.0
  before_script:
    - apt-get update && apt-get install -y chromium-driver
    - composer install
    - php artisan dusk:chrome-driver
  script:
    - php artisan serve &
    - php artisan dusk
```

## Best Practices

1. **Use Page Objects** for complex pages
2. **Wait for Livewire** updates with `pause()`
3. **Use specific selectors** (IDs, data attributes)
4. **Mock external services** (Mail, Storage, SMS)
5. **Clean up after tests** (use RefreshDatabase)
6. **Run tests in headless mode** for CI/CD
7. **Take screenshots** on failure for debugging
8. **Group related tests** in describe blocks

## Test Execution Time

- **Single test**: ~2-5 seconds
- **Full suite**: ~10-15 minutes (250+ tests)
- **Optimization tips**:
  - Use SQLite in-memory database
  - Run tests in parallel (if supported)
  - Skip slow tests in development
  - Use test groups/filters

## Debugging Failed Tests

1. **Check screenshots**: `tests/Browser/screenshots/`
2. **Check console logs**: `tests/Browser/console/`
3. **Check source files**: `tests/Browser/source/`
4. **Run in visible mode**: `php artisan dusk --no-headless`
5. **Add breakpoints**: Use `pause()` in tests
6. **Check Laravel logs**: `storage/logs/laravel.log`

## Common Issues and Fixes

| Issue | Fix |
|-------|-----|
| ChromeDriver not found | Install via Homebrew or `php artisan dusk:chrome-driver` |
| Port 8000 in use | Kill process or use different port |
| Element not found | Check selector, add wait time |
| Test timeout | Increase timeout, check server |
| Database error | Run migrations, check .env |
| Permission denied | `chmod +x run-dusk-tests.sh` |

## Next Steps

1. ✅ Run tests locally: `./run-dusk-tests.sh`
2. ✅ Fix any selector issues
3. ✅ Add to CI/CD pipeline
4. ✅ Monitor test execution time
5. ✅ Add more edge case tests as needed








