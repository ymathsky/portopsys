<?php
/**
 * Authentication Class
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/AuditLogger.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, password_hash, full_name, email, role, assigned_counter_id
                FROM admin_users 
                WHERE username = ? AND is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Update last login
            $stmt = $this->db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['assigned_counter_id'] = $user['assigned_counter_id'];
            
            return ['success' => true, 'message' => 'Login successful', 'user' => $user];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return true;
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            // Verify old password
            $stmt = $this->db->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Update password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$newHash, $userId]);
            
            AuditLogger::log('password_change', 'auth', "Password changed for user ID {$userId}", $userId);
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error changing password: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create new user
     */
    public function createUser($username, $password, $fullName, $email, $role, $assignedCounterId = null) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("
                INSERT INTO admin_users (username, password_hash, full_name, email, role, assigned_counter_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$username, $passwordHash, $fullName, $email, $role, $assignedCounterId]);
            
            return ['success' => true, 'message' => 'User created successfully', 'user_id' => $this->db->lastInsertId()];
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
            return ['success' => false, 'message' => 'Error creating user: ' . $e->getMessage()];
        }
    }
}
