<?php
/**
 * Admin Header Template
 */

if (!defined('APP_NAME')) {
    die('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin'; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Modern Sidebar Enhancements */
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .nav-item {
            animation: slideIn 0.3s ease-out forwards;
        }
        .nav-item:nth-child(1) { animation-delay: 0.05s; }
        .nav-item:nth-child(2) { animation-delay: 0.1s; }
        .nav-item:nth-child(3) { animation-delay: 0.15s; }
        .nav-item:nth-child(4) { animation-delay: 0.2s; }
        .nav-item:nth-child(5) { animation-delay: 0.25s; }
        .nav-item:nth-child(6) { animation-delay: 0.3s; }
        
        .sidebar-gradient {
            background: linear-gradient(180deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%);
        }
        
        .nav-icon-glow {
            filter: drop-shadow(0 2px 8px rgba(139, 92, 246, 0.3));
        }
        
        /* Compatibility Layers for Existing Pages using old classes */
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 500; font-size: 0.875rem; transition: background-color 0.2s; cursor: pointer; border: 1px solid transparent; }
        .btn-primary { background-color: #4f46e5; color: white; }
        .btn-primary:hover { background-color: #4338ca; }
        .btn-secondary { background-color: white; color: #374151; border-color: #d1d5db; }
        .btn-secondary:hover { background-color: #f9fafb; }
        .btn-danger { background-color: #ef4444; color: white; }
        .btn-danger:hover { background-color: #dc2626; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        
        .card, .section-card { background-color: white; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); padding: 1.5rem; margin-bottom: 1.5rem; }
        .table-responsive { overflow-x: auto; }
        .table, .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .table th, .data-table th { padding: 0.75rem 1rem; background-color: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb; }
        .table td, .data-table td { padding: 1rem; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 0.875rem; }
        .table tr:last-child td, .data-table tr:last-child td { border-bottom: none; }
        
        .form-control { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; color: #111827; background-color: white; }
        .form-control:focus { outline: none; border-color: #6366f1; ring: 2px solid #e0e7ff; }
        
        /* Filter Forms Compatibility */
        .filter-form { display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; margin-bottom: 1.5rem; }
        .filter-group { display: flex; flex-direction: column; gap: 0.25rem; }
        .filter-group label { font-size: 0.875rem; font-weight: 500; color: #374151; }
        
        .modal { display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .modal-content { background-color: white; width: 100%; max-width: 32rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden; margin: 2rem; }
        .modal-header { padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { font-size: 1.25rem; font-weight: 600; color: #111827; margin: 0; }
        .modal-body { padding: 1.5rem; }
        .close { cursor: pointer; color: #9ca3af; font-size: 1.5rem; }
        .close:hover { color: #4b5563; }
        
        .alert-success { background-color: #ecfdf5; color: #065f46; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem; border: 1px solid #d1fae5; }
        .alert-error, .alert-danger { background-color: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem; border: 1px solid #fee2e2; }
        
        /* Stats Grid Compatibility */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; }
        .stat-card { background-color: white; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #111827; margin: 0.5rem 0; }
        .stat-label { color: #6b7280; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; }

        /* Counter Desk Specifics */
        .token-display-large { font-size: 5rem; font-weight: 800; color: #111827; font-feature-settings: "tnum"; font-variant-numeric: tabular-nums; margin: 1rem 0; }
        .current-token-display { text-align: center; padding: 2rem; background-color: #f9fafb; border-radius: 0.75rem; border: 1px solid #e5e7eb; }
        .service-type { color: #4f46e5; font-size: 1.125rem; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600; }
        
        /* Badges */
        .status-badge { padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-serving { background-color: #d1fae5; color: #065f46; }
        .status-waiting { background-color: #fef3c7; color: #92400e; }
        .status-completed { background-color: #dbeafe; color: #1e40af; }

        /* Counter Selection Styles */
        .counter-select-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .counter-select-btn { display: flex; flex-direction: column; align-items: center; padding: 1.5rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; text-align: center; color: #374151; transition: all 0.2s; }
        .counter-select-btn:hover { border-color: #6366f1; background-color: #f9fafb; text-decoration: none; }
        .counter-select-btn.active { border-color: #4f46e5; background-color: #eef2ff; ring: 2px solid #6366f1; }
        .counter-select-btn strong { font-size: 1.5rem; color: #111827; margin-bottom: 0.25rem; }
        .counter-select-btn .status { margin-top: 0.5rem; font-size: 0.75rem; text-transform: uppercase; padding: 0.25rem 0.5rem; border-radius: 9999px; background: #f3f4f6; }
    </style>
</head>
<body class="h-full">
    <div class="min-h-full flex">
        <!-- Modern Enhanced Sidebar -->
        <aside class="w-72 sidebar-gradient fixed h-full z-10 flex flex-col hidden md:flex shadow-2xl">
            <!-- Premium Brand Header -->
            <div class="relative h-20 flex items-center justify-center overflow-hidden border-b border-white/10">
                <!-- Animated Background -->
                <div class="absolute inset-0">
                    <div class="absolute top-0 left-0 w-32 h-32 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full opacity-20 blur-3xl animate-pulse"></div>
                    <div class="absolute bottom-0 right-0 w-32 h-32 bg-gradient-to-tl from-blue-500 to-indigo-500 rounded-full opacity-20 blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
                </div>
                
                <!-- Logo Container -->
                <div class="relative flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-indigo-400 via-purple-400 to-pink-400 rounded-2xl flex items-center justify-center shadow-2xl transform hover:rotate-6 transition-transform duration-300">
                        <span class="text-2xl filter drop-shadow-lg">🎯</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-white tracking-tight"><?php echo APP_NAME; ?></h2>
                        <p class="text-xs text-indigo-300 font-medium">Management Portal</p>
                    </div>
                </div>
            </div>
            
            <nav class="flex-1 overflow-y-auto py-6 px-4 scrollbar-thin scrollbar-thumb-indigo-500 scrollbar-track-transparent">
                <!-- Main Navigation Section -->
                <div class="space-y-2">
                    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="nav-item group relative flex items-center px-4 py-3.5 rounded-xl transition-all duration-300 <?php echo (strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false) ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300 <?php echo (strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false) ? 'bg-white/20 shadow-lg' : ''; ?>">
                                <span class="text-xl transform group-hover:scale-110 transition-transform duration-300">📊</span>
                            </div>
                        </div>
                        <span class="font-semibold text-sm">Dashboard</span>
                        <?php if (strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false): ?>
                            <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                        <?php endif; ?>
                    </a>
                    
                    <a href="<?php echo BASE_URL; ?>/admin/counter.php" class="nav-item group relative flex items-center px-4 py-3.5 rounded-xl transition-all duration-300 <?php echo (strpos($_SERVER['PHP_SELF'], 'counter.php') !== false) ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300 <?php echo (strpos($_SERVER['PHP_SELF'], 'counter.php') !== false) ? 'bg-white/20 shadow-lg' : ''; ?>">
                                <span class="text-xl transform group-hover:scale-110 transition-transform duration-300">🖥️</span>
                            </div>
                        </div>
                        <span class="font-semibold text-sm">Counter Desk</span>
                        <?php if (strpos($_SERVER['PHP_SELF'], 'counter.php') !== false): ?>
                            <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- Management Section -->
                <div class="mt-8">
                    <div class="px-4 mb-4 flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mr-3 shadow-lg">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <h3 class="text-xs font-black text-indigo-200 uppercase tracking-widest">Management</h3>
                    </div>
                    <div class="space-y-1.5">
                        <a href="<?php echo BASE_URL; ?>/admin/tokens.php" class="nav-item group relative flex items-center px-4 py-3 rounded-xl transition-all duration-300 <?php echo (strpos($_SERVER['PHP_SELF'], 'tokens.php') !== false) ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                            <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300">
                                <span class="text-lg transform group-hover:scale-110 transition-transform duration-300">🎫</span>
                            </div>
                            <span class="font-medium text-sm">Tokens</span>
                            <?php if (strpos($_SERVER['PHP_SELF'], 'tokens.php') !== false): ?>
                                <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                            <?php endif; ?>
                        </a>
                        
                        <a href="<?php echo BASE_URL; ?>/admin/vessels.php" class="nav-item group relative flex items-center px-4 py-3 rounded-xl transition-all duration-300 <?php echo (strpos($_SERVER['PHP_SELF'], 'vessels.php') !== false) ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                            <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300">
                                <span class="text-lg transform group-hover:scale-110 transition-transform duration-300">🚢</span>
                            </div>
                            <span class="font-medium text-sm">Vessels</span>
                            <?php if (strpos($_SERVER['PHP_SELF'], 'vessels.php') !== false): ?>
                                <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                            <?php endif; ?>
                        </a>
                        
                        <a href="<?php echo BASE_URL; ?>/admin/schedules.php" class="nav-item group relative flex items-center px-4 py-3 rounded-xl transition-all duration-300 <?php echo (strpos($_SERVER['PHP_SELF'], 'schedules.php') !== false) ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                            <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300">
                                <span class="text-lg transform group-hover:scale-110 transition-transform duration-300">📅</span>
                            </div>
                            <span class="font-medium text-sm">Schedules</span>
                            <?php if (strpos($_SERVER['PHP_SELF'], 'schedules.php') !== false): ?>
                                <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                            <?php endif; ?>
                        </a>

                        <a href="<?php echo BASE_URL; ?>/admin/manifest.php" class="nav-item group relative flex items-center px-4 py-3 rounded-xl transition-all duration-300 <?php echo (basename($_SERVER['PHP_SELF']) === 'manifest.php') ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                            <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300">
                                <span class="text-lg transform group-hover:scale-110 transition-transform duration-300">📋</span>
                            </div>
                            <span class="font-medium text-sm">Manifest</span>
                            <?php if (basename($_SERVER['PHP_SELF']) === 'manifest.php'): ?>
                                <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                            <?php endif; ?>
                        </a>
                        
                        <a href="<?php echo BASE_URL; ?>/admin/announcements.php" class="nav-item group relative flex items-center px-4 py-3 rounded-xl transition-all duration-300 <?php echo (basename($_SERVER['PHP_SELF']) === 'announcements.php') ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                            <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300">
                                <span class="text-lg transform group-hover:scale-110 transition-transform duration-300">📢</span>
                            </div>
                            <span class="font-medium text-sm">Announcements</span>
                            <?php if (basename($_SERVER['PHP_SELF']) === 'announcements.php'): ?>
                                <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                            <?php endif; ?>
                        </a>
                        
                        <a href="<?php echo BASE_URL; ?>/admin/services.php" class="nav-item group relative flex items-center px-4 py-3 rounded-xl transition-all duration-300 <?php echo (strpos($_SERVER['PHP_SELF'], 'services.php') !== false) ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                            <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300">
                                <span class="text-lg transform group-hover:scale-110 transition-transform duration-300">🏷️</span>
                            </div>
                            <span class="font-medium text-sm">Services</span>
                            <?php if (strpos($_SERVER['PHP_SELF'], 'services.php') !== false): ?>
                                <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>

                <?php if (hasPermission('admin')): ?>
                <!-- System Section -->
                <div class="mt-8 mb-4">
                    <div class="px-4 mb-4 flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center mr-3 shadow-lg">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xs font-black text-purple-200 uppercase tracking-widest">System</h3>
                    </div>
                    <div class="space-y-1.5">
                        <a href="<?php echo BASE_URL; ?>/admin/users.php" class="nav-item group relative flex items-center px-4 py-3 rounded-xl transition-all duration-300 <?php echo (strpos($_SERVER['PHP_SELF'], 'users.php') !== false) ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                            <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300">
                                <span class="text-lg transform group-hover:scale-110 transition-transform duration-300">👥</span>
                            </div>
                            <span class="font-medium text-sm">Users</span>
                            <?php if (strpos($_SERVER['PHP_SELF'], 'users.php') !== false): ?>
                                <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                            <?php endif; ?>
                        </a>
                        
                        <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="nav-item group relative flex items-center px-4 py-3 rounded-xl transition-all duration-300 <?php echo (strpos($_SERVER['PHP_SELF'], 'reports.php') !== false) ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                            <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300">
                                <span class="text-lg transform group-hover:scale-110 transition-transform duration-300">&#x1F4C8;</span>
                            </div>
                            <span class="font-medium text-sm">Reports</span>
                            <?php if (strpos($_SERVER['PHP_SELF'], 'reports.php') !== false): ?>
                                <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                            <?php endif; ?>
                        </a>

                        <a href="<?php echo BASE_URL; ?>/admin/audit-log.php" class="nav-item group relative flex items-center px-4 py-3 rounded-xl transition-all duration-300 <?php echo (basename($_SERVER['PHP_SELF']) === 'audit-log.php') ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                            <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300">
                                <span class="text-lg transform group-hover:scale-110 transition-transform duration-300">&#x1F4CB;</span>
                            </div>
                            <span class="font-medium text-sm">Audit Log</span>
                            <?php if (basename($_SERVER['PHP_SELF']) === 'audit-log.php'): ?>
                                <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                            <?php endif; ?>
                        </a>
                        
                        <a href="<?php echo BASE_URL; ?>/admin/counter-settings.php" class="nav-item group relative flex items-center px-4 py-3 rounded-xl transition-all duration-300 <?php echo (strpos($_SERVER['PHP_SELF'], 'counter-settings.php') !== false) ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                            <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300">
                                <span class="text-lg transform group-hover:scale-110 transition-transform duration-300">⚙️</span>
                            </div>
                            <span class="font-medium text-sm">Counter Settings</span>
                            <?php if (strpos($_SERVER['PHP_SELF'], 'counter-settings.php') !== false): ?>
                                <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                            <?php endif; ?>
                        </a>
                        
                        <a href="<?php echo BASE_URL; ?>/admin/settings.php" class="nav-item group relative flex items-center px-4 py-3 rounded-xl transition-all duration-300 <?php echo (basename($_SERVER['PHP_SELF']) === 'settings.php') ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                            <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300">
                                <span class="text-lg transform group-hover:scale-110 transition-transform duration-300">&#x1F3A8;</span>
                            </div>
                            <span class="font-medium text-sm">Display Settings</span>
                            <?php if (basename($_SERVER['PHP_SELF']) === 'settings.php'): ?>
                                <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Profile Section (all users) -->
                <div class="mt-6 mb-2">
                    <div class="space-y-1.5">
                        <a href="<?php echo BASE_URL; ?>/admin/profile.php" class="nav-item group relative flex items-center px-4 py-3 rounded-xl transition-all duration-300 <?php echo (basename($_SERVER['PHP_SELF']) === 'profile.php') ? 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white shadow-lg shadow-indigo-500/50' : 'text-gray-300 hover:bg-white/10 hover:text-white'; ?>">
                            <div class="w-9 h-9 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center mr-3 group-hover:bg-white/20 transition-all duration-300">
                                <span class="text-lg transform group-hover:scale-110 transition-transform duration-300">&#x1F464;</span>
                            </div>
                            <span class="font-medium text-sm">My Profile &amp; PIN</span>
                            <?php if (basename($_SERVER['PHP_SELF']) === 'profile.php'): ?>
                                <div class="absolute right-3 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </nav>
            
            <!-- User Profile & Actions Footer -->
            <div class="border-t border-white/10 p-4 space-y-3">
                <!-- User Profile Card -->
                <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3 border border-white/10 hover:bg-white/10 transition-all duration-300">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-sm"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-semibold text-sm truncate"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                            <p class="text-indigo-300 text-xs truncate"><?php echo ucfirst($_SESSION['role'] ?? 'User'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Display Board Button -->
                <a href="<?php echo BASE_URL; ?>/display/" target="_blank" class="group relative flex items-center justify-center px-4 py-3 rounded-xl text-sm font-bold bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white hover:from-indigo-600 hover:via-purple-600 hover:to-pink-600 transition-all duration-300 shadow-lg shadow-indigo-500/50 hover:shadow-xl hover:shadow-indigo-500/70 overflow-hidden">
                    <span class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></span>
                    <span class="relative flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span>Display Board</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1 group-hover:-translate-y-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="md:pl-72 flex-1 flex flex-col min-h-screen transition-all duration-200">
            <!-- Enhanced Top Navbar -->
            <header class="bg-white/80 backdrop-blur-xl shadow-lg border-b border-gray-200/50 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-10 sticky top-0">
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex items-center text-sm text-gray-500">
                        <span class="hover:text-gray-700 transition font-medium">Portal Admin</span>
                        <svg class="h-5 w-5 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <h1 class="text-lg font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent"><?php echo $pageTitle ?? 'Page'; ?></h1>
                </div>
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex items-center gap-3 px-4 py-2 bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl border border-gray-200">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md">
                            <span class="text-white font-bold text-xs"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?></span>
                        </div>
                        <span class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/admin/profile.php" class="group inline-flex items-center gap-2 px-3 py-2 border-2 border-indigo-100 text-sm font-semibold rounded-xl text-indigo-600 bg-white hover:bg-indigo-50 hover:border-indigo-300 transition-all duration-200 shadow-sm hover:shadow-md" title="My Profile &amp; PIN">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <span class="hidden sm:inline">My PIN</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/logout.php" class="group inline-flex items-center gap-2 px-4 py-2 border-2 border-red-200 text-sm font-semibold rounded-xl text-red-600 bg-white hover:bg-red-50 hover:border-red-300 transition-all duration-200 shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                </div>
            </header>
            
            <main class="flex-1 p-4 sm:p-8 bg-gray-50">
<?php
// Offline banner
try {
    $db = getDB();
    $svcMode = $db->query("SELECT setting_value FROM system_settings WHERE setting_key='service_mode' LIMIT 1")->fetchColumn();
    if ($svcMode === 'offline'):
?>
<div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 rounded-2xl px-5 py-3 shadow-sm">
    <span class="text-xl">🔴</span>
    <div class="flex-1">
        <span class="font-bold text-red-700 text-sm">System is OFFLINE —</span>
        <span class="text-red-600 text-sm"> Token generation is blocked and the display board is showing a maintenance screen.</span>
    </div>
    <?php if (hasPermission('admin')): ?>
    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="text-xs font-bold text-red-700 underline whitespace-nowrap hover:text-red-900">Go Online →</a>
    <?php endif; ?>
</div>
<?php
    endif;
} catch (Exception $e) { /* silent */ }
?>
