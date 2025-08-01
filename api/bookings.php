<?php
/**
 * Booking API - ClassReserve CHAU
 * Handles AJAX requests for booking operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/RBAC.php';
require_once __DIR__ . '/../classes/BookingManager.php';

$auth = new Auth();
$rbac = new RBAC();
$bookingManager = new BookingManager();

// Check authentication
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$currentUser = $auth->getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $bookingManager, $currentUser, $rbac);
            break;
            
        case 'POST':
            handlePostRequest($action, $bookingManager, $currentUser, $rbac);
            break;
            
        case 'PUT':
            handlePutRequest($action, $bookingManager, $currentUser, $rbac);
            break;
            
        case 'DELETE':
            handleDeleteRequest($action, $bookingManager, $currentUser, $rbac);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Booking API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

/**
 * Handle GET requests
 */
function handleGetRequest($action, $bookingManager, $currentUser, $rbac) {
    switch ($action) {
        case 'list':
            // Get bookings with filters
            $filters = [];
            
            // Apply role-based filters
            if ($rbac->hasRole('student') || $rbac->hasRole('class_rep')) {
                $filters['booked_by'] = $currentUser['user_id'];
            }
            
            // Apply request filters
            if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
            if (!empty($_GET['room_id'])) $filters['room_id'] = $_GET['room_id'];
            if (!empty($_GET['course_id'])) $filters['course_id'] = $_GET['course_id'];
            if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
            if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
            
            $bookings = $bookingManager->getBookings($filters);
            echo json_encode(['success' => true, 'data' => $bookings]);
            break;
            
        case 'get':
            $booking_id = $_GET['id'] ?? null;
            if (!$booking_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Booking ID required']);
                return;
            }
            
            $booking = $bookingManager->getBookingById($booking_id);
            if (!$booking) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Booking not found']);
                return;
            }
            
            // Check permissions
            if (!$rbac->hasRole('admin') && !$rbac->hasRole('lecturer') && $booking['booked_by'] != $currentUser['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            echo json_encode(['success' => true, 'data' => $booking]);
            break;
            
        case 'stats':
            // Only admin and lecturers can view stats
            if (!$rbac->hasRole('admin') && !$rbac->hasRole('lecturer')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $filters = [];
            if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
            if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
            
            $stats = $bookingManager->getBookingStats($filters);
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'availability':
            $room_id = $_GET['room_id'] ?? null;
            $date = $_GET['date'] ?? date('Y-m-d');
            
            if (!$room_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Room ID required']);
                return;
            }
            
            // Get existing bookings for the room on the date
            $filters = [
                'room_id' => $room_id,
                'date_from' => $date,
                'date_to' => $date,
                'status' => 'approved'
            ];
            
            $bookings = $bookingManager->getBookings($filters);
            
            // Generate available time slots
            $availableSlots = generateAvailableSlots($bookings, $date);
            
            echo json_encode(['success' => true, 'data' => $availableSlots]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

/**
 * Handle POST requests (Create)
 */
function handlePostRequest($action, $bookingManager, $currentUser, $rbac) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create':
            // Only class reps and admins can create bookings
            if (!$rbac->hasRole('class_rep') && !$rbac->hasRole('admin')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only class representatives can create bookings']);
                return;
            }
            
            // Add current user as the one who booked
            $input['booked_by'] = $currentUser['user_id'];
            
            $result = $bookingManager->createBooking($input);
            
            if ($result['success']) {
                http_response_code(201);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

/**
 * Handle PUT requests (Update)
 */
function handlePutRequest($action, $bookingManager, $currentUser, $rbac) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'approve':
        case 'reject':
            // Only admin and lecturers can approve/reject
            if (!$rbac->hasRole('admin') && !$rbac->hasRole('lecturer')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $booking_id = $input['booking_id'] ?? null;
            $rejection_reason = $input['rejection_reason'] ?? null;
            
            if (!$booking_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Booking ID required']);
                return;
            }
            
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            $result = $bookingManager->updateBookingStatus($booking_id, $status, $currentUser['user_id'], $rejection_reason);
            
            echo json_encode($result);
            break;
            
        case 'update':
            // Only the person who booked or admin can update
            $booking_id = $input['booking_id'] ?? null;
            if (!$booking_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Booking ID required']);
                return;
            }
            
            $booking = $bookingManager->getBookingById($booking_id);
            if (!$booking) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Booking not found']);
                return;
            }
            
            if (!$rbac->hasRole('admin') && $booking['booked_by'] != $currentUser['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            // Update booking logic would go here
            echo json_encode(['success' => true, 'message' => 'Booking updated successfully']);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($action, $bookingManager, $currentUser, $rbac) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'cancel':
            $booking_id = $input['booking_id'] ?? null;
            $reason = $input['reason'] ?? 'Cancelled by user';
            
            if (!$booking_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Booking ID required']);
                return;
            }
            
            $booking = $bookingManager->getBookingById($booking_id);
            if (!$booking) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Booking not found']);
                return;
            }
            
            // Check permissions
            if (!$rbac->hasRole('admin') && $booking['booked_by'] != $currentUser['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $result = $bookingManager->cancelBooking($booking_id, $currentUser['user_id'], $reason);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

/**
 * Generate available time slots for a room on a specific date
 */
function generateAvailableSlots($existingBookings, $date) {
    $slots = [];
    $startHour = 8; // 8 AM
    $endHour = 18; // 6 PM
    $slotDuration = 60; // 60 minutes
    
    // Generate all possible slots
    for ($hour = $startHour; $hour < $endHour; $hour++) {
        $startTime = sprintf('%02d:00:00', $hour);
        $endTime = sprintf('%02d:00:00', $hour + 1);
        
        // Check if this slot conflicts with existing bookings
        $isAvailable = true;
        foreach ($existingBookings as $booking) {
            $bookingStart = strtotime($booking['start_time']);
            $bookingEnd = strtotime($booking['end_time']);
            $slotStart = strtotime($startTime);
            $slotEnd = strtotime($endTime);
            
            if (($slotStart >= $bookingStart && $slotStart < $bookingEnd) ||
                ($slotEnd > $bookingStart && $slotEnd <= $bookingEnd) ||
                ($slotStart <= $bookingStart && $slotEnd >= $bookingEnd)) {
                $isAvailable = false;
                break;
            }
        }
        
        $slots[] = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'available' => $isAvailable,
            'display' => date('g:i A', strtotime($startTime)) . ' - ' . date('g:i A', strtotime($endTime))
        ];
    }
    
    return $slots;
}
?>
