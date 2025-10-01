<?php
session_start();
require 'config.php';

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
        switch ($user['role']) {
            case 'student':
                header("Location: dashboard.php");
                exit;
            case 'teacher':
                header("Location: teacher_dashboard.php");
                exit;
            case 'admin':
                header("Location: admin.php");
                exit;
        }
    } else {
        // Invalid login message
        $message = "❌ Invalid username or password!";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Attendance Tracker</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap");

    *{
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
    }

    body{
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: linear-gradient(135deg, #4361ee 0%, #4cc9f0 100%);
      background-attachment: fixed;
    }

    .wrapper{
      width: 420px;
      background: white;
      color: #333;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 15px 35px rgba(67, 97, 238, 0.2);
      border: 1px solid rgba(67, 97, 238, 0.1);
    }

    .wrapper h1{
      font-size: 36px;
      text-align: center;
      margin-bottom: 30px;
      color: #4361ee;
      font-weight: 700;
    }

    .wrapper .input-box {
      position: relative;
      width: 100%;
      height: 50px;
      margin: 25px 0;
    }

    .input-box input {
      width: 100%;
      height: 100%;
      background: #f8f9fa;
      border: none;
      outline: none;
      border: 2px solid #e9ecef;
      border-radius: 15px;
      font-size: 16px;
      color: #333;
      padding: 20px 45px 20px 20px;
      transition: all 0.3s ease;
    }

    .input-box input:focus {
      border-color: #4361ee;
      background: white;
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
    }

    .input-box input::placeholder{
      color: #6c757d;
    }

    .input-box i {
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 18px;
      color: #4361ee;
    }

    .wrapper .btn{
      width: 100%;
      height: 50px;
      background: linear-gradient(135deg, #4361ee, #3a0ca3);
      border: none;
      outline: none;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
      cursor: pointer;
      font-size: 16px;
      color: white;
      font-weight: 600;
      transition: all 0.3s ease;
      margin-top: 10px;
    }

    .wrapper .btn:hover{
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
    }

    .wrapper .register-link{
      font-size: 14.5px;
      text-align: center;
      margin: 30px 0 15px;
      color: #6c757d;
    }

    .register-link p a {
      color: #4361ee;
      text-decoration: none;
      font-weight: 600;
      margin-left: 5px;
    }

    .register-link p a:hover{
      text-decoration: underline;
    }

    .alert {
      background: rgba(220, 53, 69, 0.1);
      border: 1px solid rgba(220, 53, 69, 0.3);
      color: #dc3545;
      border-radius: 10px;
      margin-bottom: 25px;
      padding: 12px 15px;
      text-align: center;
      font-weight: 500;
    }

    .logo {
      text-align: center;
      margin-bottom: 10px;
    }

    .logo i {
      font-size: 48px;
      color: #4361ee;
      margin-bottom: 10px;
    }

    .welcome-text {
      text-align: center;
      color: #6c757d;
      margin-bottom: 30px;
      font-size: 14px;
    }

    .input-box input:valid {
      border-color: #28a745;
    }

    @media (max-width: 480px) {
      .wrapper {
        width: 90%;
        padding: 30px 25px;
        margin: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="logo">
      <i class="fas fa-chalkboard-teacher"></i>
    </div>
    <h1>Welcome Back</h1>
    <div class="welcome-text">
      Sign in to your account to continue
    </div>
    
    <?php if ($message): ?>
      <div class="alert"><?= $message ?></div>
    <?php endif; ?>
    
    <form method="POST">
      <div class="input-box">
        <input type="text" name="username" placeholder="Username" required>
        <i class="fas fa-user"></i>
      </div>
      
      <div class="input-box">
        <input type="password" name="password" placeholder="Password" required>
        <i class="fas fa-lock"></i>
      </div>
      
      <button type="submit" class="btn">Sign In</button>
      
      <div class="register-link">
        <p>Don't have an account? <a href="register.php">Register now</a></p>
      </div>
    </form>
  </div>

  <script>
    // Add visual feedback for valid inputs
    document.addEventListener('DOMContentLoaded', function() {
      const inputs = document.querySelectorAll('input[required]');
      
      inputs.forEach(input => {
        input.addEventListener('input', function() {
          if (this.value.trim() !== '') {
            this.style.borderColor = '#28a745';
          } else {
            this.style.borderColor = '#e9ecef';
          }
        });
      });

      // Add focus effects
      const formInputs = document.querySelectorAll('.input-box input');
      formInputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
          this.parentElement.style.transform = 'scale(1)';
        });
      });
    });
  </script>
</body>
</html>