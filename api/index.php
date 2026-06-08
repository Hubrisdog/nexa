<?php

// Check if running on Vercel
if (getenv('VERCEL') === '1' || getenv('APP_ENV') === 'production') {
    $targetDb = '/tmp/database.sqlite';
    $sourceDb = __DIR__ . '/../database/database.sqlite';
    
    // Ensure the target database file exists in the writable /tmp folder
    if (!file_exists($targetDb)) {
        if (file_exists($sourceDb)) {
            copy($sourceDb, $targetDb);
            chmod($targetDb, 0666);
        } else {
            touch($targetDb);
            chmod($targetDb, 0666);
        }
    }
    
    // Override the DB_DATABASE environment variable dynamically
    putenv("DB_DATABASE={$targetDb}");
    $_ENV['DB_DATABASE'] = $targetDb;
    $_SERVER['DB_DATABASE'] = $targetDb;
}

require __DIR__ . '/../public/index.php';
