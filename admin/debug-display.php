<?php
/**
 * Temporary diagnostic for display settings
 * DELETE THIS FILE after debugging
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();
if (!hasPermission('admin')) die('Access denied');

$db = getDB();
$rows = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'display_%'")->fetchAll();

header('Content-Type: text/plain');
echo "=== DISPLAY SETTINGS IN DATABASE ===\n";
if (empty($rows)) {
    echo "(no display_ rows found in system_settings)\n";
} else {
    foreach ($rows as $r) {
        echo $r['setting_key'] . " = " . $r['setting_value'] . "\n";
    }
}

echo "\n=== VIDEO FILE CHECK ===\n";
foreach ($rows as $r) {
    if ($r['setting_key'] === 'display_bg_video_url') {
        $url = $r['setting_value'];
        echo "Stored path : " . $url . "\n";
        $absPath = __DIR__ . '/../' . $url;
        echo "Absolute    : " . $absPath . "\n";
        echo "File exists : " . (file_exists($absPath) ? "YES" : "NO") . "\n";
        echo "Public URL  : " . BASE_URL . '/' . $url . "\n";
    }
}

echo "\n=== UPLOADS/VIDEOS DIR ===\n";
$dir = __DIR__ . '/../uploads/videos/';
echo "Dir exists  : " . (is_dir($dir) ? "YES" : "NO") . "\n";
echo "Writable    : " . (is_writable($dir) ? "YES" : "NO") . "\n";
if (is_dir($dir)) {
    $files = scandir($dir);
    $files = array_diff($files, ['.', '..']);
    echo "Files       : " . (empty($files) ? "(none)" : implode(', ', $files)) . "\n";
}

echo "\n=== PHP UPLOAD LIMITS ===\n";
echo "upload_max_filesize : " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size       : " . ini_get('post_max_size') . "\n";
