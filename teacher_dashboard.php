<?php
session_start();
require 'config.php';

// Only teacher/admin can access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header("Location: login.php");
    exit;
}

// Fetch teacher/admin info
$stmt = $conn->prepare("SELECT fullname, username, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teacher/Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="style.php">

/* <style>
    body {margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #0d0d0d , #0d0d0d);
      color: #fff;;
    }
    .card {
      border-radius: 20px;
      box-shadow: 0px 6px 20px rgba(0,0,0,0.2);
    }
    .btn-custom {
      background: #ff5e62;
      color: white;
      border-radius: 10px;
      padding: 10px 20px;
    }
    .navbar {
      background: rgba(0, 0, 0, 0.2);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      margin-bottom: 20px;
    }
  </style> 
*/</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg container mt-3">
    <div class="container-fluid">
      <span class="navbar-brand fw-bold text-white">🎓 Attendance Tracker</span>
      <div class="d-flex">
        <a href="attendance_marking.php" class="btn btn-light me-2">Mark Attendance</a>
        <a href="logout.php" class="btn btn-dark">Logout</a>
      </div>
    </div>
  </nav>

  <!-- Dashboard -->
  <div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="col-md-6">
      <div class="card p-5 text-center bg-white">
        <h2 class="mb-4">Welcome, <?= htmlspecialchars($user['fullname']); ?> 👋</h2>
        <p><strong>Username:</strong> <?= htmlspecialchars($user['username']); ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars(ucfirst($user['role'])); ?></p>
        <a href="mark_attendance.php" class="btn btn-custom mt-3">📅 Mark Attendance</a>
      </div>
    </div>
  </div>
</body>
</html>
