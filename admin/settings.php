<?php
/**
 * Display Settings Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/SettingsManager.php';

requireLogin();

// Check if user is admin
if (!hasPermission('admin')) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$settingsManager = new SettingsManager();

// Get current settings (must be before POST handler so $videoPath preserves existing value)
$currentSettings = $settingsManager->getDisplaySettings();

// Handle Form Submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Use hidden field as primary source of truth for existing video path,
        // fallback to DB value — prevents wiping the path on unrelated saves
        $videoPath = $_POST['current_video_path'] ?? ($currentSettings['display_bg_video_url'] ?? '');
        
        // Handle video file upload
        if (isset($_FILES['bg_video']) && $_FILES['bg_video']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/videos/';

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileInfo = pathinfo($_FILES['bg_video']['name']);
            $extension = strtolower($fileInfo['extension']);
            
            // Validate file type
            $allowedTypes = ['mp4', 'webm'];
            if (!in_array($extension, $allowedTypes)) {
                throw new Exception('Only MP4 and WebM video files are allowed.');
            }
            
            // Validate file size (max 500MB)
            $maxSize = 500 * 1024 * 1024; // 500MB
            if ($_FILES['bg_video']['size'] > $maxSize) {
                throw new Exception('Video file size must be less than 500MB.');
            }
            
            // Generate unique filename
            $newFileName = 'display_bg_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $newFileName;
            
            // Delete old video file if exists
            if (!empty($videoPath) && file_exists(__DIR__ . '/../' . $videoPath)) {
                unlink(__DIR__ . '/../' . $videoPath);
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['bg_video']['tmp_name'], $uploadPath)) {
                $videoPath = 'uploads/videos/' . $newFileName;
            } else {
                throw new Exception('Failed to upload video file.');
            }
        }
        
        // Handle selecting an already-uploaded file
        if (!empty($_POST['select_existing_video'])) {
            $selected = basename($_POST['select_existing_video']); // sanitize
            $videoPath = 'uploads/videos/' . $selected;
        }

        // Handle video deletion
        if (isset($_POST['delete_video']) && $_POST['delete_video'] === '1') {
            if (!empty($videoPath) && file_exists(__DIR__ . '/../' . $videoPath)) {
                unlink(__DIR__ . '/../' . $videoPath);
            }
            $videoPath = '';
        }
        
        $settings = [
            'display_bg_type' => $_POST['bg_type'] ?? 'gradient',
            'display_bg_video_url' => $videoPath,
            'display_bg_gradient_start' => $_POST['bg_gradient_start'] ?? '#667eea',
            'display_bg_gradient_end' => $_POST['bg_gradient_end'] ?? '#764ba2',
            'display_accent_color' => $_POST['accent_color'] ?? '#f093fb',
            'display_card_bg' => $_POST['card_bg'] ?? 'rgba(255, 255, 255, 0.95)',
            'display_success_gradient_start' => $_POST['success_gradient_start'] ?? '#11998e',
            'display_success_gradient_end' => $_POST['success_gradient_end'] ?? '#38ef7d',
            'display_alert_gradient_start' => $_POST['alert_gradient_start'] ?? '#f093fb',
            'display_alert_gradient_end' => $_POST['alert_gradient_end'] ?? '#f5576c',
        ];
        
        $settingsManager->updateSettings($settings);
        $message = 'Display settings updated successfully!';
    } catch (Exception $e) {
        $error = 'Error updating settings: ' . $e->getMessage();
    }
}

// (Already loaded above, no second call needed)

$pageTitle = 'Display Settings';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Display Board Settings</h1>
            <p class="text-sm text-gray-500 mt-1">Customize the color palette for the public display board</p>
        </div>
        <a href="<?php echo BASE_URL; ?>/display/" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
            </svg>
            Preview Display Board
        </a>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-start">
            <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Left Column: Main Form -->
        <div class="lg:col-span-2">
        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6 space-y-8">
            
            <!-- Background Type Selection -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg mr-3"></span>
                    Background Type
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all hover:border-indigo-400 <?php echo ($currentSettings['display_bg_type'] ?? 'gradient') === 'gradient' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200'; ?>">
                        <input type="radio" name="bg_type" value="gradient" <?php echo ($currentSettings['display_bg_type'] ?? 'gradient') === 'gradient' ? 'checked' : ''; ?> class="sr-only" onchange="toggleBackgroundType(this.value)">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg mr-4"></div>
                            <div>
                                <div class="font-semibold text-gray-900">Gradient Background</div>
                                <div class="text-sm text-gray-500">Solid color gradient</div>
                            </div>
                        </div>
                    </label>
                    <label class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all hover:border-indigo-400 <?php echo ($currentSettings['display_bg_type'] ?? 'gradient') === 'video' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200'; ?>">
                        <input type="radio" name="bg_type" value="video" <?php echo ($currentSettings['display_bg_type'] ?? 'gradient') === 'video' ? 'checked' : ''; ?> class="sr-only" onchange="toggleBackgroundType(this.value)">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gray-900 rounded-lg mr-4 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Video Background</div>
                                <div class="text-sm text-gray-500">MP4 video loop</div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="border-t border-gray-200"></div>

            <!-- Video Background Upload -->
            <div id="videoSection" class="<?php echo ($currentSettings['display_bg_type'] ?? 'gradient') === 'video' ? '' : 'hidden'; ?>">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-6 h-6 text-gray-700 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                    </svg>
                    Video Background Settings
                </h3>
                
                <?php if (!empty($currentSettings['display_bg_video_url'])): ?>
                <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center flex-1">
                            <svg class="w-10 h-10 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">Current Video</div>
                                <div class="text-sm text-gray-500 mt-1"><?php echo basename($currentSettings['display_bg_video_url']); ?></div>
                                <video class="mt-2 rounded border border-gray-300 max-w-xs" controls>
                                    <source src="<?php echo BASE_URL . '/' . $currentSettings['display_bg_video_url']; ?>" type="video/mp4">
                                </video>
                            </div>
                        </div>
                        <button type="button" onclick="deleteVideo()" class="ml-4 px-3 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload Video Background</label>
                    <div class="flex items-center justify-center w-full">
                        <label for="bg_video" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                <p class="text-xs text-gray-500">MP4 or WebM (MAX. 500MB)</p>
                            </div>
                            <input id="bg_video" name="bg_video" type="file" class="hidden" accept="video/mp4,video/webm" onchange="updateFileName(this)">
                        </label>
                    </div>
                    <div id="fileName" class="mt-2 text-sm text-gray-600"></div>
                    <p class="mt-2 text-sm text-gray-500">
                        <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        Upload a video file to use as background. The video will loop continuously on the display board.
                    </p>
                </div>
                
                <input type="hidden" name="delete_video" id="delete_video" value="0">
                <input type="hidden" name="current_video_path" value="<?php echo htmlspecialchars($currentSettings['display_bg_video_url'] ?? ''); ?>">

                <?php
                // List already-uploaded videos on the server
                $uploadedVideos = [];
                $scanDir = __DIR__ . '/../uploads/videos/';
                if (is_dir($scanDir)) {
                    foreach (scandir($scanDir) as $f) {
                        if (preg_match('/\.(mp4|webm)$/i', $f)) {
                            $uploadedVideos[] = $f;
                        }
                    }
                }
                if (!empty($uploadedVideos)):
                ?>
                <div class="mt-4 p-4 bg-indigo-50 border border-indigo-200 rounded-lg">
                    <p class="text-sm font-semibold text-indigo-800 mb-3">📂 Or select an already-uploaded video:</p>
                    <div class="space-y-2">
                        <?php foreach ($uploadedVideos as $vf): ?>
                        <?php $isActive = ($currentSettings['display_bg_video_url'] ?? '') === 'uploads/videos/' . $vf; ?>
                        <div class="flex items-center justify-between p-2 bg-white rounded-lg border <?php echo $isActive ? 'border-indigo-500' : 'border-gray-200'; ?>">
                            <span class="text-sm text-gray-700 font-mono truncate flex-1"><?php echo htmlspecialchars($vf); ?>
                                <?php if ($isActive): ?><span class="ml-2 text-xs text-indigo-600 font-bold">(active)</span><?php endif; ?>
                            </span>
                            <button type="submit" name="select_existing_video" value="<?php echo htmlspecialchars($vf); ?>"
                                class="ml-3 px-3 py-1 text-xs font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition <?php echo $isActive ? 'opacity-50 cursor-default' : ''; ?>"
                                <?php echo $isActive ? 'disabled' : ''; ?>>
                                <?php echo $isActive ? 'Active' : 'Use This'; ?>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Background Gradient -->
            <div id="gradientSection" class="<?php echo ($currentSettings['display_bg_type'] ?? 'gradient') === 'gradient' ? '' : 'hidden'; ?>">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg mr-3"></span>
                    Background Gradient Colors
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="bg_gradient_start" value="<?php echo $currentSettings['display_bg_gradient_start'] ?? '#667eea'; ?>" class="h-12 w-20 rounded border border-gray-300 cursor-pointer">
                            <input type="text" value="<?php echo $currentSettings['display_bg_gradient_start'] ?? '#667eea'; ?>" onchange="this.previousElementSibling.value = this.value" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="bg_gradient_end" value="<?php echo $currentSettings['display_bg_gradient_end'] ?? '#764ba2'; ?>" class="h-12 w-20 rounded border border-gray-300 cursor-pointer">
                            <input type="text" value="<?php echo $currentSettings['display_bg_gradient_end'] ?? '#764ba2'; ?>" onchange="this.previousElementSibling.value = this.value" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        </div>
                    </div>
                </div>
                <div class="mt-3 p-4 rounded-lg" style="background: linear-gradient(135deg, <?php echo $currentSettings['display_bg_gradient_start'] ?? '#667eea'; ?> 0%, <?php echo $currentSettings['display_bg_gradient_end'] ?? '#764ba2'; ?> 100%); height: 80px;"></div>
            </div>

            <div class="border-t border-gray-200"></div>

            <!-- Success Status Gradient -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-gradient-to-br from-green-400 to-teal-600 rounded-lg mr-3"></span>
                    Success Status (Serving)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="success_gradient_start" value="<?php echo $currentSettings['display_success_gradient_start'] ?? '#11998e'; ?>" class="h-12 w-20 rounded border border-gray-300 cursor-pointer">
                            <input type="text" value="<?php echo $currentSettings['display_success_gradient_start'] ?? '#11998e'; ?>" onchange="this.previousElementSibling.value = this.value" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="success_gradient_end" value="<?php echo $currentSettings['display_success_gradient_end'] ?? '#38ef7d'; ?>" class="h-12 w-20 rounded border border-gray-300 cursor-pointer">
                            <input type="text" value="<?php echo $currentSettings['display_success_gradient_end'] ?? '#38ef7d'; ?>" onchange="this.previousElementSibling.value = this.value" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        </div>
                    </div>
                </div>
                <div class="mt-3 p-4 rounded-lg flex items-center justify-center text-white font-semibold" style="background: linear-gradient(135deg, <?php echo $currentSettings['display_success_gradient_start'] ?? '#11998e'; ?> 0%, <?php echo $currentSettings['display_success_gradient_end'] ?? '#38ef7d'; ?> 100%); height: 60px;">
                    Serving Status Preview
                </div>
            </div>

            <div class="border-t border-gray-200"></div>

            <!-- Alert Status Gradient -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-gradient-to-br from-pink-400 to-red-600 rounded-lg mr-3"></span>
                    Alert Status (Called)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="alert_gradient_start" value="<?php echo $currentSettings['display_alert_gradient_start'] ?? '#f093fb'; ?>" class="h-12 w-20 rounded border border-gray-300 cursor-pointer">
                            <input type="text" value="<?php echo $currentSettings['display_alert_gradient_start'] ?? '#f093fb'; ?>" onchange="this.previousElementSibling.value = this.value" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="alert_gradient_end" value="<?php echo $currentSettings['display_alert_gradient_end'] ?? '#f5576c'; ?>" class="h-12 w-20 rounded border border-gray-300 cursor-pointer">
                            <input type="text" value="<?php echo $currentSettings['display_alert_gradient_end'] ?? '#f5576c'; ?>" onchange="this.previousElementSibling.value = this.value" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        </div>
                    </div>
                </div>
                <div class="mt-3 p-4 rounded-lg flex items-center justify-center text-white font-semibold" style="background: linear-gradient(135deg, <?php echo $currentSettings['display_alert_gradient_start'] ?? '#f093fb'; ?> 0%, <?php echo $currentSettings['display_alert_gradient_end'] ?? '#f5576c'; ?> 100%); height: 60px;">
                    Called Status Preview
                </div>
            </div>

            <div class="border-t border-gray-200"></div>

            <!-- Additional Colors -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <span class="w-8 h-8 bg-gradient-to-br from-purple-400 to-pink-500 rounded-lg mr-3"></span>
                    Additional Colors
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Accent Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="accent_color" value="<?php echo $currentSettings['display_accent_color'] ?? '#f093fb'; ?>" class="h-12 w-20 rounded border border-gray-300 cursor-pointer">
                            <input type="text" value="<?php echo $currentSettings['display_accent_color'] ?? '#f093fb'; ?>" onchange="this.previousElementSibling.value = this.value" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Card Background</label>
                        <input type="text" name="card_bg" value="<?php echo $currentSettings['display_card_bg'] ?? 'rgba(255, 255, 255, 0.95)'; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        <p class="text-xs text-gray-500 mt-1">Use rgba() or hex format</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 rounded-b-xl flex items-center justify-between border-t border-gray-200">
            <div class="text-sm text-gray-500">
                <span class="font-medium">💡 Tip:</span> Changes apply immediately to the display board
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="location.reload()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                    Reset
                </button>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition font-medium shadow-sm">
                    Save Changes
                </button>
            </div>
        </div>
    </form>
    </div><!-- end lg:col-span-2 -->

        <!-- Right Column: Color Presets Sidebar -->
        <div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-24">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Color Presets</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-indigo-500 transition" onclick="applyPreset('purple')">
                        <div class="h-16 rounded-lg mb-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                        <p class="text-sm font-medium text-gray-900">Purple Gradient</p>
                        <p class="text-xs text-gray-500">Default theme</p>
                    </div>
                    <div class="p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-indigo-500 transition" onclick="applyPreset('ocean')">
                        <div class="h-16 rounded-lg mb-2" style="background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);"></div>
                        <p class="text-sm font-medium text-gray-900">Ocean Blue</p>
                        <p class="text-xs text-gray-500">Cool and calm</p>
                    </div>
                    <div class="p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-indigo-500 transition" onclick="applyPreset('sunset')">
                        <div class="h-16 rounded-lg mb-2" style="background: linear-gradient(135deg, #f12711 0%, #f5af19 100%);"></div>
                        <p class="text-sm font-medium text-gray-900">Sunset Orange</p>
                        <p class="text-xs text-gray-500">Warm and vibrant</p>
                    </div>
                    <div class="p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-indigo-500 transition" onclick="applyPreset('forest')">
                        <div class="h-16 rounded-lg mb-2" style="background: linear-gradient(135deg, #134e5e 0%, #71b280 100%);"></div>
                        <p class="text-sm font-medium text-gray-900">Forest Green</p>
                        <p class="text-xs text-gray-500">Natural and fresh</p>
                    </div>
                </div>
            </div>
        </div><!-- end right column -->

    </div><!-- end grid -->
</div><!-- end w-full -->

<script>
// Toggle background type sections
function toggleBackgroundType(type) {
    const videoSection = document.getElementById('videoSection');
    const gradientSection = document.getElementById('gradientSection');
    
    if (type === 'video') {
        videoSection.classList.remove('hidden');
        gradientSection.classList.add('hidden');
    } else {
        videoSection.classList.add('hidden');
        gradientSection.classList.remove('hidden');
    }
}

// Sync color inputs
document.querySelectorAll('input[type="color"]').forEach(input => {
    input.addEventListener('change', function() {
        this.nextElementSibling.value = this.value;
    });
});

document.querySelectorAll('input[type="text"]').forEach(input => {
    if (input.previousElementSibling && input.previousElementSibling.type === 'color') {
        input.addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                this.previousElementSibling.value = this.value;
            }
        });
    }
});

// Apply preset themes
function applyPreset(preset) {
    const presets = {
        purple: {
            bg_start: '#667eea',
            bg_end: '#764ba2',
            success_start: '#11998e',
            success_end: '#38ef7d',
            alert_start: '#f093fb',
            alert_end: '#f5576c',
            accent: '#f093fb'
        },
        ocean: {
            bg_start: '#2193b0',
            bg_end: '#6dd5ed',
            success_start: '#56ab2f',
            success_end: '#a8e063',
            alert_start: '#ff6b6b',
            alert_end: '#feca57',
            accent: '#4ecdc4'
        },
        sunset: {
            bg_start: '#f12711',
            bg_end: '#f5af19',
            success_start: '#43e97b',
            success_end: '#38f9d7',
            alert_start: '#fa709a',
            alert_end: '#fee140',
            accent: '#ff9a56'
        },
        forest: {
            bg_start: '#134e5e',
            bg_end: '#71b280',
            success_start: '#11998e',
            success_end: '#38ef7d',
            alert_start: '#eb3349',
            alert_end: '#f45c43',
            accent: '#56ab2f'
        }
    };

    const p = presets[preset];
    document.querySelector('input[name="bg_gradient_start"]').value = p.bg_start;
    document.querySelector('input[name="bg_gradient_end"]').value = p.bg_end;
    document.querySelector('input[name="success_gradient_start"]').value = p.success_start;
    document.querySelector('input[name="success_gradient_end"]').value = p.success_end;
    document.querySelector('input[name="alert_gradient_start"]').value = p.alert_start;
    document.querySelector('input[name="alert_gradient_end"]').value = p.alert_end;
    document.querySelector('input[name="accent_color"]').value = p.accent;

    // Update text inputs
    document.querySelectorAll('input[type="color"]').forEach(input => {
        input.nextElementSibling.value = input.value;
    });
}

// Update filename display
function updateFileName(input) {
    const fileNameDiv = document.getElementById('fileName');
    if (input.files && input.files[0]) {
        const fileName = input.files[0].name;
        const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2);
        fileNameDiv.innerHTML = `
            <div class="flex items-center text-indigo-600">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span><strong>${fileName}</strong> (${fileSize} MB)</span>
            </div>
        `;
    } else {
        fileNameDiv.innerHTML = '';
    }
}

// Delete video function
function deleteVideo() {
    if (confirm('Are you sure you want to delete the current video?')) {
        document.getElementById('delete_video').value = '1';
        document.querySelector('form').submit();
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
