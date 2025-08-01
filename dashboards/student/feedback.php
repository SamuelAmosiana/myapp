<?php
/**
 * Student Submit Feedback - ClassReserve CHAU
 * File: dashboards/student/feedback.php
 */

// Start session and include required files
session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';

// Initialize authentication and RBAC
$auth = new Auth();
$rbac = new RBAC();

// Check if user is logged in and has student role
$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('student', '../../auth/login.php');

// Get current user data
$currentUser = $auth->getCurrentUser();
$course_id = $currentUser['course_id'] ?? null;

// Initialize variables
$success_message = '';
$error_message = '';
$feedback_submitted = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $feedback_type = trim($_POST['feedback_type'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;
    
    // Validation
    if (empty($feedback_type) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (strlen($subject) < 5) {
        $error_message = 'Subject must be at least 5 characters long.';
    } elseif (strlen($message) < 10) {
        $error_message = 'Message must be at least 10 characters long.';
    } elseif ($rating < 1 || $rating > 5) {
        $error_message = 'Please provide a valid rating between 1 and 5.';
    } else {
        // Save feedback to database
        try {
            $db = Database::getInstance();
            $query = "INSERT INTO feedback (user_id, course_id, feedback_type, subject, message, rating, is_anonymous, status, created_at) 
                     VALUES (:user_id, :course_id, :feedback_type, :subject, :message, :rating, :is_anonymous, 'pending', NOW())";
            
            $params = [
                'user_id' => $currentUser['user_id'],
                'course_id' => $course_id,
                'feedback_type' => $feedback_type,
                'subject' => $subject,
                'message' => $message,
                'rating' => $rating,
                'is_anonymous' => $anonymous
            ];
            
            $db->query($query, $params);
            
            // Create notification for admin
            $auth->createNotification(
                1, // Assuming admin user_id is 1
                'New Student Feedback',
                'A new feedback has been submitted by ' . ($anonymous ? 'Anonymous Student' : $currentUser['name']),
                'feedback'
            );
            
            $success_message = 'Thank you! Your feedback has been submitted successfully.';
            $feedback_submitted = true;
            
            // Clear form data
            $_POST = [];
            
        } catch (Exception $e) {
            error_log("Feedback submission error: " . $e->getMessage());
            $error_message = 'Sorry, there was an error submitting your feedback. Please try again.';
        }
    }
}

// Get notifications
$notifications = $auth->getNotifications(false, 5);
$unreadCount = count(array_filter($notifications, function($n) { return !$n['is_read']; }));

// Get recent feedback submitted by this user
try {
    $db = Database::getInstance();
    $recentFeedback = $db->fetchAll(
        "SELECT * FROM feedback WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5",
        ['user_id' => $currentUser['user_id']]
    );
} catch (Exception $e) {
    $recentFeedback = [];
}

// Function to get status badge class
function getStatusBadge($status) {
    switch ($status) {
        case 'pending': return 'bg-warning';
        case 'reviewed': return 'bg-info';
        case 'resolved': return 'bg-success';
        case 'dismissed': return 'bg-secondary';
        default: return 'bg-secondary';
    }
}

// Function to format date
function formatDate($date) {
    return date('M j, Y g:i A', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback - ClassReserve CHAU</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body {
            background: white;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .sidebar {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 2rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 2rem;
            height: fit-content;
        }

        .sidebar h5 {
            color: #333;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .nav-link {
            color: rgba(51, 51, 51, 0.8) !important;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(102, 126, 234, 0.1);
            color: #333 !important;
            border-color: rgba(102, 126, 234, 0.3);
            transform: translateX(5px);
        }

        .main-content {
            padding: 2rem;
        }

        .page-header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .page-header h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: rgba(51, 51, 51, 0.8);
            margin: 0;
        }

        .content-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .content-card h5 {
            color: #333;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label {
            color: #333;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid rgba(51, 51, 51, 0.2);
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn {
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-gradient);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-outline-secondary {
            border: 1px solid rgba(51, 51, 51, 0.3);
            color: #333;
        }

        .btn-outline-secondary:hover {
            background: rgba(51, 51, 51, 0.1);
            border-color: #333;
        }

        .rating-stars {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .rating-star {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .rating-star:hover,
        .rating-star.active {
            color: #ffc107;
        }

        .feedback-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .feedback-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .feedback-subject {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .feedback-meta {
            color: rgba(51, 51, 51, 0.7);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .feedback-message {
            color: rgba(51, 51, 51, 0.8);
            font-size: 0.95rem;
        }

        .alert {
            border-radius: 15px;
            border: none;
            backdrop-filter: blur(20px);
        }

        .alert-success {
            background: rgba(75, 181, 67, 0.2);
            color: #155724;
            border: 1px solid rgba(75, 181, 67, 0.3);
        }

        .alert-danger {
            background: rgba(245, 87, 108, 0.2);
            color: #721c24;
            border: 1px solid rgba(245, 87, 108, 0.3);
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: rgba(51, 51, 51, 0.7);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .feedback-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap"></i> ClassReserve CHAU
            </a>
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
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="sidebar">
                    <h5><i class="fas fa-user-graduate"></i> Student Panel</h5>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a class="nav-link" href="class_status.php">
                            <i class="fas fa-calendar-alt"></i> Class Schedule
                        </a>
                        <a class="nav-link active" href="feedback.php">
                            <i class="fas fa-comment"></i> Submit Feedback
                        </a>
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                        <a class="nav-link" href="notifications.php">
                            <i class="fas fa-bell"></i> Notifications
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-danger ms-2"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Page Header -->
                    <div class="page-header">
                        <h2><i class="fas fa-comment-dots text-primary"></i> Submit Feedback</h2>
                        <p>Share your thoughts, suggestions, or report issues to help us improve ClassReserve CHAU</p>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Feedback Form -->
                    <?php if (!$feedback_submitted): ?>
                    <div class="content-card">
                        <h5><i class="fas fa-edit text-primary"></i> Feedback Form</h5>
                        <form method="POST" id="feedbackForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="feedback_type" class="form-label">Feedback Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="feedback_type" name="feedback_type" required>
                                        <option value="">Select feedback type...</option>
                                        <option value="suggestion" <?php echo (isset($_POST['feedback_type']) && $_POST['feedback_type'] === 'suggestion') ? 'selected' : ''; ?>>Suggestion</option>
                                        <option value="bug_report" <?php echo (isset($_POST['feedback_type']) && $_POST['feedback_type'] === 'bug_report') ? 'selected' : ''; ?>>Bug Report</option>
                                        <option value="feature_request" <?php echo (isset($_POST['feedback_type']) && $_POST['feedback_type'] === 'feature_request') ? 'selected' : ''; ?>>Feature Request</option>
                                        <option value="complaint" <?php echo (isset($_POST['feedback_type']) && $_POST['feedback_type'] === 'complaint') ? 'selected' : ''; ?>>Complaint</option>
                                        <option value="compliment" <?php echo (isset($_POST['feedback_type']) && $_POST['feedback_type'] === 'compliment') ? 'selected' : ''; ?>>Compliment</option>
                                        <option value="other" <?php echo (isset($_POST['feedback_type']) && $_POST['feedback_type'] === 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="rating" class="form-label">Overall Rating <span class="text-danger">*</span></label>
                                    <div class="rating-stars" id="ratingStars">
                                        <i class="fas fa-star rating-star" data-rating="1"></i>
                                        <i class="fas fa-star rating-star" data-rating="2"></i>
                                        <i class="fas fa-star rating-star" data-rating="3"></i>
                                        <i class="fas fa-star rating-star" data-rating="4"></i>
                                        <i class="fas fa-star rating-star" data-rating="5"></i>
                                    </div>
                                    <input type="hidden" id="rating" name="rating" value="<?php echo $_POST['rating'] ?? ''; ?>" required>
                                    <small class="text-muted">Click on stars to rate your experience</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject" name="subject" 
                                       value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" 
                                       placeholder="Brief description of your feedback" 
                                       minlength="5" maxlength="200" required>
                                <div class="form-text">Minimum 5 characters, maximum 200 characters</div>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="6" 
                                          placeholder="Please provide detailed feedback..." 
                                          minlength="10" maxlength="1000" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                <div class="form-text">Minimum 10 characters, maximum 1000 characters</div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="anonymous" name="anonymous" 
                                           <?php echo (isset($_POST['anonymous'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="anonymous">
                                        Submit anonymously
                                    </label>
                                    <div class="form-text">Your name will not be shown to administrators if checked</div>
                                </div>
                            </div>

                            <div class="d-flex gap-3">
                                <button type="submit" name="submit_feedback" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Submit Feedback
                                </button>
                                <button type="reset" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-2"></i>
                                    Reset Form
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- Recent Feedback -->
                    <div class="content-card">
                        <h5><i class="fas fa-history text-info"></i> Your Recent Feedback</h5>
                        <?php if (empty($recentFeedback)): ?>
                            <div class="empty-state">
                                <i class="fas fa-comment-slash"></i>
                                <h6>No Previous Feedback</h6>
                                <p>You haven't submitted any feedback yet. Your feedback history will appear here.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentFeedback as $feedback): ?>
                                <div class="feedback-item">
                                    <div class="feedback-header">
                                        <div class="flex-grow-1">
                                            <div class="feedback-subject"><?php echo htmlspecialchars($feedback['subject']); ?></div>
                                            <div class="feedback-meta">
                                                <i class="fas fa-tag me-1"></i>
                                                <?php echo ucfirst(str_replace('_', ' ', $feedback['feedback_type'])); ?>
                                                • 
                                                <i class="fas fa-star me-1"></i>
                                                <?php echo $feedback['rating']; ?>/5
                                                • 
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo formatDate($feedback['created_at']); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="badge <?php echo getStatusBadge($feedback['status']); ?>">
                                                <?php echo ucfirst($feedback['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="feedback-message">
                                        <?php echo nl2br(htmlspecialchars($feedback['message'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="content-card">
                        <h5><i class="fas fa-bolt text-warning"></i> Quick Actions</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <a href="dashboard.php" class="btn btn-primary w-100">
                                    <i class="fas fa-home me-2"></i>
                                    Back to Dashboard
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="class_status.php" class="btn btn-outline-light w-100">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    View Class Schedule
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Rating stars functionality
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.rating-star');
            const ratingInput = document.getElementById('rating');
            
            // Set initial rating if exists
            const initialRating = ratingInput.value;
            if (initialRating) {
                updateStars(parseInt(initialRating));
            }
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.dataset.rating);
                    ratingInput.value = rating;
                    updateStars(rating);
                });
                
                star.addEventListener('mouseover', function() {
                    const rating = parseInt(this.dataset.rating);
                    highlightStars(rating);
                });
            });
            
            document.getElementById('ratingStars').addEventListener('mouseleave', function() {
                const currentRating = parseInt(ratingInput.value) || 0;
                updateStars(currentRating);
            });
            
            function updateStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
            }
            
            function highlightStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.style.color = '#ffc107';
                    } else {
                        star.style.color = '#ddd';
                    }
                });
            }
        });
        
        // Form validation
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            const rating = document.getElementById('rating').value;
            if (!rating || rating < 1 || rating > 5) {
                e.preventDefault();
                alert('Please provide a rating between 1 and 5 stars.');
                return false;
            }
        });
        
        // Character counters
        function updateCharCount(inputId, countId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(countId);
            if (input && counter) {
                input.addEventListener('input', function() {
                    const remaining = maxLength - this.value.length;
                    counter.textContent = remaining + ' characters remaining';
                    counter.className = remaining < 50 ? 'form-text text-warning' : 'form-text text-muted';
                });
            }
        }
        
        // Auto-resize textarea
        const textarea = document.getElementById('message');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        }
    </script>
</body>
</html>