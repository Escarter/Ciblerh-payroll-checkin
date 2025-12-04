# UI Test Coverage - 100% Target

## Overview

This document tracks comprehensive UI test coverage for all components in the application. The goal is **100% UI test coverage** for all user-facing features.

## Test Coverage Status

### ✅ Completed Components

#### 1. Dashboard (`tests/Browser/Dashboard/DashboardUITest.php`)
- ✅ View dashboard
- ✅ Admin sees all companies
- ✅ Manager sees only their companies
- ✅ Filter by company
- ✅ Filter by department
- ✅ Change period filter
- ✅ Display statistics

#### 2. Companies (`tests/Browser/Companies/CompaniesUITest.php`)
- ✅ View companies page
- ✅ Search companies
- ✅ Open create modal
- ✅ Create company
- ✅ Edit company
- ✅ Delete company
- ✅ Switch to deleted tab
- ✅ Restore deleted company
- ✅ Select all companies
- ✅ Bulk delete companies
- ✅ Change order by field
- ✅ Change items per page
- ✅ Validation errors

#### 3. Departments (`tests/Browser/Departments/DepartmentsUITest.php`)
- ✅ View departments page
- ✅ Search departments
- ✅ Create department
- ✅ Edit department
- ✅ Delete department
- ✅ Switch to deleted tab
- ✅ Restore deleted department
- ✅ Select all departments
- ✅ Bulk delete departments

#### 4. Employees (`tests/Browser/Employees/EmployeesUITest.php`)
- ✅ View employees page
- ✅ Search employees
- ✅ Open create modal
- ✅ Create employee
- ✅ Edit employee
- ✅ Delete employee
- ✅ Switch to deleted tab
- ✅ Select all employees
- ✅ Bulk delete employees
- ✅ Change order by field
- ✅ Change items per page
- ✅ Validation errors

#### 5. Services (`tests/Browser/Services/ServicesUITest.php`)
- ✅ View services page
- ✅ Search services
- ✅ Create service
- ✅ Edit service
- ✅ Delete service
- ✅ Switch to deleted tab
- ✅ Restore deleted service
- ✅ Select all services
- ✅ Bulk delete services

#### 6. Payslips Index (`tests/Browser/Payslips/PayslipsIndexUITest.php`)
- ✅ View payslips index page
- ✅ See payslip processes list
- ✅ Navigate to payslip details
- ✅ Switch between active and deleted tabs
- ✅ Delete payslip process
- ✅ Restore deleted payslip process
- ✅ Select all payslip processes
- ✅ Bulk delete payslip processes
- ✅ Empty state

#### 7. Payslips Details (`tests/Browser/Payslips/DetailsUITest.php`)
- ✅ View payslips details page
- ✅ Search for payslips
- ✅ Switch between active and deleted tabs
- ✅ Change items per page
- ✅ Change order by field
- ✅ Change order direction
- ✅ Select all payslips
- ✅ Toggle unmatched employees view
- ✅ See bulk resend failed button
- ✅ Click bulk resend failed and see modal
- ✅ Payslip table displays correct columns
- ✅ See payslip status badges
- ✅ Empty state
- ✅ Navigate back to payslips index
- ✅ Supervisor filtering

### 🔄 Components Needing Tests

#### 8. Leaves (`app/Livewire/Portal/Leaves/Index.php`)
**Required Tests:**
- View leaves page
- Search leaves
- Create leave request
- Edit leave request
- Approve/reject leave
- Delete leave
- Switch between active and deleted tabs
- Filter by leave type
- Filter by status
- Filter by date range
- Bulk operations
- Export leaves

#### 9. Leave Types (`app/Livewire/Portal/Leaves/Types/Index.php`)
**Required Tests:**
- View leave types page
- Search leave types
- Create leave type
- Edit leave type
- Delete leave type
- Switch between active and deleted tabs
- Bulk operations

#### 10. Overtimes (`app/Livewire/Portal/Overtimes/Index.php`)
**Required Tests:**
- View overtimes page
- Search overtimes
- Create overtime request
- Edit overtime request
- Approve/reject overtime
- Delete overtime
- Switch between active and deleted tabs
- Filter by status
- Filter by date range
- Bulk operations
- Export overtimes

#### 11. Absences (`app/Livewire/Portal/Absences/Index.php`)
**Required Tests:**
- View absences page
- Search absences
- Create absence
- Edit absence
- Delete absence
- Switch between active and deleted tabs
- Filter by status
- Filter by date range
- Bulk operations
- Export absences

#### 12. Advance Salaries (`app/Livewire/Portal/AdvanceSalaries/Index.php`)
**Required Tests:**
- View advance salaries page
- Search advance salaries
- Create advance salary request
- Edit advance salary request
- Approve/reject advance salary
- Delete advance salary
- Switch between active and deleted tabs
- Filter by status
- Filter by date range
- Bulk operations
- Export advance salaries

#### 13. Checklogs (`app/Livewire/Portal/Checklogs/Index.php`)
**Required Tests:**
- View checklogs page
- Search checklogs
- Create checklog
- Edit checklog
- Delete checklog
- Switch between active and deleted tabs
- Filter by employee
- Filter by date range
- Bulk operations
- Export checklogs

#### 14. Download Jobs (`app/Livewire/Portal/DownloadJobs/Index.php`)
**Required Tests:**
- View download jobs page
- Search download jobs
- Create download job
- View job details
- Download completed job
- Delete job
- Switch between active and deleted tabs
- Filter by job type
- Filter by status
- Filter by date range
- View job stats

