# Enhanced Import System Guide

## Overview

The import system has been significantly enhanced to make it much easier for end users to import data without needing to know internal IDs. The system now supports:

- **Name-based lookups** instead of requiring IDs
- **Auto-creation** of missing entities (optional)
- **Better validation** with helpful error messages
- **Comprehensive field coverage** including notification preferences

## Key Improvements

### 1. Name-Based Lookups
- **Before**: Required exact department_id and service_id numbers
- **After**: Can use department names and service names directly

### 2. Auto-Creation (Optional)
- Can automatically create departments/services if they don't exist
- Reduces preparation time for users

### 3. Enhanced Validation
- Fuzzy matching suggests similar names if exact match fails
- Clear error messages with available options
- Comprehensive field validation

### 4. Complete Field Coverage
- All user fields are now supported in imports
- Notification preferences included

## Template Formats

### Employee Import Template

**File**: `employee_import_template.csv`

**Required Columns**:
1. **First Name** - Employee's first name
2. **Last Name** - Employee's last name
3. **Email** - Primary email address (must be unique)
4. **Professional Phone Number** - Work phone (E.164 format preferred)
5. **Matricule** - Employee ID number
6. **Position** - Job title
7. **Net Salary** - Monthly salary (numeric)
8. **Salary Grade** - Salary level/category
9. **Contract End Date** - End date (YYYY-MM-DD or Excel date)
10. **Department** - Department name (not ID!)
11. **Service** - Service name (not ID!)
12. **Role** - Must be: employee, supervisor, or manager
13. **Status** - 1 for active, 0 for inactive
14. **Password** - Plain text password (will be hashed)
15. **Remaining Leave Days** - Current leave balance
16. **Monthly Leave Allocation** - Leave days per month
17. **Receive SMS Notifications** - 1 for yes, 0 for no
18. **Personal Phone Number** - Personal phone (optional, E.164 format)
19. **Work Start Time** - Daily start time (HH:MM format)
20. **Work End Time** - Daily end time (HH:MM format)
21. **Receive Email Notifications** - 1 for yes, 0 for no
22. **Alternative Email** - Secondary email (optional)
23. **Date of Birth** - Birth date (YYYY-MM-DD or Excel date)

**Notes**:
- Department and Service can be names instead of IDs
- If department/service doesn't exist, it can be auto-created (depending on settings)
- Phone numbers should be in E.164 format (+1234567890) or will be formatted automatically
- Times should be in HH:MM format (24-hour)

### Department Import Template

**File**: `department_import_template.csv`

**Columns**:
1. **Department Name** - Name of the department
2. **Supervisor Email** - Email of department supervisor (optional)
3. **Company Name** - Company name (optional if importing within company context)

**Notes**:
- Supervisor must exist as a user with supervisor/manager role
- Company name can be provided if not importing within specific company context

### Service Import Template

**File**: `service_import_template.csv`

**Columns**:
1. **Service Name** - Name of the service
2. **Department Name** - Department this service belongs to

**Notes**:
- Department must exist or be auto-created
- Services are automatically linked to the correct company via department

## Usage Instructions

### For Administrators

1. **Download Templates**: Use the download buttons in the import modals
2. **Fill Data**: Enter data using names instead of IDs
3. **Upload**: Import the filled Excel/CSV files
4. **Review**: Check for any validation errors with helpful suggestions

### Auto-Creation Settings

The system supports optional auto-creation of missing entities:

- **Departments**: Can be auto-created when importing employees/services
- **Services**: Can be auto-created when importing employees
- **Companies**: Can be auto-created when importing departments

### Error Handling

**Enhanced error messages include**:
- Exact field causing the error
- Suggested corrections for typos
- List of available options when names don't match
- Clear instructions for fixing issues

**Example error messages**:
- `"Department 'IT Dept' not found. Did you mean: IT Department (95% match), Tech Department (78% match)?"`
- `"Service 'Dev Team' not found in IT Department. Available services: Development Team, QA Team"`

## Migration from Old System

**For existing imports using IDs**:
- Old ID-based imports still work
- System automatically detects if input is numeric (ID) or text (name)
- No breaking changes to existing workflows

**Recommended migration**:
1. Update templates to use names instead of IDs
2. Train users on new name-based approach
3. Enable auto-creation for smoother imports

## Technical Details

### Import Classes Enhanced

- **EmployeeImport**: Now supports name-based department/service lookup
- **DepartmentImport**: Supports company name lookup
- **ServiceImport**: Supports department name lookup

### Helper Functions Added

- `findOrCreateDepartment()`: Finds or creates departments by name
- `findOrCreateService()`: Finds or creates services by name
- `findOrCreateCompany()`: Finds or creates companies by name
- `findDepartmentByName()`: Fuzzy matching for departments
- `findServiceByName()`: Fuzzy matching for services

### Configuration Options

Future enhancements may include:
- Admin settings to enable/disable auto-creation
- Validation strictness levels
- Custom validation rules per company

## Best Practices

1. **Use Descriptive Names**: Make department and service names clear and unique
2. **Standardize Naming**: Use consistent naming conventions across imports
3. **Validate Before Bulk Import**: Test with small datasets first
4. **Backup Data**: Always backup before large imports
5. **Use Templates**: Always start with provided templates to ensure correct format

## Troubleshooting

### Common Issues

**"Department not found" errors**:
- Check spelling against existing departments
- Use auto-creation if enabled
- Review fuzzy matching suggestions

**"Phone number format invalid"**:
- Use E.164 format: +1234567890
- Or provide numbers without country code for auto-detection

**"Role must be employee, supervisor, or manager"**:
- Only these three roles are supported
- Check for typos in role names

**"Email already exists"**:
- Each email must be unique across all users
- Check for duplicate entries in import file