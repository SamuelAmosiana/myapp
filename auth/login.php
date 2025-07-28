<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ClassReserve CHAU</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 450px;
            width: 100%;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #333;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
            margin-bottom: 0;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .university-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .role-info {
            background: rgba(108, 117, 125, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
            font-size: 0.8rem;
            margin: 0.2rem;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-container">
                    <!-- Header -->
                    <div class="login-header">
                        <h1><i class="fas fa-graduation-cap text-primary"></i> ClassReserve</h1>
                        <p>Chalimbana University</p>
                    </div>
                    
                    <!-- University Info -->
                    <div class="university-info">
                        <h6><i class="fas fa-info-circle"></i> Classroom Reservation System</h6>
                        <small>Enter your Student ID to access the system</small>
                    </div>
                    
                    <!-- Error/Success Messages -->
                    <div id="message-container">
                        <!-- Messages will be displayed here -->
                    </div>
                    
                    <!-- Login Form -->
                    <form id="loginForm" method="POST" action="process_login.php">
                        <div class="mb-3">
                            <label for="role" class="form-label">
                                <i class="fas fa-user-tag"></i> Select Role
                            </label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">-- Select Role --</option>
                                <option value="admin">Admin</option>
                                <option value="lecturer">Lecturer</option>
                                <option value="class_rep">Class Rep</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                        <div id="studentFields" class="mb-3 d-none">
                            <label for="student_id" class="form-label">
                                <i class="fas fa-id-card"></i> Student ID
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="student_id" 
                                   name="student_id" 
                                   placeholder="Enter your Student ID (e.g., 2104035934)"
                                   autocomplete="username">
                            <div class="form-text">
                                <i class="fas fa-lock"></i> Your Student ID is used as both username and password
                            </div>
                        </div>
                        <div id="adminLecturerFields" class="d-none">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" autocomplete="username">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" autocomplete="current-password">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-login" id="loginBtn">
                            <span id="loginText">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </span>
                            <span id="loginSpinner" class="d-none">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                Logging in...
                            </span>
                        </button>
                    </form>
                    
                    <!-- Role Information -->
                    <div class="role-info">
                        <h6><i class="fas fa-users"></i> User Roles:</h6>
                        <div class="text-center">
                            <span class="role-badge bg-danger text-white">
                                <i class="fas fa-crown"></i> Admin
                            </span>
                            <span class="role-badge bg-success text-white">
                                <i class="fas fa-user-tie"></i> Class Rep
                            </span>
                            <span class="role-badge bg-info text-white">
                                <i class="fas fa-chalkboard-teacher"></i> Lecturer
                            </span>
                            <span class="role-badge bg-primary text-white">
                                <i class="fas fa-user-graduate"></i> Student
                            </span>
                        </div>
                        <small class="text-muted d-block mt-2 text-center">
                            Different roles have different access levels
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Handle form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const roleSelect = document.getElementById('role');
            const role = roleSelect.value;
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            const loginSpinner = document.getElementById('loginSpinner');
            const messageContainer = document.getElementById('message-container');
            
            // Validate role
            if (!role) {
                showMessage('Please select your role.', 'danger');
                return;
            }
            
            // Show loading state
            loginBtn.disabled = true;
            loginText.classList.add('d-none');
            loginSpinner.classList.remove('d-none');
            
            // Create form data
            const formData = new FormData();
            formData.append('role', role);
            if (role === 'student' || role === 'class_rep') {
                const studentId = document.getElementById('student_id').value.trim();
                if (!studentId) {
                    showMessage('Please enter your Student ID', 'danger');
                    resetButton();
                    return;
                }
                formData.append('student_id', studentId);
            } else if (role === 'admin' || role === 'lecturer') {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                if (!email || !password) {
                    showMessage('Please enter your email and password', 'danger');
                    resetButton();
                    return;
                }
                formData.append('email', email);
                formData.append('password', password);
            }
            
            // Submit form via AJAX
            fetch('process_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    showMessage(data.message, 'danger');
                    resetButton();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Login failed. Please try again.', 'danger');
                resetButton();
            });
        });
        
        // Reset button state
        function resetButton() {
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            const loginSpinner = document.getElementById('loginSpinner');
            
            loginBtn.disabled = false;
            loginText.classList.remove('d-none');
            loginSpinner.classList.add('d-none');
        }
        
        // Show message function
        function showMessage(message, type) {
            const messageContainer = document.getElementById('message-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
            
            messageContainer.innerHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="${icon}"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    const alert = messageContainer.querySelector('.alert');
                    if (alert) {
                        alert.classList.remove('show');
                        setTimeout(() => {
                            alert.remove();
                        }, 150);
                    }
                }, 3000);
            }
        }
        
        // Handle URL parameters for messages
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');
            const success = urlParams.get('success');
            
            if (error) {
                showMessage(decodeURIComponent(error), 'danger');
            }
            
            if (success) {
                showMessage(decodeURIComponent(success), 'success');
            }
        });
        
        // Auto-focus on student ID input
        document.getElementById('student_id').focus();

        // Role-based field display
        const roleSelect = document.getElementById('role');
        const studentFields = document.getElementById('studentFields');
        const adminLecturerFields = document.getElementById('adminLecturerFields');

        roleSelect.addEventListener('change', function() {
            const role = this.value;
            if (role === 'student' || role === 'class_rep') {
                studentFields.classList.remove('d-none');
                studentFields.querySelector('input').required = true;
                adminLecturerFields.classList.add('d-none');
                adminLecturerFields.querySelectorAll('input').forEach(input => input.required = false);
            } else if (role === 'admin' || role === 'lecturer') {
                adminLecturerFields.classList.remove('d-none');
                adminLecturerFields.querySelectorAll('input').forEach(input => input.required = true);
                studentFields.classList.add('d-none');
                studentFields.querySelector('input').required = false;
            } else {
                studentFields.classList.add('d-none');
                adminLecturerFields.classList.add('d-none');
                studentFields.querySelector('input').required = false;
                adminLecturerFields.querySelectorAll('input').forEach(input => input.required = false);
            }
        });
    </script>
</body>
</html>