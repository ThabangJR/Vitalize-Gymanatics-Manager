<?php
// starts a session to store messages
session_start();

// Includes all necessary files
require_once 'database.php';
require_once 'ProgramManager.php';
require_once 'EnrollmentManager.php';
require_once 'datavalidation.php';

// Initializes the database
try {
    $db = new Database();
    $conn = $db->getConnection();
    $programManager = new ProgramManager($conn);
    $enrollmentManager = new EnrollmentManager($conn);
} catch (RuntimeException $e) {
    http_response_code(500);
    echo "<h1>Database Connection Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    exit();
}

// Handles the form submissions with validation and error handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_program'])) {
            validateProgramData($_POST);
            if ($programManager->addProgram($_POST['name'], $_POST['description'], $_POST['coach_name'], $_POST['coach_contact'], $_POST['duration'], $_POST['skill_level'])) {
                set_message('success', 'The program has been successfully added.');
            } else {
                throw new Exception("Failed to add program.");
            }
        } elseif (isset($_POST['edit_program'])) {
            validateProgramData($_POST);
            if ($programManager->editProgram($_POST['program_id'], $_POST['name'], $_POST['description'], $_POST['coach_name'], $_POST['coach_contact'], $_POST['duration'], $_POST['skill_level'])) {
                set_message('success', 'The program has been successfully updated.');
            } else {
                throw new Exception("Failed to update program.");
            }
        } elseif (isset($_POST['delete_program'])) {
            if ($programManager->deleteProgram($_POST['program_id'])) {
                set_message('success', 'The program has been successfully deleted.');
            } else {
                throw new Exception("Failed to delete program.");
            }
        } elseif (isset($_POST['enroll_gymnast'])) {
            validateEnrollmentData($_POST);
            $result = $enrollmentManager->enrollGymnast($_POST['program_id'], $_POST['gymnast_name'], $_POST['age'], $_POST['experience_level']);
            if ($result['success']) {
                set_message('success', $result['message']);
            } else {
                throw new Exception($result['message']);
            }
        } elseif (isset($_POST['track_progress'])) {
            validateProgressData($_POST);
            if ($enrollmentManager->recordAttendance($_POST['enrollment_id'], $_POST['date'], isset($_POST['attended']) ? 1 : 0)) {
                $message = "Attendance recorded successfully.";
                if (!empty($_POST['progress_notes']) || !empty($_POST['score'])) {
                    if ($enrollmentManager->recordProgress($_POST['enrollment_id'], $_POST['date'], $_POST['progress_notes'], $_POST['score'] ?? 0)) {
                        $message .= " Progress also recorded.";
                    } else {
                        throw new Exception("Failed to record progress.");
                    }
                }
                set_message('success', $message);
            } else {
                throw new Exception("Failed to record attendance.");
            }
        }
    } catch (Exception $e) {
        set_message('danger', "Error: " . $e->getMessage());
    }

    // Refresh page to show updates and messages
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// --- Get Data for HTML Interface ---
$programs = $programManager->getPrograms($_GET['search'] ?? '', $_GET['skill_level'] ?? '');
$enrollments_query = $conn->query("SELECT e.id, e.gymnast_name, p.name FROM enrollments e JOIN programs p ON e.program_id = p.id");
$enrollments = $enrollments_query ? $enrollments_query->fetch_all(MYSQLI_ASSOC) : [];
$all_enrollments = $conn->query("SELECT id FROM enrollments");
$all_enrollments_data = $all_enrollments ? $all_enrollments->fetch_all(MYSQLI_ASSOC) : [];

// changing progress bar color according to score

function getProgressBarColor($score) {
    if ($score <= 30) {
        return '#f44336'; 
    } elseif ($score <= 70) {
        return '#ff9800'; 
    } else {
        return '#4caf50'; 
    }
}

//function for messages ---
function set_message($type, $text) {
    $_SESSION['message_type'] = $type;
    $_SESSION['message_text'] = $text;
}

