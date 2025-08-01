<?php
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';
require_once __DIR__ . '/../../classes/Course.php';

session_start();
$auth = new Auth();
$rbac = new RBAC();
$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('admin', '../../auth/login.php');

$courseObj = new Course();
$courses = $courseObj->getAllCourses();

// Handle add, edit, delete actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_course'])) {
        $data = [
            'course_name' => $_POST['course_name'],
            'course_code' => $_POST['course_code'],
            'program_id' => $_POST['program_id']
        ];
        $courseObj->addCourse($data);
        header('Location: manage_courses.php?msg=added');
        exit;
    } elseif (isset($_POST['edit_course'])) {
        $course_id = $_POST['course_id'];
        $data = [
            'course_name' => $_POST['course_name'],
            'course_code' => $_POST['course_code'],
            'program_id' => $_POST['program_id']
        ];
        $courseObj->updateCourse($course_id, $data);
        header('Location: manage_courses.php?msg=updated');
        exit;
    } elseif (isset($_POST['delete_course'])) {
        $course_id = $_POST['course_id'];
        $courseObj->deleteCourse($course_id);
        header('Location: manage_courses.php?msg=deleted');
        exit;
    }
}
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $message = 'Course added successfully!';
    if ($_GET['msg'] === 'updated') $message = 'Course updated successfully!';
    if ($_GET['msg'] === 'deleted') $message = 'Course deleted successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Courses - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-book"></i> Manage Courses</h2>
    <?php if ($message): ?>
        <div class="alert alert-success"> <?= $message ?> </div>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-header">Add New Course</div>
        <div class="card-body">
            <form method="post">
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="course_name" class="form-control" placeholder="Course Name" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="course_code" class="form-control" placeholder="Course Code" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="program_id" class="form-control" placeholder="Program ID" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_course" class="btn btn-success w-100"><i class="fas fa-plus"></i> Add Course</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">All Courses</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Program ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?= htmlspecialchars($course['course_id']) ?></td>
                        <td><?= htmlspecialchars($course['course_name']) ?></td>
                        <td><?= htmlspecialchars($course['course_code']) ?></td>
                        <td><?= htmlspecialchars($course['program_id']) ?></td>
                        <td>
                            <!-- Edit Button trigger modal -->
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $course['course_id'] ?>"><i class="fas fa-edit"></i></button>
                            <form method="post" style="display:inline-block" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">
                                <button type="submit" name="delete_course" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?= $course['course_id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $course['course_id'] ?>" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <form method="post">
                            <div class="modal-header">
                              <h5 class="modal-title" id="editModalLabel<?= $course['course_id'] ?>">Edit Course</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">
                                <div class="mb-2">
                                    <label>Course Name</label>
                                    <input type="text" name="course_name" class="form-control" value="<?= htmlspecialchars($course['course_name']) ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label>Course Code</label>
                                    <input type="text" name="course_code" class="form-control" value="<?= htmlspecialchars($course['course_code']) ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label>Program ID</label>
                                    <input type="number" name="program_id" class="form-control" value="<?= htmlspecialchars($course['program_id']) ?>" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" name="edit_course" class="btn btn-primary">Save Changes</button>
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
