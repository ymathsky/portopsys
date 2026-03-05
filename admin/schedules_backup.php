<?php
/**
 * Manage Schedules
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/PortManager.php';

requireLogin();

// Check if user is admin
if (!hasPermission('admin')) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$portManager = new PortManager();

// Handle Form Submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        try {
            if ($action === 'add_standard') {
                $data = [
                    'vessel_id' => $_POST['vessel_id'],
                    'schedule_name' => $_POST['schedule_name'],
                    'trip_number_prefix' => $_POST['trip_number_prefix'],
                    'origin' => $_POST['origin'],
                    'destination' => $_POST['destination'],
                    'departure_time' => $_POST['departure_time'],
                    'arrival_time' => $_POST['arrival_time'],
                    'monday' => isset($_POST['monday']) ? 1 : 0,
                    'tuesday' => isset($_POST['tuesday']) ? 1 : 0,
                    'wednesday' => isset($_POST['wednesday']) ? 1 : 0,
                    'thursday' => isset($_POST['thursday']) ? 1 : 0,
                    'friday' => isset($_POST['friday']) ? 1 : 0,
                    'saturday' => isset($_POST['saturday']) ? 1 : 0,
                    'sunday' => isset($_POST['sunday']) ? 1 : 0,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                    'effective_from' => $_POST['effective_from'],
                    'effective_until' => !empty($_POST['effective_until']) ? $_POST['effective_until'] : null,
                    'notes' => $_POST['notes']
                ];
                $portManager->addStandardSchedule($data);
                $message = 'Standard schedule created successfully';
            } elseif ($action === 'edit_standard') {
                $data = [
                    'vessel_id' => $_POST['vessel_id'],
                    'schedule_name' => $_POST['schedule_name'],
                    'trip_number_prefix' => $_POST['trip_number_prefix'],
                    'origin' => $_POST['origin'],
                    'destination' => $_POST['destination'],
                    'departure_time' => $_POST['departure_time'],
                    'arrival_time' => $_POST['arrival_time'],
                    'monday' => isset($_POST['monday']) ? 1 : 0,
                    'tuesday' => isset($_POST['tuesday']) ? 1 : 0,
                    'wednesday' => isset($_POST['wednesday']) ? 1 : 0,
                    'thursday' => isset($_POST['thursday']) ? 1 : 0,
                    'friday' => isset($_POST['friday']) ? 1 : 0,
                    'saturday' => isset($_POST['saturday']) ? 1 : 0,
                    'sunday' => isset($_POST['sunday']) ? 1 : 0,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                    'effective_from' => $_POST['effective_from'],
                    'effective_until' => !empty($_POST['effective_until']) ? $_POST['effective_until'] : null,
                    'notes' => $_POST['notes']
                ];
                $portManager->updateStandardSchedule($_POST['id'], $data);
                $message = 'Standard schedule updated successfully';
            } elseif ($action === 'delete_standard') {
                $portManager->deleteStandardSchedule($_POST['id']);
                $message = 'Standard schedule deleted successfully';
            } elseif ($action === 'add_exception') {
                $data = [
                    'standard_schedule_id' => !empty($_POST['standard_schedule_id']) ? $_POST['standard_schedule_id'] : null,
                    'vessel_id' => $_POST['vessel_id'],
                    'exception_type' => $_POST['exception_type'],
                    'exception_date' => $_POST['exception_date'],
                    'trip_number' => $_POST['trip_number'],
                    'origin' => $_POST['origin'],
                    'destination' => $_POST['destination'],
                    'departure_time' => $_POST['departure_time'],
                    'arrival_time' => $_POST['arrival_time'],
                    'status' => $_POST['status'],
                    'reason' => $_POST['reason'],
                    'notes' => $_POST['notes']
                ];
                $portManager->addScheduleException($data);
                $message = 'Schedule exception created successfully';
            } elseif ($action === 'edit_exception') {
                $data = [
                    'standard_schedule_id' => !empty($_POST['standard_schedule_id']) ? $_POST['standard_schedule_id'] : null,
                    'vessel_id' => $_POST['vessel_id'],
                    'exception_type' => $_POST['exception_type'],
                    'exception_date' => $_POST['exception_date'],
                    'trip_number' => $_POST['trip_number'],
                    'origin' => $_POST['origin'],
                    'destination' => $_POST['destination'],
                    'departure_time' => $_POST['departure_time'],
                    'arrival_time' => $_POST['arrival_time'],
                    'status' => $_POST['status'],
                    'reason' => $_POST['reason'],
                    'notes' => $_POST['notes']
                ];
                $portManager->updateScheduleException($_POST['id'], $data);
                $message = 'Schedule exception updated successfully';
            } elseif ($action === 'delete_exception') {
                $portManager->deleteScheduleException($_POST['id']);
                $message = 'Schedule exception deleted successfully';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$standardSchedules = $portManager->getAllStandardSchedules();
$scheduleExceptions = $portManager->getAllScheduleExceptions();
$vessels = $portManager->getAllVessels();

// Calculate stats
$scheduleStats = [
    'total_standard' => count($standardSchedules),
    'active_standard' => count(array_filter($standardSchedules, fn($s) => $s['is_active'] == 1)),
    'total_exceptions' => count($scheduleExceptions),
    'cancelled' => count(array_filter($scheduleExceptions, fn($s) => $s['exception_type'] === 'cancellation')),
    'time_change' => count(array_filter($scheduleExceptions, fn($s) => $s['exception_type'] === 'time_change')),
    'special_trips' => count(array_filter($scheduleExceptions, fn($s) => $s['exception_type'] === 'special_trip')),
    'delays' => count(array_filter($scheduleExceptions, fn($s) => $s['exception_type'] === 'delay'))
];

$pageTitle = 'Manage Trip Schedules';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <span class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white">📅</span>
                Manage Trip Schedules
            </h1>
            <p class="text-gray-600 mt-1">Manage standard schedules and exceptions</p>
        </div>
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
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white">
            <div class="text-center">
                <p class="text-blue-100 text-xs font-medium mb-1">Standard</p>
                <p class="text-2xl font-bold"><?php echo $scheduleStats['total_standard']; ?></p>
                <p class="text-xl mt-1">📋</p>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white">
            <div class="text-center">
                <p class="text-green-100 text-xs font-medium mb-1">Active</p>
                <p class="text-2xl font-bold"><?php echo $scheduleStats['active_standard']; ?></p>
                <p class="text-xl mt-1">✅</p>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white">
            <div class="text-center">
                <p class="text-purple-100 text-xs font-medium mb-1">Exceptions</p>
                <p class="text-2xl font-bold"><?php echo $scheduleStats['total_exceptions']; ?></p>
                <p class="text-xl mt-1">⚠️</p>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white">
            <div class="text-center">
                <p class="text-red-100 text-xs font-medium mb-1">Cancelled</p>
                <p class="text-2xl font-bold"><?php echo $scheduleStats['cancelled']; ?></p>
                <p class="text-xl mt-1">❌</p>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl p-4 text-white">
            <div class="text-center">
                <p class="text-yellow-100 text-xs font-medium mb-1">Time Change</p>
                <p class="text-2xl font-bold"><?php echo $scheduleStats['time_change']; ?></p>
                <p class="text-xl mt-1">⏰</p>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl p-4 text-white">
            <div class="text-center">
                <p class="text-cyan-100 text-xs font-medium mb-1">Special Trips</p>
                <p class="text-2xl font-bold"><?php echo $scheduleStats['special_trips']; ?></p>
                <p class="text-xl mt-1">🎯</p>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-4 text-white">
            <div class="text-center">
                <p class="text-orange-100 text-xs font-medium mb-1">Delays</p>
                <p class="text-2xl font-bold"><?php echo $scheduleStats['delays']; ?></p>
                <p class="text-xl mt-1">🕒</p>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button onclick="switchTab('standard')" id="standardTab" class="tab-button active border-b-2 border-indigo-500 py-4 px-1 text-sm font-medium text-indigo-600">
                    📋 Standard Schedules
                </button>
                <button onclick="switchTab('exceptions')" id="exceptionsTab" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    ⚠️ Schedule Exceptions
                </button>
            </nav>
        </div>
    </div>

    <!-- Schedules Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900">Trip Schedules</h2>
            <p class="text-sm text-gray-600 mt-1">View and manage all scheduled vessel trips</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trip Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vessel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-lg font-medium mb-2">No trips scheduled</p>
                                    <p class="text-sm">Create your first trip schedule to get started</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $trip): 
                            $statusConfig = [
                                'scheduled' => ['color' => 'bg-indigo-100 text-indigo-800 border-indigo-200', 'icon' => '📋'],
                                'boarding' => ['color' => 'bg-cyan-100 text-cyan-800 border-cyan-200', 'icon' => '🎫'],
                                'departed' => ['color' => 'bg-orange-100 text-orange-800 border-orange-200', 'icon' => '🚀'],
                                'arrived' => ['color' => 'bg-green-100 text-green-800 border-green-200', 'icon' => '✅'],
                                'delayed' => ['color' => 'bg-yellow-100 text-yellow-800 border-yellow-200', 'icon' => '⏰'],
                                'cancelled' => ['color' => 'bg-red-100 text-red-800 border-red-200', 'icon' => '❌']
                            ];
                            $status = $statusConfig[$trip['status']] ?? ['color' => 'bg-gray-100 text-gray-800 border-gray-200', 'icon' => '📋'];
                        ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-3">🗓️</span>
                                        <div>
                                            <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($trip['trip_number']) ?: 'N/A'; ?></div>
                                            <div class="text-xs text-gray-500">Trip ID: #<?php echo $trip['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="text-xl mr-2">🚢</span>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($trip['vessel_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo ucfirst($trip['vessel_type'] ?? 'Unknown'); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center text-sm text-gray-900">
                                            <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            <?php echo htmlspecialchars($trip['origin']); ?>
                                        </div>
                                        <div class="flex items-center text-sm text-gray-900">
                                            <svg class="w-4 h-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            <?php echo htmlspecialchars($trip['destination']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center text-sm">
                                            <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="text-gray-600 text-xs mr-1">Dept:</span>
                                            <span class="font-medium text-gray-900"><?php echo date('M d, H:i', strtotime($trip['departure_time'])); ?></span>
                                        </div>
                                        <div class="flex items-center text-sm">
                                            <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span class="text-gray-600 text-xs mr-1">Arr:</span>
                                            <span class="font-medium text-gray-900"><?php echo date('M d, H:i', strtotime($trip['arrival_time'])); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border <?php echo $status['color']; ?>">
                                        <span class="mr-1"><?php echo $status['icon']; ?></span>
                                        <?php echo ucfirst($trip['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick="editTrip(<?php echo htmlspecialchars(json_encode($trip)); ?>)" class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this trip?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $trip['id']; ?>">
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
<div id="addScheduleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-0 w-full max-w-2xl">
        <div class="relative bg-white rounded-2xl shadow-2xl max-h-[85vh] flex flex-col">
            <!-- Modal Header with Gradient -->
            <div class="flex items-center justify-between p-6 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-t-2xl">
                <h2 id="modalTitle" class="text-2xl font-bold text-white">Schedule New Trip</h2>
                <button type="button" onclick="closeModal('addScheduleModal')" class="text-white hover:text-gray-200 transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Body -->
            <form method="POST" class="flex-1 overflow-y-auto">
                <div class="p-6 space-y-5">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="scheduleId">
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Vessel *</label>
                        <select name="vessel_id" id="vesselId" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required>
                            <option value="">-- Select Vessel --</option>
                            <?php foreach ($vessels as $vessel): ?>
                                <option value="<?php echo $vessel['id']; ?>">
                                    <?php echo htmlspecialchars($vessel['name']); ?> (<?php echo ucfirst($vessel['type']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Trip Number</label>
                        <input type="text" name="trip_number" id="tripNum" placeholder="e.g. VOY-2026-001" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Origin</label>
                            <input type="text" name="origin" id="origin" value="Port of Manila" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Destination</label>
                            <input type="text" name="destination" id="destination" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Departure Time</label>
                            <input type="datetime-local" name="departure_time" id="deptTime" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Est. Arrival Time</label>
                            <input type="datetime-local" name="arrival_time" id="arrTime" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                        <select name="status" id="tripStatus" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                            <option value="scheduled">Scheduled</option>
                            <option value="boarding">Boarding</option>
                            <option value="departed">Departed</option>
                            <option value="arrived">Arrived</option>
                            <option value="delayed">Delayed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                    <button type="button" onclick="closeModal('addScheduleModal')" class="px-5 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200 font-medium">
                        Cancel
                    </button>
                    <button type="submit" id="submitBtn" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 font-medium shadow-md hover:shadow-lg">
                        Save Schedule
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
    
    // Reset form
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').innerText = 'Schedule New Trip';
    document.getElementById('submitBtn').innerText = 'Save Schedule';
    document.getElementById('scheduleId').value = '';
    document.getElementById('vesselId').value = '';
    document.getElementById('tripNum').value = '';
    document.getElementById('origin').value = 'Port of Manila';
    document.getElementById('destination').value = '';
    
    // Set default departure to now + 1 hour
    const now = new Date();
    now.setHours(now.getHours() + 1);
    now.setMinutes(0);
    const dateString = now.toISOString().slice(0, 16);
    document.getElementById('deptTime').value = dateString;
    document.getElementById('arrTime').value = '';
    
    document.getElementById('tripStatus').value = 'scheduled';
}

function closeModal(id) {
    const modal = document.getElementById(id);
    modal.classList.add('hidden');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function editTrip(trip) {
    openModal('addScheduleModal');
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').innerText = 'Edit Schedule';
    document.getElementById('submitBtn').innerText = 'Update Schedule';
    
    document.getElementById('scheduleId').value = trip.id;
    document.getElementById('vesselId').value = trip.vessel_id;
    document.getElementById('tripNum').value = trip.trip_number;
    document.getElementById('origin').value = trip.origin;
    document.getElementById('destination').value = trip.destination;
    
    // Format dates for datetime-local input (YYYY-MM-DDTHH:MM)
    if(trip.departure_time) document.getElementById('deptTime').value = trip.departure_time.replace(' ', 'T').slice(0, 16);
    if(trip.arrival_time) document.getElementById('arrTime').value = trip.arrival_time.replace(' ', 'T').slice(0, 16);
    
    document.getElementById('tripStatus').value = trip.status;
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        closeModal(event.target.id);
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
