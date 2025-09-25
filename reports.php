<?php
session_start();
require 'config.php';

// Check if teacher/admin is logged in
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher','admin'])) {
    header("Location: login.php");
    exit;
}

$classFilter = $_GET['class'] ?? '';
$dateFilter  = $_GET['date'] ?? '';

// Fetch unique classes for filter dropdown
$classesStmt = $pdo->query("SELECT DISTINCT class FROM students ORDER BY class");
$classes = $classesStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch attendance records
$sql = "SELECT a.attendance_id, s.fullname, s.roll_no, s.class, a.date, a.status, u.username AS marked_by
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        LEFT JOIN users u ON a.marked_by = u.id
        WHERE 1";

$params = [];
if ($classFilter) {
    $sql .= " AND s.class = ?";
    $params[] = $classFilter;
}
if ($dateFilter) {
    $sql .= " AND a.date = ?";
    $params[] = $dateFilter;
}

$sql .= " ORDER BY a.date DESC, s.class, s.roll_no";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.php">

 <style> 
 body { margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #0d0d0d , #0d0d0d);
      color: #fff; }
.card { border-radius: 20px; box-shadow: 0px 6px 20px rgba(0,0,0,0.15); }
.navbar { background: rgba(255,255,255,0.12); backdrop-filter: blur(6px); border-radius: 12px; margin-bottom: 18px; }
.badge-present { background-color:rgb(55, 58, 56); }
.badge-absent { background-color: #dc3545; }
.badge-late { background-color: #ffc107; color: black; }
.badge-sick { background-color: #0dcaf0; color: black; }
</style>

</head>
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
<div class="col-md-12">
<div class="card p-4 bg-white">
<h2 class="text-center mb-4">Attendance Report</h2>

<!-- Filter Form -->
<form class="row mb-4" method="GET">
<div class="col-md-4">
<select name="class" class="form-select">
<option value="">-- Select Class --</option>
<?php foreach ($classes as $c): ?>
<option value="<?= htmlspecialchars($c) ?>" <?= ($classFilter == $c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-4">
<input type="date" name="date" class="form-control" value="<?= htmlspecialchars($dateFilter) ?>">
</div>
<div class="col-md-4">
<button type="submit" class="btn btn-primary w-100">Filter</button>
</div>
</form>

<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
<th>#</th>
<th>Roll No</th>
<th>Student Name</th>
<th>Class</th>
<th>Date</th>
<th>Status</th>
<th>Marked By</th>
</tr>
</thead>
<tbody>
<?php if ($attendanceRecords): ?>
<?php foreach($attendanceRecords as $i => $a): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($a['roll_no']) ?></td>
<td><?= htmlspecialchars($a['fullname']) ?></td>
<td><?= htmlspecialchars($a['class']) ?></td>
<td><?= htmlspecialchars($a['date']) ?></td>
<td>
<?php
$status = $a['status'];
$badgeClass = $status === 'Present' ? 'badge-present'
  : ($status === 'Absent' ? 'badge-absent'
  : ($status === 'Late' ? 'badge-late' : 'badge-sick'));
?>
<span class="badge <?= $badgeClass ?>"><?= $status ?></span>
</td>
<td><?= htmlspecialchars($a['marked_by'] ?? '-') ?></td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="7" class="text-center">No records found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</body>
</html>
