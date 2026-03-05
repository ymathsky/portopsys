<?php
/**
 * Service Categories Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/ServiceManager.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

requireLogin();

$db = getDB();
$serviceManager = new ServiceManager();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $code = strtoupper(trim($_POST['code']));
        $priority = (int)$_POST['priority_level'];
        $serviceTime = (int)$_POST['avg_service_time'];
        $basePrice = floatval($_POST['base_price'] ?? 0);
        $description = trim($_POST['description']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $db->prepare("
                INSERT INTO service_categories (name, code, priority_level, avg_service_time, base_price, description, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $code, $priority, $serviceTime, $basePrice, $description, $isActive]);
            $message = "Service category added successfully!";
            $messageType = 'success';
            AuditLogger::log('create', 'service', "Added service category '{$name}' (code: {$code})");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Error: Service code already exists!";
            } else {
                $message = "Error adding service: " . $e->getMessage();
            }
            $messageType = 'error';
        }
    }
    
    if ($action === 'edit') {
        $id = (int)$_POST['service_id'];
        $name = trim($_POST['name']);
        $code = strtoupper(trim($_POST['code']));
        $priority = (int)$_POST['priority_level'];
        $serviceTime = (int)$_POST['avg_service_time'];
        $basePrice = floatval($_POST['base_price'] ?? 0);
        $description = trim($_POST['description']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $db->prepare("
                UPDATE service_categories 
                SET name = ?, code = ?, priority_level = ?, avg_service_time = ?, base_price = ?, description = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $code, $priority, $serviceTime, $basePrice, $description, $isActive, $id]);
            $message = "Service category updated successfully!";
            $messageType = 'success';
            AuditLogger::log('update', 'service', "Updated service category '{$name}' (ID {$id})", $id);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Error: Service code already exists!";
            } else {
                $message = "Error updating service: " . $e->getMessage();
            }
            $messageType = 'error';
        }
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['service_id'];
        
        try {
            // Check if service is in use
            $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM tokens WHERE service_category_id = ?");
            $checkStmt->execute([$id]);
            $usage = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usage['count'] > 0) {
                $message = "Cannot delete service: It has {$usage['count']} token(s) associated with it. Consider deactivating instead.";
                $messageType = 'error';
            } else {
                $stmt = $db->prepare("DELETE FROM service_categories WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Service category deleted successfully!";
                $messageType = 'success';
                AuditLogger::log('delete', 'service', "Deleted service category ID {$id}", $id);
            }
        } catch (PDOException $e) {
            $message = "Error deleting service: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'toggle_status') {
        $id = (int)$_POST['service_id'];
        
        try {
            $stmt = $db->prepare("UPDATE service_categories SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Service status updated successfully!";
            $messageType = 'success';
            AuditLogger::log('update', 'service', "Toggled status for service category ID {$id}", $id);
        } catch (PDOException $e) {
            $message = "Error updating status: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Get all services
$services = $db->query("SELECT * FROM service_categories ORDER BY priority_level DESC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get service statistics
$stats = [];
foreach ($services as $service) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tokens WHERE service_category_id = ?");
    $stmt->execute([$service['id']]);
    $stats[$service['id']] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Management - Queue System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Include Header -->
    <?php 
    $pageTitle = 'Service Management';
    include __DIR__ . '/includes/header.php'; 
    ?>

    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Service Management</h1>
            <p class="text-gray-600">Configure and manage service categories</p>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-lg p-3">
                        <i class="fas fa-list text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Services</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($services); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-lg p-3">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Active Services</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count(array_filter($services, fn($s) => $s['is_active'])); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-lg p-3">
                        <i class="fas fa-times-circle text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Inactive Services</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count(array_filter($services, fn($s) => !$s['is_active'])); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-500 rounded-lg p-3">
                        <i class="fas fa-ticket text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Tokens</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo array_sum($stats); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Service Button -->
        <div class="mb-6 flex items-center justify-between">
            <button onclick="openAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <i class="fas fa-plus mr-2"></i>Add New Service
            </button>
            
            <button onclick="toggleHelp()" class="text-gray-600 hover:text-gray-900 px-4 py-2 border border-gray-300 rounded-lg transition-colors">
                <i class="fas fa-question-circle mr-2"></i>Help
            </button>
        </div>

        <!-- Help Section (Hidden by default) -->
        <div id="helpSection" class="hidden mb-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-4">Service Settings Guide</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <h4 class="font-semibold text-blue-800 mb-2"><i class="fas fa-tag mr-2"></i>Service Code</h4>
                    <p class="text-blue-700">Short identifier (max 10 characters) used for token prefixes. Examples: PASS, CARGO, VIP</p>
                </div>
                <div>
                    <h4 class="font-semibold text-blue-800 mb-2"><i class="fas fa-sort-amount-up mr-2"></i>Priority Level</h4>
                    <p class="text-blue-700">0-100 scale. Higher values = higher priority. Red (≥80), Yellow (50-79), Green (<50)</p>
                </div>
                <div>
                    <h4 class="font-semibold text-blue-800 mb-2"><i class="fas fa-clock mr-2"></i>Avg. Service Time</h4>
                    <p class="text-blue-700">Expected minutes per customer. Used for wait time estimates and queue analytics.</p>
                </div>
                <div>
                    <h4 class="font-semibold text-blue-800 mb-2"><i class="fas fa-toggle-on mr-2"></i>Active Status</h4>
                    <p class="text-blue-700">Only active services appear in token generation. Deactivate instead of deleting if in use.</p>
                </div>
            </div>
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                <p class="text-sm text-yellow-800"><i class="fas fa-exclamation-triangle mr-2"></i><strong>Note:</strong> Services with existing tokens cannot be deleted. Deactivate them instead to preserve historical data.</p>
            </div>
        </div>

        <!-- Services Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Time (min)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tokens</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($services as $service): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <?php echo htmlspecialchars($service['code']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium <?php 
                                        if ($service['priority_level'] >= 80) echo 'bg-red-100 text-red-800';
                                        elseif ($service['priority_level'] >= 50) echo 'bg-yellow-100 text-yellow-800';
                                        else echo 'bg-green-100 text-green-800';
                                    ?>">
                                        <?php echo $service['priority_level']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                    <?php echo $service['avg_service_time']; ?> min
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php $bp = floatval($service['base_price'] ?? 0); ?>
                                    <?php if ($bp > 0): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                            &#8369;<?php echo number_format($bp, 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($service['is_active']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times-circle mr-1"></i>Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                    <span class="text-sm font-medium"><?php echo $stats[$service['id']]; ?></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo htmlspecialchars(substr($service['description'] ?? '', 0, 50)) . (strlen($service['description'] ?? '') > 50 ? '...' : ''); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick='editService(<?php echo json_encode($service); ?>)' class="text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Toggle service status?');">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                        <button type="submit" class="<?php echo $service['is_active'] ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'; ?> mr-3" title="<?php echo $service['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo $service['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this service? This cannot be undone.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($services)): ?>
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-4"></i>
                                    <p>No service categories found. Add your first service to get started.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Service Modal -->
    <div id="serviceModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-8 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-2xl font-bold text-gray-900">Add Service Category</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" id="serviceForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="service_id" id="serviceId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Service Name *</label>
                        <input type="text" name="name" id="serviceName" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., Passenger Service">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Service Code *</label>
                        <input type="text" name="code" id="serviceCode" required maxlength="10"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
                               placeholder="e.g., PASS">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Priority Level *</label>
                        <input type="number" name="priority_level" id="priorityLevel" required min="0" max="100" value="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">0-100 (Higher = Higher Priority)</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Avg. Service Time (minutes) *</label>
                        <input type="number" name="avg_service_time" id="avgServiceTime" required min="1" value="10"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Base Price (&#8369;)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-medium">&#8369;</span>
                            <input type="number" name="base_price" id="basePrice" min="0" step="0.01" value="0.00"
                                   class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="0.00">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Default fare for this service. Can be overridden per vessel.</p>
                    </div>
                </div>
                
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="serviceDescription" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Enter service description..."></textarea>
                </div>
                
                <div class="mt-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="isActive" checked
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm font-medium text-gray-700">Active</span>
                    </label>
                </div>
                
                <div class="flex justify-end space-x-4 mt-8">
                    <button type="button" onclick="closeModal()"
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors">
                        <i class="fas fa-save mr-2"></i><span id="submitText">Add Service</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleHelp() {
            const helpSection = document.getElementById('helpSection');
            helpSection.classList.toggle('hidden');
        }
        
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Service Category';
            document.getElementById('formAction').value = 'add';
            document.getElementById('submitText').textContent = 'Add Service';
            document.getElementById('serviceForm').reset();
            document.getElementById('isActive').checked = true;
            document.getElementById('basePrice').value = '0.00';
            document.getElementById('serviceModal').classList.remove('hidden');
        }

        function editService(service) {
            document.getElementById('modalTitle').textContent = 'Edit Service Category';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('submitText').textContent = 'Update Service';
            document.getElementById('serviceId').value = service.id;
            document.getElementById('serviceName').value = service.name;
            document.getElementById('serviceCode').value = service.code;
            document.getElementById('priorityLevel').value = service.priority_level;
            document.getElementById('avgServiceTime').value = service.avg_service_time;
            document.getElementById('basePrice').value = parseFloat(service.base_price || 0).toFixed(2);
            document.getElementById('serviceDescription').value = service.description || '';
            document.getElementById('isActive').checked = service.is_active == 1;
            document.getElementById('serviceModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('serviceModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('serviceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Auto-uppercase code input
        document.getElementById('serviceCode').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
