<?php
session_start();

// Database config
$host = "cloud-database-db.mysql.database.azure.com";
$user = "zameer";   // must include @servername
$password = "ZAIDISGAY*123";
$dbname = "attendance_db";
$port = 3306;

// Path to SSL certificate required by Azure MySQL
$ssl_ca = __DIR__ . "/DigiCertGlobalRootCA.crt.pem";

// Init mysqli
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, $ssl_ca, NULL, NULL);

if (!mysqli_real_connect($conn, $host, $user, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("❌ Database connection failed: " . mysqli_connect_error());
}

// Handle login
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password_input = $_POST['password'];

    // Prepared statement
    $stmt = mysqli_prepare($conn, "SELECT id, username, password, role FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && $password_input === $user['password']) { 
        // ⚠️ Plain text password comparison. 
        // In production, store hashed passwords and use password_verify().
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'student') {
            header("Location: dashboard.php"); 
        } elseif ($user['role'] === 'teacher' || $user['role'] === 'admin') {
            header("Location: teacher_dashboard.php"); 
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

        <?php if (!empty($message)): ?>
          <div class="alert alert-danger"><?= $message ?></div>
        <?php endif; ?>

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
