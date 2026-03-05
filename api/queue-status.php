<?php
/**
 * API Endpoint: Get Queue Status
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TokenManager.php';
require_once __DIR__ . '/../includes/PortManager.php';

try {
    $tokenManager = new TokenManager();
    $portManager  = new PortManager();
    
    $type = $_GET['type'] ?? 'all';
    
    switch ($type) {
        case 'waiting':
            $data = $tokenManager->getWaitingQueue();
            break;
            
        case 'serving':
            $data = $tokenManager->getCurrentlyServing();
            break;
            
        case 'stats':
            $data = $tokenManager->getTodayStatistics();
            break;

        case 'announcements':
            $location = $_GET['location'] ?? 'display';
            $data = $portManager->getActiveAnnouncements($location);
            break;
            
        case 'all':
        default:
            $data = [
                'waiting' => $tokenManager->getWaitingQueue(10),
                'serving' => $tokenManager->getCurrentlyServing(),
                'stats' => $tokenManager->getTodayStatistics()
            ];
            break;
    }
    
    jsonResponse(true, 'Queue data retrieved', $data);
    
} catch (Exception $e) {
    jsonResponse(false, $e->getMessage(), null, 500);
}
