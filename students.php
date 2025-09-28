<?php
session_start();
require 'config.php';

// Handle Delete Request
if (isset($_GET['delete_student_id'])) {
    $delete_id = (int)$_GET['delete_student_id'];
    if ($delete_id > 0) {
        $delStmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
        if ($delStmt->execute([$delete_id])) {
            header("Location: students.php?teacher_id=" . (int)$_GET['teacher_id']);
            exit;
        } else {
            echo "<p style='color:red;'>Failed to delete student.</p>";
        }
    }
}

// Get teacher_id from URL (example: ?teacher_id=1)
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

if ($teacher_id <= 0) {
    die("Please provide a valid teacher ID in the URL (e.g., ?teacher_id=1)");
}

// Fetch all grades/classes that this teacher teaches
$stmt = $pdo->prepare("
    SELECT grade 
    FROM teacher_classes 
    WHERE teacher_id = ?
");
$stmt->execute([$teacher_id]);
$grades = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($grades)) {
    die("This teacher is not assigned to any class.");
}

// Fetch all students from those grades
$inQuery = implode(',', array_fill(0, count($grades), '?'));
$sql = "SELECT student_id, fullname, roll_no, class, profile_photo 
        FROM students 
        WHERE class IN ($inQuery)
        ORDER BY class, roll_no";
$stmt = $pdo->prepare($sql);
$stmt->execute($grades);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Students under Teacher</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
        h2 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #007bff; color: #fff; }
        img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        .delete-btn { color: #fff; background: #e63946; padding: 5px 10px; border-radius: 5px; text-decoration: none; }
        .delete-btn:hover { background: #d62828; }
    </style>
</head>
<body>

<h2>👨‍🏫 Students under Teacher ID <?= htmlspecialchars($teacher_id) ?></h2>

<?php if (!empty($students)): ?>
    <table>
        <tr>
            <th>Photo</th>
            <th>Student ID</th>
            <th>Full Name</th>
            <th>Roll No</th>
            <th>Class</th>
            <th>Action</th>
        </tr>
        <?php foreach ($students as $student): ?>
        <tr>
            <td>
                <?php if(!empty($student['profile_photo'])): ?>
                    <img src="<?= htmlspecialchars($student['profile_photo']) ?>" alt="Photo">
                <?php else: ?>
                    <img src="img.jpg" alt="No Photo">
                <?php endif; ?>
            </td>
            <td><?= $student['student_id'] ?></td>
            <td><?= htmlspecialchars($student['fullname']) ?></td>
            <td><?= htmlspecialchars($student['roll_no']) ?></td>
            <td><?= htmlspecialchars($student['class']) ?></td>
            <td>
                <a href="students.php?teacher_id=<?= $teacher_id ?>&delete_student_id=<?= $student['student_id'] ?>" 
                   onclick="return confirm('Are you sure you want to delete this student?');" 
                   class="delete-btn">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No students found for this teacher.</p>
<?php endif; ?>

</body>
</html>
