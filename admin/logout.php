<?php
/**
 * Admin Logout
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

AuditLogger::log('logout', 'auth', "User '{$_SESSION['username']}' logged out");
$auth = new Auth();
$auth->logout();

redirect(BASE_URL . '/admin/login.php');
