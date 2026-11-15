# Update Audit Log Translations

This document describes how to update all `auditLog()` calls to use translation keys instead of translated strings.

## Pattern to Find and Replace

### Before:
```php
auditLog(
    $user,
    $actionType,
    'web',
    __('audit_logs.bulk_approved_absences', ['count' => count($absences)]),
    null,
    [],
    [],
    [
        'bulk_operation' => true,
        // ... other metadata
    ]
);
```

### After:
```php
auditLog(
    $user,
    $actionType,
    'web',
    'bulk_approved_absences',  // Translation key without __()
    null,
    [],
    [],
    [
        'translation_key' => 'bulk_approved_absences',
        'translation_params' => ['count' => count($absences)],
        'bulk_operation' => true,
        // ... other metadata
    ]
);
```

## Steps

1. Find all occurrences of `__('audit_logs.` in auditLog calls
2. Extract the translation key (e.g., `bulk_approved_absences`)
3. Extract the translation parameters (e.g., `['count' => count($absences)]`)
4. Replace the `__('audit_logs.KEY', PARAMS)` with just `'KEY'`
5. Add `'translation_key' => 'KEY'` and `'translation_params' => PARAMS` to the metadata array

## Files That Need Updating

Based on grep results, the following files contain auditLog calls with translations:

- app/Livewire/Portal/Overtimes/Index.php
- app/Livewire/Portal/Leaves/Index.php
- app/Livewire/Portal/Checklogs/Index.php
- app/Livewire/Portal/AdvanceSalaries/Index.php
- app/Livewire/Portal/Employees/Index.php
- app/Livewire/Portal/Employees/All.php
- app/Livewire/Portal/Companies/Index.php
- app/Livewire/Portal/Departments/Index.php
- app/Livewire/Portal/Services/Index.php
- app/Livewire/Portal/Roles/Index.php
- app/Livewire/Portal/Leaves/Types/Index.php
- app/Livewire/Portal/DownloadJobs/Index.php
- app/Livewire/Portal/ImportJobs/Index.php
- app/Livewire/Portal/Payslips/Index.php
- app/Livewire/Portal/Payslips/All.php
- app/Livewire/Portal/Payslips/Details.php
- app/Livewire/Portal/Employees/Payslip/History.php
- app/Livewire/Employee/Absences/Index.php
- app/Livewire/Employee/Leaves/Index.php
- app/Livewire/Employee/Overtime/Index.php
- app/Livewire/Employee/AdvanceSalary/Index.php
- app/Livewire/Employee/Checklog/Index.php (partially done)
- app/Livewire/Portal/Reports/Payslip.php
- app/Observers/*.php (check observers for audit log calls)

## Notes

- The `getTranslatedActionPerformAttribute()` accessor in `app/Models/AuditLog.php` has been updated to check metadata for translation keys and parameters first
- Old entries without translation keys will still work (backward compatible)
- New entries should use the translation key approach for proper language switching
