<?php
/**
 * Reports & Analytics
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TokenManager.php';
require_once __DIR__ . '/../includes/PortManager.php';

requireLogin();

if (!hasPermission('admin')) {
    die('Access denied');
}

$portManager = new PortManager();
$db = getDB();

// Get date range
$startDate  = $_GET['start_date']    ?? date('Y-m-d', strtotime('-7 days'));
$endDate    = $_GET['end_date']      ?? date('Y-m-d');
$summaryDate = $_GET['summary_date'] ?? date('Y-m-d');

// Daily summary for the selected date
$summary = $portManager->getDailySummary($summaryDate);

// ── CSV Export ────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'port_summary_' . $summaryDate . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Port Daily Summary - ' . $summaryDate]);
    fputcsv($out, []);
    fputcsv($out, ['Metric', 'Value']);
    fputcsv($out, ['Total Tokens',      $summary['total_tokens']]);
    fputcsv($out, ['Completed',         $summary['completed']]);
    fputcsv($out, ['Cancelled',         $summary['cancelled']]);
    fputcsv($out, ['No Shows',          $summary['no_shows']]);
    fputcsv($out, ['Still Waiting',     $summary['still_waiting']]);
    fputcsv($out, ['Total Passengers',  $summary['total_passengers']]);
    fputcsv($out, ['Total Revenue (PHP)', number_format($summary['total_revenue'], 2)]);
    fputcsv($out, ['Avg Wait (min)',    round($summary['avg_wait_min'] ?? 0)]);
    fputcsv($out, []);
    if (!empty($summary['by_service'])) {
        fputcsv($out, ['--- By Service ---']);
        fputcsv($out, ['Service', 'Tokens', 'Completed', 'Revenue']);
        foreach ($summary['by_service'] as $row) {
            fputcsv($out, [$row['service_name'], $row['tokens'], $row['completed'], number_format($row['revenue'], 2)]);
        }
    }
    if (!empty($summary['by_vessel'])) {
        fputcsv($out, []);
        fputcsv($out, ['--- By Vessel ---']);
        fputcsv($out, ['Vessel', 'Type', 'Trips', 'Tokens', 'Passengers', 'Completed', 'Revenue']);
        foreach ($summary['by_vessel'] as $row) {
            fputcsv($out, [
                $row['vessel_name'] ?? 'Unknown',
                $row['vessel_type'] ?? '',
                $row['total_trips'],
                $row['tokens'],
                $row['passengers'],
                $row['completed'],
                number_format($row['revenue'], 2)
            ]);
        }
    }
    if (!empty($summary['by_trip'])) {
        fputcsv($out, []);
        fputcsv($out, ['--- By Trip ---']);
        fputcsv($out, ['Schedule', 'Vessel', 'Passengers', 'Revenue']);
        foreach ($summary['by_trip'] as $row) {
            fputcsv($out, [$row['schedule_name'] ?? 'N/A', $row['vessel_name'] ?? 'N/A', $row['passengers'], number_format($row['revenue'], 2)]);
        }
    }
    fclose($out);
    exit;
}

// Daily statistics
$stmt = $db->prepare("
    SELECT * FROM daily_statistics
    WHERE date BETWEEN ? AND ?
    ORDER BY date DESC
");
$stmt->execute([$startDate, $endDate]);
$dailyStats = $stmt->fetchAll();

// Service category breakdown
$stmt = $db->prepare("
    SELECT 
        sc.name as service_name,
        COUNT(t.id) as total_tokens,
        COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed,
        AVG(CASE WHEN t.actual_wait_time IS NOT NULL THEN t.actual_wait_time END) as avg_wait,
        AVG(CASE WHEN t.service_duration IS NOT NULL THEN t.service_duration END) as avg_service
    FROM service_categories sc
    LEFT JOIN tokens t ON sc.id = t.service_category_id AND DATE(t.issued_at) BETWEEN ? AND ?
    GROUP BY sc.id, sc.name
    ORDER BY total_tokens DESC
");
$stmt->execute([$startDate, $endDate]);
$serviceStats = $stmt->fetchAll();

// Counter performance
$stmt = $db->prepare("
    SELECT 
        c.counter_number,
        c.counter_name,
        COUNT(t.id) as tokens_served,
        AVG(CASE WHEN t.service_duration IS NOT NULL THEN t.service_duration END) as avg_service_time
    FROM service_counters c
    LEFT JOIN tokens t ON c.id = t.counter_id AND DATE(t.issued_at) BETWEEN ? AND ?
    GROUP BY c.id, c.counter_number, c.counter_name
    ORDER BY tokens_served DESC
");
$stmt->execute([$startDate, $endDate]);
$counterPerformance = $stmt->fetchAll();

$pageTitle = 'Reports & Analytics';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
            <span class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white">📈</span>
            Reports & Analytics
        </h1>
        <p class="text-gray-600 mt-1">View system performance and statistics</p>
    </div>
    
    <!-- Date Filter -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <button type="submit" class="px-8 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition font-medium shadow-md">
                Generate Report
            </button>
        </form>
    </div>

    <!-- ─── Daily Summary ─────────────────────────────────────── -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <span class="w-7 h-7 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center text-base">📋</span>
                Daily Summary
            </h3>
            <form method="GET" class="flex items-center gap-3 flex-wrap">
                <!-- preserve the range filter params -->
                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                <input type="hidden" name="end_date"   value="<?php echo htmlspecialchars($endDate); ?>">
                <input type="date" name="summary_date" value="<?php echo htmlspecialchars($summaryDate); ?>"
                       class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-indigo-500 outline-none">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors">Load</button>
                <a href="?summary_date=<?php echo htmlspecialchars($summaryDate); ?>&export=csv"
                   class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">
                    ⬇ Export CSV
                </a>
            </form>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-4 text-center">
                <div class="text-3xl font-black text-indigo-600"><?php echo $summary['total_tokens']; ?></div>
                <div class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">Total Tokens</div>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 text-center">
                <div class="text-3xl font-black text-green-600"><?php echo $summary['completed']; ?></div>
                <div class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">Completed</div>
            </div>
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 text-center">
                <div class="text-3xl font-black text-blue-600"><?php echo number_format($summary['total_passengers']); ?></div>
                <div class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">Passengers</div>
            </div>
            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-4 text-center">
                <div class="text-3xl font-black text-emerald-600">₱<?php echo number_format($summary['total_revenue'], 0); ?></div>
                <div class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">Revenue</div>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-red-50 rounded-xl p-3 text-center">
                <div class="text-2xl font-bold text-red-500"><?php echo $summary['cancelled']; ?></div>
                <div class="text-xs text-gray-500 mt-0.5">Cancelled</div>
            </div>
            <div class="bg-orange-50 rounded-xl p-3 text-center">
                <div class="text-2xl font-bold text-orange-500"><?php echo $summary['no_shows']; ?></div>
                <div class="text-xs text-gray-500 mt-0.5">No Shows</div>
            </div>
            <div class="bg-yellow-50 rounded-xl p-3 text-center">
                <div class="text-2xl font-bold text-yellow-600"><?php echo $summary['still_waiting']; ?></div>
                <div class="text-xs text-gray-500 mt-0.5">Still Waiting</div>
            </div>
            <div class="bg-purple-50 rounded-xl p-3 text-center">
                <div class="text-2xl font-bold text-purple-600"><?php echo round($summary['avg_wait_min'] ?? 0); ?> min</div>
                <div class="text-xs text-gray-500 mt-0.5">Avg Wait</div>
            </div>
        </div>

        <?php if (!empty($summary['by_service'])): ?>
        <div class="mb-5">
            <h4 class="text-sm font-bold text-gray-700 mb-3">By Service</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Service</th>
                            <th class="text-center px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Tokens</th>
                            <th class="text-center px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Completed</th>
                            <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($summary['by_service'] as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5 font-medium text-gray-800"><?php echo htmlspecialchars($row['service_name']); ?></td>
                            <td class="px-4 py-2.5 text-center text-gray-600"><?php echo $row['tokens']; ?></td>
                            <td class="px-4 py-2.5 text-center text-green-600"><?php echo $row['completed']; ?></td>
                            <td class="px-4 py-2.5 text-right font-semibold text-emerald-700">₱<?php echo number_format($row['revenue'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($summary['by_vessel'])): ?>
        <div class="mb-5">
            <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                <span class="text-base">&#x1F6A2;</span> By Vessel
            </h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Vessel</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Type</th>
                            <th class="text-center px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Trips</th>
                            <th class="text-center px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Tokens</th>
                            <th class="text-center px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Passengers</th>
                            <th class="text-center px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Completed</th>
                            <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($summary['by_vessel'] as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5 font-semibold text-gray-800"><?php echo htmlspecialchars($row['vessel_name'] ?? 'Unknown'); ?></td>
                            <td class="px-4 py-2.5 text-gray-500 text-xs uppercase"><?php echo htmlspecialchars($row['vessel_type'] ?? ''); ?></td>
                            <td class="px-4 py-2.5 text-center text-gray-600"><?php echo $row['total_trips']; ?></td>
                            <td class="px-4 py-2.5 text-center text-gray-600"><?php echo $row['tokens']; ?></td>
                            <td class="px-4 py-2.5 text-center text-blue-600"><?php echo number_format($row['passengers']); ?></td>
                            <td class="px-4 py-2.5 text-center text-green-600"><?php echo $row['completed']; ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-emerald-700">&#x20B1;<?php echo number_format($row['revenue'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                        <tr>
                            <td colspan="6" class="px-4 py-2.5 text-right text-xs font-bold text-gray-600 uppercase">Total Revenue</td>
                            <td class="px-4 py-2.5 text-right font-black text-emerald-700">&#x20B1;<?php echo number_format(array_sum(array_column($summary['by_vessel'], 'revenue')), 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($summary['by_trip'])): ?>
        <div>
            <h4 class="text-sm font-bold text-gray-700 mb-3">By Trip</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Schedule</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Vessel</th>
                            <th class="text-center px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Passengers</th>
                            <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($summary['by_trip'] as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5 font-medium text-gray-800"><?php echo htmlspecialchars($row['schedule_name'] ?? 'Unknown'); ?></td>
                            <td class="px-4 py-2.5 text-gray-600"><?php echo htmlspecialchars($row['vessel_name'] ?? 'N/A'); ?></td>
                            <td class="px-4 py-2.5 text-center text-blue-600"><?php echo number_format($row['passengers']); ?></td>
                            <td class="px-4 py-2.5 text-right font-semibold text-emerald-700">₱<?php echo number_format($row['revenue'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($summary['total_tokens'] === 0): ?>
        <div class="text-center py-8 text-gray-400">
            <div class="text-4xl mb-2">📭</div>
            <p>No tokens found for <?php echo htmlspecialchars($summaryDate); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- ─── Daily Statistics ─────────────────────────────────── -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Daily Statistics
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Completed</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Cancelled</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">No Show</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Avg Wait</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Avg Service</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Max Wait</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($dailyStats as $stat): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?php echo formatDate($stat['date']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo number_format($stat['total_tokens']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><span class="text-green-600 font-semibold"><?php echo number_format($stat['completed_tokens']); ?></span></td>
                            <td class="px-6 py-4 whitespace-nowrap"><span class="text-gray-500"><?php echo number_format($stat['cancelled_tokens']); ?></span></td>
                            <td class="px-6 py-4 whitespace-nowrap"><span class="text-red-600"><?php echo number_format($stat['no_show_tokens']); ?></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo round($stat['avg_wait_time'] ?? 0); ?> min</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo round($stat['avg_service_time'] ?? 0); ?> min</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo round($stat['max_wait_time'] ?? 0); ?> min</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Service Category Breakdown -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
            </svg>
            Service Category Performance
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Service</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Total Tokens</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Completed</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Completion Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Avg Wait</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Avg Service</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($serviceStats as $stat): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($stat['service_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo number_format($stat['total_tokens']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-green-600 font-semibold"><?php echo number_format($stat['completed']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                $rate = $stat['total_tokens'] > 0 ? ($stat['completed'] / $stat['total_tokens']) * 100 : 0;
                                echo round($rate) . '%';
                                ?>
                            </td>
                            <td><?php echo round($stat['avg_wait'] ?? 0); ?></td>
                            <td><?php echo round($stat['avg_service'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Counter Performance -->
    <div class="section-card">
        <h3>Counter Performance</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Counter</th>
                        <th>Tokens Served</th>
                        <th>Avg Service Time (min)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($counterPerformance as $perf): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($perf['counter_number']); ?> - <?php echo htmlspecialchars($perf['counter_name']); ?></td>
                            <td><?php echo number_format($perf['tokens_served']); ?></td>
                            <td><?php echo round($perf['avg_service_time'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
