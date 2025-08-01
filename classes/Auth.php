<?php
/**
 * Updated Authentication System for ClassReserve CHAU
 * File: classes/Auth.php
 */

require_once __DIR__ . '/../config/Database.php';

class Auth {
    private $db;
    
    public function __construct() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = Database::getInstance();
    }
    
    /**
     * Login user using Student ID as both username and password (for students/class reps)
     * @param string $student_id
     * @param string $role
     * @return array Result with success status and message
     */
    public function login($student_id, $role = null) {
        try {
            $student_id = trim($student_id);
            if (empty($student_id)) {
                return [
                    'success' => false,
                    'message' => 'Student ID is required'
                ];
            }
            // Find user by student ID and role
            $query = "SELECT u.user_id, u.name, u.email, u.student_id, u.course_id, 
                            u.intake, u.year_of_study, u.department, u.is_active,
                            r.role_name, r.role_id,
                            c.course_name, c.course_code,
                            p.program_name, p.program_type,
                            u.password_hash
                     FROM users u
                     JOIN roles r ON u.role_id = r.role_id
                     LEFT JOIN courses c ON u.course_id = c.course_id
                     LEFT JOIN programs p ON c.program_id = p.program_id
                     WHERE u.student_id = :student_id AND r.role_name = :role_name AND u.is_active = 1";
            $user = $this->db->fetchOne($query, ['student_id' => $student_id, 'role_name' => $role]);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Invalid Student ID or role. Please check and try again.'
                ];
            }
            // For students/class reps, Student ID is used as password as well
            if ($user['student_id'] !== $student_id) {
                return [
                    'success' => false,
                    'message' => 'Invalid Student ID or password.'
                ];
            }
            $this->createSession($user);
            $this->updateLastLogin($user['user_id']);
            return [
                'success' => true,
                'message' => 'Login successful! Welcome ' . $user['name'],
                'user' => $user,
                'redirect' => $this->getRedirectUrl($user['role_name'])
            ];
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ];
        }
    }

    /**
     * Login user using email and password (for admin/lecturer)
     * @param string $email
     * @param string $password
     * @param string $role
     * @return array
     */
    public function loginWithEmail($email, $password, $role) {
        try {
            $email = trim($email);
            if (empty($email) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Email and password are required.'
                ];
            }
            // Find user by email and role
            $query = "SELECT u.user_id, u.name, u.email, u.student_id, u.course_id, 
                            u.intake, u.year_of_study, u.department, u.is_active,
                            r.role_name, r.role_id,
                            c.course_name, c.course_code,
                            p.program_name, p.program_type,
                            u.password_hash
                     FROM users u
                     JOIN roles r ON u.role_id = r.role_id
                     LEFT JOIN courses c ON u.course_id = c.course_id
                     LEFT JOIN programs p ON c.program_id = p.program_id
                     WHERE u.email = :email AND r.role_name = :role_name AND u.is_active = 1";
            $user = $this->db->fetchOne($query, ['email' => $email, 'role_name' => $role]);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Invalid email, password, or role.'
                ];
            }
            // For admin/lecturer, compare password directly (stored as plain text in DB)
            if ($user['password_hash'] !== $password) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password.'
                ];
            }
            $this->createSession($user);
            $this->updateLastLogin($user['user_id']);
            return [
                'success' => true,
                'message' => 'Login successful! Welcome ' . $user['name'],
                'user' => $user,
                'redirect' => $this->getRedirectUrl($user['role_name'])
            ];
        } catch (Exception $e) {
            error_log("LoginWithEmail error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ];
        }
    }
    
    /**
     * Create user session
     * @param array $user
     */
    private function createSession($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['student_id'] = $user['student_id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['course_id'] = $user['course_id'];
        $_SESSION['course_name'] = $user['course_name'];
        $_SESSION['course_code'] = $user['course_code'];
        $_SESSION['program_name'] = $user['program_name'];
        $_SESSION['program_type'] = $user['program_type'];
        $_SESSION['intake'] = $user['intake'];
        $_SESSION['year_of_study'] = $user['year_of_study'];
        $_SESSION['department'] = $user['department'];
        $_SESSION['is_logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
    
    /**
     * Update user's last login time (add last_login column if needed)
     * @param int $user_id
     */
    private function updateLastLogin($user_id) {
        // Check if last_login column exists, if not we'll skip this
        try {
            $this->db->query("UPDATE users SET updated_at = NOW() WHERE user_id = :user_id", 
                           ['user_id' => $user_id]);
        } catch (Exception $e) {
            // Column might not exist, continue without error
            error_log("Could not update last login: " . $e->getMessage());
        }
    }
    
    /**
     * Get redirect URL based on user role
     * @param string $role_name
     * @return string
     */
    private function getRedirectUrl($role_name) {
        switch ($role_name) {
            case 'admin':
                return '../dashboards/admin/dashboard.php';
            case 'class_rep':
                return '../dashboards/class_rep/dashboard.php';
            case 'lecturer':
                return '../dashboards/lecturer/dashboard.php';
            case 'student':
                return '../dashboards/student/dashboard.php';
            default:
                return 'index.php';
        }
    }
    
    /**
     * Logout user
     * @return bool
     */
    public function logout() {
        // Destroy all session data
        $_SESSION = [];
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
        
        return true;
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
    }
    
    /**
     * Get current logged in user data
     * @return array|null
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'],
            'student_id' => $_SESSION['student_id'],
            'role_name' => $_SESSION['role_name'],
            'role_id' => $_SESSION['role_id'],
            'name' => $_SESSION['name'],
            'email' => $_SESSION['email'],
            'course_id' => $_SESSION['course_id'],
            'course_name' => $_SESSION['course_name'],
            'course_code' => $_SESSION['course_code'],
            'program_name' => $_SESSION['program_name'],
            'program_type' => $_SESSION['program_type'],
            'intake' => $_SESSION['intake'],
            'year_of_study' => $_SESSION['year_of_study'],
            'department' => $_SESSION['department']
        ];
    }
    
    /**
     * Get user role
     * @return string|null
     */
    public function getUserRole() {
        return $this->isLoggedIn() ? $_SESSION['role_name'] : null;
    }
    
    /**
     * Get user ID
     * @return int|null
     */
    public function getUserId() {
        return $this->isLoggedIn() ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Check if session is expired (2 hours timeout)
     * @return bool
     */
    public function isSessionExpired() {
        if (!$this->isLoggedIn()) {
            return true;
        }
        
        $timeout = 2 * 60 * 60; // 2 hours in seconds
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
            $this->logout();
            return true;
        }
        
        return false;
    }
    
    /**
     * Require login - redirect to login page if not logged in
     * @param string $redirect_url
     */
    public function requireLogin($redirect_url = 'login.php') {
        if (!$this->isLoggedIn() || $this->isSessionExpired()) {
            header("Location: $redirect_url");
            exit;
        }
    }
    
    /**
     * Check daily booking limit for class reps (2 hours per day)
     * @return array
     */
    public function checkDailyBookingLimit() {
        if (!$this->isLoggedIn() || $_SESSION['role_name'] !== 'class_rep') {
            return ['can_book' => false, 'message' => 'Unauthorized'];
        }
        
        $today = date('Y-m-d');
        $course_id = $_SESSION['course_id'];
        
        if (!$course_id) {
            return ['can_book' => false, 'message' => 'No course assigned to your account'];
        }
        
        // Get total minutes booked today for this course
        $query = "SELECT SUM(duration_minutes) as total_minutes 
                 FROM bookings 
                 WHERE course_id = :course_id 
                 AND booking_date = :today 
                 AND status IN ('approved', 'pending')";
        
        $result = $this->db->fetchOne($query, [
            'course_id' => $course_id,
            'today' => $today
        ]);
        
        $total_minutes = $result['total_minutes'] ?? 0;
        $max_minutes = 2 * 60; // 2 hours = 120 minutes
        $remaining_minutes = $max_minutes - $total_minutes;
        
        return [
            'can_book' => $remaining_minutes > 0,
            'total_booked' => $total_minutes,
            'remaining_minutes' => max(0, $remaining_minutes),
            'remaining_hours' => max(0, round($remaining_minutes / 60, 1)),
            'message' => $remaining_minutes > 0 ? 
                "You have " . round($remaining_minutes/60, 1) . " hours remaining today" : 
                "Daily 2-hour booking limit reached"
        ];
    }
    
    /**
     * Get available lecturers for a course
     * @param int $course_id
     * @return array
     */
    public function getAvailableLecturers($course_id = null) {
        $course_id = $course_id ?? $_SESSION['course_id'];
        
        $query = "SELECT u.user_id, u.name, u.email, u.department 
                 FROM users u
                 JOIN roles r ON u.role_id = r.role_id
                 WHERE r.role_name = 'lecturer' AND u.is_active = 1
                 ORDER BY u.name";
        
        return $this->db->fetchAll($query);
    }
    
    /**
     * Get user statistics for dashboard
     * @return array
     */
    public function getDashboardStats() {
        $user_id = $this->getUserId();
        $role = $this->getUserRole();
        $course_id = $_SESSION['course_id'] ?? null;
        
        $stats = [];
        
        switch ($role) {
            case 'admin':
                // Total bookings today
                $stats['bookings_today'] = $this->db->count('bookings', 'booking_date = CURDATE()');
                // Total active users
                $stats['active_users'] = $this->db->count('users', 'is_active = 1');
                // Available rooms
                $stats['available_rooms'] = $this->db->count('rooms', 'is_available = 1');
                // Pending approvals
                $stats['pending_approvals'] = $this->db->count('bookings', "status = 'pending'");
                break;
                
            case 'class_rep':
                // My bookings today
                $stats['my_bookings_today'] = $this->db->count('bookings', 
                    'booked_by = :user_id AND booking_date = CURDATE()', 
                    ['user_id' => $user_id]);
                // Course bookings this week
                $stats['course_bookings_week'] = $this->db->count('bookings', 
                    'course_id = :course_id AND booking_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)', 
                    ['course_id' => $course_id]);
                // Remaining hours today
                $limit_check = $this->checkDailyBookingLimit();
                $stats['remaining_hours'] = $limit_check['remaining_hours'];
                break;
                
            case 'lecturer':
                // Classes today
                $stats['classes_today'] = $this->db->count('bookings', 
                    'lecturer_id = :user_id AND booking_date = CURDATE() AND status = "approved"', 
                    ['user_id' => $user_id]);
                // Pending approvals
                $stats['pending_approvals'] = $this->db->count('bookings', 
                    'lecturer_id = :user_id AND status = "pending"', 
                    ['user_id' => $user_id]);
                break;
                
            case 'student':
                // Classes today
                $stats['classes_today'] = $this->db->count('bookings', 
                    'course_id = :course_id AND booking_date = CURDATE() AND status = "approved"', 
                    ['course_id' => $course_id]);
                break;
        }
        
        // Unread notifications for all users
        $stats['unread_notifications'] = $this->db->count('notifications', 
            'user_id = :user_id AND is_read = 0',
            ['user_id' => $user_id]);
        
        return $stats;
    }
    
    /**
     * Check if user has specific permission
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission) {
        $role = $this->getUserRole();
        
        $permissions = [
            'admin' => ['*'], // Admin has all permissions
            'class_rep' => ['create_booking', 'edit_own_booking', 'view_course_bookings'],
            'lecturer' => ['approve_booking', 'view_assigned_bookings', 'create_booking'],
            'student' => ['view_course_bookings']
        ];
        
        if (!isset($permissions[$role])) {
            return false;
        }
        
        return in_array('*', $permissions[$role]) || in_array($permission, $permissions[$role]);
    }
    
    /**
     * Require specific permission
     * @param string $permission
     * @param string $redirect_url
     */
    public function requirePermission($permission, $redirect_url = 'unauthorized.php') {
        if (!$this->hasPermission($permission)) {
            header("Location: $redirect_url");
            exit;
        }
    }
    
    /**
     * Get recent user activity for audit trail
     * @param int $limit
     * @return array
     */
    public function getRecentActivity($limit = 10) {
        $user_id = $this->getUserId();
        
        // Get recent bookings
        $bookings_query = "SELECT 
                            'booking' as activity_type, 
                            b.booking_id as reference_id,
                            CONCAT('Booked room ', r.room_name, ' for ', c.course_name) as description,
                            b.created_at as activity_time
                         FROM bookings b
                         JOIN rooms r ON b.room_id = r.room_id
                         JOIN courses c ON b.course_id = c.course_id
                         WHERE b.booked_by = :user_id
                         ORDER BY b.created_at DESC
                         LIMIT :limit";
        
        // Get recent notifications
        $notifications_query = "SELECT 
                                'notification' as activity_type,
                                notification_id as reference_id,
                                title as description,
                                created_at as activity_time
                             FROM notifications
                             WHERE user_id = :user_id
                             ORDER BY created_at DESC
                             LIMIT :limit";
        
        try {
            // Get bookings
            $bookings = $this->db->fetchAll($bookings_query, [
                'user_id' => $user_id,
                'limit' => $limit
            ]);
            
            // Get notifications
            $notifications = $this->db->fetchAll($notifications_query, [
                'user_id' => $user_id,
                'limit' => $limit
            ]);
            
            // Combine and sort by activity_time
            $all_activities = array_merge($bookings, $notifications);
            
            // Sort by activity_time descending
            usort($all_activities, function($a, $b) {
                return strtotime($b['activity_time']) - strtotime($a['activity_time']);
            });
            
            // Return only the requested limit
            return array_slice($all_activities, 0, $limit);
            
        } catch (Exception $e) {
            error_log("GetRecentActivity error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create notification for user
     * @param int $user_id
     * @param string $title
     * @param string $message
     * @param string $type
     * @return bool
     */
    public function createNotification($user_id, $title, $message, $type = 'info') {
        $query = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                 VALUES (:user_id, :title, :message, :type, NOW())";
        
        try {
            $this->db->query($query, [
                'user_id' => $user_id,
                'title' => $title,
                'message' => $message,
                'type' => $type
            ]);
            return true;
        } catch (Exception $e) {
            error_log("Notification creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark notification as read
     * @param int $notification_id
     * @return bool
     */
    public function markNotificationRead($notification_id) {
        $user_id = $this->getUserId();
        
        $query = "UPDATE notifications 
                 SET is_read = 1, read_at = NOW() 
                 WHERE notification_id = :notification_id AND user_id = :user_id";
        
        try {
            $this->db->query($query, [
                'notification_id' => $notification_id,
                'user_id' => $user_id
            ]);
            return true;
        } catch (Exception $e) {
            error_log("Mark notification read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user notifications
     * @param bool $unread_only
     * @param int $limit
     * @return array
     */
    public function getNotifications($unread_only = false, $limit = 20) {
        $user_id = $this->getUserId();
        
        $where_clause = "user_id = :user_id";
        $params = ['user_id' => $user_id, 'limit' => $limit];
        
        if ($unread_only) {
            $where_clause .= " AND is_read = 0";
        }
        
        $query = "SELECT * FROM notifications 
                 WHERE $where_clause 
                 ORDER BY created_at DESC 
                 LIMIT :limit";
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Update user profile (limited fields)
     * @param array $data
     * @return array
     */
    public function updateProfile($data) {
        $user_id = $this->getUserId();
        
        // Only allow certain fields to be updated
        $allowed_fields = ['name', 'email'];
        $update_fields = [];
        $params = ['user_id' => $user_id];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $update_fields[] = "$field = :$field";
                $params[$field] = trim($data[$field]);
            }
        }
        
        if (empty($update_fields)) {
            return ['success' => false, 'message' => 'No valid fields to update'];
        }
        
        $query = "UPDATE users SET " . implode(', ', $update_fields) . ", updated_at = NOW() 
                 WHERE user_id = :user_id";
        
        try {
            $this->db->query($query, $params);
            
            // Update session data
            foreach ($allowed_fields as $field) {
                if (isset($params[$field])) {
                    $_SESSION[$field] = $params[$field];
                }
            }
            
            return ['success' => true, 'message' => 'Profile updated successfully'];
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
    }
    
    /**
     * Validate student ID format (customize based on your institution's format)
     * @param string $student_id
     * @return bool
     */
    public function validateStudentId($student_id) {
        // Example: CHAU student IDs might be in format: CHAU/2024/CS/001
        // Adjust this pattern based on your actual format
        $pattern = '/^CHAU\/\d{4}\/[A-Z]{2,4}\/\d{3,4}$/';
        return preg_match($pattern, $student_id);
    }
    
    /**
     * Get user's upcoming bookings
     * @param int $days_ahead
     * @return array
     */
    public function getUpcomingBookings($days_ahead = 7) {
        $user_id = $this->getUserId();
        $role = $this->getUserRole();
        
        $where_clause = "";
        $params = [
            'user_id' => $user_id,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime("+$days_ahead days"))
        ];
        
        switch ($role) {
            case 'class_rep':
                $where_clause = "b.booked_by = :user_id";
                break;
            case 'lecturer':
                $where_clause = "b.lecturer_id = :user_id";
                break;
            case 'student':
                $where_clause = "b.course_id = :course_id";
                $params['course_id'] = $_SESSION['course_id'];
                break;
            case 'admin':
                $where_clause = "1 = 1"; // Admin sees all
                break;
        }
        
        $query = "SELECT b.*, r.room_name, r.room_location, c.course_name, c.course_code,
                        u.name as booked_by_name, l.name as lecturer_name
                 FROM bookings b
                 JOIN rooms r ON b.room_id = r.room_id
                 JOIN courses c ON b.course_id = c.course_id
                 JOIN users u ON b.booked_by = u.user_id
                 LEFT JOIN users l ON b.lecturer_id = l.user_id
                 WHERE $where_clause 
                 AND b.booking_date BETWEEN :start_date AND :end_date
                 AND b.status IN ('approved', 'pending')
                 ORDER BY b.booking_date ASC, b.start_time ASC";
        
        return $this->db->fetchAll($query, $params);
    }
    /**
 * Get pending bookings for approval
 * @param int $limit
 * @return array
 */

/**
 * Get today's approved classes for a lecturer
 */
public function getTodaysClasses($lecturer_id) {
    $sql = "SELECT b.*, r.room_name, r.location, c.course_name, c.course_code,
                   u.name as booked_by_name, p.program_name
            FROM bookings b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN courses c ON b.course_id = c.course_id
            JOIN programs p ON c.program_id = p.program_id
            JOIN users u ON b.booked_by = u.user_id
            WHERE b.lecturer_id = :lecturer_id 
            AND b.booking_date = CURDATE()
            AND b.status = 'approved'
            ORDER BY b.start_time ASC";

    return $this->db->fetchAll($sql, ['lecturer_id' => $lecturer_id]);
}

/**
 * Get pending bookings for a lecturer
 */
public function getPendingBookings($lecturer_id) {
    $sql = "SELECT b.*, r.room_name, r.location, c.course_name, c.course_code,
                   u.name as booked_by_name, p.program_name
            FROM bookings b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN courses c ON b.course_id = c.course_id
            JOIN programs p ON c.program_id = p.program_id
            JOIN users u ON b.booked_by = u.user_id
            WHERE b.lecturer_id = :lecturer_id 
            AND b.status = 'pending'
            ORDER BY b.created_at DESC";

    return $this->db->fetchAll($sql, ['lecturer_id' => $lecturer_id]);
}

/**
 * Get upcoming classes within 7 days
 */
public function getUpcomingClasses($lecturer_id) {
    $sql = "SELECT b.*, r.room_name, r.location, c.course_name, c.course_code,
                   u.name as booked_by_name, p.program_name
            FROM bookings b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN courses c ON b.course_id = c.course_id
            JOIN programs p ON c.program_id = p.program_id
            JOIN users u ON b.booked_by = u.user_id
            WHERE b.lecturer_id = :lecturer_id 
            AND b.booking_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND b.status = 'approved'
            ORDER BY b.booking_date ASC, b.start_time ASC";

    return $this->db->fetchAll($sql, ['lecturer_id' => $lecturer_id]);
}
/**
 * Get bookings made by the class rep
 */
public function getClassRepBookings($classRepId) {
    $sql = "SELECT b.*, r.room_name, c.course_name, u.name as lecturer_name
            FROM bookings b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN courses c ON b.course_id = c.course_id
            LEFT JOIN users u ON b.lecturer_id = u.user_id
            WHERE b.booked_by = :class_rep_id
            ORDER BY b.booking_date DESC";

    return $this->db->fetchAll($sql, ['class_rep_id' => $classRepId]);
}
public function getPendingApprovals($classRepId) {
    $sql = "SELECT b.*, r.room_name, c.course_name, u.name as lecturer_name
            FROM bookings b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN courses c ON b.course_id = c.course_id
            LEFT JOIN users u ON b.lecturer_id = u.user_id
            WHERE b.booked_by = :class_rep_id AND b.status = 'pending'
            ORDER BY b.created_at DESC";

    return $this->db->fetchAll($sql, ['class_rep_id' => $classRepId]);
}
public function getAvailableRoomsToday() {
    $sql = "SELECT r.*, 
                   (SELECT COUNT(*) FROM bookings b 
                    WHERE b.room_id = r.room_id 
                    AND b.booking_date = CURDATE() 
                    AND b.status = 'approved') as bookings_today
            FROM rooms r 
            WHERE r.is_available = 1
            ORDER BY r.room_name";

    return $this->db->fetchAll($sql);
}
public function getTodaysBookingsForClassRep($course_id) {
    $sql = "SELECT b.*, r.room_name, r.location, c.course_name, u.name as lecturer_name
            FROM bookings b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN courses c ON b.course_id = c.course_id
            JOIN users u ON b.lecturer_id = u.user_id
            WHERE b.course_id = :course_id 
            AND b.booking_date = CURDATE()
            AND b.status = 'approved'
            ORDER BY b.start_time ASC";

    return $this->db->fetchAll($sql, ['course_id' => $course_id]);
}
public function getUpcomingBookingsForClassRep($course_id) {
    $sql = "SELECT b.*, r.room_name, r.location, c.course_name, u.name as lecturer_name
            FROM bookings b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN courses c ON b.course_id = c.course_id
            JOIN users u ON b.lecturer_id = u.user_id
            WHERE b.course_id = :course_id 
            AND b.booking_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND b.status = 'approved'
            ORDER BY b.booking_date ASC, b.start_time ASC";

    return $this->db->fetchAll($sql, ['course_id' => $course_id]);
}
public function getStudentBookings($student_id) {
    $sql = "SELECT b.*, r.room_name, c.course_name, c.course_code, p.program_name
            FROM bookings b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN courses c ON b.course_id = c.course_id
            JOIN programs p ON c.program_id = p.program_id
            WHERE b.booked_by = :student_id
            ORDER BY b.booking_date DESC, b.start_time ASC";

    return $this->db->fetchAll($sql, ['student_id' => $student_id]);
}
/**
 * Get upcoming approved classes for a given course (used in student dashboard)
 */
public function getUpcomingClassesForStudent($course_id) {
    $sql = "SELECT b.*, r.room_name, r.location, c.course_name, c.course_code,
                   l.name as lecturer_name, p.program_name, u.name as booked_by_name
            FROM bookings b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN courses c ON b.course_id = c.course_id
            JOIN programs p ON c.program_id = p.program_id
            LEFT JOIN users l ON b.lecturer_id = l.user_id
            JOIN users u ON b.booked_by = u.user_id
            WHERE b.course_id = :course_id 
              AND b.booking_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
              AND b.status = 'approved'
            ORDER BY b.booking_date ASC, b.start_time ASC";

    return $this->db->fetchAll($sql, ['course_id' => $course_id]);
}
/**
 * Get course info for a student (used in student dashboard)
 * @param int $course_id
 * @return array|null
 */
public function getCourseInfoForStudent($course_id) {
    return $this->db->fetchOne(
        "SELECT c.*, p.program_name, p.program_type
         FROM courses c
         JOIN programs p ON c.program_id = p.program_id
         WHERE c.course_id = :course_id",
        ['course_id' => $course_id]
    );
}
/**
 * Get today's approved classes for a student (used in student dashboard)
 * @param int $course_id
 * @return array
 */
public function getTodaysClassesForStudent($course_id) {
    $sql = "SELECT b.*, r.room_name, r.location, c.course_name, c.course_code,
                   l.name as lecturer_name, p.program_name, u.name as booked_by_name
            FROM bookings b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN courses c ON b.course_id = c.course_id
            JOIN programs p ON c.program_id = p.program_id
            LEFT JOIN users l ON b.lecturer_id = l.user_id
            JOIN users u ON b.booked_by = u.user_id
            WHERE b.course_id = :course_id 
              AND b.booking_date = CURDATE() -- Only today's classes
              AND b.status = 'approved'
            ORDER BY b.start_time ASC";

    $result = $this->db->fetchAll($sql, ['course_id' => $course_id]);
    return $result ?: []; // Ensure an array is always returned
}

}
?>