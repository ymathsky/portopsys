<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TokenManager.php';
require_once __DIR__ . '/../includes/ServiceManager.php';
require_once __DIR__ . '/../includes/SettingsManager.php';

requireLogin();

$tokenManager    = new TokenManager();
$serviceManager  = new ServiceManager();
$settingsManager = new SettingsManager();

$stats           = $tokenManager->getTodayStatistics();
$waitingQueue    = $tokenManager->getWaitingQueue(20);
$serving         = $tokenManager->getCurrentlyServing();
$counterStatus   = $serviceManager->getCounterStatus();
$hourlyStats     = $tokenManager->getHourlyStats();
$weeklyTrend     = $tokenManager->getWeeklyTrend();
$outcomeBreakdown= $tokenManager->getOutcomeBreakdown();
$counterPerf     = $tokenManager->getCounterPerformance();
$serviceMode     = $settingsManager->getSetting('service_mode', 'online');
$serviceModeMsg  = $settingsManager->getSetting('service_mode_message', '');

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<style>
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
    }
    50% {
        box-shadow: 0 0 30px rgba(59, 130, 246, 0.5);
    }
}

@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

.stat-card {
    animation: slideInUp 0.6s ease-out;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-8px);
}

.counter-card {
    animation: slideInUp 0.6s ease-out;
    transition: all 0.3s ease;
}

.counter-card:hover {
    transform: translateY(-5px);
}

.live-badge {
    animation: pulse-glow 2s ease-in-out infinite;
}

