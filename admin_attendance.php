<?php
session_start();
require 'config.php';

// Ensure only admins can access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle bulk attendance marking
if ($_POST['action'] ?? '' === 'mark_attendance') {
    // Process attendance for multiple students
}

// Get classes for filtering
$classes = $pdo->query("SELECT DISTINCT class FROM students ORDER BY class")->fetchAll();

// Get students based on selected class
$selectedClass = $_GET['class'] ?? '';
$students = [];
if ($selectedClass) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE class = ? ORDER BY roll_no");
    $stmt->execute([$selectedClass]);
    $students = $stmt->fetchAll();
}
?>