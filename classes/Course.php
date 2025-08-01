<?php
require_once __DIR__ . '/../config/Database.php';

class Course {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllCourses() {
        $sql = "SELECT * FROM courses ORDER BY course_name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getCourseById($course_id) {
        $sql = "SELECT * FROM courses WHERE course_id = ?";
        return $this->db->fetchOne($sql, [$course_id]);
    }

    public function addCourse($data) {
        return $this->db->insert('courses', $data);
    }

    public function updateCourse($course_id, $data) {
        return $this->db->update('courses', $data, 'course_id = ?', [$course_id]);
    }

    public function deleteCourse($course_id) {
        return $this->db->delete('courses', 'course_id = ?', [$course_id]);
    }
}
