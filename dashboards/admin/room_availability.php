<?php
/**
 * Admin Room Availability Dashboard - ClassReserve CHAU
 * File: dashboards/admin/room_availability.php
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

// Get detailed room availability data
$roomAvailability = $auth->getDetailedRoomAvailability();
$todayBookings = $auth->getTodayBookingsWithTiming();

// Helper function to format time duration
function formatTimeDuration($minutes) {
    if ($minutes < 0) return "Ended";
    if ($minutes < 60) return $minutes . " min";
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . "h " . ($mins > 0 ? $mins . "m" : "");
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'occupied': return 'bg-danger';
        case 'available_until': return 'bg-warning';
        case 'available_all_day': return 'bg-success';
        case 'current': return 'bg-primary';
        case 'upcoming': return 'bg-info';
        case 'completed': return 'bg-secondary';
        default: return 'bg-light';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Availability - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-container {
            min-height: 100vh;
            padding: 20px 0;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            height: fit-content;
        }

        .sidebar h5 {
            color: white;
            font-weight: 600;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
            transform: translateX(5px);
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .availability-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .availability-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .room-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .room-details {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .booking-info {
            background: rgba(0, 123, 255, 0.1);
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .booking-info.occupied {
            background: rgba(220, 53, 69, 0.1);
            border-left-color: #dc3545;
        }

        .booking-info.available {
            background: rgba(40, 167, 69, 0.1);
            border-left-color: #28a745;
        }

        .time-indicator {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .refresh-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            z-index: 1000;
        }

        .stats-row {
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 10px;
            }
            
            .main-content {
                padding: 20px;
                margin-top: 20px;
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
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
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
                            <a class="nav-link" href="dashboard.php">
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
                            <a class="nav-link active" href="room_availability.php">
                                <i class="fas fa-clock"></i> Room Availability
                            </a>
                            <a class="nav-link" href="bookings.php">
                                <i class="fas fa-calendar-alt"></i> All Bookings
                            </a>
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-md-9 col-lg-10">
                    <div class="main-content">
                        <!-- Header -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h2><i class="fas fa-clock text-primary"></i> Real-Time Room Availability</h2>
                                <p class="text-muted">Monitor current room usage and upcoming availability in real-time</p>
                            </div>
                        </div>

                        <!-- Statistics Row -->
                        <div class="stats-row">
                            <div class="row">
                                <?php
                                $availableCount = 0;
                                $occupiedCount = 0;
                                $totalRooms = count($roomAvailability);
                                
                                foreach ($roomAvailability as $room) {
                                    if ($room['availability_status'] === 'occupied') {
                                        $occupiedCount++;
                                    } else {
                                        $availableCount++;
                                    }
                                }
                                ?>
                                <div class="col-md-3 mb-3">
                                    <div class="stat-card">
                                        <div class="stat-number"><?php echo $totalRooms; ?></div>
                                        <div class="stat-label">Total Rooms</div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                        <div class="stat-number"><?php echo $availableCount; ?></div>
                                        <div class="stat-label">Available Now</div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stat-card" style="background: linear-gradient(135deg, #dc3545, #e74c3c);">
                                        <div class="stat-number"><?php echo $occupiedCount; ?></div>
                                        <div class="stat-label">Currently Occupied</div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stat-card" style="background: linear-gradient(135deg, #17a2b8, #20c997);">
                                        <div class="stat-number"><?php echo count($todayBookings); ?></div>
                                        <div class="stat-label">Today's Bookings</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Room Availability Cards -->
                        <div class="row">
                            <div class="col-12">
                                <h4 class="mb-4"><i class="fas fa-building"></i> Room Status Overview</h4>
                            </div>
                        </div>

                        <div class="row">
                            <?php foreach ($roomAvailability as $room): ?>
                                <div class="col-lg-6 col-xl-4 mb-4">
                                    <div class="availability-card">
                                        <div class="room-header">
                                            <div class="room-title">
                                                <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($room['room_name']); ?>
                                            </div>
                                            <span class="status-badge <?php echo getStatusBadgeClass($room['availability_status']); ?>">
                                                <?php 
                                                switch ($room['availability_status']) {
                                                    case 'occupied': echo 'In Use'; break;
                                                    case 'available_until': echo 'Available'; break;
                                                    case 'available_all_day': echo 'Free All Day'; break;
                                                }
                                                ?>
                                            </span>
                                        </div>

                                        <div class="room-details">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($room['location']); ?> •
                                            <i class="fas fa-users"></i> <?php echo $room['capacity']; ?> capacity •
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($room['room_type']); ?>
                                        </div>

                                        <?php if ($room['facilities']): ?>
                                            <div class="room-details">
                                                <i class="fas fa-tools"></i> <?php echo htmlspecialchars($room['facilities']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($room['availability_status'] === 'occupied'): ?>
                                            <div class="booking-info occupied">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong><i class="fas fa-clock"></i> Currently In Use</strong>
                                                    <span class="time-indicator text-danger">
                                                        Until <?php echo date('H:i', strtotime($room['current_end'])); ?>
                                                    </span>
                                                </div>
                                                <div class="text-muted">
                                                    <i class="fas fa-book"></i> <?php echo htmlspecialchars($room['current_course_name']); ?><br>
                                                    <i class="fas fa-user"></i> Booked by: <?php echo htmlspecialchars($room['current_booked_by']); ?>
                                                </div>
                                            </div>
                                        <?php elseif ($room['availability_status'] === 'available_until'): ?>
                                            <div class="booking-info available">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong><i class="fas fa-check-circle"></i> Available Now</strong>
                                                    <span class="time-indicator text-warning">
                                                        Until <?php echo date('H:i', strtotime($room['next_start'])); ?>
                                                    </span>
                                                </div>
                                                <div class="text-muted">
                                                    <i class="fas fa-calendar-alt"></i> Next booking: <?php echo htmlspecialchars($room['next_course_name']); ?><br>
                                                    <i class="fas fa-user"></i> By: <?php echo htmlspecialchars($room['next_booked_by']); ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="booking-info available">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <strong><i class="fas fa-check-circle"></i> Available All Day</strong>
                                                    <span class="time-indicator text-success">No bookings today</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Today's Bookings Timeline -->
                        <div class="row mt-5">
                            <div class="col-12">
                                <h4 class="mb-4"><i class="fas fa-calendar-day"></i> Today's Booking Timeline</h4>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th><i class="fas fa-clock"></i> Time</th>
                                                <th><i class="fas fa-door-open"></i> Room</th>
                                                <th><i class="fas fa-book"></i> Course</th>
                                                <th><i class="fas fa-user"></i> Booked By</th>
                                                <th><i class="fas fa-chalkboard-teacher"></i> Lecturer</th>
                                                <th><i class="fas fa-hourglass-half"></i> Duration</th>
                                                <th><i class="fas fa-info-circle"></i> Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($todayBookings)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted py-4">
                                                        <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                                                        No bookings scheduled for today
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($todayBookings as $booking): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo date('H:i', strtotime($booking['start_time'])); ?></strong>
                                                            -
                                                            <strong><?php echo date('H:i', strtotime($booking['end_time'])); ?></strong>
                                                        </td>
                                                        <td>
                                                            <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($booking['room_name']); ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($booking['location']); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlspecialchars($booking['course_name']); ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($booking['course_code']); ?></small>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($booking['booked_by_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($booking['lecturer_name'] ?: 'Not assigned'); ?></td>
                                                        <td><?php echo formatTimeDuration($booking['duration_minutes']); ?></td>
                                                        <td>
                                                            <span class="badge <?php echo getStatusBadgeClass($booking['booking_status']); ?>">
                                                                <?php 
                                                                switch ($booking['booking_status']) {
                                                                    case 'current': 
                                                                        echo 'In Progress (' . formatTimeDuration($booking['minutes_until_end']) . ' left)'; 
                                                                        break;
                                                                    case 'upcoming': 
                                                                        echo 'Starts in ' . formatTimeDuration($booking['minutes_until_start']); 
                                                                        break;
                                                                    case 'completed': 
                                                                        echo 'Completed'; 
                                                                        break;
                                                                }
                                                                ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Auto-refresh indicator -->
    <div class="refresh-indicator">
        <i class="fas fa-sync-alt"></i> Auto-refresh: <span id="countdown">60</span>s
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-refresh functionality
        let countdown = 60;
        const countdownElement = document.getElementById('countdown');
        
        function updateCountdown() {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                location.reload();
            }
        }
        
        // Update countdown every second
        setInterval(updateCountdown, 1000);
        
        // Add smooth animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.availability-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
