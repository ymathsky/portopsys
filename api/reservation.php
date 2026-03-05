<?php
/**
 * API Endpoint: Reservation (Pre-booking)
 *
 * GET  /api/reservation.php?code=RES-XXXXXX
 *      → Returns reservation details or 404
 *
 * POST /api/reservation.php  { action: 'create', ... }
 *      → Creates an advance reservation, returns reservation_code
 *
 * POST /api/reservation.php  { action: 'redeem', code: 'RES-....' }
 *      → Converts a pending reservation to a live waiting token
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TokenManager.php';
require_once __DIR__ . '/../includes/SettingsManager.php';

// ── GET: look up a reservation ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $code = trim($_GET['code'] ?? '');
    if ($code === '') {
        jsonResponse(false, 'Reservation code is required', null, 400);
    }

    $tokenManager = new TokenManager();
    $reservation  = $tokenManager->getReservationByCode($code);

    if (!$reservation) {
        jsonResponse(false, 'Reservation not found', null, 404);
    }

    // Sanitise before returning (don't expose internal IDs etc. unless needed)
    $out = [
        'reservation_code'  => $reservation['reservation_code'],
        'reserved_for_date' => $reservation['reserved_for_date'],
        'token_number'      => $reservation['token_number'],
        'status'            => $reservation['status'],
        'customer_name'     => $reservation['customer_name'],
        'customer_mobile'   => $reservation['customer_mobile'],
        'service_name'      => $reservation['service_name'],
        'vessel_name'       => $reservation['vessel_name']    ?? null,
        'vessel_type'       => $reservation['vessel_type']    ?? null,
        'departure_time'    => $reservation['departure_time'] ?? null,
        'arrival_time'      => $reservation['arrival_time']   ?? null,
        'route_name'        => $reservation['route_name']     ?? null,
        'priority_type'     => $reservation['priority_type'],
        'passenger_count'   => $reservation['passenger_count'],
        'fare_paid'         => $reservation['fare_paid'],
        'id'                => $reservation['id'],
    ];
    jsonResponse(true, 'Reservation found', $out);
}

// ── POST ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    $settingsManager = new SettingsManager();
    $tokenManager    = new TokenManager();

    switch ($action) {

        // ── Create a new reservation ─────────────────────────────────────────
        case 'create':
            // Check feature is enabled
            if (!$settingsManager->getSetting('prebooking_enabled', '1')) {
                jsonResponse(false, 'Pre-booking is currently disabled', null, 503);
            }

            // Validate required fields
            $required = ['service_category_id', 'priority_type', 'customer_name',
                         'customer_mobile', 'reserved_for_date'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    jsonResponse(false, "Field '{$field}' is required", null, 400);
                }
            }

            // Validate priority type
            $allowedPriority = ['regular', 'senior', 'pwd', 'pregnant', 'emergency'];
            if (!in_array($input['priority_type'], $allowedPriority)) {
                jsonResponse(false, 'Invalid priority type', null, 400);
            }

            // Validate date
            $advanceDays = (int)$settingsManager->getSetting('prebooking_advance_days', '7');
            $reservedDate = $input['reserved_for_date'];
            $today      = date('Y-m-d');
            $maxDate    = date('Y-m-d', strtotime("+{$advanceDays} days"));
            if ($reservedDate <= $today || $reservedDate > $maxDate) {
                jsonResponse(false, "Reservation date must be between tomorrow and {$maxDate}", null, 400);
            }

            $customerData = [
                'name'   => strip_tags(trim($input['customer_name'])),
                'mobile' => trim($input['customer_mobile']),
                'email'  => trim($input['customer_email'] ?? ''),
            ];
            $bookingData = [
                'schedule_id'      => !empty($input['schedule_id'])    ? intval($input['schedule_id'])    : null,
                'reserved_for_date'=> $reservedDate,
                'fare_paid'        => !empty($input['fare_paid'])       ? floatval($input['fare_paid'])    : 0,
                'passenger_count'  => !empty($input['passenger_count']) ? intval($input['passenger_count']): 1,
            ];
            $vesselId = !empty($input['vessel_id']) ? intval($input['vessel_id']) : null;

            $result = $tokenManager->createReservation(
                intval($input['service_category_id']),
                $input['priority_type'],
                $customerData,
                $vesselId,
                $bookingData
            );

            jsonResponse(true, 'Reservation created successfully', $result);
            break;

        // ── Redeem an existing reservation ───────────────────────────────────
        case 'redeem':
            $code = trim($input['code'] ?? '');
            if ($code === '') {
                jsonResponse(false, 'Reservation code is required', null, 400);
            }

            $token = $tokenManager->redeemReservation($code);
            jsonResponse(true, 'Reservation redeemed! You are now in the queue.', [
                'token_number'      => $token['token_number'],
                'queue_position'    => $token['queue_position'],
                'estimated_wait'    => $token['estimated_wait_time'],
                'service_name'      => $token['service_name'],
                'vessel_name'       => $token['vessel_name'] ?? null,
                'priority_type'     => $token['priority_type'],
            ]);
            break;

        default:
            jsonResponse(false, 'Invalid action', null, 400);
    }
}

// Fallback
jsonResponse(false, 'Method not allowed', null, 405);
