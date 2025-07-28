<?php
/**
 * Role-Based Access Control System for ClassReserve CHAU
 * File: classes/RBAC.php
 */

require_once 'classes/Auth.php';

class RBAC {
    private $auth;
    
    // Define permissions for each role
    private $permissions = [
        'admin' => [
            'view_all_bookings',
            'manage_users',
            'manage_rooms',
            'manage_courses',
            'cancel_any_booking',
            'view_reports',
            'manage_settings',
            'send_notifications'
        ],
        'class_rep' => [
            'create_booking',
            'view_own_bookings',
            'view_course_bookings',
            'cancel_own_booking',
            'view_available_rooms',
            'receive_notifications'
        ],
        'lecturer' => [
            'view_all_bookings',
            'view_available_rooms',
            'receive_notifications',
            'view_course_schedules'
        ],
        'student' => [
            'view_own_bookings',
            'view_course_bookings',
            'view_available_rooms',
            'receive_notifications'
        ]
    ];
    
    public function __construct() {
        $this->auth = new Auth();
    }
    
    /**
     * Check if current user has specific permission
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission) {
        $userRole = $this->auth->getUserRole();
        
        if (!$userRole) {
            return false;
        }
        
        return isset($this->permissions[$userRole]) && 
               in_array($permission, $this->permissions[$userRole]);
    }
    
    /**
     * Require specific permission or redirect/exit
     * @param string $permission
     * @param string $redirect_url
     * @param string $error_message
     */
    public function requirePermission($permission, $redirect_url = null, $error_message = null) {
        if (!$this->hasPermission($permission)) {
            if ($redirect_url) {
                $_SESSION['error_message'] = $error_message ?? 'Access denied. Insufficient permissions.';
                header("Location: $redirect_url");
                exit;
            } else {
                http_response_code(403);
                die($error_message ?? 'Access denied. Insufficient permissions.');
            }
        }
    }
    
    /**
     * Check if current user can access specific role area
     * @param string $required_role
     * @return bool
     */
    public function canAccessRole($required_role) {
        $userRole = $this->auth->getUserRole();
        
        if (!$userRole) {
            return false;
        }
        
        // Admin can access all areas
        if ($userRole === 'admin') {
            return true;
        }
        
        // Users can only access their own role area
        return $userRole === $required_role;
    }
    
    /**
     * Require specific role access
     * @param string $required_role
     * @param string $redirect_url
     */
    public function requireRole($required_role, $redirect_url = 'index.php') {
        if (!$this->canAccessRole($required_role)) {
            $_SESSION['error_message'] = 'Access denied. You do not have permission to access this area.';
            header("Location: $redirect_url");
            exit;
        }
    }
    
    /**
     * Check if user can manage specific booking
     * @param array $booking
     * @return bool
     */
    public function canManageBooking($booking) {
        $userRole = $this->auth->getUserRole();
        $userId = $this->auth->getUserId();
        $currentUser = $this->auth->getCurrentUser();
        
        switch ($userRole) {
            case 'admin':
                return true; // Admin can manage all bookings
                
            case 'class_rep':
                // Class rep can manage bookings from their course
                return $booking['user_id'] == $userId || 
                       $booking['course_id'] == $currentUser['course_id'];
                
            case 'lecturer':
                // Lecturers cannot manage bookings, only view
                return false;
                
            case 'student':
                // Students can only view, not manage
                return false;
                
            default:
                return false;
        }
    }
    
    /**
     * Check if user can create booking
     * @return array
     */
    public function canCreateBooking() {
        $userRole = $this->auth->getUserRole();
        
        if ($userRole !== 'class_rep' && $userRole !== 'admin') {
            return [
                'can_create' => false,
                'message' => 'Only class representatives can create bookings.'
            ];
        }
        
        if ($userRole === 'class_rep') {
            // Check daily limit
            $limitCheck = $this->auth->checkDailyBookingLimit();
            return [
                'can_create' => $limitCheck['can_book'],
                'message' => $limitCheck['message']
            ];
        }
        
        return [
            'can_create' => true,
            'message' => 'You can create bookings.'
        ];
    }
    
