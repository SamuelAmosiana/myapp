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
        return $this->db->update('programs', $data, 'program_id = ?', [$program_id]);
    }

    public function deleteProgram($program_id) {
        return $this->db->delete('programs', 'program_id = ?', [$program_id]);
    }
}