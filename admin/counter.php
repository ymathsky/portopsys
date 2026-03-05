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

<!-- Modern Background with Gradient -->
<div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
    <div class="absolute top-0 left-1/4 w-96 h-96 bg-gradient-to-br from-indigo-400 to-purple-400 rounded-full opacity-10 blur-3xl animate-pulse"></div>
    <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-gradient-to-tl from-pink-400 to-purple-400 rounded-full opacity-10 blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
    <div class="absolute top-1/2 left-0 w-64 h-64 bg-gradient-to-r from-blue-400 to-indigo-400 rounded-full opacity-5 blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
</div>

<style>
    @keyframes slideInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulse-glow {
        0%, 100% { box-shadow: 0 0 20px rgba(99, 102, 241, 0.3); }
        50% { box-shadow: 0 0 30px rgba(99, 102, 241, 0.6); }
    }
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    .animate-slide-in { animation: slideInUp 0.5s ease-out forwards; }
    .counter-card { 
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .counter-card:hover { 
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(99, 102, 241, 0.1);
    }
    .counter-card.active { 
        background: linear-gradient(145deg, #eef2ff 0%, #e0e7ff 100%);
        animation: pulse-glow 2s ease-in-out infinite;
    }
    .glass-effect {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
</style>

<div class="w-full px-4 py-6 max-w-7xl mx-auto">
    <?php if ($userRole !== 'counter_staff' || !$assignedCounterId): ?>
        <div class="mb-12 animate-slide-in">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-3xl font-extrabold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent mb-2">
                        Select Counter Station
                    </h2>
                    <p class="text-gray-600 text-sm">Choose a counter to begin managing queue operations</p>
                </div>
                <div class="flex items-center gap-3 px-4 py-2 bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-sm font-medium text-gray-700"><?php echo count($counterStatus); ?> Counters Online</span>
                </div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($counterStatus as $index => $counter): ?>
                    <a href="?counter_id=<?php echo $counter['id']; ?>" 
                       class="counter-card <?php echo ($selectedCounterId == $counter['id']) ? 'active' : ''; ?> group relative rounded-2xl p-6 border-2 <?php echo ($selectedCounterId == $counter['id']) ? 'border-indigo-400' : 'border-gray-200 hover:border-indigo-300'; ?> overflow-hidden"
                       style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        
                        <!-- Decorative Background -->
                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full opacity-20 -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"></div>
                        
                        <div class="relative flex flex-col items-center text-center">
                            <!-- Icon with Gradient Background -->
                            <div class="w-20 h-20 mb-4 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center text-3xl shadow-lg transform group-hover:rotate-6 transition-transform duration-300">
                                <span class="filter drop-shadow-md">🖥️</span>
                            </div>
                            
                            <!-- Counter Info -->
                            <h3 class="font-extrabold text-gray-900 text-xl mb-1 group-hover:text-indigo-600 transition-colors">
                                <?php echo htmlspecialchars($counter['counter_number']); ?>
                            </h3>
                            <p class="text-sm text-gray-600 font-medium mb-3"><?php echo htmlspecialchars($counter['counter_name']); ?></p>
                            
                            <!-- Status Badge with Enhanced Styling -->
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold shadow-md transform group-hover:scale-105 transition-transform
                                <?php 
                                echo match($counter['current_status']) {
                                    'available' => 'bg-gradient-to-r from-green-400 to-emerald-500 text-white',
                                    'serving' => 'bg-gradient-to-r from-blue-400 to-indigo-500 text-white',
                                    'break' => 'bg-gradient-to-r from-yellow-400 to-orange-500 text-white',
                                    'closed' => 'bg-gradient-to-r from-gray-400 to-gray-500 text-white',
                                    default => 'bg-gradient-to-r from-gray-400 to-gray-500 text-white'
                                };
                                ?>">
                                <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                                <?php echo ucfirst($counter['current_status']); ?>
                            </span>
                        </div>
                        
                        <!-- Selection Indicator -->
                        <?php if ($selectedCounterId == $counter['id']): ?>
                            <div class="absolute top-3 right-3 w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg animate-bounce">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Hover Glow Effect -->
                        <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-gradient-to-r from-indigo-500/5 to-purple-500/5"></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($selectedCounterId): ?>
        <div id="counter-operations" data-counter-id="<?php echo $selectedCounterId; ?>" class="animate-slide-in">
            <?php
            $currentCounter = array_filter($counterStatus, fn($c) => $c['id'] == $selectedCounterId);
            $currentCounter = reset($currentCounter);
            ?>
            
            <!-- Counter Header with Modern Design -->
            <div class="glass-effect rounded-3xl shadow-2xl border border-gray-200/50 p-8 mb-8 backdrop-blur-xl relative overflow-hidden">
                <!-- Decorative background elements -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-indigo-200 to-purple-200 rounded-full opacity-20 -mr-32 -mt-32"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-gradient-to-tr from-pink-200 to-purple-200 rounded-full opacity-20 -ml-24 -mb-24"></div>
                
                <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="flex items-center gap-5">
                        <div class="w-20 h-20 bg-gradient-to-br from-indigo-500 via-purple-600 to-pink-500 rounded-2xl flex items-center justify-center text-white text-3xl shadow-2xl transform hover:rotate-6 transition-transform duration-300">
                            🖥️
                        </div>
                        <div>
                            <h2 class="text-3xl font-extrabold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent flex items-center gap-3">
                                <?php echo htmlspecialchars($currentCounter['counter_number'] ?? 'Unknown'); ?>
                            </h2>
                            <p class="text-gray-600 mt-1 font-medium"><?php echo htmlspecialchars($currentCounter['counter_name'] ?? ''); ?></p>
                            <div class="flex items-center gap-2 mt-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-xs text-gray-500 font-medium">Active Session</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <select id="counterStatusSelect" class="appearance-none pl-4 pr-10 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-700 font-semibold shadow-sm hover:shadow-md transition-all cursor-pointer">
                                <option value="available" <?php echo ($currentCounter['current_status'] === 'available') ? 'selected' : ''; ?>>🟢 Available</option>
                                <option value="serving" <?php echo ($currentCounter['current_status'] === 'serving') ? 'selected' : ''; ?>>🔵 Serving</option>
                                <option value="break" <?php echo ($currentCounter['current_status'] === 'break') ? 'selected' : ''; ?>>🟡 On Break</option>
                                <option value="closed" <?php echo ($currentCounter['current_status'] === 'closed') ? 'selected' : ''; ?>>⚫ Closed</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        <button id="updateStatusBtn" class="px-8 py-3 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white rounded-xl hover:from-indigo-700 hover:via-purple-700 hover:to-pink-700 transition-all duration-300 font-bold shadow-lg hover:shadow-2xl transform hover:scale-105 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Update Status
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Current Token Display - Ultra Modern -->
            <div id="currentTokenDisplay" class="glass-effect rounded-3xl shadow-2xl border border-gray-200/50 p-10 mb-8 backdrop-blur-xl relative overflow-hidden">
                <!-- Animated Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 opacity-40"></div>
                <div class="absolute top-0 left-0 w-96 h-96 bg-gradient-to-br from-indigo-300 to-purple-300 rounded-full opacity-10 -ml-48 -mt-48 animate-pulse"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 bg-gradient-to-tl from-pink-300 to-purple-300 rounded-full opacity-10 -mr-48 -mb-48 animate-pulse" style="animation-delay: 1s;"></div>
                
                <?php
                $currentToken = array_filter($serving, fn($t) => $t['counter_id'] == $selectedCounterId);
                $currentToken = reset($currentToken);
                
                if ($currentToken):
                ?>
                    <div class="relative text-center">
                        <!-- Status Badge -->
                        <div class="inline-flex items-center gap-2 px-6 py-2 bg-gradient-to-r from-green-400 to-emerald-500 text-white rounded-full text-sm font-bold mb-6 shadow-lg animate-pulse">
                            <span class="w-3 h-3 bg-white rounded-full animate-ping absolute"></span>
                            <span class="w-3 h-3 bg-white rounded-full"></span>
                            <span>NOW SERVING</span>
                        </div>
                        
                        <!-- Massive Token Number -->
                        <div class="my-10">
                            <div class="relative inline-block">
                                <div class="text-8xl md:text-9xl font-black bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent drop-shadow-2xl mb-6 tracking-tight" style="animation: float 3s ease-in-out infinite;">
                                    <?php echo htmlspecialchars($currentToken['token_number']); ?>
                                </div>
                                <div class="absolute -inset-4 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-3xl opacity-20 blur-2xl -z-10"></div>
                            </div>
                            <div class="mt-6 p-4 bg-white/60 backdrop-blur-sm rounded-2xl inline-block shadow-lg border border-gray-200/50">
                                <p class="text-2xl text-gray-700 font-bold"><?php echo htmlspecialchars($currentToken['service_category']); ?></p>
                            </div>
                        </div>
                        
                        <!-- Customer Info Cards -->
                        <div class="flex justify-center gap-4 mb-8 flex-wrap">
                            <?php if ($currentToken['customer_name']): ?>
                                <div class="flex items-center gap-3 px-6 py-3 bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 hover:shadow-xl transition-shadow">
                                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <p class="text-xs text-gray-500 font-medium">Customer</p>
                                        <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($currentToken['customer_name']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="flex items-center gap-3 px-6 py-3 bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 hover:shadow-xl transition-shadow">
                                <span class="px-4 py-2 rounded-xl text-sm font-bold shadow-md <?php 
                                echo match($currentToken['priority_type']) {
                                    'emergency' => 'bg-gradient-to-r from-red-400 to-red-600 text-white',
                                    'hazmat' => 'bg-gradient-to-r from-orange-400 to-orange-600 text-white',
                                    'perishable' => 'bg-gradient-to-r from-yellow-400 to-yellow-600 text-white',
                                    'urgent' => 'bg-gradient-to-r from-purple-400 to-purple-600 text-white',
                                    'express' => 'bg-gradient-to-r from-blue-400 to-blue-600 text-white',
                                    'senior' => 'bg-gradient-to-r from-amber-400 to-orange-500 text-white',
                                    'pwd' => 'bg-gradient-to-r from-blue-400 to-blue-600 text-white',
                                    'pregnant' => 'bg-gradient-to-r from-pink-400 to-rose-500 text-white',
                                    'student' => 'bg-gradient-to-r from-green-400 to-emerald-600 text-white',
                                    default => 'bg-gradient-to-r from-gray-400 to-gray-600 text-white'
                                };
                                ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $currentToken['priority_type'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex justify-center gap-4 flex-wrap">
                            <?php if ($currentToken['status'] === 'called'): ?>
                                <button onclick="startServing(<?php echo $currentToken['id']; ?>)" class="group px-10 py-4 bg-gradient-to-r from-green-500 via-emerald-500 to-teal-500 text-white rounded-2xl hover:from-green-600 hover:via-emerald-600 hover:to-teal-600 transition-all duration-300 font-bold shadow-2xl hover:shadow-green-500/50 transform hover:scale-105 flex items-center gap-3">
                                    <svg class="w-6 h-6 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Start Service
                                </button>
                                <!-- Recall: re-announce the same token -->
                                <button onclick="recallCurrentToken(<?php echo $currentToken['id']; ?>)" class="group px-8 py-4 bg-gradient-to-r from-violet-500 to-purple-600 text-white rounded-2xl hover:from-violet-600 hover:to-purple-700 transition-all duration-300 font-bold shadow-2xl hover:shadow-purple-500/50 transform hover:scale-105 flex items-center gap-3" title="Re-announce this token number">
                                    <svg class="w-6 h-6 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                    </svg>
                                    Recall
                                    <?php if ($currentToken['recall_count'] > 0): ?>
                                        <span class="bg-white/30 text-white text-xs font-black px-2 py-0.5 rounded-full"><?php echo $currentToken['recall_count']; ?>x</span>
                                    <?php endif; ?>
                                </button>
                            <?php elseif ($currentToken['status'] === 'serving'): ?>
                                <button onclick="completeToken(<?php echo $currentToken['id']; ?>)" class="group px-10 py-4 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 text-white rounded-2xl hover:from-blue-600 hover:via-indigo-600 hover:to-purple-600 transition-all duration-300 font-bold shadow-2xl hover:shadow-indigo-500/50 transform hover:scale-105 flex items-center gap-3">
                                    <svg class="w-6 h-6 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Complete Service
                                </button>
                            <?php endif; ?>
                            <!-- Transfer: move this token to another counter -->
                            <button onclick="openTransferModal(<?php echo $currentToken['id']; ?>, '<?php echo htmlspecialchars($currentToken['token_number'], ENT_QUOTES); ?>')" class="group px-8 py-4 bg-gradient-to-r from-cyan-500 to-teal-600 text-white rounded-2xl hover:from-cyan-600 hover:to-teal-700 transition-all duration-300 font-bold shadow-2xl hover:shadow-cyan-500/50 transform hover:scale-105 flex items-center gap-3" title="Transfer to another counter">
                                <svg class="w-6 h-6 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                Transfer
                            </button>
                            <button onclick="markNoShow(<?php echo $currentToken['id']; ?>)" class="group px-10 py-4 bg-gradient-to-r from-yellow-400 to-orange-500 text-white rounded-2xl hover:from-yellow-500 hover:to-orange-600 transition-all duration-300 font-bold shadow-2xl hover:shadow-yellow-500/50 transform hover:scale-105 flex items-center gap-3">
                                <svg class="w-6 h-6 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Mark No Show
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="relative text-center py-16">
                        <div class="inline-block mb-6" style="animation: float 3s ease-in-out infinite;">
                            <div class="text-8xl mb-4 filter drop-shadow-lg">🎫</div>
                        </div>
                        <p class="text-2xl text-gray-700 font-bold mb-10">No token currently assigned</p>
                        <div class="flex justify-center gap-4 flex-wrap">
                            <button onclick="callNextToken()" class="group px-12 py-5 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white rounded-2xl hover:from-indigo-700 hover:via-purple-700 hover:to-pink-700 transition-all duration-300 font-extrabold shadow-2xl hover:shadow-indigo-500/50 transform hover:scale-110 text-xl flex items-center gap-4">
                                <span class="text-3xl animate-pulse">📢</span>
                                <span>Call Next Token</span>
                                <svg class="w-6 h-6 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                            <button onclick="openQRScanner()" class="group px-12 py-5 bg-gradient-to-r from-green-500 via-emerald-500 to-teal-500 text-white rounded-2xl hover:from-green-600 hover:via-emerald-600 hover:to-teal-600 transition-all duration-300 font-extrabold shadow-2xl hover:shadow-green-500/50 transform hover:scale-110 text-xl flex items-center gap-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                </svg>
                                <span>Scan QR Code</span>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Queue Overview - Enhanced Modern Design -->
            <div class="glass-effect rounded-3xl shadow-2xl border border-gray-200/50 p-8 backdrop-blur-xl relative overflow-hidden">
                <!-- Decorative Background -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-bl from-indigo-200 to-purple-200 rounded-full opacity-10 -mr-32 -mt-32"></div>
                
                <div class="relative">
                    <h3 class="text-2xl font-extrabold text-gray-900 mb-6 flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Next in Queue</span>
                    </h3>
                    <div id="upcomingTokens" class="space-y-4">
                        <div class="text-center text-gray-500 py-8">
                            <div class="inline-block w-12 h-12 border-4 border-gray-300 border-t-indigo-600 rounded-full animate-spin mb-4"></div>
                            <p class="font-medium">Loading queue...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Real-time Vessel Capacity ─────────────────────────────── -->
        <div class="glass-effect rounded-3xl shadow-2xl border border-gray-200/50 p-8 backdrop-blur-xl relative overflow-hidden mt-6">
            <div class="absolute top-0 right-0 w-48 h-48 bg-gradient-to-bl from-teal-200 to-cyan-200 rounded-full opacity-10 -mr-24 -mt-24"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-extrabold text-gray-900 flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <span class="bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-transparent">Vessel Capacity — Today</span>
                    </h3>
                    <span id="capacityLastUpdate" class="text-xs text-gray-400 font-medium"></span>
                </div>
                <div id="capacityGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div class="text-center text-gray-500 py-8 col-span-full">
                        <div class="inline-block w-10 h-10 border-4 border-gray-300 border-t-teal-500 rounded-full animate-spin mb-3"></div>
                        <p class="font-medium">Loading capacity…</p>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="glass-effect rounded-3xl shadow-2xl border border-gray-200/50 p-16 text-center backdrop-blur-xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 opacity-50"></div>
            <div class="relative" style="animation: float 3s ease-in-out infinite;">
                <div class="text-9xl mb-6 filter drop-shadow-2xl">🖥️</div>
            </div>
            <p class="relative text-2xl text-gray-700 font-bold">Please select a counter to manage</p>
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
    fetch(`${BASE_URL}/api/queue-status.php?type=waiting&limit=5`)
        .then(res => res.json())
        .then(data => {
            if (data.success) updateUpcomingTokens(data.data);
        });

    loadCapacityStatus();
}

function loadCapacityStatus() {
    fetch(`${BASE_URL}/api/capacity-status.php`)
        .then(res => res.json())
        .then(data => {
            if (data.success) renderCapacity(data.data);
        })
        .catch(() => {});
}

function renderCapacity(vessels) {
    const grid = document.getElementById('capacityGrid');
    if (!grid) return;

    document.getElementById('capacityLastUpdate').textContent =
        'Updated ' + new Date().toLocaleTimeString('en-PH', {hour:'2-digit', minute:'2-digit', second:'2-digit'});

    if (!vessels || vessels.length === 0) {
        grid.innerHTML = `<div class="col-span-full text-center py-8 text-gray-400">
            <div class="text-4xl mb-2">🚢</div><p class="font-medium">No active vessels for today</p></div>`;
        return;
    }

    grid.innerHTML = vessels.map(v => {
        const unlimited = v.max_capacity <= 0;
        const pct       = unlimited ? 0 : v.pct;
        const barColor  = v.status === 'full'        ? 'from-red-500 to-red-600'
                        : v.status === 'almost_full' ? 'from-orange-400 to-amber-500'
                        : 'from-teal-400 to-cyan-500';
        const badgeCls  = v.status === 'full'        ? 'bg-red-100 text-red-700'
                        : v.status === 'almost_full' ? 'bg-orange-100 text-orange-700'
                        : v.status === 'unlimited'   ? 'bg-gray-100 text-gray-600'
                        : 'bg-teal-100 text-teal-700';
        const badgeLabel= v.status === 'full'        ? '🔴 FULL'
                        : v.status === 'almost_full' ? '🟠 Almost Full'
                        : v.status === 'unlimited'   ? '∞ Unlimited'
                        : '🟢 Available';

        return `
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-900 truncate text-base">${v.name}</p>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">${v.type}</p>
                </div>
                <span class="ml-2 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap ${badgeCls}">${badgeLabel}</span>
            </div>

            ${!unlimited ? `
            <div class="mb-3">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>${v.occupied} occupied</span>
                    <span>${v.remaining} remaining / ${v.max_capacity}</span>
                </div>
                <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r ${barColor} rounded-full transition-all duration-700"
                         style="width:${pct}%"></div>
                </div>
                <p class="text-right text-xs font-bold mt-1 ${
                    v.status==='full' ? 'text-red-600' : v.status==='almost_full' ? 'text-orange-600' : 'text-teal-600'
                }">${pct}% filled</p>
            </div>` : ''}

            <div class="grid grid-cols-3 gap-2 text-center mt-2">
                <div class="bg-blue-50 rounded-xl p-2">
                    <p class="text-lg font-black text-blue-600">${v.boarded}</p>
                    <p class="text-xs text-blue-500 font-medium">Boarded</p>
                </div>
                <div class="bg-amber-50 rounded-xl p-2">
                    <p class="text-lg font-black text-amber-600">${v.in_service}</p>
                    <p class="text-xs text-amber-500 font-medium">Serving</p>
                </div>
                <div class="bg-purple-50 rounded-xl p-2">
                    <p class="text-lg font-black text-purple-600">${v.waiting}</p>
                    <p class="text-xs text-purple-500 font-medium">Waiting</p>
                </div>
            </div>
        </div>`;
    }).join('');
}

function updateUpcomingTokens(tokens) {
    const container = document.getElementById('upcomingTokens');
    if (!tokens || tokens.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <div class="text-6xl mb-4 opacity-50">📭</div>
                <p class="text-gray-500 font-medium">No tokens waiting in queue</p>
            </div>
        `;
        return;
    }
    
    const PRIORITY_BADGE = {
        emergency : { cls: 'bg-gradient-to-r from-red-500 to-red-700 text-white animate-pulse', icon: '🚨' },
        urgent    : { cls: 'bg-gradient-to-r from-orange-500 to-red-500 text-white', icon: '⚡' },
        senior    : { cls: 'bg-gradient-to-r from-amber-400 to-orange-500 text-white', icon: '👴' },
        pwd       : { cls: 'bg-gradient-to-r from-blue-400 to-blue-600 text-white', icon: '♿' },
        pregnant  : { cls: 'bg-gradient-to-r from-pink-400 to-rose-500 text-white', icon: '🤰' },
        student   : { cls: 'bg-gradient-to-r from-green-400 to-emerald-600 text-white', icon: '🎓' },
        hazmat    : { cls: 'bg-gradient-to-r from-orange-400 to-orange-600 text-white', icon: '☢️' },
        express   : { cls: 'bg-gradient-to-r from-cyan-400 to-blue-500 text-white', icon: '🚀' },
        regular   : { cls: 'bg-gradient-to-r from-gray-400 to-gray-600 text-white', icon: '👤' },
    };
    const isPriority = ['emergency','urgent','senior','pwd','pregnant'].includes;

    container.innerHTML = tokens.map((token, index) => {
        const pb    = PRIORITY_BADGE[token.priority_type] || PRIORITY_BADGE.regular;
        const isPri = ['emergency','urgent','senior','pwd','pregnant','student'].includes(token.priority_type);
        const rowBg = isPri
            ? 'from-amber-50 to-yellow-50 border-amber-200 hover:border-amber-400'
            : 'from-white to-gray-50 border-gray-200 hover:border-indigo-300';
        return `
        <div class="group flex items-center justify-between p-5 bg-gradient-to-r ${rowBg} rounded-2xl hover:from-indigo-50 hover:to-purple-50 transition-all duration-300 shadow-sm hover:shadow-xl border transform hover:scale-105" style="animation: slideInUp 0.5s ease-out ${index * 0.1}s both;">
            <div class="flex items-center gap-5">
                <div class="relative">
                    <div class="w-16 h-16 ${isPri ? 'bg-gradient-to-br from-amber-500 to-orange-600' : 'bg-gradient-to-br from-indigo-500 via-purple-600 to-pink-500'} rounded-2xl flex items-center justify-center text-white font-black text-lg shadow-lg transform group-hover:rotate-6 transition-transform duration-300">
                        ${token.queue_position}
                    </div>
                    <div class="absolute -top-1 -right-1 w-5 h-5 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full border-2 border-white shadow-md"></div>
                </div>
                <div>
                    <p class="font-black text-gray-900 text-xl mb-1">${token.token_number}</p>
                    <p class="text-sm text-gray-600 font-semibold flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        ${token.service_category}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1.5 rounded-xl text-xs font-bold shadow-md transform group-hover:scale-110 transition-transform ${pb.cls}">
                    ${pb.icon} ${token.priority_type.replace('_',' ').toUpperCase()}
                </span>
                <svg class="w-6 h-6 text-gray-400 group-hover:text-indigo-600 group-hover:translate-x-2 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </div>`;
    }).join('');
}

function callNextToken() {
    const button = event.target;
    button.disabled = true;
    button.innerHTML = '<svg class="animate-spin w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';
    
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
            showNotification('✅ ' + data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('❌ Error: ' + data.message, 'error');
            button.disabled = false;
            button.innerHTML = '<span class="text-3xl animate-pulse">📢</span><span>Call Next Token</span>';
        }
    })
    .catch(err => {
        showNotification('❌ Network error: ' + err, 'error');
        button.disabled = false;
        button.innerHTML = '<span class="text-3xl animate-pulse">📢</span><span>Call Next Token</span>';
    });
}

