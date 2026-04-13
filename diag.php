<?php
// Temporary diagnostic page - DELETE after use
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>PHP: " . phpversion() . "\n";

// Test env.php
try {
    require_once __DIR__ . '/config/env.php';
    echo "env.php: OK\n";
    echo "APP_BASE_URL: " . APP_BASE_URL . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
} catch (Throwable $e) {
    echo "env.php ERROR: " . $e->getMessage() . "\n";
}

// Test DB connection
try {
    require_once __DIR__ . '/config/config.php';
    $db = getDB();
    echo "DB connection: OK\n";
    // Check tables exist
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
} catch (Throwable $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
