# Import Preview System - Developer Guide

## Overview

The Import Preview System provides a reusable, performant way to preview and validate import data before processing. It includes:

- **Sample-based preview** (first 10 rows) to prevent performance issues
- **Real-time validation** with detailed error messages
- **Reusable components** that work across all import modules
- **Caching system** for temporary data storage
- **User-friendly interface** with clear visual feedback

## Architecture

### Core Components

1. **`WithImportPreview` Trait** - Core preview functionality
2. **`BaseImportComponent`** - Base class with common import logic
3. **Preview Modal Component** - Reusable UI component
4. **Import Classes** - Enhanced with validation logic

### File Structure

```
app/
├── Livewire/
│   ├── BaseImportComponent.php              # Base class for imports
│   ├── Traits/
│   │   └── WithImportPreview.php            # Preview functionality trait
│   └── Components/
│       └── ImportPreview.php                # Preview modal component
├── Imports/
│   ├── EmployeeImport.php                   # Enhanced import classes
│   ├── DepartmentImport.php
│   └── ServiceImport.php
resources/
├── views/
│   ├── components/
│   │   └── import-preview-modal.blade.php  # Reusable modal
│   └── livewire/
│       └── components/
│           └── import-preview.blade.php     # Livewire preview component
```

## Implementing Preview in New Import Modules

### Step 1: Extend BaseImportComponent

```php
<?php

namespace App\Livewire\Portal\YourModule;

use App\Livewire\BaseImportComponent;

class Index extends BaseImportComponent
{
    use WithDataTable; // If you need data table functionality

    protected $importType = 'your_module';        // Unique identifier
    protected $importPermission = 'your_module-create'; // Permission required

    // Your existing properties and methods...
}
```

### Step 2: Implement Required Methods

```php
/**
 * Get column definitions for preview display
 */
protected function getImportColumns(): array
{
    return [
        0 => __('your_module.column_name'),
        1 => __('your_module.another_column'),
        // ... map column indices to translated labels
    ];
}

/**
 * Validate a single preview row
 */
protected function validatePreviewRow(array $rowData, int $rowNumber): array
{
    $errors = [];
    $warnings = [];
    $parsedData = [];

    // Validate your business logic here
    if (empty($rowData[0] ?? '')) {
        $errors[] = __('your_module.field_required');
    }

    // Check for duplicates, relationships, etc.
    if ($this->checkForDuplicates($rowData[0])) {
        $warnings[] = __('your_module.duplicate_warning');
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings,
        'parsed_data' => $parsedData  // Transformed data for display
    ];
}

/**
 * Perform the actual import
 */
protected function performImport()
{
    // Use your existing import logic
    Excel::import(new YourModuleImport($this->contextData), $this->your_file);

    return [
        'imported_count' => 'count_here_or_unknown',
        'additional_context' => 'any_extra_info'
    ];
}
```

### Step 3: Update Import View

```blade
{{-- In your import modal --}}
@if($your_file)
    <div class="mb-4">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-info" wire:click="processPreview">
                <i class="fas fa-eye me-2"></i>
                {{ __('common.show_preview') }}
            </button>
            <small class="text-muted align-self-center">{{ __('common.preview_before_import') }}</small>
        </div>
    </div>
@endif

{{-- Add preview modal at end of file --}}
<x-import-preview-modal
    :previewData="$previewData"
    :previewErrors="$previewErrors"
    :totalRows="$totalRows"
    :processedRows="$processedRows"
    :hasLargeFile="$hasLargeFile"
    :showPreview="$showPreview"
    :columns="$this->getPreviewColumns()"
    :canProceed="$this->canProceedWithImport()"
    modalId="yourModuleImportPreviewModal"
    :title="__('Import :name Preview', ['name' => __('your_module.name')])"
    importType="your_module"
/>
```

### Step 4: Add Translations

Add these keys to `lang/en/common.php` and `lang/fr/common.php`:

