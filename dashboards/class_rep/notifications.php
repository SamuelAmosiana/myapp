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

// Mark all as read when visiting
$db->update('notifications', ['is_read' => 1], 'user_id = :user_id', ['user_id' => $currentUser['user_id']]);

// Get all notifications for this user
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$notifications = $db->fetchAll($sql, [$currentUser['user_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - Class Rep Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2><i class="fas fa-bell"></i> Notifications</h2>
    <div class="card">
        <div class="card-header">Your Notifications</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($notifications as $n): ?>
                    <tr>
                        <td>
                            <?php
                                $icon = 'fa-info-circle text-info';
                                if ($n['notification_type'] === 'booking_approved') $icon = 'fa-check-circle text-success';
                                elseif ($n['notification_type'] === 'booking_rejected') $icon = 'fa-times-circle text-danger';
                                elseif ($n['notification_type'] === 'booking_cancelled') $icon = 'fa-ban text-warning';
                                elseif ($n['notification_type'] === 'class_reminder') $icon = 'fa-bell text-primary';
                            ?>
                            <i class="fas <?= $icon ?>"></i>
                        </td>
                        <td><?= htmlspecialchars($n['title']) ?></td>
                        <td><?= htmlspecialchars($n['message']) ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($n['created_at'])) ?></td>
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