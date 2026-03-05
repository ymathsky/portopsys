<?php
/**
 * Passenger Manifest – per trip, exportable & printable
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/PortManager.php';

requireLogin();

$db          = getDB();
$portManager = new PortManager();

// ── Parameters ────────────────────────────────────────────────
$date        = $_GET['date']        ?? date('Y-m-d');
$scheduleId  = isset($_GET['schedule_id']) && $_GET['schedule_id'] !== '' ? (int)$_GET['schedule_id'] : null;
$vesselId    = isset($_GET['vessel_id'])   && $_GET['vessel_id']   !== '' ? (int)$_GET['vessel_id']   : null;
$export      = $_GET['export'] ?? '';

// ── All vessels for filter dropdown ───────────────────────────
$allVessels = $portManager->getAllVessels();

// ── Today's trips for selected date ───────────────────────────
// Queries both standard_schedules (recurring) and ad-hoc walk-ins with no schedule
$dayName = strtolower(date('l', strtotime($date)));

$tripSql = "
    SELECT s.id AS schedule_id,
           s.schedule_name,
           s.trip_number_prefix,
           s.origin,
           s.destination,
           s.departure_time,
           s.arrival_time,
           s.fare,
           s.capacity_per_trip,
           s.trip_status,
           v.id   AS vessel_id,
           v.name AS vessel_name,
           v.type AS vessel_type,
           COUNT(t.id)                        AS token_count,
           COALESCE(SUM(t.passenger_count),0) AS pax_count,
           COALESCE(SUM(CASE WHEN t.status = 'completed'  THEN t.passenger_count ELSE 0 END),0) AS pax_boarded,
           COALESCE(SUM(CASE WHEN t.status = 'cancelled'  THEN t.passenger_count ELSE 0 END),0) AS pax_cancelled,
           COALESCE(SUM(CASE WHEN t.status NOT IN ('cancelled') THEN t.fare_paid  ELSE 0 END),0) AS revenue
    FROM standard_schedules s
    LEFT JOIN vessels v ON s.vessel_id = v.id
    LEFT JOIN tokens  t ON t.schedule_id = s.id AND DATE(t.issued_at) = ?
    WHERE s.is_active = 1
      AND s.$dayName = 1
      AND s.effective_from <= ?
      AND (s.effective_until IS NULL OR s.effective_until >= ?)
";
$params = [$date, $date, $date];

if ($vesselId) {
    $tripSql .= " AND s.vessel_id = ?";
    $params[] = $vesselId;
}
$tripSql .= " GROUP BY s.id ORDER BY s.departure_time ASC";

$stmt = $db->prepare($tripSql);
$stmt->execute($params);
$trips = $stmt->fetchAll();

// Also pull walk-in tokens for this date that have vessel_id but NO schedule_id
$walkinSql = "
    SELECT v.id AS vessel_id, v.name AS vessel_name, v.type AS vessel_type,
           COUNT(t.id)                        AS token_count,
           COALESCE(SUM(t.passenger_count),0) AS pax_count,
           COALESCE(SUM(CASE WHEN t.status = 'completed'  THEN t.passenger_count ELSE 0 END),0) AS pax_boarded,
           COALESCE(SUM(CASE WHEN t.status = 'cancelled'  THEN t.passenger_count ELSE 0 END),0) AS pax_cancelled,
           COALESCE(SUM(CASE WHEN t.status NOT IN ('cancelled') THEN t.fare_paid  ELSE 0 END),0) AS revenue
    FROM tokens t
    JOIN vessels v ON t.vessel_id = v.id
    WHERE DATE(t.issued_at) = ?
      AND t.schedule_id IS NULL
";
$wParams = [$date];
if ($vesselId) { $walkinSql .= " AND t.vessel_id = ?"; $wParams[] = $vesselId; }
$walkinSql .= " GROUP BY v.id ORDER BY v.name";
$stmt2 = $db->prepare($walkinSql);
$stmt2->execute($wParams);
$walkinGroups = $stmt2->fetchAll();

// ── Manifest rows for selected trip ───────────────────────────
$manifest    = [];
$tripInfo    = null;
if ($scheduleId !== null) {
    // Fetch trip meta
    $stmt = $db->prepare("
        SELECT s.*, v.name AS vessel_name, v.type AS vessel_type, v.max_capacity
        FROM standard_schedules s
        LEFT JOIN vessels v ON s.vessel_id = v.id
        WHERE s.id = ?
    ");
    $stmt->execute([$scheduleId]);
    $tripInfo = $stmt->fetch();

    // Fetch passengers
    $stmt = $db->prepare("
        SELECT t.*,
               cat.name AS service_name,
               sc.counter_number,
               sc.counter_name
        FROM tokens t
        LEFT JOIN service_categories  cat ON t.service_category_id = cat.id
        LEFT JOIN service_counters    sc  ON t.counter_id = sc.id
        WHERE t.schedule_id = ?
          AND DATE(t.issued_at) = ?
        ORDER BY t.issued_at ASC
    ");
    $stmt->execute([$scheduleId, $date]);
    $manifest = $stmt->fetchAll();
} elseif ($vesselId !== null && isset($_GET['walkin'])) {
    // Walk-in vessel manifest (no schedule)
    $stmt = $db->prepare("
        SELECT v.name AS vessel_name, v.type AS vessel_type, v.max_capacity
        FROM vessels v WHERE v.id = ?
    ");
    $stmt->execute([$vesselId]);
    $vMeta = $stmt->fetch();
    if ($vMeta) {
        $tripInfo = array_merge($vMeta, [
            'schedule_name'     => 'Walk-in / No Schedule',
            'origin'            => '—',
            'destination'       => '—',
            'departure_time'    => null,
            'arrival_time'      => null,
            'fare'              => null,
            'capacity_per_trip' => $vMeta['max_capacity'] ?? null,
            'trip_status'       => 'on_time',
            'trip_number_prefix'=> '',
        ]);
        $stmt2 = $db->prepare("
            SELECT t.*,
                   cat.name AS service_name,
                   sc.counter_number,
                   sc.counter_name
            FROM tokens t
            LEFT JOIN service_categories cat ON t.service_category_id = cat.id
            LEFT JOIN service_counters   sc  ON t.counter_id = sc.id
            WHERE t.vessel_id = ?
              AND t.schedule_id IS NULL
              AND DATE(t.issued_at) = ?
            ORDER BY t.issued_at ASC
        ");
        $stmt2->execute([$vesselId, $date]);
        $manifest = $stmt2->fetchAll();
    }
}

// ── CSV Export ────────────────────────────────────────────────
if ($export === 'csv' && $tripInfo) {
    $vesselLabel    = $tripInfo['vessel_name'] ?? 'Unknown';
    $schedLabel     = $tripInfo['schedule_name'] ?? ($tripInfo['trip_number_prefix'] ?? 'Trip');
    $filename       = 'manifest_' . $vesselLabel . '_' . $date . '.csv';
    $filename       = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    // BOM for Excel UTF-8
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['PASSENGER MANIFEST']);
    fputcsv($out, ['Vessel',    $vesselLabel]);
    fputcsv($out, ['Trip',      $schedLabel]);
    fputcsv($out, ['Route',     ($tripInfo['origin'] ?? '—') . ' → ' . ($tripInfo['destination'] ?? '—')]);
    fputcsv($out, ['Departure', $tripInfo['departure_time'] ? date('h:i A', strtotime($tripInfo['departure_time'])) : '—']);
    fputcsv($out, ['Date',      $date]);
    fputcsv($out, ['Status',    strtoupper($tripInfo['trip_status'] ?? 'ON TIME')]);
    fputcsv($out, []);
    fputcsv($out, ['#', 'Token No.', 'Passenger Name', 'Mobile', 'Email', 'Priority', 'Pax Count', 'Booking Type', 'Service', 'Status', 'Fare Paid (PHP)', 'Issued At', 'Completed At']);
    $row = 1;
    $totalPax = 0; $totalFare = 0;
    foreach ($manifest as $p) {
        fputcsv($out, [
            $row++,
            $p['token_number'],
            $p['customer_name']   ?? '(anonymous)',
            $p['customer_mobile'] ?? '—',
            $p['customer_email']  ?? '—',
            ucfirst($p['priority_type']),
            $p['passenger_count'],
            ucfirst($p['booking_type']),
            $p['service_name']    ?? '—',
            ucfirst($p['status']),
            number_format($p['fare_paid'], 2),
            $p['issued_at']    ? date('Y-m-d H:i', strtotime($p['issued_at']))    : '—',
            $p['completed_at'] ? date('Y-m-d H:i', strtotime($p['completed_at'])) : '—',
        ]);
        if ($p['status'] !== 'cancelled') {
            $totalPax  += (int)$p['passenger_count'];
            $totalFare += (float)$p['fare_paid'];
        }
    }
    fputcsv($out, []);
    fputcsv($out, ['', '', '', '', '', 'TOTAL', $totalPax, '', '', '', number_format($totalFare, 2)]);
    fclose($out);
    exit;
}

// ── Print-friendly view ───────────────────────────────────────
if ($export === 'print' && $tripInfo) {
    $paxTotal    = array_sum(array_column(array_filter($manifest, fn($r) => $r['status'] !== 'cancelled'), 'passenger_count'));
    $fareTotal   = array_sum(array_column(array_filter($manifest, fn($r) => $r['status'] !== 'cancelled'), 'fare_paid'));
    $boardedTotal= array_sum(array_column(array_filter($manifest, fn($r) => $r['status'] === 'completed'),  'passenger_count'));
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Passenger Manifest – <?php echo htmlspecialchars($tripInfo['vessel_name'] ?? 'Vessel'); ?> – <?php echo $date; ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 11pt; color: #111; background: #fff; }
  .print-page { max-width: 900px; margin: 0 auto; padding: 20px; }
  .header { border-bottom: 3px double #333; padding-bottom: 12px; margin-bottom: 16px; }
  .header h1 { font-size: 18pt; font-weight: bold; }
  .header .sub { font-size: 11pt; color: #555; margin-top: 4px; }
  .meta-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px 24px; margin: 14px 0 18px; padding: 12px; background: #f4f4f4; border-radius: 6px; }
  .meta-grid dt { font-size: 9pt; font-weight: bold; text-transform: uppercase; color: #777; }
  .meta-grid dd { font-size: 11pt; font-weight: 600; }
  .stat-row { display: flex; gap: 20px; margin-bottom: 18px; }
  .stat-box { flex: 1; border: 1px solid #ddd; border-radius: 6px; padding: 10px 14px; text-align: center; }
  .stat-box .num { font-size: 20pt; font-weight: bold; }
  .stat-box .lbl { font-size: 9pt; color: #777; text-transform: uppercase; }
  table { width: 100%; border-collapse: collapse; font-size: 10pt; }
  thead tr { background: #1e293b; color: #fff; }
  thead th { padding: 8px 10px; text-align: left; font-weight: 600; }
  tbody tr:nth-child(even) { background: #f8f9fa; }
  tbody td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
  .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8.5pt; font-weight: 600; }
  .badge-completed  { background: #dcfce7; color: #166534; }
  .badge-waiting    { background: #fef9c3; color: #854d0e; }
  .badge-serving    { background: #dbeafe; color: #1e40af; }
  .badge-called     { background: #e0e7ff; color: #3730a3; }
  .badge-cancelled  { background: #fee2e2; color: #991b1b; text-decoration: line-through; }
  .badge-no_show    { background: #f3f4f6; color: #6b7280; }
  .priority-senior, .priority-pwd, .priority-pregnant { font-weight: bold; color: #b45309; }
  .priority-emergency { font-weight: bold; color: #dc2626; }
  .priority-student { color: #1d4ed8; }
  .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 12px; font-size: 9pt; color: #888; display: flex; justify-content: space-between; }
  .no-print { margin-bottom: 16px; }
  @media print {
    .no-print { display: none !important; }
    body { font-size: 10pt; }
    .print-page { padding: 0; }
  }
</style>
</head>
<body>
<div class="print-page">
  <!-- Print / Back buttons -->
  <div class="no-print" style="display:flex;gap:10px;margin-bottom:14px;">
    <button onclick="window.print()" style="padding:8px 20px;background:#1e293b;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:12pt;">🖨️ Print</button>
    <a href="manifest.php?date=<?php echo urlencode($date); ?>&schedule_id=<?php echo $scheduleId ?? ''; ?>&vessel_id=<?php echo $vesselId ?? ''; ?>" style="padding:8px 16px;background:#e5e7eb;color:#111;border-radius:6px;text-decoration:none;font-size:12pt;">← Back</a>
  </div>

  <div class="header">
    <h1>🚢 Passenger Manifest</h1>
    <div class="sub">Generated: <?php echo date('F d, Y h:i A'); ?> &nbsp;|&nbsp; Page 1</div>
  </div>

  <dl class="meta-grid">
    <div><dt>Vessel</dt><dd><?php echo htmlspecialchars($tripInfo['vessel_name'] ?? '—'); ?></dd></div>
    <div><dt>Trip / Schedule</dt><dd><?php echo htmlspecialchars($tripInfo['schedule_name'] ?? $tripInfo['trip_number_prefix'] ?? '—'); ?></dd></div>
    <div><dt>Date</dt><dd><?php echo date('F d, Y', strtotime($date)); ?></dd></div>
    <div><dt>Origin</dt><dd><?php echo htmlspecialchars($tripInfo['origin'] ?? '—'); ?></dd></div>
    <div><dt>Destination</dt><dd><?php echo htmlspecialchars($tripInfo['destination'] ?? '—'); ?></dd></div>
    <div><dt>Status</dt><dd><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $tripInfo['trip_status'] ?? 'ON TIME'))); ?></dd></div>
    <div><dt>Departure</dt><dd><?php echo $tripInfo['departure_time'] ? date('h:i A', strtotime($tripInfo['departure_time'])) : '—'; ?></dd></div>
    <div><dt>Arrival</dt><dd><?php echo $tripInfo['arrival_time'] ? date('h:i A', strtotime($tripInfo['arrival_time'])) : '—'; ?></dd></div>
    <div><dt>Capacity</dt><dd><?php echo ($tripInfo['capacity_per_trip'] ?? $tripInfo['max_capacity'] ?? '—'); ?></dd></div>
  </dl>

  <div class="stat-row">
    <div class="stat-box"><div class="num"><?php echo count($manifest); ?></div><div class="lbl">Tokens</div></div>
    <div class="stat-box"><div class="num"><?php echo $paxTotal; ?></div><div class="lbl">Passengers</div></div>
    <div class="stat-box"><div class="num"><?php echo $boardedTotal; ?></div><div class="lbl">Boarded</div></div>
    <div class="stat-box"><div class="num">₱<?php echo number_format($fareTotal, 2); ?></div><div class="lbl">Revenue</div></div>
  </div>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Token No.</th>
        <th>Passenger Name</th>
        <th>Mobile</th>
        <th>Priority</th>
        <th>Pax</th>
        <th>Booking</th>
        <th>Status</th>
        <th>Fare (PHP)</th>
        <th>Issued</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($manifest)): ?>
      <tr><td colspan="10" style="text-align:center;color:#888;padding:30px;">No passengers recorded for this trip.</td></tr>
      <?php else: ?>
      <?php $i = 1; foreach ($manifest as $p): ?>
      <tr>
        <td><?php echo $i++; ?></td>
        <td style="font-weight:bold;font-family:monospace;"><?php echo htmlspecialchars($p['token_number']); ?></td>
        <td><?php echo htmlspecialchars($p['customer_name'] ?? '(anonymous)'); ?></td>
        <td><?php echo htmlspecialchars($p['customer_mobile'] ?? '—'); ?></td>
        <td class="priority-<?php echo $p['priority_type']; ?>"><?php echo ucfirst($p['priority_type']); ?></td>
        <td style="text-align:center;"><?php echo $p['passenger_count']; ?></td>
        <td><?php echo ucfirst($p['booking_type']); ?></td>
        <td><span class="badge badge-<?php echo $p['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $p['status'])); ?></span></td>
        <td style="text-align:right;">₱<?php echo number_format($p['fare_paid'], 2); ?></td>
        <td style="font-size:9pt;white-space:nowrap;"><?php echo $p['issued_at'] ? date('h:i A', strtotime($p['issued_at'])) : '—'; ?></td>
      </tr>
      <?php endforeach; ?>
      <tr style="background:#1e293b;color:#fff;font-weight:bold;">
        <td colspan="5" style="text-align:right;padding:8px 10px;">TOTALS</td>
        <td style="text-align:center;"><?php echo $paxTotal; ?></td>
        <td colspan="2"></td>
        <td style="text-align:right;">₱<?php echo number_format($fareTotal, 2); ?></td>
        <td></td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="footer">
    <span>Port Queuing Management System &mdash; Official Passenger Manifest</span>
    <span><?php echo htmlspecialchars($tripInfo['vessel_name'] ?? ''); ?> &mdash; <?php echo $date; ?></span>
  </div>
</div>
</body>
</html>
    <?php
    exit;
}

// ── Normal admin page ─────────────────────────────────────────
$pageTitle = 'Passenger Manifest';
include __DIR__ . '/includes/header.php';

// Totals for current manifest
$manifestPax   = 0; $manifestFare = 0; $manifestBoarded = 0; $manifestCancelled = 0;
foreach ($manifest as $p) {
    if ($p['status'] !== 'cancelled') { $manifestPax += (int)$p['passenger_count']; $manifestFare += (float)$p['fare_paid']; }
    if ($p['status'] === 'completed')  { $manifestBoarded += (int)$p['passenger_count']; }
    if ($p['status'] === 'cancelled')  { $manifestCancelled += (int)$p['passenger_count']; }
}

$statusColors = [
    'on_time'  => 'bg-emerald-100 text-emerald-700',
    'delayed'  => 'bg-amber-100 text-amber-700',
    'cancelled'=> 'bg-red-100 text-red-700',
    'departed' => 'bg-blue-100 text-blue-700',
    'arrived'  => 'bg-purple-100 text-purple-700',
];
$priorityColors = [
    'regular'   => 'bg-gray-100 text-gray-700',
    'senior'    => 'bg-amber-100 text-amber-700',
    'pwd'       => 'bg-orange-100 text-orange-700',
    'pregnant'  => 'bg-pink-100 text-pink-700',
    'student'   => 'bg-blue-100 text-blue-700',
    'emergency' => 'bg-red-100 text-red-700',
    'urgent'    => 'bg-red-100 text-red-700',
];
$tokenColors = [
    'waiting'   => ['bg-amber-100 text-amber-800', '⏳'],
    'called'    => ['bg-indigo-100 text-indigo-800', '📣'],
    'serving'   => ['bg-blue-100 text-blue-800', '⚙️'],
    'completed' => ['bg-green-100 text-green-800', '✅'],
    'cancelled' => ['bg-red-100 text-red-800 line-through', '✖'],
    'no_show'   => ['bg-gray-100 text-gray-600', '👻'],
];
?>

<div class="w-full">
  <!-- ── Page Header ─────────────────────────────────────── -->
  <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
        <span class="w-10 h-10 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl flex items-center justify-center text-white text-xl">📋</span>
        Passenger Manifest
      </h1>
      <p class="text-gray-500 mt-1 text-sm">View, export and print the passenger list for each trip</p>
    </div>
    <?php if ($tripInfo): ?>
    <div class="flex gap-2 flex-wrap">
      <a href="manifest.php?date=<?php echo urlencode($date); ?>&schedule_id=<?php echo $scheduleId ?? ''; ?>&vessel_id=<?php echo $vesselId ?? ''; ?><?php echo isset($_GET['walkin']) ? '&walkin=1' : ''; ?>&export=csv"
         class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow transition">
        📥 Export CSV
      </a>
      <a href="manifest.php?date=<?php echo urlencode($date); ?>&schedule_id=<?php echo $scheduleId ?? ''; ?>&vessel_id=<?php echo $vesselId ?? ''; ?><?php echo isset($_GET['walkin']) ? '&walkin=1' : ''; ?>&export=print"
         target="_blank"
         class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-lg text-sm font-medium shadow transition">
        🖨️ Print Manifest
      </a>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── Filter Bar ──────────────────────────────────────── -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-4">
      <div class="flex-1 min-w-[160px]">
        <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide">Date</label>
        <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
      </div>
      <div class="flex-1 min-w-[180px]">
        <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide">Vessel (optional)</label>
        <select name="vessel_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
          <option value="">All Vessels</option>
          <?php foreach ($allVessels as $v): ?>
          <option value="<?php echo $v['id']; ?>" <?php echo $vesselId === (int)$v['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($v['name']); ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="px-6 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium shadow transition">
        🔍 Filter Trips
      </button>
      <?php if ($scheduleId || ($vesselId && isset($_GET['walkin']))): ?>
      <a href="manifest.php?date=<?php echo urlencode($date); ?><?php echo $vesselId ? '&vessel_id='.$vesselId : ''; ?>"
         class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">
        ← All Trips
      </a>
      <?php endif; ?>
    </form>
  </div>

  <?php if (!$tripInfo): ?>
  <!-- ── Trip List ───────────────────────────────────────── -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
      <span class="text-lg">🗓️</span>
      <h2 class="font-bold text-gray-900">
        Trips on <?php echo date('l, F d Y', strtotime($date)); ?>
      </h2>
      <span class="ml-auto text-sm text-gray-400"><?php echo count($trips) + count($walkinGroups); ?> trip(s)</span>
    </div>

    <?php if (empty($trips) && empty($walkinGroups)): ?>
    <div class="py-16 text-center text-gray-400">
      <div class="text-5xl mb-3">🚢</div>
      <p class="text-lg font-medium">No trips found for this date.</p>
      <p class="text-sm mt-1">Try a different date or vessel filter.</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Trip / Schedule</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Vessel</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Route</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Departure</th>
            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Tokens</th>
            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Pax</th>
            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Revenue</th>
            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Manifest</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($trips as $trip):
            $sc = $statusColors[$trip['trip_status']] ?? 'bg-gray-100 text-gray-700';
          ?>
          <tr class="hover:bg-gray-50 transition">
            <td class="px-5 py-3 font-semibold text-gray-900">
              <?php echo htmlspecialchars($trip['schedule_name']); ?>
              <?php if ($trip['trip_number_prefix']): ?>
              <div class="text-xs text-gray-400 font-normal mt-0.5"><?php echo htmlspecialchars($trip['trip_number_prefix']); ?></div>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3">
              <div class="font-medium text-gray-800"><?php echo htmlspecialchars($trip['vessel_name'] ?? '—'); ?></div>
              <div class="text-xs text-gray-400 capitalize"><?php echo $trip['vessel_type'] ?? ''; ?></div>
            </td>
            <td class="px-5 py-3 text-gray-600">
              <?php echo htmlspecialchars($trip['origin'] ?? '—'); ?> →<br>
              <span class="font-medium text-gray-800"><?php echo htmlspecialchars($trip['destination'] ?? '—'); ?></span>
            </td>
            <td class="px-5 py-3 text-gray-700 font-mono text-xs">
              <?php echo $trip['departure_time'] ? date('h:i A', strtotime($trip['departure_time'])) : '—'; ?>
            </td>
            <td class="px-5 py-3 text-center font-bold text-gray-900"><?php echo $trip['token_count']; ?></td>
            <td class="px-5 py-3 text-center">
              <span class="font-bold text-gray-900"><?php echo $trip['pax_count']; ?></span>
              <?php if ($trip['capacity_per_trip']): ?>
              <span class="text-xs text-gray-400"> / <?php echo $trip['capacity_per_trip']; ?></span>
              <?php endif; ?>
            </td>
            <td class="px-5 py-3 text-right font-medium text-gray-800">₱<?php echo number_format($trip['revenue'], 2); ?></td>
            <td class="px-5 py-3 text-center">
              <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $sc; ?>">
                <?php echo strtoupper(str_replace('_', ' ', $trip['trip_status'] ?? 'on time')); ?>
              </span>
            </td>
            <td class="px-5 py-3 text-center">
              <a href="manifest.php?date=<?php echo urlencode($date); ?>&schedule_id=<?php echo $trip['schedule_id']; ?>"
                 class="inline-flex items-center gap-1 px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-medium transition">
                👁 View
              </a>
            </td>
          </tr>
          <?php endforeach; ?>

          <?php foreach ($walkinGroups as $wg): ?>
          <tr class="hover:bg-gray-50 transition bg-slate-50/50">
            <td class="px-5 py-3 font-semibold text-gray-700">
              <span class="italic text-gray-500">Walk-in (no schedule)</span>
            </td>
            <td class="px-5 py-3">
              <div class="font-medium text-gray-800"><?php echo htmlspecialchars($wg['vessel_name']); ?></div>
              <div class="text-xs text-gray-400 capitalize"><?php echo $wg['vessel_type'] ?? ''; ?></div>
            </td>
            <td class="px-5 py-3 text-gray-400 italic text-xs">—</td>
            <td class="px-5 py-3 text-gray-400 text-xs">—</td>
            <td class="px-5 py-3 text-center font-bold text-gray-900"><?php echo $wg['token_count']; ?></td>
            <td class="px-5 py-3 text-center font-bold text-gray-900"><?php echo $wg['pax_count']; ?></td>
            <td class="px-5 py-3 text-right font-medium text-gray-800">₱<?php echo number_format($wg['revenue'], 2); ?></td>
            <td class="px-5 py-3 text-center"><span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">WALK-IN</span></td>
            <td class="px-5 py-3 text-center">
              <a href="manifest.php?date=<?php echo urlencode($date); ?>&vessel_id=<?php echo $wg['vessel_id']; ?>&walkin=1"
                 class="inline-flex items-center gap-1 px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-medium transition">
                👁 View
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <!-- ── Manifest Detail ─────────────────────────────────── -->

  <!-- Trip Info Card -->
  <div class="bg-gradient-to-r from-teal-600 to-cyan-700 rounded-xl text-white p-5 mb-5 shadow-lg">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <div class="text-teal-100 text-xs font-semibold uppercase tracking-widest mb-1">Trip Manifest</div>
        <h2 class="text-xl font-bold"><?php echo htmlspecialchars($tripInfo['schedule_name'] ?? '—'); ?></h2>
        <div class="text-teal-100 mt-1">
          <?php echo htmlspecialchars($tripInfo['vessel_name'] ?? '—'); ?>
          <?php if (($tripInfo['vessel_type'] ?? '') !== ''): ?>
          &mdash; <span class="capitalize"><?php echo $tripInfo['vessel_type']; ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="text-right">
        <div class="text-teal-100 text-xs">Date</div>
        <div class="font-bold text-lg"><?php echo date('M d, Y', strtotime($date)); ?></div>
      </div>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">
      <div>
        <div class="text-teal-200 text-xs uppercase tracking-wide">Origin</div>
        <div class="font-semibold"><?php echo htmlspecialchars($tripInfo['origin'] ?? '—'); ?></div>
      </div>
      <div>
        <div class="text-teal-200 text-xs uppercase tracking-wide">Destination</div>
        <div class="font-semibold"><?php echo htmlspecialchars($tripInfo['destination'] ?? '—'); ?></div>
      </div>
      <div>
        <div class="text-teal-200 text-xs uppercase tracking-wide">Departure</div>
        <div class="font-semibold"><?php echo $tripInfo['departure_time'] ? date('h:i A', strtotime($tripInfo['departure_time'])) : '—'; ?></div>
      </div>
      <div>
        <div class="text-teal-200 text-xs uppercase tracking-wide">Trip Status</div>
        <div class="font-semibold"><?php echo strtoupper(str_replace('_', ' ', $tripInfo['trip_status'] ?? 'On Time')); ?></div>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
    <?php
      $statCards = [
        ['Total Tokens',    count($manifest),    '🎫', 'bg-white border-gray-200'],
        ['Total Pax',       $manifestPax,         '👥', 'bg-white border-gray-200'],
        ['Boarded',         $manifestBoarded,     '✅', 'bg-emerald-50 border-emerald-200'],
        ['Revenue',         '₱'.number_format($manifestFare,2), '💰', 'bg-white border-gray-200'],
      ];
      foreach ($statCards as [$lbl, $val, $ico, $cls]):
    ?>
    <div class="<?php echo $cls; ?> border rounded-xl p-4 text-center shadow-sm">
      <div class="text-2xl mb-1"><?php echo $ico; ?></div>
      <div class="text-2xl font-bold text-gray-900"><?php echo $val; ?></div>
      <div class="text-xs text-gray-500 uppercase tracking-wide mt-0.5"><?php echo $lbl; ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Passenger Table -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
      <span class="text-lg">🧑‍✈️</span>
      <h2 class="font-bold text-gray-900">Passenger List</h2>
      <span class="ml-auto text-sm text-gray-400"><?php echo count($manifest); ?> record(s)</span>
    </div>

    <?php if (empty($manifest)): ?>
    <div class="py-16 text-center text-gray-400">
      <div class="text-5xl mb-3">🚢</div>
      <p class="text-lg font-medium">No passengers recorded for this trip.</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">#</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Token</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Name</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Mobile</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Priority</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Pax</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Booking</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Fare</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Issued</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php $i = 1; foreach ($manifest as $p):
            [$stCls, $stIco] = $tokenColors[$p['status']] ?? ['bg-gray-100 text-gray-600', '—'];
            $prCls = $priorityColors[$p['priority_type']] ?? 'bg-gray-100 text-gray-700';
            $rowFade = $p['status'] === 'cancelled' ? 'opacity-50' : '';
          ?>
          <tr class="hover:bg-gray-50 transition <?php echo $rowFade; ?>">
            <td class="px-4 py-3 text-gray-400 text-xs"><?php echo $i++; ?></td>
            <td class="px-4 py-3 font-mono font-bold text-gray-900 text-xs"><?php echo htmlspecialchars($p['token_number']); ?></td>
            <td class="px-4 py-3">
              <div class="font-medium text-gray-900"><?php echo htmlspecialchars($p['customer_name'] ?? '(anonymous)'); ?></div>
              <?php if ($p['customer_email']): ?>
              <div class="text-xs text-gray-400"><?php echo htmlspecialchars($p['customer_email']); ?></div>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-gray-600 text-xs"><?php echo htmlspecialchars($p['customer_mobile'] ?? '—'); ?></td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $prCls; ?>">
                <?php echo ucfirst($p['priority_type']); ?>
              </span>
            </td>
            <td class="px-4 py-3 text-center font-bold text-gray-900"><?php echo $p['passenger_count']; ?></td>
            <td class="px-4 py-3 text-gray-600 text-xs capitalize"><?php echo $p['booking_type']; ?></td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $stCls; ?>">
                <?php echo $stIco; ?> <?php echo ucfirst(str_replace('_', ' ', $p['status'])); ?>
              </span>
            </td>
            <td class="px-4 py-3 text-right font-medium text-gray-800">₱<?php echo number_format($p['fare_paid'], 2); ?></td>
            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
              <?php echo $p['issued_at'] ? date('h:i A', strtotime($p['issued_at'])) : '—'; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
          <tr>
            <td colspan="5" class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Totals (excl. cancelled)</td>
            <td class="px-4 py-3 text-center font-bold text-gray-900"><?php echo $manifestPax; ?></td>
            <td colspan="2"></td>
            <td class="px-4 py-3 text-right font-bold text-gray-900">₱<?php echo number_format($manifestFare, 2); ?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
