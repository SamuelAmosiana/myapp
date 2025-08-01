<?php
require_once __DIR__ . '/../config/Database.php';

class Room {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllRooms() {
        $sql = "SELECT * FROM rooms ORDER BY room_name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getRoomById($room_id) {
        $sql = "SELECT * FROM rooms WHERE room_id = ?";
        return $this->db->fetchOne($sql, [$room_id]);
    }

    public function addRoom($data) {
        return $this->db->insert('rooms', $data);
    }

    public function updateRoom($room_id, $data) {
        try {
            // Simple direct update with essential fields only
            $sql = "UPDATE rooms SET room_name = ?, location = ?, capacity = ?, room_type = ?, facilities = ?, is_available = ? WHERE room_id = ?";
            $params = [
                $data['room_name'],
                $data['location'],
                $data['capacity'],
                $data['room_type'],
                $data['facilities'],
                $data['is_available'],
                $room_id
            ];
            
            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            // Silent fail for presentation
            return false;
        }
    }

    public function deleteRoom($room_id) {
        return $this->db->delete('rooms', 'room_id = ?', [$room_id]);
    }
}
