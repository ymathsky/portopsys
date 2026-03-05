<?php
/**
 * Application Configuration
 * Queue Management System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Manila');

// Application Settings
define('APP_NAME', 'Port Queuing Management System');
define('APP_VERSION', '1.0.0');

// Auto-detect BASE_URL from current host, or use override from env.php
if (!defined('APP_BASE_URL')) {
    require_once __DIR__ . '/env.php'; // ensures APP_BASE_URL is defined
}
if (APP_BASE_URL !== '') {
    define('BASE_URL', rtrim(APP_BASE_URL, '/'));
} else {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Strip subdirectory only on localhost/127 setups (e.g. localhost/qs)
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    // Walk up to find the project root (the folder containing /config)
    // Works for both domain root (/portopsys.com/) and subdirectory (/qs/)
    $basePath = '';
    if (preg_match('|^.*/qs|', $scriptDir, $m)) {
        $basePath = $m[0]; // e.g. /qs
    }
    define('BASE_URL', $scheme . '://' . $host . $basePath);
}

// Path Settings
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CONFIG_PATH', ROOT_PATH . '/config');

// SMS Configuration (Semaphore, Twilio, or other provider)
define('SMS_ENABLED', false);
define('SMS_API_KEY', ''); // Add your SMS provider API key here
define('SMS_API_URL', 'https://api.semaphore.co/api/v4/messages'); // Example for Semaphore

// Email Configuration  (credentials live in env.php)
// EMAIL_ENABLED, SMTP_HOST, SMTP_PORT, SMTP_SECURE, SMTP_USER, SMTP_PASS, SMTP_FROM_NAME
// are all defined in env.php so they can differ between local and hosting.
if (!defined('EMAIL_ENABLED')) define('EMAIL_ENABLED', false);
if (!defined('SMTP_HOST'))     define('SMTP_HOST', 'localhost');
if (!defined('SMTP_PORT'))     define('SMTP_PORT', 587);
if (!defined('SMTP_SECURE'))   define('SMTP_SECURE', 'tls');
if (!defined('SMTP_USER'))     define('SMTP_USER', '');
if (!defined('SMTP_PASS'))     define('SMTP_PASS', '');
if (!defined('SMTP_FROM_NAME'))define('SMTP_FROM_NAME', APP_NAME);
define('EMAIL_FROM', SMTP_USER);
define('EMAIL_FROM_NAME', SMTP_FROM_NAME);

// Security
define('PASSWORD_SALT', 'QMS_2026_SECURE_SALT'); // Change this to a random string
define('STATUS_CHANGE_PIN', '1234'); // PIN required to change live trip status (change this!)

// Display Settings
define('TOKENS_PER_PAGE', 50);
define('DISPLAY_REFRESH_INTERVAL', 3); // seconds
define('AUTO_CALL_INTERVAL', 300); // seconds (5 minutes)

// Priority Weights (higher = more priority)
define('PRIORITY_WEIGHTS', [
    'emergency'  => 100,
    'urgent'     => 90,
    'hazmat'     => 80,
    'perishable' => 70,
    'express'    => 60,
    'senior'     => 55,
    'pwd'        => 55,
    'pregnant'   => 55,
    'student'    => 52,
    'regular'    => 50,
]);

// Include database connection
require_once CONFIG_PATH . '/database.php';

// Helper Functions
function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/admin/login.php');
    }
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function hasPermission($requiredRole) {
    $roles = ['counter_staff' => 1, 'admin' => 2, 'super_admin' => 3];
    $userRole = getUserRole();
    
    if (!isset($roles[$userRole]) || !isset($roles[$requiredRole])) {
        return false;
    }
    
    return $roles[$userRole] >= $roles[$requiredRole];
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function jsonResponse($success, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

function formatTime($timestamp) {
    return date('h:i A', strtotime($timestamp));
}

function formatDate($timestamp) {
    return date('M d, Y', strtotime($timestamp));
}

function formatDateTime($timestamp) {
    return date('M d, Y h:i A', strtotime($timestamp));
}

function calculateWaitTime($queuePosition, $avgServiceTime) {
    return $queuePosition * $avgServiceTime;
}
