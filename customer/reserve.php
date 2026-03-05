<?php
/**
 * Customer Pre-Booking / Reservation Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/ServiceManager.php';
require_once __DIR__ . '/../includes/PortManager.php';
require_once __DIR__ . '/../includes/SettingsManager.php';

$serviceManager  = new ServiceManager();
$portManager     = new PortManager();
$settingsManager = new SettingsManager();

$services     = $serviceManager->getActiveCategories();
$vessels      = $portManager->getAllVessels();

$prebookEnabled  = (bool)$settingsManager->getSetting('prebooking_enabled', '1');
$advanceDays     = (int)$settingsManager->getSetting('prebooking_advance_days', '7');

$pageTitle = 'Advance Reservation';
$minDate   = date('Y-m-d', strtotime('+1 day'));
$maxDate   = date('Y-m-d', strtotime("+{$advanceDays} days"));

// Pre-load standard schedules for JS consumption (for any future date)
$allSchedules = $portManager->getAllStandardSchedules();
$schedulesJson = json_encode(array_map(fn($s) => [
    'id'             => $s['id'],
    'vessel_id'      => $s['vessel_id'],
    'vessel_name'    => $s['vessel_name'] ?? ($s['vessel'] ?? ''),
    'route_name'     => $s['route_name']  ?? ($s['route']  ?? ''),
    'departure_time' => $s['departure_time'],
    'arrival_time'   => $s['arrival_time'],
], $allSchedules));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .step-card { transition: all 0.3s ease; }
        .priority-badge { transition: all 0.2s; }
        .priority-badge:hover { transform: scale(1.05); }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-700 via-indigo-600 to-blue-500 min-h-screen">
<div class="container mx-auto px-4 py-8 max-w-4xl">

    <!-- Header -->
    <header class="text-center mb-8">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-3 drop-shadow-lg"><?php echo APP_NAME; ?></h1>
        <p class="text-xl text-white/90">📋 Advance Reservation</p>
        <p class="text-white/70 text-sm mt-1">Book up to <?php echo $advanceDays; ?> days in advance</p>
    </header>

    <?php if (!$prebookEnabled): ?>
    <div class="bg-red-50 border-2 border-red-300 rounded-2xl p-8 text-center max-w-xl mx-auto">
        <div class="text-5xl mb-4">🚫</div>
        <h2 class="text-2xl font-bold text-red-700 mb-2">Pre-booking is currently unavailable</h2>
        <p class="text-red-600">Please visit the terminal to get a token, or check back later.</p>
        <a href="<?php echo BASE_URL; ?>/customer/index.php" class="mt-6 inline-block px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition-colors">
            ← Get a Walk-in Token
        </a>
    </div>
    <?php else: ?>

    <!-- Progress Steps -->
    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 mb-6">
        <div class="flex justify-between items-center relative">
            <div class="absolute top-5 left-0 right-0 h-1 bg-white/20">
                <div id="progressBar" class="h-full bg-white transition-all duration-500" style="width:0%"></div>
            </div>
            <?php foreach ([['1','Date & Trip'],['2','Service'],['3','Passenger'],['4','Details']] as $i => [$n,$lbl]): ?>
            <div class="step-indicator flex-1 text-center relative z-10">
                <div id="stepCircle<?php echo $n; ?>" class="w-10 h-10 mx-auto <?php echo $n === '1' ? 'bg-white text-purple-700' : 'bg-white/20 text-white'; ?> rounded-full flex items-center justify-center font-bold mb-2 shadow-lg"><?php echo $n; ?></div>
                <div class="<?php echo $n === '1' ? 'text-white' : 'text-white/60'; ?> text-xs md:text-sm font-medium"><?php echo $lbl; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── Step 1: Date & Trip ─────────────────────────────────────── -->
    <div id="step1" class="step-content">
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2 text-center">Select Date & Trip</h2>
            <p class="text-gray-600 text-center mb-8">Choose which day you plan to travel</p>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Travel Date <span class="text-red-500">*</span></label>
                <input type="date" id="reservationDate"
                       min="<?php echo $minDate; ?>" max="<?php echo $maxDate; ?>"
                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:ring focus:ring-purple-200 transition-all text-lg font-semibold">
                <p class="text-sm text-gray-500 mt-1">Available from tomorrow to <?php echo date('M d, Y', strtotime($maxDate)); ?></p>
            </div>

            <!-- Schedule picker (optional) -->
            <div id="scheduleWrap" class="mb-6 hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Trip / Schedule (optional)</label>
                <div id="scheduleList" class="space-y-3 max-h-64 overflow-y-auto"></div>
                <p class="text-xs text-gray-400 mt-2">Selecting a trip lets staff match your reservation to a vessel. You can skip this step.</p>
            </div>

            <button onclick="goToStep(2)" id="step1NextBtn"
                    class="w-full py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-bold text-lg shadow-lg transition-all transform hover:scale-[1.02] disabled:opacity-40 disabled:scale-100"
                    disabled>
                Next: Choose Service →
            </button>
        </div>
    </div>

    <!-- ── Step 2: Service Selection ──────────────────────────────── -->
    <div id="step2" class="step-content hidden">
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2 text-center">Select Service Type</h2>
            <p class="text-gray-600 text-center mb-8">Choose the service you need</p>
            <div id="serviceOptions" class="space-y-3">
                <?php foreach ($services as $service): ?>
                <button onclick="selectService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['name'], ENT_QUOTES); ?>', <?php echo $service['avg_service_time']; ?>)"
                        class="w-full bg-gradient-to-r from-gray-50 to-gray-100 hover:from-purple-50 hover:to-indigo-50 border-2 border-gray-200 hover:border-purple-400 rounded-xl p-6 text-left transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 group-hover:text-purple-600 mb-1"><?php echo htmlspecialchars($service['name']); ?></h3>
                            <p class="text-gray-600 text-sm">Service Code: <?php echo $service['code']; ?></p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500 mb-1">Avg. Time</div>
                            <div class="text-2xl font-bold text-purple-600"><?php echo $service['avg_service_time']; ?> <span class="text-sm">min</span></div>
                        </div>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
            <button onclick="goToStep(1)" class="mt-6 w-full py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-medium transition-colors">
                ← Back to Date & Trip
            </button>
        </div>
    </div>

    <!-- ── Step 3: Passenger Type ──────────────────────────────────── -->
    <div id="step3" class="step-content hidden">
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2 text-center">Passenger Type</h2>
            <p class="text-gray-600 text-center mb-8">Select your passenger category</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button onclick="selectPriority('regular')" class="priority-badge bg-gradient-to-br from-gray-50 to-gray-100 border-2 border-gray-300 rounded-2xl p-6 text-left hover:border-gray-500">
                    <div class="flex items-center justify-between mb-3"><span class="text-3xl">👤</span><span class="px-3 py-1 bg-gray-200 text-gray-700 rounded-full text-sm font-medium">Regular</span></div>
                    <h3 class="text-xl font-bold text-gray-800 mb-1">Regular Passenger</h3>
                    <p class="text-gray-600 text-sm">Standard boarding queue</p>
                </button>
                <button onclick="selectPriority('senior')" class="priority-badge bg-gradient-to-br from-amber-50 to-amber-100 border-2 border-amber-300 rounded-2xl p-6 text-left hover:border-amber-500">
                    <div class="flex items-center justify-between mb-3"><span class="text-3xl">👴</span><span class="px-3 py-1 bg-amber-200 text-amber-700 rounded-full text-sm font-medium">Priority</span></div>
                    <h3 class="text-xl font-bold text-gray-800 mb-1">Senior Citizen</h3>
                    <p class="text-gray-600 text-sm">60 years old and above</p>
                </button>
                <button onclick="selectPriority('pwd')" class="priority-badge bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-300 rounded-2xl p-6 text-left hover:border-blue-500">
                    <div class="flex items-center justify-between mb-3"><span class="text-3xl">♿</span><span class="px-3 py-1 bg-blue-200 text-blue-700 rounded-full text-sm font-medium">Priority</span></div>
                    <h3 class="text-xl font-bold text-gray-800 mb-1">PWD</h3>
                    <p class="text-gray-600 text-sm">Person with Disability</p>
                </button>
                <button onclick="selectPriority('pregnant')" class="priority-badge bg-gradient-to-br from-pink-50 to-pink-100 border-2 border-pink-300 rounded-2xl p-6 text-left hover:border-pink-500">
                    <div class="flex items-center justify-between mb-3"><span class="text-3xl">🤰</span><span class="px-3 py-1 bg-pink-200 text-pink-700 rounded-full text-sm font-medium">Priority</span></div>
                    <h3 class="text-xl font-bold text-gray-800 mb-1">Pregnant Women</h3>
                    <p class="text-gray-600 text-sm">Expecting mothers</p>
                </button>
            </div>
            <button onclick="goToStep(2)" class="mt-6 w-full py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-medium transition-colors">
                ← Back to Service Selection
            </button>
        </div>
    </div>

    <!-- ── Step 4: Contact Details ─────────────────────────────────── -->
    <div id="step4" class="step-content hidden">
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2 text-center">Contact Information</h2>
            <p class="text-gray-600 text-center mb-8">We'll send your reservation code to your mobile</p>

            <!-- Booking Summary -->
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border-2 border-purple-200 rounded-2xl p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Reservation Summary</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-600">Travel Date:</span>   <span class="font-semibold" id="sum_date">—</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Schedule:</span>      <span class="font-semibold" id="sum_schedule">Not selected</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Service:</span>       <span class="font-semibold" id="sum_service">—</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Passenger Type:</span><span class="font-semibold" id="sum_priority">—</span></div>
                </div>
            </div>

            <form id="reserveForm" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="res_name" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:ring focus:ring-purple-200 transition-all" placeholder="Enter your full name">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mobile Number <span class="text-red-500">*</span></label>
                    <input type="tel" id="res_mobile" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:ring focus:ring-purple-200 transition-all" placeholder="09XX-XXX-XXXX">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address (Optional)</label>
                    <input type="email" id="res_email" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:ring focus:ring-purple-200 transition-all" placeholder="your.email@example.com">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Number of Passengers</label>
                    <input type="number" id="res_pax" min="1" max="20" value="1" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:ring focus:ring-purple-200 transition-all">
                </div>

                <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-4">
                    <p class="text-sm text-blue-800"><strong>📋 How to redeem:</strong> On your travel day, visit any terminal kiosk, tap "Redeem Reservation", and enter your code to join the queue instantly.</p>
                </div>

                <button type="submit" id="reserveBtn" class="w-full py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-bold text-lg shadow-lg transition-all transform hover:scale-[1.02]">
                    📋 Confirm Reservation
                </button>
            </form>

            <button onclick="goToStep(3)" class="mt-4 w-full py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-medium transition-colors">
                ← Back to Passenger Type
            </button>
        </div>
    </div>

    <div class="text-center mt-6">
        <a href="<?php echo BASE_URL; ?>/customer/index.php" class="text-white hover:text-white/80 underline mr-6">Walk-in Token</a>
        <a href="<?php echo BASE_URL; ?>" class="text-white hover:text-white/80 underline">Back to Home</a>
    </div>

    <!-- ── Success Modal ──────────────────────────────────────────────────── -->
    <div id="successModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-6 rounded-t-3xl text-center">
                <div class="text-5xl mb-2">🎉</div>
                <h2 class="text-3xl font-bold text-white">Reservation Confirmed!</h2>
            </div>
            <div class="p-8" id="reservationDetails"><!-- populated by JS --></div>
            <div class="flex flex-col sm:flex-row gap-3 px-8 pb-8">
                <button onclick="printPage()" class="flex-1 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-medium transition-colors">🖨️ Print</button>
                <button onclick="location.reload()" class="flex-1 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-medium transition-colors">📋 New Reservation</button>
                <a href="<?php echo BASE_URL; ?>/customer/index.php" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium text-center transition-colors">🎫 Walk-in Token</a>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const ALL_SCHEDULES = <?php echo $schedulesJson; ?>;
let currentStep = 1;
let formData = {
    reservationDate : '',
    scheduleId      : null,
    scheduleName    : 'Not selected',
    vesselId        : null,
    serviceId       : '',
    serviceName     : '',
    priority        : '',
};

// ── Step navigation ──────────────────────────────────────────────────────────
function goToStep(step) {
    document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
    document.getElementById('step' + step).classList.remove('hidden');
    currentStep = step;
    const progress = ((step - 1) / 3) * 100;
    document.getElementById('progressBar').style.width = progress + '%';
    for (let i = 1; i <= 4; i++) {
        const c = document.getElementById('stepCircle' + i);
        if (i < step) {
            c.className = 'w-10 h-10 mx-auto bg-green-500 text-white rounded-full flex items-center justify-center font-bold mb-2 shadow-lg';
            c.innerHTML = '✓';
        } else if (i === step) {
            c.className = 'w-10 h-10 mx-auto bg-white text-purple-700 rounded-full flex items-center justify-center font-bold mb-2 shadow-lg';
            c.innerHTML = i;
        } else {
            c.className = 'w-10 h-10 mx-auto bg-white/20 text-white rounded-full flex items-center justify-center font-bold mb-2';
            c.innerHTML = i;
        }
    }
    window.scrollTo({top: 0, behavior: 'smooth'});
}

// ── Step 1: Date & schedule picker ───────────────────────────────────────────
document.getElementById('reservationDate').addEventListener('change', function () {
    const date = this.value;
    formData.reservationDate = date;
    if (!date) {
        document.getElementById('scheduleWrap').classList.add('hidden');
        document.getElementById('step1NextBtn').disabled = true;
        return;
    }

    // Filter schedules — for simplicity show all standard schedules (they repeat daily)
    const dayOfWeek = new Date(date + 'T00:00:00').toLocaleDateString('en-US', {weekday:'long'});
    const list = document.getElementById('scheduleList');
    list.innerHTML = '';

    ALL_SCHEDULES.forEach(s => {
        const card = document.createElement('button');
        card.type = 'button';
        card.className = 'w-full text-left p-4 border-2 border-gray-200 hover:border-purple-400 rounded-xl transition-all hover:bg-purple-50 flex justify-between items-center';
        card.innerHTML = `
            <div>
                <p class="font-bold text-gray-800">${s.vessel_name || 'Unknown vessel'}</p>
                <p class="text-sm text-gray-600">${s.route_name || ''}</p>
            </div>
            <div class="text-right">
                <p class="font-bold text-purple-600">${s.departure_time ? s.departure_time.substring(0,5) : ''}</p>
                <p class="text-xs text-gray-500">Arr: ${s.arrival_time ? s.arrival_time.substring(0,5) : ''}</p>
            </div>`;
        card.addEventListener('click', () => {
            // Highlight selected
            list.querySelectorAll('button').forEach(b => b.classList.remove('border-purple-600','bg-purple-50'));
            card.classList.add('border-purple-600','bg-purple-50');
            formData.scheduleId   = s.id;
            formData.scheduleName = (s.vessel_name || '') + ' ' + (s.departure_time ? s.departure_time.substring(0,5) : '');
            formData.vesselId     = s.vessel_id;
        });
        list.appendChild(card);
    });

    if (ALL_SCHEDULES.length) {
        document.getElementById('scheduleWrap').classList.remove('hidden');
    }
    document.getElementById('step1NextBtn').disabled = false;
});

// ── Step 2: Service ───────────────────────────────────────────────────────────
function selectService(id, name, avg) {
    formData.serviceId   = id;
    formData.serviceName = name;
    goToStep(3);
}

// ── Step 3: Priority ──────────────────────────────────────────────────────────
function selectPriority(type) {
    formData.priority = type;
    // Update summary
    document.getElementById('sum_date').textContent     = formData.reservationDate;
    document.getElementById('sum_schedule').textContent = formData.scheduleName;
    document.getElementById('sum_service').textContent  = formData.serviceName;
    const labels = {regular:'Regular',senior:'Senior Citizen',pwd:'PWD',pregnant:'Pregnant'};
    document.getElementById('sum_priority').textContent = labels[type] || type;
    goToStep(4);
}

// ── Step 4: Submit ────────────────────────────────────────────────────────────
document.getElementById('reserveForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('reserveBtn');
    btn.disabled = true;
    btn.textContent = 'Creating reservation…';

    const payload = {
        action              : 'create',
        service_category_id : formData.serviceId,
        priority_type       : formData.priority,
        customer_name       : document.getElementById('res_name').value.trim(),
        customer_mobile     : document.getElementById('res_mobile').value.trim(),
        customer_email      : document.getElementById('res_email').value.trim(),
        passenger_count     : parseInt(document.getElementById('res_pax').value) || 1,
        reserved_for_date   : formData.reservationDate,
        schedule_id         : formData.scheduleId,
        vessel_id           : formData.vesselId,
    };

    try {
        const res  = await fetch(`${BASE_URL}/api/reservation.php`, {
            method  : 'POST',
            headers : {'Content-Type': 'application/json'},
            body    : JSON.stringify(payload),
        });
        const data = await res.json();

        if (!data.success) {
            alert('❌ ' + data.message);
            btn.disabled = false;
            btn.textContent = '📋 Confirm Reservation';
            return;
        }

        // Show success modal
        document.getElementById('reservationDetails').innerHTML = `
            <div class="text-center mb-6">
                <p class="text-gray-600 mb-2">Your Reservation Code</p>
                <div class="text-5xl font-black text-purple-700 tracking-widest mb-2">${data.data.reservation_code}</div>
                <p class="text-xs text-gray-400 mb-4">Save this code — you'll need it to check in</p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&color=5b21b6&bgcolor=ffffff&data=${encodeURIComponent(data.data.reservation_code)}"
                     alt="Reservation QR Code"
                     class="mx-auto rounded-xl border-4 border-purple-200 shadow-lg">
                <p class="text-xs text-gray-400 mt-2">Scan this QR at the terminal kiosk to redeem</p>
            </div>
            <div class="space-y-2 text-sm bg-gray-50 rounded-xl p-4">
                <div class="flex justify-between"><span class="text-gray-600">Travel Date:</span>   <span class="font-semibold">${data.data.reserved_for_date}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Token Number:</span>  <span class="font-semibold">${data.data.token_number}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Schedule:</span>      <span class="font-semibold">${formData.scheduleName}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Service:</span>       <span class="font-semibold">${formData.serviceName}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Passenger Type:</span><span class="font-semibold">${formData.priority}</span></div>
            </div>
            <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                <p class="text-sm text-yellow-800"><strong>⚠️ Important:</strong> On your travel day, go to a terminal kiosk, tap "Redeem Reservation", and enter <strong>${data.data.reservation_code}</strong> to join the priority queue.</p>
            </div>`;
        document.getElementById('successModal').classList.remove('hidden');

    } catch (err) {
        alert('❌ Network error: ' + err);
        btn.disabled = false;
        btn.textContent = '📋 Confirm Reservation';
    }
});

function printPage() { window.print(); }
</script>
</body>
</html>