function startServing(tokenId) {
    const button = event.target.closest('button');
    button.disabled = true;
    const originalHTML = button.innerHTML;
    button.innerHTML = '<svg class="animate-spin w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';
    
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
            showNotification('✅ Service started successfully!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showNotification('❌ Error: ' + data.message, 'error');
            button.disabled = false;
            button.innerHTML = originalHTML;
        }
    })
    .catch(err => {
        showNotification('❌ Network error: ' + err, 'error');
        button.disabled = false;
        button.innerHTML = originalHTML;
    });
}

function completeToken(tokenId) {
    const notes = prompt('Add any notes (optional):');
    
    const button = event.target.closest('button');
    button.disabled = true;
    const originalHTML = button.innerHTML;
    button.innerHTML = '<svg class="animate-spin w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';
    
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
            showNotification('✅ Service completed successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('❌ Error: ' + data.message, 'error');
            button.disabled = false;
            button.innerHTML = originalHTML;
        }
    })
    .catch(err => {
        showNotification('❌ Network error: ' + err, 'error');
        button.disabled = false;
        button.innerHTML = originalHTML;
    });
}

function markNoShow(tokenId) {
    if (!confirm('⚠️ Mark this token as no-show?')) return;
    
    const button = event.target.closest('button');
    button.disabled = true;
    const originalHTML = button.innerHTML;
    button.innerHTML = '<svg class="animate-spin w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';
    
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
            showNotification('✅ Token marked as no-show', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showNotification('❌ Error: ' + data.message, 'error');
            button.disabled = false;
            button.innerHTML = originalHTML;
        }
    })
    .catch(err => {
        showNotification('❌ Network error: ' + err, 'error');
        button.disabled = false;
        button.innerHTML = originalHTML;
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-2xl shadow-2xl font-bold text-white transform transition-all duration-500 ${
        type === 'success' ? 'bg-gradient-to-r from-green-500 to-emerald-600' :
        type === 'error' ? 'bg-gradient-to-r from-red-500 to-red-600' :
        'bg-gradient-to-r from-blue-500 to-indigo-600'
    } animate-slide-in`;
    notification.style.animation = 'slideInUp 0.5s ease-out';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

document.getElementById('updateStatusBtn')?.addEventListener('click', function() {
    const status = document.getElementById('counterStatusSelect').value;
    const button = this;
    button.disabled = true;
    const originalHTML = button.innerHTML;
    button.innerHTML = '<svg class="animate-spin w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';
    
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
            showNotification('✅ Counter status updated successfully!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showNotification('❌ Error: ' + data.message, 'error');
            button.disabled = false;
            button.innerHTML = originalHTML;
        }
    })
    .catch(err => {
        showNotification('❌ Network error: ' + err, 'error');
        button.disabled = false;
        button.innerHTML = originalHTML;
    });
});

// QR Scanner Functions
let _qrCameras  = [];
let _qrCamIndex = 0;
let _qrScanner  = null;

function openQRScanner() {
    document.getElementById('qrScannerModal').classList.remove('hidden');
    document.getElementById('qr-switch-cam-btn').classList.add('hidden');
    document.getElementById('qr-reader').innerHTML = '';
    _qrScanner = new Html5Qrcode('qr-reader');

    Html5Qrcode.getCameras().then(cameras => {
        if (!cameras || cameras.length === 0) return;
        _qrCameras  = cameras;
        const backIdx = cameras.findIndex(c => /back|rear|environment/i.test(c.label));
        _qrCamIndex = backIdx >= 0 ? backIdx : cameras.length - 1;
        if (cameras.length > 1) document.getElementById('qr-switch-cam-btn').classList.remove('hidden');
        startQRCamera();
    }).catch(() => { startQRCamera(); });
}

function startQRCamera() {
    const camId = _qrCameras.length > 0 ? _qrCameras[_qrCamIndex].id : { facingMode: 'environment' };
    _qrScanner.start(
        camId,
        { fps: 10, qrbox: { width: 220, height: 220 } },
        (decodedText) => {
            _qrScanner.stop().catch(() => {});
            onScanSuccess(decodedText);
        },
        () => {}
    ).catch((err) => {
        // Fall back to file scanner
        const scanner = new Html5QrcodeScanner('qr-reader', { fps: 10, qrbox: 220 }, false);
        scanner.render(onScanSuccess, onScanError);
        window.html5QrcodeScanner = { clear: () => scanner.clear() };
    });
    window.html5QrcodeScanner = { clear: () => _qrScanner.stop().catch(() => {}) };
}

function switchQRCamera() {
    if (_qrCameras.length < 2) return;
    const btn = document.getElementById('qr-switch-cam-btn');
    btn.disabled = true;
    _qrScanner.stop().then(() => {
        _qrCamIndex = (_qrCamIndex + 1) % _qrCameras.length;
        startQRCamera();
        btn.disabled = false;
    }).catch(() => { btn.disabled = false; });
}

function closeQRScanner() {
    document.getElementById('qrScannerModal').classList.add('hidden');
    if (_qrScanner) _qrScanner.stop().catch(() => {});
    if (window.html5QrcodeScanner) window.html5QrcodeScanner.clear();
}

function startQRScanner() { openQRScanner(); } // legacy alias

function onScanSuccess(decodedText, decodedResult) {
    // Extract token number from URL or use directly
    let tokenNumber = decodedText;
    
    // If it's a URL, extract the token parameter
    if (decodedText.includes('token=')) {
        const urlParams = new URLSearchParams(decodedText.split('?')[1]);
        tokenNumber = urlParams.get('token');
    }
    
    if (tokenNumber) {
        closeQRScanner();
        callSpecificToken(tokenNumber);
    }
}

function onScanError(error) {
    // Ignore scanning errors (they happen continuously while scanning)
}

// ── RECALL ────────────────────────────────────────────────────────────────────
function recallCurrentToken(tokenId) {
    if (!confirm('📢 Re-announce this token number to the waiting area?')) return;

    const btn = event.target.closest('button');
    btn.disabled = true;
    const orig = btn.innerHTML;
    btn.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke-width="4"/></svg> Recalling...';

    fetch(`${BASE_URL}/api/counter-operations.php`, {
        method  : 'POST',
        headers : {'Content-Type':'application/json'},
        body    : JSON.stringify({action:'recall_token', token_id: tokenId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showNotification('📢 Token recalled — please announce!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showNotification('❌ ' + data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    })
    .catch(err => {
        showNotification('❌ Network error: ' + err, 'error');
        btn.disabled = false;
        btn.innerHTML = orig;
    });
}

// ── TRANSFER ──────────────────────────────────────────────────────────────────
let _transferTokenId = null;

function openTransferModal(tokenId, tokenNumber) {
    _transferTokenId = tokenId;
    document.getElementById('transferTokenLabel').textContent = tokenNumber;
    // Load active counters into select (exclude current)
    const sel = document.getElementById('transferCounterSelect');
    sel.innerHTML = '<option value="">Loading counters…</option>';

    fetch(`${BASE_URL}/api/get-services.php?type=counter_status`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            const counters = data.data.filter(c => c.id != counterId); // exclude current
            sel.innerHTML = '<option value="">— Select a counter —</option>';
            counters.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                const statusIcon = {available:'🟢', serving:'🔵', break:'🟡', closed:'⚫'}[c.current_status] || '⚪';
                opt.textContent = `${statusIcon} ${c.counter_number} — ${c.counter_name} (${c.current_status})`;
                sel.appendChild(opt);
            });
        })
        .catch(err => sel.innerHTML = '<option value="">Error: ' + err + '</option>');

    document.getElementById('transferModal').classList.remove('hidden');
}

function closeTransferModal() {
    document.getElementById('transferModal').classList.add('hidden');
    _transferTokenId = null;
}

function confirmTransfer() {
    const btn = document.getElementById('transferConfirmBtn');
    const newCounterId = document.getElementById('transferCounterSelect').value;
    if (!newCounterId) {
        alert('Please select a target counter');
        return;
    }

    btn.disabled = true;
    const orig = btn.innerHTML;
    btn.innerHTML = 'Transferring…';

    fetch(`${BASE_URL}/api/counter-operations.php`, {
        method  : 'POST',
        headers : {'Content-Type':'application/json'},
        body    : JSON.stringify({
            action          : 'transfer_token',
            token_id        : _transferTokenId,
            new_counter_id  : parseInt(newCounterId)
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeTransferModal();
            showNotification('✅ Token transferred successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('❌ ' + data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    })
    .catch(err => {
        showNotification('❌ Network error: ' + err, 'error');
        btn.disabled = false;
        btn.innerHTML = orig;
    });
}

function callSpecificToken(tokenNumber) {
    if (!counterId) {
        showNotification('❌ Please select a counter first', 'error');
        return;
    }
    
    const notification = showNotification('🔍 Looking for token ' + tokenNumber + '...', 'info');
    
    fetch(`${BASE_URL}/api/counter-operations.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'call_specific_token',
            counter_id: counterId,
            token_number: tokenNumber
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('✅ Token ' + tokenNumber + ' called successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('❌ ' + data.message, 'error');
        }
    })
    .catch(err => {
        showNotification('❌ Network error: ' + err, 'error');
    });
}

