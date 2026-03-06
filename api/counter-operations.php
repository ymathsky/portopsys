<?php
/**
 * API Endpoint: Counter Operations
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TokenManager.php';
require_once __DIR__ . '/../includes/ServiceManager.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// For API endpoints, return JSON 401 instead of redirecting to login page
// (requireLogin() redirects, which breaks fetch() calls from the counter UI)
if (!isLoggedIn()) {
    jsonResponse(false, 'Session expired — please log in again', null, 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    $db = getDB();
    $tokenManager = new TokenManager();
    $serviceManager = new ServiceManager();
    
    switch ($action) {
        case 'call_next':
            if (!isset($input['counter_id'])) {
                jsonResponse(false, 'Counter ID is required', null, 400);
            }
            
            $counterId = intval($input['counter_id']);
            $nextToken = $tokenManager->callNextToken($counterId);
            
            if ($nextToken) {
                AuditLogger::log('call_token', 'counter', "Called next token at counter ID {$counterId}: " . ($nextToken['token_number'] ?? ''), (int)($nextToken['id'] ?? 0));
                jsonResponse(true, 'Token called successfully', $nextToken);
            } else {
                jsonResponse(true, 'No tokens in queue', null);
            }
            break;
            
        case 'call_specific_token':
            if (!isset($input['counter_id']) || !isset($input['token_number'])) {
                jsonResponse(false, 'Counter ID and Token Number are required', null, 400);
            }
            
            $counterId = intval($input['counter_id']);
            $tokenNumber = trim($input['token_number']);
            
            // Find token by token_number
            $token = $tokenManager->getTokenByNumber($tokenNumber);
            
            if (!$token) {
                jsonResponse(false, 'Token not found', null, 404);
            }
            
            if ($token['status'] !== 'waiting' && $token['status'] !== 'pending') {
                jsonResponse(false, 'Token is not in waiting status (Current: ' . $token['status'] . ')', null, 400);
            }
            
            // Call the specific token
            $calledToken = $tokenManager->callSpecificToken($token['id'], $counterId);
            
            if ($calledToken) {
                AuditLogger::log('call_token', 'counter', "Called specific token {$tokenNumber} at counter ID {$counterId}", (int)($token['id']));
                jsonResponse(true, 'Token called successfully', $calledToken);
            } else {
                jsonResponse(false, 'Failed to call token', null, 500);
            }
            break;
            
        case 'start_serving':
            if (!isset($input['token_id']) || !isset($input['counter_id'])) {
                jsonResponse(false, 'Token ID and Counter ID are required', null, 400);
            }
            
            $tokenManager->startServing($input['token_id'], $input['counter_id']);
            AuditLogger::log('status_change', 'counter', "Started serving token ID {$input['token_id']} at counter ID {$input['counter_id']}", (int)$input['token_id']);
            jsonResponse(true, 'Service started');
            break;
            
        case 'complete':
            if (!isset($input['token_id'])) {
                jsonResponse(false, 'Token ID is required', null, 400);
            }
            
            $notes = $input['notes'] ?? null;
            $tokenManager->completeToken($input['token_id'], $notes);
            AuditLogger::log('complete', 'counter', "Completed token ID {$input['token_id']}", (int)$input['token_id']);
            jsonResponse(true, 'Token completed successfully');
            break;
            
        case 'no_show':
            if (!isset($input['token_id'])) {
                jsonResponse(false, 'Token ID is required', null, 400);
            }
            
            $tokenManager->markNoShow($input['token_id']);
            AuditLogger::log('no_show', 'counter', "Marked token ID {$input['token_id']} as no-show", (int)$input['token_id']);
            jsonResponse(true, 'Token marked as no-show');
            break;
            
        case 'cancel':
            if (!isset($input['token_id'])) {
                jsonResponse(false, 'Token ID is required', null, 400);
            }
            
            $reason = $input['reason'] ?? null;
            $tokenManager->cancelToken($input['token_id'], $reason);
            AuditLogger::log('cancel', 'counter', "Cancelled token ID {$input['token_id']}" . ($reason ? ": {$reason}" : ''), (int)$input['token_id']);
            jsonResponse(true, 'Token cancelled successfully');
            break;
            
        case 'recall_token':
            // Re-announce a token that was already called (customer may have missed it)
            if (!isset($input['token_id'])) {
                jsonResponse(false, 'Token ID is required', null, 400);
            }

            $recalledToken = $tokenManager->recallToken($input['token_id']);
            AuditLogger::log('recall_token', 'counter', "Recalled token ID {$input['token_id']} (recall #{$recalledToken['recall_count']})", (int)$input['token_id']);
            jsonResponse(true, 'Token recalled — please announce again', $recalledToken);
            break;

        case 'transfer_token':
            // Move a token to a different service counter
            if (!isset($input['token_id']) || !isset($input['new_counter_id'])) {
                jsonResponse(false, 'token_id and new_counter_id are required', null, 400);
            }

            $transferredToken = $tokenManager->transferToken($input['token_id'], intval($input['new_counter_id']));
            AuditLogger::log('transfer_token', 'counter',
                "Transferred token ID {$input['token_id']} to counter ID {$input['new_counter_id']}",
                (int)$input['token_id']);
            jsonResponse(true, 'Token transferred successfully', $transferredToken);
            break;

        case 'mass_call':
            // Call up to N (max 10) next-priority tokens at this counter
            if (!isset($input['counter_id'])) {
                jsonResponse(false, 'Counter ID is required', null, 400);
            }

            $counterId   = intval($input['counter_id']);
            $batchSize   = max(1, min(10, intval($input['count'] ?? 5)));

            // Get service IDs for this counter
            $stmt = $db->prepare("SELECT service_category_id FROM counter_services WHERE counter_id = ?");
            $stmt->execute([$counterId]);
            $serviceIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($serviceIds)) {
                jsonResponse(false, 'No services assigned to this counter', null, 400);
            }

            $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
            $stmt = $db->prepare("
                SELECT t.id
                FROM tokens t
                INNER JOIN service_categories sc ON t.service_category_id = sc.id
                WHERE t.status = 'waiting'
                AND t.service_category_id IN ($placeholders)
                ORDER BY
                    CASE t.priority_type
                        WHEN 'emergency' THEN 1
                        WHEN 'urgent'    THEN 1
                        WHEN 'senior'    THEN 2
                        WHEN 'pwd'       THEN 2
                        WHEN 'pregnant'  THEN 2
                        WHEN 'student'   THEN 3
                        ELSE 4
                    END,
                    sc.priority_level DESC,
                    t.issued_at ASC
                LIMIT ?
            ");
            $stmt->execute(array_merge($serviceIds, [$batchSize]));
            $tokenIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($tokenIds)) {
                jsonResponse(true, 'No tokens in queue', []);
            }

            $called = [];
            $db->beginTransaction();
            try {
                foreach ($tokenIds as $tid) {
                    $db->prepare("UPDATE tokens SET status='called', counter_id=?, called_at=NOW() WHERE id=?")
                       ->execute([$counterId, $tid]);
                    AuditLogger::log('call_token', 'counter', "Mass-called token ID {$tid} at counter ID {$counterId}", (int)$tid);
                    $called[] = $tokenManager->getToken($tid);
                }
                // Mark counter as serving
                $db->prepare("UPDATE service_counters SET current_status='serving', updated_at=NOW() WHERE id=?")
                   ->execute([$counterId]);
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }

            jsonResponse(true, count($called) . ' token(s) called', $called);
            break;

        case 'update_counter_status':
            if (!isset($input['counter_id']) || !isset($input['status'])) {
                jsonResponse(false, 'Counter ID and status are required', null, 400);
            }
            
            $staffName = $input['staff_name'] ?? $_SESSION['full_name'];
            $serviceManager->updateCounterStatus($input['counter_id'], $input['status'], $staffName);
            AuditLogger::log('status_change', 'counter', "Counter ID {$input['counter_id']} set to '{$input['status']}'", (int)$input['counter_id']);
            jsonResponse(true, 'Counter status updated');
            break;
            
        default:
            jsonResponse(false, 'Invalid action', null, 400);
    }
    
} catch (Exception $e) {
    jsonResponse(false, $e->getMessage(), null, 500);
}