    /**
     * Get navigation menu based on user role
     * @return array
     */
    public function getNavigationMenu() {
        $userRole = $this->auth->getUserRole();
        $currentUser = $this->auth->getCurrentUser();
        
        $baseMenu = [
            'dashboard' => [
                'title' => 'Dashboard',
                'url' => 'dashboard.php',
                'icon' => 'fas fa-tachometer-alt'
            ],
            'bookings' => [
                'title' => 'View Bookings',
                'url' => 'bookings.php',
                'icon' => 'fas fa-calendar-alt'
            ]
        ];
        
        switch ($userRole) {
            case 'admin':
                return array_merge($baseMenu, [
                    'create_booking' => [
                        'title' => 'Create Booking',
                        'url' => 'create_booking.php',
                        'icon' => 'fas fa-plus-circle'
                    ],
                    'manage_users' => [
                        'title' => 'Manage Users',
                        'url' => 'manage_users.php',
                        'icon' => 'fas fa-users'
                    ],
                    'manage_rooms' => [
                        'title' => 'Manage Rooms',
                        'url' => 'manage_rooms.php',
                        'icon' => 'fas fa-building'
                    ],
                    'reports' => [
                        'title' => 'Reports',
                        'url' => 'reports.php',
                        'icon' => 'fas fa-chart-bar'
                    ],
                    'settings' => [
                        'title' => 'Settings',
                        'url' => 'settings.php',
                        'icon' => 'fas fa-cog'
                    ]
                ]);
                
            case 'class_rep':
                return array_merge($baseMenu, [
                    'create_booking' => [
                        'title' => 'Create Booking',
                        'url' => 'create_booking.php',
                        'icon' => 'fas fa-plus-circle'
                    ],
                    'my_bookings' => [
                        'title' => 'My Bookings',
                        'url' => 'my_bookings.php',
                        'icon' => 'fas fa-list'
                    ]
                ]);
                
            case 'lecturer':
                return array_merge($baseMenu, [
                    'schedule' => [
                        'title' => 'Class Schedule',
                        'url' => 'schedule.php',
                        'icon' => 'fas fa-clock'
                    ]
                ]);
                
            case 'student':
                return array_merge($baseMenu, [
                    'schedule' => [
                        'title' => 'Class Schedule',
                        'url' => 'schedule.php',
                        'icon' => 'fas fa-clock'
                    ]
                ]);
                
            default:
                return $baseMenu;
        }
    }
    
    /**
     * Get user dashboard widgets based on role
     * @return array
     */
    public function getDashboardWidgets() {
        $userRole = $this->auth->getUserRole();
        
        switch ($userRole) {
            case 'admin':
                return [
                    'total_bookings_today',
                    'active_users',
                    'available_rooms',
                    'recent_bookings',
                    'system_notifications'
                ];
                
            case 'class_rep':
                return [
                    'my_bookings_today',
                    'remaining_hours',
                    'available_rooms',
                    'upcoming_classes',
                    'notifications'
                ];
                
            case 'lecturer':
                return [
                    'todays_classes',
                    'upcoming_classes',
                    'room_availability',
                    'notifications'
                ];
                
            case 'student':
                return [
                    'todays_classes',
                    'upcoming_classes',
                    'notifications'
                ];
                
            default:
                return ['notifications'];
        }
    }
    
    /**
     * Check if user can view specific booking details
     * @param array $booking
     * @return bool
     */
    public function canViewBooking($booking) {
        $userRole = $this->auth->getUserRole();
        $userId = $this->auth->getUserId();
        $currentUser = $this->auth->getCurrentUser();
        
        switch ($userRole) {
            case 'admin':
            case 'lecturer':
                return true; // Can view all bookings
                
            case 'class_rep':
            case 'student':
                // Can view bookings from their course or their own bookings
                return $booking['course_id'] == $currentUser['course_id'] || 
                       $booking['user_id'] == $userId;
                
            default:
                return false;
        }
    }
    
    /**
     * Get role display name
     * @param string $role
     * @return string
     */
    public function getRoleDisplayName($role = null) {
        $role = $role ?? $this->auth->getUserRole();
        
        $roleNames = [
            'admin' => 'Administrator',
            'class_rep' => 'Class Representative',
            'lecturer' => 'Lecturer',
            'student' => 'Student'
        ];
        
        return $roleNames[$role] ?? 'Unknown';
    }
}

// Helper function to get RBAC instance
function rbac() {
    static $rbac = null;
    if ($rbac === null) {
        $rbac = new RBAC();
    }
    return $rbac;
}

// Helper functions for common permission checks
function hasPermission($permission) {
    return rbac()->hasPermission($permission);
}

function requirePermission($permission, $redirect_url = null, $error_message = null) {
    rbac()->requirePermission($permission, $redirect_url, $error_message);
}

function requireRole($required_role, $redirect_url = 'index.php') {
    rbac()->requireRole($required_role, $redirect_url);
}

function canCreateBooking() {
    return rbac()->canCreateBooking();
}
?>