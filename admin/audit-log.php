<?php
/**
 * Admin — Audit Log Viewer
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

requireLogin();

if (!hasPermission('admin')) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$db = getDB();

/* ── Filters ─────────────────────────────────────────────────── */
$fModule    = trim($_GET['module']    ?? '');
$fAction    = trim($_GET['action']    ?? '');
$fUser      = trim($_GET['user']      ?? '');
$fDateFrom  = trim($_GET['date_from'] ?? date('Y-m-d'));
$fDateTo    = trim($_GET['date_to']   ?? date('Y-m-d'));
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 50;
$offset     = ($page - 1) * $perPage;

/* ── Export CSV ──────────────────────────────────────────────── */
$isExport = isset($_GET['export']) && $_GET['export'] === 'csv';

/* ── Build WHERE ─────────────────────────────────────────────── */
$where  = [];
$params = [];

if ($fModule)   { $where[] = 'module    = ?'; $params[] = $fModule; }
if ($fAction)   { $where[] = 'action    = ?'; $params[] = $fAction; }
if ($fUser)     { $where[] = 'username LIKE ?'; $params[] = "%{$fUser}%"; }
if ($fDateFrom) { $where[] = 'DATE(created_at) >= ?'; $params[] = $fDateFrom; }
if ($fDateTo)   { $where[] = 'DATE(created_at) <= ?'; $params[] = $fDateTo; }

$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ── Count total ─────────────────────────────────────────────── */
$countStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs $whereStr");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

