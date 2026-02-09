<?php


function validateProgramData($data) {
    if (empty($data['name']) || empty($data['coach_name']) || empty($data['coach_contact']) || empty($data['duration']) || empty($data['skill_level'])) {
        throw new Exception("All program fields are required.");
    }
    if (!is_numeric($data['duration']) || $data['duration'] <= 0) {
        throw new Exception("Duration must be a positive number.");
    }
    $acceptableLevels = ['Beginner', 'Intermediate', 'Advanced'];
    if (!in_array($data['skill_level'], $acceptableLevels)) {
        throw new Exception("Invalid skill level.");
    }
    if (!filter_var($data['coach_contact'], FILTER_VALIDATE_EMAIL) && !preg_match("/^[0-9\-\s\(\)]+$/", $data['coach_contact'])) {
        throw new Exception("Invalid coach contact format. Please use a valid email or phone number.");
    }
    return true;
}

function validateEnrollmentData($data) {
    if (empty($data['program_id']) || empty($data['gymnast_name']) || empty($data['age']) || empty($data['experience_level'])) {
        throw new Exception("All enrollment fields are required.");
    }
    if (!is_numeric($data['age']) || $data['age'] <= 0) {
        throw new Exception("Age must be a positive number.");
    }
    $acceptableLevels = ['Beginner', 'Intermediate', 'Advanced'];
    if (!in_array($data['experience_level'], $acceptableLevels)) {
        throw new Exception("Invalid experience level.");
    }
    return true;
}

function validateProgressData($data) {
    if (empty($data['enrollment_id']) || empty($data['date'])) {
        throw new Exception("Enrollment ID and date are required.");
    }
    if (!empty($data['score']) && (!is_numeric($data['score']) || $data['score'] < 0 || $data['score'] > 100)) {
        throw new Exception("Score must be a number between 0 and 100.");
    }
    return true;
}
?>
