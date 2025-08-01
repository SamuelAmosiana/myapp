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

// Handle approve/cancel actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_booking'])) {
        $booking_id = $_POST['booking_id'];
        $db->update('bookings', ['status' => 'approved'], 'booking_id = ?', [$booking_id]);
    } elseif (isset($_POST['cancel_booking'])) {
        $booking_id = $_POST['booking_id'];
        $db->update('bookings', ['status' => 'cancelled'], 'booking_id = ?', [$booking_id]);
    }
    header('Location: bookings.php');
    exit;
}

// Fetch all bookings with details
$sql = "
SELECT b.*, 
       u.name AS booked_by_name, 
       r.room_name, 
       c.course_name
FROM bookings b
LEFT JOIN users u ON b.booked_by = u.user_id
LEFT JOIN rooms r ON b.room_id = r.room_id
LEFT JOIN courses c ON b.course_id = c.course_id
ORDER BY b.booking_date DESC, b.start_time DESC
";
$bookings = $db->fetchAll($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Bookings - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-calendar-alt"></i> All Bookings</h2>
    <div class="card">
        <div class="card-header">Bookings List</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Room</th>
                        <th>Course</th>
                        <th>Booked By</th>
                        <th>Date</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?= htmlspecialchars($booking['booking_id']) ?></td>
                        <td><?= htmlspecialchars($booking['room_name']) ?></td>
                        <td><?= htmlspecialchars($booking['course_name']) ?></td>
                        <td><?= htmlspecialchars($booking['booked_by_name']) ?></td>
                        <td><?= htmlspecialchars($booking['booking_date']) ?></td>
                        <td><?= htmlspecialchars($booking['start_time']) ?></td>
                        <td><?= htmlspecialchars($booking['end_time']) ?></td>
                        <td>
                            <?php
                                $status = $booking['status'];
                                $badge = 'secondary';
                                if ($status === 'approved') $badge = 'success';
                                elseif ($status === 'pending') $badge = 'warning';
                                elseif ($status === 'cancelled') $badge = 'danger';
                            ?>
                            <span class="badge bg-<?= $badge ?>"><?= ucfirst($status) ?></span>
                        </td>
                        <td>
                            <?php if ($booking['status'] === 'pending'): ?>
                                <form method="post" style="display:inline-block">
                                    <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                                    <button type="submit" name="approve_booking" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                </form>
                                <form method="post" style="display:inline-block" onsubmit="return confirm('Cancel this booking?');">
                                    <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                                    <button type="submit" name="cancel_booking" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
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