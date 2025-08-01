<?php
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';
require_once __DIR__ . '/../../classes/Program.php';

session_start();
$auth = new Auth();
$rbac = new RBAC();
$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('admin', '../../auth/login.php');

$programObj = new Program();
$programs = $programObj->getAllPrograms();

// Handle add, edit, delete actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_program'])) {
        $data = [
            'program_name' => $_POST['program_name'],
            'program_type' => $_POST['program_type']
        ];
        $programObj->addProgram($data);
        header('Location: manage_programs.php?msg=added');
        exit;
    } elseif (isset($_POST['edit_program'])) {
        $program_id = $_POST['program_id'];
        $data = [
            'program_name' => $_POST['program_name'],
            'program_type' => $_POST['program_type']
        ];
        $programObj->updateProgram($program_id, $data);
        header('Location: manage_programs.php?msg=updated');
        exit;
    } elseif (isset($_POST['delete_program'])) {
        $program_id = $_POST['program_id'];
        $programObj->deleteProgram($program_id);
        header('Location: manage_programs.php?msg=deleted');
        exit;
    }
}
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $message = 'Program added successfully!';
    if ($_GET['msg'] === 'updated') $message = 'Program updated successfully!';
    if ($_GET['msg'] === 'deleted') $message = 'Program deleted successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Programs - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-graduation-cap"></i> Manage Programs</h2>
    <?php if ($message): ?>
        <div class="alert alert-success"> <?= $message ?> </div>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-header">Add New Program</div>
        <div class="card-body">
            <form method="post">
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" name="program_name" class="form-control" placeholder="Program Name" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="program_type" class="form-control" placeholder="Program Type" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_program" class="btn btn-success w-100"><i class="fas fa-plus"></i> Add Program</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">All Programs</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($programs as $program): ?>
                    <tr>
                        <td><?= htmlspecialchars($program['program_id']) ?></td>
                        <td><?= htmlspecialchars($program['program_name']) ?></td>
                        <td><?= htmlspecialchars($program['program_type']) ?></td>
                        <td>
                            <!-- Edit Button trigger modal -->
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $program['program_id'] ?>"><i class="fas fa-edit"></i></button>
                            <form method="post" style="display:inline-block" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="program_id" value="<?= $program['program_id'] ?>">
                                <button type="submit" name="delete_program" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?= $program['program_id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $program['program_id'] ?>" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <form method="post">
                            <div class="modal-header">
                              <h5 class="modal-title" id="editModalLabel<?= $program['program_id'] ?>">Edit Program</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="program_id" value="<?= $program['program_id'] ?>">
                                <div class="mb-2">
                                    <label>Program Name</label>
                                    <input type="text" name="program_name" class="form-control" value="<?= htmlspecialchars($program['program_name']) ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label>Program Type</label>
                                    <input type="text" name="program_type" class="form-control" value="<?= htmlspecialchars($program['program_type']) ?>" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" name="edit_program" class="btn btn-primary">Save Changes</button>
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
