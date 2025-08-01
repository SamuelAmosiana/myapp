<?php
/**
 * Lecturer Dashboard - My Classes
 * File: dashboards/lecturer/my_classes.php
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

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_class_notes'])) {
        $class_id = $_POST['class_id'];
        $notes = $_POST['class_notes'];
        // For now, we'll show a success message since updateClassNotes method doesn't exist
        $message = "Class notes feature will be implemented soon!";
        $messageType = "info";
    }
}

// Get lecturer's classes using only working methods
$todaysClasses = $auth->getTodaysClasses($lecturer_id);
$pendingBookings = $auth->getPendingBookings($lecturer_id);

// Create a combined classes array from available data
$myClasses = [];

// Add today's classes
foreach ($todaysClasses as $class) {
    $class['status'] = 'active';
    $class['class_type'] = 'today';
    $myClasses[] = $class;
}

// Add pending classes
foreach ($pendingBookings as $booking) {
    $booking['status'] = 'pending';
    $booking['class_type'] = 'pending';
    $myClasses[] = $booking;
}

$totalClasses = count($myClasses);

// For demonstration purposes, let's create some sample upcoming classes
// In a real implementation, you would create a proper getUpcomingClasses method
$upcomingClasses = []; // Empty for now to avoid database errors

// Group classes by status
$activeClasses = array_filter($myClasses, function($class) {
    return $class['status'] === 'active';
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes - ClassReserve CHAU</title>
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

        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: none;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .class-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: none;
            transition: transform 0.3s ease;
        }

        .class-card:hover {
            transform: translateY(-3px);
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .class-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .class-code {
            background: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .class-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-item i {
            color: var(--primary-color);
            width: 16px;
        }

        .badge-status {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background-color: var(--success-color);
            color: white;
        }

        .status-pending {
            background-color: var(--warning-color);
            color: white;
        }

        .class-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-tabs {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .nav-pills .nav-link {
            border-radius: 20px;
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        @media (max-width: 768px) {
            .class-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .class-info {
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
                                <a class="nav-link active" href="my_classes.php">
                                    <i class="fas fa-book"></i> My Classes
                                    <span class="badge bg-info ms-2"><?php echo $totalClasses; ?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="profile.php">
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
                                <h2><i class="fas fa-book text-info"></i> My Classes</h2>
                                <p class="text-muted">Manage and view all your assigned classes</p>
                            </div>
                        </div>

                        <!-- Alert Messages -->
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="stats-number text-info"><?php echo $totalClasses; ?></div>
                                    <h6><i class="fas fa-book"></i> Total Classes</h6>
                                    <small class="text-muted">All assigned classes</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="stats-number text-success"><?php echo count($activeClasses); ?></div>
                                    <h6><i class="fas fa-play-circle"></i> Active Classes</h6>
                                    <small class="text-muted">Currently running</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="stats-number text-primary"><?php echo count($todaysClasses); ?></div>
                                    <h6><i class="fas fa-calendar-day"></i> Today's Classes</h6>
                                    <small class="text-muted">Classes scheduled today</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="stats-number text-warning"><?php echo count($pendingBookings); ?></div>
                                    <h6><i class="fas fa-clock"></i> Pending</h6>
                                    <small class="text-muted">Awaiting approval</small>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Tabs -->
                        <div class="filter-tabs">
                            <ul class="nav nav-pills" id="classFilter" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="all-tab" data-bs-toggle="pill" data-bs-target="#all-classes" type="button" role="tab">
                                        <i class="fas fa-list"></i> All Classes (<?php echo $totalClasses; ?>)
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="active-tab" data-bs-toggle="pill" data-bs-target="#active-classes" type="button" role="tab">
                                        <i class="fas fa-play-circle"></i> Active (<?php echo count($activeClasses); ?>)
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="today-tab" data-bs-toggle="pill" data-bs-target="#today-classes" type="button" role="tab">
                                        <i class="fas fa-calendar-day"></i> Today (<?php echo count($todaysClasses); ?>)
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content" id="classFilterContent">
                            <!-- All Classes Tab -->
                            <div class="tab-pane fade show active" id="all-classes" role="tabpanel">
                                <?php if (empty($myClasses)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-book-open text-muted" style="font-size: 4rem;"></i>
                                        <h4 class="mt-3">No Classes Assigned</h4>
                                        <p class="text-muted">You don't have any classes assigned yet.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($myClasses as $class): ?>
                                        <div class="class-card">
                                            <div class="class-header">
                                                <div>
                                                    <h5 class="class-title"><?php echo htmlspecialchars($class['course_name']); ?></h5>
                                                    <span class="class-code"><?php echo htmlspecialchars($class['course_code']); ?></span>
                                                </div>
                                                <span class="badge badge-status status-<?php echo $class['status']; ?>">
                                                    <?php echo ucfirst($class['status']); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="class-info">
                                                <div class="info-item">
                                                    <i class="fas fa-user"></i>
                                                    <span><strong>Booked by:</strong> <?php echo htmlspecialchars($class['booked_by_name'] ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <i class="fas fa-clock"></i>
                                                    <span><strong>Time:</strong> 
                                                        <?php echo date('h:i A', strtotime($class['start_time'])); ?> - 
                                                        <?php echo date('h:i A', strtotime($class['end_time'])); ?>
                                                    </span>
                                                </div>
                                                <div class="info-item">
                                                    <i class="fas fa-calendar"></i>
                                                    <span><strong>Date:</strong> <?php echo date('M d, Y', strtotime($class['booking_date'])); ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <i class="fas fa-door-open"></i>
                                                    <span><strong>Room:</strong> <?php echo htmlspecialchars($class['room_name'] ?? 'Not assigned'); ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <i class="fas fa-graduation-cap"></i>
                                                    <span><strong>Program:</strong> <?php echo htmlspecialchars($class['program_name'] ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <i class="fas fa-calendar-plus"></i>
                                                    <span><strong>Created:</strong> <?php echo date('M d, Y', strtotime($class['created_at'])); ?></span>
                                                </div>
                                            </div>

                                            <?php if (!empty($class['description'])): ?>
                                                <div class="mb-3">
                                                    <strong>Description:</strong>
                                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($class['description']); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <div class="class-actions">
                                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#classDetailsModal" 
                                                        data-class-id="<?php echo $class['booking_id']; ?>">
                                                    <i class="fas fa-eye"></i> View Details
                                                </button>
                                                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#studentsModal"
                                                        data-class-id="<?php echo $class['booking_id']; ?>">
                                                    <i class="fas fa-users"></i> View Students
                                                </button>
                                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#notesModal"
                                                        data-class-id="<?php echo $class['booking_id']; ?>"
                                                        data-class-notes="<?php echo htmlspecialchars($class['notes'] ?? ''); ?>">
                                                    <i class="fas fa-sticky-note"></i> Notes
                                                </button>
                                                <a href="class_schedule.php?booking_id=<?php echo $class['booking_id']; ?>" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-calendar-alt"></i> Schedule
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Active Classes Tab -->
                            <div class="tab-pane fade" id="active-classes" role="tabpanel">
                                <?php if (empty($activeClasses)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-pause-circle text-muted" style="font-size: 4rem;"></i>
                                        <h4 class="mt-3">No Active Classes</h4>
                                        <p class="text-muted">No classes are currently active.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($activeClasses as $class): ?>
                                        <div class="class-card">
                                            <div class="class-header">
                                                <div>
                                                    <h5 class="class-title"><?php echo htmlspecialchars($class['course_name']); ?></h5>
                                                    <span class="class-code"><?php echo htmlspecialchars($class['course_code']); ?></span>
                                                </div>
                                                <span class="badge badge-status status-active">Active</span>
                                            </div>
                                            <div class="class-info">
                                                <div class="info-item">
                                                    <i class="fas fa-users"></i>
                                                    <span><strong>Students:</strong> <?php echo $class['student_count'] ?? '0'; ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <i class="fas fa-door-open"></i>
                                                    <span><strong>Room:</strong> <?php echo $class['room_name'] ?? 'Not assigned'; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Today's Classes Tab -->
                            <div class="tab-pane fade" id="today-classes" role="tabpanel">
                                <?php if (empty($todaysClasses)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-calendar-times text-muted" style="font-size: 4rem;"></i>
                                        <h4 class="mt-3">No Classes Today</h4>
                                        <p class="text-muted">You don't have any classes scheduled for today.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($todaysClasses as $class): ?>
                                        <div class="class-card">
                                            <div class="class-header">
                                                <div>
                                                    <h5 class="class-title"><?php echo htmlspecialchars($class['course_name']); ?></h5>
                                                    <span class="class-code"><?php echo htmlspecialchars($class['course_code']); ?></span>
                                                </div>
                                                <div>
                                                    <span class="badge bg-primary me-2">
                                                        <?php echo date('h:i A', strtotime($class['start_time'])); ?> - 
                                                        <?php echo date('h:i A', strtotime($class['end_time'])); ?>
                                                    </span>
                                                    <span class="badge badge-status status-active">Today</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes Modal -->
    <div class="modal fade" id="notesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-sticky-note"></i> Class Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="class_id" id="notesClassId">
                        <div class="mb-3">
                            <label for="class_notes" class="form-label">Notes:</label>
                            <textarea class="form-control" name="class_notes" id="class_notes" 
                                      rows="6" placeholder="Add your notes about this class..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_class_notes" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Notes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Handle notes modal
        document.getElementById('notesModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const classId = button.getAttribute('data-class-id');
            const classNotes = button.getAttribute('data-class-notes');
            
            document.getElementById('notesClassId').value = classId;
            document.getElementById('class_notes').value = classNotes;
        });
    </script>
</body>
</html>
