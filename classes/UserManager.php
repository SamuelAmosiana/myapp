<?php
require_once __DIR__ . '/../config/Database.php';

class UserManager {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllUsers() {
        $sql = "SELECT * FROM users ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getUserById($user_id) {
        $sql = "SELECT * FROM users WHERE user_id = ?";
        return $this->db->fetchOne($sql, [$user_id]);
    }

    public function addUser($name, $email, $password, $role_id, $course_id = null) {
        $data = [
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role_id' => $role_id,
            'course_id' => $course_id,
            'is_active' => 1
        ];
        return $this->db->insert('users', $data);
    }

    public function updateUser($user_id, $name, $email, $role_id, $course_id = null) {
        $data = [
            'name' => $name,
            'email' => $email,
            'role_id' => $role_id,
            'course_id' => $course_id
        ];
        return $this->db->update('users', $data, 'user_id = ?', [$user_id]);
    }

    public function deleteUser($user_id) {
        return $this->db->delete('users', 'user_id = ?', [$user_id]);
    }
}