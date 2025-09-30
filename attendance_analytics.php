<?php
session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user info
$stmt = $pdo->prepare("SELECT fullname, username, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch attendance records
$attendance_stmt = $pdo->prepare("
    SELECT date, status, subject 
    FROM attendance 
    WHERE student_id = ? 
    ORDER BY date ASC
");
$attendance_stmt->execute([$_SESSION['user_id']]);
$attendance_records = $attendance_stmt->fetchAll();

// Overall statistics
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

// Monthly attendance
$monthly_data = [];
$current_year = date('Y');
for ($i = 1; $i <= 12; $i++) {
    $month_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present
        FROM attendance
        WHERE student_id = ? AND YEAR(date)=? AND MONTH(date)=?
    ");
    $month_stmt->execute([$_SESSION['user_id'], $current_year, $i]);
    $month_stats = $month_stmt->fetch();
    
    $monthly_rate = $month_stats['total'] > 0 ? round(($month_stats['present'] / $month_stats['total']) * 100, 0) : 0;
    $monthly_data[] = [
        'month' => date('M', mktime(0,0,0,$i,1)),
        'rate' => $monthly_rate
    ];
}

// Subject-wise statistics
$subject_stmt = $pdo->prepare("
    SELECT 
        subject,
        COUNT(*) AS total,
        SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) AS absent,
        SUM(CASE WHEN status='Late' THEN 1 ELSE 0 END) AS late
    FROM attendance
    WHERE student_id = ?
    GROUP BY subject
");
$subject_stmt->execute([$_SESSION['user_id']]);
$subject_data = $subject_stmt->fetchAll();

// Daily attendance (WEEKDAY)
$daily_stmt = $pdo->prepare("
    SELECT 
        WEEKDAY(date) AS day_index,
        COUNT(*) AS total,
        SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present
    FROM attendance
    WHERE student_id = ?
    GROUP BY day_index
    ORDER BY day_index
");
$daily_stmt->execute([$_SESSION['user_id']]);
$daily_data_raw = $daily_stmt->fetchAll();

// Convert day_index to day name
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$daily_data = [];
foreach ($daily_data_raw as $row) {
    $daily_data[] = [
        'day' => $days[$row['day_index']],
        'total' => $row['total'],
        'present' => $row['present']
    ];
}

// Prepare chart data
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
<title>Attendance Analytics</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* Keep your existing CSS here */
</style>
</head>
<body>
<div class="container mt-4">
<h2>Attendance Analytics</h2>
<p>Overall attendance rate: <?= $attendance_rate ?>%</p>

<!-- Charts -->
<canvas id="monthlyChart"></canvas>
<canvas id="dailyChart"></canvas>
<canvas id="subjectChart"></canvas>

<script>
// Monthly Chart
const monthlyChart = new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: <?= $monthly_labels ?>,
        datasets: [{
            label: 'Monthly Attendance %',
            data: <?= $monthly_chart_data ?>,
            borderColor: '#4361ee',
            fill: true,
            tension: 0.4
        }]
    },
    options: { scales: { y: { beginAtZero: true, max: 100 } } }
});

// Daily Chart
const dailyChart = new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: <?= $daily_labels ?>,
        datasets: [{
            label: 'Daily Attendance %',
            data: <?= $daily_rates ?>,
            backgroundColor: '#4361ee'
        }]
    },
    options: { scales: { y: { beginAtZero: true, max: 100 } } }
});

// Subject Chart
const subjectChart = new Chart(document.getElementById('subjectChart'), {
    type: 'bar',
    data: {
        labels: <?= $subject_labels ?>,
        datasets: [
            { label: 'Present', data: <?= $subject_present ?>, backgroundColor: '#2ecc71' },
            { label: 'Absent', data: <?= $subject_absent ?>, backgroundColor: '#e74c3c' }
        ]
    },
    options: { scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>