/* ── Fetch rows ──────────────────────────────────────────────── */
if ($isExport) {
    $dataStmt = $db->prepare("
        SELECT id, created_at, username, action, module, description, record_id, ip_address
        FROM audit_logs $whereStr
        ORDER BY id DESC
    ");
} else {
    $dataStmt = $db->prepare("
        SELECT * FROM audit_logs $whereStr
        ORDER BY id DESC
        LIMIT $perPage OFFSET $offset
    ");
}
$dataStmt->execute($params);
$logs = $dataStmt->fetchAll();

/* ── Distinct filter options ─────────────────────────────────── */
$modules = $db->query("SELECT DISTINCT module FROM audit_logs ORDER BY module")->fetchAll(PDO::FETCH_COLUMN);
$actions = $db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

/* ── CSV export ──────────────────────────────────────────────── */
if ($isExport) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_log_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Timestamp', 'Username', 'Action', 'Module', 'Description', 'Record ID', 'IP Address']);
    foreach ($logs as $row) {
        fputcsv($out, [
            $row['id'], $row['created_at'], $row['username'] ?? '—',
            $row['action'], $row['module'], $row['description'],
            $row['record_id'] ?? '—', $row['ip_address'] ?? '—',
        ]);
    }
    fclose($out);
    exit;
}

/* ── Badge helpers ───────────────────────────────────────────── */
function actionBadge(string $action): string {
    $map = [
        'login'            => 'bg-green-100 text-green-700',
        'logout'           => 'bg-gray-100 text-gray-600',
        'login_failed'     => 'bg-red-100 text-red-700',
        'create'           => 'bg-blue-100 text-blue-700',
        'update'           => 'bg-indigo-100 text-indigo-700',
        'delete'           => 'bg-red-100 text-red-700',
        'status_change'    => 'bg-yellow-100 text-yellow-700',
        'pin_change'       => 'bg-purple-100 text-purple-700',
        'password_change'  => 'bg-purple-100 text-purple-700',
        'call_token'       => 'bg-cyan-100 text-cyan-700',
        'complete'         => 'bg-green-100 text-green-700',
        'no_show'          => 'bg-orange-100 text-orange-700',
        'cancel'           => 'bg-red-100 text-red-700',
        'token_generated'  => 'bg-teal-100 text-teal-700',
    ];
    $cls = $map[$action] ?? 'bg-gray-100 text-gray-600';
    return "<span class=\"px-2 py-0.5 rounded-full text-xs font-bold {$cls}\">" . htmlspecialchars($action) . "</span>";
}

function moduleBadge(string $module): string {
    $map = [
        'auth'         => 'bg-violet-100 text-violet-700',
        'user'         => 'bg-blue-100 text-blue-700',
        'vessel'       => 'bg-sky-100 text-sky-700',
        'schedule'     => 'bg-indigo-100 text-indigo-700',
        'service'      => 'bg-cyan-100 text-cyan-700',
        'announcement' => 'bg-amber-100 text-amber-700',
        'token'        => 'bg-teal-100 text-teal-700',
        'counter'      => 'bg-green-100 text-green-700',
    ];
    $cls = $map[$module] ?? 'bg-gray-100 text-gray-500';
    return "<span class=\"px-2 py-0.5 rounded-full text-xs font-semibold {$cls}\">" . htmlspecialchars(ucfirst($module)) . "</span>";
}

$pageTitle = 'Audit Log';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <span class="w-10 h-10 bg-gradient-to-br from-slate-500 to-gray-700 rounded-xl flex items-center justify-center text-white text-lg">&#x1F4CB;</span>
                Audit Log
            </h1>
            <p class="text-gray-500 mt-1 text-sm">Complete trail of all system actions &mdash; <?php echo number_format($totalRows); ?> record<?php echo $totalRows !== 1 ? 's' : ''; ?> found</p>
        </div>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['export'=>'csv'])); ?>"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-50 transition shadow-sm">
            &#x2193; Export CSV
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Module</label>
                <select name="module" class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="">All</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $fModule === $m ? 'selected' : ''; ?>>
                            <?php echo ucfirst(htmlspecialchars($m)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Action</label>
                <select name="action" class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="">All</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $fAction === $a ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($a); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">User</label>
                <input type="text" name="user" value="<?php echo htmlspecialchars($fUser); ?>"
                    placeholder="Username..."
                    class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($fDateFrom); ?>"
                    class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($fDateTo); ?>"
                    class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-1.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Filter</button>
                <a href="audit-log.php" class="px-3 py-1.5 border border-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">Reset</a>
            </div>
        </div>
    </form>

    <!-- Stats strip -->
    <?php
    $statsStmt = $db->prepare("
        SELECT action, COUNT(*) AS cnt FROM audit_logs
        WHERE DATE(created_at) = CURDATE()
        GROUP BY action
    ");
    $statsStmt->execute();
    $todayStats = [];
    foreach ($statsStmt->fetchAll() as $r) $todayStats[$r['action']] = $r['cnt'];

    $highlight = [
        'login_failed'    => ['label'=>'Failed logins today', 'color'=>'text-red-600',    'bg'=>'bg-red-50'],
        'delete'          => ['label'=>'Deletions today',     'color'=>'text-red-600',    'bg'=>'bg-red-50'],
        'status_change'   => ['label'=>'Status changes today','color'=>'text-yellow-600', 'bg'=>'bg-yellow-50'],
        'token_generated' => ['label'=>'Tokens today',        'color'=>'text-teal-700',   'bg'=>'bg-teal-50'],
    ];
    ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <?php foreach ($highlight as $key => $h): ?>
        <div class="<?php echo $h['bg']; ?> rounded-xl border border-gray-200 p-3 flex items-center gap-3">
            <div class="flex-1">
                <p class="text-xs text-gray-500"><?php echo $h['label']; ?></p>
                <p class="text-2xl font-black <?php echo $h['color']; ?>"><?php echo $todayStats[$key] ?? 0; ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Log table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-40">Timestamp</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Module</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">IP</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-8"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center py-12 text-gray-400">No audit records found for the selected filters.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $i => $log): ?>
                <tr class="hover:bg-gray-50 transition <?php echo $log['action'] === 'login_failed' ? 'bg-red-50' : ($log['action'] === 'delete' ? 'bg-orange-50/40' : ''); ?>">
                    <td class="px-4 py-3 text-xs text-gray-500 font-mono whitespace-nowrap">
                        <?php echo date('M d H:i:s', strtotime($log['created_at'])); ?>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($log['username']): ?>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-6 h-6 rounded-md bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center">
                                    <?php echo strtoupper(substr($log['username'], 0, 1)); ?>
                                </span>
                                <span class="font-medium text-gray-900"><?php echo htmlspecialchars($log['username']); ?></span>
                            </span>
                        <?php else: ?>
                            <span class="text-gray-400 text-xs italic">guest</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3"><?php echo moduleBadge($log['module']); ?></td>
                    <td class="px-4 py-3"><?php echo actionBadge($log['action']); ?></td>
                    <td class="px-4 py-3 text-gray-700 max-w-xs truncate" title="<?php echo htmlspecialchars($log['description']); ?>">
                        <?php echo htmlspecialchars($log['description']); ?>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400 font-mono"><?php echo htmlspecialchars($log['ip_address'] ?? '—'); ?></td>
                    <td class="px-4 py-3">
                        <?php if (!empty($log['old_values']) || !empty($log['new_values'])): ?>
                        <button onclick="toggleDiff(<?php echo $log['id']; ?>)"
                            class="text-indigo-500 hover:text-indigo-700 transition text-xs font-medium" title="Show diff">
                            &#x1F4C4;
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($log['old_values']) || !empty($log['new_values'])): ?>
                <tr id="diff-<?php echo $log['id']; ?>" class="hidden bg-slate-50">
                    <td colspan="7" class="px-6 py-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <?php if (!empty($log['old_values'])): ?>
                            <div>
                                <p class="text-gray-500 font-semibold mb-1 uppercase tracking-wide">Before</p>
                                <pre class="bg-red-50 border border-red-200 rounded-lg p-3 overflow-x-auto text-red-800 whitespace-pre-wrap"><?php echo htmlspecialchars(json_encode(json_decode($log['old_values']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($log['new_values'])): ?>
                            <div>
                                <p class="text-gray-500 font-semibold mb-1 uppercase tracking-wide">After</p>
                                <pre class="bg-green-50 border border-green-200 rounded-lg p-3 overflow-x-auto text-green-800 whitespace-pre-wrap"><?php echo htmlspecialchars(json_encode(json_decode($log['new_values']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="border-t border-gray-100 px-6 py-4 flex items-center justify-between">
            <p class="text-sm text-gray-500">
                Showing <?php echo number_format($offset + 1); ?>&ndash;<?php echo number_format(min($offset + $perPage, $totalRows)); ?>
                of <?php echo number_format($totalRows); ?>
            </p>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                    class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">&larr; Prev</a>
                <?php endif; ?>
                <?php
                $start = max(1, $page - 2);
                $end   = min($totalPages, $page + 2);
                for ($p = $start; $p <= $end; $p++):
                ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>"
                    class="px-3 py-1.5 border rounded-lg text-sm transition <?php echo $p === $page ? 'bg-indigo-600 text-white border-indigo-600 font-semibold' : 'border-gray-200 text-gray-600 hover:bg-gray-50'; ?>">
                    <?php echo $p; ?>
                </a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                    class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">Next &rarr;</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleDiff(id) {
    const row = document.getElementById('diff-' + id);
    if (row) row.classList.toggle('hidden');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
