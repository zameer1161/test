<?php
session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user info from DB
$stmt = $pdo->prepare("SELECT fullname, username, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch attendance records for analytics
$attendance_stmt = $pdo->prepare("
    SELECT date, status, subject 
    FROM attendance 
    WHERE student_id = ? 
    ORDER BY date ASC
");
$attendance_stmt->execute([$_SESSION['user_id']]);
$attendance_records = $attendance_stmt->fetchAll();

// Calculate overall statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late
    FROM attendance 
    WHERE student_id = ?
");
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetch();

$attendance_rate = $stats['total'] > 0 ? round(($stats['present'] / $stats['total']) * 100, 1) : 0;
$absent_rate = $stats['total'] > 0 ? round(($stats['absent'] / $stats['total']) * 100, 1) : 0;
$late_rate = $stats['total'] > 0 ? round(($stats['late'] / $stats['total']) * 100, 1) : 0;

// Calculate monthly attendance
$monthly_data = [];
$current_year = date('Y');
for ($i = 1; $i <= 12; $i++) {
    $monthly_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present
        FROM attendance 
        WHERE student_id = ? 
        AND YEAR(date) = ? 
        AND MONTH(date) = ?
    ");
    $monthly_stmt->execute([$_SESSION['user_id'], $current_year, $i]);
    $month_stats = $monthly_stmt->fetch();
    
    $monthly_rate = $month_stats['total'] > 0 ? round(($month_stats['present'] / $month_stats['total']) * 100, 0) : 0;
    $monthly_data[] = [
        'month' => date('M', mktime(0, 0, 0, $i, 1)),
        'rate' => $monthly_rate
    ];
}

// Calculate subject-wise attendance
$subject_stmt = $pdo->prepare("
    SELECT 
        subject,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late
    FROM attendance 
    WHERE student_id = ?
    GROUP BY subject
");
$subject_stmt->execute([$_SESSION['user_id']]);
$subject_data = $subject_stmt->fetchAll();

// Calculate daily patterns (day of week) — fixed for ONLY_FULL_GROUP_BY
$daily_stmt = $pdo->prepare("
    SELECT 
        WEEKDAY(date) AS day_index,
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present
    FROM attendance
    WHERE student_id = ?
    GROUP BY day_index
    ORDER BY day_index
");
$daily_stmt->execute([$_SESSION['user_id']]);
$daily_data_raw = $daily_stmt->fetchAll();

// Convert day_index to names
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$daily_data = [];
foreach ($daily_data_raw as $row) {
    $daily_data[] = [
        'day' => $days[$row['day_index']],
        'total' => $row['total'],
        'present' => $row['present']
    ];
}

// Prepare data for charts (JSON format)
$monthly_chart_data = json_encode(array_column($monthly_data, 'rate'));
$monthly_labels = json_encode(array_column($monthly_data, 'month'));

$subject_labels = json_encode(array_column($subject_data, 'subject'));
$subject_present = json_encode(array_column($subject_data, 'present'));
$subject_absent = json_encode(array_column($subject_data, 'absent'));

$daily_labels = json_encode(array_column($daily_data, 'day'));
$daily_rates = [];
foreach ($daily_data as $day) {
    $daily_rates[] = $day['total'] > 0 ? round(($day['present'] / $day['total']) * 100, 0) : 0;
}
$daily_rates = json_encode($daily_rates);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance Analytics - Student Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* Your existing CSS goes here */
</style>
</head>
<body>
<!-- Navbar and dashboard HTML goes here — same as your code -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Chart JS scripts — same as your code, using $monthly_chart_data, $daily_rates, $subject_present, $subject_absent
</script>
</body>
</html>
