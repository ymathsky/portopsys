<?php
/**
 * API Endpoint: Get Token Details
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TokenManager.php';

try {
    if (!isset($_GET['token_number']) && !isset($_GET['token_id'])) {
        jsonResponse(false, 'Token number or ID is required', null, 400);
    }
    
    $tokenManager = new TokenManager();
    $db = getDB();
    
    if (isset($_GET['token_id'])) {
        $tokenId = intval($_GET['token_id']);
        $token = $tokenManager->getToken($tokenId);
    } else {
        $tokenNumber = sanitize($_GET['token_number']);
        $stmt = $db->prepare("
            SELECT t.*, sc.name as service_name, sc.code as service_code,
                   c.counter_number, c.counter_name
            FROM tokens t
            INNER JOIN service_categories sc ON t.service_category_id = sc.id
            LEFT JOIN service_counters c ON t.counter_id = c.id
            WHERE t.token_number = ?
            ORDER BY t.issued_at DESC
            LIMIT 1
        ");
        $stmt->execute([$tokenNumber]);
        $token = $stmt->fetch();
    }
    
    if (!$token) {
        jsonResponse(false, 'Token not found', null, 404);
    }

    // Check QR expiry (15 minutes from issuance)
    if (!empty($token['qr_expires_at']) && strtotime($token['qr_expires_at']) < time()) {
        jsonResponse(false, 'This QR code has expired. It was valid for 15 minutes from time of issuance.', ['expired' => true, 'token_number' => $token['token_number']], 410);
    }

    // Recalculate estimated wait time if still waiting
    if ($token['status'] === 'waiting') {
        $stmt = $db->prepare("
            SELECT COUNT(*) as ahead
            FROM tokens
            WHERE status = 'waiting'
            AND service_category_id = ?
            AND issued_at < ?
        ");
        $stmt->execute([$token['service_category_id'], $token['issued_at']]);
        $result = $stmt->fetch();
        $token['tokens_ahead'] = $result['ahead'];
    }
    
    jsonResponse(true, 'Token found', $token);
    
} catch (Exception $e) {
    jsonResponse(false, $e->getMessage(), null, 500);
}
