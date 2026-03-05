<?php
/**
 * API Endpoint: Get Services
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/ServiceManager.php';

try {
    $serviceManager = new ServiceManager();
    
    $type = $_GET['type'] ?? 'categories';
    
    switch ($type) {
        case 'categories':
            $data = $serviceManager->getActiveCategories();
            break;
            
        case 'counters':
            $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';
            $data = $serviceManager->getCounters($activeOnly);
            break;
            
        case 'counter_status':
            $data = $serviceManager->getCounterStatus();
            break;
            
        default:
            jsonResponse(false, 'Invalid type parameter', null, 400);
    }
    
    jsonResponse(true, 'Data retrieved successfully', $data);
    
} catch (Exception $e) {
    jsonResponse(false, $e->getMessage(), null, 500);
}
