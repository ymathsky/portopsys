<?php
/**
 * API endpoint to get vessel services
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/PortManager.php';

if (!isset($_GET['vessel_id'])) {
    echo json_encode(['error' => 'Vessel ID is required']);
    exit;
}

$vesselId = intval($_GET['vessel_id']);
$portManager = new PortManager();

try {
    $services = $portManager->getVesselServices($vesselId);

    // Always return IDs for backward-compat, plus full data for price display
    $serviceIds = array_map(fn($s) => $s['id'], $services);

    // Build a price map: service_id => effective_price (vessel override ?? service base_price)
    $priceMap = [];
    foreach ($services as $s) {
        $priceMap[$s['id']] = (float)($s['effective_price'] ?? 0);
    }

    // If caller wants full objects (e.g. vessels.php modal), return them
    $withPrices = isset($_GET['with_prices']) && $_GET['with_prices'];

    echo json_encode([
        'success'  => true,
        'services' => $withPrices
            ? array_map(fn($s) => [
                'id'            => $s['id'],
                'price'         => (float)($s['vessel_price'] ?? 0),   // vessel-specific (for modal pre-fill)
                'effective_price' => (float)($s['effective_price'] ?? 0), // what to charge
              ], $services)
            : $serviceIds,
        'prices'   => $priceMap,   // always included for JS convenience
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
