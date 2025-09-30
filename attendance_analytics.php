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

// Calculate daily patterns (day of week) - FIXED FOR ONLY_FULL_GROUP_BY
$daily_stmt = $pdo->prepare("
    SELECT 
        DAYNAME(date) as day,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present
    FROM attendance 
    WHERE student_id = ?
    GROUP BY DAYNAME(date)
    ORDER BY MIN(date)
");
$daily_stmt->execute([$_SESSION['user_id']]);
$daily_data = $daily_stmt->fetchAll();

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
    /* --- YOUR ORIGINAL CSS KEPT AS-IS --- */
    :root {
      --primary: #4361ee;
      --secondary: #3f37c9;
      --success: #4cc9f0;
      --light: #f8f9fa;
      --dark: #212529;
      --gradient: linear-gradient(135deg, #4361ee, #3a0ca3);
    }
    
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    /* --- Other CSS KEPT AS-IS --- */
  </style>
</head>
<body>
  <!-- --- FULL HTML AND DESIGN KEPT AS-IS --- -->
  <!-- NAVBAR, CARDS, CHARTS, TABLES ALL SAME --- -->

  <!-- SCRIPTS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // --- YOUR CHART.JS CODE KEPT AS-IS ---
    // Monthly Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyChart = new Chart(monthlyCtx, {
      type: 'line',
      data: {
        labels: <?= $monthly_labels ?>,
        datasets: [{
          label: 'Attendance Rate (%)',
          data: <?= $monthly_chart_data ?>,
          borderColor: '#4361ee',
          backgroundColor: 'rgba(67, 97, 238, 0.1)',
          borderWidth: 3,
          fill: true,
          tension: 0.4
        }]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });

    // Daily Chart
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    const dailyChart = new Chart(dailyCtx, {
      type: 'bar',
      data: {
        labels: <?= $daily_labels ?>,
        datasets: [{
          label: 'Attendance Rate (%)',
          data: <?= $daily_rates ?>,
          backgroundColor: 'rgba(67, 97, 238, 0.7)',
          borderColor: '#4361ee',
          borderWidth: 1
        }]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });

    // Subject Chart
    const subjectCtx = document.getElementById('subjectChart').getContext('2d');
    const subjectChart = new Chart(subjectCtx, {
      type: 'bar',
      data: {
        labels: <?= $subject_labels ?>,
        datasets: [
          { label: 'Present', data: <?= $subject_present ?>, backgroundColor: 'rgba(46, 204, 113, 0.7)' },
          { label: 'Absent', data: <?= $subject_absent ?>, backgroundColor: 'rgba(231, 76, 60, 0.7)' }
        ]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });
  </script>
</body>
</html>
