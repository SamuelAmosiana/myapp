<?php
/**
 * Class Representative Dashboard - ClassReserve CHAU
 * File: dashboards/class_rep/dashboard.php
 */

// Start session and include required files
session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';

// Initialize authentication and RBAC
$auth = new Auth();
$rbac = new RBAC();

// Check if user is logged in and has class_rep role
$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('class_rep', '../../auth/login.php');

// Get current user data
$currentUser = $auth->getCurrentUser();
$dashboardStats = $auth->getDashboardStats();

// Get daily booking limit info
$bookingLimit = $auth->checkDailyBookingLimit();

// Get today's bookings for this class rep
$todaysBookings = $auth->db->fetchAll(
    "SELECT b.*, r.room_name, r.location, c.course_name, c.course_code,
            l.name as lecturer_name, p.program_name
     FROM bookings b
     JOIN rooms r ON b.room_id = r.room_id
     JOIN courses c ON b.course_id = c.course_id
     JOIN programs p ON c.program_id = p.program_id
     LEFT JOIN users l ON b.lecturer_id = l.user_id
     WHERE b.booked_by = :user_id 
     AND b.booking_date = CURDATE()
     ORDER BY b.start_time ASC",
    ['user_id' => $currentUser['user_id']]
);

// Get upcoming bookings (next 7 days)
$upcomingBookings = $auth->db->fetchAll(
    "SELECT b.*, r.room_name, r.location, c.course_name, c.course_code,
            l.name as lecturer_name, p.program_name
     FROM bookings b
     JOIN rooms r ON b.room_id = r.room_id
     JOIN courses c ON b.course_id = c.course_id
     JOIN programs p ON c.program_id = p.program_id
     LEFT JOIN users l ON b.lecturer_id = l.user_id
     WHERE b.booked_by = :user_id 
     AND b.booking_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
     ORDER BY b.booking_date ASC, b.start_time ASC",
    ['user_id' => $currentUser['user_id']]
);

// Get available lecturers for this course
$availableLecturers = $auth->getAvailableLecturers($currentUser['course_id']);

// Get notifications
$notifications = $auth->getNotifications(false, 10);

