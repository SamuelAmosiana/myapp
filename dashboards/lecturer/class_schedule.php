<?php
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';

session_start();
$auth = new Auth();
$rbac = new RBAC();
$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('lecturer', '../../auth/login.php');

$currentUser = $auth->getCurrentUser();
$lecturer_id = $currentUser['user_id'];

$todaysClasses = $auth->getTodaysClasses($lecturer_id);
$upcomingClasses = $auth->getUpcomingClasses($lecturer_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Schedule - Lecturer Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-calendar-alt"></i> My Schedule</h2>
    <div class="card mb-4">
        <div class="card-header">Today's Classes</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Room</th>
                        <th>Course</th>
                        <th>Program</th>
                        <th>Booked By</th>
                        <th>Subject</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($todaysClasses as $class): ?>
                    <tr>
                        <td><?= date('H:i', strtotime($class['start_time'])) ?> - <?= date('H:i', strtotime($class['end_time'])) ?></td>
                        <td><?= htmlspecialchars($class['room_name']) ?> (<?= htmlspecialchars($class['location']) ?>)</td>
                        <td><?= htmlspecialchars($class['course_name']) ?> (<?= htmlspecialchars($class['course_code']) ?>)</td>
                        <td><?= htmlspecialchars($class['program_name']) ?></td>
                        <td><?= htmlspecialchars($class['booked_by_name']) ?></td>
                        <td><?= htmlspecialchars($class['subject'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header">Upcoming Classes (Next 7 Days)</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Room</th>
                        <th>Course</th>
                        <th>Program</th>
                        <th>Booked By</th>
                        <th>Subject</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($upcomingClasses as $class): ?>
                    <tr>
                        <td><?= htmlspecialchars($class['booking_date']) ?></td>
                        <td><?= date('H:i', strtotime($class['start_time'])) ?> - <?= date('H:i', strtotime($class['end_time'])) ?></td>
                        <td><?= htmlspecialchars($class['room_name']) ?> (<?= htmlspecialchars($class['location']) ?>)</td>
                        <td><?= htmlspecialchars($class['course_name']) ?> (<?= htmlspecialchars($class['course_code']) ?>)</td>
                        <td><?= htmlspecialchars($class['program_name']) ?></td>
                        <td><?= htmlspecialchars($class['booked_by_name']) ?></td>
                        <td><?= htmlspecialchars($class['subject'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-link mt-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