if (counterId) {
    startAutoRefresh();
    loadCounterData();
}

window.addEventListener('beforeunload', () => {
    if (refreshInterval) clearInterval(refreshInterval);
    if (window.html5QrcodeScanner) {
        window.html5QrcodeScanner.clear();
    }
});
</script>

<!-- Transfer Modal -->
<div id="transferModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-cyan-600 to-teal-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-2xl font-bold text-white flex items-center gap-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Transfer Token
            </h3>
            <button onclick="closeTransferModal()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6">
            <p class="text-gray-700 mb-4">Transfer token <strong id="transferTokenLabel" class="text-indigo-600 font-black text-xl"></strong> to another counter:</p>
            <select id="transferCounterSelect" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 mb-6 text-gray-700 font-medium">
                <option value="">Loading…</option>
            </select>
            <div class="flex gap-3">
                <button onclick="closeTransferModal()" class="flex-1 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-semibold transition-colors">Cancel</button>
                <button id="transferConfirmBtn" onclick="confirmTransfer()" class="flex-1 py-3 bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-700 hover:to-teal-700 text-white rounded-xl font-bold transition-all shadow-lg">
                    ✅ Confirm Transfer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QR Scanner Modal -->
<div id="qrScannerModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-2xl font-bold text-white flex items-center gap-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                Scan QR Code
            </h3>
            <button onclick="closeQRScanner()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <div id="qr-reader" class="w-full"></div>
            <div class="flex justify-center mt-3">
                <button id="qr-switch-cam-btn" onclick="switchQRCamera()" class="hidden items-center gap-2 px-4 py-2 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-xl text-sm font-semibold transition-colors">
                    🔄 Switch Camera
                </button>
            </div>
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-xl p-4">
                <p class="text-blue-800 text-sm"><strong>📱 Instructions:</strong> Position the QR code within the camera frame. The system will automatically scan and call the token.</p>
            </div>
        </div>
    </div>
</div>

<!-- Add Html5-QRCode Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
