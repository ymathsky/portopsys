<?php
/**
 * My Profile — Change Password & Status PIN
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/UserManager.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

requireLogin();

$userManager = new UserManager();
$auth        = new Auth();
$userId      = $_SESSION['user_id'];
$user        = $userManager->getUserById($userId);
$pinInfo     = $userManager->hasPinSet($userId);

$message = '';
$error   = '';
$section = ''; // 'password' | 'pin'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── Change Password ─────────────────────────────────────────────── */
    if ($action === 'change_password') {
        $section = 'password';
        $old  = $_POST['old_password']  ?? '';
        $new  = $_POST['new_password']  ?? '';
        $conf = $_POST['confirm_password'] ?? '';

        if (empty($old) || empty($new) || empty($conf)) {
            $error = 'All password fields are required.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $conf) {
            $error = 'New passwords do not match.';
        } else {
            $result = $auth->changePassword($userId, $old, $new);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }

    /* ── Set / Change Status PIN ─────────────────────────────────────── */
    elseif ($action === 'change_pin') {
        $section = 'pin';
        $password = $_POST['confirm_password_pin'] ?? '';
        $newPin   = $_POST['new_pin']     ?? '';
        $confPin  = $_POST['confirm_pin'] ?? '';

        if (empty($password)) {
            $error = 'Please confirm your account password.';
        } elseif (!password_verify($password, $user['password_hash'])) {
            $error = 'Account password is incorrect.';
        } elseif (empty($newPin) || empty($confPin)) {
            $error = 'Both PIN fields are required.';
        } elseif ($newPin !== $confPin) {
            $error = 'PINs do not match.';
        } else {
            $result = $userManager->setPin($userId, $newPin);
            if ($result['success']) {
                $message = $result['message'];
                AuditLogger::log('pin_change', 'auth', "User '{$_SESSION['username']}' updated their status PIN", $userId);
                $pinInfo = $userManager->hasPinSet($userId); // refresh
            } else {
                $error = $result['message'];
            }
        }
    }

    /* ── Clear Status PIN ────────────────────────────────────────────── */
    elseif ($action === 'clear_pin') {
        $section = 'pin';
        $password = $_POST['confirm_password_clear'] ?? '';
        if (empty($password)) {
            $error = 'Please confirm your account password to clear PIN.';
        } elseif (!password_verify($password, $user['password_hash'])) {
            $error = 'Account password is incorrect.';
        } else {
            $result = $userManager->clearPin($userId);
            $message = $result['message'] . ' You will fall back to the global PIN.';
            AuditLogger::log('pin_change', 'auth', "User '{$_SESSION['username']}' cleared their status PIN", $userId);
            $pinInfo = $userManager->hasPinSet($userId);
        }
    }
}

