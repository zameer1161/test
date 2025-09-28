<?php
session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
echo $_SESSION['user_id'];

// Fetch user info from DB
$stmt = $pdo->prepare("SELECT fullname, username, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch recent attendance records for the student
$attendance_stmt = $pdo->prepare("
    SELECT date, status, subject 
    FROM attendance 
    WHERE student_id = ? 
    ORDER BY date DESC 
    LIMIT 5
");
$attendance_stmt->execute([$_SESSION['user_id']]);
$recent_attendance = $attendance_stmt->fetchAll();

// Calculate attendance statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent
    FROM attendance 
    WHERE student_id = ?
");
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetch();

$attendance_rate = $stats['total'] > 0 ? round(($stats['present'] / $stats['total']) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard - Attendance Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
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
    
    .dashboard-container {
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .navbar-custom {
      background: var(--gradient);
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      margin-top: 20px;
      padding: 15px 25px;
    }
    
    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
    }
    
    .btn-custom {
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 10px;
      padding: 10px 20px;
      transition: all 0.3s ease;
    }
    
    .btn-custom:hover {
      background: var(--secondary);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    
    .btn-logout {
      background: #e63946;
      color: white;
      border-radius: 10px;
      padding: 10px 20px;
      transition: all 0.3s ease;
    }
    
    .btn-logout:hover {
      background: #d00000;
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    
    .welcome-card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      padding: 30px;
      margin-bottom: 30px;
      transition: transform 0.3s ease;
    }
    
    .welcome-card:hover {
      transform: translateY(-5px);
    }
    
    .stats-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      padding: 20px;
      height: 100%;
      transition: transform 0.3s ease;
    }
    
    .stats-card:hover {
      transform: translateY(-3px);
    }
    
    .attendance-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      padding: 20px;
      height: 100%;
    }
    
    .attendance-rate {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--primary);
    }
    
    .progress {
      height: 10px;
      border-radius: 10px;
    }
    
    .attendance-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .attendance-table th {
      background-color: #f1f5fd;
      padding: 12px 15px;
      text-align: left;
    }
    
    .attendance-table td {
      padding: 12px 15px;
      border-bottom: 1px solid #eaeaea;
    }
    
    .status-present {
      color: #2ecc71;
      font-weight: 600;
    }
    
    .status-absent {
      color: #e74c3c;
      font-weight: 600;
    }
    
    .icon-circle {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 15px;
    }
    
    .icon-present {
      background: rgba(46, 204, 113, 0.1);
      color: #2ecc71;
    }
    
    .icon-absent {
      background: rgba(231, 76, 60, 0.1);
      color: #e74c3c;
    }
    
    .icon-total {
      background: rgba(67, 97, 238, 0.1);
      color: var(--primary);
    }
    
    .icon-rate {
      background: rgba(76, 201, 240, 0.1);
      color: var(--success);
    }
    
    h2 {
      color: var(--dark);
      font-weight: 700;
      margin-bottom: 20px;
    }
    
    .user-info {
      background: #f8f9ff;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
    }
    
    .quick-actions {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      margin-top: 25px;
    }
    
    @media (max-width: 768px) {
      .quick-actions {
        flex-direction: column;
      }
      
      .quick-actions a {
        width: 100%;
        text-align: center;
      }
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <div class="dashboard-container">
    <nav class="navbar navbar-expand-lg navbar-custom">
      <div class="container-fluid">
        <span class="navbar-brand text-white"><i class="fas fa-graduation-cap me-2"></i>Attendance Tracker</span>
        <div class="d-flex">
          <a href="student_attendance.php" class="btn btn-light me-2"><i class="fas fa-calendar-alt me-1"></i> My Attendance</a>
          <a href="logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </div>
      </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="container mt-4">
      <!-- Welcome Card -->
      <div class="welcome-card">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h2>Welcome back, <?= htmlspecialchars($user['fullname']); ?>! <span class="wave">👋</span></h2>
            <div class="user-info">
              <p class="mb-1"><strong><i class="fas fa-user me-2"></i>Username:</strong> <?= htmlspecialchars($user['username']); ?></p>
              <p class="mb-0"><strong><i class="fas fa-user-tag me-2"></i>Role:</strong> <?= htmlspecialchars(ucfirst($user['role'])); ?></p>
            </div>
            <p class="text-muted">Here's your attendance summary for this semester.</p>
          </div>
          <div class="col-md-4 text-center">
            <div class="attendance-rate"><?= $attendance_rate ?>%</div>
            <p class="text-muted">Overall Attendance Rate</p>
            <div class="progress">
              <div class="progress-bar bg-success" role="progressbar" style="width: <?= $attendance_rate ?>%" 
                   aria-valuenow="<?= $attendance_rate ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>
        </div>
        
        <div class="quick-actions">
          <a href="student_attendance.php" class="btn btn-custom"><i class="fas fa-calendar-check me-2"></i>View Full Attendance</a>
          <a href="attendance_analytics.php" class="btn btn-outline-primary"><i class="fas fa-chart-line me-2"></i>Attendance Analytics</a>
          <a href="download_attendance.php" class="btn btn-outline-primary"><i class="fas fa-download me-2"></i>Download Report</a>
        </div>
      </div>
      
      <!-- Stats Cards -->
      <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
          <div class="stats-card">
            <div class="icon-circle icon-total">
              <i class="fas fa-calendar-day"></i>
            </div>
            <h4><?= $stats['total'] ?></h4>
            <p class="text-muted">Total Classes</p>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stats-card">
            <div class="icon-circle icon-present">
              <i class="fas fa-check-circle"></i>
            </div>
            <h4 class="text-success"><?= $stats['present'] ?></h4>
            <p class="text-muted">Present</p>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stats-card">
            <div class="icon-circle icon-absent">
              <i class="fas fa-times-circle"></i>
            </div>
            <h4 class="text-danger"><?= $stats['absent'] ?></h4>
            <p class="text-muted">Absent</p>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stats-card">
            <div class="icon-circle icon-rate">
              <i class="fas fa-chart-pie"></i>
            </div>
            <h4 class="text-primary"><?= $attendance_rate ?>%</h4>
            <p class="text-muted">Attendance Rate</p>
          </div>
        </div>
      </div>
      
      <!-- Recent Attendance and Calendar -->
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="attendance-card">
            <h4><i class="fas fa-history me-2"></i>Recent Attendance</h4>
            <?php if (count($recent_attendance) > 0): ?>
              <div class="table-responsive">
                <table class="attendance-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Subject</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($recent_attendance as $record): ?>
                      <tr>
                        <td><?= date('M j, Y', strtotime($record['date'])) ?></td>
                        <td><?= htmlspecialchars($record['subject']) ?></td>
                        <td>
                          <span class="status-<?= strtolower($record['status']) ?>">
                            <?= $record['status'] ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p class="text-muted">No attendance records found.</p>
            <?php endif; ?>
            <div class="text-end mt-3">
              <a href="student_attendance.php" class="btn btn-sm btn-custom">View All Records</a>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4">
          <div class="attendance-card">
            <h4><i class="fas fa-calendar-alt me-2"></i>This Week</h4>
            <div class="text-center py-4">
              <div class="mb-3">
                <i class="fas fa-calendar-week fa-3x text-primary"></i>
              </div>
              <h5>Weekly Overview</h5>
              <p class="text-muted">View your attendance for the current week</p>
              <a href="#" class="btn btn-custom btn-sm">View Weekly Report</a>
            </div>
          </div>
          
          <div class="attendance-card mt-4">
            <h4><i class="fas fa-bell me-2"></i>Notifications</h4>
            <div class="d-flex align-items-center mb-3">
              <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-primary"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <p class="mb-0">Your attendance is above 75%</p>
                <small class="text-muted">2 days ago</small>
              </div>
            </div>
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-success"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <p class="mb-0">You've been marked present today</p>
                <small class="text-muted">5 hours ago</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
