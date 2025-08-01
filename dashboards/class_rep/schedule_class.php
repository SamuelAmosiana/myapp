<?php
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';
require_once __DIR__ . '/../../classes/Room.php';

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
$rooms = $roomObj->getAllRooms();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room_id = $_POST['room_id'];
    $subject = $_POST['subject'] ?? '';
    $lecturer_id = $_POST['lecturer_id'] ?? null;
    $remarks = $_POST['remarks'] ?? '';
    $recurrence = $_POST['recurrence'] ?? 'once';
    $days_of_week = $_POST['days_of_week'] ?? [];
    
    $course_id = $currentUser['course_id'];
    $booked_by = $currentUser['user_id'];
    $duration = (strtotime($end_time) - strtotime($start_time)) / 60;
    
    $success_count = 0;
    $error_count = 0;
    
    // Generate dates based on recurrence
    $dates = [];
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    
    while ($current_date <= $end_date_obj) {
        $day_of_week = $current_date->format('N'); // 1=Monday, 7=Sunday
        
        if ($recurrence === 'once') {
            $dates[] = $current_date->format('Y-m-d');
            break;
        } elseif ($recurrence === 'weekly' && in_array($day_of_week, $days_of_week)) {
            $dates[] = $current_date->format('Y-m-d');
        } elseif ($recurrence === 'daily') {
            $dates[] = $current_date->format('Y-m-d');
        }
        
        $current_date->add(new DateInterval('P1D'));
    }
    
    // Create bookings for each date
    foreach ($dates as $date) {
        $start_dt = $date . ' ' . $start_time;
        $end_dt = $date . ' ' . $end_time;
        
        // Check for overlapping bookings
        $db = $roomObj->db;
        $overlap = $db->fetchOne(
            "SELECT * FROM bookings WHERE room_id = ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?)) AND status != 'cancelled'",
            [$room_id, $end_dt, $end_dt, $start_dt, $start_dt, $start_dt, $end_dt]
        );
        
        if (!$overlap) {
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
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    if ($success_count > 0) {
        $message = '<div class="alert alert-success">Successfully scheduled ' . $success_count . ' class(es)!';
        if ($error_count > 0) {
            $message .= ' ' . $error_count . ' booking(s) could not be created due to conflicts.';
        }
        $message .= '</div>';
    } else {
        $message = '<div class="alert alert-danger">No classes could be scheduled due to conflicts.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule Class - Class Rep Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-clock"></i> Schedule Class</h2>
    <?= $message ?>
    <div class="card mb-4">
        <div class="card-header">Bulk Class Scheduling</div>
        <div class="card-body">
            <form method="post">
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Start Time</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label>End Time</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label>Recurrence</label>
                        <select name="recurrence" class="form-select" id="recurrence" required>
                            <option value="once">Once</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>
                </div>
                
                <div class="row g-2 mb-2" id="weeklyOptions" style="display: none;">
                    <div class="col-md-12">
                        <label>Days of Week</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="days_of_week[]" value="1" id="monday">
                            <label class="form-check-label" for="monday">Monday</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="days_of_week[]" value="2" id="tuesday">
                            <label class="form-check-label" for="tuesday">Tuesday</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="days_of_week[]" value="3" id="wednesday">
                            <label class="form-check-label" for="wednesday">Wednesday</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="days_of_week[]" value="4" id="thursday">
                            <label class="form-check-label" for="thursday">Thursday</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="days_of_week[]" value="5" id="friday">
                            <label class="form-check-label" for="friday">Friday</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="days_of_week[]" value="6" id="saturday">
                            <label class="form-check-label" for="saturday">Saturday</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="days_of_week[]" value="7" id="sunday">
                            <label class="form-check-label" for="sunday">Sunday</label>
                        </div>
                    </div>
                </div>
                
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label>Room</label>
                        <select name="room_id" class="form-select" required>
                            <option value="">Select Room</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= $room['room_id'] ?>"><?= htmlspecialchars($room['room_name']) ?> (<?= htmlspecialchars($room['location']) ?>, <?= $room['capacity'] ?> seats)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Lecturer</label>
                        <select name="lecturer_id" class="form-select">
                            <option value="">Select Lecturer</option>
                            <?php foreach ($lecturers as $lec): ?>
                                <option value="<?= $lec['user_id'] ?>"><?= htmlspecialchars($lec['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Class Subject (optional)">
                    </div>
                </div>
                
                <div class="row g-2 mb-2">
                    <div class="col-md-12">
                        <label>Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Remarks (optional)">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success mt-2"><i class="fas fa-calendar-plus"></i> Schedule Classes</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">Scheduling Tips</div>
        <div class="card-body">
            <ul class="list-unstyled">
                <li><i class="fas fa-info-circle text-info"></i> <strong>Once:</strong> Schedule a single class on the start date</li>
                <li><i class="fas fa-info-circle text-info"></i> <strong>Daily:</strong> Schedule classes every day between start and end dates</li>
                <li><i class="fas fa-info-circle text-info"></i> <strong>Weekly:</strong> Schedule classes on selected days of the week</li>
                <li><i class="fas fa-exclamation-triangle text-warning"></i> All bookings will be created with 'pending' status and require admin approval</li>
                <li><i class="fas fa-clock text-primary"></i> Classes that conflict with existing bookings will be skipped</li>
            </ul>
        </div>
    </div>
    
    <a href="dashboard.php" class="btn btn-link mt-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('recurrence').addEventListener('change', function() {
    const weeklyOptions = document.getElementById('weeklyOptions');
    if (this.value === 'weekly') {
        weeklyOptions.style.display = 'block';
    } else {
        weeklyOptions.style.display = 'none';
    }
});
</script>
</body>
</html>
