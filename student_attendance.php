<?php
session_start();
require './connection/config.php';

// Ensure only students can access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: login.php');
    exit;
}

// Get the student's record
$userId = $_SESSION['user_id'];
$studentStmt = $pdo->prepare("SELECT student_id, fullname, roll_no, class FROM students WHERE user_id = ?");
$studentStmt->execute([$userId]);
$student = $studentStmt->fetch();
if (!$student) {
    die('Student profile not found.');
}

$dateFilterStart = $_GET['start'] ?? '';
$dateFilterEnd   = $_GET['end'] ?? '';

// Build query
$sql = "SELECT date, status FROM attendance WHERE student_id = ?";
$params = [$student['student_id']];
if ($dateFilterStart) { $sql .= " AND date >= ?"; $params[] = $dateFilterStart; }
if ($dateFilterEnd)   { $sql .= " AND date <= ?"; $params[] = $dateFilterEnd; }
$sql .= " ORDER BY date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Summary
$totalDays = count($records);
$present = 0; $absent = 0; $late = 0; $sick = 0;
foreach ($records as $r) {
    if ($r['status'] === 'Present') { $present++; }
    elseif ($r['status'] === 'Absent') { $absent++; }
    elseif ($r['status'] === 'Late') { $late++; }
    elseif ($r['status'] === 'Sick') { $sick++; }
}
$considered = ($present + $absent + $late);
$attendancePercent = $considered ? round(($present / $considered) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Attendance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="style.php">
  <style>
  /* minimal page-specific tweaks can go here */
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg container mt-3">
    <div class="container-fluid">
      <span class="navbar-brand fw-bold text-white">🎓 Attendance Tracker</span>
      <div class="d-flex">
        <a href="dashboard.php" class="btn btn-light me-2">Dashboard</a>
        <a href="logout.php" class="btn btn-light">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container d-flex justify-content-center align-items-start min-vh-100 mt-3">
    <div class="col-md-10">
      <div class="card p-4 bg-white">
        <h2 class="text-center mb-3">My Attendance</h2>
        <p class="text-center mb-4">
          <strong><?= htmlspecialchars($student['fullname']) ?></strong>
          (Roll: <?= htmlspecialchars($student['roll_no']) ?>, Class: <?= htmlspecialchars($student['class']) ?>)
        </p>

        <form class="row g-3 mb-3" method="GET">
          <div class="col-md-4">
            <label class="form-label">From</label>
            <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($dateFilterStart) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">To</label>
            <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($dateFilterEnd) ?>">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
          </div>
        </form>

        <div class="row mb-3">
          <div class="col-md-3"><div class="alert alert-secondary mb-2">Total: <?= $totalDays ?></div></div>
          <div class="col-md-3"><div class="alert alert-success mb-2">Present: <?= $present ?></div></div>
          <div class="col-md-3"><div class="alert alert-warning mb-2">Late: <?= $late ?></div></div>
          <div class="col-md-3"><div class="alert alert-danger mb-2">Absent: <?= $absent ?></div></div>
          <div class="col-md-3"><div class="alert alert-info mb-2">Sick: <?= $sick ?></div></div>
          <div class="col-md-12"><div class="alert alert-info">Attendance (excl. Sick): <?= $attendancePercent ?>%</div></div>
        </div>

        <table class="table table-bordered table-striped">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($records): ?>
              <?php foreach ($records as $i => $r): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($r['date']) ?></td>
                  <td>
                    <?php $status = $r['status']; ?>
                    <span class="badge <?= $status==='Present' ? 'bg-success' : ($status==='Absent' ? 'bg-danger' : ($status==='Late' ? 'bg-warning text-dark' : 'bg-info text-dark')) ?>"><?= htmlspecialchars($status) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="3" class="text-center">No records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>


