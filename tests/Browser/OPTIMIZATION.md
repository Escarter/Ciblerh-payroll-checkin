# Test Execution Optimization Guide

## Current Performance

- **Total Tests**: ~250+ UI tests
- **Estimated Time**: 10-15 minutes (full suite)
- **Average per test**: 2-5 seconds

## Optimization Strategies

### 1. Database Optimization

**Use SQLite in-memory database:**
```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

**Benefits**:
- Faster than MySQL
- No database setup needed
- Automatic cleanup

**Trade-offs**:
- Some MySQL-specific features won't work
- Use MySQL for integration tests if needed

### 2. Parallel Test Execution

**Run tests in parallel groups:**
```bash
# Group 1: Core features
php artisan dusk tests/Browser/Dashboard tests/Browser/Companies tests/Browser/Departments &

# Group 2: Employee features
php artisan dusk tests/Browser/Employees tests/Browser/Services &

# Group 3: Payslip features
php artisan dusk tests/Browser/Payslips &
```

**Note**: Laravel Dusk doesn't natively support parallel execution. Use process management or CI/CD parallel jobs.

### 3. Test Grouping

**Create test groups for faster feedback:**
```php
// In Pest.php or test files
uses()->group('critical'); // Critical path tests
uses()->group('slow');     // Slow tests
```

**Run specific groups:**
```bash
php artisan dusk --group=critical
```

### 4. Reduce Wait Times

**Optimize pause times:**
```php
// Instead of fixed pauses
->pause(1000)

// Use waitFor when possible
->waitFor('#element', 5)

// Use shorter pauses for fast operations
->pause(300) // For simple DOM updates
```

### 5. Skip Slow Tests in Development

**Mark slow tests:**
```php
test('slow test', function () {
    // Skip in development
    if (app()->environment('local')) {
        $this->markTestSkipped('Skipped in local environment');
    }
    // ... test code
})->skip(fn() => app()->environment('local'));
```

### 6. Use Test Factories Efficiently

**Create minimal test data:**
```php
// Instead of creating many records
Company::factory()->count(10)->create();

// Create only what's needed
$company = Company::factory()->create();
```

### 7. Mock External Services

**Already implemented:**
- ✅ Mail::fake()
- ✅ Storage::fake()
- ✅ Bus::fake()

**Additional optimizations:**
- Mock slow API calls
- Use fake SMS services
- Skip actual file operations

### 8. CI/CD Optimization

**Use parallel jobs:**
```yaml
# GitHub Actions
strategy:
  matrix:
    test-group: [dashboard, companies, employees, payslips]
```

**Cache dependencies:**
```yaml
- uses: actions/cache@v3
  with:
    path: vendor
    key: composer-${{ hashFiles('composer.lock') }}
```

### 9. Selective Test Execution

**Run only changed tests:**
```bash
# Run tests for changed files only
git diff --name-only | grep -E '\.(php|blade\.php)$' | xargs php artisan dusk
```

### 10. Database Seeding Optimization

**Skip seeding in tests:**
```php
// Don't seed unless necessary
// Use factories directly instead
```

**Use transactions:**
```php
// Wrap tests in transactions for faster cleanup
use Illuminate\Foundation\Testing\DatabaseTransactions;
```

## Performance Targets

- **Critical path tests**: < 2 minutes
- **Full suite**: < 10 minutes
- **CI/CD pipeline**: < 15 minutes

## Monitoring

### Track Test Execution Time

```bash
# Run with timing
time php artisan dusk

# Or use PHPUnit's timing
php artisan dusk --testdox
```

### Identify Slow Tests

```bash
# Run with verbose output
php artisan dusk --verbose

# Profile specific tests
php artisan dusk --filter="slow test name"
```

## Recommended Optimizations

### Priority 1: Quick Wins
1. ✅ Use SQLite in-memory database
2. ✅ Reduce pause times where possible
3. ✅ Use waitFor instead of fixed pauses
4. ✅ Create minimal test data

### Priority 2: Medium Impact
1. ⏳ Group tests by feature
2. ⏳ Run critical tests first
3. ⏳ Skip slow tests in development
4. ⏳ Cache dependencies in CI/CD

### Priority 3: Advanced
1. ⏳ Parallel test execution
2. ⏳ Test result caching
3. ⏳ Selective test execution
4. ⏳ Database transaction wrapping

## Example Optimized Test

```php
test('user can view companies page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/companies')
            ->waitFor('h1', 5) // Wait for page load
            ->assertSee('Companies')
            ->assertPathIs('/portal/companies');
    });
})->group('critical'); // Mark as critical
```

## CI/CD Optimization

### GitHub Actions Matrix Strategy

```yaml
strategy:
  fail-fast: false
  matrix:
    test-group:
      - Dashboard
      - Companies
      - Employees
      - Payslips
      - Leaves
      - Reports
```

### Cache Strategy

```yaml
- name: Cache Composer
  uses: actions/cache@v3
  with:
    path: vendor
    key: ${{ runner.os }}-composer-${{ hashFiles('composer.lock') }}

- name: Cache NPM
  uses: actions/cache@v3
  with:
    path: node_modules
    key: ${{ runner.os }}-npm-${{ hashFiles('package-lock.json') }}
```

## Monitoring Test Performance

### Track Metrics

1. **Test execution time per file**
2. **Slowest tests**
3. **Failure rate**
4. **CI/CD pipeline duration**

### Tools

- GitHub Actions: Built-in timing
- GitLab CI: Built-in timing
- Custom scripts: Track in database/logs

## Best Practices

1. ✅ Keep tests fast (< 5 seconds each)
2. ✅ Use factories, not seeders
3. ✅ Mock external services
4. ✅ Clean up after tests
5. ✅ Group related tests
6. ✅ Skip non-critical tests in development
7. ✅ Use parallel execution in CI/CD
8. ✅ Cache dependencies
9. ✅ Monitor performance regularly
10. ✅ Optimize slow tests first

