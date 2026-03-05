<?php
/**
 * Get Counter Services API
 * Returns the service IDs assigned to a specific counter
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['counter_id'])) {
        throw new Exception('Counter ID is required');
    }
    
    $counterId = $_GET['counter_id'];
    
    $stmt = $db->prepare("SELECT service_category_id FROM counter_services WHERE counter_id = ?");
    $stmt->execute([$counterId]);
    $services = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'services' => array_map('intval', $services)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
