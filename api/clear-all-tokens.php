<?php
/**
 * API Endpoint: Clear All Tokens
 * Deletes ALL token records (all dates), resets counters and inserts a token_reset entry.
 * POST only — admin/super_admin only
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

if (!isset($_SESSION['user_id'])) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Unauthorized', null, 401);
}
if (!in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])) {
    jsonResponse(false, 'Forbidden', null, 403);
}

try {
    $db = getDB();
    $db->beginTransaction();

    // Delete dependent records first to avoid FK constraint errors
    $db->exec("DELETE FROM token_history");
    $db->exec("DELETE FROM notifications");
    $db->exec("DELETE FROM tokens");

    // Reset all active counters to available
    $db->exec("UPDATE service_counters SET current_status = 'available', updated_at = NOW() WHERE is_active = 1");

    // Insert token reset so numbering restarts at 0001
    $db->exec("INSERT INTO token_resets (reset_at) VALUES (NOW())");

    $db->commit();

    AuditLogger::log('clear_all_tokens', 'system', 'All tokens cleared by admin');

    jsonResponse(true, 'All tokens cleared successfully');

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    jsonResponse(false, 'Clear failed: ' . $e->getMessage(), null, 500);
}
