<?php
/**
 * API Endpoint: Get / Toggle Service Mode
 * GET  → returns current mode
 * POST → admin toggles mode (online / offline)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/SettingsManager.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

$settingsManager = new SettingsManager();

// GET — public, used by display board and token kiosk
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse(true, 'OK', [
        'mode'    => $settingsManager->getSetting('service_mode', 'online'),
        'message' => $settingsManager->getSetting('service_mode_message', ''),
    ]);
    exit;
}

// POST — admin only
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
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $newMode = $input['mode'] ?? null;
    if (!in_array($newMode, ['online', 'offline'])) {
        jsonResponse(false, 'Invalid mode. Use "online" or "offline"', null, 400);
    }

    $message = trim($input['message'] ?? '');
    if ($newMode === 'offline' && empty($message)) {
        $message = 'System is temporarily offline for maintenance. Please check back shortly.';
    }

    $settingsManager->updateSetting('service_mode', $newMode);
    $settingsManager->updateSetting('service_mode_message', $message);

    AuditLogger::log(
        'update', 'settings',
        "Service mode changed to '{$newMode}'" . ($message ? ": {$message}" : '')
    );

    jsonResponse(true, "Service mode set to {$newMode}", [
        'mode'    => $newMode,
        'message' => $message,
    ]);

} catch (Exception $e) {
    jsonResponse(false, $e->getMessage(), null, 500);
}
