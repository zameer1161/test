<?php
session_start();
require 'config.php'; // $pdo must be defined

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $roll_no  = trim($_POST['roll_no'] ?? '');
    $class    = trim($_POST['class'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    // profile photo upload (optional)
    $photoPath = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $newName = uniqid("student_") . "." . strtolower($ext);
        $targetFile = $uploadDir . $newName;
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetFile)) {
            $photoPath = "uploads/" . $newName;
        }
    }

    if ($fullname === '' || $roll_no === '' || $class === '' || $username === '' || $password === '' || $email === '') {
        $message = "❌ Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();

            // Check username exists
            $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $checkUser->execute([$username]);
            if ($checkUser->fetch()) {
                $pdo->rollBack();
                $message = "❌ Username already taken.";
            } else {
                // Insert into users
                $insUser = $pdo->prepare("INSERT INTO users (username, password, role, email, created_at) VALUES (?, ?, 'student', ?, NOW())");
                $insUser->execute([$username, $password, $email]);
                $user_id = $pdo->lastInsertId();

                // Check duplicate roll_no
                $checkStudent = $pdo->prepare("SELECT student_id FROM students WHERE roll_no = ? LIMIT 1");
                $checkStudent->execute([$roll_no]);
                if ($checkStudent->fetch()) {
                    $pdo->rollBack();
                    $message = "❌ Roll number already registered.";
                } else {
                    // Insert student with email
                    $insStudent = $pdo->prepare("INSERT INTO students (user_id, fullname, roll_no, class, email, profile_photo, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $insStudent->execute([$user_id, $fullname, $roll_no, $class, $email, $photoPath]);
                    $pdo->commit();
                    $message = "✅ Student registered successfully! <a href='login.php' class='text-primary'>Login here</a>";
                }
            }

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "❌ Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Attendance Tracker</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #4361ee;
    --secondary: #3a0ca3;
    --accent: #4cc9f0;
    --light: #f8f9fa;
    --dark: #212529;
    --gradient: linear-gradient(135deg, #4361ee, #4cc9f0);
}

body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: var(--dark);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.navbar-custom {
    background: white;
    border-radius: 12px;
    margin: 20px auto;
    max-width: 1400px;
    padding: 15px 25px;
    box-shadow: 0 4px 20px rgba(67, 97, 238, 0.1);
    border: 1px solid rgba(67, 97, 238, 0.1);
}

.registration-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.registration-card {
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(67, 97, 238, 0.1);
    box-shadow: 0 15px 35px rgba(67, 97, 238, 0.1);
    overflow: hidden;
    max-width: 800px;
    width: 100%;
}

.card-header {
    background: var(--gradient);
    padding: 30px;
    text-align: center;
    border-bottom: none;
}

.card-header h2 {
    margin: 0;
    color: white;
    font-weight: 700;
}

.card-body {
    padding: 40px;
}

.form-control {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    color: var(--dark);
    padding: 12px 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    background: white;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
    color: var(--dark);
}

.form-control::placeholder {
    color: #6c757d;
}

.form-label {
    color: var(--dark);
    font-weight: 600;
    margin-bottom: 8px;
}

.btn-register {
    background: var(--gradient);
    border: none;
    border-radius: 10px;
    padding: 12px;
    font-weight: 600;
    font-size: 1.1rem;
    color: white;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
    color: white;
}

.alert-custom {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    color: var(--dark);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.alert-success {
    background: rgba(40, 167, 69, 0.1);
    border-color: rgba(40, 167, 69, 0.3);
    color: #155724;
}

.alert-danger {
    background: rgba(220, 53, 69, 0.1);
    border-color: rgba(220, 53, 69, 0.3);
    color: #721c24;
}

.alert-info {
    background: rgba(13, 202, 240, 0.1);
    border-color: rgba(13, 202, 240, 0.3);
    color: #055160;
}

.file-upload {
    position: relative;
    overflow: hidden;
}

.file-upload input[type="file"] {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    cursor: pointer;
}

.file-upload-label {
    display: block;
    padding: 10px 15px;
    background: white;
    border: 2px dashed #dee2e6;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #6c757d;
}

.file-upload-label:hover {
    background: #f8f9fa;
    border-color: var(--primary);
    color: var(--primary);
}

.login-link {
    text-align: center;
    margin-top: 25px;
    color: #6c757d;
}

.login-link a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
}

.login-link a:hover {
    text-decoration: underline;
}

.input-group-icon {
    position: relative;
}

.input-group-icon .form-control {
    padding-left: 45px;
}

.input-group-icon i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary);
    z-index: 5;
}

