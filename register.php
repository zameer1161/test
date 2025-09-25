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

    if ($fullname === '' || $roll_no === '' || $class === '' || $username === '' || $password === '') {
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
                $insUser = $pdo->prepare("INSERT INTO users (username, password, role, created_at) VALUES (?, ?, 'student', NOW())");
                $insUser->execute([$username, $password]);
                $user_id = $pdo->lastInsertId();

                // Check duplicate roll_no
                $checkStudent = $pdo->prepare("SELECT student_id FROM students WHERE roll_no = ? LIMIT 1");
                $checkStudent->execute([$roll_no]);
                if ($checkStudent->fetch()) {
                    $pdo->rollBack();
                    $message = "❌ Roll number already registered.";
                } else {
                    // Insert student without email
                    $insStudent = $pdo->prepare("INSERT INTO students (user_id, fullname, roll_no, class, profile_photo, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $insStudent->execute([$user_id, $fullname, $roll_no, $class, $photoPath]);
                    $pdo->commit();
                    $message = "✅ Student registered successfully! <a href='login.php'>Login</a>";
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
<title>Register - Attendance Tracker</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.php">

<style>
/*body {
    margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #0d0d0d , #0d0d0d);
      color: #fff;}
.card { border-radius: 20px; box-shadow: 0px 6px 20px rgba(0,0,0,0.15); }
.btn-custom { background: #ff5e62; color: white; transition: 0.18s; border-radius: 10px; padding: 10px 20px; }
.btn-custom:hover { transform: translateY(-2px); }
.navbar { background: rgba(255,255,255,0.12); backdrop-filter: blur(6px); border-radius: 12px; margin-bottom: 18px; }*/
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg container mt-3">
<div class="container-fluid">
<span class="navbar-brand fw-bold text-white">🎓 Attendance Tracker</span>
<div class="d-flex"><a href="login.php" class="btn btn-light">Login</a></div>
</div>
</nav>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
<div class="col-md-7">
<div class="card p-5 bg-white">
<h2 class="text-center mb-4">Create Student Account</h2>

<?php if ($message): ?>
<div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" novalidate>
<div class="row">
<div class="mb-3 col-md-6">
<label class="form-label">Full Name</label>
<input type="text" name="fullname" class="form-control" required value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
</div>
<div class="mb-3 col-md-6">
<label class="form-label">Roll Number</label>
<input type="text" name="roll_no" class="form-control" required value="<?= htmlspecialchars($_POST['roll_no'] ?? '') ?>">
</div>
</div>

<div class="row">
<div class="mb-3 col-md-6">
<label class="form-label">Class</label>
<input type="text" name="class" class="form-control" required value="<?= htmlspecialchars($_POST['class'] ?? '') ?>">
</div>
<div class="mb-3 col-md-6">
<label class="form-label">Username</label>
<input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
</div>
</div>

<div class="row">
<div class="mb-3 col-md-6">
<label class="form-label">Password</label>
<input type="password" name="password" class="form-control" required>
</div>
<div class="mb-3 col-md-6">
<label class="form-label">Profile Photo (optional)</label>
<input type="file" name="profile_photo" class="form-control" accept="image/*">
</div>
</div>

<button type="submit" class="btn btn-custom w-100">Register</button>
</form>
</div>
</div>
</div>
</body>
</html>
