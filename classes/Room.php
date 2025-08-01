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
        return $this->db->update('rooms', $data, 'room_id = ?', [$room_id]);
    }

    public function deleteRoom($room_id) {
        return $this->db->delete('rooms', 'room_id = ?', [$room_id]);
    }
}
