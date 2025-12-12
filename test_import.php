<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Console\Command;
use App\Imports\EmployeeImport;
use App\Models\Company;
use App\Models\Department;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Employee Import...\n";

try {
    // Get first company
    $company = Company::first();
    if (!$company) {
        echo "No company found. Please create a company first.\n";
        exit(1);
    }

    echo "Using company: {$company->name} (ID: {$company->id})\n";

    // Create import instance
    $import = new EmployeeImport($company, null, null, true, 1);

    // Test file path
    $filePath = 'test_import_small.csv';

    if (!file_exists($filePath)) {
        echo "Test file not found: {$filePath}\n";
        exit(1);
    }

    echo "Importing file: {$filePath}\n";

    // Store file temporarily
    $tempPath = 'temp/test_import_' . time() . '.csv';
    Storage::disk('local')->put($tempPath, file_get_contents($filePath));

    echo "File stored at: storage/app/{$tempPath}\n";

    // Try to import
    Excel::import($import, storage_path('app/' . $tempPath));

    // Check for errors
    $errorCount = 0;
    if (method_exists($import, 'errors')) {
        $errorCount = $import->errors()->count();
    }

    $failureCount = 0;
    if (method_exists($import, 'failures')) {
        $failureCount = $import->failures()->count();
    }

    echo "Import completed!\n";
    echo "Errors: {$errorCount}\n";
    echo "Failures: {$failureCount}\n";

    if ($errorCount > 0) {
        echo "Error details:\n";
        foreach ($import->errors() as $error) {
            echo "- Row {$error->row()}: " . implode(', ', $error->errors()) . "\n";
        }
    }

    if ($failureCount > 0) {
        echo "Failure details:\n";
        foreach ($import->failures() as $failure) {
            echo "- Row {$failure->row()}: " . implode(', ', $failure->errors()) . "\n";
        }
    }

    // Clean up
    Storage::disk('local')->delete($tempPath);
    unlink($filePath);

    echo "Test completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}