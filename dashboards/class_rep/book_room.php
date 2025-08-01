<?php
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Room.php';
require_once __DIR__ . '/../../classes/RBAC.php';
require_once __DIR__ . '/../../config/Database.php';

session_start();
$auth = new Auth();
$rbac = new RBAC();
$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('class_rep', '../../auth/login.php');

$currentUser = $auth->getCurrentUser();
$roomObj = new Room();
$message = '';

// Get available lecturers for this course
$lecturers = $auth->getAvailableLecturers($currentUser['course_id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room_id = $_POST['room_id'];
    $subject = $_POST['subject'] ?? '';
    $lecturer_id = $_POST['lecturer_id'] ?? null;
    $remarks = $_POST['remarks'] ?? '';
    $course_id = $currentUser['course_id'];
    $booked_by = $currentUser['user_id'];
    $start_dt = $date . ' ' . $start_time;
    $end_dt = $date . ' ' . $end_time;
    $duration = (strtotime($end_dt) - strtotime($start_dt)) / 60;

    // Check for overlapping bookings
    $db = Database::getInstance();
    $overlap = $db->fetchOne(
        "SELECT * FROM bookings WHERE room_id = ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?)) AND status != 'cancelled'",
        [$room_id, $end_dt, $end_dt, $start_dt, $start_dt, $start_dt, $end_dt]
    );
    if ($overlap) {
        $message = '<div class="alert alert-danger">Room is not available for the selected time.</div>';
    } else {
        // Insert booking
        $data = [
            'course_id' => $course_id,
            'room_id' => $room_id,
            'booked_by' => $booked_by,
            'lecturer_id' => $lecturer_id,
            'start_time' => $start_dt,
            'end_time' => $end_dt,
            'booking_date' => $date,
            'duration_minutes' => $duration,
            'subject' => $subject,
            'status' => 'pending',
            'remarks' => $remarks
        ];
        $db->insert('bookings', $data);
        $message = '<div class="alert alert-success">Booking request submitted! Awaiting approval.</div>';
    }
}

// For the form, show all rooms (filter on submit)
$rooms = $roomObj->getAllRooms();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Room - Class Rep Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-plus-circle"></i> Book a Room</h2>
    <?= $message ?>
    <div class="card mb-4">
        <div class="card-header">Booking Form</div>
        <div class="card-body">
            <form method="post">
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Start Time</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label>End Time</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label>Room</label>
                        <select name="room_id" class="form-select" required>
                            <option value="">Select Room</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= $room['room_id'] ?>"><?= htmlspecialchars($room['room_name']) ?> (<?= htmlspecialchars($room['location']) ?>, <?= $room['capacity'] ?> seats)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Lecturer</label>
                        <select name="lecturer_id" class="form-select">
                            <option value="">Select Lecturer</option>
                            <?php foreach ($lecturers as $lec): ?>
                                <option value="<?= $lec['user_id'] ?>"><?= htmlspecialchars($lec['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Class Subject (optional)">
                    </div>
                    <div class="col-md-8">
                        <label>Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Remarks (optional)">
                    </div>
                </div>
                <button type="submit" class="btn btn-success mt-2"><i class="fas fa-paper-plane"></i> Submit Booking</button>
            </form>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-link mt-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
