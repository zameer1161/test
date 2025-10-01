<?php
session_start();
require 'config.php';

// Only teacher/admin can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher','admin'])) {
    header("Location: login.php");
    exit;
}

// Get class directly from the URL
$selectedClass = $_GET['grade'] ?? '';

// Fetch teacher info
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE user_id=?");
$stmt->execute([$_SESSION['user_id']]);
$teacherData = $stmt->fetch(PDO::FETCH_ASSOC);

$subject = $teacherData['subject'] ?? '';

// Fetch students of that class only
$students = [];
if ($selectedClass) {
    $stmt = $pdo->prepare("SELECT student_id, fullname, roll_no, profile_photo 
                           FROM students 
                           WHERE grade=? 
                           ORDER BY roll_no");
    $stmt->execute([$selectedClass]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $date = $_POST['date'] ?? date('Y-m-d');
    $subject = $_POST['subject'] ?? '';
    $teacher_id = $_SESSION['user_id'];
    $statusArray = $_POST['status'] ?? [];
    $class = $_POST['class'] ?? $selectedClass;

    if (!empty($statusArray) && $class) {
        try {
            $pdo->beginTransaction();
            foreach ($statusArray as $student_id => $selectedStatus) {
                if ($selectedStatus) {
                    // Prevent duplicate attendance
                    $check = $pdo->prepare("SELECT attendance_id FROM attendance 
                                            WHERE student_id=? AND date=? AND class=?");
                    $check->execute([$student_id, $date, $class]);
                    if ($check->fetch()) continue;

                    // Insert attendance (ignore attendance_id, use my_row_id auto_increment)
                    $insert = $pdo->prepare("INSERT INTO attendance (student_id, class, date, status, marked_by)
                                             VALUES (?, ?, ?, ?, ?)");
                    $insert->execute([$student_id, $class, $date, $selectedStatus, $teacher_id]);
                }
            }
            $pdo->commit();
            $message = "✅ Attendance marked successfully for $date!";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "❌ Error: " . $e->getMessage();
        }
    } else {
        $message = "❌ No attendance status selected or class missing.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mark Attendance</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { background: #f5f7fa; font-family: 'Segoe UI', sans-serif; }
.navbar-custom { background: linear-gradient(135deg,#4361ee,#3a0ca3); border-radius:15px; margin-top:20px; padding:15px 25px; }
.navbar-brand { font-weight:700; font-size:1.5rem; color:#fff; }
.wrapper { max-width:1100px; margin:30px auto; padding:25px; background:#fff; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.1); }
h2 { color:#212529; font-weight:700; margin-bottom:20px; }
.table thead { background:#4361ee; color:#fff; }
.btn-custom { background:#4361ee; color:#fff; border-radius:10px; padding:10px 20px; border:none; transition:0.3s; }
.btn-custom:hover { background:#3f37c9; transform:translateY(-2px); }
img.rounded-circle { border-radius:50%; border:2px solid #4361ee; }
.alert { border-radius:15px; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom container">
<div class="container-fluid">
<span class="navbar-brand"><i class="fas fa-graduation-cap me-2"></i>Attendance Tracker</span>
<div class="d-flex">
<a href="teacher_dashboard.php" class="btn btn-light me-2">Dashboard</a>
<a href="Reports.php" class="btn btn-light me-2">Reports</a>
<a href="logout.php" class="btn btn-light">Logout</a>
</div>
</div>
</nav>

<div class="container mt-4">
<div class="wrapper">
<h2>Mark Attendance for Class <?= htmlspecialchars($selectedClass) ?></h2>
<?php if ($message): ?>
<div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<form method="POST">
<div class="row mb-3">
<div class="col-md-6">
<label class="form-label">Select Date</label>
<input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
</div>
<div class="col-md-6">
<label class="form-label">Subject</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($subject) ?>" readonly>
<input type="hidden" name="subject" value="<?= htmlspecialchars($subject) ?>">
<input type="hidden" name="class" value="<?= htmlspecialchars($selectedClass) ?>">
</div>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped align-middle">
<thead>
<tr>
<th>#</th>
<th>Roll No</th>
<th>Student Name</th>
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
<td><?php if($s['profile_photo']): ?><img src="<?= htmlspecialchars($s['profile_photo']) ?>" width="50" class="rounded-circle"><?php endif; ?></td>
<td>
<div class="form-check form-check-inline">
<input class="form-check-input" type="radio" name="status[<?= $s['student_id'] ?>]" value="Present" id="present_<?= $s['student_id'] ?>">
<label class="form-check-label" for="present_<?= $s['student_id'] ?>">Present</label>
</div>
<div class="form-check form-check-inline">
<input class="form-check-input" type="radio" name="status[<?= $s['student_id'] ?>]" value="Absent" id="absent_<?= $s['student_id'] ?>" checked>
<label class="form-check-label" for="absent_<?= $s['student_id'] ?>">Absent</label>
</div>
<div class="form-check form-check-inline">
<input class="form-check-input" type="radio" name="status[<?= $s['student_id'] ?>]" value="Late" id="late_<?= $s['student_id'] ?>">
<label class="form-check-label" for="late_<?= $s['student_id'] ?>">Late</label>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="text-end mt-3">
<button type="submit" class="btn btn-custom"><i class="fas fa-check-circle me-2"></i>Submit Attendance</button>
</div>
</form>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
