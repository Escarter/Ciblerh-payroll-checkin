<?php

/**
 * Script to fix wait times in all browser test files
 * This ensures consistent waiting for Livewire components
 */

$testFiles = glob(__DIR__ . '/**/*UITest.php');

foreach ($testFiles as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    // Replace ->visit('/path') with ->visitAndWait('/path')
    $content = preg_replace(
        '/->visit\(([\'"])([^\'"]+)\1\)\s*\n\s*->pause\((\d+)\)/',
        '->visitAndWait($1$2$1, $3)',
        $content
    );
    
    // Replace ->visit('/path') followed by assertPathIs with visitAndWait
    $content = preg_replace(
        '/->visit\(([\'"])([^\'"]+)\1\)\s*\n\s*->assertPathIs/',
        '->visitAndWait($1$2$1)->assertPathIs',
        $content
    );
    
    // Add pause after visit if not already present
    $content = preg_replace(
        '/->visit\(([\'"])([^\'"]+)\1\)\s*\n(?!\s*->pause)/',
        "->visitAndWait($1$2$1)\n",
        $content
    );
    
    // Ensure minimum wait time of 2000ms for Livewire
    $content = preg_replace(
        '/->pause\((\d+)\)/',
        function($matches) {
            $time = (int)$matches[1];
            return $time < 2000 ? '->pause(2000)' : $matches[0];
        },
        $content
    );
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Updated: $file\n";
    }
}

echo "Done!\n";

