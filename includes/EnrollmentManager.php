<?php

class EnrollmentManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }

    // Checks if a gymnast is already enrolled to a specific program.
     
    public function isAlreadyEnrolled($programId, $gymnastName) {
        $stmt = $this->db->prepare("SELECT id FROM enrollments WHERE program_id = ? AND gymnast_name = ?");
        $stmt->bind_param("is", $programId, $gymnastName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // If a row is found, then the gymnast is already enrolled.
        return $result->num_rows > 0;
    }

    
    public function enrollGymnast($program_id, $name, $age, $experience_level) {
        // Check for duplicates 
        if ($this->isAlreadyEnrolled($program_id, $name)) {
            return [
                'success' => false,
                'message' => "This gymnast is already enrolled in this program."
            ];
        }

        // Continues with enrollment if no duplicates are found
        $stmt = $this->db->prepare("INSERT INTO enrollments (program_id, gymnast_name, age, experience_level, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isis", $program_id, $name, $age, $experience_level);
        
        if ($stmt->execute()) {
            $program = $this->db->query("SELECT coach_name, coach_contact FROM programs WHERE id = $program_id")->fetch_assoc();
            return [
                'success' => true,
                'message' => "Enrollment successful. Coach {$program['coach_name']} notified at {$program['coach_contact']}"
            ];
        }

        // Return a failure message if the query fails for another reason
        return ['success' => false, 'message' => 'Enrollment failed'];
    }

    public function recordAttendance($enrollment_id, $date, $attended) {
        $stmt = $this->db->prepare("INSERT INTO attendance (enrollment_id, date, attended) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $enrollment_id, $date, $attended);
        return $stmt->execute();
    }

    public function recordProgress($enrollment_id, $date, $notes, $score) {
        $stmt = $this->db->prepare("INSERT INTO progress (enrollment_id, date, notes, score) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $enrollment_id, $date, $notes, $score);
        return $stmt->execute();
    }

    public function getGymnastProgress($enrollment_id) {
        $stmt = $this->db->prepare("SELECT AVG(score) as avg_score, COUNT(*) as sessions FROM progress WHERE enrollment_id = ?");
        $stmt->bind_param("i", $enrollment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        $stmt = $this->db->prepare("SELECT COUNT(*) as attended FROM attendance WHERE enrollment_id = ? AND attended = 1");
        $stmt->bind_param("i", $enrollment_id);
        $stmt->execute();
        $attendance_result = $stmt->get_result();
        $attended = $attendance_result->fetch_assoc()['attended'];

        return [
            'average_score' => round($data['avg_score'] ?? 0, 2),
            'sessions_attended' => $attended,
            'total_sessions' => $data['sessions'] ?? 0
        ];
    }
}
?>