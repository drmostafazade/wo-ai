#!/usr/bin/env php
<?php
echo "Checking PHP syntax for all files...\n\n";

$errors = [];
$dir = __DIR__;

// Check all PHP files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $output = shell_exec("php -l " . escapeshellarg($file->getPathname()) . " 2>&1");
        if (strpos($output, 'No syntax errors') === false) {
            $errors[] = [
                'file' => $file->getPathname(),
                'error' => $output
            ];
        }
    }
}

if (empty($errors)) {
    echo "✅ All PHP files have valid syntax!\n";
} else {
    echo "❌ Found syntax errors:\n\n";
    foreach ($errors as $error) {
        echo "File: " . $error['file'] . "\n";
        echo "Error: " . $error['error'] . "\n\n";
    }
}
