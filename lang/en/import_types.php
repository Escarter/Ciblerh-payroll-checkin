<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Import Types Language Lines
    |--------------------------------------------------------------------------
    */

    'permission_denied' => 'You do not have permission to perform this import.',
    'file_not_found' => 'Import file not found.',
    'auto_create_departments_services' => 'Auto-create missing departments and services',
    'auto_create_missing_entities' => 'Auto-create missing entities',
    'auto_create_entities' => 'Auto-create missing departments/services',
    'department_override_description' => 'Override department assignment from import file. Leave empty to use department from file.',
    'service_override_description' => 'Override service assignment from import file. Leave empty to use service from file.',

    // Import type descriptions
    'employees_description' => 'Import employee data including personal information, employment details, and assignments.',
    'departments_description' => 'Import department information and organizational structure.',
    'companies_description' => 'Import company information and basic details.',
    'services_description' => 'Import service definitions and department assignments.',
    'leave_types_description' => 'Import leave type definitions and policies.',
    'supervisors_managers_description' => 'Import supervisor and manager user accounts with their roles and permissions.',
];