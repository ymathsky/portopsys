<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TokenManager.php';
require_once __DIR__ . '/../includes/ServiceManager.php';

$db = getDB();

echo "<pre>";
try {
    $sm = new ServiceManager();
    $status = $sm->getCounterStatus();
    echo "getCounterStatus: OK (" . count($status) . " counters)\n";
} catch (Throwable $e) {
    echo "getCounterStatus ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

try {
    $tm = new TokenManager();
    $stats = $tm->getTodayStatistics();
    echo "getTodayStatistics: OK\n";
} catch (Throwable $e) {
    echo "getTodayStatistics ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

try {
    $sm2 = new ServiceManager();
    $cs = $sm2->getCounters();
    echo "getCounters: OK (" . count($cs) . ")\n";
} catch (Throwable $e) {
    echo "getCounters ERROR: " . $e->getMessage() . "\n";
}

// Check for PortManager
try {
    require_once __DIR__ . '/../includes/PortManager.php';
    $pm = new PortManager();
    echo "PortManager: OK\n";
} catch (Throwable $e) {
    echo "PortManager ERROR: " . $e->getMessage() . "\n";
}

// Check dashboard query directly
try {
    $r = $db->query("SELECT * FROM daily_statistics LIMIT 1");
    echo "daily_statistics view: OK\n";
} catch (Throwable $e) {
    echo "daily_statistics ERROR: " . $e->getMessage() . "\n";
}

try {
    $r = $db->query("SELECT * FROM counter_status_view LIMIT 1");
    echo "counter_status_view: OK\n";
} catch (Throwable $e) {
    echo "counter_status_view ERROR: " . $e->getMessage() . "\n";
}
echo "</pre>";
