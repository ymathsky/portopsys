<?php
/**
 * Counter Management Page - Enhanced UI
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TokenManager.php';
require_once __DIR__ . '/../includes/ServiceManager.php';

requireLogin();

$tokenManager = new TokenManager();
$serviceManager = new ServiceManager();

// Get assigned counter for counter staff
$assignedCounterId = $_SESSION['assigned_counter_id'] ?? null;
$userRole = getUserRole();

// If counter staff, only show their assigned counter
if ($userRole === 'counter_staff' && $assignedCounterId) {
    $selectedCounterId = $assignedCounterId;
} else {
    $selectedCounterId = $_GET['counter_id'] ?? null;
}

$counterStatus = $serviceManager->getCounterStatus();
$serving = $tokenManager->getCurrentlyServing();

$pageTitle = 'Counter Management';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl">
    <?php if ($userRole !== 'counter_staff' || !$assignedCounterId): ?>
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Select Counter</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($counterStatus as $counter): ?>
                    <a href="?counter_id=<?php echo $counter['id']; ?>" 
                       class="group relative bg-white rounded-xl p-6 border-2 transition-all duration-200 hover:shadow-lg <?php echo ($selectedCounterId == $counter['id']) ? 'border-indigo-500 shadow-md' : 'border-gray-200 hover:border-indigo-300'; ?>">
                        <div class="flex flex-col items-center text-center">
                            <div class="text-3xl mb-2">🖥️</div>
                            <h3 class="font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($counter['counter_number']); ?></h3>
                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($counter['counter_name']); ?></p>
                            <span class="mt-3 inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                <?php 
                                echo match($counter['current_status']) {
                                    'available' => 'bg-green-100 text-green-800',
                                    'serving' => 'bg-blue-100 text-blue-800',
                                    'break' => 'bg-yellow-100 text-yellow-800',
                                    'closed' => 'bg-gray-100 text-gray-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                                ?>">
                                <?php echo ucfirst($counter['current_status']); ?>
                            </span>
                        </div>
                        <?php if ($selectedCounterId == $counter['id']): ?>
                            <div class="absolute top-2 right-2">
                                <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($selectedCounterId): ?>
        <div id="counter-operations" data-counter-id="<?php echo $selectedCounterId; ?>">
            <?php
            $currentCounter = array_filter($counterStatus, fn($c) => $c['id'] == $selectedCounterId);
            $currentCounter = reset($currentCounter);
            ?>
            
            <!-- Counter Header -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                            <span class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white text-xl">
                                🖥️
                            </span>
                            <?php echo htmlspecialchars($currentCounter['counter_number'] ?? 'Unknown'); ?>
                        </h2>
                        <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($currentCounter['counter_name'] ?? ''); ?></p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <select id="counterStatusSelect" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="available" <?php echo ($currentCounter['current_status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="serving" <?php echo ($currentCounter['current_status'] === 'serving') ? 'selected' : ''; ?>>Serving</option>
                            <option value="break" <?php echo ($currentCounter['current_status'] === 'break') ? 'selected' : ''; ?>>On Break</option>
                            <option value="closed" <?php echo ($currentCounter['current_status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                        </select>
                        <button id="updateStatusBtn" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                            Update Status
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Current Token Display -->
            <div id="currentTokenDisplay" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6">
                <?php
                $currentToken = array_filter($serving, fn($t) => $t['counter_id'] == $selectedCounterId);
                $currentToken = reset($currentToken);
                
                if ($currentToken):
                ?>
                    <div class="text-center">
                        <span class="inline-block px-4 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold mb-4">
                            Now Serving
                        </span>
                        
                        <div class="my-8">
                            <div class="text-7xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-4">
                                <?php echo htmlspecialchars($currentToken['token_number']); ?>
                            </div>
                            <p class="text-xl text-gray-700 font-medium"><?php echo htmlspecialchars($currentToken['service_category']); ?></p>
                        </div>
                        
                        <div class="flex justify-center gap-8 mb-6 text-sm">
                            <?php if ($currentToken['customer_name']): ?>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span class="font-medium"><?php echo htmlspecialchars($currentToken['customer_name']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php 
                                echo match($currentToken['priority_type']) {
                                    'emergency' => 'bg-red-100 text-red-800',
                                    'hazmat' => 'bg-orange-100 text-orange-800',
                                    'perishable' => 'bg-yellow-100 text-yellow-800',
                                    'urgent' => 'bg-purple-100 text-purple-800',
                                    'express' => 'bg-blue-100 text-blue-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                                ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $currentToken['priority_type'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex justify-center gap-4">
                            <?php if ($currentToken['status'] === 'called'): ?>
                                <button onclick="startServing(<?php echo $currentToken['id']; ?>)" class="px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 transition font-medium shadow-md">
                                    Start Service
                                </button>
                            <?php elseif ($currentToken['status'] === 'serving'): ?>
                                <button onclick="completeToken(<?php echo $currentToken['id']; ?>)" class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition font-medium shadow-md">
                                    Complete Service
                                </button>
                            <?php endif; ?>
                            <button onclick="markNoShow(<?php echo $currentToken['id']; ?>)" class="px-8 py-3 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition font-medium">
                                Mark No Show
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">🎫</div>
                        <p class="text-xl text-gray-600 mb-8">No token currently assigned</p>
                        <button onclick="callNextToken()" class="px-10 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition font-semibold shadow-lg text-lg">
                            📢 Call Next Token
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Queue Overview -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Next in Queue
                </h3>
                <div id="upcomingTokens" class="space-y-3">
                    <div class="text-center text-gray-500 py-4">Loading queue...</div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <div class="text-6xl mb-4">🖥️</div>
            <p class="text-xl text-gray-600">Please select a counter to manage</p>
        </div>
    <?php endif; ?>
</div>

<script>
const counterId = <?php echo $selectedCounterId ?? 'null'; ?>;
const BASE_URL = '<?php echo BASE_URL; ?>';

let refreshInterval;

function startAutoRefresh() {
    refreshInterval = setInterval(() => {
        loadCounterData();
    }, 5000);
}

function loadCounterData() {
    fetch(`${BASE_URL}/api/queue-status.php?type=serving`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateCurrentToken(data.data);
            }
        });
        
    fetch(`${BASE_URL}/api/queue-status.php?type=waiting&limit=5`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateUpcomingTokens(data.data);
            }
        });
}

function updateUpcomingTokens(tokens) {
    const container = document.getElementById('upcomingTokens');
    if (!tokens || tokens.length === 0) {
        container.innerHTML = '<div class="text-center text-gray-500 py-4">No tokens waiting</div>';
        return;
    }
    
    container.innerHTML = tokens.map(token => `
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold">
                    ${token.queue_position}
                </div>
                <div>
                    <p class="font-bold text-gray-900">${token.token_number}</p>
                    <p class="text-sm text-gray-600">${token.service_category}</p>
                </div>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold ${
                token.priority_type === 'emergency' ? 'bg-red-100 text-red-800' :
                token.priority_type === 'hazmat' ? 'bg-orange-100 text-orange-800' :
                token.priority_type === 'urgent' ? 'bg-purple-100 text-purple-800' :
                'bg-gray-100 text-gray-800'
            }">
                ${token.priority_type.replace('_', ' ').toUpperCase()}
            </span>
        </div>
    `).join('');
}

function callNextToken() {
    fetch(`${BASE_URL}/api/counter-operations.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'call_next',
            counter_id: counterId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Network error: ' + err));
}

function startServing(tokenId) {
    fetch(`${BASE_URL}/api/counter-operations.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'start_serving',
            token_id: tokenId,
            counter_id: counterId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function completeToken(tokenId) {
    const notes = prompt('Add any notes (optional):');
    
    fetch(`${BASE_URL}/api/counter-operations.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'complete',
            token_id: tokenId,
            notes: notes
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Service completed!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function markNoShow(tokenId) {
    if (!confirm('Mark this token as no-show?')) return;
    
    fetch(`${BASE_URL}/api/counter-operations.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'no_show',
            token_id: tokenId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

document.getElementById('updateStatusBtn')?.addEventListener('click', function() {
    const status = document.getElementById('counterStatusSelect').value;
    
    fetch(`${BASE_URL}/api/counter-operations.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'update_counter_status',
            counter_id: counterId,
            status: status
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Counter status updated!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
});

if (counterId) {
    startAutoRefresh();
    loadCounterData();
}

window.addEventListener('beforeunload', () => {
    if (refreshInterval) clearInterval(refreshInterval);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
