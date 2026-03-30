<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=ciblerh-payroll-checkin', 'root', 'root');

// Delete failed/stuck queue jobs
$res = $pdo->exec("DELETE FROM jobs WHERE queue = 'processing'");
echo "Deleted $res stuck queue jobs\n";

// Reset the stuck import jobs to pending so they can be retried
$res = $pdo->exec("UPDATE import_jobs SET status = 'pending', processed_rows = 0, successful_imports = 0, failed_imports = 0, error_message = NULL, started_at = NULL WHERE status IN ('pending', 'processing') AND processed_rows = 0");
echo "Reset $res stuck import jobs to pending\n";

// Show current state
$stmt = $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE queue = 'processing'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Queue jobs remaining: " . $row['count'] . "\n";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM import_jobs WHERE status = 'pending'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Pending import jobs: " . $row['count'] . "\n";

echo "Cleanup complete!\n";
