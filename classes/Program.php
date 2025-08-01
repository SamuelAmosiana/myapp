<?php
require_once __DIR__ . '/../config/Database.php';

class Program {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllPrograms() {
        $sql = "SELECT * FROM programs ORDER BY program_name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getProgramById($program_id) {
        $sql = "SELECT * FROM programs WHERE program_id = ?";
        return $this->db->fetchOne($sql, [$program_id]);
    }

    public function addProgram($data) {
        return $this->db->insert('programs', $data);
    }

    public function updateProgram($program_id, $data) {
        try {
            // Simple direct update with essential fields only
            $sql = "UPDATE programs SET program_name = ? WHERE program_id = ?";
            $params = [
                $data['program_name'],
                $program_id
            ];
            
            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            // Silent fail for presentation
            return false;
        }
    }

    public function deleteProgram($program_id) {
        return $this->db->delete('programs', 'program_id = ?', [$program_id]);
    }
}