<?php
session_start();
require 'config.php';

// Check if teacher/admin is logged in
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher','admin'])) {
    header("Location: login.php");
    exit;
}

$message = "";

// Fetch all students
$stmt = $pdo->query("SELECT student_id, fullname, roll_no, class, profile_photo FROM students ORDER BY class, roll_no");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $date = $_POST['date'] ?? date('Y-m-d');
    $teacher_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();
        foreach ($_POST['status'] as $student_id => $status) {
            // Prevent duplicate attendance for same student/date
            $check = $pdo->prepare("SELECT attendance_id FROM attendance WHERE student_id=? AND date=?");
            $check->execute([$student_id, $date]);
            if ($check->fetch()) continue;

            $insert = $pdo->prepare("INSERT INTO attendance (student_id, class, date, status, marked_by) 
                                     VALUES (?, ?, ?, ?, ?)");
            // Get class of student
            $studentClass = '';
            foreach ($students as $s) {
                if ($s['student_id'] == $student_id) { $studentClass = $s['class']; break; }
            }

            $insert->execute([$student_id, $studentClass, $date, $status, $teacher_id]);
        }
        $pdo->commit();
        $message = "✅ Attendance marked successfully for $date!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "❌ Error: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Mark Attendance</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="style.css" href="style.php"
/*<style>
body { margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #0d0d0d , #0d0d0d);
      color: #fff; }
.card { border-radius: 20px; box-shadow: 0px 6px 20px rgba(0,0,0,0.15); }
.btn-custom { background: #ff5e62; color: white; transition: 0.18s; border-radius: 10px; padding: 8px 20px; }
.btn-custom:hover { transform: translateY(-2px); }
.navbar { background: rgba(255,255,255,0.12); backdrop-filter: blur(6px); border-radius: 12px; margin-bottom: 18px; }
</style>
*/ </head>
<body>
<nav class="navbar navbar-expand-lg container mt-3">
<div class="container-fluid">
<span class="navbar-brand fw-bold text-white">🎓 Attendance Tracker</span>
<div class="d-flex">
<a href="teacher_dashboard.php" class="btn btn-light me-2">Dashboard</a>
<a href="logout.php" class="btn btn-light">Logout</a>
</div>
</div>
</nav>

<div class="container d-flex justify-content-center align-items-start min-vh-100 mt-3">
<div class="col-md-10">
<div class="card p-4 bg-white">
<h2 class="text-center mb-4">Mark Attendance</h2>

<?php if ($message): ?>
<div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<form method="POST">
<div class="mb-3">
<label class="form-label">Select Date</label>
<input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
</div>

<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
<th>#</th>
<th>Roll No</th>
<th>Student Name</th>
<th>Class</th>
<th>Profile</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach($students as $i => $s): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($s['roll_no']) ?></td>
<td><?= htmlspecialchars($s['fullname']) ?></td>
<td><?= htmlspecialchars($s['class']) ?></td>
<td>
<?php if ($s['profile_photo']): ?>
<img src="<?= htmlspecialchars($s['profile_photo']) ?>" width="50" class="rounded-circle">
<?php endif; ?>
</td>
<td>
<select name="status[<?= $s['student_id'] ?>]" class="form-select">
<option value="Present">Present</option>
<option value="Absent" selected>Absent</option>
<option value="Late">Late</option>
</select>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<button type="submit" class="btn btn-custom w-100">Submit Attendance</button>
</form>
</div>
</div>
</div>
</body>
</html>
