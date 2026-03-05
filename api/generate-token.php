<?php
/**
 * API Endpoint: Generate Token
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TokenManager.php';
require_once __DIR__ . '/../includes/PortManager.php';
require_once __DIR__ . '/../includes/SettingsManager.php';
require_once __DIR__ . '/../includes/AuditLogger.php';
require_once __DIR__ . '/../includes/Mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

// Check service mode before anything else
$settingsManager = new SettingsManager();
$serviceMode = $settingsManager->getSetting('service_mode', 'online');
if ($serviceMode !== 'online') {
    $modeMessage = $settingsManager->getSetting('service_mode_message', 'System is currently offline.');
    jsonResponse(false, $modeMessage, ['service_mode' => $serviceMode], 503);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['service_category_id'])) {
        jsonResponse(false, 'Service category is required', null, 400);
    }
    
    $serviceCategoryId = intval($input['service_category_id']);
    $priorityType = $input['priority_type'] ?? 'regular';
    
    // Validate priority type
    $validPriorities = ['regular', 'senior', 'pwd', 'pregnant', 'student', 'emergency'];
    if (!in_array($priorityType, $validPriorities)) {
        jsonResponse(false, 'Invalid priority type', null, 400);
    }
    
    // Customer data
    $customerData = [
        'name' => $input['customer_name'] ?? null,
        'mobile' => $input['customer_mobile'] ?? null,
        'email' => $input['customer_email'] ?? null
    ];
    
    // Extended booking data
    $bookingData = [
        'schedule_id'     => isset($input['schedule_id']) && $input['schedule_id'] ? intval($input['schedule_id']) : null,
        'booking_type'    => in_array($input['booking_type'] ?? '', ['walkin','prebooked']) ? $input['booking_type'] : 'walkin',
        'fare_paid'       => isset($input['fare_paid']) ? floatval($input['fare_paid']) : 0,
        'passenger_count' => isset($input['passenger_count']) ? max(1, intval($input['passenger_count'])) : 1,
    ];
    
    // Get vessel_id if provided
    $vesselId = isset($input['vessel_id']) && $input['vessel_id'] ? intval($input['vessel_id']) : null;
    
    // Check vessel capacity if vessel_id is provided
    if ($vesselId) {
        $portManager = new PortManager();
        $vessel = $portManager->getVesselById($vesselId);
        $remaining = $portManager->getRemainingCapacity($vesselId);
        $passengerCount = $bookingData['passenger_count'] ?? 1;

        if ($remaining !== null && $remaining < $passengerCount) {
            $msg = $remaining <= 0
                ? "This vessel is now full. Maximum {$vessel['max_capacity']} passengers allowed."
                : "Only {$remaining} seat(s) remaining on this vessel. You requested {$passengerCount}.";
            jsonResponse(false, $msg, ['remaining' => $remaining, 'type' => 'capacity_full'], 400);
        }
    }
    
    // Generate token
    $tokenManager = new TokenManager();
    $token = $tokenManager->generateToken($serviceCategoryId, $priorityType, $customerData, $vesselId, $bookingData);
    
    // Get full token details
    $tokenDetails = $tokenManager->getToken($token['token_id']);
    // Add pre-formatted time (server is Asia/Manila via config.php)
    $tokenDetails['issued_at_formatted'] = date('h:i A', strtotime($tokenDetails['issued_at']));
    $tokenDetails['issued_date_formatted'] = date('M d, Y', strtotime($tokenDetails['issued_at']));
    
    AuditLogger::log(
        'token_generated', 'token',
        "Token {$tokenDetails['token_number']} generated for '{$customerData['name']}' (priority: {$priorityType}, booking: {$bookingData['booking_type']})",
        (int)$token['token_id']
    );

    // Send confirmation email if customer provided an address
    Mailer::sendTokenConfirmation($tokenDetails);

    jsonResponse(true, 'Token generated successfully', $tokenDetails, 201);
    
} catch (Exception $e) {
    jsonResponse(false, $e->getMessage(), null, 500);
}
