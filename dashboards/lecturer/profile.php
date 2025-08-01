<?php
/**
 * Lecturer Dashboard - Profile Settings
 * File: dashboards/lecturer/profile.php
 */

// Start session and include required files
session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';

// Initialize authentication and RBAC
$auth = new Auth();
$rbac = new RBAC();

// Check if user is logged in and has lecturer role
$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('lecturer', '../../auth/login.php');

// Get current user data
$currentUser = $auth->getCurrentUser();
$lecturer_id = $currentUser['user_id'];

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $result = $auth->updateProfile([
            'name' => $_POST['name'],
            'email' => $_POST['email']
        ]);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = "success";
            // Refresh current user data
            $currentUser = $auth->getCurrentUser();
        } else {
            $message = $result['message'];
            $messageType = "danger";
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = "All password fields are required.";
            $messageType = "danger";
        } elseif ($new_password !== $confirm_password) {
            $message = "New passwords do not match.";
            $messageType = "danger";
        } elseif (strlen($new_password) < 6) {
            $message = "New password must be at least 6 characters long.";
            $messageType = "danger";
        } else {
            // For now, show info message since password change method may not exist
            $message = "Password change functionality will be implemented soon.";
            $messageType = "info";
        }
    }
}

// Get user statistics
$todaysClasses = $auth->getTodaysClasses($lecturer_id);
$pendingBookings = $auth->getPendingBookings($lecturer_id);
$notifications = $auth->getNotifications(false, 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - ClassReserve CHAU</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand, .nav-link {
            color: var(--dark-color) !important;
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            min-height: calc(100vh - 120px);
        }

        .sidebar h5 {
            color: var(--primary-color);
            font-weight: 600;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        .sidebar .nav-link {
            color: var(--dark-color);
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            transform: translateX(5px);
        }

        .main-content {
            padding: 20px;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 3rem;
            color: white;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .profile-name {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .profile-role {
            color: #6c757d;
            font-size: 1.1rem;
        }

        .stats-row {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .form-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .form-section h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
        }

        .info-label {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-value {
            color: #495057;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .stats-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Navigation Bar -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-graduation-cap"></i> ClassReserve CHAU
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($currentUser['name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2">
                    <div class="sidebar">
                        <h5 class="mb-3"><i class="fas fa-chalkboard-teacher"></i> Lecturer Panel</h5>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="pending_approvals.php">
                                    <i class="fas fa-clock"></i> Pending Approvals
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="class_schedule.php">
                                    <i class="fas fa-calendar-alt"></i> Class Schedule
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="my_classes.php">
                                    <i class="fas fa-book"></i> My Classes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="profile.php">
                                    <i class="fas fa-user-cog"></i> Profile Settings
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="col-md-9 col-lg-10">
                    <div class="main-content">
                        <!-- Page Header -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h2><i class="fas fa-user-cog text-primary"></i> Profile Settings</h2>
                                <p class="text-muted">Manage your personal information and account settings</p>
                            </div>
                        </div>

                        <!-- Alert Messages -->
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Profile Overview -->
                        <div class="profile-card">
                            <div class="profile-header">
                                <div class="profile-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="profile-name"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                                <div class="profile-role">
                                    <i class="fas fa-chalkboard-teacher"></i> Lecturer
                                </div>
                                
                                <div class="stats-row">
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo count($todaysClasses); ?></div>
                                        <div class="stat-label">Today's Classes</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo count($pendingBookings); ?></div>
                                        <div class="stat-label">Pending Approvals</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo count($notifications); ?></div>
                                        <div class="stat-label">Notifications</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Personal Information -->
                            <div class="col-md-8">
                                <!-- Basic Information -->
                                <div class="form-section">
                                    <h5><i class="fas fa-user"></i> Personal Information</h5>
                                    
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">Full Name</div>
                                            <div class="info-value"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Email Address</div>
                                            <div class="info-value"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Staff ID</div>
                                            <div class="info-value"><?php echo htmlspecialchars($currentUser['student_id'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Department</div>
                                            <div class="info-value"><?php echo htmlspecialchars($currentUser['department'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Role</div>
                                            <div class="info-value"><?php echo ucfirst($currentUser['role_name']); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">User ID</div>
                                            <div class="info-value">#<?php echo $currentUser['user_id']; ?></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Update Profile Form -->
                                <div class="form-section">
                                    <h5><i class="fas fa-edit"></i> Update Profile</h5>
                                    
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="name" class="form-label">Full Name</label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="text-end">
                                            <button type="submit" name="update_profile" class="btn btn-gradient">
                                                <i class="fas fa-save"></i> Update Profile
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Change Password Form -->
                                <div class="form-section">
                                    <h5><i class="fas fa-lock"></i> Change Password</h5>
                                    
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="current_password" class="form-label">Current Password</label>
                                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="new_password" class="form-label">New Password</label>
                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                <div class="form-text">Password must be at least 6 characters long.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            </div>
                                        </div>
                                        
                                        <div class="text-end">
                                            <button type="submit" name="change_password" class="btn btn-gradient">
                                                <i class="fas fa-key"></i> Change Password
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Account Information Sidebar -->
                            <div class="col-md-4">
                                <!-- Account Status -->
                                <div class="form-section">
                                    <h5><i class="fas fa-info-circle"></i> Account Status</h5>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Account Status:</span>
                                            <span class="badge bg-success">Active</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Role:</span>
                                            <span class="badge bg-primary">Lecturer</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Member Since:</span>
                                            <span class="text-muted">2024</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="form-section">
                                    <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="dashboard.php" class="btn btn-outline-primary">
                                            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                                        </a>
                                        <a href="pending_approvals.php" class="btn btn-outline-warning">
                                            <i class="fas fa-clock"></i> View Pending Approvals
                                        </a>
                                        <a href="my_classes.php" class="btn btn-outline-info">
                                            <i class="fas fa-book"></i> My Classes
                                        </a>
                                        <a href="../../auth/logout.php" class="btn btn-outline-danger">
                                            <i class="fas fa-sign-out-alt"></i> Logout
                                        </a>
                                    </div>
                                </div>

                                <!-- Help & Support -->
                                <div class="form-section">
                                    <h5><i class="fas fa-question-circle"></i> Help & Support</h5>
                                    
                                    <p class="text-muted mb-3">Need help with your account or the system?</p>
                                    
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-secondary" type="button">
                                            <i class="fas fa-book"></i> User Guide
                                        </button>
                                        <button class="btn btn-outline-secondary" type="button">
                                            <i class="fas fa-envelope"></i> Contact Support
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            // Password confirmation validation
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePassword() {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            newPassword.addEventListener('input', validatePassword);
            confirmPassword.addEventListener('input', validatePassword);
        });
    </script>
</body>
</html>