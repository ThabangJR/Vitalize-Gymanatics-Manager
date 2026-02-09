<?php


class ProgramManager {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }
    public function addProgram($name, $description, $coach_name, $coach_contact, $duration, $skill_level) {
      
        $stmt = $this->db->prepare("INSERT INTO programs (name, description, coach_name, coach_contact, duration, skill_level, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssis", $name, $description, $coach_name, $coach_contact, $duration, $skill_level);
        return $stmt->execute();
    }
    public function editProgram($id, $name, $description, $coach_name, $coach_contact, $duration, $skill_level) {
        $stmt = $this->db->prepare("UPDATE programs SET name=?, description=?, coach_name=?, coach_contact=?, duration=?, skill_level=? WHERE id=?");
        $stmt->bind_param("ssssisi", $name, $description, $coach_name, $coach_contact, $duration, $skill_level, $id);
        return $stmt->execute();
    }
    public function deleteProgram($id) {
        $stmt = $this->db->prepare("DELETE FROM programs WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    public function getPrograms($search = '', $skill_level = '') {
        $query = "SELECT p.*, COUNT(e.id) as enrolled_count FROM programs p LEFT JOIN enrollments e ON p.id = e.program_id";
        $params = [];
        $types = '';
        if ($search || $skill_level) {
            $query .= " WHERE ";
            if ($search) {
                $query .= "p.name LIKE ? ";
                $params[] = "%$search%";
                $types .= 's';
            }
            if ($skill_level) {
                $query .= ($search ? "AND " : "") . "p.skill_level = ?";
                $params[] = $skill_level;
                $types .= 's';
            }
        }
        $query .= " GROUP BY p.id";
        $stmt = $this->db->prepare($query);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>