// Get available rooms
$availableRooms = $auth->db->fetchAll(
    "SELECT r.*, 
            (SELECT COUNT(*) FROM bookings b 
             WHERE b.room_id = r.room_id 
             AND b.booking_date = CURDATE() 
             AND b.status = 'approved') as bookings_today
     FROM rooms r 
     WHERE r.is_available = 1
     ORDER BY r.room_name"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Representative Dashboard - ClassReserve CHAU</title>
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
            background: linear-gradient(135deg, var(--success-color) 0%, var(--primary-color) 100%);
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
            background: linear-gradient(135deg, var(--success-color) 0%, var(--primary-color) 100%);
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
        
        .booking-card {
            background: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--success-color);
        }
        
        .time-badge {
            background: var(--success-color);
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
        
        .status-cancelled {
            background: var(--danger-color);
            color: white;
        }
        
        .limit-warning {
            background: var(--warning-color);
            color: #333;
            border-radius: 10px;
            padding: 15px;
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
                    <i class="fas fa-user-tie"></i> Class Representative Dashboard
                </a>
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="bookings.php"><i class="fas fa-calendar"></i> My Bookings</a></li>
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
                        <h5 class="mb-3"><i class="fas fa-user-tie"></i> Class Rep Panel</h5>
                        <nav class="nav flex-column">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                            <a class="nav-link" href="book_room.php">
                                <i class="fas fa-plus-circle"></i> Book Room
                            </a>
                            <a class="nav-link" href="my_bookings.php">
                                <i class="fas fa-calendar-alt"></i> My Bookings
                            </a>
                            <a class="nav-link" href="schedule_class.php">
                                <i class="fas fa-clock"></i> Schedule Class
                            </a>
                            <a class="nav-link" href="view_responses.php">
                                <i class="fas fa-comments"></i> Lecturer Responses
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
                                <h2><i class="fas fa-user-tie text-success"></i> Welcome, <?php echo htmlspecialchars($currentUser['name']); ?>!</h2>
                                <p class="text-muted">Class Representative Dashboard - <?php echo htmlspecialchars($currentUser['course_name']); ?> (<?php echo htmlspecialchars($currentUser['program_name']); ?>)</p>
                            </div>
                        </div>

                        <!-- Daily Limit Warning -->
                        <?php if (!$bookingLimit['can_book']): ?>
                            <div class="limit-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Daily Booking Limit Reached!</strong> You have used your 2-hour daily booking limit. 
                                New bookings will be available tomorrow.
                            </div>
                        <?php endif; ?>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-success me-3">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo count($todaysBookings); ?></h4>
                                            <small class="text-muted">Today's Bookings</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-info me-3">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo $bookingLimit['remaining_hours']; ?>h</h4>
                                            <small class="text-muted">Remaining Hours</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-warning me-3">
                                            <i class="fas fa-calendar-week"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?php echo count($upcomingBookings); ?></h4>
                                            <small class="text-muted">Upcoming Bookings</small>
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

                        <!-- Today's Bookings -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="table-container">
                                    <h5><i class="fas fa-calendar-check text-success"></i> Today's Bookings</h5>
                                    <?php if (empty($todaysBookings)): ?>
                                        <p class="text-muted">No bookings scheduled for today</p>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($todaysBookings as $booking): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="booking-card">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($booking['course_name']); ?></h6>
                                                            <span class="time-badge">
                                                                <?php echo date('H:i', strtotime($booking['start_time'])); ?> - 
                                                                <?php echo date('H:i', strtotime($booking['end_time'])); ?>
                                                            </span>
                                                        </div>
                                                        <p class="mb-2 text-muted">
                                                            <i class="fas fa-map-marker-alt"></i> 
                                                            <?php echo htmlspecialchars($booking['room_name']); ?> 
                                                            (<?php echo htmlspecialchars($booking['location']); ?>)
                                                        </p>
                                                        <?php if ($booking['lecturer_name']): ?>
                                                            <p class="mb-2 text-muted">
                                                                <i class="fas fa-chalkboard-teacher"></i> 
                                                                Lecturer: <?php echo htmlspecialchars($booking['lecturer_name']); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                                <?php echo ucfirst($booking['status']); ?>
                                                            </span>
                                                            <div>
                                                                <a href="edit_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                                                                   class="btn btn-sm btn-primary">Edit</a>
                                                                <a href="cancel_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                                                                   class="btn btn-sm btn-danger">Cancel</a>
                                                            </div>
                                                        </div>
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
                                            <a href="book_room.php" class="btn btn-success btn-custom w-100">
                                                <i class="fas fa-plus-circle"></i> Book Room
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <a href="schedule_class.php" class="btn btn-primary btn-custom w-100">
                                                <i class="fas fa-clock"></i> Schedule Class
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <a href="my_bookings.php" class="btn btn-info btn-custom w-100">
                                                <i class="fas fa-calendar-alt"></i> View All Bookings
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <a href="view_responses.php" class="btn btn-warning btn-custom w-100">
                                                <i class="fas fa-comments"></i> Lecturer Responses
                                            </a>
                                        </div>
                                    </div>
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

                        <!-- Available Rooms -->
                        <div class="row">
                            <div class="col-12">
                                <div class="table-container">
                                    <h5><i class="fas fa-building text-info"></i> Available Rooms Today</h5>
                                    <?php if (empty($availableRooms)): ?>
                                        <p class="text-muted">No rooms available</p>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach (array_slice($availableRooms, 0, 6) as $room): ?>
                                                <div class="col-md-4 mb-3">
                                                    <div class="booking-card">
                                                        <h6 class="mb-2"><?php echo htmlspecialchars($room['room_name']); ?></h6>
                                                        <p class="mb-2 text-muted">
                                                            <i class="fas fa-map-marker-alt"></i> 
                                                            <?php echo htmlspecialchars($room['location']); ?>
                                                        </p>
                                                        <p class="mb-2 text-muted">
                                                            <i class="fas fa-users"></i> 
                                                            Capacity: <?php echo $room['capacity']; ?> students
                                                        </p>
                                                        <p class="mb-2 text-muted">
                                                            <i class="fas fa-calendar"></i> 
                                                            <?php echo $room['bookings_today']; ?> bookings today
                                                        </p>
                                                        <a href="book_room.php?room_id=<?php echo $room['room_id']; ?>" 
                                                           class="btn btn-sm btn-success">Book This Room</a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
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