# Selector Fixes Needed

This document tracks selector issues found when running tests and their fixes.

## Common Selector Patterns

Based on actual HTML structure, here are the correct selectors:

### Search Inputs
- ✅ `#search` - Correct (used in companies, employees, etc.)
- ✅ `wire:model.live="query"` - Livewire model binding

### Select Dropdowns
- ✅ `#orderBy` - Correct
- ✅ `#direction` - Correct (for order direction)
- ✅ `#perPage` - Correct
- ✅ `wire:model.live="orderBy"` - Livewire model binding

### Buttons
- ✅ `button:contains("Text")` - Text-based selector
- ✅ `wire:click="methodName"` - Livewire click handler
- ✅ `data-bs-toggle="modal"` - Bootstrap modal trigger

### Modals
- ✅ `#ModalName` - Modal ID (e.g., `#CompanyModal`, `#DeleteModal`)
- ✅ `data-bs-target="#ModalName"` - Bootstrap modal target

### Checkboxes
- ✅ `input[type="checkbox"][wire\\:model="selectAll"]` - Select all checkbox
- ✅ `input[type='checkbox'][value='{$id}']` - Individual item checkbox

### Tabs
- ✅ `button:contains("Active")` - Active tab button
- ✅ `button:contains("Deleted")` - Deleted tab button
- ✅ `wire:click="switchTab('active')"` - Livewire tab switch

## Potential Issues to Fix

### 1. Modal Selectors
Some modals might use different IDs. Check actual HTML:
- Company Modal: `#CompanyModal` ✅
- Delete Modal: `#DeleteModal` ✅
- Bulk Delete Modal: `#BulkDeleteModal` ✅

### 2. Button Text Variations
Button text might be translated. Use more flexible selectors:
```php
// Instead of exact text match
->click('button:contains("Create Company")')

// Use data attributes or wire:click
->click('a[wire\\:click.prevent="openCreateModal"]')
```

### 3. Livewire Updates
Always pause after Livewire actions:
```php
->type('#search', 'query')
->pause(1000) // Wait for Livewire to filter
```

### 4. Form Fields
Some forms might use different field names:
- Check actual form structure in blade files
- Use `wire:model` attributes when available
- Use IDs when available, fallback to names

## Testing Selectors

To verify selectors work:

1. Run test in visible mode:
   ```bash
   php artisan dusk --no-headless tests/Browser/Companies/CompaniesUITest.php
   ```

2. Add pause to inspect:
   ```php
   ->pause(5000) // Pause for 5 seconds to inspect
   ```

3. Check browser console for errors

4. Take screenshot:
   ```php
   ->screenshot('debug-screenshot')
   ```

## Common Fixes

### Fix 1: Search Input Selector
**Issue**: Search might use different ID
**Fix**: Check blade file for actual ID or use `wire:model` selector

### Fix 2: Modal Not Opening
**Issue**: Modal might need Bootstrap trigger
**Fix**: Use `data-bs-toggle` and `data-bs-target` attributes

### Fix 3: Element Not Found After Livewire Update
**Issue**: Livewire updates DOM asynchronously
**Fix**: Add `pause()` after Livewire actions

### Fix 4: Button Click Not Working
**Issue**: Button might be disabled or hidden
**Fix**: Check button state, use `waitFor()` before clicking

## Running Tests to Find Issues

```bash
# Run single test file to debug
php artisan dusk --no-headless tests/Browser/Companies/CompaniesUITest.php

# Run with filter
php artisan dusk --filter="user can view companies page"

# Run with screenshots
php artisan dusk --screenshots tests/Browser/Companies/CompaniesUITest.php
```

## Next Steps

1. Run tests: `./run-dusk-tests.sh`
2. Fix selector issues as they appear
3. Update this document with fixes
4. Re-run tests to verify fixes

