<?php
/**
 * Live Schedule Board - Public, Customer-Facing
 * Shows today's departures with status, capacity, and countdown
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/PortManager.php';

$portManager   = new PortManager();
$today         = date('Y-m-d');
$schedules     = $portManager->getTodaySchedules($today);
$announcements = $portManager->getActiveAnnouncements('customer');

$statusConfig = [
    'on_time'   => ['label'=>'On Time',   'bg'=>'bg-green-500',  'text'=>'text-green-700',  'border'=>'border-green-200',  'icon'=>'&#x2705;'],
    'boarding'  => ['label'=>'Boarding',  'bg'=>'bg-blue-500',   'text'=>'text-blue-700',   'border'=>'border-blue-200',   'icon'=>'&#x1F6A2;'],
    'delayed'   => ['label'=>'Delayed',   'bg'=>'bg-yellow-500', 'text'=>'text-yellow-700', 'border'=>'border-yellow-200', 'icon'=>'&#x23F1;'],
    'cancelled' => ['label'=>'Cancelled', 'bg'=>'bg-red-500',    'text'=>'text-red-700',    'border'=>'border-red-200',    'icon'=>'&#x274C;'],
    'departed'  => ['label'=>'Departed',  'bg'=>'bg-gray-500',   'text'=>'text-gray-500',   'border'=>'border-gray-200',   'icon'=>'&#x1F6E5;'],
];
$typeConfig = [
    'info'    => ['banner'=>'bg-blue-50 border-blue-300 text-blue-800',    'icon'=>'&#x2139;'],
    'warning' => ['banner'=>'bg-yellow-50 border-yellow-300 text-yellow-800','icon'=>'&#x26A0;'],
    'danger'  => ['banner'=>'bg-red-50 border-red-300 text-red-800',       'icon'=>'&#x1F6A8;'],
    'success' => ['banner'=>'bg-green-50 border-green-300 text-green-800', 'icon'=>'&#x2705;'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Schedules &mdash; <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-badge{0%,100%{opacity:1}50%{opacity:.55}}
        .boarding-pulse{animation:pulse-badge 1.4s ease-in-out infinite}
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 min-h-screen">
<div class="container mx-auto px-4 py-8 max-w-4xl">

    <!-- Header -->
    <header class="text-center mb-6">
        <div class="text-5xl mb-2">&#x1F6E5;</div>
        <h1 class="text-4xl font-bold text-white mb-1"><?php echo APP_NAME; ?></h1>
        <p class="text-white/80 text-lg">Live Departure Schedule</p>
        <p class="text-white/60 text-sm mt-1" id="live-clock"></p>
    </header>

    <!-- Announcements -->
    <div id="announcements-container">
    <?php foreach ($announcements as $ann):
        $tc = $typeConfig[$ann['type']] ?? $typeConfig['info']; ?>
    <div class="mb-4 px-4 py-3 rounded-xl border <?php echo $tc['banner']; ?> flex items-start gap-3 shadow-sm">
        <span class="text-xl"><?php echo $tc['icon']; ?></span>
        <div>
            <p class="font-bold"><?php echo htmlspecialchars($ann['title']); ?></p>
            <p class="text-sm mt-0.5"><?php echo nl2br(htmlspecialchars($ann['body'])); ?></p>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Date bar -->
    <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-6 py-3 mb-6 flex items-center justify-between">
        <span class="text-white font-semibold text-sm">&#x1F4C5; <?php echo date('l, F j, Y', strtotime($today)); ?></span>
        <a href="<?php echo BASE_URL; ?>/customer/" class="bg-white text-indigo-700 text-sm font-bold px-4 py-2 rounded-xl hover:bg-indigo-50 transition">&#x1F3AB; Get Token</a>
    </div>

    <!-- Schedule Cards -->
    <?php if (empty($schedules)): ?>
    <div class="bg-white rounded-3xl shadow-xl p-12 text-center">
        <div class="text-6xl mb-4">&#x1F6AB;</div>
        <h2 class="text-2xl font-bold text-gray-700">No Trips Today</h2>
        <p class="text-gray-500 mt-2">There are no scheduled departures for today.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
    <?php foreach ($schedules as $s):
        $cfg        = $statusConfig[$s['trip_status'] ?? 'on_time'] ?? $statusConfig['on_time'];
        $deptStr    = date('H:i', strtotime($s['departure_time']));
        $todayDept  = strtotime(date('Y-m-d') . ' ' . $s['departure_time']);
        $capTotal   = $s['capacity_per_trip'] ?? null;
        $capBooked  = (int)($s['passengers_booked'] ?? 0);
        $capPct     = ($capTotal > 0) ? min(100, round(($capBooked / $capTotal) * 100)) : 0;
        $fullyBooked = ($capTotal > 0 && $capBooked >= $capTotal);
        $departed   = $s['trip_status'] === 'departed';
    ?>
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border-2 <?php echo $cfg['border']; ?> <?php echo $departed ? 'opacity-55' : ''; ?>">
        <div class="h-2 <?php echo $cfg['bg']; ?> <?php echo $s['trip_status']==='boarding' ? 'boarding-pulse' : ''; ?>"></div>
        <div class="p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <span class="<?php echo $cfg['bg']; ?> text-white text-xs font-bold px-2.5 py-0.5 rounded-full <?php echo $s['trip_status']==='boarding' ? 'boarding-pulse' : ''; ?>">
                            <?php echo $cfg['icon']; ?> <?php echo $cfg['label']; ?>
                        </span>
                        <?php if ($fullyBooked): ?><span class="bg-red-100 text-red-700 text-xs font-bold px-2.5 py-0.5 rounded-full">FULL</span><?php endif; ?>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 truncate">
                        <?php echo htmlspecialchars($s['origin']); ?> &rarr; <?php echo htmlspecialchars($s['destination']); ?>
                    </h3>
                    <p class="text-gray-500 text-sm mt-0.5">
                        &#x1F6A2; <?php echo htmlspecialchars($s['vessel_name'] ?? 'TBA'); ?>
                        <?php if (!empty($s['schedule_name'])): ?> &bull; <?php echo htmlspecialchars($s['schedule_name']); ?><?php endif; ?>
                    </p>
                    <?php if (!empty($s['delay_reason']) && in_array($s['trip_status'], ['delayed','cancelled'])): ?>
                    <p class="text-sm mt-1 font-medium <?php echo $cfg['text']; ?>">&#x2139; <?php echo htmlspecialchars($s['delay_reason']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-3xl font-black text-gray-900"><?php echo $deptStr; ?></p>
                    <p class="text-xs text-gray-400 mb-1">Departure</p>
                    <?php if (!$departed && $s['trip_status'] !== 'cancelled'): ?>
                    <p class="countdown text-sm font-bold <?php echo $cfg['text']; ?>" data-dept="<?php echo $todayDept; ?>">-</p>
                    <?php endif; ?>
                    <?php if ($s['fare'] > 0): ?>
                    <p class="text-sm font-bold text-indigo-700 mt-1">&#x20B1;<?php echo number_format($s['fare'], 2); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($capTotal): ?>
            <div class="mt-3">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Capacity</span>
                    <span><?php echo $capBooked; ?>/<?php echo $capTotal; ?> booked</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div class="h-2.5 rounded-full transition-all <?php echo $capPct >= 90 ? 'bg-red-500' : ($capPct >= 65 ? 'bg-yellow-400' : 'bg-green-500'); ?>"
                         style="width:<?php echo $capPct; ?>%"></div>
                </div>
                <p class="text-xs mt-1 <?php echo $fullyBooked ? 'text-red-600 font-bold' : 'text-gray-400'; ?>">
                    <?php echo $fullyBooked ? '&#x26A0; Fully Booked' : max(0,$capTotal-$capBooked).' seats remaining'; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="text-center mt-8 text-white/50 text-xs">
        &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> | Developed by Ymath &mdash; Auto-refreshes every 60s
    </div>
</div>
<script>
function tick(){
    const n=new Date();
    document.getElementById('live-clock').textContent=
        n.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit',second:'2-digit'})+
        ' \u2014 '+n.toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
}
tick();setInterval(tick,1000);

// Poll announcements every 10s so deletions appear quickly
const BASE_URL_SCHED = '<?php echo BASE_URL; ?>';
const typeColors = {
    info:    'bg-blue-50 border-blue-300 text-blue-800',
    warning: 'bg-yellow-50 border-yellow-300 text-yellow-800',
    danger:  'bg-red-50 border-red-300 text-red-800',
    success: 'bg-green-50 border-green-300 text-green-800'
};
const typeIcons = {info:'\u2139',warning:'\u26A0',danger:'\uD83D\uDEA8',success:'\u2705'};
async function refreshAnnouncements(){
    try{
        const res = await fetch(`${BASE_URL_SCHED}/api/queue-status.php?type=announcements&location=customer`);
        const result = await res.json();
        const container = document.getElementById('announcements-container');
        if(!container) return;
        if(result.success && result.data && result.data.length > 0){
            container.innerHTML = result.data.map(a=>{
                const cls = typeColors[a.type] || typeColors.info;
                const icon = typeIcons[a.type] || typeIcons.info;
                const body = (a.body||'').replace(/\n/g,'<br>');
                return `<div class="mb-4 px-4 py-3 rounded-xl border ${cls} flex items-start gap-3 shadow-sm"><span class="text-xl">${icon}</span><div><p class="font-bold">${a.title}</p><p class="text-sm mt-0.5">${body}</p></div></div>`;
            }).join('');
        } else {
            container.innerHTML = '';
        }
    }catch(e){}
}
setInterval(refreshAnnouncements, 10000);

function tickCD(){
    const now=Math.floor(Date.now()/1000);
    document.querySelectorAll('.countdown').forEach(el=>{
        const dept=parseInt(el.dataset.dept);
        const diff=dept-now;
        if(diff<=0){el.textContent='Departed';return;}
        const h=Math.floor(diff/3600),m=Math.floor((diff%3600)/60),s=diff%60;
        el.textContent=h>0?`Departs in ${h}h ${m}m`:`Departs in ${m}m ${s}s`;
    });
}
tickCD();setInterval(tickCD,1000);
setTimeout(()=>location.reload(),30000);
</script>
</body>
</html>