function display_message() {
    if (isset($_SESSION['message_type']) && isset($_SESSION['message_text'])) {
        echo '<div class="alert alert-' . htmlspecialchars($_SESSION['message_type']) . '">' . htmlspecialchars($_SESSION['message_text']) . '</div>';
        unset($_SESSION['message_type']);
        unset($_SESSION['message_text']);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vitalize Gymnastics Management System</title>
    <link href="styles.css" rel="stylesheet">
    <style>
       
        .message-container {
            margin-top: 20px;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            padding: 20px;
            background-color: #f0f0f0;
            color: #555;
            border-radius: 8px;
        }
       
        .progress-bar-container {
            width: 100%;
            background-color: #e0e0e0;
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden; 
        }
        .progress-bar-fill {
            height: 20px;
           
            text-align: center;
            line-height: 20px;
            color: white;
            font-size: 12px;
            font-weight: bold;
            transition: width 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Vitalize Gymnastics Management System</h1>

        <div class="message-container">
            <?php display_message(); ?>
        </div>

        <div class="card">
            <h2>Manage Programs</h2>
            <form method="POST" class="form">
                <input type="hidden" name="program_id" id="program_id">
                <input type="text" name="name" placeholder="Program Name" required>
                <textarea name="description" placeholder="Description"></textarea>
                <input type="text" name="coach_name" placeholder="Coach Name" required>
                <input type="text" name="coach_contact" placeholder="Coach Contact (Email or Phone)" required>
                <input type="number" name="duration" placeholder="Duration (weeks)" required>
                <select name="skill_level" required>
                    <option value="Beginner">Beginner</option>
                    <option value="Intermediate">Intermediate</option>
                    <option value="Advanced">Advanced</option>
                </select>
                <button type="submit" name="add_program" class="btn btn-primary">Add Program</button>
                <button type="submit" name="edit_program" class="btn btn-secondary edit-btn hidden">Update Program</button>
            </form>
        </div>

        <div class="card">
            <h2>Program List</h2>
            <form method="GET" class="form-inline">
                <input type="text" name="search" placeholder="Search programs">
                <select name="skill_level">
                    <option value="">All Levels</option>
                    <option value="Beginner">Beginner</option>
                    <option value="Intermediate">Intermediate</option>
                    <option value="Advanced">Advanced</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Coach</th>
                        <th>Duration</th>
                        <th>Skill Level</th>
                        <th>Enrolled</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($programs as $program) : ?>
                        <tr>
                            <td><?= htmlspecialchars($program['name']) ?></td>
                            <td><?= htmlspecialchars($program['coach_name']) ?></td>
                            <td><?= htmlspecialchars($program['duration']) ?> weeks</td>
                            <td><?= htmlspecialchars($program['skill_level']) ?></td>
                            <td><?= htmlspecialchars($program['enrolled_count']) ?></td>
                            <td>
                                <button onclick='editProgram(
                                    <?= json_encode($program['id']) ?>,
                                    <?= json_encode($program['name']) ?>,
                                    <?= json_encode($program['description']) ?>,
                                    <?= json_encode($program['coach_name']) ?>,
                                    <?= json_encode($program['coach_contact']) ?>,
                                    <?= json_encode($program['duration']) ?>,
                                    <?= json_encode($program['skill_level']) ?>
                                )' class="btn btn-edit">Edit</button>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="program_id" value="<?= htmlspecialchars($program['id']) ?>">
                                    <button type="submit" name="delete_program" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this program?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Enroll Gymnast</h2>
            <form method="POST" class="form">
                <select name="program_id" required>
                    <option value="">Select Program</option>
                    <?php foreach ($programs as $program) : ?>
                        <option value="<?= htmlspecialchars($program['id']) ?>"><?= htmlspecialchars($program['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="gymnast_name" placeholder="Gymnast Name" required>
                <input type="number" name="age" placeholder="Age" required>
                <select name="experience_level" required>
                    <option value="Beginner">Beginner</option>
                    <option value="Intermediate">Intermediate</option>
                    <option value="Advanced">Advanced</option>
                </select>
                <button type="submit" name="enroll_gymnast" class="btn btn-primary">Enroll</button>
            </form>
        </div>

        <div class="card">
            <h2>Track Progress & Attendance</h2>
            <form method="POST" class="form">
                <select name="enrollment_id" required>
                    <option value="">Select Gymnast</option>
                    <?php foreach ($enrollments as $enrollment) : ?>
                        <option value="<?= htmlspecialchars($enrollment['id']) ?>">
                            <?= htmlspecialchars($enrollment['gymnast_name']) ?> - <?= htmlspecialchars($enrollment['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="date" required>
                <label><input type="checkbox" name="attended" value="1"> Attended</label>
                <textarea name="progress_notes" placeholder="Progress Notes"></textarea>
                <input type="number" name="score" placeholder="Score (0-100)" min="0" max="100">
                <button type="submit" name="track_progress" class="btn btn-primary">Record</button>
            </form>
            
            <div class="progress-section">
                <?php
                // Fetches all the enrollment data to display progress for every enrolled gymnast
                $all_enrollments = $conn->query("SELECT e.id, e.gymnast_name, p.name FROM enrollments e JOIN programs p ON e.program_id = p.id");
                $all_enrollments_data = $all_enrollments ? $all_enrollments->fetch_all(MYSQLI_ASSOC) : [];

                foreach ($all_enrollments_data as $enrollment) :
                    // Calls the method for each individual enrollment ID
                    $progress = $enrollmentManager->getGymnastProgress($enrollment['id']);
                    // Defines the color based on the score
                    $color = getProgressBarColor($progress['average_score']);
                ?>
                    <div class="progress-item">
                        <h4><?= htmlspecialchars($enrollment['gymnast_name']) ?> (<?= htmlspecialchars($enrollment['name']) ?>)</h4>
                        
                        <p>Average Score: <?= htmlspecialchars($progress['average_score']) ?>%</p>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?= htmlspecialchars($progress['average_score']) ?>%; background-color: <?= htmlspecialchars($color) ?>;"></div>
                        </div>
                        <p>Attendance: <?= htmlspecialchars($progress['sessions_attended']) ?> / <?= htmlspecialchars($progress['total_sessions']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Created by [Thabang Mohale]</p>
    </div>

    <script>
        function editProgram(id, name, description, coach_name, coach_contact, duration, skill_level) {
            document.querySelector('input[name="program_id"]').value = id;
            document.querySelector('input[name="name"]').value = name;
            document.querySelector('textarea[name="description"]').value = description;
            document.querySelector('input[name="coach_name"]').value = coach_name;
            document.querySelector('input[name="coach_contact"]').value = coach_contact;
            document.querySelector('input[name="duration"]').value = duration;
            document.querySelector('select[name="skill_level"]').value = skill_level;
            document.querySelector('button[name="add_program"]').classList.add('hidden');
            document.querySelector('button[name="edit_program"]').classList.remove('hidden');
        }
    </script>
</body>
</html>