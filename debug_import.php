<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=ciblerh-payroll-checkin', 'root', 'root');

// Check jobs table
echo "=== Recent Jobs in Queue ===\n";
$stmt = $pdo->query("SELECT id, queue, attempts, created_at FROM jobs ORDER BY created_at DESC LIMIT 10");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "Job {$row['id']}: Queue={$row['queue']}, Attempts={$row['attempts']}, Created={$row['created_at']}\n";
}

// Check import jobs
echo "\n=== Recent Import Jobs ===\n";
$stmt = $pdo->query("SELECT id, import_type, status, total_rows, processed_rows, successful_imports, failed_imports, error_message, created_at FROM import_jobs ORDER BY created_at DESC LIMIT 10");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "Import {$row['id']}: Type={$row['import_type']}, Status={$row['status']}, Total={$row['total_rows']}, Processed={$row['processed_rows']}, Success={$row['successful_imports']}, Failed={$row['failed_imports']}, Created={$row['created_at']}\n";
    if ($row['error_message']) {
        echo "  Error: " . substr($row['error_message'], 0, 100) . "\n";
    }
}

echo "\n=== Most Recent Import Job Details ===\n";
$stmt = $pdo->query("SELECT id, import_type, status, total_rows, processed_rows, successful_imports, failed_imports, error_details, error_message, created_at FROM import_jobs ORDER BY created_at DESC LIMIT 1");
$job = $stmt->fetch(PDO::FETCH_ASSOC);
if ($job) {
    echo "Import Job {$job['id']}:\n";
    echo "  Import Type: {$job['import_type']}\n";
    echo "  Status: {$job['status']}\n";
    echo "  Total Rows: {$job['total_rows']}\n";
    echo "  Processed Rows: {$job['processed_rows']}\n";
    echo "  Successful: {$job['successful_imports']}\n";
    echo "  Failed: {$job['failed_imports']}\n";
    if ($job['error_message']) {
        echo "  Error Message: {$job['error_message']}\n";
    }
    if ($job['error_details']) {
        echo "  Error Details:\n";
        $details = json_decode($job['error_details'], true);
        if (is_array($details)) {
            foreach (array_slice($details, 0, 3) as $detail) {
                echo "    - " . json_encode($detail) . "\n";
            }
        }
    }
}
