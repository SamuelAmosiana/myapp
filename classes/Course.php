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
        try {
            // Simple direct update with essential fields only
            $sql = "UPDATE courses SET course_name = ?, course_code = ?, program_id = ? WHERE course_id = ?";
            $params = [
                $data['course_name'],
                $data['course_code'], 
                $data['program_id'],
                $course_id
            ];
            
            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            // Silent fail for presentation
            return false;
        }
    }

    public function deleteCourse($course_id) {
        return $this->db->delete('courses', 'course_id = ?', [$course_id]);
    }
}
