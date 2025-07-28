<?php
/**
 * Lecturer Dashboard - ClassReserve CHAU
 * File: dashboards/lecturer/dashboard.php
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
$dashboardStats = $auth->getDashboardStats();

// Get today's classes for this lecturer
$todaysClasses = $auth->db->fetchAll(
    "SELECT b.*, r.room_name, r.location, c.course_name, c.course_code,
            u.name as booked_by_name, p.program_name
     FROM bookings b
     JOIN rooms r ON b.room_id = r.room_id
     JOIN courses c ON b.course_id = c.course_id
     JOIN programs p ON c.program_id = p.program_id
     JOIN users u ON b.booked_by = u.user_id
     WHERE b.lecturer_id = :lecturer_id 
     AND b.booking_date = CURDATE()
     AND b.status = 'approved'
     ORDER BY b.start_time ASC",
    ['lecturer_id' => $currentUser['user_id']]
);

// Get pending bookings that need lecturer approval
$pendingBookings = $auth->db->fetchAll(
    "SELECT b.*, r.room_name, r.location, c.course_name, c.course_code,
            u.name as booked_by_name, p.program_name
     FROM bookings b
     JOIN rooms r ON b.room_id = r.room_id
     JOIN courses c ON b.course_id = c.course_id
     JOIN programs p ON c.program_id = p.program_id
     JOIN users u ON b.booked_by = u.user_id
     WHERE b.lecturer_id = :lecturer_id 
     AND b.status = 'pending'
     ORDER BY b.created_at DESC",
    ['lecturer_id' => $currentUser['user_id']]
);

// Get upcoming classes (next 7 days)
$upcomingClasses = $auth->db->fetchAll(
    "SELECT b.*, r.room_name, r.location, c.course_name, c.course_code,
            u.name as booked_by_name, p.program_name
     FROM bookings b
     JOIN rooms r ON b.room_id = r.room_id
     JOIN courses c ON b.course_id = c.course_id
     JOIN programs p ON c.program_id = p.program_id
     JOIN users u ON b.booked_by = u.user_id
     WHERE b.lecturer_id = :lecturer_id 
     AND b.booking_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
     AND b.status = 'approved'
     ORDER BY b.booking_date ASC, b.start_time ASC",
    ['lecturer_id' => $currentUser['user_id']]
);

// Get notifications
$notifications = $auth->getNotifications(false, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Dashboard - ClassReserve CHAU</title>
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
            background: linear-gradient(135deg, var(--info-color) 0%, var(--primary-color) 100%);
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
            background: linear-gradient(135deg, var(--info-color) 0%, var(--primary-color) 100%);
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
        
        .class-card {
            background: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--info-color);
        }
        
        .time-badge {
            background: var(--info-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending {
            background: var(--warning-color);
            color: #333;
        }
        
        .status-approved {
            background: var(--success-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation Bar -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-chalkboard-teacher"></i> Lecturer Dashboard
                </a>
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="schedule.php"><i class="fas fa-calendar"></i> My Schedule</a></li>
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
                        <h5 class="mb-3"><i class="fas fa-chalkboard-teacher"></i> Lecturer Panel</h5>
                        <nav class="nav flex-column">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                            <a class="nav-link" href="schedule.php">
                                <i class="fas fa-calendar-alt"></i> My Schedule
                            </a>
                            <a class="nav-link" href="approvals.php">
                                <i class="fas fa-check-circle"></i> Pending Approvals
                                <?php if (count($pendingBookings) > 0): ?>
                                    <span class="badge bg-warning ms-2"><?php echo count($pendingBookings); ?></span>
                                <?php endif; ?>
                            </a>
                            <a class="nav-link" href="classes.php">
                                <i class="fas fa-book"></i> My Classes
                            </a>
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <a class="nav-link" href="notifications.php">
                                <i class="fas fa-bell"></i> Notifications
                                <?php if (count(array_filter($notifications, function($n) { return !$n['is_read']; })) > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo count(array_filter($notifications, function($n) { return !$n['is_read']; })); ?></span>
                                <?php endif; ?>
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
                                <h2><i class="fas fa-chalkboard-teacher text-info"></i> Welcome, <?php echo htmlspecialchars($currentUser['name']); ?>!</h2>
                                <p class="text-muted">Lecturer Dashboard - Manage your classes and approvals</p>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-info me-3">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo count($todaysClasses); ?></h4>
                                            <small class="text-muted">Today's Classes</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-warning me-3">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo count($pendingBookings); ?></h4>
                                            <small class="text-muted">Pending Approvals</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-success me-3">
                                            <i class="fas fa-calendar-week"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo count($upcomingClasses); ?></h4>
                                            <small class="text-muted">Upcoming Classes</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-primary me-3">
                                            <i class="fas fa-bell"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo count(array_filter($notifications, function($n) { return !$n['is_read']; })); ?></h4>
                                            <small class="text-muted">New Notifications</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Classes -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="table-container">
                                    <h5><i class="fas fa-clock text-info"></i> Today's Classes</h5>
                                    <?php if (empty($todaysClasses)): ?>
                                        <p class="text-muted">No classes scheduled for today</p>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($todaysClasses as $class): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="class-card">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($class['course_name']); ?></h6>
                                                            <span class="time-badge">
                                                                <?php echo date('H:i', strtotime($class['start_time'])); ?> - 
                                                                <?php echo date('H:i', strtotime($class['end_time'])); ?>
                                                            </span>
                                                        </div>
                                                        <p class="mb-2 text-muted">
                                                            <i class="fas fa-map-marker-alt"></i> 
                                                            <?php echo htmlspecialchars($class['room_name']); ?> 
                                                            (<?php echo htmlspecialchars($class['location']); ?>)
                                                        </p>
                                                        <p class="mb-2 text-muted">
                                                            <i class="fas fa-user"></i> 
                                                            Booked by: <?php echo htmlspecialchars($class['booked_by_name']); ?>
                                                        </p>
                                                        <p class="mb-0 text-muted">
                                                            <i class="fas fa-graduation-cap"></i> 
                                                            <?php echo htmlspecialchars($class['program_name']); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Approvals and Notifications -->
                        <div class="row">
                            <!-- Pending Approvals -->
                            <div class="col-md-6 mb-4">
                                <div class="table-container">
                                    <h5><i class="fas fa-exclamation-triangle text-warning"></i> Pending Approvals</h5>
                                    <?php if (empty($pendingBookings)): ?>
                                        <p class="text-muted">No pending approvals</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Course</th>
                                                        <th>Room</th>
                                                        <th>Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($pendingBookings as $booking): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($booking['course_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                                            <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                                            <td>
                                                                <a href="approve_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                                                                   class="btn btn-sm btn-success">Approve</a>
                                                                <a href="reject_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                                                                   class="btn btn-sm btn-danger">Reject</a>
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
                                    <h5><i class="fas fa-bell text-primary"></i> Recent Notifications</h5>
                                    <?php if (empty($notifications)): ?>
                                        <p class="text-muted">No recent notifications</p>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach (array_slice($notifications, 0, 5) as $notification): ?>
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

                        <!-- Quick Actions -->
                        <div class="row">
                            <div class="col-12">
                                <div class="table-container">
                                    <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <a href="schedule.php" class="btn btn-info btn-custom w-100">
                                                <i class="fas fa-calendar-alt"></i> View Schedule
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="approvals.php" class="btn btn-warning btn-custom w-100">
                                                <i class="fas fa-check-circle"></i> Manage Approvals
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="profile.php" class="btn btn-primary btn-custom w-100">
                                                <i class="fas fa-user"></i> Edit Profile
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="notifications.php" class="btn btn-success btn-custom w-100">
                                                <i class="fas fa-bell"></i> View All Notifications
                                            </a>
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
        // Auto-refresh dashboard every 60 seconds
        setInterval(function() {
            location.reload();
        }, 60000);
        
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