$pageTitle = 'My Profile';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full max-w-3xl mx-auto">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-8">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl font-black shadow-lg shadow-indigo-500/30">
            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
        </div>
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></h1>
            <p class="text-sm text-gray-500">
                @<?php echo htmlspecialchars($user['username']); ?>
                &bull;
                <span class="font-medium text-indigo-600"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span>
                <?php if ($user['last_login']): ?>
                    &bull; Last login <?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($message): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl flex items-start gap-3">
        <svg class="w-5 h-5 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl flex items-start gap-3">
        <svg class="w-5 h-5 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6">

        <!-- ── Change Password Card ─────────────────────────────────── -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600 text-lg">&#x1F512;</div>
                <div>
                    <h2 class="font-bold text-gray-900">Change Password</h2>
                    <p class="text-xs text-gray-500">Used to log in to the admin portal</p>
                </div>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="change_password">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                    <input type="password" name="old_password" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                        <input type="password" name="new_password" id="newPwd" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="Min. 6 characters" oninput="checkPwdMatch()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confPwd" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="Repeat password" oninput="checkPwdMatch()">
                        <p id="pwdMatchHint" class="mt-1 text-xs hidden"></p>
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl text-sm font-semibold hover:from-indigo-700 hover:to-purple-700 transition shadow-md hover:shadow-lg">
                        Update Password
                    </button>
                </div>
            </form>
        </div>

        <!-- ── Status PIN Card ───────────────────────────────────────── -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-yellow-100 flex items-center justify-center text-yellow-600 text-lg">&#x1F511;</div>
                    <div>
                        <h2 class="font-bold text-gray-900">Status Change PIN</h2>
                        <p class="text-xs text-gray-500">Required when updating live trip status on the Schedules page</p>
                    </div>
                </div>
                <?php if ($pinInfo['set']): ?>
                <span class="flex items-center gap-1.5 text-xs font-semibold text-green-700 bg-green-100 px-3 py-1 rounded-full border border-green-200">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                    PIN Set &mdash; <?php echo date('M d, Y', strtotime($pinInfo['since'])); ?>
                </span>
                <?php else: ?>
                <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-500 bg-gray-100 px-3 py-1 rounded-full border border-gray-200">
                    &#x26A0; Using global PIN
                </span>
                <?php endif; ?>
            </div>

            <?php if (!$pinInfo['set']): ?>
            <div class="mx-6 mt-5 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-start gap-2">
                <span class="text-base leading-none">&#x26A0;</span>
                <span>You are currently using the <strong>global PIN</strong> from the system config. Setting a personal PIN overrides it for your account only.</span>
            </div>
            <?php endif; ?>

            <!-- Set / Change PIN form -->
            <form method="POST" class="p-6 space-y-4 border-b border-gray-100">
                <input type="hidden" name="action" value="change_pin">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Confirm Account Password <span class="text-gray-400 font-normal">(required to change PIN)</span>
                    </label>
                    <input type="password" name="confirm_password_pin" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent"
                        placeholder="Your login password">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">New PIN</label>
                        <input type="password" name="new_pin" id="newPin" inputmode="numeric" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-center tracking-widest text-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent"
                            placeholder="e.g. 5678" oninput="checkPinMatch()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New PIN</label>
                        <input type="password" name="confirm_pin" id="confPin" inputmode="numeric" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm text-center tracking-widest text-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent"
                            placeholder="Repeat PIN" oninput="checkPinMatch()">
                        <p id="pinMatchHint" class="mt-1 text-xs hidden"></p>
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-xl text-sm font-semibold hover:from-yellow-600 hover:to-orange-600 transition shadow-md hover:shadow-lg">
                        <?php echo $pinInfo['set'] ? 'Update PIN' : 'Set PIN'; ?>
                    </button>
                </div>
            </form>

            <!-- Clear PIN (only if one is set) -->
            <?php if ($pinInfo['set']): ?>
            <form method="POST" class="p-6 flex flex-col sm:flex-row sm:items-center gap-4">
                <input type="hidden" name="action" value="clear_pin">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Account Password <span class="text-gray-400 font-normal">(to confirm removal)</span>
                    </label>
                    <input type="password" name="confirm_password_clear" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent"
                        placeholder="Your login password">
                </div>
                <div class="shrink-0 pt-5 sm:pt-0 mt-0 sm:mt-6">
                    <button type="submit"
                        onclick="return confirm('Remove your personal PIN and fall back to the global PIN?')"
                        class="px-5 py-2.5 border border-red-200 text-red-600 rounded-xl text-sm font-semibold hover:bg-red-50 hover:border-red-300 transition">
                        Remove PIN
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>

    </div><!-- /grid -->
</div>

<script>
function checkPwdMatch() {
    const a = document.getElementById('newPwd').value;
    const b = document.getElementById('confPwd').value;
    const hint = document.getElementById('pwdMatchHint');
    if (!b) { hint.classList.add('hidden'); return; }
    if (a === b) {
        hint.className = 'mt-1 text-xs text-green-600';
        hint.textContent = '&#x2714; Passwords match';
        hint.innerHTML = '&#x2714; Passwords match';
    } else {
        hint.className = 'mt-1 text-xs text-red-600';
        hint.innerHTML = '&#x2718; Passwords do not match';
    }
    hint.classList.remove('hidden');
}

function checkPinMatch() {
    const a = document.getElementById('newPin').value;
    const b = document.getElementById('confPin').value;
    const hint = document.getElementById('pinMatchHint');
    if (!b) { hint.classList.add('hidden'); return; }
    if (a === b) {
        hint.className = 'mt-1 text-xs text-green-600';
        hint.innerHTML = '&#x2714; PINs match';
    } else {
        hint.className = 'mt-1 text-xs text-red-600';
        hint.innerHTML = '&#x2718; PINs do not match';
    }
    hint.classList.remove('hidden');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
