<?php
/**
 * Student Profile - ClassReserve CHAU
 * File: dashboards/student/profile.php
 */

session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';

$auth = new Auth();
$rbac = new RBAC();

$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('student', '../../auth/login.php');

$currentUser = $auth->getCurrentUser();
$course_id = $currentUser['course_id'] ?? null;

$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($name) || empty($email)) {
        $error_message = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        $result = $auth->updateProfile(['name' => $name, 'email' => $email]);
        if ($result['success']) {
            $success_message = $result['message'];
            $currentUser = $auth->getCurrentUser();
        } else {
            $error_message = $result['message'];
        }
    }
}

$notifications = $auth->getNotifications(false, 5);
$unreadCount = count(array_filter($notifications, function($n) { return !$n['is_read']; }));

// Get statistics
$todaysClasses = $course_id ? $auth->getTodaysClassesForStudent($course_id) : [];
$upcomingClasses = $course_id ? $auth->getUpcomingClassesForStudent($course_id) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ClassReserve CHAU</title>
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
        
        .profile-header { background: var(--glass-bg); backdrop-filter: blur(20px); border-radius: 20px; border: 1px solid var(--glass-border); padding: 2rem; margin-bottom: 2rem; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); text-align: center; }
        .profile-avatar { width: 100px; height: 100px; background: var(--primary-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2.5rem; color: white; }
        .profile-name { font-size: 1.8rem; font-weight: 700; color: #333; margin-bottom: 0.5rem; }
        .profile-role { color: rgba(51, 51, 51, 0.7); margin-bottom: 1rem; }
        
        .content-card { background: var(--glass-bg); backdrop-filter: blur(20px); border-radius: 20px; border: 1px solid var(--glass-border); padding: 2rem; margin-bottom: 2rem; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        .content-card h5 { color: #333; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
        .info-item { background: rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 1rem; border: 1px solid rgba(255, 255, 255, 0.1); }
        .info-label { color: rgba(51, 51, 51, 0.7); font-size: 0.9rem; margin-bottom: 0.5rem; text-transform: uppercase; }
        .info-value { color: #333; font-weight: 500; }
        
        .form-control { border-radius: 12px; border: 1px solid rgba(51, 51, 51, 0.2); padding: 0.75rem 1rem; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
        .form-label { color: #333; font-weight: 500; }
        
        .btn { border-radius: 12px; padding: 0.75rem 1.5rem; font-weight: 500; border: none; transition: all 0.3s ease; }
        .btn-primary { background: var(--primary-gradient); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4); }
        
        .alert { border-radius: 15px; border: none; backdrop-filter: blur(20px); }
        .alert-success { background: rgba(75, 181, 67, 0.2); color: #155724; border: 1px solid rgba(75, 181, 67, 0.3); }
        .alert-danger { background: rgba(245, 87, 108, 0.2); color: #721c24; border: 1px solid rgba(245, 87, 108, 0.3); }
        
        .stats { display: flex; justify-content: center; gap: 2rem; margin-top: 1rem; }
        .stat { text-align: center; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: #667eea; }
        .stat-label { color: rgba(51, 51, 51, 0.7); font-size: 0.9rem; }
        
        @media (max-width: 768px) { .main-content { padding: 1rem; } .stats { flex-direction: column; gap: 1rem; } .info-grid { grid-template-columns: 1fr; } }
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
                        <a class="nav-link active" href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a class="nav-link" href="notifications.php">
                            <i class="fas fa-bell"></i> Notifications
                            <?php if ($unreadCount > 0): ?><span class="badge bg-danger ms-2"><?php echo $unreadCount; ?></span><?php endif; ?>
                        </a>
                    </nav>
                </div>
            </div>

            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <div class="profile-header">
                        <div class="profile-avatar"><i class="fas fa-user-graduate"></i></div>
                        <div class="profile-name"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                        <div class="profile-role">
                            <i class="fas fa-graduation-cap me-2"></i>Student
                            <?php if (isset($currentUser['course_name'])): ?> • <?php echo htmlspecialchars($currentUser['course_name']); ?><?php endif; ?>
                        </div>
                        <div class="stats">
                            <div class="stat">
                                <div class="stat-value"><?php echo count($todaysClasses); ?></div>
                                <div class="stat-label">Today's Classes</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?php echo count($upcomingClasses); ?></div>
                                <div class="stat-label">Upcoming Classes</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?php echo $unreadCount; ?></div>
                                <div class="stat-label">Notifications</div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <div class="content-card">
                        <h5><i class="fas fa-id-card text-primary"></i> Personal Information</h5>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Student ID</div>
                                <div class="info-value"><?php echo htmlspecialchars($currentUser['student_id']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Role</div>
                                <div class="info-value"><span class="badge bg-primary"><?php echo ucfirst($currentUser['role_name']); ?></span></div>
                            </div>
                            <?php if (isset($currentUser['course_name'])): ?>
                            <div class="info-item">
                                <div class="info-label">Course</div>
                                <div class="info-value"><?php echo htmlspecialchars($currentUser['course_name']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($currentUser['program_name'])): ?>
                            <div class="info-item">
                                <div class="info-label">Program</div>
                                <div class="info-value"><?php echo htmlspecialchars($currentUser['program_name']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="content-card">
                        <h5><i class="fas fa-edit text-success"></i> Update Profile</h5>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                </div>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </form>
                    </div>

                    <div class="content-card">
                        <h5><i class="fas fa-bolt text-info"></i> Quick Actions</h5>
                        <div class="row">
                            <div class="col-md-3 mb-2"><a href="dashboard.php" class="btn btn-outline-primary w-100"><i class="fas fa-home me-2"></i>Dashboard</a></div>
                            <div class="col-md-3 mb-2"><a href="class_status.php" class="btn btn-outline-primary w-100"><i class="fas fa-calendar me-2"></i>Schedule</a></div>
                            <div class="col-md-3 mb-2"><a href="feedback.php" class="btn btn-outline-primary w-100"><i class="fas fa-comment me-2"></i>Feedback</a></div>
                            <div class="col-md-3 mb-2"><a href="notifications.php" class="btn btn-outline-primary w-100"><i class="fas fa-bell me-2"></i>Notifications</a></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
