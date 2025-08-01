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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $data = [
        'name' => $name,
        'email' => $email
    ];
    if (!empty($password)) {
        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }
    $result = $db->update('users', $data, 'user_id = ?', [$currentUser['user_id']]);
    if ($result) {
        $message = '<div class="alert alert-success">Profile updated successfully!</div>';
        // Refresh session data
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
    } else {
        $message = '<div class="alert alert-danger">No changes made or update failed.</div>';
    }
}
// Refresh user data
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - Class Rep Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-user"></i> My Profile</h2>
    <?= $message ?>
    <div class="card mb-4">
        <div class="card-header">Profile Information</div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($currentUser['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($currentUser['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                    <input type="password" name="password" class="form-control" placeholder="New Password">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Profile</button>
            </form>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-link mt-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>