.gradient-bg-1 {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.gradient-bg-2 {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.gradient-bg-3 {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.gradient-bg-4 {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.gradient-bg-5 {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.gradient-bg-6 {
    background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
}

.glassmorphic {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}
</style>

<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">Dashboard Overview</h1>
        <p class="text-sm text-gray-500 mt-1">Real-time queue management and analytics</p>
    </div>
    <div>
        <span class="live-badge inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow-lg">
            <span class="w-2 h-2 bg-white rounded-full mr-2 animate-pulse"></span>
            Live Data
        </span>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
    <div class="stat-card gradient-bg-1 p-6 rounded-2xl shadow-xl hover:shadow-2xl relative overflow-hidden" style="animation-delay: 0s;">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-16 -mt-16"></div>
        <div class="relative z-10">
            <div class="text-xs font-semibold text-white/80 uppercase tracking-wider">Total Tokens</div>
            <div class="mt-3 text-4xl font-extrabold text-white"><?php echo number_format($stats['total_tokens']); ?></div>
            <div class="mt-2 text-xs text-white/70">Issued Today</div>
        </div>
    </div>
    
    <div class="stat-card gradient-bg-2 p-6 rounded-2xl shadow-xl hover:shadow-2xl relative overflow-hidden" style="animation-delay: 0.1s;">
        <div class="absolute bottom-0 left-0 w-32 h-32 bg-white opacity-10 rounded-full -ml-16 -mb-16"></div>
        <div class="relative z-10">
            <div class="text-xs font-semibold text-white/80 uppercase tracking-wider">Waiting</div>
            <div class="mt-3 text-4xl font-extrabold text-white"><?php echo number_format($stats['waiting']); ?></div>
            <div class="mt-2 text-xs text-white/70">Pending in Queue</div>
        </div>
    </div>
    
    <div class="stat-card gradient-bg-3 p-6 rounded-2xl shadow-xl hover:shadow-2xl relative overflow-hidden" style="animation-delay: 0.2s;">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-16 -mt-16"></div>
        <div class="relative z-10">
            <div class="text-xs font-semibold text-white/80 uppercase tracking-wider">Serving</div>
            <div class="mt-3 text-4xl font-extrabold text-white"><?php echo number_format($stats['serving']); ?></div>
            <div class="mt-2 text-xs text-white/70">Active Counters</div>
        </div>
    </div>
    
    <div class="stat-card gradient-bg-4 p-6 rounded-2xl shadow-xl hover:shadow-2xl relative overflow-hidden" style="animation-delay: 0.3s;">
        <div class="absolute bottom-0 left-0 w-32 h-32 bg-white opacity-10 rounded-full -ml-16 -mb-16"></div>
        <div class="relative z-10">
            <div class="text-xs font-semibold text-white/80 uppercase tracking-wider">Completed</div>
            <div class="mt-3 text-4xl font-extrabold text-white"><?php echo number_format($stats['completed']); ?></div>
            <div class="mt-2 text-xs text-white/70">Processed Today</div>
        </div>
    </div>
    
    <div class="stat-card gradient-bg-5 p-6 rounded-2xl shadow-xl hover:shadow-2xl relative overflow-hidden" style="animation-delay: 0.4s;">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-16 -mt-16"></div>
        <div class="relative z-10">
            <div class="text-xs font-semibold text-white/80 uppercase tracking-wider">Avg Wait</div>
            <div class="mt-3 text-4xl font-extrabold text-white"><?php echo round($stats['avg_wait_time'] ?? 0); ?><span class="text-xl font-normal text-white/70">m</span></div>
            <div class="mt-2 text-xs text-white/70">Target: < 15m</div>
        </div>
    </div>
    
    <div class="stat-card gradient-bg-6 p-6 rounded-2xl shadow-xl hover:shadow-2xl relative overflow-hidden" style="animation-delay: 0.5s;">
        <div class="absolute bottom-0 left-0 w-32 h-32 bg-white opacity-10 rounded-full -ml-16 -mb-16"></div>
        <div class="relative z-10">
            <div class="text-xs font-semibold text-white/80 uppercase tracking-wider">Avg Service</div>
            <div class="mt-3 text-4xl font-extrabold text-white"><?php echo round($stats['avg_service_time'] ?? 0); ?><span class="text-xl font-normal text-white/70">m</span></div>
            <div class="mt-2 text-xs text-white/70">Target: < 10m</div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     OPERATIONS CONTROL ROW
════════════════════════════════════════════════════════════ -->
<?php if (hasPermission('admin')): ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

    <!-- Service Mode Toggle -->
    <div class="glassmorphic rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl <?php echo $serviceMode==='online' ? 'bg-gradient-to-br from-emerald-400 to-green-500' : 'bg-gradient-to-br from-red-400 to-rose-500'; ?> flex items-center justify-center shadow-lg">
                    <span class="text-xl"><?php echo $serviceMode==='online' ? '🟢' : '🔴'; ?></span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Service Mode</h3>
                    <p class="text-xs text-gray-500">Controls token generation &amp; display board</p>
                </div>
            </div>
            <span id="modeLabel" class="px-3 py-1 rounded-full text-xs font-bold <?php echo $serviceMode==='online' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo strtoupper($serviceMode); ?>
            </span>
        </div>
        <div id="offlineMsgWrap" class="mb-4 <?php echo $serviceMode==='online' ? 'hidden' : ''; ?>">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Offline Message</label>
            <textarea id="offlineMsgInput" rows="2" class="w-full border border-gray-200 rounded-lg text-sm p-2 focus:ring-2 focus:ring-indigo-400 focus:outline-none resize-none"><?php echo htmlspecialchars($serviceModeMsg); ?></textarea>
        </div>
        <div class="flex gap-3">
            <button onclick="setServiceMode('online')" id="btnOnline"
                class="flex-1 py-2 rounded-xl text-sm font-bold transition-all <?php echo $serviceMode==='online' ? 'bg-gradient-to-r from-emerald-400 to-green-500 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                ✅ Go Online
            </button>
            <button onclick="setServiceMode('offline')" id="btnOffline"
                class="flex-1 py-2 rounded-xl text-sm font-bold transition-all <?php echo $serviceMode==='offline' ? 'bg-gradient-to-r from-red-400 to-rose-500 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                🔴 Go Offline
            </button>
        </div>
        <p id="modeFeedback" class="text-xs text-center mt-2 text-gray-400"></p>
    </div>

    <!-- End of Day Reset -->
    <div class="glassmorphic rounded-2xl shadow-xl p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center shadow-lg">
                <span class="text-xl">🔄</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900">End of Day Reset</h3>
                <p class="text-xs text-gray-500">Archive queue &amp; reset all counters</p>
            </div>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4 text-xs text-amber-800 space-y-1">
            <div>• Cancels all remaining waiting/called tokens</div>
            <div>• Auto-completes any still-serving tokens</div>
            <div>• Resets all counter statuses to Available</div>
        </div>
        <button onclick="confirmEndOfDay()" id="btnEOD"
            class="w-full py-2.5 rounded-xl bg-gradient-to-r from-orange-400 to-amber-500 text-white text-sm font-bold shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all">
            🔄 Run End of Day Reset
        </button>
        <p id="eodFeedback" class="text-xs text-center mt-2 text-gray-400"></p>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════
     ANALYTICS CHARTS
════════════════════════════════════════════════════════════ -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Hourly Bar Chart -->
    <div class="glassmorphic rounded-2xl shadow-xl p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900">Today — Tokens by Hour</h3>
                <p class="text-xs text-gray-500">Volume breakdown across the day</p>
            </div>
        </div>
        <canvas id="hourlyChart" height="180"></canvas>
    </div>

    <!-- 7-Day Trend Line Chart -->
    <div class="glassmorphic rounded-2xl shadow-xl p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-400 to-pink-500 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900">7-Day Token Trend</h3>
                <p class="text-xs text-gray-500">Volume over the last 7 days</p>
            </div>
        </div>
        <canvas id="weeklyChart" height="180"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Outcome Doughnut -->
    <div class="glassmorphic rounded-2xl shadow-xl p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-cyan-500 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900">Today's Outcomes</h3>
                <p class="text-xs text-gray-500">Token resolution breakdown</p>
            </div>
        </div>
        <canvas id="outcomeChart" height="200"></canvas>
    </div>

    <!-- Counter Performance Table -->
    <div class="glassmorphic rounded-2xl shadow-xl p-6 lg:col-span-2">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-400 to-pink-500 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900">Counter Performance Today</h3>
                <p class="text-xs text-gray-500">Tokens handled &amp; service times per counter</p>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-500 uppercase border-b border-gray-200">
                    <th class="text-left pb-2 font-semibold">Counter</th>
                    <th class="text-center pb-2 font-semibold">Handled</th>
                    <th class="text-center pb-2 font-semibold">Avg Service</th>
                    <th class="text-center pb-2 font-semibold">No-shows</th>
                    <th class="text-left pb-2 font-semibold">Bar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                $maxHandled = max(1, max(array_column($counterPerf, 'tokens_handled') ?: [1]));
                foreach ($counterPerf as $cp):
                    $pct = $maxHandled > 0 ? round(($cp['tokens_handled'] / $maxHandled) * 100) : 0;
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-2 font-semibold text-gray-800"><?php echo htmlspecialchars($cp['counter_name']); ?> <span class="text-xs text-gray-400">#<?php echo $cp['counter_number']; ?></span></td>
                    <td class="py-2 text-center font-bold text-indigo-600"><?php echo $cp['tokens_handled']; ?></td>
                    <td class="py-2 text-center text-gray-600"><?php echo $cp['avg_service_time'] ? round($cp['avg_service_time']).'m' : '—'; ?></td>
                    <td class="py-2 text-center <?php echo $cp['no_shows'] > 0 ? 'text-amber-600 font-bold' : 'text-gray-400'; ?>"><?php echo $cp['no_shows']; ?></td>
                    <td class="py-2 pl-2">
                        <div class="bg-gray-100 rounded-full h-2 w-full">
                            <div class="bg-gradient-to-r from-indigo-400 to-purple-500 h-2 rounded-full" style="width:<?php echo $pct; ?>%"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($counterPerf)): ?>
                <tr><td colspan="5" class="py-8 text-center text-gray-400 italic text-sm">No counter data yet today</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Analytics data from PHP ──────────────────────────────
const hourlyData  = <?php echo json_encode(array_values($hourlyStats)); ?>;
const weeklyLabels= <?php echo json_encode($weeklyTrend['labels']); ?>;
const weeklyData  = <?php echo json_encode($weeklyTrend['data']); ?>;
const outcomeData = <?php echo json_encode(array_values($outcomeBreakdown)); ?>;

// Hourly bar chart
new Chart(document.getElementById('hourlyChart'), {
    type: 'bar',
    data: {
        labels: Array.from({length:24}, (_,i)=> i%3===0 ? (i===0?'12am':i<12?i+'am':i===12?'12pm':(i-12)+'pm') : ''),
        datasets: [{ data: hourlyData, backgroundColor: 'rgba(99,102,241,0.7)',
            borderColor:'rgb(99,102,241)', borderWidth:1, borderRadius:4 }]
    },
    options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}},
        responsive:true, maintainAspectRatio:true }
});

// Weekly trend line chart
new Chart(document.getElementById('weeklyChart'), {
    type: 'line',
    data: {
        labels: weeklyLabels,
        datasets: [{data: weeklyData, borderColor:'rgb(168,85,247)', backgroundColor:'rgba(168,85,247,0.1)',
            tension:0.4, fill:true, pointBackgroundColor:'rgb(168,85,247)', pointRadius:5 }]
    },
    options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}},
        responsive:true, maintainAspectRatio:true }
});

