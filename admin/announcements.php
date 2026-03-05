<?php
/**
 * Admin - Announcements Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/PortManager.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

requireLogin();

if (!hasPermission('admin')) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$portManager = new PortManager();
$message = '';
$error   = '';

/* ── POST actions ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $rawStart = $_POST['starts_at'] ?: null;
        $rawEnd   = $_POST['ends_at']   ?: null;
        $data = [
            'title'        => trim($_POST['title'] ?? ''),
            'body'         => trim($_POST['body'] ?? ''),
            'type'         => $_POST['type'] ?? 'info',
            'show_customer'=> isset($_POST['show_customer']) ? 1 : 0,
            'show_display' => isset($_POST['show_display'])  ? 1 : 0,
            'is_active'    => isset($_POST['is_active'])     ? 1 : 0,
            'starts_at'    => $rawStart ? $rawStart . ' 00:00:00' : null,
            'ends_at'      => $rawEnd   ? $rawEnd   . ' 23:59:59' : null,
            'created_by'   => $_SESSION['user_id'] ?? null,
        ];
        if (empty($data['title'])) {
            $error = 'Title is required.';
        } else {
            $portManager->addAnnouncement($data);
            $message = 'Announcement added successfully.';
            AuditLogger::log('create', 'announcement', "Added announcement: '{$data['title']}'");
        }

    } elseif ($action === 'edit') {
        $id      = intval($_POST['id']);
        $rawStart = $_POST['starts_at'] ?: null;
        $rawEnd   = $_POST['ends_at']   ?: null;
        $data = [
            'title'        => trim($_POST['title'] ?? ''),
            'body'         => trim($_POST['body'] ?? ''),
            'type'         => $_POST['type'] ?? 'info',
            'show_customer'=> isset($_POST['show_customer']) ? 1 : 0,
            'show_display' => isset($_POST['show_display'])  ? 1 : 0,
            'is_active'    => isset($_POST['is_active'])     ? 1 : 0,
            'starts_at'    => $rawStart ? $rawStart . ' 00:00:00' : null,
            'ends_at'      => $rawEnd   ? $rawEnd   . ' 23:59:59' : null,
        ];
        if (empty($data['title'])) {
            $error = 'Title is required.';
        } else {
            $portManager->updateAnnouncement($id, $data);
            $message = 'Announcement updated successfully.';
            AuditLogger::log('update', 'announcement', "Updated announcement ID {$id}: '{$data['title']}'", $id);
        }

    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $portManager->deleteAnnouncement($id);
        $message = 'Announcement deleted.';
        AuditLogger::log('delete', 'announcement', "Deleted announcement ID {$id}", $id);

    } elseif ($action === 'toggle') {
        $id       = intval($_POST['id']);
        $newState = intval($_POST['is_active']) === 1 ? 0 : 1;
        $portManager->updateAnnouncement($id, ['is_active' => $newState]);
        $message = 'Status updated.';
        AuditLogger::log('update', 'announcement', "Toggled announcement ID {$id} to " . ($newState ? 'active' : 'inactive'), $id);
    }
}

$announcements = $portManager->getAllAnnouncements();

$pageTitle = 'Announcements';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">📢 Announcements</h1>
            <p class="text-gray-500 mt-1">Manage passenger notices and alerts</p>
        </div>
        <button onclick="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold transition-colors shadow-sm">
            + New Announcement
        </button>
    </div>

    <?php if ($message): ?>
        <div class="mb-4 p-4 bg-green-50 border border-green-300 rounded-xl text-green-800 font-medium"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-4 p-4 bg-red-50 border border-red-300 rounded-xl text-red-800 font-medium"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Announcements Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <?php if (empty($announcements)): ?>
            <div class="text-center py-16 text-gray-500">
                <div class="text-5xl mb-4">📭</div>
                <p class="text-lg font-medium">No announcements yet</p>
                <p class="text-sm mt-1">Click "+ New Announcement" to create one.</p>
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Title / Body</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Shown On</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Dates</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                    $typeStyles = [
                        'info'    => 'bg-blue-100 text-blue-700',
                        'warning' => 'bg-yellow-100 text-yellow-700',
                        'danger'  => 'bg-red-100 text-red-700',
                        'success' => 'bg-green-100 text-green-700',
                    ];
                    $typeIcons = [
                        'info'    => 'ℹ️',
                        'warning' => '⚠️',
                        'danger'  => '🚨',
                        'success' => '✅',
                    ];
                    foreach ($announcements as $ann):
                        $ts = $typeStyles[$ann['type']] ?? $typeStyles['info'];
                        $ti = $typeIcons[$ann['type']] ?? 'ℹ️';
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 max-w-xs">
                            <div class="font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($ann['title']); ?></div>
                            <div class="text-gray-500 text-xs mt-0.5 line-clamp-2"><?php echo htmlspecialchars(substr($ann['body'], 0, 100)); ?><?php echo strlen($ann['body']) > 100 ? '…' : ''; ?></div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $ts; ?>">
                                <?php echo $ti; ?> <?php echo ucfirst($ann['type']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex justify-center gap-1 flex-wrap">
                                <?php if ($ann['show_customer']): ?>
                                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs font-medium">Customer</span>
                                <?php endif; ?>
                                <?php if ($ann['show_display']): ?>
                                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs font-medium">Display</span>
                                <?php endif; ?>
                                <?php if (!$ann['show_customer'] && !$ann['show_display']): ?>
                                    <span class="text-gray-400 text-xs">None</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-gray-600 text-xs whitespace-nowrap">
                            <?php if ($ann['starts_at']): ?>
                                <div>From: <?php echo date('M j, Y', strtotime($ann['starts_at'])); ?></div>
                            <?php endif; ?>
                            <?php if ($ann['ends_at']): ?>
                                <div>Until: <?php echo date('M j, Y', strtotime($ann['ends_at'])); ?></div>
                            <?php endif; ?>
                            <?php if (!$ann['starts_at'] && !$ann['ends_at']): ?>
                                <span class="text-gray-400">No limit</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $ann['id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $ann['is_active']; ?>">
                                <button type="submit" class="px-3 py-1 rounded-full text-xs font-semibold transition-colors
                                    <?php echo $ann['is_active'] ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'; ?>">
                                    <?php echo $ann['is_active'] ? '● Active' : '○ Inactive'; ?>
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex justify-center gap-2">
                                <button onclick='openModal(<?php echo htmlspecialchars(json_encode($ann), ENT_QUOTES); ?>)'
                                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-medium transition-colors">
                                    Edit
                                </button>
                                <form method="POST" onsubmit="return confirm('Delete this announcement?');" class="inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $ann['id']; ?>">
                                    <button type="submit" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs font-medium transition-colors">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="annModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800" id="modalTitle">New Announcement</h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <form method="POST" class="p-6 space-y-5">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id"     id="formId"     value="">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="formTitle" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:border-indigo-500 focus:ring focus:ring-indigo-200 outline-none transition"
                       placeholder="e.g. Trip delay notice">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Message Body</label>
                <textarea name="body" id="formBody" rows="4"
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:border-indigo-500 focus:ring focus:ring-indigo-200 outline-none transition"
                          placeholder="Full announcement text…"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Type</label>
                    <select name="type" id="formType"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:border-indigo-500 outline-none">
                        <option value="info">ℹ️ Info</option>
                        <option value="warning">⚠️ Warning</option>
                        <option value="danger">🚨 Danger</option>
                        <option value="success">✅ Success</option>
                    </select>
                </div>
                <div class="flex flex-col justify-end gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" id="formActive" value="1" class="w-4 h-4 rounded text-indigo-600" checked>
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="show_customer" id="formShowCustomer" value="1" class="w-4 h-4 rounded text-indigo-600" checked>
                        <span class="text-sm font-medium text-gray-700">Show on Customer Board</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="show_display" id="formShowDisplay" value="1" class="w-4 h-4 rounded text-indigo-600">
                        <span class="text-sm font-medium text-gray-700">Show on Display Board</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Start Date (optional)</label>
                    <input type="date" name="starts_at" id="formStartsAt"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">End Date (optional)</label>
                    <input type="date" name="ends_at" id="formEndsAt"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:border-indigo-500 outline-none">
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors">
                    Save Announcement
                </button>
                <button type="button" onclick="closeModal()" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(ann) {
    const modal = document.getElementById('annModal');
    if (ann) {
        // Edit mode
        document.getElementById('modalTitle').textContent = 'Edit Announcement';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('formId').value     = ann.id;
        document.getElementById('formTitle').value  = ann.title;
        document.getElementById('formBody').value   = ann.body;
        document.getElementById('formType').value   = ann.type;
        document.getElementById('formActive').checked       = ann.is_active == 1;
        document.getElementById('formShowCustomer').checked = ann.show_customer == 1;
        document.getElementById('formShowDisplay').checked  = ann.show_display == 1;
        document.getElementById('formStartsAt').value = ann.starts_at ? ann.starts_at.substring(0,10) : '';
        document.getElementById('formEndsAt').value   = ann.ends_at   ? ann.ends_at.substring(0,10)   : '';
    } else {
        // Add mode
        document.getElementById('modalTitle').textContent = 'New Announcement';
        document.getElementById('formAction').value = 'add';
        document.getElementById('formId').value     = '';
        document.getElementById('formTitle').value  = '';
        document.getElementById('formBody').value   = '';
        document.getElementById('formType').value   = 'info';
        document.getElementById('formActive').checked       = true;
        document.getElementById('formShowCustomer').checked = true;
        document.getElementById('formShowDisplay').checked  = false;
        document.getElementById('formStartsAt').value = '';
        document.getElementById('formEndsAt').value   = '';
    }
    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('annModal').classList.add('hidden');
}

// Close modal on outside click
document.getElementById('annModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
