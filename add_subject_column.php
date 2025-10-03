<?php
require 'config.php';

$message = '';

// Handle teacher-class assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_teacher_class'])) {
    try {
        $teacher_id = $_POST['teacher_id'];
        $grade = $_POST['grade'];
        
        // Check if assignment already exists
        $check_sql = "SELECT id FROM teacher_classes WHERE teacher_id = ? AND grade = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$teacher_id, $grade]);
        
        if ($check_stmt->rowCount() === 0) {
            $sql = "INSERT INTO teacher_classes (teacher_id, grade) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$teacher_id, $grade]);
            $message = "<div class='alert alert-success'>Teacher assigned to class successfully!</div>";
        } else {
            $message = "<div class='alert alert-warning'>This teacher is already assigned to this class.</div>";
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Handle remove teacher-class assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_assignment'])) {
    try {
        $assignment_id = $_POST['assignment_id'];
        $sql = "DELETE FROM teacher_classes WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$assignment_id]);
        $message = "<div class='alert alert-success'>Assignment removed successfully!</div>";
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Get all teachers
$teachers_sql = "SELECT t.teacher_id, u.fullname, u.email, u.username 
                 FROM teachers t 
                 JOIN users u ON t.user_id = u.id 
                 ORDER BY u.fullname";
$teachers = $pdo->query($teachers_sql)->fetchAll(PDO::FETCH_ASSOC);

// Get unique classes
$classes_sql = "SELECT DISTINCT class FROM students ORDER BY class";
$classes = $pdo->query($classes_sql)->fetchAll(PDO::FETCH_COLUMN);

// Get current assignments
$assignments_sql = "
    SELECT tc.id, tc.teacher_id, tc.grade, u.fullname as teacher_name
    FROM teacher_classes tc
    JOIN teachers t ON tc.teacher_id = t.teacher_id
    JOIN users u ON t.user_id = u.id
    ORDER BY tc.grade, u.fullname
";
$assignments = $pdo->query($assignments_sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Teachers to Classes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
           
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <h2>Assign Teachers to Classes</h2>
                    
                    <?php echo $message; ?>

                    <!-- Assign Teacher to Class -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Assign Teacher to Class</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-5">
                                        <label>Select Teacher</label>
                                        <select name="teacher_id" class="form-select" required>
                                            <option value="">Choose Teacher</option>
                                            <?php foreach ($teachers as $teacher): ?>
                                                <option value="<?= $teacher['teacher_id'] ?>">
                                                    <?= htmlspecialchars($teacher['fullname']) ?> (<?= $teacher['username'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label>Class/Grade</label>
                                        <select name="grade" class="form-select" required>
                                            <option value="">Choose Class</option>
                                            <?php foreach ($classes as $class): ?>
                                                <option value="<?= $class ?>"><?= $class ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <button type="submit" name="assign_teacher_class" class="btn btn-primary w-100">Assign</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Current Assignments -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Current Teacher-Class Assignments</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($assignments)): ?>
                                <p class="text-muted">No teacher-class assignments found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Teacher</th>
                                                <th>Class</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($assignments as $assignment): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($assignment['teacher_name']) ?></td>
                                                    <td><?= htmlspecialchars($assignment['grade']) ?></td>
                                                    <td>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                                            <button type="submit" name="remove_assignment" class="btn btn-danger btn-sm" 
                                                                    onclick="return confirm('Are you sure you want to remove this assignment?')">
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Teacher Summary -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Teacher Assignment Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php
                                $teacher_summary = $pdo->query("
                                    SELECT u.fullname, COUNT(tc.grade) as class_count,
                                           GROUP_CONCAT(tc.grade ORDER BY tc.grade SEPARATOR ', ') as classes
                                    FROM teachers t
                                    JOIN users u ON t.user_id = u.id
                                    LEFT JOIN teacher_classes tc ON t.teacher_id = tc.teacher_id
                                    GROUP BY t.teacher_id, u.fullname
                                    ORDER BY u.fullname
                                ")->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                
                                <?php foreach ($teacher_summary as $summary): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6><?= htmlspecialchars($summary['fullname']) ?></h6>
                                                <p class="mb-1">
                                                    <strong>Classes Assigned:</strong> 
                                                    <?= $summary['class_count'] ?>
                                                </p>
                                                <p class="mb-0 text-muted small">
                                                    <?= $summary['classes'] ?: 'No classes assigned' ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>