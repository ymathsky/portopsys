<?php
/**
 * Manage System Users
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/UserManager.php';
require_once __DIR__ . '/../includes/ServiceManager.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

requireLogin();

// Check if user is super_admin
if (!hasPermission('super_admin')) {
    echo "Access Denied";
    exit;
}

$userManager = new UserManager();
$serviceManager = new ServiceManager();
$counters = $serviceManager->getCounters();

// Handle Form Submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        try {
            if ($action === 'add') {
                $userManager->addUser(
                    $_POST['username'],
                    $_POST['password'],
                    $_POST['full_name'],
                    $_POST['email'],
                    $_POST['role'],
                    !empty($_POST['assigned_counter_id']) ? $_POST['assigned_counter_id'] : null
                );
                $message = 'User added successfully';
                AuditLogger::log('create', 'user', "Created user '{$_POST['username']}' with role '{$_POST['role']}'");
            } elseif ($action === 'delete') {
                $userManager->deleteUser($_POST['id']);
                $message = 'User deleted successfully';
                AuditLogger::log('delete', 'user', "Deleted user ID {$_POST['id']}", (int)$_POST['id']);
            } elseif ($action === 'edit') {
                 $userManager->updateUser(
                    $_POST['id'],
                    $_POST['username'],
                    $_POST['full_name'],
                    $_POST['email'],
                    $_POST['role'],
                    !empty($_POST['assigned_counter_id']) ? $_POST['assigned_counter_id'] : null,
                    !empty($_POST['password']) ? $_POST['password'] : null
                );
                $message = 'User updated successfully';
                AuditLogger::log('update', 'user', "Updated user '{$_POST['username']}' (ID {$_POST['id']})", (int)$_POST['id']);
                $targetId = (int)$_POST['id'];
                $newPin   = trim($_POST['new_pin'] ?? '');
                $confPin  = trim($_POST['confirm_pin'] ?? '');
                if (empty($newPin)) {
                    $error = 'PIN cannot be empty.';
                } elseif ($newPin !== $confPin) {
                    $error = 'PINs do not match.';
                } else {
                    $result  = $userManager->adminResetPin($targetId, $newPin);
                    $message = $result['success'] ? 'PIN reset successfully for user.' : $result['message'];
                    if ($result['success']) AuditLogger::log('pin_change', 'user', "Admin reset status PIN for user ID {$targetId}", $targetId);
                    if (!$result['success']) $error = $result['message'];
                }
            } elseif ($action === 'clear_pin') {
                $result  = $userManager->clearPin((int)$_POST['id']);
                $message = $result['success'] ? 'User PIN cleared; they will use the global PIN.' : $result['message'];
                if ($result['success']) AuditLogger::log('pin_change', 'user', "Admin cleared status PIN for user ID {$_POST['id']}", (int)$_POST['id']);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$users = $userManager->getAllUsers();
$pageTitle = 'Manage System Users';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <span class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white">👥</span>
                Manage System Users
            </h1>
            <p class="text-gray-600 mt-1">Create and manage admin users and permissions</p>
        </div>
        <button onclick="openModal('addUserModal')" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition font-medium shadow-md">
            <span class="inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add New User
            </span>
        </button>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-start">
            <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-start">
            <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Full Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Assigned Counter</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Last Login</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">PIN</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?php 
                            echo match($user['role']) {
                                'super_admin' => 'bg-purple-100 text-purple-800',
                                'admin' => 'bg-blue-100 text-blue-800',
                                'counter_staff' => 'bg-green-100 text-green-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $user['counter_name'] ? htmlspecialchars($user['counter_name']) : '<span class="text-gray-400">-</span>'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : '<span class="text-gray-400">Never</span>'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if (!empty($user['status_pin_hash'])): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-semibold border border-green-200">&#x2705; Set</span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-xs font-semibold border border-gray-200">&#x26A0; Global</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex gap-2 flex-wrap">
                                <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition">
                                    Edit
                                </button>
                                <button onclick="openResetPinModal(<?php echo $user['id']; ?>, '<?php echo addslashes(htmlspecialchars($user['username'])); ?>', <?php echo !empty($user['status_pin_hash']) ? 'true' : 'false'; ?>)"
                                    class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 transition">
                                    &#x1F511; PIN
                                </button>
                                <?php if ($user['role'] !== 'super_admin' || $user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="px-3 py-1 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                                        Delete
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between">
            <h2 id="modalTitle" class="text-xl font-bold text-white">Add New User</h2>
            <button onclick="closeModal('addUserModal')" class="text-white hover:text-gray-200 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form method="POST">
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="userId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                        <input type="text" name="username" id="username" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="full_name" id="fullName" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" id="email"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select name="role" id="role" onchange="toggleCounterSelect()"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="counter_staff">Counter Staff</option>
                            <option value="admin">Administrator</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    
                    <div id="counterSelectGroup">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Assign to Counter</label>
                        <select name="assigned_counter_id" id="assignedCounter"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">-- No Assignment --</option>
                            <?php foreach ($counters as $counter): ?>
                                <option value="<?php echo $counter['id']; ?>">
                                    <?php echo htmlspecialchars($counter['counter_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mt-4">
                    <label id="passwordLabel" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" id="password"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <p id="passwordHelp" class="text-xs text-gray-500 mt-1" style="display:none">Leave blank to keep existing password</p>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                <button type="button" onclick="closeModal('addUserModal')" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                    Cancel
                </button>
                <button type="submit" id="submitBtn" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition font-medium shadow-md">
                    Save User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    const modal = document.getElementById(id);
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    // Reset form
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').innerText = 'Add New User';
    document.getElementById('submitBtn').innerText = 'Save User';
    document.getElementById('userId').value = '';
    document.getElementById('username').value = '';
    document.getElementById('fullName').value = '';
    document.getElementById('email').value = '';
    document.getElementById('role').value = 'counter_staff';
    document.getElementById('assignedCounter').value = '';
    document.getElementById('password').value = '';
    document.getElementById('password').required = true;
    document.getElementById('passwordHelp').style.display = 'none';
    toggleCounterSelect();
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    document.body.style.overflow = 'auto';
}

function editUser(user) {
    openModal('addUserModal');
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').innerText = 'Edit User';
    document.getElementById('submitBtn').innerText = 'Update User';
    
    document.getElementById('userId').value = user.id;
    document.getElementById('username').value = user.username;
    document.getElementById('fullName').value = user.full_name;
    document.getElementById('email').value = user.email;
    document.getElementById('role').value = user.role;
    document.getElementById('assignedCounter').value = user.assigned_counter_id || '';
    
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('passwordHelp').style.display = 'block';
    toggleCounterSelect();
}

function toggleCounterSelect() {
    const role = document.getElementById('role').value;
    const group = document.getElementById('counterSelectGroup');
    if (role === 'counter_staff') {
        group.style.display = 'block';
    } else {
        group.style.display = 'none';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Reset PIN Modal
function openResetPinModal(userId, username, hasPin) {
    document.getElementById('resetPinUserId').value = userId;
    document.getElementById('resetPinUsername').textContent = username;
    document.getElementById('resetPinHasPinMsg').classList.toggle('hidden', !hasPin);
    document.getElementById('resetPinNoPinMsg').classList.toggle('hidden', hasPin);
    document.getElementById('newPinInput').value = '';
    document.getElementById('confPinInput').value = '';
    document.getElementById('pinMismatch').classList.add('hidden');
    const modal = document.getElementById('resetPinModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('newPinInput').focus(), 80);
}

function closeResetPinModal() {
    document.getElementById('resetPinModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function submitResetPin() {
    const a = document.getElementById('newPinInput').value;
    const b = document.getElementById('confPinInput').value;
    if (!a || !b) return;
    if (a !== b) {
        document.getElementById('pinMismatch').classList.remove('hidden');
        return;
    }
    document.getElementById('resetPinForm').submit();
}
</script>

<!-- Reset PIN Modal -->
<div id="resetPinModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-yellow-500 to-orange-500 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">&#x1F511; Reset Status PIN</h2>
            <button onclick="closeResetPinModal()" class="text-white hover:text-yellow-100 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 mb-4">
                Setting a new PIN for <strong id="resetPinUsername" class="text-gray-900"></strong>.
            </p>
            <div id="resetPinHasPinMsg" class="hidden mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-xs text-green-700">
                &#x2705; This user already has a personal PIN. This will replace it.
            </div>
            <div id="resetPinNoPinMsg" class="hidden mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
                &#x26A0; This user has no personal PIN yet (using global PIN). Setting one here will override it.
            </div>
            <form id="resetPinForm" method="POST">
                <input type="hidden" name="action" value="reset_pin">
                <input type="hidden" name="id" id="resetPinUserId">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">New PIN</label>
                    <input type="password" id="newPinInput" name="new_pin" inputmode="numeric"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-center tracking-widest text-lg focus:outline-none focus:ring-2 focus:ring-yellow-400"
                        placeholder="e.g. 5678" oninput="document.getElementById('pinMismatch').classList.add('hidden')">
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm PIN</label>
                    <input type="password" id="confPinInput" name="confirm_pin" inputmode="numeric"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-center tracking-widest text-lg focus:outline-none focus:ring-2 focus:ring-yellow-400"
                        placeholder="Repeat PIN"
                        onkeydown="if(event.key==='Enter') submitResetPin()">
                    <p id="pinMismatch" class="hidden mt-1.5 text-xs text-red-600 font-medium">&#x26A0; PINs do not match.</p>
                </div>
            </form>
        </div>
        <div class="bg-gray-50 px-6 py-4 flex gap-3 border-t border-gray-200">
            <button onclick="closeResetPinModal()" class="flex-1 px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-100 transition">Cancel</button>
            <button onclick="submitResetPin()" class="flex-1 px-4 py-2 bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-lg text-sm font-semibold hover:from-yellow-600 hover:to-orange-600 transition shadow">Save PIN</button>
        </div>
        <!-- Clear PIN sub-form (separate submit) -->
        <div class="px-6 pb-5 pt-1 flex items-center justify-between">
            <span class="text-xs text-gray-400">Remove personal PIN and revert to global?</span>
            <form method="POST" onsubmit="return confirm('Clear this user\'s personal PIN?')">
                <input type="hidden" name="action" value="clear_pin">
                <input type="hidden" name="id" id="clearPinUserId">
                <button type="button" onclick="
                    document.getElementById('clearPinUserId').value = document.getElementById('resetPinUserId').value;
                    this.closest('form').submit();
                " class="text-xs text-red-500 hover:text-red-700 underline font-medium transition">Clear PIN</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