```php
// Your module specific translations
'your_module' => [
    'name' => 'Your Module',
    'column_name' => 'Column Name',
    'field_required' => 'Field is required',
    'duplicate_warning' => 'Duplicate entry found',
],
```

## Configuration Options

### Preview Settings (in WithImportPreview trait)

```php
// Customize these in your component if needed
protected $maxPreviewRows = 10;        // Rows to preview
protected $largeFileThreshold = 1;     // MB threshold for warnings
protected $maxFileSize = 5;            // Max file size in MB
protected $previewCacheTtl = 15;       // Cache TTL in minutes
```

### Validation Customization

Override validation methods to customize behavior:

```php
protected function validateFileForPreview() {
    // Custom file validation
    parent::validateFileForPreview();

    // Your custom checks
    if (!$this->checkCustomRequirements()) {
        throw new \Exception('Custom validation failed');
    }
}

protected function extractPreviewData(): array {
    // Custom data extraction if needed
    return parent::extractPreviewData();
}
```

## Performance Considerations

### Memory Management
- **Sample processing**: Only first N rows are processed
- **Lazy loading**: Preview data loaded on-demand
- **File streaming**: Large files processed in chunks
- **Cache cleanup**: Automatic cleanup of expired preview data

### User Experience
- **Progressive disclosure**: Optional preview doesn't interrupt workflow
- **Visual feedback**: Clear loading states and progress indicators
- **Error recovery**: Detailed error messages with suggested fixes
- **Responsive design**: Works on all screen sizes

## Error Handling

### Validation Errors
```php
return [
    'valid' => false,
    'errors' => [
        __('field_required', ['field' => 'Name']),
        __('invalid_format', ['field' => 'Email'])
    ],
    'warnings' => [
        __('duplicate_found', ['value' => 'john@example.com'])
    ]
];
```

### System Errors
- Automatically logged to Laravel logs
- User-friendly error messages
- Graceful fallbacks when preview fails

## Security Considerations

- **File validation**: Strict file type and size checking
- **Permission checks**: Integration with Laravel Gates
- **Input sanitization**: All data validated before processing
- **Cache isolation**: User-specific cache keys prevent data leakage

## Testing

### Unit Tests
```php
// Test validation logic
public function test_preview_validation() {
    $component = new YourModuleIndex();
    $result = $component->validatePreviewRow(['valid', 'data'], 1);

    $this->assertTrue($result['valid']);
}

// Test import functionality
public function test_successful_import() {
    // Mock file upload and test import process
}
```

### Integration Tests
```php
// Test full import workflow
public function test_import_with_preview() {
    // Upload file, show preview, validate, import
}
```

## Troubleshooting

### Common Issues

**Preview not showing data:**
- Check if file is properly uploaded
- Verify column indices match your `getImportColumns()` method
- Check for PHP errors in validation methods

**Validation errors not displaying:**
- Ensure error messages are properly translated
- Check if validation methods return correct array structure
- Verify modal is properly included in the view

**Performance issues:**
- Reduce `$maxPreviewRows` if needed
- Check if files are being processed in memory
- Monitor cache usage and cleanup

**Import fails after preview:**
- Ensure import logic matches preview validation
- Check for differences in data processing between preview and import
- Verify file is still accessible during import

## Migration from Old Import System

When upgrading existing import modules:

1. **Extend BaseImportComponent** instead of Component
2. **Add WithImportPreview trait** if needed
3. **Implement required methods** (getImportColumns, validatePreviewRow, performImport)
4. **Update views** to include preview functionality
5. **Test thoroughly** to ensure no regressions

## Best Practices

1. **Keep validation logic DRY** - Reuse helper functions across modules
2. **Provide clear error messages** - Users should understand what to fix
3. **Handle edge cases** - Empty files, malformed data, large files
4. **Test with real data** - Use actual import files for testing
5. **Monitor performance** - Add logging for large file processing
6. **Document customizations** - Comment any module-specific logic

This system provides a solid foundation for import functionality while maintaining performance and user experience standards.