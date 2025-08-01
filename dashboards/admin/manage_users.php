<?php
/**
 * Admin Dashboard - Manage Users
 * File: dashboards/admin/manage_users.php
 */

// Start session and include required files
session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';
require_once __DIR__ . '/../../classes/UserManager.php';
require_once __DIR__ . '/../../classes/Course.php';

// Initialize authentication and RBAC
$auth = new Auth();
$rbac = new RBAC();
$userManager = new UserManager();
$courseObj = new Course();

// Check if user is logged in and has admin role
$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('admin', '../../auth/login.php');

// Get current user data
$currentUser = $auth->getCurrentUser();

// Get all users and courses
$users = $userManager->getAllUsers();
$courses = $courseObj->getAllCourses();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $course_id = !empty($_POST['course_id']) ? $_POST['course_id'] : null;
        
        // Basic validation
        if (empty($name) || empty($email) || empty($password) || empty($role)) {
            $errorMessage = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Please enter a valid email address.";
        } elseif (strlen($password) < 6) {
            $errorMessage = "Password must be at least 6 characters long.";
        } else {
            // Add new user
            $result = $userManager->addUser($name, $email, $password, $role, $course_id);
            
            if ($result) {
                $successMessage = "User '$name' added successfully!";
                // Clear form data
                $_POST = [];
            } else {
                $errorMessage = "Failed to add user. Email might already exist or invalid role selected.";
            }
        }
        $users = $userManager->getAllUsers(); // Refresh users after add
    } elseif (isset($_POST['update_user'])) {
        // Validate input for update
        $user_id = intval($_POST['user_id']);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $course_id = !empty($_POST['course_id']) ? $_POST['course_id'] : null;
        
        // Basic validation
        if (empty($name) || empty($email) || empty($role)) {
            $errorMessage = "All fields are required for update.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Please enter a valid email address.";
        } else {
            // Update user
            $result = $userManager->updateUser($user_id, $name, $email, $role, $course_id);
            
            if ($result) {
                $successMessage = "User '$name' updated successfully!";
            } else {
                $errorMessage = "Failed to update user. Please try again.";
            }
        }
        $users = $userManager->getAllUsers(); // Refresh users after update
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $user_id = intval($_POST['user_id']);
        $result = $userManager->deleteUser($user_id);
        
        // Handle new return format from deleteUser
        if (is_array($result)) {
            if ($result['success']) {
                $successMessage = $result['message'];
            } else {
                $errorMessage = $result['message'];
            }
        } else {
            // Fallback for old boolean return
            if ($result) {
                $successMessage = "User deleted successfully!";
            } else {
                $errorMessage = "Failed to delete user. User may have related records.";
            }
        }
        $users = $userManager->getAllUsers(); // Refresh users after delete
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - ClassReserve CHAU</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        /* Reuse the same styles from dashboard.php */
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 15px 15px 0 0;
        }
        
        .sidebar {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            height: calc(100vh - 120px);
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            color: #333;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            transform: translateX(5px);
        }
        
        .dashboard-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 20px;
            backdrop-filter: blur(10px);
        }
        
        /* Add any additional styles needed for this page */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation Bar (same as dashboard.php) -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-graduation-cap"></i> ClassReserve CHAU
                </a>
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar (same as dashboard.php) -->
                <div class="col-md-3 col-lg-2">
                    <div class="sidebar">
                        <h5 class="mb-3"><i class="fas fa-tachometer-alt"></i> Admin Panel</h5>
                        <nav class="nav flex-column">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                            <a class="nav-link active" href="manage_users.php">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                            <a class="nav-link" href="manage_courses.php">
                                <i class="fas fa-book"></i> Manage Courses
                            </a>
                            <a class="nav-link" href="manage_programs.php">
                                <i class="fas fa-graduation-cap"></i> Manage Programs
                            </a>
                            <a class="nav-link" href="manage_rooms.php">
                                <i class="fas fa-building"></i> Manage Rooms
                            </a>
                            <a class="nav-link" href="bookings.php">
                                <i class="fas fa-calendar-alt"></i> All Bookings
                            </a>
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog"></i> System Settings
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-md-9 col-lg-10">
                    <div class="main-content">
                        <!-- Page Header -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h2><i class="fas fa-users"></i> Manage Users</h2>
                                <p class="text-muted">View, add, edit, and delete system users</p>
                            </div>
                        </div>

                        <!-- Success/Error Messages -->
                        <?php if (isset($successMessage)): ?>
                            <div class="alert alert-success"><?php echo $successMessage; ?></div>
                        <?php endif; ?>
                        <?php if (isset($errorMessage)): ?>
                            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                        <?php endif; ?>

                        <!-- Add User Form -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-user-plus"></i> Add New User</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label for="name" class="form-label">Full Name</label>
                                                    <input type="text" class="form-control" id="name" name="name" required>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="email" class="form-label">Email</label>
                                                    <input type="email" class="form-control" id="email" name="email" required>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="password" class="form-label">Password</label>
                                                    <input type="password" class="form-control" id="password" name="password" required>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label for="role" class="form-label">Role</label>
                                                    <select class="form-select" id="role" name="role" required>
                                                        <option value="student">Student</option>
                                                        <option value="lecturer">Lecturer</option>
                                                        <option value="class_rep">Class Representative</option>
                                                        <option value="admin">Administrator</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4 mb-3" id="courseField">
                                                    <label for="course_id" class="form-label">Course</label>
                                                    <select class="form-select" id="course_id" name="course_id">
                                                        <option value="">Select Course</option>
                                                        <?php foreach ($courses as $course): ?>
                                                            <option value="<?php echo $course['course_id']; ?>">
                                                                <?php echo htmlspecialchars($course['course_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4 mb-3 d-flex align-items-end">
                                                    <button type="submit" name="add_user" class="btn btn-primary">
                                                        <i class="fas fa-save"></i> Add User
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Users Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-users-cog"></i> System Users</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="usersTable" class="table table-striped" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Photo</th>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Role</th>
                                                        <th>Course</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($users as $user): ?>
                                                        <tr>
                                                            <td><?php echo $user['user_id']; ?></td>
                                                            <td>
                                                                <img src="<?php echo $user['photo'] ?? '../../assets/default-user.png'; ?>" 
                                                                     class="user-avatar" alt="User Photo">
                                                            </td>
                                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                            <td>
                                                                <span class="badge 
                                                                    <?php echo match($user['role']) {
                                                                        'admin' => 'bg-danger',
                                                                        'lecturer' => 'bg-warning',
                                                                        'class_rep' => 'bg-info',
                                                                        default => 'bg-primary'
                                                                    }; ?>">
                                                                    <?php echo ucfirst($user['role']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo $user['course_name'] ?? 'N/A'; ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-warning edit-user" 
                                                                        data-userid="<?php echo $user['user_id']; ?>"
                                                                        data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                                        data-role="<?php echo $user['role']; ?>"
                                                                        data-course="<?php echo $user['course_id'] ?? ''; ?>">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-danger delete-user" 
                                                                        data-userid="<?php echo $user['user_id']; ?>">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="editName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="editRole" class="form-label">Role</label>
                            <select class="form-select" id="editRole" name="role" required>
                                <option value="student">Student</option>
                                <option value="lecturer">Lecturer</option>
                                <option value="class_rep">Class Representative</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <div class="mb-3" id="editCourseField">
                            <label for="editCourse" class="form-label">Course</label>
                            <select class="form-select" id="editCourse" name="course_id">
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo htmlspecialchars($course['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#usersTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']]
            });

            // Use event delegation for edit buttons (works with DataTables)
            $(document).on('click', '.edit-user', function() {
                const userId = $(this).data('userid');
                const userName = $(this).data('name');
                const userEmail = $(this).data('email');
                const userRole = $(this).data('role');
                const userCourse = $(this).data('course');

                console.log('Edit user clicked:', userId, userName, userEmail, userRole, userCourse);

                $('#editUserId').val(userId);
                $('#editName').val(userName);
                $('#editEmail').val(userEmail);
                $('#editRole').val(userRole);
                $('#editCourse').val(userCourse);

                // Show/hide course field based on role
                toggleCourseField(userRole, 'edit');

                // Show modal
                $('#editUserModal').modal('show');
            });

            // Use event delegation for delete buttons (works with DataTables)
            $(document).on('click', '.delete-user', function() {
                const userId = $(this).data('userid');
                const userName = $(this).closest('tr').find('td:nth-child(3)').text();
                
                console.log('Delete user clicked:', userId, userName);
                
                $('#deleteUserId').val(userId);
                $('#deleteUserModal .modal-body p').html(`Are you sure you want to delete user <strong>${userName}</strong>? This action cannot be undone.`);
                $('#deleteUserModal').modal('show');
            });

            // Toggle course field based on role selection
            $('#role').change(function() {
                toggleCourseField($(this).val(), 'add');
            });
            
            $('#editRole').change(function() {
                toggleCourseField($(this).val(), 'edit');
            });

            function toggleCourseField(role, context) {
                const courseField = context === 'edit' ? '#editCourseField' : '#courseField';
                const courseSelect = context === 'edit' ? '#editCourse' : '#course_id';
                
                if (role === 'student' || role === 'class_rep') {
                    $(courseField).show();
                    $(courseSelect).prop('required', true);
                } else {
                    $(courseField).hide();
                    $(courseSelect).prop('required', false).val('');
                }
            }

            // Initialize course field visibility
            toggleCourseField($('#role').val(), 'add');
            
            // Form validation
            $('form').on('submit', function(e) {
                const form = $(this);
                const requiredFields = form.find('input[required], select[required]');
                let isValid = true;
                
                requiredFields.each(function() {
                    if (!$(this).val()) {
                        $(this).addClass('is-invalid');
                        isValid = false;
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
            
            // Remove validation styling on input
            $('input, select').on('input change', function() {
                $(this).removeClass('is-invalid');
            });
        });
    </script>
</body>
</html>