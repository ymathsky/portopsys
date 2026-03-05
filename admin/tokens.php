<?php
/**
 * Token List & Search
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TokenManager.php';

requireLogin();

$db = getDB();

// Filters
$status = $_GET['status'] ?? 'all';
$date = $_GET['date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT t.*, sc.name as service_name, sc.code as service_code,
               c.counter_number, c.counter_name
        FROM tokens t
        INNER JOIN service_categories sc ON t.service_category_id = sc.id
        LEFT JOIN service_counters c ON t.counter_id = c.id
        WHERE DATE(t.issued_at) = ?";

$params = [$date];

if ($status !== 'all') {
    $sql .= " AND t.status = ?";
    $params[] = $status;
}

if ($search) {
    $sql .= " AND (t.token_number LIKE ? OR t.customer_name LIKE ? OR t.customer_mobile LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$sql .= " ORDER BY t.issued_at DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tokens = $stmt->fetchAll();

$pageTitle = 'Token Management';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
            <span class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white">
                🎫
            </span>
            Token Management
        </h1>
        <p class="text-gray-600 mt-1">View and manage all issued tokens</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date); ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="waiting" <?php echo $status === 'waiting' ? 'selected' : ''; ?>>Waiting</option>
                    <option value="called" <?php echo $status === 'called' ? 'selected' : ''; ?>>Called</option>
                    <option value="serving" <?php echo $status === 'serving' ? 'selected' : ''; ?>>Serving</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="no_show" <?php echo $status === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                </select>
            </div>
            
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Token #, Name, Mobile..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                    Filter
                </button>
                <a href="?" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                    Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Token List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Token #</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Service</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Counter</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Issued At</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Wait Time</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($tokens)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <div class="text-4xl mb-2">📋</div>
                                No tokens found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tokens as $token): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-gray-900"><?php echo htmlspecialchars($token['token_number']); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($token['service_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($token['service_code']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php 
                                    echo match($token['priority_type']) {
                                        'emergency' => 'bg-red-100 text-red-800',
                                        'hazmat' => 'bg-orange-100 text-orange-800',
                                        'perishable' => 'bg-yellow-100 text-yellow-800',
                                        'urgent' => 'bg-purple-100 text-purple-800',
                                        'express' => 'bg-blue-100 text-blue-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                    ?>">
                                        <?php echo strtoupper($token['priority_type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($token['customer_name']): ?>
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($token['customer_name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($token['customer_mobile'] ?? ''); ?></div>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-400 italic">Walk-in</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full <?php 
                                    echo match($token['status']) {
                                        'waiting' => 'bg-yellow-100 text-yellow-800',
                                        'called' => 'bg-purple-100 text-purple-800',
                                        'serving' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800',
                                        'no_show' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                    ?>">
                                        <?php echo ucfirst($token['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($token['counter_number'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDateTime($token['issued_at']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($token['actual_wait_time']): ?>
                                        <?php echo $token['actual_wait_time']; ?> min
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex gap-2">
                                        <button onclick="viewToken(<?php echo $token['id']; ?>)" class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition">
                                            View
                                        </button>
                                        <?php if (in_array($token['status'], ['waiting', 'called'])): ?>
                                            <button onclick="cancelToken(<?php echo $token['id']; ?>)" class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 transition">
                                                Cancel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function viewToken(tokenId) {
    window.open(`${BASE_URL}/customer/token-status.php?token_id=${tokenId}`, '_blank');
}

function cancelToken(tokenId) {
    if (!confirm('Are you sure you want to cancel this token?')) return;
    
    const reason = prompt('Cancellation reason (optional):');
    
    fetch(`${BASE_URL}/api/counter-operations.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'cancel',
            token_id: tokenId,
            reason: reason
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Token cancelled successfully');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Network error: ' + err));
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
