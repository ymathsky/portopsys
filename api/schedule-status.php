<?php
/**
 * API: Live schedule statuses for today
 * Returns JSON array of schedule status/capacity for client-side polling
 */
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/PortManager.php';

try {
    $portManager = new PortManager();
    $schedules   = $portManager->getTodaySchedules(date('Y-m-d'));

    $result = [];
    foreach ($schedules as $s) {
        $cap       = $s['capacity_per_trip'] ?? null;
        $booked    = (int)($s['passengers_booked'] ?? 0);
        $isFull    = $cap !== null && $cap > 0 && $booked >= $cap;
        $capPct    = ($cap && $cap > 0) ? min(100, round($booked / $cap * 100)) : 0;
        $remaining  = ($cap && $cap > 0) ? max(0, (int)$cap - $booked) : null;
        $isDisabled = in_array($s['trip_status'], ['cancelled', 'departed']) || $isFull;

        $result[] = [
            'id'           => (int)$s['id'],
            'trip_status'  => $s['trip_status'] ?? 'on_time',
            'delay_reason' => $s['delay_reason'] ?? null,
            'cap_total'    => $cap ? (int)$cap : null,
            'cap_booked'   => $booked,
            'cap_remaining'=> $remaining,
            'cap_pct'      => $capPct,
            'is_full'      => $isFull,
            'is_disabled'  => $isDisabled,
        ];
    }

    echo json_encode(['success' => true, 'data' => $result, 'ts' => time()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
