<?php
/**
 * API Endpoint: End-of-Day Reset
 * POST → admin/super_admin only
 * 1. Cancels all waiting/called tokens
 * 2. Completes all serving tokens
 * 3. Resets all active counters to 'available'
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

// Auth check
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

    // 1. Cancel all waiting / called tokens (today)
    $stmt = $db->prepare("
        UPDATE tokens
        SET status = 'cancelled',
            notes  = CASE WHEN notes IS NULL OR notes = '' THEN 'End of day reset' ELSE CONCAT(notes, ' | End of day reset') END
        WHERE status IN ('waiting', 'called')
          AND DATE(issued_at) = CURDATE()
    ");
    $stmt->execute();
    $cancelledCount = $stmt->rowCount();

    // 2. Complete any still-serving tokens (today)
    $stmt = $db->prepare("
        UPDATE tokens
        SET status       = 'completed',
            completed_at = NOW(),
            notes        = CASE WHEN notes IS NULL OR notes = '' THEN 'Auto-completed at end of day' ELSE CONCAT(notes, ' | Auto-completed at end of day') END
        WHERE status = 'serving'
          AND DATE(issued_at) = CURDATE()
    ");
    $stmt->execute();
    $completedCount = $stmt->rowCount();

    // 3. Reset all active counters back to 'available'
    $stmt = $db->prepare("
        UPDATE service_counters
        SET current_status = 'available',
            updated_at     = NOW()
        WHERE is_active = 1
          AND current_status NOT IN ('closed')
    ");
    $stmt->execute();
    $countersReset = $stmt->rowCount();

    // 4. Reset all standard schedules' trip_status back to on_time for the next day
    $stmt = $db->prepare("
        UPDATE standard_schedules
        SET trip_status  = 'on_time',
            delay_reason = ''
        WHERE is_active = 1
    ");
    $stmt->execute();
    $statusesReset = $stmt->rowCount();

    $db->commit();

    AuditLogger::log(
        'system_reset', 'system',
        "End-of-day reset: {$cancelledCount} cancelled, {$completedCount} completed, {$countersReset} counters reset, {$statusesReset} trip statuses reset"
    );

    jsonResponse(true, 'End-of-day reset completed successfully', [
        'cancelled'      => $cancelledCount,
        'auto_completed' => $completedCount,
        'counters_reset' => $countersReset,
        'statuses_reset' => $statusesReset,
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    jsonResponse(false, 'Reset failed: ' . $e->getMessage(), null, 500);
}
