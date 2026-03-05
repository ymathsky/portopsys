<?php
/**
 * Customer Token Generation Interface
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/ServiceManager.php';
require_once __DIR__ . '/../includes/PortManager.php';

$serviceManager = new ServiceManager();
$portManager = new PortManager();
$services = $serviceManager->getActiveCategories();
$standardSchedules = $portManager->getAllStandardSchedules();
$vessels = $portManager->getAllVessels();

$pageTitle = 'Get Token';

// Build today's schedule status/capacity map
$todaySchedulesData = $portManager->getTodaySchedules();
$todayScheduleMap = [];
foreach ($todaySchedulesData as $ts) {
    $todayScheduleMap[$ts['id']] = $ts;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .step-card {
            transition: all 0.3s ease;
        }
        .step-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .priority-badge {
            transition: all 0.2s;
        }
        .priority-badge:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <header class="text-center mb-8">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-3 drop-shadow-lg"><?php echo APP_NAME; ?></h1>
            <p class="text-xl text-white/90">Get Your Queue Token</p>
        </header>

        <!-- ── Redeem Reservation Banner ──────────────────────────────────── -->
        <div class="mb-6">
            <button onclick="toggleRedeemPanel()" class="w-full bg-white/10 hover:bg-white/20 backdrop-blur-sm border-2 border-white/30 hover:border-white/60 rounded-2xl px-6 py-4 text-white font-bold text-lg flex items-center justify-between transition-all">
                <span class="flex items-center gap-3">
                    <span class="text-2xl">📋</span>
                    Have an advance reservation? Redeem it here
                </span>
                <svg id="redeemChevron" class="w-6 h-6 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div id="redeemPanel" class="hidden mt-3 bg-white rounded-2xl shadow-2xl p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Redeem Your Reservation</h3>
                <p class="text-gray-600 text-sm mb-4">Enter your reservation code (e.g. <code class="bg-gray-100 px-2 py-0.5 rounded font-mono">RES-A3X9K2</code>) to join the queue.</p>

                <div id="redeemLookupSection">
                    <div class="flex gap-3">
                        <input type="text" id="redeemCodeInput"
                               placeholder="RES-XXXXXX"
                               maxlength="10"
                               class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-all uppercase font-mono font-bold tracking-widest text-indigo-700 text-lg"
                               oninput="this.value=this.value.toUpperCase()">
                        <button onclick="lookupReservation()" id="redeemLookupBtn"
                                class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition-colors shadow-lg">
                            Look Up
                        </button>
                        <button onclick="openRedeemQRScanner()" title="Scan QR Code"
                                class="px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-bold transition-colors shadow-lg flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                            <span class="hidden sm:inline">Scan QR</span>
                        </button>
                    </div>
                    <div id="redeemError" class="hidden mt-3 p-3 bg-red-50 border border-red-300 text-red-700 rounded-xl text-sm"></div>
                </div>

                <!-- Details preview shown after a successful lookup -->
                <div id="redeemPreview" class="hidden mt-4">
                    <div class="bg-indigo-50 border-2 border-indigo-200 rounded-xl p-4 mb-4" id="redeemPreviewDetails"></div>
                    <div class="flex gap-3">
                        <button onclick="cancelRedeemLookup()" class="flex-1 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-medium transition-colors">
                            ← Back
                        </button>
                        <button onclick="confirmRedeem()" id="redeemConfirmBtn"
                                class="flex-1 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-bold transition-all shadow-lg">
                            ✅ Join Queue Now
                        </button>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 text-center">
                    <p class="text-gray-500 text-sm">Don't have a reservation yet?
                        <a href="<?php echo BASE_URL; ?>/customer/reserve.php" class="text-indigo-600 hover:text-indigo-800 font-semibold underline">Book in advance →</a>
                    </p>
                </div>
            </div>
        </div>
        <!-- ── End Redeem Banner ─────────────────────────────────────── -->

        <!-- Progress Steps -->
        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 mb-6">
            <div class="flex justify-between items-center relative">
                <div class="absolute top-5 left-0 right-0 h-1 bg-white/20">
                    <div id="progressBar" class="h-full bg-white transition-all duration-500" style="width: 0%"></div>
                </div>
                
                <div class="step-indicator flex-1 text-center relative z-10">
                    <div id="stepCircle1" class="w-10 h-10 mx-auto bg-white text-indigo-600 rounded-full flex items-center justify-center font-bold mb-2 shadow-lg">1</div>
                    <div class="text-white text-xs md:text-sm font-medium">Trip Schedule</div>
                </div>
                <div class="step-indicator flex-1 text-center relative z-10">
                    <div id="stepCircle2" class="w-10 h-10 mx-auto bg-white/20 text-white rounded-full flex items-center justify-center font-bold mb-2">2</div>
                    <div class="text-white/60 text-xs md:text-sm font-medium">Service</div>
                </div>
                <div class="step-indicator flex-1 text-center relative z-10">
                    <div id="stepCircle3" class="w-10 h-10 mx-auto bg-white/20 text-white rounded-full flex items-center justify-center font-bold mb-2">3</div>
                    <div class="text-white/60 text-xs md:text-sm font-medium">Passenger Type</div>
                </div>
                <div class="step-indicator flex-1 text-center relative z-10">
                    <div id="stepCircle4" class="w-10 h-10 mx-auto bg-white/20 text-white rounded-full flex items-center justify-center font-bold mb-2">4</div>
                    <div class="text-white/60 text-xs md:text-sm font-medium">Details & Price</div>
                </div>
            </div>
        </div>
        
        <!-- Step 1: Trip Schedule & Vessel Selection -->
        <div id="step1" class="step-content">
            <div class="bg-white rounded-3xl shadow-2xl p-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-2 text-center">Select Trip Schedule</h2>
                <p class="text-gray-600 text-center mb-2">Choose your departure schedule and vessel</p>
                <p class="text-center mb-6">
                    <span class="inline-flex items-center gap-1.5 text-xs text-green-600 bg-green-50 border border-green-200 rounded-full px-3 py-1">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        Live &mdash; <span id="schedule-sync-time">syncing&hellip;</span>
                    </span>
                </p>
                
                <div id="scheduleOptionsMain" class="space-y-3">
                    <?php 
                    // Get today's day of week
                    $today = strtolower(date('l'));
                    $todayDate = date('Y-m-d');
                    
                    $hasSchedules = false;
                    foreach ($standardSchedules as $schedule): 
                        // Check if schedule is active today
                        $isActiveToday = $schedule[$today] == 1 && $schedule['is_active'] == 1;
                        if (!$isActiveToday) continue;
                        
                        // Check if within effective dates
                        if ($schedule['effective_from'] > $todayDate) continue;
                        if ($schedule['effective_until'] && $schedule['effective_until'] < $todayDate) continue;
                        
                        $hasSchedules = true;
                        
                        // Live status from today's schedule map
                        $liveData    = $todayScheduleMap[$schedule['id']] ?? null;
                        $tripStatus  = $liveData['trip_status'] ?? 'on_time';

                        // Don't show departed trips at all
                        if ($tripStatus === 'departed') continue;

                        $isFull      = $portManager->isScheduleFullyBooked($schedule['id']);
                        $deptTs      = strtotime(date('Y-m-d') . ' ' . $schedule['departure_time']);
                        $isDisabled  = ($tripStatus === 'cancelled' || $tripStatus === 'departed' || $isFull);
                        
                        // Status badge config
                        $statusBadges = [
                            'on_time'   => ['color' => 'bg-green-100 text-green-700',   'label' => '🟢 On Time'],
                            'boarding'  => ['color' => 'bg-cyan-100 text-cyan-700 animate-pulse', 'label' => '🚢 Boarding Now'],
                            'delayed'   => ['color' => 'bg-yellow-100 text-yellow-700', 'label' => '⏱ Delayed'],
                            'cancelled' => ['color' => 'bg-red-100 text-red-700',       'label' => '❌ Cancelled'],
                            'departed'  => ['color' => 'bg-gray-100 text-gray-500',     'label' => '✅ Departed'],
                        ];
                        $badge = $statusBadges[$tripStatus] ?? $statusBadges['on_time'];
                        
                        // Capacity info
                        $cap       = $liveData['capacity_per_trip'] ?? null;
                        $booked    = (int)($liveData['passengers_booked'] ?? 0);
                        $capPct    = ($cap && $cap > 0) ? min(100, round($booked / $cap * 100)) : 0;
                        $remaining = ($cap && $cap > 0) ? max(0, (int)$cap - $booked) : null;
                        $capColor  = $capPct >= 90 ? 'bg-red-500' : ($capPct >= 65 ? 'bg-yellow-400' : 'bg-green-500');
                        $remainColor = $remaining !== null && $remaining <= 10 ? 'text-red-600' : ($remaining !== null && $remaining <= 30 ? 'text-orange-600' : 'text-green-700');

                        $btnClass = $isDisabled
                            ? 'w-full bg-gray-100 border-2 border-gray-300 rounded-xl p-6 text-left opacity-60 cursor-not-allowed'
                            : 'schedule-option w-full bg-gradient-to-r from-cyan-50 to-blue-50 hover:from-cyan-100 hover:to-blue-100 border-2 border-cyan-200 hover:border-cyan-400 rounded-xl p-6 text-left transition-all group';
                        $onClickAttr = $isDisabled ? '' : "onclick=\"selectSchedule({$schedule['id']}, '" . htmlspecialchars($schedule['schedule_name'], ENT_QUOTES) . "', {$schedule['vessel_id']}, '" . htmlspecialchars($schedule['vessel_name'], ENT_QUOTES) . "', {$deptTs})\"";
                    ?>
                        <button id="sched-btn-<?php echo $schedule['id']; ?>"
                                <?php echo $onClickAttr; ?>
                                <?php echo $isDisabled ? 'disabled' : ''; ?>
                                data-schedule-id="<?php echo $schedule['id']; ?>"
                                data-dept-ts="<?php echo $deptTs; ?>"
                                data-vessel-id="<?php echo $schedule['vessel_id']; ?>"
                                data-vessel-name="<?php echo htmlspecialchars($schedule['vessel_name'], ENT_QUOTES); ?>"
                                data-schedule-name="<?php echo htmlspecialchars($schedule['schedule_name'], ENT_QUOTES); ?>"
                                class="<?php echo $btnClass; ?> sched-card">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                                        <span class="text-3xl">&#x26F5;</span>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h3 class="text-xl font-bold text-gray-800 group-hover:text-cyan-600"><?php echo htmlspecialchars($schedule['schedule_name']); ?></h3>
                                                <span id="sched-status-<?php echo $schedule['id']; ?>" class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $badge['color']; ?>"><?php echo $badge['label']; ?></span>
                                                <span id="sched-full-<?php echo $schedule['id']; ?>" class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-600 text-white <?php echo $isFull ? '' : 'hidden'; ?>">FULL</span>
                                            </div>
                                            <p class="text-sm text-gray-600">Vessel: <?php echo htmlspecialchars($schedule['vessel_name']); ?></p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-sm mb-2">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-gray-700"><?php echo htmlspecialchars($schedule['origin']); ?></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-gray-700"><?php echo htmlspecialchars($schedule['destination']); ?></span>
                                        </div>
                                    </div>
                                    <div id="sched-cap-wrap-<?php echo $schedule['id']; ?>" class="mt-2 <?php echo $cap ? '' : 'hidden'; ?>">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs text-gray-500">Capacity</span>
                                            <span id="sched-cap-text-<?php echo $schedule['id']; ?>" class="text-xs text-gray-500"><?php echo $booked; ?>/<?php echo $cap ?? '?'; ?></span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mb-1.5">
                                            <div id="sched-cap-bar-<?php echo $schedule['id']; ?>" class="<?php echo $capColor; ?> h-2 rounded-full transition-all" style="width:<?php echo $capPct; ?>%"></div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span id="sched-cap-remaining-<?php echo $schedule['id']; ?>" class="text-xs font-bold <?php echo $remainColor; ?>">
                                                <?php if ($remaining === 0): ?>
                                                    🔴 No seats left
                                                <?php elseif ($remaining !== null && $remaining <= 10): ?>
                                                    🔴 Only <?php echo $remaining; ?> seat<?php echo $remaining == 1 ? '' : 's'; ?> left!
                                                <?php elseif ($remaining !== null && $remaining <= 30): ?>
                                                    🟠 <?php echo $remaining; ?> seats left
                                                <?php elseif ($remaining !== null): ?>
                                                    🟢 <?php echo $remaining; ?> seats available
                                                <?php endif; ?>
                                            </span>
                                            <span class="text-xs text-gray-400"><?php echo $capPct; ?>% filled</span>
                                        </div>
                                    </div>
                                    <p id="sched-delay-<?php echo $schedule['id']; ?>" class="mt-1 text-xs text-yellow-700 bg-yellow-50 px-2 py-1 rounded <?php echo ($liveData && $liveData['delay_reason']) ? '' : 'hidden'; ?>">
                                        <?php echo ($liveData && $liveData['delay_reason']) ? 'Reason: ' . htmlspecialchars($liveData['delay_reason']) : ''; ?>
                                    </p>
                                </div>
                                <div class="text-right ml-4 shrink-0">
                                    <div class="text-sm text-gray-500 mb-1">Departure</div>
                                    <div class="text-2xl font-bold text-cyan-600"><?php echo date('H:i', strtotime($schedule['departure_time'])); ?></div>
                                    <div class="text-xs text-gray-500 mt-1">Arrival: <?php echo date('H:i', strtotime($schedule['arrival_time'])); ?></div>
                                    <div class="text-xs font-semibold text-indigo-600 mt-2 countdown-timer" data-dept="<?php echo $deptTs; ?>"></div>
                                    <?php if (!empty($schedule['fare']) && (float)$schedule['fare'] > 0): ?>
                                    <div class="mt-2 px-2 py-1 bg-blue-50 border border-blue-200 rounded-lg inline-block">
                                        <span class="text-xs font-bold text-blue-700">&#x20B1;<?php echo number_format((float)$schedule['fare'], 2); ?> fare</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </button>
                    <?php endforeach; ?>
                    
                    <?php if (!$hasSchedules): ?>
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">📅</div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">No Schedules Available</h3>
                            <p class="text-gray-600">There are no scheduled trips for today. Please check back tomorrow.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Step 2: Service Selection -->
        <div id="step2" class="step-content hidden">
            <div class="bg-white rounded-3xl shadow-2xl p-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-2 text-center">Select Service Type</h2>
                <p class="text-gray-600 text-center mb-8">Choose the service you need</p>
                
                <div id="serviceOptions" class="space-y-3">
                    <div id="servicesLoading" class="hidden text-center py-6">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-500 border-t-transparent"></div>
                        <p class="text-gray-500 mt-2">Loading services...</p>
                    </div>
                    <div id="noServicesMsg" class="hidden text-center py-8">
                        <p class="text-4xl mb-3">🚢</p>
                        <p class="text-lg font-semibold text-gray-700">No services available for this vessel.</p>
                        <p class="text-sm text-gray-500 mt-1">Please contact port staff for assistance.</p>
                    </div>
                    <?php foreach ($services as $service): 
                        $vesselType = 'general';
                        if (strpos($service['code'], 'CGO') === 0) $vesselType = 'cargo';
                        elseif (strpos($service['code'], 'ROR') === 0) $vesselType = 'roro';
                        elseif (strpos($service['code'], 'BOT') === 0) $vesselType = 'boat';
                    ?>
                        <button onclick="selectService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['name'], ENT_QUOTES); ?>', <?php echo $service['avg_service_time']; ?>)" 
                                data-vessel="<?php echo $vesselType; ?>"
                                data-service-id="<?php echo $service['id']; ?>"
                                data-price="0"
                                class="service-option w-full bg-gradient-to-r from-gray-50 to-gray-100 hover:from-indigo-50 hover:to-purple-50 border-2 border-gray-200 hover:border-indigo-400 rounded-xl p-6 text-left transition-all group hidden">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800 group-hover:text-indigo-600 mb-1"><?php echo htmlspecialchars($service['name']); ?></h3>
                                    <p class="text-gray-600 text-sm">Service Code: <?php echo $service['code']; ?></p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500 mb-1">Avg. Time</div>
                                    <div class="text-2xl font-bold text-indigo-600"><?php echo $service['avg_service_time']; ?> <span class="text-sm">min</span></div>
                                    <div id="svc-price-<?php echo $service['id']; ?>" class="mt-1 text-sm font-bold text-green-600 hidden"></div>
                                </div>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <button onclick="goToStep(1)" class="mt-6 w-full py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-medium transition-colors">
                    ← Back to Schedule
                </button>
            </div>
        </div>
        
        <!-- Step 3: Passenger Type Selection -->
        <div id="step3" class="step-content hidden">
            <div class="bg-white rounded-3xl shadow-2xl p-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-2 text-center">Passenger Type</h2>
                <p class="text-gray-600 text-center mb-8">Select your passenger category</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button onclick="selectPriority('regular')" class="priority-badge bg-gradient-to-br from-gray-50 to-gray-100 border-2 border-gray-300 rounded-2xl p-6 text-left hover:border-gray-500 group">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-3xl">👤</span>
                            <span class="px-3 py-1 bg-gray-200 text-gray-700 rounded-full text-sm font-medium">Regular</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-1">Regular Passenger</h3>
                        <p class="text-gray-600 text-sm">Standard boarding queue</p>
                    </button>
                    
                    <button onclick="selectPriority('senior')" class="priority-badge bg-gradient-to-br from-amber-50 to-amber-100 border-2 border-amber-300 rounded-2xl p-6 text-left hover:border-amber-500 group">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-3xl">👴</span>
                            <span class="px-3 py-1 bg-amber-200 text-amber-700 rounded-full text-sm font-medium">Priority</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-1">Senior Citizen</h3>
                        <p class="text-gray-600 text-sm">60 years old and above - Priority boarding</p>
                    </button>
                    
                    <button onclick="selectPriority('pwd')" class="priority-badge bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-300 rounded-2xl p-6 text-left hover:border-blue-500 group">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-3xl">♿</span>
                            <span class="px-3 py-1 bg-blue-200 text-blue-700 rounded-full text-sm font-medium">Priority</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-1">PWD</h3>
                        <p class="text-gray-600 text-sm">Person with Disability - Priority assistance</p>
                    </button>
                    
                    <button onclick="selectPriority('pregnant')" class="priority-badge bg-gradient-to-br from-pink-50 to-pink-100 border-2 border-pink-300 rounded-2xl p-6 text-left hover:border-pink-500 group">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-3xl">🤰</span>
                            <span class="px-3 py-1 bg-pink-200 text-pink-700 rounded-full text-sm font-medium">Priority</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-1">Pregnant Women</h3>
                        <p class="text-gray-600 text-sm">Expecting mothers - Priority seating</p>
                    </button>

                    <button onclick="selectPriority('student')" class="priority-badge bg-gradient-to-br from-green-50 to-green-100 border-2 border-green-300 rounded-2xl p-6 text-left hover:border-green-500 group">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-3xl">🎓</span>
                            <span class="px-3 py-1 bg-green-200 text-green-700 rounded-full text-sm font-medium">Discounted</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-1">Student</h3>
                        <p class="text-gray-600 text-sm">Valid student ID required - Discounted fare</p>
                    </button>
                </div>
                
                <button onclick="goToStep(2)" class="mt-6 w-full py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-medium transition-colors">
                    ← Back to Service Selection
                </button>
            </div>
        </div>
        
        <!-- Step 4: Contact Details & Ticket Price -->
        <div id="step4" class="step-content hidden">
            <div class="bg-white rounded-3xl shadow-2xl p-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-2 text-center">Contact Information & Ticket Price</h2>
                <p class="text-gray-600 text-center mb-8">Complete your booking details</p>
                
                <!-- Ticket Price Summary -->
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border-2 border-indigo-200 rounded-2xl p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Booking Summary</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Schedule:</span>
                            <span class="font-semibold text-gray-800" id="summary_schedule">-</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Vessel:</span>
                            <span class="font-semibold text-gray-800" id="summary_vessel">-</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Service:</span>
                            <span class="font-semibold text-gray-800" id="summary_service">-</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Passenger Type:</span>
                            <span class="font-semibold text-gray-800" id="summary_priority">-</span>
                        </div>
                        <hr class="border-gray-300">
                        <div class="flex justify-between items-center text-lg">
                            <span class="font-bold text-gray-800">Base Fare:</span>
                            <span class="font-bold text-indigo-600">₱<span id="base_price">150.00</span></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600">Discount:</span>
                            <span class="font-semibold text-green-600">-₱<span id="discount_amount">0.00</span></span>
                        </div>
                        <hr class="border-gray-300">
                        <div class="flex justify-between items-center text-2xl">
                            <span class="font-bold text-gray-800">Total:</span>
                            <span class="font-bold text-indigo-600">₱<span id="total_price">150.00</span></span>
                        </div>
                    </div>
                </div>
                
                <form id="detailsForm" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" id="customer_name" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-all" placeholder="Enter your full name">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mobile Number <span class="text-red-500">*</span></label>
                        <input type="tel" id="customer_mobile" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-all" placeholder="09XX-XXX-XXXX">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address (Optional)</label>
                        <input type="email" id="customer_email" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-all" placeholder="your.email@example.com">
                        <p class="text-sm text-gray-500 mt-1">For email notifications</p>
                    </div>
                    
                    <div class="bg-amber-50 border-2 border-amber-200 rounded-xl p-4">
                        <p class="text-sm text-amber-800"><strong>Note:</strong> Payment will be collected at the boarding counter. Please present your token number.</p>
                    </div>
                    
                    <!-- Booking Type -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Booking Type</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-3 p-4 border-2 border-indigo-300 rounded-xl cursor-pointer hover:bg-indigo-50 transition-all">
                                <input type="radio" name="booking_type" value="walkin" checked class="w-4 h-4 text-indigo-600">
                                <div>
                                    <div class="font-semibold text-gray-800">🚶 Walk-in</div>
                                    <div class="text-xs text-gray-500">Boarding today</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:bg-purple-50 transition-all">
                                <input type="radio" name="booking_type" value="prebooked" class="w-4 h-4 text-purple-600">
                                <div>
                                    <div class="font-semibold text-gray-800">📋 Pre-booked</div>
                                    <div class="text-xs text-gray-500">Advance reservation</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="pt-4">
                        <div id="generateError" class="hidden mb-3"></div>
                        <button type="submit" id="generateBtn" class="w-full py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-bold text-lg shadow-lg transition-all transform hover:scale-[1.02]">
                            🎫 Generate Token & Confirm Booking
                        </button>
                    </div>
                </form>
                
                <button onclick="goToStep(3)" class="mt-4 w-full py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-medium transition-colors">
                    ← Back to Passenger Type
                </button>
            </div>
        </div>
        
        <div class="text-center mt-6">
            <a href="<?php echo BASE_URL; ?>/customer/token-status.php" class="text-white hover:text-white/80 underline mr-6">Check Token Status</a>
            <a href="<?php echo BASE_URL; ?>/customer/reserve.php" class="text-white hover:text-white/80 underline mr-6">📋 Advance Reservation</a>
            <a href="<?php echo BASE_URL; ?>" class="text-white hover:text-white/80 underline">Back to Home</a>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div id="successModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6 rounded-t-3xl">
                <h2 class="text-3xl font-bold text-white text-center">Token Generated Successfully! 🎫</h2>
            </div>
            <div class="p-8" id="tokenDetails">
                <!-- Will be populated by JavaScript -->
            </div>
            <div class="flex flex-col sm:flex-row gap-3 px-8 pb-8">
                <button onclick="printToken()" class="flex-1 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-medium transition-colors">
                    🖨️ Print Token
                </button>
                <button onclick="location.reload()" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition-colors">
                    🎫 Get Another Token
                </button>
                <button onclick="goToStatus()" class="flex-1 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-medium transition-colors">
                    📊 Check Status
                </button>
            </div>
        </div>
    </div>
    
    <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const APP_NAME = '<?php echo APP_NAME; ?>';
    let generatedToken = null;
    let currentStep = 1;
    let formData = {
        vesselType: '',
        scheduleId: '',
        scheduleName: '',
        vesselId: '',
        vesselName: '',
        serviceId: '',
        serviceName: '',
        priority: '',
        departureTsMs: 0,
        bookingType: 'walkin',
        farePaid: 0
    };

    // Countdown timers for schedule cards
    function formatCountdown(ms) {
        if (ms <= 0) return 'Departing';
        const totalSec = Math.floor(ms / 1000);
        const h = Math.floor(totalSec / 3600);
        const m = Math.floor((totalSec % 3600) / 60);
        const s = totalSec % 60;
        if (h > 0) return h + 'h ' + m + 'm';
        if (m > 0) return m + 'm ' + s + 's';
        return s + 's';
    }
    function tickCountdowns() {
        const now = Date.now();
        document.querySelectorAll('.countdown-timer[data-dept]').forEach(el => {
            const deptMs = parseInt(el.dataset.dept) * 1000;
            const diff = deptMs - now;
            el.textContent = diff <= 0 ? '🚢 Departing' : '⏱ ' + formatCountdown(diff) + ' to departure';
        });
    }
    tickCountdowns();
    setInterval(tickCountdowns, 1000);

    // ─── Live schedule status sync ────────────────────────────────
    const STATUS_CONFIG = {
        on_time:   { color: 'bg-green-100 text-green-700',              label: '&#x1F7E2; On Time' },
        boarding:  { color: 'bg-cyan-100 text-cyan-700 animate-pulse',  label: '&#x1F6A2; Boarding Now' },
        delayed:   { color: 'bg-yellow-100 text-yellow-700',            label: '&#x23F1; Delayed' },
        cancelled: { color: 'bg-red-100 text-red-700',                  label: '&#x274C; Cancelled' },
        departed:  { color: 'bg-gray-100 text-gray-500',                label: '&#x2705; Departed' },
    };
    const CAP_COLOR = pct => pct >= 90 ? 'bg-red-500' : pct >= 65 ? 'bg-yellow-400' : 'bg-green-500';
    const DISABLED_CLS = 'w-full bg-gray-100 border-2 border-gray-300 rounded-xl p-6 text-left opacity-60 cursor-not-allowed sched-card';
    const ENABLED_CLS  = 'schedule-option w-full bg-gradient-to-r from-cyan-50 to-blue-50 hover:from-cyan-100 hover:to-blue-100 border-2 border-cyan-200 hover:border-cyan-400 rounded-xl p-6 text-left transition-all group sched-card';

    async function syncScheduleStatus() {
        try {
            const res  = await fetch(`${BASE_URL}/api/schedule-status.php?_=${Date.now()}`);
            const json = await res.json();
            if (!json.success) return;

            json.data.forEach(s => {
                const btn      = document.getElementById(`sched-btn-${s.id}`);
                const badgeEl  = document.getElementById(`sched-status-${s.id}`);
                const fullEl   = document.getElementById(`sched-full-${s.id}`);
                const capBar   = document.getElementById(`sched-cap-bar-${s.id}`);
                const capText  = document.getElementById(`sched-cap-text-${s.id}`);
                const capWrap  = document.getElementById(`sched-cap-wrap-${s.id}`);
                const delayEl  = document.getElementById(`sched-delay-${s.id}`);
                if (!btn) return;

                const cfg = STATUS_CONFIG[s.trip_status] || STATUS_CONFIG.on_time;

                // Status badge
                if (badgeEl) {
                    badgeEl.className = `px-2 py-0.5 rounded-full text-xs font-semibold ${cfg.color}`;
                    badgeEl.innerHTML = cfg.label;
                }

                // FULL badge
                if (fullEl) fullEl.classList.toggle('hidden', !s.is_full);

                // Capacity bar
                if (capBar && s.cap_total) {
                    capBar.className = `${CAP_COLOR(s.cap_pct)} h-2 rounded-full transition-all`;
                    capBar.style.width = s.cap_pct + '%';
                    if (capText) capText.textContent = `${s.cap_booked}/${s.cap_total}`;
                    if (capWrap) capWrap.classList.remove('hidden');

                    // Remaining seats label
                    const remEl = document.getElementById(`sched-cap-remaining-${s.id}`);
                    if (remEl && s.cap_remaining !== undefined && s.cap_remaining !== null) {
                        const r = s.cap_remaining;
                        remEl.className = 'text-xs font-bold ' + (r === 0 ? 'text-red-600' : r <= 10 ? 'text-red-600' : r <= 30 ? 'text-orange-600' : 'text-green-700');
                        remEl.innerHTML = r === 0
                            ? '\uD83D\uDD34 No seats left'
                            : r <= 10
                                ? `\uD83D\uDD34 Only ${r} seat${r === 1 ? '' : 's'} left!`
                            : r <= 30
                                ? `\uD83D\uDFE0 ${r} seats left`
                            : `\uD83D\uDFE2 ${r} seats available`;

                        // Also update % text
                        const pctEl = remEl.nextElementSibling;
                        if (pctEl) pctEl.textContent = s.cap_pct + '% filled';
                    }
                }

                // Delay reason
                if (delayEl) {
                    if (s.delay_reason) {
                        delayEl.textContent = 'Reason: ' + s.delay_reason;
                        delayEl.classList.remove('hidden');
                    } else {
                        delayEl.classList.add('hidden');
                    }
                }

                // Hide departed trips entirely
                if (s.trip_status === 'departed') {
                    btn.style.display = 'none';
                    return;
                }
                btn.style.display = '';

                // Enable / disable button (cancelled / full)
                const wasDisabled = btn.disabled;
                if (s.is_disabled) {
                    btn.disabled = true;
                    btn.onclick  = null;
                    btn.className = DISABLED_CLS;
                } else {
                    btn.disabled = false;
                    btn.className = ENABLED_CLS;
                    if (wasDisabled) {
                        // Restore onclick from data attributes
                        const deptTs   = parseInt(btn.dataset.deptTs);
                        const schedId  = parseInt(btn.dataset.scheduleId);
                        const vId      = parseInt(btn.dataset.vesselId);
                        const vName    = btn.dataset.vesselName;
                        const sName    = btn.dataset.scheduleName;
                        btn.onclick = () => selectSchedule(schedId, sName, vId, vName, deptTs);
                    }
                }
            });

            // Update last-sync indicator
            const syncEl = document.getElementById('schedule-sync-time');
            if (syncEl) syncEl.textContent = 'Updated ' + new Date().toLocaleTimeString('en-PH', {hour:'2-digit', minute:'2-digit', second:'2-digit'});

        } catch(e) { /* silent — network blip */ }
    }

    syncScheduleStatus();
    setInterval(syncScheduleStatus, 10000); // poll every 10 s for near-real-time status updates

    // Redirect to token status page after successful token generation
    function goToStatus() {
        if (generatedToken && generatedToken.token_number) {
            window.location.href = BASE_URL + '/customer/token-status.php?token_number=' + encodeURIComponent(generatedToken.token_number);
        }
    }
    
    // Step Navigation
    function goToStep(step) {
        // Hide all steps
        document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
        
        // Show current step
        document.getElementById('step' + step).classList.remove('hidden');
        currentStep = step;
        
        // Update progress bar
        const progress = ((step - 1) / 3) * 100;
        document.getElementById('progressBar').style.width = progress + '%';
        
        for (let i = 1; i <= 4; i++) {
            const circle = document.getElementById('stepCircle' + i);
            if (i < step) {
                circle.className = 'w-10 h-10 mx-auto bg-green-500 text-white rounded-full flex items-center justify-center font-bold mb-2 shadow-lg';
                circle.innerHTML = '✓';
            } else if (i === step) {
                circle.className = 'w-10 h-10 mx-auto bg-white text-indigo-600 rounded-full flex items-center justify-center font-bold mb-2 shadow-lg';
                circle.innerHTML = i;
            } else {
                circle.className = 'w-10 h-10 mx-auto bg-white/20 text-white rounded-full flex items-center justify-center font-bold mb-2';
                circle.innerHTML = i;
            }
        }
        
        // Scroll to top
        window.scrollTo({top: 0, behavior: 'smooth'});
    }
    
    // Step 1: Schedule Selection
    function selectSchedule(scheduleId, scheduleName, vesselId, vesselName, deptTs) {
        formData.scheduleId = scheduleId;
        formData.scheduleName = scheduleName;
        formData.vesselId = vesselId;
        formData.vesselName = vesselName;
        formData.vesselType = 'boat';
        formData.departureTsMs = (deptTs || 0) * 1000;

        const serviceOptions = document.querySelectorAll('.service-option');
        const noServicesMsg  = document.getElementById('noServicesMsg');
        const servicesLoading = document.getElementById('servicesLoading');

        // Reset: hide everything while loading
        serviceOptions.forEach(opt => opt.classList.add('hidden'));
        if (noServicesMsg)   noServicesMsg.classList.add('hidden');
        if (servicesLoading) servicesLoading.classList.remove('hidden');

        goToStep(2);

        // Fetch services attached to this vessel
        fetch(BASE_URL + '/api/vessel-services.php?vessel_id=' + vesselId)
            .then(r => r.json())
            .then(data => {
                if (servicesLoading) servicesLoading.classList.add('hidden');

                const priceMap = data.prices || {};

                const allowedIds = (data.success && Array.isArray(data.services) && data.services.length > 0)
                    ? data.services.map(id => String(id))
                    : null;

                let visible = 0;
                serviceOptions.forEach(opt => {
                    const svcId = opt.dataset.serviceId;
                    const show = !allowedIds || allowedIds.includes(String(svcId));
                    opt.classList.toggle('hidden', !show);
                    if (show) {
                        visible++;
                        // Update price badge
                        const price = priceMap[svcId] !== undefined ? parseFloat(priceMap[svcId]) : 0;
                        opt.dataset.price = price;
                        const priceEl = document.getElementById('svc-price-' + svcId);
                        if (priceEl) {
                            if (price > 0) {
                                priceEl.textContent = '\u20b1' + price.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                priceEl.classList.remove('hidden');
                            } else {
                                priceEl.textContent = 'Free / No fare set';
                                priceEl.classList.remove('hidden');
                                priceEl.classList.add('text-gray-400');
                                priceEl.classList.remove('text-green-600');
                            }
                        }
                    }
                });

                if (visible === 0 && noServicesMsg) {
                    noServicesMsg.classList.remove('hidden');
                }
            })
            .catch(() => {
                // Fallback: show all services if API call fails
                if (servicesLoading) servicesLoading.classList.add('hidden');
                serviceOptions.forEach(opt => opt.classList.remove('hidden'));
            });
    }
    
    // Step 2: Service Selection
    function selectService(id, name, avgTime) {
        formData.serviceId = id;
        formData.serviceName = name;
        formData.avgTime = avgTime;
        // Capture vessel-specific fare from button's data-price attribute
        const btn = document.querySelector(`.service-option[data-service-id="${id}"]`);
        formData.serviceFare = btn ? parseFloat(btn.dataset.price || 0) : 0;
        goToStep(3);
    }
    
    // Step 3: Priority Selection
    function selectPriority(priority) {
        formData.priority = priority;
        updateSummary();
        goToStep(4);
    }
    
    // Update booking summary and calculate pricing
    function updateSummary() {
        // Update summary display
        document.getElementById('summary_schedule').textContent = formData.scheduleName || '-';
        document.getElementById('summary_vessel').textContent = formData.vesselName || '-';
        document.getElementById('summary_service').textContent = formData.serviceName || '-';
        
        // Display priority type
        const priorityLabels = {
            'regular': 'Regular Passenger',
            'senior': 'Senior Citizen',
            'pwd': 'PWD (Person with Disability)',
            'pregnant': 'Pregnant Women',
            'student': 'Student'
        };
        document.getElementById('summary_priority').textContent = priorityLabels[formData.priority] || '-';
        
        // Calculate pricing — use vessel-service configured fare as base (0 = no fare)
        const baseFare = formData.serviceFare || 0;
        let discount = 0;
        
        // Apply discounts for priority passengers
        if (formData.priority === 'senior' || formData.priority === 'pwd') {
            discount = baseFare * 0.20; // 20% discount for senior citizens and PWD
        } else if (formData.priority === 'pregnant') {
            discount = baseFare * 0.10; // 10% discount for pregnant women
        } else if (formData.priority === 'student') {
            discount = baseFare * 0.20; // 20% discount for students
        }
        
        const totalPrice = baseFare - discount;
        
        // Update price display
        document.getElementById('base_price').textContent = baseFare.toFixed(2);
        document.getElementById('discount_amount').textContent = discount.toFixed(2);
        document.getElementById('total_price').textContent = totalPrice.toFixed(2);
        
        // Store fare
        formData.farePaid = totalPrice;
    }
    
    // Step 4: Submit Form
    document.getElementById('detailsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const generateBtn = document.getElementById('generateBtn');
        generateBtn.disabled = true;
        generateBtn.textContent = 'Generating Token...';

        // Clear any previous error
        const errBox = document.getElementById('generateError');
        if (errBox) errBox.classList.add('hidden');
        
        const customerName = document.getElementById('customer_name').value;
        const customerMobile = document.getElementById('customer_mobile').value;
        
        if (!customerName || !customerMobile) {
            alert('Please fill in your name and mobile number.');
            generateBtn.disabled = false;
            generateBtn.textContent = '🎫 Generate Token & Confirm Booking';
            return;
        }

        const submitData = {
            service_category_id: formData.serviceId,
            priority_type: formData.priority,
            customer_name: customerName,
            customer_mobile: customerMobile,
            customer_email: document.getElementById('customer_email').value,
            vessel_id: formData.vesselId || null,
            schedule_id: formData.scheduleId || null,
            fare_paid: formData.farePaid || 0,
            booking_type: document.querySelector('input[name="booking_type"]:checked')?.value || 'walkin',
            passenger_count: 1
        };
        
        try {
            const response = await fetch(`${BASE_URL}/api/generate-token.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(submitData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                generatedToken = result.data;
                showSuccessModal(result.data);
            } else {
                generateBtn.disabled = false;
                generateBtn.textContent = '🎫 Generate Token';

                // Show styled error — highlight capacity-full prominently
                const isCapacity = result.data?.type === 'capacity_full';
                const errBox = document.getElementById('generateError');
                if (errBox) {
                    errBox.className = isCapacity
                        ? 'mt-4 p-4 rounded-xl bg-red-50 border-2 border-red-400 text-red-800 font-semibold text-center'
                        : 'mt-4 p-4 rounded-xl bg-yellow-50 border border-yellow-300 text-yellow-800 font-semibold text-center';
                    errBox.innerHTML = (isCapacity ? '🚢 <strong>Vessel Full</strong><br>' : '⚠️ ') + result.message;
                    errBox.classList.remove('hidden');
                } else {
                    alert(result.message);
                }
            }
        } catch (error) {
            alert('Network error. Please try again.');
            generateBtn.disabled = false;
            generateBtn.textContent = '🎫 Generate Token';
        }
    });
    
    function showSuccessModal(token) {
        const modal = document.getElementById('successModal');
        const details = document.getElementById('tokenDetails');
        
        const priorityBadges = {
            'regular': '<span class="px-3 py-1 bg-gray-200 text-gray-700 rounded-full text-sm font-medium">REGULAR PASSENGER</span>',
            'senior': '<span class="px-3 py-1 bg-amber-200 text-amber-700 rounded-full text-sm font-medium">SENIOR CITIZEN</span>',
            'pwd': '<span class="px-3 py-1 bg-blue-200 text-blue-700 rounded-full text-sm font-medium">PWD</span>',
            'pregnant': '<span class="px-3 py-1 bg-pink-200 text-pink-700 rounded-full text-sm font-medium">PREGNANT WOMEN</span>',
            'student':  '<span class="px-3 py-1 bg-green-200 text-green-700 rounded-full text-sm font-medium">STUDENT</span>'
        };
        
        const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(BASE_URL + '/customer/token-status.php?token=' + token.token_number)}`;
        
        details.innerHTML = `
            <div class="text-center mb-6">
                <div class="text-7xl font-black text-indigo-600 mb-3">${token.token_number}</div>
                <div class="text-xl text-gray-700 font-medium mb-3">${token.service_name}</div>
                <div>${priorityBadges[token.priority_type] || ''}</div>
            </div>
            
            <div class="flex justify-center mb-6">
                <div class="bg-white p-4 rounded-xl shadow-lg border-2 border-indigo-200">
                    <img src="${qrCodeUrl}" alt="QR Code" class="w-48 h-48">
                    <p class="text-xs text-gray-600 text-center mt-2">Scan to check status</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-blue-600 mb-1">#${token.queue_position}</div>
                    <div class="text-sm text-gray-600">Queue Position</div>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-purple-600 mb-1">${token.estimated_wait_time}</div>
                    <div class="text-sm text-gray-600">Est. Wait (min)</div>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-green-600 mb-1">${token.issued_at_formatted || new Date(token.issued_at.replace(' ', 'T')).toLocaleTimeString('en-PH', {hour: '2-digit', minute:'2-digit', hour12: true})}</div>
                    <div class="text-sm text-gray-600">Issued At</div>
                </div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                <p class="text-yellow-800 text-sm"><strong>Important:</strong> Please keep this token number. You can check your status anytime.</p>
            </div>
        `;
        
        modal.classList.remove('hidden');
    }
    
    function printToken() {
        if (!generatedToken) return;

        const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=${encodeURIComponent(BASE_URL + '/customer/token-status.php?token=' + generatedToken.token_number)}`;
        const issuedStr = new Date(generatedToken.issued_at).toLocaleString('en-PH', {
            year:'numeric', month:'2-digit', day:'2-digit',
            hour:'2-digit', minute:'2-digit'
        });

        // 58mm ≈ 220px at 96dpi — opening at this width forces the browser to lay
        // out content at thermal-paper width so nothing overflows on the right.
        const printWindow = window.open('', '_blank', 'width=220,height=800,menubar=no,toolbar=no');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=220, initial-scale=1">
                <title>Token - ${generatedToken.token_number}</title>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }

                    @page {
                        size: 58mm auto;
                        margin: 0;
                    }

                    html, body {
                        width: 100%;
                        max-width: 220px;
                        margin: 0;
                        padding: 0;
                        font-family: Arial, sans-serif;
                        font-size: 7.5pt;
                        color: #000;
                        background: #fff;
                    }

                    .ticket {
                        width: 100%;
                        padding: 2mm 1mm;
                        text-align: center;
                    }

                    .header {
                        font-size: 7pt;
                        font-weight: bold;
                        text-transform: uppercase;
                        letter-spacing: 0.2px;
                        line-height: 1.4;
                        border-bottom: 1px dashed #000;
                        padding-bottom: 1.5mm;
                        margin-bottom: 1.5mm;
                    }

                    .token-number {
                        font-size: 13pt;
                        font-weight: 900;
                        line-height: 1.2;
                        margin: 1.5mm 0;
                        letter-spacing: -0.3px;
                    }

                    .divider {
                        border-top: 1px dashed #000;
                        margin: 1.5mm 0;
                    }

                    .info-row {
                        display: flex;
                        justify-content: space-between;
                        font-size: 7pt;
                        line-height: 1.5;
                        text-align: left;
                        gap: 2px;
                    }

                    .info-row .label { font-weight: bold; white-space: nowrap; }
                    .info-row .value { text-align: right; }

                    .qr-wrap {
                        margin: 1.5mm auto;
                        display: inline-block;
                        border: 1px solid #000;
                        padding: 1mm;
                        background: #fff;
                    }

                    .qr-wrap img { display: block; }

                    .scan-txt {
                        font-size: 6pt;
                        color: #444;
                        margin-top: 1mm;
                    }

                    .footer {
                        font-size: 6pt;
                        border-top: 1px dashed #000;
                        padding-top: 1.5mm;
                        margin-top: 1.5mm;
                        color: #333;
                    }
                </style>
            </head>
            <body>
                <div class="ticket">
                    <div class="header">
                        Port Queuing<br>Management System
                    </div>

                    <div class="token-number">${generatedToken.token_number}</div>

                    <div class="divider"></div>

                    <div class="info-row">
                        <span class="label">Service:</span>
                        <span class="value">${generatedToken.service_name}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Priority:</span>
                        <span class="value">${generatedToken.priority_type.replace('_',' ').toUpperCase()}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Queue #:</span>
                        <span class="value">#${generatedToken.queue_position}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Est. Wait:</span>
                        <span class="value">${generatedToken.estimated_wait_time} min</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Issued:</span>
                        <span class="value">${issuedStr}</span>
                    </div>

                    <div class="divider"></div>

                    <div class="qr-wrap">
                        <img src="${qrCodeUrl}" width="80" height="80" alt="QR">
                    </div>
                    <div class="scan-txt">Scan to check status</div>

                    <div class="footer">Please keep this token for reference</div>
                </div>
                <script>
                    window.onload = function() { window.print(); }
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('successModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // ─── Redeem Reservation ─────────────────────────────────────────────────
    let _redeemCode = null;

    function toggleRedeemPanel() {
        const panel   = document.getElementById('redeemPanel');
        const chevron = document.getElementById('redeemChevron');
        const hidden  = panel.classList.toggle('hidden');
        chevron.style.transform = hidden ? '' : 'rotate(180deg)';
    }

    async function lookupReservation() {
        const code = document.getElementById('redeemCodeInput').value.trim().toUpperCase();
        const errDiv = document.getElementById('redeemError');
        errDiv.classList.add('hidden');

        if (!code || code.length < 8) {
            errDiv.textContent = 'Please enter a valid reservation code (e.g. RES-A3X9K2)';
            errDiv.classList.remove('hidden');
            return;
        }

        const btn = document.getElementById('redeemLookupBtn');
        btn.disabled = true;
        btn.textContent = 'Looking up…';

        try {
            const res  = await fetch(`${BASE_URL}/api/reservation.php?code=${encodeURIComponent(code)}`);
            const data = await res.json();

            btn.disabled = false;
            btn.textContent = 'Look Up';

            if (!data.success) {
                errDiv.textContent = data.message || 'Reservation not found.';
                errDiv.classList.remove('hidden');
                return;
            }

            if (data.data.status !== 'pending') {
                const statusMsg = data.data.status === 'waiting' ? 'already redeemed (already in queue)' :
                                  data.data.status === 'completed' ? 'already completed' : data.data.status;
                errDiv.textContent = `This reservation has been ${statusMsg}.`;
                errDiv.classList.remove('hidden');
                return;
            }

            _redeemCode = code;
            const d = data.data;
            const priorityLabels = {regular:'Regular',senior:'Senior Citizen 👴',pwd:'PWD ♿',pregnant:'Pregnant 🤰',emergency:'Emergency 🚨'};

            document.getElementById('redeemPreviewDetails').innerHTML = `
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-600">Reservation Code:</span><span class="font-black text-indigo-700 text-base">${d.reservation_code}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Travel Date:</span><span class="font-semibold">${d.reserved_for_date}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Customer:</span><span class="font-semibold">${d.customer_name || '—'}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Service:</span><span class="font-semibold">${d.service_name}</span></div>
                    ${d.vessel_name ? `<div class="flex justify-between"><span class="text-gray-600">Vessel:</span><span class="font-semibold">${d.vessel_name}</span></div>` : ''}
                    <div class="flex justify-between"><span class="text-gray-600">Passenger Type:</span><span class="font-semibold">${priorityLabels[d.priority_type] || d.priority_type}</span></div>
                </div>
            `;

            document.getElementById('redeemLookupSection').classList.add('hidden');
            document.getElementById('redeemPreview').classList.remove('hidden');

        } catch (err) {
            btn.disabled = false;
            btn.textContent = 'Look Up';
            errDiv.textContent = 'Network error: ' + err;
            errDiv.classList.remove('hidden');
        }
    }

    function cancelRedeemLookup() {
        _redeemCode = null;
        document.getElementById('redeemPreview').classList.add('hidden');
        document.getElementById('redeemLookupSection').classList.remove('hidden');
        document.getElementById('redeemCodeInput').value = '';
    }

    async function confirmRedeem() {
        if (!_redeemCode) return;

        const btn = document.getElementById('redeemConfirmBtn');
        btn.disabled = true;
        btn.textContent = 'Joining queue…';

        try {
            const res  = await fetch(`${BASE_URL}/api/reservation.php`, {
                method  : 'POST',
                headers : {'Content-Type':'application/json'},
                body    : JSON.stringify({action:'redeem', code:_redeemCode}),
            });
            const data = await res.json();

            if (!data.success) {
                alert('❌ ' + data.message);
                btn.disabled = false;
                btn.textContent = '✅ Join Queue Now';
                return;
            }

            const d = data.data;
            // Show success using the same success modal
            document.getElementById('tokenDetails').innerHTML = `
                <div class="text-center mb-6">
                    <div class="inline-block px-6 py-2 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-full font-bold mb-4">
                        📋 RESERVATION REDEEMED
                    </div>
                    <div class="text-8xl font-black text-indigo-600 my-4">${d.token_number}</div>
                    <p class="text-gray-600">You are now in the queue!</p>
                </div>
                <div class="space-y-2 text-sm bg-gray-50 rounded-xl p-4 mb-4">
                    <div class="flex justify-between"><span class="text-gray-600">Queue Position:</span><span class="font-bold text-indigo-700">#${d.queue_position}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Est. Wait Time:</span><span class="font-bold">${d.estimated_wait} min</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Service:</span><span class="font-semibold">${d.service_name}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Passenger Type:</span><span class="font-semibold">${d.priority_type}</span></div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <p class="text-sm text-amber-800"><strong>⏳ Please wait near the boarding counter.</strong> Listen for your token number to be called.</p>
                </div>
            `;
            document.getElementById('successModal').classList.remove('hidden');

        } catch (err) {
            alert('❌ Network error: ' + err);
            btn.disabled = false;
            btn.textContent = '✅ Join Queue Now';
        }
    }
    // ─── Redeem QR Scanner ────────────────────────────────────────────────
    function openRedeemQRScanner() {
        document.getElementById('redeemQRModal').classList.remove('hidden');
        document.getElementById('redeem-qr-reader').innerHTML = '';
        document.getElementById('redeem-qr-status').textContent = 'Starting camera…';

        const html5Qrcode = new Html5Qrcode('redeem-qr-reader');
        window._redeemQrScanner = html5Qrcode;

        Html5Qrcode.getCameras().then(cameras => {
            if (!cameras || cameras.length === 0) {
                document.getElementById('redeem-qr-status').textContent = '⚠️ No camera found on this device.';
                return;
            }
            // Prefer back/environment camera on mobile
            const cam = cameras.find(c => /back|rear|environment/i.test(c.label)) || cameras[cameras.length - 1];
            document.getElementById('redeem-qr-status').textContent = '📷 Camera active — point at QR code';

            html5Qrcode.start(
                cam.id,
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    let code = decodedText.trim().toUpperCase();
                    if (code.includes('CODE=')) {
                        const m = code.match(/CODE=([A-Z0-9-]+)/);
                        if (m) code = m[1];
                    } else if (code.includes('?')) {
                        const parts = decodedText.split(/[/?=]/);
                        const candidate = parts.find(p => /^RES-/.test(p.toUpperCase()));
                        if (candidate) code = candidate.toUpperCase();
                    }
                    closeRedeemQRScanner();
                    document.getElementById('redeemCodeInput').value = code;
                    lookupReservation();
                },
                () => { /* ignore per-frame errors */ }
            ).catch(err => {
                document.getElementById('redeem-qr-status').textContent = '⚠️ Camera error: ' + err;
            });
        }).catch(err => {
            document.getElementById('redeem-qr-status').textContent = '⚠️ Camera access denied. Please allow camera permissions and try again.';
        });
    }

    function closeRedeemQRScanner() {
        if (window._redeemQrScanner) {
            window._redeemQrScanner.stop().catch(() => {});
            window._redeemQrScanner = null;
        }
        document.getElementById('redeemQRModal').classList.add('hidden');
    }
    </script>
    
<!-- Redeem QR Scanner Modal -->
<div id="redeemQRModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                Scan Reservation QR Code
            </h3>
            <button onclick="closeRedeemQRScanner()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <p id="redeem-qr-status" class="text-center text-sm text-gray-500 mb-3">Starting camera…</p>
            <div id="redeem-qr-reader" class="w-full rounded-xl overflow-hidden"></div>
            <div class="mt-4 bg-indigo-50 border border-indigo-200 rounded-xl p-4">
                <p class="text-indigo-800 text-sm"><strong>📱 Tip:</strong> Point your camera at the QR code from your reservation confirmation. It will be detected automatically.</p>
            </div>
        </div>
    </div>
</div>

<!-- html5-qrcode library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <!-- Footer -->
    <div class="fixed bottom-4 right-4 text-white/60 text-xs">
        Developed by Ymath
    </div>
</body>
</html>
