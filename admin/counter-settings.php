<?php
/**
 * Counter Settings Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/ServiceManager.php';

requireLogin();

// Check if user is admin
if (!hasPermission('admin')) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

// Get database connection
$db = getDB();

$serviceManager = new ServiceManager();
$message = '';
$error = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $counterNumber = strtoupper(trim($_POST['counter_number']));
                    $counterName = trim($_POST['counter_name']);
                    
                    $stmt = $db->prepare("INSERT INTO service_counters (counter_number, counter_name, is_active) VALUES (?, ?, 1)");
                    $stmt->execute([$counterNumber, $counterName]);
                    
                    $counterId = $db->lastInsertId();
                    
                    // Assign all services by default
                    if (!empty($_POST['services'])) {
                        $serviceStmt = $db->prepare("INSERT INTO counter_services (counter_id, service_category_id) VALUES (?, ?)");
                        foreach ($_POST['services'] as $serviceId) {
                            $serviceStmt->execute([$counterId, $serviceId]);
                        }
                    }
                    
                    $message = "Counter '{$counterNumber}' added successfully!";
                    break;
                    
                case 'edit':
                    $counterId = $_POST['counter_id'];
                    $counterNumber = strtoupper(trim($_POST['counter_number']));
                    $counterName = trim($_POST['counter_name']);
                    $isActive = isset($_POST['is_active']) ? 1 : 0;
                    
                    $stmt = $db->prepare("UPDATE service_counters SET counter_number = ?, counter_name = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$counterNumber, $counterName, $isActive, $counterId]);
                    
                    // Update service assignments
                    $db->prepare("DELETE FROM counter_services WHERE counter_id = ?")->execute([$counterId]);
                    
                    if (!empty($_POST['services'])) {
                        $serviceStmt = $db->prepare("INSERT INTO counter_services (counter_id, service_category_id) VALUES (?, ?)");
                        foreach ($_POST['services'] as $serviceId) {
                            $serviceStmt->execute([$counterId, $serviceId]);
                        }
                    }
                    
                    $message = "Counter updated successfully!";
                    break;
                    
                case 'delete':
                    $counterId = $_POST['counter_id'];
                    
                    // Check if counter has any tokens
                    $check = $db->prepare("SELECT COUNT(*) as count FROM tokens WHERE counter_id = ?");
                    $check->execute([$counterId]);
                    $result = $check->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result['count'] > 0) {
                        $error = "Cannot delete counter with existing token history. Deactivate it instead.";
                    } else {
                        $db->prepare("DELETE FROM service_counters WHERE id = ?")->execute([$counterId]);
                        $message = "Counter deleted successfully!";
                    }
                    break;
                    
                case 'toggle_status':
                    $counterId = $_POST['counter_id'];
                    $stmt = $db->prepare("UPDATE service_counters SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$counterId]);
                    $message = "Counter status updated!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get all counters with service counts
$counters = $db->query("
    SELECT 
        c.*,
        COUNT(DISTINCT cs.service_category_id) as service_count,
        COUNT(DISTINCT t.id) as total_tokens,
        GROUP_CONCAT(DISTINCT s.code SEPARATOR ', ') as service_codes
    FROM service_counters c
    LEFT JOIN counter_services cs ON c.id = cs.counter_id
    LEFT JOIN tokens t ON c.id = t.counter_id
    LEFT JOIN service_categories s ON cs.service_category_id = s.id
    GROUP BY c.id
    ORDER BY c.counter_number ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Get all services for assignment
$services = $db->query("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Counter Settings';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <span class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white">🖥️</span>
                Counter Settings
            </h1>
            <p class="text-gray-600 mt-1">Manage service counters and their configurations</p>
        </div>
        <button onclick="openModal('addCounterModal')" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition font-medium shadow-md">
            <span class="inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Add New Counter
            </span>
        </button>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-start">
            <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-start">
            <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Counters</p>
                    <p class="text-3xl font-bold mt-1"><?php echo count($counters); ?></p>
                </div>
                <div class="text-4xl opacity-80">🖥️</div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Active Counters</p>
                    <p class="text-3xl font-bold mt-1"><?php echo count(array_filter($counters, fn($c) => $c['is_active'])); ?></p>
                </div>
                <div class="text-4xl opacity-80">✅</div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Inactive Counters</p>
                    <p class="text-3xl font-bold mt-1"><?php echo count(array_filter($counters, fn($c) => !$c['is_active'])); ?></p>
                </div>
                <div class="text-4xl opacity-80">⏸️</div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Total Services</p>
                    <p class="text-3xl font-bold mt-1"><?php echo count($services); ?></p>
                </div>
                <div class="text-4xl opacity-80">🎫</div>
            </div>
        </div>
    </div>

    <!-- Counters Grid -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900">Service Counters</h2>
            <p class="text-sm text-gray-600 mt-1">Manage all counter configurations and service assignments</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Counter</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current State</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Services</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Tokens</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($counters)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-lg font-medium mb-2">No counters configured</p>
                                    <p class="text-sm">Add your first counter to get started</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($counters as $counter): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-3">🖥️</span>
                                        <span class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($counter['counter_number']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($counter['counter_name']); ?></div>
                                    <?php if ($counter['staff_name']): ?>
                                        <div class="text-xs text-gray-500">Staff: <?php echo htmlspecialchars($counter['staff_name']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($counter['is_active']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span>
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <span class="w-2 h-2 bg-gray-500 rounded-full mr-1.5"></span>
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'available' => 'bg-blue-100 text-blue-800',
                                        'serving' => 'bg-green-100 text-green-800',
                                        'break' => 'bg-yellow-100 text-yellow-800',
                                        'closed' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusColor = $statusColors[$counter['current_status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                        <?php echo ucfirst($counter['current_status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-indigo-100 text-indigo-800">
                                            <?php echo $counter['service_count']; ?> services
                                        </span>
                                        <?php if ($counter['service_codes']): ?>
                                            <span class="text-xs text-gray-500 truncate max-w-[150px]" title="<?php echo htmlspecialchars($counter['service_codes']); ?>">
                                                <?php echo htmlspecialchars($counter['service_codes']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900"><?php echo number_format($counter['total_tokens']); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick="editCounter(<?php echo htmlspecialchars(json_encode($counter)); ?>)" class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="counter_id" value="<?php echo $counter['id']; ?>">
                                            <button type="submit" class="<?php echo $counter['is_active'] ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900'; ?> transition-colors" title="<?php echo $counter['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <?php if ($counter['is_active']): ?>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    <?php else: ?>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    <?php endif; ?>
                                                </svg>
                                            </button>
                                        </form>
                                        
                                        <?php if ($counter['total_tokens'] == 0): ?>
                                            <button onclick="deleteCounter(<?php echo $counter['id']; ?>, '<?php echo htmlspecialchars($counter['counter_number']); ?>')" class="text-red-600 hover:text-red-900 transition-colors" title="Delete">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-300" title="Cannot delete counter with token history">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                </svg>
                                            </span>
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

<!-- Add Counter Modal -->
<div id="addCounterModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-0 w-full max-w-2xl">
        <div class="relative bg-white rounded-2xl shadow-2xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between p-6 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-t-2xl">
                <h2 id="modalTitle" class="text-2xl font-bold text-white">Add New Counter</h2>
                <button type="button" onclick="closeModal('addCounterModal')" class="text-white hover:text-gray-200 transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" class="flex-1 overflow-y-auto">
                <div class="p-6 space-y-5">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="counter_id" id="counterId">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Counter Number *</label>
                            <input type="text" name="counter_number" id="counterNumber" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="e.g., CGO-W1">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Counter Name *</label>
                            <input type="text" name="counter_name" id="counterName" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="e.g., Cargo Ship - Window 1">
                        </div>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" id="isActive" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm font-medium text-gray-700">Counter is Active</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Assign Services *</label>
                        <div class="grid grid-cols-2 gap-3 max-h-64 overflow-y-auto p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <?php foreach ($services as $service): ?>
                                <label class="flex items-start p-3 bg-white rounded-lg border border-gray-200 hover:border-indigo-300 cursor-pointer transition-all">
                                    <input type="checkbox" name="services[]" value="<?php echo $service['id']; ?>" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 service-checkbox">
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($service['name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo $service['code']; ?> • <?php echo $service['avg_service_time']; ?> min</div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-2">
                            <button type="button" onclick="toggleAllServices(true)" class="text-sm text-indigo-600 hover:text-indigo-800 mr-3">Select All</button>
                            <button type="button" onclick="toggleAllServices(false)" class="text-sm text-gray-600 hover:text-gray-800">Deselect All</button>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                    <button type="button" onclick="closeModal('addCounterModal')" class="px-5 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200 font-medium">
                        Cancel
                    </button>
                    <button type="submit" id="submitBtn" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 font-medium shadow-md hover:shadow-lg">
                        Save Counter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(id) {
    const modal = document.getElementById(id);
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    const modal = document.getElementById(id);
    modal.classList.add('hidden');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Reset form
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Add New Counter';
    document.getElementById('submitBtn').textContent = 'Save Counter';
    document.getElementById('counterId').value = '';
    document.getElementById('counterNumber').value = '';
    document.getElementById('counterName').value = '';
    document.getElementById('isActive').checked = true;
    document.querySelectorAll('.service-checkbox').forEach(cb => cb.checked = false);
}

function editCounter(counter) {
    openModal('addCounterModal');
    
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').textContent = 'Edit Counter';
    document.getElementById('submitBtn').textContent = 'Update Counter';
    document.getElementById('counterId').value = counter.id;
    document.getElementById('counterNumber').value = counter.counter_number;
    document.getElementById('counterName').value = counter.counter_name;
    document.getElementById('isActive').checked = counter.is_active == 1;
    
    // Load assigned services
    fetch('<?php echo BASE_URL; ?>/api/get-counter-services.php?counter_id=' + counter.id)
        .then(response => response.json())
        .then(data => {
            document.querySelectorAll('.service-checkbox').forEach(cb => {
                cb.checked = data.services.includes(parseInt(cb.value));
            });
        });
}

function deleteCounter(id, number) {
    if (confirm(`Are you sure you want to delete counter "${number}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="counter_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function toggleAllServices(select) {
    document.querySelectorAll('.service-checkbox').forEach(cb => cb.checked = select);
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        closeModal(event.target.id);
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
