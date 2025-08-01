<?php
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';
require_once __DIR__ . '/../../config/Database.php';

session_start();
$auth = new Auth();
$rbac = new RBAC();
$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('admin', '../../auth/login.php');

$db = Database::getInstance();

// Date filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Summary stats
$total = $db->fetchOne("SELECT COUNT(*) as cnt FROM bookings WHERE booking_date BETWEEN ? AND ?", [$start_date, $end_date])['cnt'];
$approved = $db->fetchOne("SELECT COUNT(*) as cnt FROM bookings WHERE status='approved' AND booking_date BETWEEN ? AND ?", [$start_date, $end_date])['cnt'];
$pending = $db->fetchOne("SELECT COUNT(*) as cnt FROM bookings WHERE status='pending' AND booking_date BETWEEN ? AND ?", [$start_date, $end_date])['cnt'];
$cancelled = $db->fetchOne("SELECT COUNT(*) as cnt FROM bookings WHERE status='cancelled' AND booking_date BETWEEN ? AND ?", [$start_date, $end_date])['cnt'];

// Most booked rooms
$top_rooms = $db->fetchAll("SELECT r.room_name, COUNT(*) as cnt FROM bookings b LEFT JOIN rooms r ON b.room_id = r.room_id WHERE b.booking_date BETWEEN ? AND ? GROUP BY b.room_id ORDER BY cnt DESC LIMIT 5", [$start_date, $end_date]);
// Most booked courses
$top_courses = $db->fetchAll("SELECT c.course_name, COUNT(*) as cnt FROM bookings b LEFT JOIN courses c ON b.course_id = c.course_id WHERE b.booking_date BETWEEN ? AND ? GROUP BY b.course_id ORDER BY cnt DESC LIMIT 5", [$start_date, $end_date]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
    <form class="row g-2 mb-4" method="get">
        <div class="col-md-3">
            <label>Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="col-md-3">
            <label>End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button>
        </div>
    </form>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Bookings</h5>
                    <p class="display-6 text-primary"><?= $total ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Approved</h5>
                    <p class="display-6 text-success"><?= $approved ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <p class="display-6 text-warning"><?= $pending ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Cancelled</h5>
                    <p class="display-6 text-danger"><?= $cancelled ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Top 5 Most Booked Rooms</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($top_rooms as $room): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($room['room_name']) ?>
                            <span class="badge bg-primary rounded-pill"><?= $room['cnt'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Top 5 Most Booked Courses</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($top_courses as $course): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($course['course_name']) ?>
                            <span class="badge bg-info rounded-pill"><?= $course['cnt'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-link mt-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>