#### 15. Settings (`app/Livewire/Portal/Settings/Index.php`)
**Required Tests:**
- View settings page
- Update SMTP settings
- Update SMS provider settings
- Update general settings
- Save settings
- Validation errors
- Test SMTP connection
- Test SMS balance

#### 16. Roles (`app/Livewire/Portal/Roles/Index.php`)
**Required Tests:**
- View roles page
- Search roles
- Create role
- Edit role
- Delete role
- Assign permissions
- Switch between active and deleted tabs
- Bulk operations

#### 17. Reports
**Required Tests for each report type:**

**Checklog Report (`app/Livewire/Portal/Reports/Checklog.php`):**
- View checklog report page
- Filter by company
- Filter by department
- Filter by date range
- Generate report
- Export report

**Overtime Report (`app/Livewire/Portal/Reports/Overtime.php`):**
- View overtime report page
- Filter by company
- Filter by department
- Filter by date range
- Generate report
- Export report

**Payslip Report (`app/Livewire/Portal/Reports/Payslip.php`):**
- View payslip report page
- Filter by company
- Filter by department
- Filter by date range
- Generate report
- Export report

#### 18. Profile Setting (`app/Livewire/Portal/ProfileSetting.php`)
**Required Tests:**
- View profile page
- Update profile information
- Change password
- Update email preferences
- Update SMS preferences
- Save changes
- Validation errors

#### 19. All Employees (`app/Livewire/Portal/Employees/All.php`)
**Required Tests:**
- View all employees page
- Search all employees
- Filter by role
- Filter by status
- Create employee
- Edit employee
- Delete employee
- Switch between active and deleted tabs
- Bulk operations

#### 20. Payslips All (`app/Livewire/Portal/Payslips/All.php`)
**Required Tests:**
- View all payslips page
- Search payslips
- Filter by status
- Filter by date range
- View payslip details
- Download payslip
- Switch between active and deleted tabs

#### 21. Employee Payslip History (`app/Livewire/Portal/Employees/Payslip/History.php`)
**Required Tests:**
- View employee payslip history
- Search payslips
- Filter by month/year
- Download payslip
- View payslip details

#### 22. Audit Logs (`app/Livewire/Portal/AuditLogs/Index.php`)
**Required Tests:**
- View audit logs page
- Search audit logs
- Filter by user
- Filter by action
- Filter by date range
- View log details
- Export audit logs

## Test Patterns

### Common Test Patterns

Each component should have tests for:

1. **View/Display**
   - Page loads correctly
   - Required elements are visible
   - Data displays correctly

2. **CRUD Operations**
   - Create (with validation)
   - Read/View
   - Update/Edit
   - Delete (soft delete)
   - Restore
   - Force delete

3. **Search & Filter**
   - Search functionality
   - Filter by various criteria
   - Clear filters

4. **Pagination & Sorting**
   - Change items per page
   - Change order by field
   - Change order direction
   - Navigate pages

5. **Bulk Operations**
   - Select all
   - Select individual items
   - Bulk delete
   - Bulk restore
   - Bulk force delete

6. **Tabs & Navigation**
   - Switch between active/deleted tabs
   - Navigate to related pages
   - Breadcrumb navigation

7. **Modals & Forms**
   - Open modals
   - Fill forms
   - Submit forms
   - Close modals
   - Validation errors

8. **Role-Based Access**
   - Admin access
   - Manager access
   - Supervisor access
   - Employee access (if applicable)

9. **Empty States**
   - No data message
   - Create button visibility

10. **Error Handling**
    - Validation errors
    - Permission errors
    - Not found errors

## Running All UI Tests

```bash
# Run all browser tests
php artisan dusk

# Run specific test suite
php artisan dusk tests/Browser/Dashboard
php artisan dusk tests/Browser/Companies
php artisan dusk tests/Browser/Employees

# Run with coverage report
php artisan dusk --coverage

# Run in visible mode
php artisan dusk --no-headless
```

## Coverage Goals

- **Target**: 100% UI test coverage
- **Current**: ~35% (7 components fully tested)
- **Remaining**: ~65% (15 components need tests)

## Next Steps

1. ✅ Complete Dashboard tests
2. ✅ Complete Companies tests
3. ✅ Complete Departments tests
4. ✅ Complete Employees tests
5. ✅ Complete Services tests
6. ✅ Complete Payslips Index tests
7. ✅ Complete Payslips Details tests
8. ⏳ Create Leaves tests
9. ⏳ Create Leave Types tests
10. ⏳ Create Overtimes tests
11. ⏳ Create Absences tests
12. ⏳ Create Advance Salaries tests
13. ⏳ Create Checklogs tests
14. ⏳ Create Download Jobs tests
15. ⏳ Create Settings tests
16. ⏳ Create Roles tests
17. ⏳ Create Reports tests
18. ⏳ Create Profile Setting tests
19. ⏳ Create All Employees tests
20. ⏳ Create Payslips All tests
21. ⏳ Create Employee Payslip History tests
22. ⏳ Create Audit Logs tests

## Notes

- All tests use `RefreshDatabase` for clean state
- External services are mocked (Mail, Storage, SMS)
- Tests use Page Objects where applicable
- Helper trait provides common functionality
- Tests follow consistent naming conventions




