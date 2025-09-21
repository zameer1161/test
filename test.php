<?php
session_start();
// require './config.php';

$message = "";

// Handle login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && $password === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'student') {
            header("Location: dashboard.php"); // student dashboard
        } elseif ($user['role'] === 'teacher' || $user['role'] === 'admin') {
            header("Location: teacher_dashboard.php"); // teacher/admin dashboard
        }
        exit;
    } else {
        $message = "❌ Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Attendance Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #ff9966, #ff5e62);
      font-family: 'Segoe UI', sans-serif;
    }
    .card {
      border-radius: 20px;
      box-shadow: 0px 6px 20px rgba(0,0,0,0.2);
    }
    .btn-custom {
      background: #ff5e62;
      color: white;
      transition: 0.3s ease;
      border-radius: 10px;
      padding: 10px 20px;
    }
    .btn-custom:hover {
      background: #e14b50;
      transform: translateY(-2px);
    }
    .navbar {
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg container mt-3">
    <div class="container-fluid">
      <span class="navbar-brand fw-bold text-white">🎓 Attendance Tracker</span>
      <div class="d-flex">
        <a href="register.php" class="btn btn-light">Register</a>
      </div>
    </div>
  </nav>

  <!-- Login Form -->
  <div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="col-md-6">
      <div class="card p-5 bg-white">
        <h2 class="text-center mb-4">Login</h2>
        <?php //if ($message): ?>
          <!-- <div  class="alert alert-danger"><?= $message ?></div> -->
        <?php // endif; ?>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-custom w-100">Login</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
