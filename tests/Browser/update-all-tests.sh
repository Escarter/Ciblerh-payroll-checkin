#!/bin/bash

# Script to update all browser test files with proper waits

find tests/Browser -name "*UITest.php" -type f | while read file; do
    # Add visitAndWait helper usage
    sed -i '' 's/->visit(\([^)]*\))$/->visitAndWait(\1)/g' "$file"
    sed -i '' 's/->visit(\([^)]*\)) ->pause/->visitAndWait(\1)/g' "$file"
    
    # Ensure minimum pause times
    sed -i '' 's/->pause(\([0-9]\+\))/->pause(2000)/g' "$file"
    
    echo "Updated: $file"
done

echo "All test files updated!"

