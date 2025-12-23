# Permission Updates Summary

## Completed Updates

### 1. Database Seeder (`database/seeders/RolesAndPermissionsSeeder.php`)
✅ Added permissions for all modules:
- `{module}-restore`
- `{module}-bulkdelete`
- `{module}-bulkrestore`

Modules updated: role, company, department, service, employee, absence, advance_salary, leave, leave_type, overtime, ticking, payslip, importjob, audit_log

✅ Updated role permission syncs for admin, manager, supervisor, and employee roles

### 2. Component Methods - Portal
✅ Updated all Portal component methods:
- `app/Livewire/Portal/Roles/Index.php`
- `app/Livewire/Portal/Companies/Index.php`
- `app/Livewire/Portal/Departments/Index.php`
- `app/Livewire/Portal/Services/Index.php`
- `app/Livewire/Portal/Absences/Index.php`
- `app/Livewire/Portal/AdvanceSalaries/Index.php`
- `app/Livewire/Portal/Leaves/Index.php`
- `app/Livewire/Portal/Leaves/Types/Index.php`
- `app/Livewire/Portal/Overtimes/Index.php`
- `app/Livewire/Portal/Checklogs/Index.php`
- `app/Livewire/Portal/Payslips/Index.php`
- `app/Livewire/Portal/Payslips/Details.php`
- `app/Livewire/Portal/Payslips/All.php`
- `app/Livewire/Portal/Employees/Index.php`
- `app/Livewire/Portal/Employees/All.php`
- `app/Livewire/Portal/Employees/Payslip/History.php`
- `app/Livewire/Portal/ImportJobs/Index.php`
- `app/Livewire/Portal/AuditLogs/Index.php`

### 3. Component Methods - Employee
✅ Updated all Employee component methods:
- `app/Livewire/Employee/Absences/Index.php`
- `app/Livewire/Employee/AdvanceSalary/Index.php`
- `app/Livewire/Employee/Leaves/Index.php`
- `app/Livewire/Employee/Overtime/Index.php`
- `app/Livewire/Employee/Checklog/Index.php`

### 4. Views - Portal
✅ Updated views to conditionally show tabs and bulk select:
- `resources/views/livewire/portal/roles/index.blade.php`
- `resources/views/livewire/portal/companies/index.blade.php`
- `resources/views/livewire/portal/departments/index.blade.php`
- `resources/views/livewire/portal/services/index.blade.php`
- `resources/views/livewire/portal/employees/index.blade.php`

## Remaining View Updates Needed

### Portal Views Still Needing Updates:

1. **Absences** - `resources/views/livewire/portal/absences/index.blade.php`
2. **Leaves** - `resources/views/livewire/portal/leaves/index.blade.php`
3. **Overtimes** - `resources/views/livewire/portal/overtimes/index.blade.php`
4. **Checklogs** - `resources/views/livewire/portal/checklogs/index.blade.php`
5. **Payslips** - `resources/views/livewire/portal/payslips/index.blade.php`
6. **Payslips All** - `resources/views/livewire/portal/payslips/all.blade.php`
7. **Payslips Details** - `resources/views/livewire/portal/payslips/details.blade.php`
8. **Employees All** - `resources/views/livewire/portal/employees/all.blade.php`
9. **Employees Payslip History** - `resources/views/livewire/portal/employees/payslip/history.blade.php`
10. **Leave Types** - `resources/views/livewire/portal/leaves/types/index.blade.php`
11. **Advance Salaries** - `resources/views/livewire/portal/advance-salaries/index.blade.php`
12. **Import Jobs** - `resources/views/livewire/portal/import-jobs/index.blade.php`
13. **Audit Logs** - `resources/views/livewire/portal/audit-logs/index.blade.php`
14. **Download Jobs** - `resources/views/livewire/portal/download-jobs/index.blade.php` (if applicable)

### Employee Views Still Needing Updates:

1. **Absences** - `resources/views/livewire/employee/absences/index.blade.php`
2. **Leaves** - `resources/views/livewire/employee/leaves/index.blade.php`
3. **Overtime** - `resources/views/livewire/employee/overtime/index.blade.php`
4. **Checklog** - `resources/views/livewire/employee/checklog/index.blade.php`
5. **Advance Salary** - `resources/views/livewire/employee/advance-salary/index.blade.php`

## Pattern for View Updates

For each module view, apply the following pattern:

### 1. Wrap Tabs Section
```blade
@if(auth()->user()->can('{module}-bulkdelete') && auth()->user()->can('{module}-bulkrestore'))
    <!-- Tab Buttons (Active/Deleted) -->
    <div class="d-flex gap-2">
        <button>Active</button>
        <button>Deleted</button>
    </div>
    <!-- Bulk Actions Section -->
@endif
```

### 2. Update Bulk Action Buttons
```blade
@can('{module}-bulkdelete')
    <!-- Bulk Delete Button -->
@endcan

@can('{module}-bulkrestore')
    <!-- Bulk Restore Button -->
@endcan

@can('{module}-restore')
    <!-- Individual Restore Button -->
@endcan
```

### 3. Hide Individual Checkboxes
Also ensure individual item checkboxes are only shown if user has both bulkdelete and bulkrestore permissions.

## Permission Mapping

| Module | Permission Prefix |
|--------|------------------|
| Roles | `role-` |
| Companies | `company-` |
| Departments | `department-` |
| Services | `service-` |
| Employees | `employee-` |
| Absences | `absence-` |
| Advance Salaries | `advance_salary-` |
| Leaves | `leave-` |
| Leave Types | `leave_type-` |
| Overtimes | `overtime-` |
| Checklogs/Ticking | `ticking-` |
| Payslips | `payslip-` |
| Import Jobs | `importjob-` |
| Audit Logs | `audit_log-` |
