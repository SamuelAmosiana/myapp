<?php
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';
require_once __DIR__ . '/../../classes/Room.php';

session_start();
$auth = new Auth();
$rbac = new RBAC();
$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('admin', '../../auth/login.php');

$roomObj = new Room();
$rooms = $roomObj->getAllRooms();

// Handle add, edit, delete actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_room'])) {
        $data = [
            'room_name' => $_POST['room_name'],
            'location' => $_POST['location'],
            'capacity' => $_POST['capacity'],
            'room_type' => $_POST['room_type'],
            'facilities' => $_POST['facilities'],
            'is_available' => isset($_POST['is_available']) ? 1 : 0
        ];
        $roomObj->addRoom($data);
        header('Location: manage_rooms.php?msg=added');
        exit;
    } elseif (isset($_POST['edit_room'])) {
        $room_id = $_POST['room_id'];
        $data = [
            'room_name' => $_POST['room_name'],
            'location' => $_POST['location'],
            'capacity' => $_POST['capacity'],
            'room_type' => $_POST['room_type'],
            'facilities' => $_POST['facilities'],
            'is_available' => isset($_POST['is_available']) ? 1 : 0
        ];
        $roomObj->updateRoom($room_id, $data);
        header('Location: manage_rooms.php?msg=updated');
        exit;
    } elseif (isset($_POST['delete_room'])) {
        $room_id = $_POST['room_id'];
        $roomObj->deleteRoom($room_id);
        header('Location: manage_rooms.php?msg=deleted');
        exit;
    }
}
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $message = 'Room added successfully!';
    if ($_GET['msg'] === 'updated') $message = 'Room updated successfully!';
    if ($_GET['msg'] === 'deleted') $message = 'Room deleted successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Rooms - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-building"></i> Manage Rooms</h2>
    <?php if ($message): ?>
        <div class="alert alert-success"> <?= $message ?> </div>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-header">Add New Room</div>
        <div class="card-body">
            <form method="post">
                <div class="row g-2">
                    <div class="col-md-3">
                        <input type="text" name="room_name" class="form-control" placeholder="Room Name" required>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="location" class="form-control" placeholder="Location">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="capacity" class="form-control" placeholder="Capacity" required min="1">
                    </div>
                    <div class="col-md-2">
                        <select name="room_type" class="form-select">
                            <option value="lecture_hall">Lecture Hall</option>
                            <option value="classroom">Classroom</option>
                            <option value="lab">Lab</option>
                            <option value="seminar_room">Seminar Room</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="facilities" class="form-control" placeholder="Facilities (comma separated)">
                    </div>
                    <div class="col-md-1 d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_available" checked>
                            <label class="form-check-label">Available</label>
                        </div>
                    </div>
                    <div class="col-md-12 mt-2">
                        <button type="submit" name="add_room" class="btn btn-success"><i class="fas fa-plus"></i> Add Room</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">All Rooms</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Capacity</th>
                        <th>Type</th>
                        <th>Facilities</th>
                        <th>Available</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><?= htmlspecialchars($room['room_id']) ?></td>
                        <td><?= htmlspecialchars($room['room_name']) ?></td>
                        <td><?= htmlspecialchars($room['location']) ?></td>
                        <td><?= htmlspecialchars($room['capacity']) ?></td>
                        <td><?= htmlspecialchars($room['room_type']) ?></td>
                        <td><?= htmlspecialchars($room['facilities']) ?></td>
                        <td><?= $room['is_available'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                        <td>
                            <!-- Edit Button trigger modal -->
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $room['room_id'] ?>"><i class="fas fa-edit"></i></button>
                            <form method="post" style="display:inline-block" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="room_id" value="<?= $room['room_id'] ?>">
                                <button type="submit" name="delete_room" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?= $room['room_id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $room['room_id'] ?>" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <form method="post">
                            <div class="modal-header">
                              <h5 class="modal-title" id="editModalLabel<?= $room['room_id'] ?>">Edit Room</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="room_id" value="<?= $room['room_id'] ?>">
                                <div class="mb-2">
                                    <label>Room Name</label>
                                    <input type="text" name="room_name" class="form-control" value="<?= htmlspecialchars($room['room_name']) ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label>Location</label>
                                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($room['location']) ?>">
                                </div>
                                <div class="mb-2">
                                    <label>Capacity</label>
                                    <input type="number" name="capacity" class="form-control" value="<?= htmlspecialchars($room['capacity']) ?>" required min="1">
                                </div>
                                <div class="mb-2">
                                    <label>Room Type</label>
                                    <select name="room_type" class="form-select">
                                        <option value="lecture_hall" <?= $room['room_type']=='lecture_hall'?'selected':'' ?>>Lecture Hall</option>
                                        <option value="classroom" <?= $room['room_type']=='classroom'?'selected':'' ?>>Classroom</option>
                                        <option value="lab" <?= $room['room_type']=='lab'?'selected':'' ?>>Lab</option>
                                        <option value="seminar_room" <?= $room['room_type']=='seminar_room'?'selected':'' ?>>Seminar Room</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label>Facilities</label>
                                    <input type="text" name="facilities" class="form-control" value="<?= htmlspecialchars($room['facilities']) ?>">
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_available" <?= $room['is_available'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Available</label>
                                </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" name="edit_room" class="btn btn-primary">Save Changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
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
