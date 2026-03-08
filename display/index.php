<?php
/**
 * Display Board - Shows currently serving tokens
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TokenManager.php';
require_once __DIR__ . '/../includes/ServiceManager.php';
require_once __DIR__ . '/../includes/SettingsManager.php';

$tokenManager = new TokenManager();
$serviceManager = new ServiceManager();
$settingsManager = new SettingsManager();

$serving = $tokenManager->getCurrentlyServing();
$counterStatus = $serviceManager->getCounterStatus();

// Get display settings from database
$displaySettings = $settingsManager->getDisplaySettings();

// Extract colors and settings with defaults
$bgType = $displaySettings['display_bg_type'] ?? 'gradient';
$bgVideoUrl = $displaySettings['display_bg_video_url'] ?? '';
$bgStart = $displaySettings['display_bg_gradient_start'] ?? '#667eea';
$bgEnd = $displaySettings['display_bg_gradient_end'] ?? '#764ba2';
$accentColor = $displaySettings['display_accent_color'] ?? '#f093fb';
$cardBg = $displaySettings['display_card_bg'] ?? 'rgba(255, 255, 255, 0.95)';
$successStart = $displaySettings['display_success_gradient_start'] ?? '#11998e';
$successEnd = $displaySettings['display_success_gradient_end'] ?? '#38ef7d';
$alertStart = $displaySettings['display_alert_gradient_start'] ?? '#f093fb';
$alertEnd = $displaySettings['display_alert_gradient_end'] ?? '#f5576c';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Board - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet"></noscript>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        :root {
            --bg-primary: linear-gradient(135deg, <?php echo $bgStart; ?> 0%, <?php echo $bgEnd; ?> 100%);
            --bg-secondary: linear-gradient(135deg, <?php echo $alertStart; ?> 0%, <?php echo $alertEnd; ?> 100%);
            --card-bg: <?php echo $cardBg; ?>;
            --shadow-soft: 0 10px 40px rgba(0, 0, 0, 0.1);
            --shadow-strong: 0 20px 60px rgba(0, 0, 0, 0.15);
            --accent-gradient: linear-gradient(135deg, <?php echo $bgStart; ?> 0%, <?php echo $bgEnd; ?> 100%);
            --success-gradient: linear-gradient(135deg, <?php echo $successStart; ?> 0%, <?php echo $successEnd; ?> 100%);
            --alert-gradient: linear-gradient(135deg, <?php echo $alertStart; ?> 0%, <?php echo $alertEnd; ?> 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Helvetica Neue', sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            overflow-x: hidden;
            <?php if ($bgType === 'video'): ?>
            background: #000;
            <?php endif; ?>
        }

        /* Video Background */
        <?php if ($bgType === 'video' && !empty($bgVideoUrl)): ?>
        #video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -2;
        }
        #video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            z-index: -1;
        }
        <?php endif; ?>

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            <?php if ($bgType === 'gradient'): ?>
            background: linear-gradient(135deg, <?php echo $bgStart; ?> 0%, <?php echo $bgEnd; ?> 50%, <?php echo $alertStart; ?> 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            <?php else: ?>
            display: none;
            <?php endif; ?>
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating particles */
        .particle {
            position: fixed;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            pointer-events: none;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); }
            50% { transform: translateY(-20px) translateX(10px); }
        }
        
        /* Header Styling */
        .display-header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        
        .display-header h1 {
            font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            font-size: 36px;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.2);
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .display-header .datetime {
            font-size: 18px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.95);
            background: rgba(255, 255, 255, 0.15);
            padding: 12px 24px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        /* Main Grid Layout */
        .display-main {
            padding: 40px;
            min-height: calc(100vh - 180px);
        }
        
        .counter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            max-width: 1800px;
            margin: 0 auto;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .display-header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 12px;
            }

            .display-header h1 {
                font-size: 22px;
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }

            .header-badge {
                font-size: 10px;
                padding: 4px 12px;
            }

            .display-header .datetime {
                font-size: 14px;
                padding: 8px 16px;
            }

            .display-main {
                padding: 20px 15px;
                min-height: calc(100vh - 200px);
            }

            .counter-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .counter-display {
                height: auto;
                min-height: 160px;
                flex-direction: column;
            }

            .counter-info-side {
                flex: 1;
                padding: 16px 16px 12px;
                border-right: none;
                border-bottom: 1px solid rgba(102, 126, 234, 0.12);
            }

            .counter-number {
                font-size: clamp(24px, 10vw, 44px);
            }

            .counter-title {
                font-size: 10px;
            }

            .staff-info {
                font-size: 11px;
            }

            .token-display-side {
                flex: 1;
                padding: 16px;
            }

            .token-number {
                font-size: clamp(20px, 8vw, 36px);
                letter-spacing: 0px;
                word-break: break-word;
                line-height: 1.2;
                max-width: 100%;
            }

            .token-label {
                font-size: 10px;
            }

            .service-name {
                font-size: 12px;
                line-height: 1.3;
            }

            .status-badge-display {
                font-size: 8px;
                padding: 5px 12px;
            }

            .display-footer {
                padding: 0 10px 0 0;
                min-height: 40px;
                font-size: 11px;
            }

            .marquee span {
                font-size: 12px;
            }

        }

        /* ── Flash overlay ─────────────────────────────── */
        #flashOverlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0,0,0,0.78);
            align-items: center;
            justify-content: center;
            flex-direction: column;
            animation: flashFadeIn 0.25s ease;
        }
        #flashOverlay.show {
            display: flex;
        }
        @keyframes flashFadeIn {
            from { opacity: 0; transform: scale(0.92); }
            to   { opacity: 1; transform: scale(1); }
        }
        @keyframes flashPulse {
            0%,100% { transform: scale(1); }
            50%      { transform: scale(1.06); }
        }
        .flash-label {
            font-size: clamp(18px, 3vw, 28px);
            font-weight: 700;
            color: rgba(255,255,255,0.7);
            letter-spacing: 6px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .flash-token {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(80px, 18vw, 220px);
            font-weight: 900;
            background: linear-gradient(135deg, #38ef7d 0%, #11998e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            animation: flashPulse 0.8s ease-in-out infinite;
            letter-spacing: 4px;
        }
        .flash-counter {
            font-size: clamp(22px, 4vw, 48px);
            font-weight: 800;
            color: #fff;
            margin-top: 18px;
            letter-spacing: 2px;
        }
        .flash-bar {
            width: 0%;
            height: 6px;
            background: linear-gradient(90deg, #38ef7d, #11998e);
            border-radius: 3px;
            margin-top: 30px;
            transition: width linear;
        }

        @media (max-width: 480px) {
            .display-header h1 {
                font-size: 18px;
            }

            .counter-number {
                font-size: clamp(20px, 9vw, 34px);
            }

            .token-number {
                font-size: clamp(18px, 7vw, 28px);
                letter-spacing: 0px;
                word-break: break-word;
                line-height: 1.1;
            }

            .display-main {
                padding: 15px 10px;
            }

            .counter-display {
                min-height: 140px;
                border-radius: 20px;
            }

            .counter-info-side,
            .token-display-side {
                padding: 14px;
            }
        }
        
        /* Card Styling - Modern Glass Morphism */
        .counter-display {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 
                0 8px 32px rgba(31, 38, 135, 0.15),
                0 2px 8px rgba(0, 0, 0, 0.1),
                inset 0 1px 1px rgba(255, 255, 255, 0.8);
            display: flex;
            min-height: 180px;
            height: auto;
            position: relative;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.6);
            align-items: stretch;
        }

        .counter-display:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 20px 60px rgba(31, 38, 135, 0.25),
                0 8px 20px rgba(0, 0, 0, 0.15),
                inset 0 1px 1px rgba(255, 255, 255, 0.9);
        }

        /* Gradient Border Effect with Glow */
        .counter-display::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(180deg, 
                rgba(148, 163, 184, 0.6) 0%, 
                rgba(148, 163, 184, 0.3) 100%);
            transition: all 0.3s ease;
        }
        
        .counter-display.serving {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 
                0 12px 48px rgba(16, 185, 129, 0.2),
                0 4px 12px rgba(0, 0, 0, 0.1),
                inset 0 1px 1px rgba(255, 255, 255, 0.9);
        }
        
        .counter-display.serving::before {
            background: var(--success-gradient);
            box-shadow: 
                0 0 25px rgba(56, 239, 125, 0.7),
                0 0 50px rgba(56, 239, 125, 0.3);
            width: 8px;
        }

        .counter-display.called {
            animation: pulse-card 2s infinite;
            border-color: rgba(245, 87, 108, 0.3);
            background: rgba(255, 255, 255, 0.98);
        }

        .counter-display.called::before {
            background: var(--alert-gradient);
            box-shadow: 
                0 0 35px rgba(245, 87, 108, 0.9),
                0 0 60px rgba(245, 87, 108, 0.4);
            animation: pulse-gradient 2s infinite;
        }

        .counter-display.on-break::before {
            background: linear-gradient(180deg, #f59e0b 0%, #f97316 100%);
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.5);
            width: 8px;
        }

        .counter-display.closed-state {
            opacity: 0.55;
        }
        .counter-display.closed-state::before {
            background: linear-gradient(180deg, #94a3b8 0%, #64748b 100%);
        }

        @keyframes pulse-card {
            0%, 100% { 
                box-shadow: 
                    0 12px 48px rgba(245, 87, 108, 0.25),
                    0 4px 12px rgba(0, 0, 0, 0.1),
                    inset 0 1px 1px rgba(255, 255, 255, 0.9);
            }
            50% { 
                box-shadow: 
                    0 20px 70px rgba(245, 87, 108, 0.4),
                    0 8px 20px rgba(245, 87, 108, 0.2),
                    inset 0 1px 1px rgba(255, 255, 255, 0.9);
            }
        }

        @keyframes pulse-gradient {
            0%, 100% { width: 8px; }
            50% { width: 12px; }
        }

        /* Left Side: Counter Info - Enhanced */
        .counter-info-side {
            flex: 0 0 48%;
            min-width: 0;
            padding: 22px 18px 22px 26px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(135deg, 
                rgba(102, 126, 234, 0.08) 0%, 
                rgba(118, 75, 162, 0.08) 50%,
                rgba(102, 126, 234, 0.05) 100%);
            position: relative;
            overflow: hidden;
            border-right: 1px solid rgba(102, 126, 234, 0.12);
        }

        .counter-info-side::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .counter-title {
            font-size: 11px;
            color: #8b5cf6;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            font-weight: 800;
            margin-bottom: 4px;
            text-shadow: 0 2px 4px rgba(139, 92, 246, 0.1);
        }

        .counter-number {
            font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            font-size: clamp(28px, 4.5vw, 58px);
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #8b5cf6 50%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.1;
            margin-bottom: 10px;
            filter: drop-shadow(0 4px 8px rgba(102, 126, 234, 0.2));
            position: relative;
            z-index: 1;
            word-break: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
        }

        .staff-info {
            font-size: 12px;
            color: #475569;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            z-index: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #cbd5e1;
            transition: all 0.3s ease;
        }

        .serving .status-dot, .called .status-dot {
            background: #10b981;
            box-shadow: 
                0 0 0 3px rgba(16, 185, 129, 0.2),
                0 0 15px rgba(16, 185, 129, 0.6);
            animation: pulse-dot 2s infinite;
        }

        .on-break .status-dot {
            background: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2), 0 0 12px rgba(245, 158, 11, 0.5);
        }

        .closed-state .status-dot {
            background: #94a3b8;
        }

        @keyframes pulse-dot {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 
                    0 0 0 3px rgba(16, 185, 129, 0.2),
                    0 0 15px rgba(16, 185, 129, 0.6);
            }
            50% { 
                transform: scale(1.2);
                box-shadow: 
                    0 0 0 6px rgba(16, 185, 129, 0.15),
                    0 0 20px rgba(16, 185, 129, 0.8);
            }
        }

        /* Right Side: Token Display - Enhanced */
        .token-display-side {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 22px 18px;
            position: relative;
            background: linear-gradient(135deg, 
                rgba(240, 147, 251, 0.06) 0%, 
                rgba(245, 87, 108, 0.06) 50%,
                rgba(240, 147, 251, 0.04) 100%);
            overflow: hidden;
        }

        .token-display-side::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(245, 87, 108, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .token-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            color: #94a3b8;
            font-weight: 800;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .token-number {
            font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            font-size: clamp(22px, 3.8vw, 52px);
            font-weight: 900;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 50%, #ff6b6b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.1;
            margin-bottom: 8px;
            text-align: center;
            filter: drop-shadow(0 4px 8px rgba(240, 147, 251, 0.25));
            position: relative;
            z-index: 1;
            letter-spacing: 1px;
            word-break: break-word;
            max-width: 100%;
        }

        .service-name {
            font-size: 15px;
            color: #475569;
            text-align: center;
            font-weight: 600;
            max-width: 90%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            position: relative;
            z-index: 1;
        }

        /* Status Badge - Enhanced */
        .status-badge-display {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 
                0 4px 12px rgba(0, 0, 0, 0.1),
                inset 0 1px 1px rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
            margin-bottom: 12px;
            align-self: flex-start;
        }

        .status-badge-display.open {
            background: linear-gradient(135deg, rgba(148, 163, 184, 0.3), rgba(148, 163, 184, 0.2));
            color: #64748b;
        }

        .status-badge-display.serving {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(56, 239, 125, 0.15));
            color: #059669;
            box-shadow: 
                0 4px 12px rgba(16, 185, 129, 0.2),
                0 0 20px rgba(16, 185, 129, 0.1),
                inset 0 1px 1px rgba(255, 255, 255, 0.5);
        }
        }

        .status-serving .status-badge-display {
            background: var(--success-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(56, 239, 125, 0.3);
        }

        .status-called .status-badge-display {
            background: var(--alert-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
            animation: pulse-badge 1.5s infinite;
        }

        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .status-available .status-badge-display {
            background: rgba(148, 163, 184, 0.15);
            color: #64748b;
            backdrop-filter: blur(10px);
        }

        /* Connection Status */
        .connection-status {
            width: 12px;
            height: 12px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.8);
            display: inline-block;
            animation: pulse-connection 2s infinite;
        }

        @keyframes pulse-connection {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .connection-status.offline {
            background: #ef4444;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.8);
        }
        
        /* Header Controls */
        .header-controls {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .icon-btn {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            padding: 12px 20px;
            border-radius: 12px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .icon-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .icon-btn.active {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border-color: transparent;
            box-shadow: 0 8px 25px rgba(56, 239, 125, 0.3);
        }

        /* Footer Ticker */
        .display-footer {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            padding: 0 16px 0 0;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 48px;
        }

        .marquee {
            flex: 1;
            display: flex;
            overflow: hidden;
            white-space: nowrap;
            min-width: 0;
        }

        .marquee span {
            display: inline-block;
            padding-left: 100%;
            white-space: nowrap;
            animation: marquee-scroll 30s linear infinite;
            color: white;
            font-size: 18px;
            font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.2; }
        }

        @keyframes marquee-scroll {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }

        .waiting-message {
            color: #94a3b8;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
        }
        
    </style>
</head>
<body>
    <!-- Maintenance Overlay -->
    <div id="maintenanceOverlay" style="display:none; position:fixed; inset:0; z-index:9999;
        background:rgba(10,10,30,0.96); backdrop-filter:blur(8px);
        flex-direction:column; align-items:center; justify-content:center; text-align:center;">
        <div style="font-size:72px; margin-bottom:24px;">🔧</div>
        <div style="font-size:36px; font-weight:800; color:#fff; font-family:'Poppins',sans-serif;
            background:linear-gradient(135deg,#a78bfa,#f0abfc);
            -webkit-background-clip:text; -webkit-text-fill-color:transparent; margin-bottom:16px;">System Offline</div>
        <div id="maintenanceMsg" style="font-size:18px; color:rgba(255,255,255,0.7); max-width:560px;
            font-family:'Poppins',sans-serif; line-height:1.6; padding:0 24px;"></div>
        <div style="margin-top:32px; display:flex; gap:12px; align-items:center;">
            <span style="width:10px;height:10px;background:#f87171;border-radius:50%;display:inline-block;
                box-shadow:0 0 12px #f87171; animation:blink 1.2s infinite;"></span>
            <span style="color:rgba(255,255,255,0.5);font-size:13px;font-family:'Poppins',sans-serif;">Service temporarily unavailable</span>
        </div>
    </div>

    <?php if ($bgType === 'video' && !empty($bgVideoUrl)): ?>
    <!-- Video Background -->
    <video id="video-background" autoplay loop muted playsinline>
        <source src="<?php echo BASE_URL . '/' . htmlspecialchars($bgVideoUrl); ?>" type="video/mp4">
    </video>
    <div id="video-overlay"></div>
    <?php endif; ?>
    
    <div class="bg-animation"></div>
    
    <div class="display-header">
        <h1>
            <?php echo APP_NAME; ?>
            <span class="connection-status" id="connStatus" title="System Online"></span>
        </h1>
        <div class="header-controls">
            <div class="datetime" id="datetime"></div>
        </div>
    </div>
    
    <!-- Token Flash Overlay -->
    <div id="flashOverlay">
        <div class="flash-label">NOW SERVING</div>
        <div class="flash-token" id="flashToken"></div>
        <div class="flash-counter" id="flashCounter"></div>
        <div class="flash-bar" id="flashBar"></div>
    </div>

    <div class="display-main">
        <div class="counter-grid" id="counterGrid">
            <!-- Counters will be dynamically loaded here -->
        </div>
    </div>
    
    <div class="display-footer">
        <div class="marquee">
            <span id="tickerText">🚢 WELCOME TO PORT TERMINAL • PLEASE WAIT FOR YOUR NUMBER • PRIORITY LANES ACTIVE • KEEP YOUR TOKEN READY 🎫</span>
        </div>
        <span id="countdown" style="display:none;"></span>

        <!-- Branding -->
        <div style="
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 7px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 999px;
            padding: 5px 14px 5px 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.15);
        ">
            <span style="
                width: 20px; height: 20px;
                background: linear-gradient(135deg, #667eea 0%, #f093fb 100%);
                border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                font-size: 11px; flex-shrink: 0;
                box-shadow: 0 0 8px rgba(102,126,234,0.6);
            ">⚓</span>
            <span style="
                font-size: 11px;
                font-weight: 600;
                font-family: 'Poppins', system-ui, sans-serif;
                letter-spacing: 0.5px;
                color: rgba(255,255,255,0.55);
                white-space: nowrap;
            ">Developed by</span>
            <span style="
                font-size: 11px;
                font-weight: 800;
                font-family: 'Poppins', system-ui, sans-serif;
                letter-spacing: 0.5px;
                background: linear-gradient(135deg, #a78bfa, #f0abfc);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                white-space: nowrap;
            ">Ymath</span>
        </div>
    </div>
    
    <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    let countdown = 5;
    let countdownInterval;
    let lastAnnouncedToken = {};  // counter.id → last seen called_tokens_list string
    let _flashQueue    = [];       // [{token, counter}]
    let _flashRunning  = false;
    let _flashTimer    = null;
    let _flashBarTimer = null;

    // ── Flash queue (processes one-by-one with 4.5 s gap) ────────────────
    function enqueueFlash(newTokens, counterName) {
        newTokens.forEach(tk => _flashQueue.push({ token: tk, counter: counterName }));
        if (!_flashRunning) processFlashQueue();
    }
    function processFlashQueue() {
        if (_flashQueue.length === 0) { _flashRunning = false; return; }
        _flashRunning = true;
        const next = _flashQueue.shift();
        showFlash(next.token, '➜ ' + next.counter);
        setTimeout(processFlashQueue, 4500); // display duration + small gap
    }

    // ── Flash overlay ─────────────────────────────────────────────────────
    function showFlash(tokenNumber, counterName) {
        const overlay = document.getElementById('flashOverlay');
        document.getElementById('flashToken').textContent   = tokenNumber;
        document.getElementById('flashCounter').textContent = counterName;
        const bar = document.getElementById('flashBar');

        // Clear any previous timer
        if (_flashTimer)    clearTimeout(_flashTimer);
        if (_flashBarTimer) clearTimeout(_flashBarTimer);

        overlay.classList.add('show');

        // Animate progress bar from 0→100% over 4 s, then hide
        bar.style.transition = 'none';
        bar.style.width = '0%';
        // Force reflow
        bar.getBoundingClientRect();
        bar.style.transition = 'width 4s linear';
        bar.style.width = '100%';

        _flashTimer = setTimeout(() => {
            overlay.classList.remove('show');
            bar.style.width = '0%';
        }, 4000);
    }

    // Hide flash on click (manual dismiss)
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('flashOverlay').addEventListener('click', () => {
            document.getElementById('flashOverlay').classList.remove('show');
            if (_flashTimer) clearTimeout(_flashTimer);
        });
    });
    
    // Update date and time
    function updateDateTime() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        document.getElementById('datetime').textContent = now.toLocaleDateString('en-US', options);
    }
    
    // Load counter data
    async function loadDisplayData() {
        const connStatus = document.getElementById('connStatus');
        try {
            const response = await fetch(`${BASE_URL}/api/get-services.php?type=counter_status`);
            const result = await response.json();
            
            if (result.success) {
                connStatus.classList.remove('offline');
                connStatus.title = "System Online";
                displayCounters(result.data);
            }
        } catch (error) {
            console.error('Error loading display data:', error);
            connStatus.classList.add('offline');
            connStatus.title = "Connection Lost";
        }
    }
    
    // Status config for display board cards
    const DISP_STATUS = {
        available: {
            cardClass:  'available',
            badgeStyle: 'background:linear-gradient(135deg,rgba(148,163,184,0.3),rgba(148,163,184,0.2));color:#475569;',
            label:      'OPEN',
            idleIcon:   '⏳',
            idleText:   'Next',
            idleBg:     'rgba(0,0,0,0.08)'
        },
        serving: {
            cardClass:  'serving called',
            badgeStyle: 'background:linear-gradient(135deg,#10b981,#38ef7d);color:#fff;box-shadow:0 4px 12px rgba(16,185,129,0.35);',
            label:      'SERVING',
            idleIcon:   null,
            idleText:   null,
            idleBg:     null
        },
        break: {
            cardClass:  'on-break',
            badgeStyle: 'background:linear-gradient(135deg,#f59e0b,#f97316);color:#fff;box-shadow:0 4px 12px rgba(245,158,11,0.35);',
            label:      'ON BREAK',
            idleIcon:   '☕',
            idleText:   'On Break',
            idleBg:     'rgba(245,158,11,0.08)'
        },
        closed: {
            cardClass:  'closed-state',
            badgeStyle: 'background:linear-gradient(135deg,#94a3b8,#64748b);color:#fff;',
            label:      'CLOSED',
            idleIcon:   '🚫',
            idleText:   'Closed',
            idleBg:     'rgba(0,0,0,0.12)'
        }
    };

    function displayCounters(counters) {
        const grid = document.getElementById('counterGrid');

        // Detect newly called tokens (single OR mass call) and queue flashes
        counters.forEach(counter => {
            const cname      = counter.counter_name || 'Counter ' + counter.counter_number;
            const calledList = counter.called_tokens_list || '';
            const lastList   = lastAnnouncedToken[counter.id] || '';

            if (calledList !== lastList) {
                const lastSet   = new Set(lastList ? lastList.split(',') : []);
                const newTokens = calledList ? calledList.split(',').filter(t => t && !lastSet.has(t)) : [];
                if (newTokens.length) enqueueFlash(newTokens, cname);
                lastAnnouncedToken[counter.id] = calledList;
            } else if (!calledList) {
                lastAnnouncedToken[counter.id] = '';
            }
        });

        // Render counter cards
        grid.innerHTML = counters.map(counter => {
            const cfg = DISP_STATUS[counter.current_status] || DISP_STATUS.available;

            const durLine = (counter.current_status === 'serving' && counter.service_duration_minutes != null)
                ? `<div style="font-size:12px;color:#64748b;margin-top:6px;">⏱ ${counter.service_duration_minutes} min serving</div>`
                : '';

            // Mass-call mode: show all called tokens in the card
            const calledTokens = (counter.called_tokens_list || '').split(',').filter(Boolean);
            const isMultiCall  = calledTokens.length > 1;

            let rightPanel;
            if (isMultiCall) {
                rightPanel = `<div class="token-display-side" style="flex-direction:column;gap:6px;padding:12px;justify-content:center;">
                    <div class="token-label">NOW CALLING (${calledTokens.length})</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;justify-content:center;">
                        ${calledTokens.map(t =>
                            `<div style="background:linear-gradient(135deg,#f59e0b,#ef4444);color:#fff;font-weight:900;font-size:clamp(12px,1.8vw,20px);padding:5px 10px;border-radius:8px;letter-spacing:1px;">${t}</div>`
                        ).join('')}
                    </div>
                </div>`;
            } else if (counter.current_token) {
                rightPanel = `<div class="token-display-side">
                        <div class="token-label">Token Number</div>
                        <div class="token-number">${counter.current_token}</div>
                        <div class="service-name">${counter.current_service || ''}</div>
                        ${durLine}
                   </div>`;
            } else {
                rightPanel = `<div class="token-display-side" style="background:${cfg.idleBg}; justify-content:center; align-items:center;">
                        <div class="waiting-message" style="${counter.current_status==='break'?'color:#f59e0b;':counter.current_status==='closed'?'color:#94a3b8;':''}">
                            ${cfg.idleIcon}<br>${cfg.idleText}
                        </div>
                   </div>`;
            }
                    <div class="status-badge-display" style="${cfg.badgeStyle}">${cfg.label}</div>
                    <div class="counter-title">Counter</div>
                    <div class="counter-number">${counter.counter_number}</div>
                    <div class="staff-info">
                        <span class="status-dot"></span>
                        ${counter.counter_name || ''}
                    </div>
                </div>
                ${rightPanel}
            </div>`;
        }).join('');
    }
    
    // Countdown and refresh
    function startCountdown() {
        countdown = 5;
        document.getElementById('countdown').textContent = countdown;
        
        countdownInterval = setInterval(() => {
            countdown--;
            document.getElementById('countdown').textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                loadDisplayData();
                startCountdown();
            }
        }, 1000);
    }
    
    // Play notification sound (optional)
    function playNotificationSound() {
        // You can add an audio element and play it when new token is called
        // const audio = new Audio('notification.mp3');
        // audio.play();
    }
    
    // Load announcements into ticker
    async function loadAnnouncements() {
        try {
            const res = await fetch(`${BASE_URL}/api/queue-status.php?type=announcements&location=display`);
            const result = await res.json();
            const ticker = document.getElementById('tickerText');
            if (result.success && result.data && result.data.length > 0) {
                const parts = result.data.map(a => {
                    const icon = a.type === 'danger' ? '🚨' : a.type === 'warning' ? '⚠️' : a.type === 'success' ? '✅' : 'ℹ️';
                    return `${icon} ${a.title}: ${a.body}`;
                });
                ticker.textContent = parts.join('   •   ');
            } else {
                ticker.textContent = '🚢 WELCOME TO PORT TERMINAL • PLEASE WAIT FOR YOUR NUMBER • PRIORITY LANES ACTIVE • KEEP YOUR TOKEN READY 🎫';
            }
            // Adjust scroll speed: ~100px per second, min 15s, max 80s
            const speed = Math.min(80, Math.max(15, Math.round(ticker.textContent.length * 0.22)));
            ticker.style.animationDuration = speed + 's';
        } catch (e) {
            // silently keep existing text
        }
    }

    // Check service mode
    async function checkServiceMode() {
        try {
            const res = await fetch(`${BASE_URL}/api/service-mode.php`);
            const result = await res.json();
            const overlay = document.getElementById('maintenanceOverlay');
            if (result.success && result.data.mode === 'offline') {
                document.getElementById('maintenanceMsg').textContent = result.data.message || 'System temporarily offline.';
                overlay.style.display = 'flex';
            } else {
                overlay.style.display = 'none';
            }
        } catch (e) { /* silently ignore */ }
    }

    // Initialize
    updateDateTime();
    setInterval(updateDateTime, 1000);
    checkServiceMode();
    setInterval(checkServiceMode, 30000);
    loadDisplayData();
    loadAnnouncements();
    setInterval(loadAnnouncements, 10000);
    startCountdown();
    
    // Fullscreen on double-click
    document.body.addEventListener('dblclick', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    });
    </script>
</body>
</html>
