<?php
/**
 * Lecturer Dashboard - Pending Approvals
 * File: dashboards/lecturer/pending_approvals.php
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

// Handle approval/rejection actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_booking'])) {
        $booking_id = $_POST['booking_id'];
        $result = $auth->approveBooking($booking_id, $lecturer_id);
        
        if ($result) {
            $message = "Booking approved successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to approve booking. Please try again.";
            $messageType = "danger";
        }
    } elseif (isset($_POST['reject_booking'])) {
        $booking_id = $_POST['booking_id'];
        $rejection_reason = $_POST['rejection_reason'] ?? '';
        $result = $auth->rejectBooking($booking_id, $lecturer_id, $rejection_reason);
        
        if ($result) {
            $message = "Booking rejected successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to reject booking. Please try again.";
            $messageType = "danger";
        }
    }
}

// Get pending bookings for this lecturer
$pendingBookings = $auth->getPendingBookings($lecturer_id);
$totalPending = count($pendingBookings);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals - ClassReserve CHAU</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .table-container h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }

        .badge-status {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .priority-high {
            background-color: #ff6b6b;
            color: white;
        }

        .priority-medium {
            background-color: #feca57;
            color: white;
        }

        .priority-low {
            background-color: #48dbfb;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
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
                                <a class="nav-link active" href="pending_approvals.php">
                                    <i class="fas fa-clock"></i> Pending Approvals
                                    <?php if ($totalPending > 0): ?>
                                        <span class="badge bg-warning ms-2"><?php echo $totalPending; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="class_schedule.php">
                                    <i class="fas fa-calendar-alt"></i> Class Schedule
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
                                <h2><i class="fas fa-clock text-warning"></i> Pending Approvals</h2>
                                <p class="text-muted">Review and manage classroom booking requests</p>
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

                        <!-- Statistics Card -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="stats-card text-center">
                                    <div class="stats-number text-warning"><?php echo $totalPending; ?></div>
                                    <h6><i class="fas fa-clock"></i> Pending Approvals</h6>
                                    <small class="text-muted">Awaiting your review</small>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Approvals Table -->
                        <div class="table-container">
                            <h5><i class="fas fa-list"></i> Booking Requests</h5>
                            
                            <?php if (empty($pendingBookings)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                                    <h4 class="mt-3">All Caught Up!</h4>
                                    <p class="text-muted">No pending approvals at the moment.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="approvalsTable" class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Priority</th>
                                                <th>Student</th>
                                                <th>Course</th>
                                                <th>Room</th>
                                                <th>Date & Time</th>
                                                <th>Duration</th>
                                                <th>Purpose</th>
                                                <th>Requested</th>
                                                <th width="200">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pendingBookings as $booking): ?>
                                                <?php
                                                // Determine priority based on booking date proximity
                                                $booking_date = strtotime($booking['booking_date']);
                                                $days_until = ceil(($booking_date - time()) / (60 * 60 * 24));
                                                
                                                if ($days_until <= 1) {
                                                    $priority_class = 'priority-high';
                                                    $priority_text = 'High';
                                                } elseif ($days_until <= 3) {
                                                    $priority_class = 'priority-medium';
                                                    $priority_text = 'Medium';
                                                } else {
                                                    $priority_class = 'priority-low';
                                                    $priority_text = 'Low';
                                                }
                                                ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-status <?php echo $priority_class; ?>">
                                                            <?php echo $priority_text; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($booking['student_name'] ?? 'N/A'); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($booking['student_email'] ?? ''); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($booking['course_name']); ?></td>
                                                    <td>
                                                        <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($booking['room_name']); ?>
                                                        <br><small class="text-muted">Capacity: <?php echo $booking['room_capacity'] ?? 'N/A'; ?></small>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></strong>
                                                        <br><small class="text-muted">
                                                            <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                                                            <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $start = strtotime($booking['start_time']);
                                                        $end = strtotime($booking['end_time']);
                                                        $duration = ($end - $start) / 3600;
                                                        echo $duration . ' hour' . ($duration != 1 ? 's' : '');
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <small><?php echo htmlspecialchars($booking['purpose'] ?? 'Not specified'); ?></small>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M d, h:i A', strtotime($booking['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                                <button type="submit" name="approve_booking" 
                                                                        class="btn btn-success btn-sm" 
                                                                        onclick="return confirm('Are you sure you want to approve this booking?')">
                                                                    <i class="fas fa-check"></i> Approve
                                                                </button>
                                                            </form>
                                                            <button type="button" class="btn btn-danger btn-sm" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#rejectModal" 
                                                                    data-booking-id="<?php echo $booking['booking_id']; ?>"
                                                                    data-booking-details="<?php echo htmlspecialchars($booking['course_name'] . ' - ' . $booking['room_name'] . ' - ' . date('M d, Y', strtotime($booking['booking_date']))); ?>">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        </div>
                                                    </td>
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

    <!-- Reject Booking Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-times-circle text-danger"></i> Reject Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="booking_id" id="rejectBookingId">
                        <p>Are you sure you want to reject this booking?</p>
                        <p class="text-muted" id="bookingDetails"></p>
                        
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">Reason for rejection (optional):</label>
                            <textarea class="form-control" name="rejection_reason" id="rejection_reason" 
                                      rows="3" placeholder="Please provide a reason for rejection..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reject_booking" class="btn btn-danger">
                            <i class="fas fa-times"></i> Reject Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#approvalsTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']] // Sort by priority
            });

            // Handle reject modal
            $('#rejectModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const bookingId = button.data('booking-id');
                const bookingDetails = button.data('booking-details');
                
                $('#rejectBookingId').val(bookingId);
                $('#bookingDetails').text(bookingDetails);
            });
        });
    </script>
</body>
</html>
