<?php
session_start();
require 'config.php';

//   Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user info from DB
$stmt = $pdo->prepare("SELECT fullname, username, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="style.php">

  </head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg container mt-3">
    <div class="container-fluid">
      <span class="navbar-brand fw-bold text-white">🎓 Attendance Tracker</span>
      <div class="d-flex">
        <a href="student_attendance.php" class="btn btn-light me-2">My Attendance</a>
        <a href="logout.php" class="btn btn-logout">Logout</a>
      </div>
    </div>
  </nav>

  <!-- Dashboard Card -->
  <div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="col-md-6">
      <div class="card p-5 text-center bg-white">
        <h2 class="mb-4">Welcome, <?= htmlspecialchars($user['fullname']); ?> 👋</h2>
        <p><strong>Username:</strong> <?= htmlspecialchars($user['username']); ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars(ucfirst($user['role'])); ?></p>
        <div class="mt-3">
          <a href="student_attendance.php" class="btn btn-primary">View My Attendance</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
