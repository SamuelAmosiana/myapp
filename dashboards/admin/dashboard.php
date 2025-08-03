<?php
/**
 * Admin Dashboard - ClassReserve CHAU
 * File: dashboards/admin/dashboard.php
 */

// Start session and include required files
session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';

// Initialize authentication and RBAC
$auth = new Auth();
$rbac = new RBAC();

// Check if user is logged in and has admin role
$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('admin', '../../auth/login.php');

// Get current user data
$currentUser = $auth->getCurrentUser();
$dashboardStats = $auth->getDashboardStats();

// Get recent activities
$recentActivities = $auth->getRecentActivity(10);

// Get pending bookings for approval
$pendingBookings = $auth->getPendingBookings(10);

// Get system notifications
$notifications = $auth->getNotifications(false, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ClassReserve CHAU</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 20px;
            backdrop-filter: blur(10px);
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 15px 15px 0 0;
        }
        
        .sidebar {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            height: calc(100vh - 120px);
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            color: #333;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            transform: translateX(5px);
        }
        
        .main-content {
            padding: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-custom {
            border-radius: 10px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
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
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2">
                    <div class="sidebar">
                        <h5 class="mb-3"><i class="fas fa-tachometer-alt"></i> Admin Panel</h5>
                        <nav class="nav flex-column">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                            <a class="nav-link" href="manage_users.php">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                            <a class="nav-link" href="manage_courses.php">
                                <i class="fas fa-book"></i> Manage Courses
                            </a>
                            <a class="nav-link" href="manage_programs.php">
                                <i class="fas fa-graduation-cap"></i> Manage Programs
                            </a>
                            <a class="nav-link" href="manage_rooms.php">
                                <i class="fas fa-building"></i> Manage Rooms
                            </a>
                            <a class="nav-link" href="room_availability.php">
                                <i class="fas fa-clock"></i> Room Availability
                            </a>
                            <a class="nav-link" href="bookings.php">
                                <i class="fas fa-calendar-alt"></i> All Bookings
                            </a>
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog"></i> System Settings
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-md-9 col-lg-10">
                    <div class="main-content">
                        <!-- Welcome Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h2><i class="fas fa-crown text-primary"></i> Welcome, <?php echo htmlspecialchars($currentUser['name']); ?>!</h2>
                                <p class="text-muted">System Administrator Dashboard - Manage the ClassReserve system</p>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-primary me-3">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo $dashboardStats['bookings_today'] ?? 0; ?></h4>
                                            <small class="text-muted">Today's Bookings</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-success me-3">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo $dashboardStats['active_users'] ?? 0; ?></h4>
                                            <small class="text-muted">Active Users</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-info me-3">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo $dashboardStats['available_rooms'] ?? 0; ?></h4>
                                            <small class="text-muted">Available Rooms</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-warning me-3">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo $dashboardStats['pending_approvals'] ?? 0; ?></h4>
                                            <small class="text-muted">Pending Approvals</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="table-container">
                                    <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <a href="manage_users.php" class="btn btn-primary btn-custom w-100">
                                                <i class="fas fa-user-plus"></i> Add User
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="manage_rooms.php" class="btn btn-success btn-custom w-100">
                                                <i class="fas fa-plus"></i> Add Room
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="bookings.php" class="btn btn-info btn-custom w-100">
                                                <i class="fas fa-eye"></i> View Bookings
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="reports.php" class="btn btn-warning btn-custom w-100">
                                                <i class="fas fa-chart-line"></i> Generate Report
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activities and Notifications -->
                        <div class="row">
                            <!-- Pending Bookings -->
                            <div class="col-md-6 mb-4">
                                <div class="table-container">
                                    <h5><i class="fas fa-clock text-warning"></i> Pending Bookings</h5>
                                    <?php if (empty($pendingBookings)): ?>
                                        <p class="text-muted">No pending bookings</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Room</th>
                                                        <th>Course</th>
                                                        <th>Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($pendingBookings as $booking): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($booking['course_name']); ?></td>
                                                            <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                                            <td>
                                                                <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>" 
                                                                   class="btn btn-sm btn-primary">Review</a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Recent Notifications -->
                            <div class="col-md-6 mb-4">
                                <div class="table-container">
                                    <h5><i class="fas fa-bell text-info"></i> Recent Notifications</h5>
                                    <?php if (empty($notifications)): ?>
                                        <p class="text-muted">No recent notifications</p>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($notifications as $notification): ?>
                                                <div class="list-group-item border-0 px-0">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                            <p class="mb-1 text-muted"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                            <small class="text-muted">
                                                                <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                        <?php if (!$notification['is_read']): ?>
                                                            <span class="badge bg-danger">New</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- System Status -->
                        <div class="row">
                            <div class="col-12">
                                <div class="table-container">
                                    <h5><i class="fas fa-server text-success"></i> System Status</h5>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <i class="fas fa-database fa-2x text-success"></i>
                                                <p class="mt-2 mb-0">Database</p>
                                                <small class="text-success">Online</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <i class="fas fa-users fa-2x text-info"></i>
                                                <p class="mt-2 mb-0">Active Users</p>
                                                <small class="text-info"><?php echo $dashboardStats['active_users'] ?? 0; ?> users</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <i class="fas fa-calendar fa-2x text-warning"></i>
                                                <p class="mt-2 mb-0">Today's Bookings</p>
                                                <small class="text-warning"><?php echo $dashboardStats['bookings_today'] ?? 0; ?> bookings</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <i class="fas fa-clock fa-2x text-danger"></i>
                                                <p class="mt-2 mb-0">Pending</p>
                                                <small class="text-danger"><?php echo $dashboardStats['pending_approvals'] ?? 0; ?> approvals</small>
                                            </div>
                                        </div>
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
        // Auto-refresh dashboard stats every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
        
        // Mark notifications as read when clicked
        document.querySelectorAll('.list-group-item').forEach(function(item) {
            item.addEventListener('click', function() {
                const badge = this.querySelector('.badge');
                if (badge) {
                    badge.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>