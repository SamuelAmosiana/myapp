<?php
require_once __DIR__ . '/../config/Database.php';

class UserManager {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllUsers() {
        $sql = "SELECT u.*, r.role_name as role, c.course_name, c.course_code 
               FROM users u 
               LEFT JOIN roles r ON u.role_id = r.role_id 
               LEFT JOIN courses c ON u.course_id = c.course_id 
               ORDER BY u.name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getUserById($user_id) {
        $sql = "SELECT * FROM users WHERE user_id = ?";
        return $this->db->fetchOne($sql, [$user_id]);
    }

    public function addUser($name, $email, $password, $role, $course_id = null) {
        try {
            // Convert role name to role_id if needed
            $role_id = $role;
            if (!is_numeric($role)) {
                $roleData = $this->db->fetchOne("SELECT role_id FROM roles WHERE role_name = ?", [$role]);
                if (!$roleData) {
                    throw new Exception("Invalid role: $role");
                }
                $role_id = $roleData['role_id'];
            }
            
            // Check if email already exists
            $existingUser = $this->db->fetchOne("SELECT user_id FROM users WHERE email = ?", [$email]);
            if ($existingUser) {
                throw new Exception("Email already exists");
            }
            
            $data = [
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role_id' => $role_id,
                'course_id' => $course_id,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            return $this->db->insert('users', $data);
            
        } catch (Exception $e) {
            error_log("Add user error: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser($user_id, $name, $email, $role, $course_id = null) {
        try {
            // Simple role conversion - handle common role names
            $roleMap = [
                'admin' => 1,
                'lecturer' => 2, 
                'class_rep' => 3,
                'student' => 4
            ];
            
            $role_id = isset($roleMap[$role]) ? $roleMap[$role] : $role;
            
            // Prepare minimal update data (only essential fields)
            $data = [
                'name' => $name,
                'email' => $email,
                'role_id' => $role_id
            ];
            
            // Add course_id only if provided
            if (!empty($course_id)) {
                $data['course_id'] = $course_id;
            }
            
            // Simple direct update
            $sql = "UPDATE users SET name = ?, email = ?, role_id = ?" . 
                   (!empty($course_id) ? ", course_id = ?" : "") . 
                   " WHERE user_id = ?";
            
            $params = [$name, $email, $role_id];
            if (!empty($course_id)) {
                $params[] = $course_id;
            }
            $params[] = $user_id;
            
            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            // Silent fail for presentation
            return false;
        }
    }

    public function deleteUser($user_id) {
        try {
            // Check if user exists
            $user = $this->getUserById($user_id);
            if (!$user) {
                throw new Exception("User not found");
            }
            
            // Check for dependencies - users with bookings, notifications, etc.
            $dependencies = [];
            
            // Check bookings
            $bookingCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM bookings WHERE booked_by = ? OR lecturer_id = ?", [$user_id, $user_id]);
            if ($bookingCount['count'] > 0) {
                $dependencies[] = $bookingCount['count'] . ' booking(s)';
            }
            
            // Check notifications
            $notificationCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?", [$user_id]);
            if ($notificationCount['count'] > 0) {
                $dependencies[] = $notificationCount['count'] . ' notification(s)';
            }
            
            // If there are dependencies, don't delete - just deactivate
            if (!empty($dependencies)) {
                // Deactivate user instead of deleting
                $result = $this->db->update('users', 
                    ['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')], 
                    'user_id = ?', 
                    [$user_id]
                );
                
                if ($result) {
                    return ['success' => true, 'message' => 'User deactivated due to existing ' . implode(', ', $dependencies) . '. User cannot be permanently deleted.'];
                } else {
                    throw new Exception("Failed to deactivate user");
                }
            } else {
                // Safe to delete - no dependencies
                $result = $this->db->delete('users', 'user_id = ?', [$user_id]);
                if ($result) {
                    return ['success' => true, 'message' => 'User deleted successfully'];
                } else {
                    throw new Exception("Failed to delete user");
                }
            }
            
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}