<?php
session_start();
require 'config.php'; // must define $conn (MySQLi connection)

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
        mysqli_begin_transaction($conn);
        try {
            // Check username exists
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_fetch_assoc($result)) {
                mysqli_rollback($conn);
                $message = "❌ Username already taken.";
            } else {
                // Insert into users
                $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, role, created_at) VALUES (?, ?, 'student', NOW())");
                mysqli_stmt_bind_param($stmt, "ss", $username, $password);
                mysqli_stmt_execute($stmt);
                $user_id = mysqli_insert_id($conn);

                // Check duplicate roll_no
                $stmt = mysqli_prepare($conn, "SELECT student_id FROM students WHERE roll_no = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, "s", $roll_no);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if (mysqli_fetch_assoc($result)) {
                    mysqli_rollback($conn);
                    $message = "❌ Roll number already registered.";
                } else {
                    // Insert student
                    $stmt = mysqli_prepare($conn, "INSERT INTO students (user_id, fullname, roll_no, class, profile_photo, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    mysqli_stmt_bind_param($stmt, "issss", $user_id, $fullname, $roll_no, $class, $photoPath);
                    mysqli_stmt_execute($stmt);

                    mysqli_commit($conn);
                    $message = "✅ Student registered successfully! <a href='login.php'>Login</a>";
                }
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = "❌ Error: " . $e->getMessage();
        }
    }
}
?>
