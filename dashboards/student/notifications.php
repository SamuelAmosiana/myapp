<?php
/**
 * Student Notifications - ClassReserve CHAU
 * File: dashboards/student/notifications.php
 */

session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';

$auth = new Auth();
$rbac = new RBAC();

$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('student', '../../auth/login.php');

$currentUser = $auth->getCurrentUser();
$success_message = '';
$error_message = '';

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notification_id = intval($_POST['notification_id']);
    if ($auth->markNotificationRead($notification_id)) {
        $success_message = 'Notification marked as read.';
    } else {
        $error_message = 'Failed to mark notification as read.';
    }
}

// Handle mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    try {
        $db = Database::getInstance();
        $db->query(
            "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = :user_id AND is_read = 0",
            ['user_id' => $currentUser['user_id']]
        );
        $success_message = 'All notifications marked as read.';
    } catch (Exception $e) {
        $error_message = 'Failed to mark all notifications as read.';
    }
}

// Get notifications
$allNotifications = $auth->getNotifications(false, 50);
$unreadNotifications = $auth->getNotifications(true, 50);
$unreadCount = count($unreadNotifications);

// Group notifications by date
$notificationsByDate = [];
foreach ($allNotifications as $notification) {
    $date = date('Y-m-d', strtotime($notification['created_at']));
    if (!isset($notificationsByDate[$date])) {
        $notificationsByDate[$date] = [];
    }
    $notificationsByDate[$date][] = $notification;
}

// Function to get notification icon
function getNotificationIcon($type) {
    switch ($type) {
        case 'booking': return 'fas fa-calendar-check';
        case 'class': return 'fas fa-chalkboard-teacher';
        case 'feedback': return 'fas fa-comment-dots';
        case 'system': return 'fas fa-cog';
        case 'announcement': return 'fas fa-bullhorn';
        default: return 'fas fa-bell';
    }
}

// Function to get notification color
function getNotificationColor($type) {
    switch ($type) {
        case 'booking': return '#28a745';
        case 'class': return '#007bff';
        case 'feedback': return '#ffc107';
        case 'system': return '#6c757d';
        case 'announcement': return '#dc3545';
        default: return '#17a2b8';
    }
}

// Function to format date
function formatDate($date) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if ($date === $today) return 'Today';
    if ($date === $yesterday) return 'Yesterday';
    return date('l, F j, Y', strtotime($date));
}

