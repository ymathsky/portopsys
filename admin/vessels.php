<?php
/**
 * Manage Vessels
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/PortManager.php';
require_once __DIR__ . '/../includes/ServiceManager.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

requireLogin();

// Check if user is admin
if (!hasPermission('admin')) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$portManager = new PortManager();
$serviceManager = new ServiceManager();

// Handle Form Submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        try {
            if ($action === 'add') {
                $portManager->addVessel(
                    $_POST['name'],
                    $_POST['type'],
                    $_POST['registration_number'],
                    $_POST['owner_name'],
                    $_POST['contact_number'],
                    (int)$_POST['max_capacity']
                );
                $message = 'Vessel added successfully';
                AuditLogger::log('create', 'vessel', "Added vessel '{$_POST['name']}' (type: {$_POST['type']})");
            } elseif ($action === 'delete') {
                $portManager->deleteVessel($_POST['id']);
                $message = 'Vessel deleted successfully';
                AuditLogger::log('delete', 'vessel', "Deleted vessel ID {$_POST['id']}", (int)$_POST['id']);
            } elseif ($action === 'edit') {
                 $portManager->updateVessel(
                    $_POST['id'],
                    $_POST['name'],
                    $_POST['type'],
                    $_POST['registration_number'],
                    $_POST['owner_name'],
                    $_POST['contact_number'],
                    (int)$_POST['max_capacity']
                );
                $message = 'Vessel updated successfully';
                AuditLogger::log('update', 'vessel', "Updated vessel '{$_POST['name']}' (ID {$_POST['id']})", (int)$_POST['id']);
            } elseif ($action === 'manage_services') {
                $vesselId = $_POST['vessel_id'];
                $serviceIds = isset($_POST['service_ids']) ? $_POST['service_ids'] : [];
                $prices = isset($_POST['service_prices']) ? $_POST['service_prices'] : [];
                $portManager->setVesselServices($vesselId, $serviceIds, $prices);
                $message = 'Vessel services updated successfully';
                AuditLogger::log('update', 'vessel', "Updated services for vessel ID {$vesselId}", (int)$vesselId);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$vessels = $portManager->getAllVessels();
$allServices = $serviceManager->getActiveCategories();

// Calculate stats
$vesselStats = [
    'total' => count($vessels),
    'cargo' => count(array_filter($vessels, fn($v) => $v['type'] === 'cargo')),
    'roro' => count(array_filter($vessels, fn($v) => $v['type'] === 'roro')),
    'boat' => count(array_filter($vessels, fn($v) => $v['type'] === 'boat')),
    'other' => count(array_filter($vessels, fn($v) => $v['type'] === 'other'))
];

$pageTitle = 'Manage Vessels';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <span class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white">🚢</span>
                Manage Vessels
            </h1>
            <p class="text-gray-600 mt-1">Register and manage port vessels</p>
        </div>
        <button onclick="openModal('addVesselModal')" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition font-medium shadow-md">
            <span class="inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Add New Vessel
            </span>
        </button>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-start">
            <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-start">
            <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Vessels</p>
                    <p class="text-3xl font-bold mt-1"><?php echo $vesselStats['total']; ?></p>
                </div>
                <div class="text-4xl opacity-80">🚢</div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-indigo-100 text-sm font-medium">Cargo Ships</p>
                    <p class="text-3xl font-bold mt-1"><?php echo $vesselStats['cargo']; ?></p>
                </div>
                <div class="text-4xl opacity-80">📦</div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">RORO Vessels</p>
                    <p class="text-3xl font-bold mt-1"><?php echo $vesselStats['roro']; ?></p>
                </div>
                <div class="text-4xl opacity-80">🚗</div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-cyan-100 text-sm font-medium">Boats</p>
                    <p class="text-3xl font-bold mt-1"><?php echo $vesselStats['boat']; ?></p>
                </div>
                <div class="text-4xl opacity-80">⛵</div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Other</p>
                    <p class="text-3xl font-bold mt-1"><?php echo $vesselStats['other']; ?></p>
                </div>
                <div class="text-4xl opacity-80">⚓</div>
            </div>
        </div>
    </div>

    <!-- Vessels Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900">Registered Vessels</h2>
            <p class="text-sm text-gray-600 mt-1">Manage all vessel registrations and details</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vessel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($vessels)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <p class="text-lg font-medium mb-2">No vessels registered</p>
                                    <p class="text-sm">Add your first vessel to get started</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vessels as $vessel): 
                            $vesselIcons = [
                                'cargo' => '🚢',
                                'roro' => '🚗',
                                'boat' => '⛵',
                                'other' => '⚓'
                            ];
                            $vesselColors = [
                                'cargo' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'roro' => 'bg-green-100 text-green-800 border-green-200',
                                'boat' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
                                'other' => 'bg-purple-100 text-purple-800 border-purple-200'
                            ];
                            $icon = $vesselIcons[$vessel['type']] ?? '🚢';
                            $colorClass = $vesselColors[$vessel['type']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                        ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-3"><?php echo $icon; ?></span>
                                        <div>
                                            <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($vessel['name']); ?></div>
                                            <?php if ($vessel['registration_number']): ?>
                                                <div class="text-xs text-gray-500">Reg: <?php echo htmlspecialchars($vessel['registration_number']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border <?php echo $colorClass; ?>">
                                        <?php echo ucfirst($vessel['type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $currentCount = $portManager->getVesselPassengerCount($vessel['id']);
                                    $maxCapacity = $vessel['max_capacity'];
                                    $percentFull = $maxCapacity > 0 ? ($currentCount / $maxCapacity) * 100 : 0;
                                    $capacityColor = $percentFull >= 90 ? 'text-red-600' : ($percentFull >= 70 ? 'text-yellow-600' : 'text-green-600');
                                    ?>
                                    <div class="text-sm">
                                        <?php if ($maxCapacity > 0): ?>
                                            <div class="font-medium <?php echo $capacityColor; ?>">
                                                <?php echo $currentCount; ?> / <?php echo $maxCapacity; ?>
                                            </div>
                                            <div class="w-24 bg-gray-200 rounded-full h-1.5 mt-1">
                                                <div class="<?php echo $percentFull >= 90 ? 'bg-red-600' : ($percentFull >= 70 ? 'bg-yellow-500' : 'bg-green-600'); ?> h-1.5 rounded-full" style="width: <?php echo min($percentFull, 100); ?>%"></div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-500">Unlimited</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($vessel['registration_number']) ?: '-'; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($vessel['owner_name']) ?: '-'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($vessel['contact_number']): ?>
                                        <div class="flex items-center text-sm text-gray-900">
                                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                            <?php echo htmlspecialchars($vessel['contact_number']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick="manageServices(<?php echo $vessel['id']; ?>, '<?php echo htmlspecialchars($vessel['name'], ENT_QUOTES); ?>')" class="text-green-600 hover:text-green-900 transition-colors" title="Manage Services">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                            </svg>
                                        </button>
                                        <button onclick="editVessel(<?php echo htmlspecialchars(json_encode($vessel)); ?>)" class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this vessel?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $vessel['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Delete">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
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

<!-- Add/Edit Modal -->
<div id="addVesselModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between">
            <h2 id="modalTitle" class="text-xl font-bold text-white">Add New Vessel</h2>
            <button onclick="closeModal('addVesselModal')" class="text-white hover:text-gray-200 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form method="POST">
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="vesselId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Vessel Name *</label>
                    <input type="text" name="name" id="vesselName" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                    <select name="type" id="vesselType"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="cargo">Cargo Ship</option>
                        <option value="roro">RORO</option>
                        <option value="boat">Boat</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Registration Number</label>
                    <input type="text" name="registration_number" id="regNum"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Owner Name</label>
                        <input type="text" name="owner_name" id="ownerName"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                        <input type="text" name="contact_number" id="contactNum"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Capacity (Passengers) *</label>
                    <input type="number" name="max_capacity" id="maxCapacity" required min="0" value="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="Enter maximum passenger capacity">
                    <p class="text-xs text-gray-500 mt-1">Set to 0 for unlimited capacity. This limits the number of tokens that can be issued.</p>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeModal('addVesselModal')" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                    Cancel
                </button>
                <button type="submit" id="submitBtn" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition font-medium shadow-md">
                    Save Vessel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Services Management Modal -->
<div id="servicesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4 flex items-center justify-between">
            <h2 id="serviceModalTitle" class="text-xl font-bold text-white">Manage Services</h2>
            <button onclick="closeModal('servicesModal')" class="text-white hover:text-gray-200 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form method="POST">
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                <input type="hidden" name="action" value="manage_services">
                <input type="hidden" name="vessel_id" id="serviceVesselId">
                
                <p class="text-sm text-gray-600 mb-4">Select the services available for this vessel:</p>
                
                <div class="grid grid-cols-1 gap-3">
                    <?php foreach ($allServices as $service): ?>
                        <div class="service-row border-2 border-gray-200 rounded-xl hover:border-green-400 hover:bg-green-50 transition p-4">
                            <div class="flex items-start gap-3">
                                <input type="checkbox"
                                       name="service_ids[]"
                                       value="<?php echo $service['id']; ?>"
                                       id="service_<?php echo $service['id']; ?>"
                                       class="service-checkbox mt-1 w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                       onchange="togglePriceInput(this, <?php echo $service['id']; ?>)">
                                <div class="flex-1 min-w-0">
                                    <label for="service_<?php echo $service['id']; ?>" class="font-semibold text-gray-900 cursor-pointer">
                                        <?php echo htmlspecialchars($service['name']); ?>
                                    </label>
                                    <div class="text-xs text-gray-500 mt-0.5">Code: <?php echo $service['code']; ?> &bull; Avg. Time: <?php echo $service['avg_service_time']; ?> min</div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <span class="text-sm font-medium text-gray-500">₱</span>
                                    <input type="number"
                                           name="service_prices[<?php echo $service['id']; ?>]"
                                           id="price_<?php echo $service['id']; ?>"
                                           min="0" step="0.01" value="0.00"
                                           placeholder="0.00"
                                           class="price-input w-28 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent disabled:opacity-40 disabled:bg-gray-100"
                                           disabled>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeModal('servicesModal')" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 transition font-medium shadow-md">
                    Save Services
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    const modal = document.getElementById(id);
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    // Reset form
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').innerText = 'Add New Vessel';
    document.getElementById('submitBtn').innerText = 'Save Vessel';
    document.getElementById('vesselId').value = '';
    document.getElementById('vesselName').value = '';
    document.getElementById('vesselType').value = 'cargo';
    document.getElementById('regNum').value = '';
    document.getElementById('ownerName').value = '';
    document.getElementById('contactNum').value = '';
    document.getElementById('maxCapacity').value = '0';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    document.body.style.overflow = 'auto';
}

function editVessel(vessel) {
    openModal('addVesselModal');
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').innerText = 'Edit Vessel';
    document.getElementById('submitBtn').innerText = 'Update Vessel';
    
    document.getElementById('vesselId').value = vessel.id;
    document.getElementById('vesselName').value = vessel.name;
    document.getElementById('vesselType').value = vessel.type;
    document.getElementById('regNum').value = vessel.registration_number;
    document.getElementById('ownerName').value = vessel.owner_name;
    document.getElementById('contactNum').value = vessel.contact_number;
    document.getElementById('maxCapacity').value = vessel.max_capacity || 0;
}

async function manageServices(vesselId, vesselName) {
    document.getElementById('serviceVesselId').value = vesselId;
    document.getElementById('serviceModalTitle').innerText = 'Manage Services \u2014 ' + vesselName;
    
    // Reset all checkboxes and prices first
    document.querySelectorAll('.service-checkbox').forEach(cb => {
        cb.checked = false;
        togglePriceInput(cb, parseInt(cb.value));
    });

    // Fetch current vessel services with prices
    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/vessel-services.php?vessel_id=' + vesselId + '&with_prices=1');
        const data = await response.json();

        if (data.services) {
            data.services.forEach(svc => {
                const id = typeof svc === 'object' ? svc.id : svc;
                const price = typeof svc === 'object' ? (svc.price || 0) : 0;
                const checkbox = document.getElementById('service_' + id);
                const priceInput = document.getElementById('price_' + id);
                if (checkbox) {
                    checkbox.checked = true;
                    togglePriceInput(checkbox, id);
                }
                if (priceInput) priceInput.value = parseFloat(price).toFixed(2);
            });
        }
    } catch (error) {
        console.error('Error fetching vessel services:', error);
    }
    
    openModal('servicesModal');
}

function togglePriceInput(checkbox, serviceId) {
    const priceInput = document.getElementById('price_' + serviceId);
    if (!priceInput) return;
    if (checkbox.checked) {
        priceInput.disabled = false;
        priceInput.closest('.service-row').classList.add('border-green-400', 'bg-green-50');
        priceInput.closest('.service-row').classList.remove('border-gray-200');
    } else {
        priceInput.disabled = true;
        priceInput.value = '0.00';
        priceInput.closest('.service-row').classList.remove('border-green-400', 'bg-green-50');
        priceInput.closest('.service-row').classList.add('border-gray-200');
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
