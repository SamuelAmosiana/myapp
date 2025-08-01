<?php
/**
 * Student Class Schedule - ClassReserve CHAU
 * File: dashboards/student/class_status.php
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
$course_id = $currentUser['course_id'] ?? null;

// Handle missing course_id
if (!$course_id) {
    $error_message = "No course assigned to your account. Please contact administration.";
    $todaysClasses = [];
    $upcomingClasses = [];
    $courseInfo = null;
} else {
    // Get class data
    $todaysClasses = $auth->getTodaysClassesForStudent($course_id);
    $upcomingClasses = $auth->getUpcomingClassesForStudent($course_id);
    $courseInfo = $auth->getCourseInfoForStudent($course_id);
}

// Get notifications
$notifications = $auth->getNotifications(false, 5);
$unreadCount = count(array_filter($notifications, function($n) { return !$n['is_read']; }));

// Calculate statistics
$totalClassesToday = count($todaysClasses);
$totalUpcomingClasses = count($upcomingClasses);
$nextClass = !empty($upcomingClasses) ? $upcomingClasses[0] : null;

// Group upcoming classes by date for better display
$classesByDate = [];
foreach ($upcomingClasses as $class) {
    $date = $class['booking_date'];
    if (!isset($classesByDate[$date])) {
        $classesByDate[$date] = [];
    }
    $classesByDate[$date][] = $class;
}

// Function to format time
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

// Function to format date
function formatDate($date) {
    return date('l, F j, Y', strtotime($date));
}

// Function to get status badge class
function getStatusBadge($status) {
    switch ($status) {
        case 'approved': return 'bg-success';
        case 'pending': return 'bg-warning';
        case 'rejected': return 'bg-danger';
        default: return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Schedule - ClassReserve CHAU</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body {
            background: white;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .sidebar {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 2rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 2rem;
            height: fit-content;
        }

        .sidebar h5 {
            color: #333;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .nav-link {
            color: rgba(51, 51, 51, 0.8) !important;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(102, 126, 234, 0.1);
            color: #333 !important;
            border-color: rgba(102, 126, 234, 0.3);
            transform: translateX(5px);
        }

        .main-content {
            padding: 2rem;
        }

        .page-header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .page-header h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: rgba(51, 51, 51, 0.8);
            margin: 0;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stat-icon.today { background: var(--success-gradient); }
        .stat-icon.upcoming { background: var(--secondary-gradient); }
        .stat-icon.next { background: var(--warning-gradient); }
        .stat-icon.course { background: var(--primary-gradient); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: rgba(51, 51, 51, 0.8);
            font-size: 0.9rem;
        }

        .content-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .content-card h5 {
            color: #333;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .class-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .class-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .class-time {
            font-size: 1.1rem;
            font-weight: 600;
            color: #4facfe;
            margin-bottom: 0.5rem;
        }

        .class-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .class-details {
            color: rgba(51, 51, 51, 0.8);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .class-location {
            color: #43e97b;
            font-weight: 500;
        }

        .class-lecturer {
            color: #f093fb;
            font-weight: 500;
        }

        .date-header {
            background: var(--secondary-gradient);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 15px;
            margin-bottom: 1rem;
            font-weight: 600;
            text-align: center;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: rgba(51, 51, 51, 0.7);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }

        .btn {
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-gradient);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .alert {
            border-radius: 15px;
            border: none;
            backdrop-filter: blur(20px);
        }

        .alert-danger {
            background: rgba(245, 87, 108, 0.2);
            color: white;
            border: 1px solid rgba(245, 87, 108, 0.3);
        }

        .table {
            color: #333;
        }

        .table th {
            border-color: rgba(51, 51, 51, 0.2);
            color: rgba(51, 51, 51, 0.9);
            font-weight: 600;
        }

        .table td {
            border-color: rgba(51, 51, 51, 0.1);
            color: rgba(51, 51, 51, 0.8);
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .class-item {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap"></i> ClassReserve CHAU
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($currentUser['name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
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
                    <h5><i class="fas fa-user-graduate"></i> Student Panel</h5>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a class="nav-link active" href="class_status.php">
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
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-danger ms-2"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Page Header -->
                    <div class="page-header">
                        <h2><i class="fas fa-calendar-week text-primary"></i> Class Schedule</h2>
                        <p>View your complete class schedule, including today's classes and upcoming sessions</p>
                        <?php if ($courseInfo): ?>
                            <div class="mt-3">
                                <span class="badge bg-primary me-2"><?php echo htmlspecialchars($courseInfo['course_code']); ?></span>
                                <span class="text-white"><?php echo htmlspecialchars($courseInfo['course_name']); ?></span>
                                <?php if (isset($courseInfo['program_name'])): ?>
                                    <span class="text-white-50 ms-2">• <?php echo htmlspecialchars($courseInfo['program_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error_message; ?>
                        </div>
                    <?php else: ?>
                        <!-- Statistics Cards -->
                        <div class="stats-container">
                            <div class="stat-card">
                                <div class="stat-icon today">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-value"><?php echo $totalClassesToday; ?></div>
                                <div class="stat-label">Classes Today</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon upcoming">
                                    <i class="fas fa-calendar-week"></i>
                                </div>
                                <div class="stat-value"><?php echo $totalUpcomingClasses; ?></div>
                                <div class="stat-label">Upcoming Classes</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon next">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                                <div class="stat-value">
                                    <?php if ($nextClass): ?>
                                        <?php echo formatTime($nextClass['start_time']); ?>
                                    <?php else: ?>
                                        --
                                    <?php endif; ?>
                                </div>
                                <div class="stat-label">Next Class</div>
                            </div>
                            
                            <?php if ($courseInfo): ?>
                            <div class="stat-card">
                                <div class="stat-icon course">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="stat-value"><?php echo htmlspecialchars($courseInfo['course_code']); ?></div>
                                <div class="stat-label">Your Course</div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Today's Classes -->
                        <div class="content-card">
                            <h5><i class="fas fa-clock text-success"></i> Today's Classes</h5>
                            <?php if (empty($todaysClasses)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h6>No Classes Today</h6>
                                    <p>You don't have any classes scheduled for today. Enjoy your free time!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($todaysClasses as $class): ?>
                                    <div class="class-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="class-time">
                                                    <i class="fas fa-clock me-2"></i>
                                                    <?php echo formatTime($class['start_time']) . ' - ' . formatTime($class['end_time']); ?>
                                                </div>
                                                <div class="class-title"><?php echo htmlspecialchars($class['course_name']); ?></div>
                                                <div class="class-details">
                                                    <span class="class-location">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?php echo htmlspecialchars($class['room_name']); ?>
                                                        <?php if (isset($class['location'])): ?>
                                                            (<?php echo htmlspecialchars($class['location']); ?>)
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                                <?php if (isset($class['lecturer_name'])): ?>
                                                    <div class="class-details">
                                                        <span class="class-lecturer">
                                                            <i class="fas fa-user-tie me-1"></i>
                                                            <?php echo htmlspecialchars($class['lecturer_name']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <span class="badge <?php echo getStatusBadge($class['status']); ?>">
                                                    <?php echo ucfirst($class['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Upcoming Classes -->
                        <div class="content-card">
                            <h5><i class="fas fa-calendar-week text-primary"></i> Upcoming Classes (Next 7 Days)</h5>
                            <?php if (empty($upcomingClasses)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-plus"></i>
                                    <h6>No Upcoming Classes</h6>
                                    <p>You don't have any classes scheduled for the next 7 days.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($classesByDate as $date => $classes): ?>
                                    <div class="date-header">
                                        <i class="fas fa-calendar-day me-2"></i>
                                        <?php echo formatDate($date); ?>
                                    </div>
                                    <?php foreach ($classes as $class): ?>
                                        <div class="class-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="class-time">
                                                        <i class="fas fa-clock me-2"></i>
                                                        <?php echo formatTime($class['start_time']) . ' - ' . formatTime($class['end_time']); ?>
                                                    </div>
                                                    <div class="class-title"><?php echo htmlspecialchars($class['course_name']); ?></div>
                                                    <div class="class-details">
                                                        <span class="class-location">
                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                            <?php echo htmlspecialchars($class['room_name']); ?>
                                                            <?php if (isset($class['location'])): ?>
                                                                (<?php echo htmlspecialchars($class['location']); ?>)
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <?php if (isset($class['lecturer_name'])): ?>
                                                        <div class="class-details">
                                                            <span class="class-lecturer">
                                                                <i class="fas fa-user-tie me-1"></i>
                                                                <?php echo htmlspecialchars($class['lecturer_name']); ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (isset($class['booked_by_name'])): ?>
                                                        <div class="class-details">
                                                            <span class="text-white-50">
                                                                <i class="fas fa-user me-1"></i>
                                                                Booked by: <?php echo htmlspecialchars($class['booked_by_name']); ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <span class="badge <?php echo getStatusBadge($class['status']); ?>">
                                                        <?php echo ucfirst($class['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Quick Actions -->
                        <div class="content-card">
                            <h5><i class="fas fa-bolt text-warning"></i> Quick Actions</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <a href="dashboard.php" class="btn btn-primary w-100">
                                        <i class="fas fa-home me-2"></i>
                                        Back to Dashboard
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="feedback.php" class="btn btn-outline-light w-100">
                                        <i class="fas fa-comment me-2"></i>
                                        Submit Feedback
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <script>
        // Auto-refresh page every 5 minutes to keep schedule updated
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutes
        
        // Add smooth scrolling for better UX
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Add loading animation for better perceived performance
        window.addEventListener('load', function() {
            document.body.style.opacity = '1';
        });
    </script>
</body>
</html>