// Function to format time
function formatTime($datetime) {
    return date('g:i A', strtotime($datetime));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - ClassReserve CHAU</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body { background: white; min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .navbar { background: var(--glass-bg) !important; backdrop-filter: blur(20px); border-bottom: 1px solid var(--glass-border); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        .navbar-brand { font-weight: 700; font-size: 1.5rem; }
        
        .sidebar { background: var(--glass-bg); backdrop-filter: blur(20px); border-radius: 20px; border: 1px solid var(--glass-border); padding: 2rem 1.5rem; margin-bottom: 2rem; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); position: sticky; top: 2rem; height: fit-content; }
        .sidebar h5 { color: #333; font-weight: 600; margin-bottom: 1.5rem; }
        
        .nav-link { color: rgba(51, 51, 51, 0.8) !important; padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 0.5rem; transition: all 0.3s ease; border: 1px solid transparent; }
        .nav-link:hover, .nav-link.active { background: rgba(102, 126, 234, 0.1); color: #333 !important; border-color: rgba(102, 126, 234, 0.3); transform: translateX(5px); }
        
        .main-content { padding: 2rem; }
        
        .page-header { background: var(--glass-bg); backdrop-filter: blur(20px); border-radius: 20px; border: 1px solid var(--glass-border); padding: 2rem; margin-bottom: 2rem; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        .page-header h2 { color: #333; font-weight: 700; margin-bottom: 0.5rem; }
        .page-header p { color: rgba(51, 51, 51, 0.8); margin: 0; }
        
        .content-card { background: var(--glass-bg); backdrop-filter: blur(20px); border-radius: 20px; border: 1px solid var(--glass-border); padding: 2rem; margin-bottom: 2rem; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        .content-card h5 { color: #333; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        
        .notification-item { background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 1.5rem; margin-bottom: 1rem; border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s ease; position: relative; }
        .notification-item:hover { background: rgba(255, 255, 255, 0.1); transform: translateY(-2px); }
        .notification-item.unread { border-left: 4px solid #007bff; background: rgba(0, 123, 255, 0.05); }
        
        .notification-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; margin-right: 1rem; flex-shrink: 0; }
        
        .notification-content { flex-grow: 1; }
        .notification-title { font-weight: 600; color: #333; margin-bottom: 0.5rem; }
        .notification-message { color: rgba(51, 51, 51, 0.8); margin-bottom: 0.5rem; line-height: 1.5; }
        .notification-meta { color: rgba(51, 51, 51, 0.6); font-size: 0.9rem; }
        
        .notification-actions { display: flex; gap: 0.5rem; }
        .btn-sm { padding: 0.25rem 0.75rem; font-size: 0.875rem; border-radius: 8px; }
        
        .date-header { background: var(--primary-gradient); color: white; padding: 0.75rem 1.5rem; border-radius: 12px; margin-bottom: 1rem; font-weight: 600; text-align: center; }
        
        .empty-state { text-align: center; padding: 3rem 2rem; color: rgba(51, 51, 51, 0.7); }
        .empty-state i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; }
        
        .stats-row { display: flex; justify-content: center; gap: 2rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 1.5rem; text-align: center; border: 1px solid rgba(255, 255, 255, 0.1); }
        .stat-value { font-size: 2rem; font-weight: 700; color: #667eea; margin-bottom: 0.5rem; }
        .stat-label { color: rgba(51, 51, 51, 0.7); font-size: 0.9rem; }
        
        .btn { border-radius: 12px; padding: 0.75rem 1.5rem; font-weight: 500; border: none; transition: all 0.3s ease; }
        .btn-primary { background: var(--primary-gradient); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4); }
        
        .alert { border-radius: 15px; border: none; backdrop-filter: blur(20px); }
        .alert-success { background: rgba(75, 181, 67, 0.2); color: #155724; border: 1px solid rgba(75, 181, 67, 0.3); }
        .alert-danger { background: rgba(245, 87, 108, 0.2); color: #721c24; border: 1px solid rgba(245, 87, 108, 0.3); }
        
        .filter-tabs { margin-bottom: 2rem; }
        .nav-tabs { border-bottom: 2px solid rgba(102, 126, 234, 0.2); }
        .nav-tabs .nav-link { border: none; color: rgba(51, 51, 51, 0.7); padding: 1rem 1.5rem; border-radius: 12px 12px 0 0; }
        .nav-tabs .nav-link.active { background: rgba(102, 126, 234, 0.1); color: #333; border-bottom: 2px solid #667eea; }
        
        @media (max-width: 768px) { 
            .main-content { padding: 1rem; } 
            .stats-row { flex-direction: column; gap: 1rem; } 
            .notification-item { padding: 1rem; }
            .notification-item .d-flex { flex-direction: column; text-align: center; }
            .notification-icon { margin: 0 auto 1rem; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-graduation-cap"></i> ClassReserve CHAU</a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($currentUser['name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2">
                <div class="sidebar">
                    <h5><i class="fas fa-user-graduate"></i> Student Panel</h5>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                        <a class="nav-link" href="class_status.php"><i class="fas fa-calendar-alt"></i> Class Schedule</a>
                        <a class="nav-link" href="feedback.php"><i class="fas fa-comment"></i> Submit Feedback</a>
                        <a class="nav-link" href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a class="nav-link active" href="notifications.php">
                            <i class="fas fa-bell"></i> Notifications
                            <?php if ($unreadCount > 0): ?><span class="badge bg-danger ms-2"><?php echo $unreadCount; ?></span><?php endif; ?>
                        </a>
                    </nav>
                </div>
            </div>

            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <div class="page-header">
                        <h2><i class="fas fa-bell text-primary"></i> Notifications</h2>
                        <p>Stay updated with important announcements, class updates, and system notifications</p>
                    </div>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo count($allNotifications); ?></div>
                            <div class="stat-label">Total Notifications</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $unreadCount; ?></div>
                            <div class="stat-label">Unread</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo count($allNotifications) - $unreadCount; ?></div>
                            <div class="stat-label">Read</div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fas fa-list text-primary"></i> All Notifications</h5>
                            <?php if ($unreadCount > 0): ?>
                                <form method="POST" style="display: inline;">
                                    <button type="submit" name="mark_all_read" class="btn btn-primary btn-sm">
                                        <i class="fas fa-check-double me-1"></i>Mark All Read
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <ul class="nav nav-tabs filter-tabs" id="notificationTabs">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#all-notifications">
                                    All (<?php echo count($allNotifications); ?>)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#unread-notifications">
                                    Unread (<?php echo $unreadCount; ?>)
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content mt-3">
                            <div class="tab-pane fade show active" id="all-notifications">
                                <?php if (empty($allNotifications)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-bell-slash"></i>
                                        <h6>No Notifications</h6>
                                        <p>You don't have any notifications yet. They will appear here when available.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notificationsByDate as $date => $notifications): ?>
                                        <div class="date-header"><?php echo formatDate($date); ?></div>
                                        <?php foreach ($notifications as $notification): ?>
                                            <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                                <div class="d-flex">
                                                    <div class="notification-icon" style="background-color: <?php echo getNotificationColor($notification['type']); ?>">
                                                        <i class="<?php echo getNotificationIcon($notification['type']); ?>"></i>
                                                    </div>
                                                    <div class="notification-content">
                                                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                                        <div class="notification-message"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></div>
                                                        <div class="notification-meta">
                                                            <i class="fas fa-clock me-1"></i><?php echo formatTime($notification['created_at']); ?>
                                                            <span class="ms-3"><i class="fas fa-tag me-1"></i><?php echo ucfirst($notification['type']); ?></span>
                                                            <?php if (!$notification['is_read']): ?>
                                                                <span class="badge bg-primary ms-2">New</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php if (!$notification['is_read']): ?>
                                                        <div class="notification-actions">
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                                                <button type="submit" name="mark_read" class="btn btn-outline-primary btn-sm">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="tab-pane fade" id="unread-notifications">
                                <?php if (empty($unreadNotifications)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-check-circle"></i>
                                        <h6>All Caught Up!</h6>
                                        <p>You have no unread notifications. Great job staying on top of things!</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($unreadNotifications as $notification): ?>
                                        <div class="notification-item unread">
                                            <div class="d-flex">
                                                <div class="notification-icon" style="background-color: <?php echo getNotificationColor($notification['type']); ?>">
                                                    <i class="<?php echo getNotificationIcon($notification['type']); ?>"></i>
                                                </div>
                                                <div class="notification-content">
                                                    <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                                    <div class="notification-message"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></div>
                                                    <div class="notification-meta">
                                                        <i class="fas fa-clock me-1"></i><?php echo formatTime($notification['created_at']); ?>
                                                        <span class="ms-3"><i class="fas fa-tag me-1"></i><?php echo ucfirst($notification['type']); ?></span>
                                                        <span class="badge bg-primary ms-2">New</span>
                                                    </div>
                                                </div>
                                                <div class="notification-actions">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                                        <button type="submit" name="mark_read" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="content-card">
                        <h5><i class="fas fa-bolt text-info"></i> Quick Actions</h5>
                        <div class="row">
                            <div class="col-md-3 mb-2"><a href="dashboard.php" class="btn btn-outline-primary w-100"><i class="fas fa-home me-2"></i>Dashboard</a></div>
                            <div class="col-md-3 mb-2"><a href="class_status.php" class="btn btn-outline-primary w-100"><i class="fas fa-calendar me-2"></i>Schedule</a></div>
                            <div class="col-md-3 mb-2"><a href="feedback.php" class="btn btn-outline-primary w-100"><i class="fas fa-comment me-2"></i>Feedback</a></div>
                            <div class="col-md-3 mb-2"><a href="profile.php" class="btn btn-outline-primary w-100"><i class="fas fa-user me-2"></i>Profile</a></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh notifications every 2 minutes
        setTimeout(function() { location.reload(); }, 120000);
        
        // Smooth scroll for better UX
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>
