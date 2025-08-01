<?php
/**
 * Booking Management System - ClassReserve CHAU
 * Handles all CRUD operations for classroom bookings
 */

require_once __DIR__ . '/../config/Database.php';

class BookingManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new booking request
     */
    public function createBooking($data) {
        try {
            // Validate required fields
            $required = ['booked_by', 'room_id', 'course_id', 'booking_date', 'start_time', 'end_time', 'purpose'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field '$field' is required"];
                }
            }
            
            // Check for time conflicts
            if ($this->hasTimeConflict($data['room_id'], $data['booking_date'], $data['start_time'], $data['end_time'])) {
                return ['success' => false, 'message' => 'Time slot is already booked for this room'];
            }
            
            // Validate time format and logic
            if (strtotime($data['start_time']) >= strtotime($data['end_time'])) {
                return ['success' => false, 'message' => 'End time must be after start time'];
            }
            
            // Validate booking date (not in the past)
            if (strtotime($data['booking_date']) < strtotime(date('Y-m-d'))) {
                return ['success' => false, 'message' => 'Cannot book rooms for past dates'];
            }
            
            // Insert booking
            $bookingData = [
                'booked_by' => $data['booked_by'],
                'room_id' => $data['room_id'],
                'course_id' => $data['course_id'],
                'lecturer_id' => $data['lecturer_id'] ?? null,
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'purpose' => $data['purpose'],
                'notes' => $data['notes'] ?? '',
                'status' => 'pending',
                'priority' => $this->calculatePriority($data),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $booking_id = $this->db->insert('bookings', $bookingData);
            
            if ($booking_id) {
                // Create notifications for relevant users
                $this->createBookingNotifications($booking_id, 'created');
                
                return [
                    'success' => true, 
                    'message' => 'Booking request submitted successfully',
                    'booking_id' => $booking_id
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create booking'];
            }
            
        } catch (Exception $e) {
            error_log("Booking creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Update booking status (approve/reject)
     */
    public function updateBookingStatus($booking_id, $status, $updated_by, $rejection_reason = null) {
        try {
            $validStatuses = ['pending', 'approved', 'rejected', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }
            
            $updateData = [
                'status' => $status,
                'updated_by' => $updated_by,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($status === 'rejected' && $rejection_reason) {
                $updateData['rejection_reason'] = $rejection_reason;
            }
            
            $result = $this->db->update('bookings', $updateData, 'booking_id = ?', [$booking_id]);
            
            if ($result) {
                // Create notifications
                $this->createBookingNotifications($booking_id, $status);
                
                return [
                    'success' => true, 
                    'message' => "Booking $status successfully"
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update booking'];
            }
            
        } catch (Exception $e) {
            error_log("Booking update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get all bookings with filters
     */
    public function getBookings($filters = []) {
        try {
            $sql = "SELECT b.*, r.room_name, r.location, r.capacity,
                           c.course_name, c.course_code,
                           p.program_name,
                           u1.name as booked_by_name,
                           u2.name as lecturer_name,
                           u3.name as updated_by_name
                    FROM bookings b
                    JOIN rooms r ON b.room_id = r.room_id
                    JOIN courses c ON b.course_id = c.course_id
                    JOIN programs p ON c.program_id = p.program_id
                    JOIN users u1 ON b.booked_by = u1.user_id
                    LEFT JOIN users u2 ON b.lecturer_id = u2.user_id
                    LEFT JOIN users u3 ON b.updated_by = u3.user_id";
            
            $conditions = [];
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $conditions[] = "b.status = :status";
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['room_id'])) {
                $conditions[] = "b.room_id = :room_id";
                $params['room_id'] = $filters['room_id'];
            }
            
            if (!empty($filters['course_id'])) {
                $conditions[] = "b.course_id = :course_id";
                $params['course_id'] = $filters['course_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $conditions[] = "b.booking_date >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions[] = "b.booking_date <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }
            
            if (!empty($filters['booked_by'])) {
                $conditions[] = "b.booked_by = :booked_by";
                $params['booked_by'] = $filters['booked_by'];
            }
            
            if ($conditions) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $sql .= " ORDER BY b.booking_date DESC, b.start_time ASC";
            
            return $this->db->fetchAll($sql, $params);
            
        } catch (Exception $e) {
            error_log("Get bookings error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get booking by ID with full details
     */
    public function getBookingById($booking_id) {
        try {
            $sql = "SELECT b.*, r.room_name, r.location, r.capacity,
                           c.course_name, c.course_code,
                           p.program_name,
                           u1.name as booked_by_name, u1.email as booked_by_email,
                           u2.name as lecturer_name, u2.email as lecturer_email,
                           u3.name as updated_by_name
                    FROM bookings b
                    JOIN rooms r ON b.room_id = r.room_id
                    JOIN courses c ON b.course_id = c.course_id
                    JOIN programs p ON c.program_id = p.program_id
                    JOIN users u1 ON b.booked_by = u1.user_id
                    LEFT JOIN users u2 ON b.lecturer_id = u2.user_id
                    LEFT JOIN users u3 ON b.updated_by = u3.user_id
                    WHERE b.booking_id = :booking_id";
            
            return $this->db->fetchOne($sql, ['booking_id' => $booking_id]);
            
        } catch (Exception $e) {
            error_log("Get booking by ID error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Delete/Cancel booking
     */
    public function cancelBooking($booking_id, $cancelled_by, $reason = null) {
        try {
            $updateData = [
                'status' => 'cancelled',
                'updated_by' => $cancelled_by,
                'updated_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => $reason ?? 'Cancelled by user'
            ];
            
            $result = $this->db->update('bookings', $updateData, 'booking_id = ?', [$booking_id]);
            
            if ($result) {
                $this->createBookingNotifications($booking_id, 'cancelled');
                return ['success' => true, 'message' => 'Booking cancelled successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to cancel booking'];
            }
            
        } catch (Exception $e) {
            error_log("Cancel booking error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Check for time conflicts
     */
    private function hasTimeConflict($room_id, $booking_date, $start_time, $end_time, $exclude_booking_id = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM bookings 
                    WHERE room_id = :room_id 
                    AND booking_date = :booking_date 
                    AND status IN ('pending', 'approved')
                    AND (
                        (start_time <= :start_time AND end_time > :start_time) OR
                        (start_time < :end_time AND end_time >= :end_time) OR
                        (start_time >= :start_time AND end_time <= :end_time)
                    )";
            
            $params = [
                'room_id' => $room_id,
                'booking_date' => $booking_date,
                'start_time' => $start_time,
                'end_time' => $end_time
            ];
            
            if ($exclude_booking_id) {
                $sql .= " AND booking_id != :exclude_booking_id";
                $params['exclude_booking_id'] = $exclude_booking_id;
            }
            
            $result = $this->db->fetchOne($sql, $params);
            return $result['count'] > 0;
            
        } catch (Exception $e) {
            error_log("Time conflict check error: " . $e->getMessage());
            return true; // Assume conflict on error for safety
        }
    }
    
    /**
     * Calculate booking priority
     */
    private function calculatePriority($data) {
        $priority = 1; // Default priority
        
        // Higher priority for earlier bookings
        $daysAhead = (strtotime($data['booking_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
        if ($daysAhead <= 1) $priority = 5; // Urgent
        elseif ($daysAhead <= 3) $priority = 4; // High
        elseif ($daysAhead <= 7) $priority = 3; // Medium
        else $priority = 2; // Normal
        
        return $priority;
    }
    
    /**
     * Create notifications for booking events
     */
    private function createBookingNotifications($booking_id, $action) {
        try {
            $booking = $this->getBookingById($booking_id);
            if (!$booking) return;
            
            $auth = new Auth();
            
            switch ($action) {
                case 'created':
                    // Notify admin and lecturers
                    $message = "New booking request for {$booking['room_name']} on {$booking['booking_date']} from {$booking['start_time']} to {$booking['end_time']}";
                    $auth->createNotification(1, 'New Booking Request', $message, 'booking'); // Admin
                    if ($booking['lecturer_id']) {
                        $auth->createNotification($booking['lecturer_id'], 'New Booking Request', $message, 'booking');
                    }
                    break;
                    
                case 'approved':
                    $message = "Your booking for {$booking['room_name']} on {$booking['booking_date']} has been approved";
                    $auth->createNotification($booking['booked_by'], 'Booking Approved', $message, 'booking');
                    break;
                    
                case 'rejected':
                    $message = "Your booking for {$booking['room_name']} on {$booking['booking_date']} has been rejected";
                    if ($booking['rejection_reason']) {
                        $message .= ". Reason: {$booking['rejection_reason']}";
                    }
                    $auth->createNotification($booking['booked_by'], 'Booking Rejected', $message, 'booking');
                    break;
                    
                case 'cancelled':
                    $message = "Booking for {$booking['room_name']} on {$booking['booking_date']} has been cancelled";
                    // Notify admin and lecturer
                    $auth->createNotification(1, 'Booking Cancelled', $message, 'booking');
                    if ($booking['lecturer_id'] && $booking['lecturer_id'] != $booking['updated_by']) {
                        $auth->createNotification($booking['lecturer_id'], 'Booking Cancelled', $message, 'booking');
                    }
                    break;
            }
            
        } catch (Exception $e) {
            error_log("Notification creation error: " . $e->getMessage());
        }
    }
    
    /**
     * Get booking statistics
     */
    public function getBookingStats($filters = []) {
        try {
            $stats = [];
            
            // Total bookings
            $sql = "SELECT COUNT(*) as total FROM bookings";
            $conditions = [];
            $params = [];
            
            if (!empty($filters['date_from'])) {
                $conditions[] = "booking_date >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions[] = "booking_date <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }
            
            if ($conditions) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $result = $this->db->fetchOne($sql, $params);
            $stats['total'] = $result['total'];
            
            // Status breakdown
            $statusSql = "SELECT status, COUNT(*) as count FROM bookings";
            if ($conditions) {
                $statusSql .= " WHERE " . implode(" AND ", $conditions);
            }
            $statusSql .= " GROUP BY status";
            
            $statusResults = $this->db->fetchAll($statusSql, $params);
            foreach ($statusResults as $row) {
                $stats[$row['status']] = $row['count'];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Booking stats error: " . $e->getMessage());
            return [];
        }
    }
}
?>
