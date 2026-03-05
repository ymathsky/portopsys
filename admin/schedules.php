<?php
/**
 * Manage Schedules - Standard Schedules & Exceptions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/PortManager.php';
require_once __DIR__ . '/../includes/UserManager.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

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
                    'fare' => !empty($_POST['fare']) ? number_format((float)$_POST['fare'], 2, '.', '') : '0.00',
                    'capacity_per_trip' => !empty($_POST['capacity_per_trip']) ? (int)$_POST['capacity_per_trip'] : null,
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
                AuditLogger::log('create', 'schedule', "Created standard schedule '{$_POST['schedule_name']}'");
            } elseif ($action === 'edit_standard') {
                $data = [
                    'vessel_id' => $_POST['vessel_id'],
                    'schedule_name' => $_POST['schedule_name'],
                    'trip_number_prefix' => $_POST['trip_number_prefix'],
                    'origin' => $_POST['origin'],
                    'destination' => $_POST['destination'],
                    'departure_time' => $_POST['departure_time'],
                    'arrival_time' => $_POST['arrival_time'],
                    'fare' => !empty($_POST['fare']) ? number_format((float)$_POST['fare'], 2, '.', '') : '0.00',
                    'capacity_per_trip' => !empty($_POST['capacity_per_trip']) ? (int)$_POST['capacity_per_trip'] : null,
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
                AuditLogger::log('update', 'schedule', "Updated standard schedule '{$_POST['schedule_name']}' (ID {$_POST['id']})", (int)$_POST['id']);
            } elseif ($action === 'delete_standard') {
                $portManager->deleteStandardSchedule($_POST['id']);
                $message = 'Standard schedule deleted successfully';
                AuditLogger::log('delete', 'schedule', "Deleted standard schedule ID {$_POST['id']}", (int)$_POST['id']);
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
                AuditLogger::log('create', 'schedule', "Added schedule exception on {$_POST['exception_date']} (type: {$_POST['exception_type']})");
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
                AuditLogger::log('update', 'schedule', "Updated schedule exception ID {$_POST['id']}", (int)$_POST['id']);
            } elseif ($action === 'delete_exception') {
                $portManager->deleteScheduleException($_POST['id']);
                $message = 'Schedule exception deleted successfully';
                AuditLogger::log('delete', 'schedule', "Deleted schedule exception ID {$_POST['id']}", (int)$_POST['id']);
            } elseif ($action === 'update_trip_status') {
                $submittedPin = trim($_POST['status_pin'] ?? '');
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                $userMgr = new UserManager();
                if (!$userMgr->verifyPin($_SESSION['user_id'], $submittedPin)) {
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Incorrect PIN. Please try again.']);
                        exit;
                    }
                    $error = 'Incorrect PIN. Trip status was not changed.';
                } else {
                    $portManager->updateTripStatus($_POST['id'], $_POST['trip_status'], $_POST['delay_reason'] ?? '');
                    $message = 'Trip status updated to "' . htmlspecialchars($_POST['trip_status']) . '" successfully.';
                    AuditLogger::log('status_change', 'schedule', "Trip ID {$_POST['id']} status changed to '{$_POST['trip_status']}'", (int)$_POST['id'], null, ['trip_status'=>$_POST['trip_status'],'delay_reason'=>$_POST['delay_reason']??'']);
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => $message]);
                        exit;
                    }
                }
            } elseif ($action === 'update_capacity') {
                $db = getDB();
                $stmt = $db->prepare("UPDATE standard_schedules SET capacity_per_trip = ? WHERE id = ?");
                $cap = !empty($_POST['capacity_per_trip']) ? (int)$_POST['capacity_per_trip'] : null;
                $stmt->execute([$cap, $_POST['id']]);
                $message = 'Capacity updated successfully';
                AuditLogger::log('update', 'schedule', "Updated capacity for schedule ID {$_POST['id']} to " . ($cap ?? 'unlimited'), (int)$_POST['id']);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$standardSchedules  = $portManager->getAllStandardSchedules();
$scheduleExceptions = $portManager->getAllScheduleExceptions();
$vessels            = $portManager->getAllVessels();
$todaySchedules     = $portManager->getTodaySchedules();

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

<style>
@keyframes pinShake {
    0%,100% { transform: translateX(0); }
    15%     { transform: translateX(-9px); }
    30%     { transform: translateX(9px); }
    45%     { transform: translateX(-6px); }
    60%     { transform: translateX(6px); }
    75%     { transform: translateX(-3px); }
    90%     { transform: translateX(3px); }
}
@keyframes slideInRight {
    from { opacity:0; transform:translateX(80px); }
    to   { opacity:1; transform:translateX(0); }
}
.pin-shake { animation: pinShake 0.45s ease; }
</style>

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

    <!-- Today's Live Trip Status -->
    <?php if (!empty($todaySchedules)): ?>
    <?php
    $statusConfig = [
        'on_time'   => ['badge'=>'bg-green-100 text-green-700 border border-green-200',   'card'=>'border-green-300 bg-green-50',  'icon'=>'&#x2705;',  'label'=>'On Time'],
        'boarding'  => ['badge'=>'bg-blue-100 text-blue-700 border border-blue-200',     'card'=>'border-blue-300 bg-blue-50',    'icon'=>'&#x1F6A2;', 'label'=>'Boarding'],
        'delayed'   => ['badge'=>'bg-yellow-100 text-yellow-700 border border-yellow-200','card'=>'border-yellow-300 bg-yellow-50','icon'=>'&#x23F1;',  'label'=>'Delayed'],
        'cancelled' => ['badge'=>'bg-red-100 text-red-700 border border-red-200',        'card'=>'border-red-300 bg-red-50',      'icon'=>'&#x274C;',  'label'=>'Cancelled'],
        'departed'  => ['badge'=>'bg-gray-100 text-gray-500 border border-gray-200',     'card'=>'border-gray-200 bg-gray-50',    'icon'=>'&#x1F6E5;', 'label'=>'Departed'],
    ];
    $actionBtns = [
        'on_time'   => ['icon'=>'&#x2705;', 'label'=>'On Time',  'ring'=>'ring-green-400',  'base'=>'bg-white border border-gray-200 text-gray-700 hover:bg-green-50 hover:border-green-400 hover:text-green-800'],
        'boarding'  => ['icon'=>'&#x1F6A2;','label'=>'Boarding', 'ring'=>'ring-blue-400',   'base'=>'bg-white border border-gray-200 text-gray-700 hover:bg-blue-50 hover:border-blue-400 hover:text-blue-800'],
        'delayed'   => ['icon'=>'&#x23F1;', 'label'=>'Delayed',  'ring'=>'ring-yellow-400', 'base'=>'bg-white border border-gray-200 text-gray-700 hover:bg-yellow-50 hover:border-yellow-400 hover:text-yellow-800'],
        'cancelled' => ['icon'=>'&#x274C;', 'label'=>'Cancel',   'ring'=>'ring-red-400',    'base'=>'bg-white border border-gray-200 text-gray-700 hover:bg-red-50 hover:border-red-400 hover:text-red-800'],
        'departed'  => ['icon'=>'&#x1F6E5;','label'=>'Departed', 'ring'=>'ring-gray-400',   'base'=>'bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 hover:border-gray-400 hover:text-gray-800'],
    ];
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                &#x1F6A2; Today&#039;s Trip Status
                <span class="text-sm font-normal text-gray-500"><?php echo date('l, M j'); ?></span>
            </h2>
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1.5 text-xs text-gray-400">
                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse inline-block"></span>
                    Live &mdash; status changes require PIN
                </span>
                <a href="<?php echo BASE_URL; ?>/customer/schedules.php" target="_blank"
                   class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                    Live board &#x2197;
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4">
        <?php foreach ($todaySchedules as $ts):
            $st  = $ts['trip_status'] ?? 'on_time';
            $cfg = $statusConfig[$st] ?? $statusConfig['on_time'];
            $cap    = $ts['capacity_per_trip'] ?? null;
            $booked = (int)$ts['passengers_booked'];
            $capPct = ($cap && $cap > 0) ? min(100, round($booked / $cap * 100)) : 0;
            $capColor = $capPct >= 90 ? 'bg-red-500' : ($capPct >= 60 ? 'bg-yellow-400' : 'bg-green-500');
        ?>
        <div class="rounded-xl border-2 <?php echo $cfg['card']; ?> p-4 transition-all">
            <div class="flex flex-col md:flex-row md:items-center gap-4">

                <!-- Left: trip info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-xl shrink-0 shadow-sm">
                            <?php echo $cfg['icon']; ?>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 leading-tight text-sm"><?php echo htmlspecialchars($ts['schedule_name']); ?></p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                <?php echo htmlspecialchars($ts['origin']); ?> &rarr; <?php echo htmlspecialchars($ts['destination']); ?>
                                &bull;
                                <span class="font-mono font-semibold text-gray-700"><?php echo date('H:i', strtotime($ts['departure_time'])); ?></span>
                            </p>
                            <?php if (!empty($ts['vessel_name'])): ?>
                            <p class="text-xs text-indigo-600 font-medium mt-0.5">
                                &#x1F6A2; <?php echo htmlspecialchars($ts['vessel_name']); ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($st === 'delayed' && !empty($ts['delay_reason'])): ?>
                            <p class="mt-1 text-xs text-yellow-700 bg-yellow-100 rounded px-2 py-0.5 inline-block">
                                &#x26A0; <?php echo htmlspecialchars($ts['delay_reason']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Capacity bar -->
                    <?php if ($cap): ?>
                    <div class="mt-3">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-500">Passengers</span>
                            <span class="font-semibold <?php echo $booked >= $cap ? 'text-red-600' : 'text-gray-700'; ?>">
                                <?php echo $booked; ?>/<?php echo $cap; ?>
                                <?php if ($booked >= $cap): ?><span class="ml-1 text-red-600 font-bold">FULL</span><?php endif; ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full <?php echo $capColor; ?> transition-all" style="width:<?php echo $capPct; ?>%"></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="mt-2 text-xs text-gray-500"><?php echo $booked; ?> booked (no capacity limit)</p>
                    <?php endif; ?>
                </div>

                <!-- Right: current status + action buttons -->
                <div class="shrink-0 flex flex-col items-start md:items-end gap-3">
                    <span class="px-3 py-1 rounded-full text-sm font-bold <?php echo $cfg['badge']; ?>">
                        <?php echo $cfg['icon']; ?> <?php echo $cfg['label']; ?>
                    </span>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($actionBtns as $val => $btn):
                            $isActive = ($st === $val);
                            $activeClass = $isActive ? 'ring-2 ' . $btn['ring'] . ' ring-offset-1 font-bold' : '';
                        ?>
                        <button type="button"
                            onclick="openStatusModal(<?php echo $ts['id']; ?>, '<?php echo $val; ?>', '<?php echo addslashes($btn['label']); ?>', <?php echo ($val === 'delayed') ? 'true' : 'false'; ?>)"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all <?php echo $btn['base']; ?> <?php echo $activeClass; ?>">
                            <?php echo $btn['icon']; ?> <?php echo $btn['label']; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status Change PIN Modal -->
    <div id="statusPinModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeStatusModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10">
            <div class="text-center mb-5">
                <div class="w-14 h-14 mx-auto bg-indigo-100 rounded-full flex items-center justify-center text-3xl mb-3">&#x1F512;</div>
                <h3 class="text-lg font-bold text-gray-900">Confirm Status Change</h3>
                <p class="text-sm text-gray-500 mt-1">Setting trip to <strong id="pinModalStatusLabel" class="text-indigo-700"></strong></p>
            </div>

            <div id="pinDelayRow" class="hidden mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Delay Reason</label>
                <input id="pinDelayReason" type="text" placeholder="e.g. Mechanical issue, Weather..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Admin PIN</label>
                <input id="pinInput" type="password" maxlength="12" placeholder="Enter PIN"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-center tracking-widest text-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    onkeydown="if(event.key==='Enter') submitStatusChange()">
                <p id="pinError" class="hidden mt-1.5 text-xs text-red-600 font-medium">&#x26A0; Incorrect PIN. Please try again.</p>
            </div>

            <div class="flex gap-3">
                <button onclick="closeStatusModal()" class="flex-1 px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button id="pinConfirmBtn" onclick="submitStatusChange()" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition shadow">
                    &#x2713; Confirm
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden form used to submit status changes -->
    <form id="statusChangeForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="update_trip_status">
        <input type="hidden" name="id" id="scfId">
        <input type="hidden" name="trip_status" id="scfStatus">
        <input type="hidden" name="delay_reason" id="scfReason">
        <input type="hidden" name="status_pin" id="scfPin">
    </form>

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

    <!-- Standard Schedules Tab Content -->
    <div id="standardContent" class="tab-content">
        <div class="mb-4 flex justify-end">
            <button onclick="openStandardModal()" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition font-medium shadow-md">
                <span class="inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    New Standard Schedule
                </span>
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <h2 class="text-lg font-semibold text-gray-900">Standard Schedules</h2>
                <p class="text-sm text-gray-600 mt-1">Recurring schedules that repeat based on day of the week</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedule Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vessel</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Times</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Active</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($standardSchedules)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <p class="text-lg font-medium mb-2">No standard schedules</p>
                                        <p class="text-sm">Create your first recurring schedule</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($standardSchedules as $schedule): 
                                $days = [];
                                if ($schedule['monday']) $days[] = 'Mon';
                                if ($schedule['tuesday']) $days[] = 'Tue';
                                if ($schedule['wednesday']) $days[] = 'Wed';
                                if ($schedule['thursday']) $days[] = 'Thu';
                                if ($schedule['friday']) $days[] = 'Fri';
                                if ($schedule['saturday']) $days[] = 'Sat';
                                if ($schedule['sunday']) $days[] = 'Sun';
                                $daysStr = implode(', ', $days);
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <span class="text-2xl mr-3">📋</span>
                                            <div>
                                                <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($schedule['schedule_name']); ?></div>
                                                <div class="text-xs text-gray-500">Trip: <?php echo htmlspecialchars($schedule['trip_number_prefix']); ?>-XXX</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['vessel_name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo ucfirst($schedule['vessel_type'] ?? 'Unknown'); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="space-y-1">
                                            <div class="flex items-center text-sm text-gray-900">
                                                <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                <?php echo htmlspecialchars($schedule['origin']); ?>
                                            </div>
                                            <div class="flex items-center text-sm text-gray-900">
                                                <svg class="w-4 h-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                                <?php echo htmlspecialchars($schedule['destination']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <div>Dept: <span class="font-medium"><?php echo date('H:i', strtotime($schedule['departure_time'])); ?></span></div>
                                            <div>Arr: <span class="font-medium"><?php echo date('H:i', strtotime($schedule['arrival_time'])); ?></span></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 font-medium"><?php echo $daysStr ?: 'None'; ?></div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo date('M d, Y', strtotime($schedule['effective_from'])); ?> -
                                            <?php echo $schedule['effective_until'] ? date('M d, Y', strtotime($schedule['effective_until'])) : 'No end'; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($schedule['is_active']): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border bg-green-100 text-green-800 border-green-200">
                                                <span class="mr-1">✅</span> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border bg-gray-100 text-gray-800 border-gray-200">
                                                <span class="mr-1">⏸️</span> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button onclick='editStandard(<?php echo htmlspecialchars(json_encode($schedule)); ?>)' class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete this standard schedule?');">
                                                <input type="hidden" name="action" value="delete_standard">
                                                <input type="hidden" name="id" value="<?php echo $schedule['id']; ?>">
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

    <!-- Exceptions Tab Content -->
    <div id="exceptionsContent" class="tab-content hidden">
        <div class="mb-4 flex justify-end">
            <button onclick="openExceptionModal()" class="px-6 py-2 bg-gradient-to-r from-red-600 to-orange-600 text-white rounded-lg hover:from-red-700 hover:to-orange-700 transition font-medium shadow-md">
                <span class="inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    New Exception
                </span>
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <h2 class="text-lg font-semibold text-gray-900">Schedule Exceptions</h2>
                <p class="text-sm text-gray-600 mt-1">One-time overrides and special cases for specific dates</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trip Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($scheduleExceptions)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        <p class="text-lg font-medium mb-2">No schedule exceptions</p>
                                        <p class="text-sm">Add exceptions for holidays, cancellations, or special trips</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($scheduleExceptions as $exception): 
                                $typeConfig = [
                                    'cancellation' => ['color' => 'bg-red-100 text-red-800 border-red-200', 'icon' => '❌', 'label' => 'Cancelled'],
                                    'time_change' => ['color' => 'bg-yellow-100 text-yellow-800 border-yellow-200', 'icon' => '⏰', 'label' => 'Time Change'],
                                    'special_trip' => ['color' => 'bg-cyan-100 text-cyan-800 border-cyan-200', 'icon' => '🎯', 'label' => 'Special Trip'],
                                    'delay' => ['color' => 'bg-orange-100 text-orange-800 border-orange-200', 'icon' => '🕒', 'label' => 'Delay']
                                ];
                                $type = $typeConfig[$exception['exception_type']] ?? ['color' => 'bg-gray-100 text-gray-800 border-gray-200', 'icon' => '⚠️', 'label' => 'Unknown'];
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900"><?php echo date('M d, Y', strtotime($exception['exception_date'])); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo date('l', strtotime($exception['exception_date'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border <?php echo $type['color']; ?>">
                                            <span class="mr-1"><?php echo $type['icon']; ?></span>
                                            <?php echo $type['label']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($exception['vessel_name']); ?></div>
                                        <div class="text-xs text-gray-500">Trip: <?php echo htmlspecialchars($exception['trip_number']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($exception['origin']); ?> → <?php echo htmlspecialchars($exception['destination']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo date('H:i', strtotime($exception['departure_time'])); ?> - <?php echo date('H:i', strtotime($exception['arrival_time'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($exception['reason']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button onclick='editException(<?php echo htmlspecialchars(json_encode($exception)); ?>)' class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete this exception?');">
                                                <input type="hidden" name="action" value="delete_exception">
                                                <input type="hidden" name="id" value="<?php echo $exception['id']; ?>">
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
</div>

<!-- Standard Schedule Modal -->
<div id="standardModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-0 w-full max-w-3xl">
        <div class="relative bg-white rounded-2xl shadow-2xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between p-6 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-t-2xl">
                <h2 id="standardModalTitle" class="text-2xl font-bold text-white">New Standard Schedule</h2>
                <button type="button" onclick="closeModal('standardModal')" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" class="flex-1 overflow-y-auto">
                <div class="p-6 space-y-5">
                    <input type="hidden" name="action" id="standardAction" value="add_standard">
                    <input type="hidden" name="id" id="standardId">
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Schedule Name *</label>
                        <input type="text" name="schedule_name" id="scheduleName" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">e.g., "Manila-Cebu Daily Route"</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Vessel *</label>
                            <select name="vessel_id" id="standardVesselId" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">-- Select Vessel --</option>
                                <?php foreach ($vessels as $vessel): ?>
                                    <option value="<?php echo $vessel['id']; ?>">
                                        <?php echo htmlspecialchars($vessel['name']); ?> (<?php echo ucfirst($vessel['type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Trip Number Prefix *</label>
                            <input type="text" name="trip_number_prefix" id="tripPrefix" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">e.g., "MNL-CEB"</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Origin *</label>
                            <input type="text" name="origin" id="standardOrigin" value="Alabat" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Destination *</label>
                            <input type="text" name="destination" id="standardDestination" value="Atimonan" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Departure Time *</label>
                            <input type="time" name="departure_time" id="standardDepartureTime" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Arrival Time *</label>
                            <input type="time" name="arrival_time" id="standardArrivalTime" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Ticket Fare / Price (₱) *</label>
                        <input type="number" name="fare" id="standardFare" step="0.01" min="0" value="0.00" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="e.g., 150.00">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Vessel Capacity <span class="font-normal text-gray-400">(seats, leave blank for unlimited)</span></label>
                        <input type="number" name="capacity_per_trip" id="standardCapacity" min="1" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="e.g., 250">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Days of Operation *</label>
                        <div class="grid grid-cols-7 gap-2">
                            <label class="flex items-center justify-center p-3 border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="monday" id="chkMonday" class="hidden peer">
                                <div class="text-center peer-checked:text-indigo-600 peer-checked:font-bold">
                                    <div class="text-xs">Mon</div>
                                    <div class="text-lg">📅</div>
                                </div>
                            </label>
                            <label class="flex items-center justify-center p-3 border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="tuesday" id="chkTuesday" class="hidden peer">
                                <div class="text-center peer-checked:text-indigo-600 peer-checked:font-bold">
                                    <div class="text-xs">Tue</div>
                                    <div class="text-lg">📅</div>
                                </div>
                            </label>
                            <label class="flex items-center justify-center p-3 border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="wednesday" id="chkWednesday" class="hidden peer">
                                <div class="text-center peer-checked:text-indigo-600 peer-checked:font-bold">
                                    <div class="text-xs">Wed</div>
                                    <div class="text-lg">📅</div>
                                </div>
                            </label>
                            <label class="flex items-center justify-center p-3 border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="thursday" id="chkThursday" class="hidden peer">
                                <div class="text-center peer-checked:text-indigo-600 peer-checked:font-bold">
                                    <div class="text-xs">Thu</div>
                                    <div class="text-lg">📅</div>
                                </div>
                            </label>
                            <label class="flex items-center justify-center p-3 border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="friday" id="chkFriday" class="hidden peer">
                                <div class="text-center peer-checked:text-indigo-600 peer-checked:font-bold">
                                    <div class="text-xs">Fri</div>
                                    <div class="text-lg">📅</div>
                                </div>
                            </label>
                            <label class="flex items-center justify-center p-3 border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="saturday" id="chkSaturday" class="hidden peer">
                                <div class="text-center peer-checked:text-indigo-600 peer-checked:font-bold">
                                    <div class="text-xs">Sat</div>
                                    <div class="text-lg">📅</div>
                                </div>
                            </label>
                            <label class="flex items-center justify-center p-3 border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="sunday" id="chkSunday" class="hidden peer">
                                <div class="text-center peer-checked:text-indigo-600 peer-checked:font-bold">
                                    <div class="text-xs">Sun</div>
                                    <div class="text-lg">📅</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Effective From *</label>
                            <input type="date" name="effective_from" id="effectiveFrom" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Effective Until</label>
                            <input type="date" name="effective_until" id="effectiveUntil" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Leave empty for no end date</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" id="isActive" checked class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <span class="text-sm font-semibold text-gray-700">Active (schedule is currently operational)</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                        <textarea name="notes" id="standardNotes" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                    </div>
                </div>
                
                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                    <button type="button" onclick="closeModal('standardModal')" class="px-5 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 font-medium shadow-md">
                        Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Exception Modal -->
<div id="exceptionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-0 w-full max-w-3xl">
        <div class="relative bg-white rounded-2xl shadow-2xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between p-6 bg-gradient-to-r from-red-600 to-orange-600 rounded-t-2xl">
                <h2 id="exceptionModalTitle" class="text-2xl font-bold text-white">New Schedule Exception</h2>
                <button type="button" onclick="closeModal('exceptionModal')" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" class="flex-1 overflow-y-auto">
                <div class="p-6 space-y-5">
                    <input type="hidden" name="action" id="exceptionAction" value="add_exception">
                    <input type="hidden" name="id" id="exceptionId">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Exception Date *</label>
                            <input type="date" name="exception_date" id="exceptionDate" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Exception Type *</label>
                            <select name="exception_type" id="exceptionType" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="">-- Select Type --</option>
                                <option value="cancellation">❌ Cancellation</option>
                                <option value="time_change">⏰ Time Change</option>
                                <option value="special_trip">🎯 Special Trip</option>
                                <option value="delay">🕒 Delay</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Related Standard Schedule (Optional)</label>
                        <select name="standard_schedule_id" id="relatedSchedule" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            <option value="">-- None / Special Trip --</option>
                            <?php foreach ($standardSchedules as $schedule): ?>
                                <option value="<?php echo $schedule['id']; ?>">
                                    <?php echo htmlspecialchars($schedule['schedule_name']); ?> (<?php echo htmlspecialchars($schedule['trip_number_prefix']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Vessel *</label>
                            <select name="vessel_id" id="exceptionVesselId" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="">-- Select Vessel --</option>
                                <?php foreach ($vessels as $vessel): ?>
                                    <option value="<?php echo $vessel['id']; ?>">
                                        <?php echo htmlspecialchars($vessel['name']); ?> (<?php echo ucfirst($vessel['type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Trip Number *</label>
                            <input type="text" name="trip_number" id="exceptionTripNumber" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Origin *</label>
                            <input type="text" name="origin" id="exceptionOrigin" value="Alabat" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Destination *</label>
                            <input type="text" name="destination" id="exceptionDestination" value="Atimonan" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Departure Time *</label>
                            <input type="time" name="departure_time" id="exceptionDepartureTime" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Arrival Time *</label>
                            <input type="time" name="arrival_time" id="exceptionArrivalTime" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status *</label>
                        <select name="status" id="exceptionStatus" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            <option value="scheduled">Scheduled</option>
                            <option value="boarding">Boarding</option>
                            <option value="departed">Departed</option>
                            <option value="arrived">Arrived</option>
                            <option value="delayed">Delayed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Reason *</label>
                        <input type="text" name="reason" id="exceptionReason" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="e.g., Holiday, Weather, Emergency Trip">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                        <textarea name="notes" id="exceptionNotes" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"></textarea>
                    </div>
                </div>
                
                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                    <button type="button" onclick="closeModal('exceptionModal')" class="px-5 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-red-600 to-orange-600 text-white rounded-lg hover:from-red-700 hover:to-orange-700 font-medium shadow-md">
                        Save Exception
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Tab Switching
function switchTab(tab) {
    const standardTab = document.getElementById('standardTab');
    const exceptionsTab = document.getElementById('exceptionsTab');
    const standardContent = document.getElementById('standardContent');
    const exceptionsContent = document.getElementById('exceptionsContent');
    
    if (tab === 'standard') {
        standardTab.classList.add('active', 'border-indigo-500', 'text-indigo-600');
        standardTab.classList.remove('border-transparent', 'text-gray-500');
        exceptionsTab.classList.remove('active', 'border-indigo-500', 'text-indigo-600');
        exceptionsTab.classList.add('border-transparent', 'text-gray-500');
        standardContent.classList.remove('hidden');
        exceptionsContent.classList.add('hidden');
    } else {
        exceptionsTab.classList.add('active', 'border-indigo-500', 'text-indigo-600');
        exceptionsTab.classList.remove('border-transparent', 'text-gray-500');
        standardTab.classList.remove('active', 'border-indigo-500', 'text-indigo-600');
        standardTab.classList.add('border-transparent', 'text-gray-500');
        exceptionsContent.classList.remove('hidden');
        standardContent.classList.add('hidden');
    }
}

// Modal Functions
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

// Standard Schedule Functions
function openStandardModal() {
    document.getElementById('standardModalTitle').textContent = 'New Standard Schedule';
    document.getElementById('standardAction').value = 'add_standard';
    document.getElementById('standardId').value = '';
    document.getElementById('scheduleName').value = '';
    document.getElementById('standardVesselId').value = '';
    document.getElementById('tripPrefix').value = '';
    document.getElementById('standardOrigin').value = 'Alabat';
    document.getElementById('standardDestination').value = 'Atimonan';
    document.getElementById('standardDepartureTime').value = '';
    document.getElementById('standardArrivalTime').value = '';
    document.getElementById('chkMonday').checked = false;
    document.getElementById('chkTuesday').checked = false;
    document.getElementById('chkWednesday').checked = false;
    document.getElementById('chkThursday').checked = false;
    document.getElementById('chkFriday').checked = false;
    document.getElementById('chkSaturday').checked = false;
    document.getElementById('chkSunday').checked = false;
    document.getElementById('effectiveFrom').value = '';
    document.getElementById('effectiveUntil').value = '';
    document.getElementById('isActive').checked = true;
    document.getElementById('standardNotes').value = '';
    document.getElementById('standardFare').value = '0.00';
    openModal('standardModal');
}

function editStandard(schedule) {
    document.getElementById('standardModalTitle').textContent = 'Edit Standard Schedule';
    document.getElementById('standardAction').value = 'edit_standard';
    document.getElementById('standardId').value = schedule.id;
    document.getElementById('scheduleName').value = schedule.schedule_name;
    document.getElementById('standardVesselId').value = schedule.vessel_id;
    document.getElementById('tripPrefix').value = schedule.trip_number_prefix;
    document.getElementById('standardOrigin').value = schedule.origin;
    document.getElementById('standardDestination').value = schedule.destination;
    document.getElementById('standardDepartureTime').value = schedule.departure_time;
    document.getElementById('standardArrivalTime').value = schedule.arrival_time;
    document.getElementById('standardFare').value = schedule.fare || '0.00';
    if (document.getElementById('standardCapacity'))
        document.getElementById('standardCapacity').value = schedule.capacity_per_trip || '';
    document.getElementById('chkMonday').checked = schedule.monday == 1;
    document.getElementById('chkTuesday').checked = schedule.tuesday == 1;
    document.getElementById('chkWednesday').checked = schedule.wednesday == 1;
    document.getElementById('chkThursday').checked = schedule.thursday == 1;
    document.getElementById('chkFriday').checked = schedule.friday == 1;
    document.getElementById('chkSaturday').checked = schedule.saturday == 1;
    document.getElementById('chkSunday').checked = schedule.sunday == 1;
    document.getElementById('effectiveFrom').value = schedule.effective_from;
    document.getElementById('effectiveUntil').value = schedule.effective_until || '';
    document.getElementById('isActive').checked = schedule.is_active == 1;
    document.getElementById('standardNotes').value = schedule.notes || '';
    openModal('standardModal');
}

// Status PIN modal
let _statusModalScheduleId = null;
let _statusModalValue      = null;
let _statusModalNeedsDelay = false;

function openStatusModal(scheduleId, newStatus, statusLabel, needsDelay) {
    _statusModalScheduleId = scheduleId;
    _statusModalValue      = newStatus;
    _statusModalNeedsDelay = needsDelay;

    document.getElementById('pinModalStatusLabel').textContent = statusLabel;
    document.getElementById('pinDelayRow').classList.toggle('hidden', !needsDelay);
    document.getElementById('pinDelayReason').value = '';
    document.getElementById('pinInput').value = '';
    document.getElementById('pinError').classList.add('hidden');
    document.getElementById('statusPinModal').classList.remove('hidden');
    setTimeout(() => {
        (needsDelay
            ? document.getElementById('pinDelayReason')
            : document.getElementById('pinInput')
        ).focus();
    }, 80);
}

function closeStatusModal() {
    document.getElementById('statusPinModal').classList.add('hidden');
    _statusModalScheduleId = null;
    _statusModalValue      = null;
}

function submitStatusChange() {
    const pin = document.getElementById('pinInput').value.trim();
    if (!pin) {
        document.getElementById('pinInput').focus();
        return;
    }

    const confirmBtn = document.getElementById('pinConfirmBtn');
    const originalHTML = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<svg class="animate-spin w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Verifying…';

    const body = new FormData();
    body.append('action', 'update_trip_status');
    body.append('id', _statusModalScheduleId);
    body.append('trip_status', _statusModalValue);
    body.append('delay_reason', _statusModalNeedsDelay ? document.getElementById('pinDelayReason').value.trim() : '');
    body.append('status_pin', pin);

    fetch(window.location.pathname + window.location.search, {
        method : 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body   : body
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeStatusModal();
            showPinToast('\u2705 ' + data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            // Shake the modal card
            const card = document.querySelector('#statusPinModal > div');
            card.classList.remove('pin-shake');
            void card.offsetWidth; // reflow to restart animation
            card.classList.add('pin-shake');

            // Red highlight on input
            const input = document.getElementById('pinInput');
            input.value = '';
            input.classList.add('border-red-400', 'bg-red-50');
            setTimeout(() => input.classList.remove('border-red-400', 'bg-red-50'), 1000);

            // Show inline error
            document.getElementById('pinError').classList.remove('hidden');

            // Toast
            showPinToast('\u274C ' + data.message, 'error');

            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalHTML;
            input.focus();
        }
    })
    .catch(err => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalHTML;
        showPinToast('\u274C Network error: ' + err, 'error');
    });
}

function showPinToast(msg, type) {
    const el = document.createElement('div');
    el.className = 'fixed top-4 right-4 z-[9999] px-5 py-3 rounded-xl shadow-2xl font-semibold text-white text-sm flex items-center gap-2 ' +
        (type === 'success' ? 'bg-gradient-to-r from-green-500 to-emerald-600'
                           : 'bg-gradient-to-r from-red-500 to-rose-600');
    el.style.cssText = 'animation:slideInRight .3s ease';
    el.innerHTML = msg;
    document.body.appendChild(el);
    setTimeout(() => { el.style.opacity='0'; el.style.transform='translateX(120%)'; el.style.transition='all .4s ease'; setTimeout(()=>el.remove(),400); }, 3000);
}

// Legacy — kept for safety (no longer used by trip status buttons)
function promptDelay(scheduleId, form) {
    const reason = prompt('Enter delay reason (optional):');
    if (reason === null) return;
    document.getElementById('reason_' + scheduleId).value = reason;
    form.submit();
}

// Exception Functions
function openExceptionModal() {
    document.getElementById('exceptionModalTitle').textContent = 'New Schedule Exception';
    document.getElementById('exceptionAction').value = 'add_exception';
    document.getElementById('exceptionId').value = '';
    document.getElementById('exceptionDate').value = '';
    document.getElementById('exceptionType').value = '';
    document.getElementById('relatedSchedule').value = '';
    document.getElementById('exceptionVesselId').value = '';
    document.getElementById('exceptionTripNumber').value = '';
    document.getElementById('exceptionOrigin').value = 'Alabat';
    document.getElementById('exceptionDestination').value = 'Atimonan';
    document.getElementById('exceptionDepartureTime').value = '';
    document.getElementById('exceptionArrivalTime').value = '';
    document.getElementById('exceptionStatus').value = 'scheduled';
    document.getElementById('exceptionReason').value = '';
    document.getElementById('exceptionNotes').value = '';
    openModal('exceptionModal');
}

function editException(exception) {
    document.getElementById('exceptionModalTitle').textContent = 'Edit Schedule Exception';
    document.getElementById('exceptionAction').value = 'edit_exception';
    document.getElementById('exceptionId').value = exception.id;
    document.getElementById('exceptionDate').value = exception.exception_date;
    document.getElementById('exceptionType').value = exception.exception_type;
    document.getElementById('relatedSchedule').value = exception.standard_schedule_id || '';
    document.getElementById('exceptionVesselId').value = exception.vessel_id;
    document.getElementById('exceptionTripNumber').value = exception.trip_number;
    document.getElementById('exceptionOrigin').value = exception.origin;
    document.getElementById('exceptionDestination').value = exception.destination;
    document.getElementById('exceptionDepartureTime').value = exception.departure_time;
    document.getElementById('exceptionArrivalTime').value = exception.arrival_time;
    document.getElementById('exceptionStatus').value = exception.status;
    document.getElementById('exceptionReason').value = exception.reason;
    document.getElementById('exceptionNotes').value = exception.notes || '';
    openModal('exceptionModal');
}
</script>

<style>
.tab-button.active {
    font-weight: 600;
}

label:has(input[type="checkbox"].peer:checked) {
    border-color: #4F46E5 !important;
    background-color: #EEF2FF !important;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
