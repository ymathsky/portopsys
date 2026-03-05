<?php
/**
 * User Manager Class
 * Handles Admin Users
 */

require_once __DIR__ . '/../config/database.php';

class UserManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllUsers() {
        $stmt = $this->db->query("
            SELECT u.*, sc.counter_name 
            FROM admin_users u
            LEFT JOIN service_counters sc ON u.assigned_counter_id = sc.id
            ORDER BY u.username ASC
        ");
        return $stmt->fetchAll();
    }

    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function addUser($username, $password, $fullName, $email, $role, $counterId = null) {
        // Hash password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO admin_users (username, password_hash, full_name, email, role, assigned_counter_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$username, $hash, $fullName, $email, $role, $counterId]);
    }

    public function updateUser($id, $username, $fullName, $email, $role, $counterId = null, $password = null) {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("
                UPDATE admin_users 
                SET username = ?, full_name = ?, email = ?, role = ?, assigned_counter_id = ?, password_hash = ?
                WHERE id = ?
            ");
            return $stmt->execute([$username, $fullName, $email, $role, $counterId, $hash, $id]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE admin_users 
                SET username = ?, full_name = ?, email = ?, role = ?, assigned_counter_id = ?
                WHERE id = ?
            ");
            return $stmt->execute([$username, $fullName, $email, $role, $counterId, $id]);
        }
    }

    public function deleteUser($id) {
        // Prevent deleting last super admin could be a nice check, but for now simple delete
        $stmt = $this->db->prepare("DELETE FROM admin_users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Set (or change) a user's status PIN.
     * Returns ['success'=>bool, 'message'=>string]
     */
    public function setPin($userId, $newPin) {
        if (strlen(trim($newPin)) < 4) {
            return ['success' => false, 'message' => 'PIN must be at least 4 characters.'];
        }
        $hash = password_hash($newPin, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            "UPDATE admin_users SET status_pin_hash = ?, status_pin_set_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$hash, $userId]);
        return ['success' => true, 'message' => 'Status PIN updated successfully.'];
    }

    /**
     * Verify the entered PIN against the stored hash for a user.
     * Falls back to the global STATUS_CHANGE_PIN if no personal PIN is set.
     */
    public function verifyPin($userId, $enteredPin) {
        $stmt = $this->db->prepare("SELECT status_pin_hash FROM admin_users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) return false;

        if (!empty($row['status_pin_hash'])) {
            return password_verify($enteredPin, $row['status_pin_hash']);
        }
        // Fall back to global constant
        return defined('STATUS_CHANGE_PIN') && $enteredPin === STATUS_CHANGE_PIN;
    }

    /**
     * Check whether a user has set a personal PIN.
     */
    public function hasPinSet($userId) {
        $stmt = $this->db->prepare("SELECT status_pin_hash, status_pin_set_at FROM admin_users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row && !empty($row['status_pin_hash'])
            ? ['set' => true, 'since' => $row['status_pin_set_at']]
            : ['set' => false, 'since' => null];
    }

    /**
     * Admin-only: reset a user's PIN (no old PIN required).
     */
    public function adminResetPin($targetUserId, $newPin) {
        return $this->setPin($targetUserId, $newPin);
    }

    /**
     * Clear (remove) a user's personal PIN so they fall back to the global constant.
     */
    public function clearPin($userId) {
        $stmt = $this->db->prepare(
            "UPDATE admin_users SET status_pin_hash = NULL, status_pin_set_at = NULL WHERE id = ?"
        );
        $stmt->execute([$userId]);
        return ['success' => true, 'message' => 'Status PIN cleared.'];
    }
}
