<?php
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';
require_once __DIR__ . '/../../config/Database.php';

session_start();
$auth = new Auth();
$rbac = new RBAC();
$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('class_rep', '../../auth/login.php');

$currentUser = $auth->getCurrentUser();
$db = Database::getInstance();
$message = '';

// Handle cancel booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'];
    // Only allow cancellation of pending bookings
    $booking = $db->fetchOne("SELECT * FROM bookings WHERE booking_id = ? AND booked_by = ? AND status = 'pending'", [$booking_id, $currentUser['user_id']]);
    if ($booking) {
        $db->update('bookings', ['status' => 'cancelled'], 'booking_id = ?', [$booking_id]);
        $message = '<div class="alert alert-success">Booking cancelled successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Cannot cancel this booking.</div>';
    }
}

// Get all bookings for this class rep
$sql = "
SELECT b.*, 
       r.room_name, 
       r.location,
       c.course_name,
       u.name AS lecturer_name
FROM bookings b
LEFT JOIN rooms r ON b.room_id = r.room_id
LEFT JOIN courses c ON b.course_id = c.course_id
LEFT JOIN users u ON b.lecturer_id = u.user_id
WHERE b.booked_by = ?
ORDER BY b.booking_date DESC, b.start_time DESC
";
$bookings = $db->fetchAll($sql, [$currentUser['user_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings - Class Rep Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-calendar-alt"></i> My Bookings</h2>
    <?= $message ?>
    <div class="card">
        <div class="card-header">Booking History</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Room</th>
                        <th>Course</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Lecturer</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?= htmlspecialchars($booking['booking_id']) ?></td>
                        <td><?= htmlspecialchars($booking['room_name']) ?> (<?= htmlspecialchars($booking['location']) ?>)</td>
                        <td><?= htmlspecialchars($booking['course_name']) ?></td>
                        <td><?= htmlspecialchars($booking['booking_date']) ?></td>
                        <td><?= date('H:i', strtotime($booking['start_time'])) ?> - <?= date('H:i', strtotime($booking['end_time'])) ?></td>
                        <td><?= htmlspecialchars($booking['lecturer_name'] ?? 'Not assigned') ?></td>
                        <td><?= htmlspecialchars($booking['subject'] ?? '-') ?></td>
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
                                <form method="post" style="display:inline-block" onsubmit="return confirm('Cancel this booking?');">
                                    <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                                    <button type="submit" name="cancel_booking" class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Cancel</button>
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