<?php
// Initialize all variables at the top
$todaysClasses = [];  // Add this line
$upcomingBookings = [];
// ... rest of your existing declarations
/**
 * Student Dashboard - ClassReserve CHAU
 * File: dashboards/student/dashboard.php
 */

// Start session and include required files
session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';

// Initialize authentication and RBAC
$auth = new Auth();
$rbac = new RBAC();

// Check if user is logged in and has student role
$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('student', '../../auth/login.php');

// Get current user data
$currentUser = $auth->getCurrentUser();
$dashboardStats = $auth->getDashboardStats();

// Get today's classes for this student's course
$currentUser = $auth->getCurrentUser();
$student_id = $currentUser['user_id'];
$bookings = $auth->getStudentBookings($student_id);


// Get upcoming classes (next 7 days)
$currentUser = $auth->getCurrentUser();
$course_id = $currentUser['course_id'] ?? null;

// TEMP fallback for testing
if (!$course_id) {
    $course_id = 1; // Replace with a real course_id in your DB
}

$upcomingClasses = $auth->getUpcomingClassesForStudent($course_id);


// Get notifications
$notifications = $auth->getNotifications(false, 10);

// Get course information
$currentUser = $auth->getCurrentUser();
$course_id = $currentUser['course_id'] ?? null;

// TEMP fallback for testing (remove in production)
if (!$course_id) {
    $course_id = 1; // Replace with a valid course_id in your DB
}

// Fetch course info using the Auth method instead of direct $db access
$courseInfo = $auth->getCourseInfoForStudent($course_id);

// Fetch upcoming classes (as in your example)
$upcomingClasses = $auth->getUpcomingClassesForStudent($course_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - ClassReserve CHAU</title>
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
        
        .class-card {
            background: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .time-badge {
            background: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .course-info {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation Bar -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-user-graduate"></i> Student Dashboard
                </a>
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="class_status.php"><i class="fas fa-calendar"></i> Class Status</a></li>
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
                        <h5 class="mb-3"><i class="fas fa-user-graduate"></i> Student Panel</h5>
                        <nav class="nav flex-column">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                            <a class="nav-link" href="class_status.php">
                                <i class="fas fa-calendar-alt"></i> Class Schedule
                            </a>
                            <a class="nav-link" href="feedback.php">
                                <i class="fas fa-comment"></i> Submit Feedback
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
                                <h2><i class="fas fa-user-graduate text-primary"></i> Welcome, <?php echo htmlspecialchars($currentUser['name']); ?>!</h2>
                                <p class="text-muted">Student Dashboard - View your class schedules and stay updated</p>
                            </div>
                        </div>

                        <!-- Course Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="course-info">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5><i class="fas fa-book"></i> Course Information</h5>
                                            <p class="mb-1"><strong>Course:</strong> <?php echo htmlspecialchars($currentUser['course_name']); ?> (<?php echo htmlspecialchars($currentUser['course_code']); ?>)</p>
                                            <p class="mb-1"><strong>Program:</strong> <?php echo htmlspecialchars($currentUser['program_name']); ?></p>
                                            <p class="mb-0"><strong>Year:</strong> <?php echo htmlspecialchars($currentUser['year_of_study']); ?> | <strong>Intake:</strong> <?php echo htmlspecialchars($currentUser['intake']); ?></p>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <h5><i class="fas fa-id-card"></i> Student ID</h5>
                                            <p class="mb-0"><strong><?php echo htmlspecialchars($currentUser['student_id']); ?></strong></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-primary me-3">
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
                                        <div class="stat-icon bg-info me-3">
                                            <i class="fas fa-bell"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo count(array_filter($notifications, function($n) { return !$n['is_read']; })); ?></h4>
                                            <small class="text-muted">New Notifications</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-warning me-3">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo $dashboardStats['classes_today'] ?? 0; ?></h4>
                                            <small class="text-muted">Total Classes Today</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Classes -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="table-container">
                                    <h5><i class="fas fa-clock text-primary"></i> Today's Classes</h5>
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
                                                        <?php if ($class['lecturer_name']): ?>
                                                            <p class="mb-2 text-muted">
                                                                <i class="fas fa-chalkboard-teacher"></i> 
                                                                Lecturer: <?php echo htmlspecialchars($class['lecturer_name']); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        <p class="mb-0 text-muted">
                                                            <i class="fas fa-user-tie"></i> 
                                                            Booked by: <?php echo htmlspecialchars($class['booked_by_name']); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions and Notifications -->
                        <div class="row">
                            <!-- Quick Actions -->
                            <div class="col-md-6 mb-4">
                                <div class="table-container">
                                    <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <a href="class_status.php" class="btn btn-primary btn-custom w-100">
                                                <i class="fas fa-calendar-alt"></i> View Schedule
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <a href="feedback.php" class="btn btn-success btn-custom w-100">
                                                <i class="fas fa-comment"></i> Submit Feedback
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <a href="profile.php" class="btn btn-info btn-custom w-100">
                                                <i class="fas fa-user"></i> Edit Profile
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <a href="notifications.php" class="btn btn-warning btn-custom w-100">
                                                <i class="fas fa-bell"></i> View Notifications
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Notifications -->
                            <div class="col-md-6 mb-4">
                                <div class="table-container">
                                    <h5><i class="fas fa-bell text-warning"></i> Recent Notifications</h5>
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

                        <!-- Upcoming Classes -->
                        <div class="row">
                            <div class="col-12">
                                <div class="table-container">
                                    <h5><i class="fas fa-calendar-week text-success"></i> Upcoming Classes (Next 7 Days)</h5>
                                    <?php if (empty($upcomingClasses)): ?>
                                        <p class="text-muted">No upcoming classes scheduled</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Time</th>
                                                        <th>Course</th>
                                                        <th>Room</th>
                                                        <th>Lecturer</th>
                                                        <th>Booked By</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($upcomingClasses as $class): ?>
                                                        <tr>
                                                            <td><?php echo date('M d, Y', strtotime($class['booking_date'])); ?></td>
                                                            <td>
                                                                <?php echo date('H:i', strtotime($class['start_time'])); ?> - 
                                                                <?php echo date('H:i', strtotime($class['end_time'])); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($class['course_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($class['room_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($class['lecturer_name'] ?? 'TBA'); ?></td>
                                                            <td><?php echo htmlspecialchars($class['booked_by_name']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
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