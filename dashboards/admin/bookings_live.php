<?php
/**
 * Live Booking Management - ClassReserve CHAU
 * Real-time CRUD operations for classroom bookings
 */

session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/RBAC.php';
require_once __DIR__ . '/../../classes/BookingManager.php';

$auth = new Auth();
$rbac = new RBAC();
$bookingManager = new BookingManager();

$auth->requireLogin('../../auth/login.php');
$rbac->requireRole('admin', '../../auth/login.php');

$currentUser = $auth->getCurrentUser();

// Get initial data
$bookings = $bookingManager->getBookings(['status' => 'pending']);
$stats = $bookingManager->getBookingStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Booking Management - ClassReserve CHAU</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body { background: white; min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .navbar { background: var(--glass-bg) !important; backdrop-filter: blur(20px); border-bottom: 1px solid var(--glass-border); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        .navbar-brand { font-weight: 700; font-size: 1.5rem; }
        
        .main-content { padding: 2rem; }
        
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: var(--glass-bg); backdrop-filter: blur(20px); border-radius: 20px; border: 1px solid var(--glass-border); padding: 2rem; text-align: center; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-value { font-size: 2.5rem; font-weight: 700; color: #667eea; margin-bottom: 0.5rem; }
        .stat-label { color: rgba(51, 51, 51, 0.8); font-weight: 500; }
        .stat-icon { font-size: 2rem; color: #667eea; margin-bottom: 1rem; }
        
        .content-card { background: var(--glass-bg); backdrop-filter: blur(20px); border-radius: 20px; border: 1px solid var(--glass-border); padding: 2rem; margin-bottom: 2rem; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        .content-card h5 { color: #333; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        
        .btn { border-radius: 12px; padding: 0.5rem 1rem; font-weight: 500; border: none; transition: all 0.3s ease; }
        .btn-primary { background: var(--primary-gradient); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4); }
        
        .badge { border-radius: 8px; padding: 0.5rem 0.75rem; }
        .badge-pending { background: linear-gradient(135deg, #ffc107, #ff8c00); color: white; }
        .badge-approved { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .badge-rejected { background: linear-gradient(135deg, #dc3545, #e83e8c); color: white; }
        .badge-cancelled { background: linear-gradient(135deg, #6c757d, #495057); color: white; }
        
        .priority-high { border-left: 4px solid #dc3545; }
        .priority-medium { border-left: 4px solid #ffc107; }
        .priority-low { border-left: 4px solid #28a745; }
        
        .live-indicator { display: inline-flex; align-items: center; gap: 0.5rem; color: #28a745; font-weight: 600; }
        .live-dot { width: 8px; height: 8px; background: #28a745; border-radius: 50%; animation: pulse 2s infinite; }
        
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 1050; }
        .toast { background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); }
        
        .modal-content { background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); }
        
        .table { background: rgba(255, 255, 255, 0.05); }
        .table th { background: rgba(102, 126, 234, 0.1); color: #333; font-weight: 600; }
        .table td { color: #333; }
        
        .refresh-btn { position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; border-radius: 50%; background: var(--primary-gradient); border: none; color: white; font-size: 1.5rem; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4); transition: all 0.3s ease; }
        .refresh-btn:hover { transform: scale(1.1); }
        
        @media (max-width: 768px) { .main-content { padding: 1rem; } .stats-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-graduation-cap"></i> ClassReserve CHAU</a>
            <div class="d-flex align-items-center">
                <div class="live-indicator me-3">
                    <div class="live-dot"></div>
                    <span>LIVE</span>
                </div>
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($currentUser['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-calendar-check text-primary"></i> Live Booking Management</h2>
                <button class="btn btn-primary" onclick="refreshData()">
                    <i class="fas fa-sync-alt me-2"></i>Refresh Now
                </button>
            </div>

            <!-- Statistics -->
            <div class="stats-row" id="statsContainer">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-value" id="pendingCount"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending Approvals</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value" id="approvedCount"><?php echo $stats['approved'] ?? 0; ?></div>
                    <div class="stat-label">Approved Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-value" id="rejectedCount"><?php echo $stats['rejected'] ?? 0; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-value" id="totalCount"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
            </div>

            <!-- Booking Actions -->
            <div class="content-card">
                <h5><i class="fas fa-bolt text-warning"></i> Quick Actions</h5>
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-success w-100" onclick="showCreateBookingModal()">
                            <i class="fas fa-plus me-2"></i>New Booking
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-info w-100" onclick="loadBookings('approved')">
                            <i class="fas fa-check me-2"></i>View Approved
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-warning w-100" onclick="loadBookings('pending')">
                            <i class="fas fa-clock me-2"></i>View Pending
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-danger w-100" onclick="loadBookings('rejected')">
                            <i class="fas fa-times me-2"></i>View Rejected
                        </button>
                    </div>
                </div>
            </div>

            <!-- Live Bookings Table -->
            <div class="content-card">
                <h5><i class="fas fa-list text-primary"></i> Live Bookings <small class="text-muted">(Auto-refreshes every 30 seconds)</small></h5>
                <div class="table-responsive">
                    <table id="bookingsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Room</th>
                                <th>Course</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Booked By</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookingsTableBody">
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Content loaded dynamically -->
                </div>
                <div class="modal-footer" id="modalFooter">
                    <!-- Actions loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Floating Refresh Button -->
    <button class="refresh-btn" onclick="refreshData()" title="Refresh Data">
        <i class="fas fa-sync-alt"></i>
    </button>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        let bookingsTable;
        let refreshInterval;

        $(document).ready(function() {
            initializeTable();
            loadBookings();
            startAutoRefresh();
        });

        function initializeTable() {
            bookingsTable = $('#bookingsTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [8] }
                ]
            });
        }

        function loadBookings(status = 'all') {
            $.ajax({
                url: '../../api/bookings.php?action=list' + (status !== 'all' ? '&status=' + status : ''),
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateBookingsTable(response.data);
                        showToast('Data refreshed successfully', 'success');
                    } else {
                        showToast('Failed to load bookings: ' + response.message, 'error');
                    }
                },
                error: function() {
                    showToast('Network error occurred', 'error');
                }
            });
        }

        function updateBookingsTable(bookings) {
            bookingsTable.clear();
            
            bookings.forEach(function(booking) {
                const statusBadge = getStatusBadge(booking.status);
                const priorityClass = getPriorityClass(booking.priority);
                const actions = getActionButtons(booking);
                
                bookingsTable.row.add([
                    booking.booking_id,
                    booking.room_name + '<br><small class="text-muted">' + booking.location + '</small>',
                    booking.course_code + '<br><small class="text-muted">' + booking.course_name + '</small>',
                    formatDate(booking.booking_date),
                    booking.start_time + ' - ' + booking.end_time,
                    booking.booked_by_name,
                    statusBadge,
                    '<span class="badge bg-secondary">P' + booking.priority + '</span>',
                    actions
                ]);
            });
            
            bookingsTable.draw();
        }

        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge badge-pending">Pending</span>',
                'approved': '<span class="badge badge-approved">Approved</span>',
                'rejected': '<span class="badge badge-rejected">Rejected</span>',
                'cancelled': '<span class="badge badge-cancelled">Cancelled</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
        }

        function getPriorityClass(priority) {
            if (priority >= 4) return 'priority-high';
            if (priority >= 3) return 'priority-medium';
            return 'priority-low';
        }

        function getActionButtons(booking) {
            let buttons = '<div class="btn-group btn-group-sm">';
            buttons += '<button class="btn btn-outline-primary" onclick="viewBooking(' + booking.booking_id + ')"><i class="fas fa-eye"></i></button>';
            
            if (booking.status === 'pending') {
                buttons += '<button class="btn btn-outline-success" onclick="approveBooking(' + booking.booking_id + ')"><i class="fas fa-check"></i></button>';
                buttons += '<button class="btn btn-outline-danger" onclick="rejectBooking(' + booking.booking_id + ')"><i class="fas fa-times"></i></button>';
            }
            
            if (booking.status !== 'cancelled') {
                buttons += '<button class="btn btn-outline-warning" onclick="cancelBooking(' + booking.booking_id + ')"><i class="fas fa-ban"></i></button>';
            }
            
            buttons += '</div>';
            return buttons;
        }

        function viewBooking(bookingId) {
            $.ajax({
                url: '../../api/bookings.php?action=get&id=' + bookingId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showBookingModal(response.data);
                    } else {
                        showToast('Failed to load booking details', 'error');
                    }
                }
            });
        }

        function showBookingModal(booking) {
            const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
            document.getElementById('modalTitle').textContent = 'Booking #' + booking.booking_id;
            
            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Booking Information</h6>
                        <p><strong>Room:</strong> ${booking.room_name} (${booking.location})</p>
                        <p><strong>Date:</strong> ${formatDate(booking.booking_date)}</p>
                        <p><strong>Time:</strong> ${booking.start_time} - ${booking.end_time}</p>
                        <p><strong>Status:</strong> ${getStatusBadge(booking.status)}</p>
                        <p><strong>Priority:</strong> P${booking.priority}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Course & People</h6>
                        <p><strong>Course:</strong> ${booking.course_code} - ${booking.course_name}</p>
                        <p><strong>Program:</strong> ${booking.program_name}</p>
                        <p><strong>Booked By:</strong> ${booking.booked_by_name}</p>
                        ${booking.lecturer_name ? '<p><strong>Lecturer:</strong> ' + booking.lecturer_name + '</p>' : ''}
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6>Purpose</h6>
                        <p>${booking.purpose}</p>
                        ${booking.notes ? '<h6>Notes</h6><p>' + booking.notes + '</p>' : ''}
                        ${booking.rejection_reason ? '<h6>Rejection Reason</h6><p class="text-danger">' + booking.rejection_reason + '</p>' : ''}
                    </div>
                </div>
            `;
            
            modal.show();
        }

        function approveBooking(bookingId) {
            if (confirm('Are you sure you want to approve this booking?')) {
                updateBookingStatus(bookingId, 'approve');
            }
        }

        function rejectBooking(bookingId) {
            const reason = prompt('Please provide a reason for rejection:');
            if (reason !== null) {
                updateBookingStatus(bookingId, 'reject', reason);
            }
        }

        function cancelBooking(bookingId) {
            const reason = prompt('Please provide a reason for cancellation:');
            if (reason !== null) {
                updateBookingStatus(bookingId, 'cancel', reason);
            }
        }

        function updateBookingStatus(bookingId, action, reason = null) {
            const data = { booking_id: bookingId };
            if (reason) data.rejection_reason = reason;
            if (reason && action === 'cancel') data.reason = reason;

            $.ajax({
                url: '../../api/bookings.php?action=' + action,
                method: 'PUT',
                contentType: 'application/json',
                data: JSON.stringify(data),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast(response.message, 'success');
                        refreshData();
                    } else {
                        showToast('Failed: ' + response.message, 'error');
                    }
                },
                error: function() {
                    showToast('Network error occurred', 'error');
                }
            });
        }

        function refreshData() {
            loadBookings();
            loadStats();
        }

        function loadStats() {
            $.ajax({
                url: '../../api/bookings.php?action=stats',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateStats(response.data);
                    }
                }
            });
        }

        function updateStats(stats) {
            document.getElementById('pendingCount').textContent = stats.pending || 0;
            document.getElementById('approvedCount').textContent = stats.approved || 0;
            document.getElementById('rejectedCount').textContent = stats.rejected || 0;
            document.getElementById('totalCount').textContent = stats.total || 0;
        }

        function startAutoRefresh() {
            refreshInterval = setInterval(refreshData, 30000); // 30 seconds
        }

        function showToast(message, type) {
            const toastContainer = document.querySelector('.toast-container');
            const toastId = 'toast-' + Date.now();
            const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
            
            const toastHtml = `
                <div id="${toastId}" class="toast ${bgClass} text-white" role="alert">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                        ${message}
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toast = new bootstrap.Toast(document.getElementById(toastId));
            toast.show();
            
            // Remove toast after it's hidden
            document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>