.required-field::after {
    content: " *";
    color: #dc3545;
}

.feature-highlight {
    background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
    border-radius: 15px;
    padding: 20px;
    margin-top: 20px;
    border-left: 4px solid var(--primary);
}

.feature-highlight h6 {
    color: var(--primary);
    margin-bottom: 15px;
}

.feature-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    color: #495057;
}

.feature-item i {
    color: var(--primary);
    margin-right: 10px;
    font-size: 1rem;
}

@media (max-width: 768px) {
    .registration-card {
        margin: 20px;
    }
    
    .card-body {
        padding: 25px;
    }
    
    .card-header {
        padding: 20px;
    }
}

.navbar-brand {
    color: var(--primary) !important;
    font-weight: 700;
}

.btn-light {
    background: white;
    border: 2px solid var(--primary);
    color: var(--primary);
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-light:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-1px);
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom container">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">
            <i class="fas fa-chalkboard-teacher me-2"></i>Attendance Tracker
        </span>
        <div class="d-flex">
            <a href="login.php" class="btn btn-light">
                <i class="fas fa-sign-in-alt me-1"></i>Login
            </a>
        </div>
    </div>
</nav>

<div class="registration-container">
    <div class="registration-card">
        <div class="card-header">
            <h2><i class="fas fa-user-plus me-2"></i>Create Student Account</h2>
            <p class="mb-0 mt-2 text-white-50">Join our digital attendance system</p>
        </div>
        
        <div class="card-body">
            <?php if ($message): ?>
            <div class="alert alert-custom alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Full Name</label>
                        <div class="input-group-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" name="fullname" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" 
                                   placeholder="Enter your full name">
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Roll Number</label>
                        <div class="input-group-icon">
                            <i class="fas fa-id-card"></i>
                            <input type="text" name="roll_no" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['roll_no'] ?? '') ?>" 
                                   placeholder="Enter roll number">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Class</label>
                        <div class="input-group-icon">
                            <i class="fas fa-graduation-cap"></i>
                            <input type="text" name="class" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['class'] ?? '') ?>" 
                                   placeholder="e.g., 10th Grade">
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Email</label>
                        <div class="input-group-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                   placeholder="Enter your email">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Username</label>
                        <div class="input-group-icon">
                            <i class="fas fa-at"></i>
                            <input type="text" name="username" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                                   placeholder="Choose a username">
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Password</label>
                        <div class="input-group-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="form-control" required 
                                   placeholder="Create a password">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Profile Photo (Optional)</label>
                        <div class="file-upload">
                            <input type="file" name="profile_photo" class="form-control" accept="image/*" id="profilePhoto">
                            <label for="profilePhoto" class="file-upload-label">
                                <i class="fas fa-camera me-2"></i>
                                <span id="fileLabel">Choose profile photo</span>
                            </label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-register w-100">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>

            <div class="feature-highlight">
                <h6><i class="fas fa-shield-alt me-2"></i>Your information is secure</h6>
                <div class="feature-item">
                    <i class="fas fa-check"></i>
                    <span>We protect your personal data</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check"></i>
                    <span>Encrypted password storage</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check"></i>
                    <span>Secure database management</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// File upload label update
document.getElementById('profilePhoto').addEventListener('change', function(e) {
    const fileName = e.target.files[0] ? e.target.files[0].name : 'Choose profile photo';
    document.getElementById('fileLabel').textContent = fileName;
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const requiredFields = this.querySelectorAll('input[required]');
    let valid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            valid = false;
            field