// Outcome doughnut 
new Chart(document.getElementById('outcomeChart'), {
    type: 'doughnut',
    data: {
        labels: ['Completed','Cancelled','No-Show','Waiting'],
        datasets:[{data: outcomeData, backgroundColor:['#10b981','#f59e0b','#ef4444','#6366f1'],
            borderWidth:0, hoverOffset:6 }]
    },
    options: { plugins:{legend:{position:'bottom', labels:{font:{size:11}}}}, cutout:'65%',
        responsive:true, maintainAspectRatio:true }
});

// ── Operations Controls ──────────────────────────────────
async function setServiceMode(mode) {
    const msg = mode==='offline' ? (document.getElementById('offlineMsgInput').value || '') : '';
    if (mode==='offline' && !confirm('Take the system OFFLINE? Token generation will be blocked and the display board will show a maintenance screen.')) return;
    document.getElementById('modeFeedback').textContent = 'Updating…';
    try {
        const res = await fetch('<?php echo BASE_URL; ?>/api/service-mode.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({mode, message: msg})
        });
        const result = await res.json();
        if (result.success) {
            document.getElementById('modeFeedback').textContent = '✅ ' + result.message;
            document.getElementById('modeLabel').textContent = mode.toUpperCase();
            document.getElementById('modeLabel').className = 'px-3 py-1 rounded-full text-xs font-bold ' +
                (mode==='online' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700');
            document.getElementById('offlineMsgWrap').classList.toggle('hidden', mode==='online');
            document.getElementById('btnOffline').className = 'flex-1 py-2 rounded-xl text-sm font-bold transition-all ' +
                (mode==='offline' ? 'bg-gradient-to-r from-red-400 to-rose-500 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
            document.getElementById('btnOnline').className = 'flex-1 py-2 rounded-xl text-sm font-bold transition-all ' +
                (mode==='online' ? 'bg-gradient-to-r from-emerald-400 to-green-500 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
            setTimeout(()=>document.getElementById('modeFeedback').textContent='',3000);
        } else {
            document.getElementById('modeFeedback').textContent = '❌ ' + result.message;
        }
    } catch(e) {
        document.getElementById('modeFeedback').textContent = '❌ Network error';
    }
}

async function confirmEndOfDay() {
    if (!confirm('Run End of Day Reset?\n\nThis will:\n• Cancel all waiting tokens\n• Complete any serving tokens\n• Reset all counters\n\nThis cannot be undone.')) return;
    const btn = document.getElementById('btnEOD');
    btn.disabled = true;
    btn.textContent = '⏳ Running…';
    document.getElementById('eodFeedback').textContent = '';
    try {
        const res = await fetch('<?php echo BASE_URL; ?>/api/end-of-day-reset.php', {method:'POST'});
        const result = await res.json();
        if (result.success) {
            const d = result.data;
            document.getElementById('eodFeedback').textContent =
                `✅ Done — ${d.cancelled} cancelled, ${d.auto_completed} completed, ${d.counters_reset} counters reset, ${d.statuses_reset} trip statuses reset`;
            document.getElementById('eodFeedback').className = 'text-xs text-center mt-2 text-emerald-600 font-semibold';
            btn.textContent = '✅ Reset Complete';
            setTimeout(()=>{ btn.disabled=false; btn.textContent='🔄 Run End of Day Reset'; }, 5000);
        } else {
            document.getElementById('eodFeedback').textContent = '❌ ' + result.message;
            document.getElementById('eodFeedback').className = 'text-xs text-center mt-2 text-red-500';
            btn.disabled=false; btn.textContent='🔄 Run End of Day Reset';
        }
    } catch(e) {
        document.getElementById('eodFeedback').textContent = '❌ Network error';
        btn.disabled=false; btn.textContent='🔄 Run End of Day Reset';
    }
}
</script>

<!-- Counter Status -->
<?php
$statusBarCls  = ['available'=>'bg-gradient-to-r from-blue-400 to-indigo-500','serving'=>'bg-gradient-to-r from-green-400 to-emerald-500','break'=>'bg-gradient-to-r from-amber-400 to-orange-500','closed'=>'bg-gradient-to-r from-gray-400 to-slate-500'];
$statusBadgeCls= ['available'=>'bg-gradient-to-r from-blue-400 to-indigo-500 text-white','serving'=>'bg-gradient-to-r from-green-400 to-emerald-500 text-white','break'=>'bg-gradient-to-r from-amber-400 to-orange-500 text-white','closed'=>'bg-gradient-to-r from-gray-400 to-slate-500 text-white'];
?>
<div class="mb-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mr-4 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Live Counter Status</h2>
                <p class="text-sm text-gray-500">Real-time &bull; auto-refreshes every 5 seconds</p>
            </div>
        </div>
        <div class="flex gap-2 text-xs">
            <span class="flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-50 text-blue-700 font-semibold"><span class="w-2 h-2 rounded-full bg-blue-400 inline-block"></span>Available</span>
            <span class="flex items-center gap-1 px-2 py-1 rounded-lg bg-green-50 text-green-700 font-semibold"><span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>Serving</span>
            <span class="flex items-center gap-1 px-2 py-1 rounded-lg bg-amber-50 text-amber-700 font-semibold"><span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>Break</span>
            <span class="flex items-center gap-1 px-2 py-1 rounded-lg bg-gray-100 text-gray-600 font-semibold"><span class="w-2 h-2 rounded-full bg-gray-400 inline-block"></span>Closed</span>
        </div>
    </div>

    <div id="counterGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($counterStatus as $index => $counter):
            $barCls   = $statusBarCls[$counter['current_status']]   ?? $statusBarCls['available'];
            $badgeCls = $statusBadgeCls[$counter['current_status']] ?? $statusBadgeCls['available'];
        ?>
            <div class="counter-card glassmorphic rounded-2xl shadow-xl hover:shadow-2xl overflow-hidden relative" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                <div class="absolute left-0 top-0 right-0 h-1 <?php echo $barCls; ?>"></div>
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                            <span class="text-2xl font-extrabold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent"><?php echo htmlspecialchars($counter['counter_number']); ?></span>
                        </div>
                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold <?php echo $badgeCls; ?> shadow-lg">
                            <span class="w-1.5 h-1.5 bg-white rounded-full mr-2 animate-pulse"></span>
                            <?php echo ucfirst($counter['current_status']); ?>
                        </span>
                    </div>

                    <?php if ($counter['counter_name']): ?>
                    <p class="text-sm font-bold text-gray-800 mb-3 truncate"><?php echo htmlspecialchars($counter['counter_name']); ?></p>
                    <?php endif; ?>

                    <div class="mb-4 bg-white/50 rounded-xl p-3">
                        <p class="text-xs text-gray-600 uppercase tracking-wide font-semibold mb-1">Staff Member</p>
                        <p class="text-sm font-bold text-gray-900">
                            <?php echo $counter['staff_name'] ? htmlspecialchars($counter['staff_name']) : '<span class="text-gray-400 italic">Unassigned</span>'; ?>
                        </p>
                    </div>

                    <?php if ($counter['current_token']): ?>
                        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-4 border-2 border-indigo-200 shadow-inner mb-4">
                            <span class="block text-xs text-indigo-600 uppercase font-bold mb-1">Current Token</span>
                            <span class="block text-3xl font-extrabold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent"><?php echo htmlspecialchars($counter['current_token']); ?></span>
                            <div class="text-sm text-gray-700 mt-1 font-medium truncate"><?php echo htmlspecialchars($counter['current_service'] ?? ''); ?></div>
                            <?php if ($counter['service_duration_minutes'] !== null): ?>
                            <div class="text-xs text-gray-400 mt-1"><?php echo (int)$counter['service_duration_minutes']; ?> min serving</div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-sm text-gray-400 italic text-center bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 mb-4">
                            No active token
                        </div>
                    <?php endif; ?>

                    <!-- Status Change Control -->
                    <div class="flex gap-2">
                        <select id="status-sel-<?php echo $counter['id']; ?>" class="flex-1 text-xs rounded-lg border border-gray-200 bg-white/80 px-2 py-2 font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            <option value="available" <?php echo $counter['current_status']==='available'?'selected':''; ?>>Available</option>
                            <option value="serving"   <?php echo $counter['current_status']==='serving'  ?'selected':''; ?>>Serving</option>
                            <option value="break"     <?php echo $counter['current_status']==='break'    ?'selected':''; ?>>Break</option>
                            <option value="closed"    <?php echo $counter['current_status']==='closed'   ?'selected':''; ?>>Closed</option>
                        </select>
                        <button onclick="setCounterStatus(<?php echo $counter['id']; ?>)" class="px-3 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-bold hover:opacity-90 transition shadow">Set</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="glassmorphic shadow-xl rounded-2xl overflow-hidden">
    <div class="px-6 py-5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center mr-4">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-white">Waiting Queue</h2>
                <p class="text-sm text-white/80">Next 20 customers in line</p>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Token Number</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Service</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Wait Time</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($waitingQueue)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center mb-4">
                                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-500 font-medium italic">Queue is currently empty</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($waitingQueue as $token): ?>
                        <tr class="hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 transition-all duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-extrabold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                    <?php echo htmlspecialchars($token['token_number']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($token['service_category']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-600">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <?php echo round((time() - strtotime($token['issued_at'])) / 60); ?> mins
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg bg-gradient-to-r from-yellow-400 to-orange-400 text-white shadow-md">
                                    Waiting
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// ── Counter Live Refresh ──────────────────────────────────────────────────────
const COUNTER_STATUS_CFG = {
    available: { bar: 'bg-gradient-to-r from-blue-400 to-indigo-500',   badge: 'bg-gradient-to-r from-blue-400 to-indigo-500 text-white' },
    serving:   { bar: 'bg-gradient-to-r from-green-400 to-emerald-500', badge: 'bg-gradient-to-r from-green-400 to-emerald-500 text-white' },
    break:     { bar: 'bg-gradient-to-r from-amber-400 to-orange-500',  badge: 'bg-gradient-to-r from-amber-400 to-orange-500 text-white' },
    closed:    { bar: 'bg-gradient-to-r from-gray-400 to-slate-500',    badge: 'bg-gradient-to-r from-gray-400 to-slate-500 text-white' },
};

function buildCounterCard(c, i) {
    const cfg   = COUNTER_STATUS_CFG[c.current_status] || COUNTER_STATUS_CFG.available;
    const label = c.current_status.charAt(0).toUpperCase() + c.current_status.slice(1);
    const opts  = ['available','serving','break','closed'].map(s =>
        `<option value="${s}"${c.current_status===s?' selected':''}>${s.charAt(0).toUpperCase()+s.slice(1)}</option>`
    ).join('');

    let tokenBlock;
    if (c.current_token) {
        const dur = c.service_duration_minutes != null ? `<div class="text-xs text-gray-400 mt-1">${c.service_duration_minutes} min serving</div>` : '';
        tokenBlock = `<div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-4 border-2 border-indigo-200 shadow-inner mb-4">
            <span class="block text-xs text-indigo-600 uppercase font-bold mb-1">Current Token</span>
            <span class="block text-3xl font-extrabold" style="background:linear-gradient(to right,#4f46e5,#9333ea);-webkit-background-clip:text;-webkit-text-fill-color:transparent">${c.current_token}</span>
            <div class="text-sm text-gray-700 mt-1 font-medium truncate">${c.current_service||''}</div>${dur}
        </div>`;
    } else {
        tokenBlock = `<div class="p-4 text-sm text-gray-400 italic text-center bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 mb-4">No active token</div>`;
    }

    const nameLine = c.counter_name ? `<p class="text-sm font-bold text-gray-800 mb-3 truncate">${c.counter_name}</p>` : '';
    const staffVal = c.staff_name || '<span class="text-gray-400 italic">Unassigned</span>';

    return `<div class="counter-card glassmorphic rounded-2xl shadow-xl hover:shadow-2xl overflow-hidden relative" style="animation-delay:${i*0.1}s">
        <div class="absolute left-0 top-0 right-0 h-1 ${cfg.bar}"></div>
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                    <span class="text-2xl font-extrabold" style="background:linear-gradient(to right,#4f46e5,#9333ea);-webkit-background-clip:text;-webkit-text-fill-color:transparent">${c.counter_number}</span>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold ${cfg.badge} shadow-lg">
                    <span class="w-1.5 h-1.5 bg-white rounded-full mr-2 animate-pulse"></span>${label}
                </span>
            </div>
            ${nameLine}
            <div class="mb-4 bg-white/50 rounded-xl p-3">
                <p class="text-xs text-gray-600 uppercase tracking-wide font-semibold mb-1">Staff Member</p>
                <p class="text-sm font-bold text-gray-900">${staffVal}</p>
            </div>
            ${tokenBlock}
            <div class="flex gap-2">
                <select id="status-sel-${c.id}" class="flex-1 text-xs rounded-lg border border-gray-200 bg-white/80 px-2 py-2 font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">${opts}</select>
                <button onclick="setCounterStatus(${c.id})" class="px-3 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-bold hover:opacity-90 transition shadow">Set</button>
            </div>
        </div>
    </div>`;
}

async function refreshCounterGrid() {
    try {
        const res  = await fetch(`${BASE_URL}/api/get-services.php?type=counter_status`);
        const data = await res.json();
        if (data.success && Array.isArray(data.data)) {
            const grid = document.getElementById('counterGrid');
            if (grid) grid.innerHTML = data.data.map((c, i) => buildCounterCard(c, i)).join('');
        }
    } catch(e) { /* silently ignore */ }
}

async function setCounterStatus(counterId) {
    const sel = document.getElementById(`status-sel-${counterId}`);
    if (!sel) return;
    const btn = sel.nextElementSibling;
    const origText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '…';
    try {
        const res  = await fetch(`${BASE_URL}/api/counter-operations.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update_counter_status', counter_id: counterId, status: sel.value })
        });
        const data = await res.json();
        if (data.success) {
            await refreshCounterGrid();
        } else {
            alert('Failed: ' + (data.message || 'Unknown error'));
            btn.disabled = false;
            btn.textContent = origText;
        }
    } catch(e) {
        alert('Request failed');
        btn.disabled = false;
        btn.textContent = origText;
    }
}

setInterval(refreshCounterGrid, 5000);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>