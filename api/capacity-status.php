<?php
/**
 * API: Real-time vessel capacity status
 * Returns all active vessels with boarded / serving / remaining counts for today
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDB();

    $date = date('Y-m-d');

    $stmt = $db->prepare("
        SELECT
            v.id,
            v.name,
            v.type,
            v.max_capacity,

            /* Completed (boarded) tokens — each may carry multiple passengers */
            COALESCE(SUM(CASE WHEN t.status = 'completed'
                              THEN t.passenger_count ELSE 0 END), 0) AS boarded,

            /* Currently being called or served */
            COALESCE(SUM(CASE WHEN t.status IN ('called','serving')
                              THEN t.passenger_count ELSE 0 END), 0) AS in_service,

            /* Waiting in queue */
            COALESCE(SUM(CASE WHEN t.status = 'waiting'
                              THEN t.passenger_count ELSE 0 END), 0) AS waiting,

            /* Total issued today (all non-cancelled) */
            COALESCE(SUM(CASE WHEN t.status != 'cancelled'
                              THEN t.passenger_count ELSE 0 END), 0) AS total_issued

        FROM vessels v
        LEFT JOIN tokens t
               ON t.vessel_id = v.id
              AND DATE(t.issued_at) = ?
        WHERE v.is_active = 1
        GROUP BY v.id
        ORDER BY v.name ASC
    ");
    $stmt->execute([$date]);
    $vessels = $stmt->fetchAll();

    $result = [];
    foreach ($vessels as $v) {
        $max        = (int) $v['max_capacity'];
        $boarded    = (int) $v['boarded'];
        $inService  = (int) $v['in_service'];
        $waiting    = (int) $v['waiting'];
        $totalIssued= (int) $v['total_issued'];

        $occupied   = $boarded + $inService;      // seats taken or being taken now
        $remaining  = $max > 0 ? max(0, $max - $occupied) : null;
        $pct        = $max > 0 ? min(100, round($occupied / $max * 100)) : null;

        $result[] = [
            'id'           => (int) $v['id'],
            'name'         => $v['name'],
            'type'         => $v['type'],
            'max_capacity' => $max,
            'boarded'      => $boarded,
            'in_service'   => $inService,
            'waiting'      => $waiting,
            'total_issued' => $totalIssued,
            'occupied'     => $occupied,
            'remaining'    => $remaining,
            'pct'          => $pct,
            // status label
            'status' => $max <= 0 ? 'unlimited'
                      : ($pct >= 100 ? 'full'
                      : ($pct >= 80  ? 'almost_full'
                      : 'available')),
        ];
    }

    jsonResponse(true, 'OK', $result);

} catch (Exception $e) {
    jsonResponse(false, $e->getMessage(), null, 500);
}
