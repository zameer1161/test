<?php
session_start();
require 'config.php';

// Only teacher/admin can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher','admin'])) {
    header("Location: login.php");
    exit;
}

// Fetch teacher/admin info
$stmt = $pdo->prepare("SELECT fullname, username, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$id = $_SESSION['user_id'];
// Fetch statistics
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_attendance = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();

// Today's attendance count
$marked_today = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date = ?");
$marked_today->execute([date('Y-m-d')]);
$today_count = $marked_today->fetchColumn();

// Recent attendance records (last 5)
$recent_stmt = $pdo->prepare("
    SELECT a.date, a.status, s.fullname, s.grade 
    FROM attendance a 
    JOIN students s ON a.student_id = s.student_id 
    ORDER BY a.date DESC
    LIMIT 5
");
$recent_stmt->execute();
$recent_attendance = $recent_stmt->fetchAll();

// Monthly statistics
$current_month = date('Y-m');
$monthly_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
    FROM attendance 
    WHERE DATE_FORMAT(date, '%Y-%m') = ?
");
$monthly_stmt->execute([$current_month]);
$monthly_stats = $monthly_stmt->fetch();

// Classes/grades available
$classes_stmt = $pdo->query("SELECT DISTINCT grade FROM students ORDER BY grade");
$classes = $classes_stmt->fetchAll();

// Notifications - check if today's attendance is incomplete
$total_students_today = $pdo->prepare("
    SELECT COUNT(DISTINCT student_id) 
    FROM attendance 
    WHERE date = ?
");
$total_students_today->execute([date('Y-m-d')]);
$marked_today_count = $total_students_today->fetchColumn();

$attendance_incomplete = $marked_today_count < $total_students;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher/Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #4361ee;
      --secondary: #3f37c9;
      --success: #4cc9f0;
      --danger: #e63946;
      --warning: #f8961e;
      --info: #4895ef;
      --light: #f8f9fa;
      --dark: #212529;
      --gradient: linear-gradient(135deg, #4361ee, #3a0ca3);
    }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
    }
    .navbar-custom {
      background: var(--gradient);
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      padding: 15px 25px;
      margin-top: 20px;
    }
    .navbar-brand { font-weight: 700; font-size: 1.5rem; color: #fff; }
    .btn-custom { background: var(--primary); color: #fff; border-radius: 10px; padding: 10px 20px; transition: all 0.3s ease; }
    .btn-custom:hover { background: var(--secondary); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1);}
    .wrapper { max-width: 1200px; margin: 20px auto; }
    .welcome-card { background: #fff; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 30px; }
    .stats-card { background: #fff; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); padding: 20px; height: 100%; text-align: center; transition: transform 0.3s ease; }
    .stats-card:hover { transform: translateY(-3px); }
    .icon-circle { width: 60px; height: 60px; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size:1.5rem; margin-bottom: 15px; }
    .icon-students { background: rgba(67,97,238,0.1); color: var(--primary); }
    .icon-attendance { background: rgba(76,201,240,0.1); color: var(--success); }
    .icon-today { background: rgba(230,57,70,0.1); color: var(--danger); }
    .icon-present { background: rgba(72,149,239,0.1); color: var(--info); }
    .icon-absent { background: rgba(248,150,30,0.1); color: var(--warning); }
    h2 { color: var(--dark); font-weight: 700; margin-bottom: 20px; }
    .notification-alert { border-left: 4px solid var(--warning); }
    .recent-table { background: #fff; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .badge-present { background-color: var(--info); }
    .badge-absent { background-color: var(--warning); }
    .badge-late { background-color: var(--danger); }
    .quick-actions { background: #fff; border-radius: 15px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .action-btn { display: flex; align-items: center; padding: 15px; border-radius: 10px; background: #f8f9fa; transition: all 0.3s ease; text-decoration: none; color: var(--dark); margin-bottom: 10px; }
    .action-btn:hover { background: var(--primary); color: white; transform: translateY(-2px); }
    .action-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; background: rgba(67,97,238,0.1); color: var(--primary); }
    .action-btn:hover .action-icon { background: rgba(255,255,255,0.2); }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-custom container">
    <div class="container-fluid">
      <span class="navbar-brand"><i class="fas fa-chalkboard-teacher me-2"></i>Attendance Tracker</span>
      <div class="d-flex">
        <a href="attendance_marking.php" class="btn btn-light me-2"><i class="fas fa-calendar-check me-1"></i>Mark Attendance</a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a href="admin_panel.php" class="btn btn-outline-light me-2"><i class="fas fa-cog me-1"></i>Admin Panel</a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-dark"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
      </div>
    </div>
  </nav>

  <div class="container wrapper">
    <!-- Welcome Card -->
    <div class="welcome-card">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <h2>Welcome back, <?= htmlspecialchars($user['fullname']); ?> 👋</h2>
          <p><strong>Username:</strong> <?= htmlspecialchars($user['username']); ?></p>
          <p><strong>Role:</strong> <?= htmlspecialchars(ucfirst($user['role'])); ?></p>
          <div class="mt-3 d-flex gap-3 flex-wrap">
            <a href="attendance_marking.php" class="btn btn-custom"><i class="fas fa-calendar-check me-1"></i>Mark Attendance</a>
            <a href="reports.php" class="btn btn-outline-primary"><i class="fas fa-chart-line me-1"></i>View Reports</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <a href="manage_students.php" class="btn btn-outline-success"><i class="fas fa-users me-1"></i>Manage Students</a>
            <?php endif; ?>
          </div>
        </div>
        <div class="text-end">
          <h5><?= date('l, F j, Y') ?></h5>
          <div id="liveClock" class="h4 text-primary"></div>
        </div>
      </div>
      
      <!-- Notification Alert -->
      <?php if ($attendance_incomplete): ?>
      <div class="alert alert-warning notification-alert mt-3 d-flex align-items-center">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <div>
          <strong>Attendance Incomplete!</strong> Only <?= $marked_today_count ?> out of <?= $total_students ?> students have attendance marked for today.
          <a href="attendance_marking.php" class="alert-link ms-1">Mark attendance now</a>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
      <div class="col-md-3 col-sm-6">
        <div class="stats-card">
          <div class="icon-circle icon-students"><i class="fas fa-user-graduate"></i></div>
          <h3><?= $total_students ?></h3>
          <p>Total Students</p>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stats-card">
          <div class="icon-circle icon-attendance"><i class="fas fa-calendar-alt"></i></div>
          <h3><?= $total_attendance ?></h3>
          <p>Total Records</p>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stats-card">
          <div class="icon-circle icon-today"><i class="fas fa-calendar-day"></i></div>
          <h3><?= $today_count ?></h3>
          <p>Marked Today</p>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="stats-card">
          <div class="icon-circle icon-present"><i class="fas fa-user-check"></i></div>
          <h3><?= $monthly_stats['present_count'] ?? 0 ?></h3>
          <p>Present This Month</p>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- Quick Actions -->
      <div class="col-lg-4">
        <div class="quick-actions">
          <h4 class="mb-3">Quick Actions</h4>
          <a href="attendance_marking.php" class="action-btn">
            <div class="action-icon"><i class="fas fa-calendar-check"></i></div>
            <div>
              <h6 class="mb-0">Mark Attendance</h6>
              <small>Record today's attendance</small>
            </div>
          </a>
          <a href="reports.php" class="action-btn">
            <div class="action-icon"><i class="fas fa-chart-bar"></i></div>
            <div>
              <h6 class="mb-0">View Reports</h6>
              <small>Attendance analytics</small>
            </div>
          </a>
          <a href="students.php?teacher_id=<?= $id?>" class="action-btn">
            <div class="action-icon"><i class="fas fa-users"></i></div>
             <div>
           <h6 class="mb-0" >All Students</h6>
           <small>View all students</small>
           </div>
           </a>
          <?php if ($_SESSION['role'] === 'admin'): ?>
          <a href="manage_users.php" class="action-btn">
            <div class="action-icon"><i class="fas fa-user-cog"></i></div>
            <div>
              <h6 class="mb-0">Manage Users</h6>
              <small>Teachers & admins</small>
            </div>
          </a>
          <?php endif; ?>
        </div>
        
        <!-- Classes Summary -->
        <div class="quick-actions mt-4">
          <h4 class="mb-3">Classes</h4>
          <?php foreach ($classes as $class): ?>
          <div class="d-flex justify-content-between py-2 border-bottom">
            <span>Grade <?= htmlspecialchars($class['grade']) ?></span>
            <a href="attendance_marking.php?grade=<?= $class['grade'] ?>" class="btn btn-sm btn-outline-primary">Mark</a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="col-lg-8">
        <div class="recent-table">
          <div class="p-3 border-bottom bg-light">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Attendance</h5>
          </div>
          <div class="p-3">
            <?php if (count($recent_attendance) > 0): ?>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Grade</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recent_attendance as $record): ?>
                  <tr>
                    <td><?= date('M j, Y', strtotime($record['date'])) ?></td>
                    <td><?= htmlspecialchars($record['fullname']) ?></td>
                    <td>Grade <?= htmlspecialchars($record['grade']) ?></td>
                    <td>
                      <span class="badge badge-<?= $record['status'] ?>">
                        <?= ucfirst($record['status']) ?>
                      </span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="text-end">
              <a href="reports.php" class="btn btn-sm btn-outline-primary">View All Records</a>
            </div>
            <?php else: ?>
            <p class="text-muted text-center py-3">No attendance records found.</p>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- Monthly Summary -->
        <div class="row mt-4">
          <div class="col-md-6">
            <div class="stats-card">
              <h5>Monthly Summary</h5>
              <div class="d-flex justify-content-between mt-3">
                <div>
                  <h4 class="text-success"><?= $monthly_stats['present_count'] ?? 0 ?></h4>
                  <small>Present</small>
                </div>
                <div>
                  <h4 class="text-warning"><?= $monthly_stats['absent_count'] ?? 0 ?></h4>
                  <small>Absent</small>
                </div>
                <div>
                  <h4 class="text-primary"><?= $monthly_stats['total_records'] ?? 0 ?></h4>
                  <small>Total</small>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="stats-card">
              <h5>Quick Stats</h5>
              <div class="mt-3">
                <div class="d-flex justify-content-between py-1">
                  <span>Attendance Rate:</span>
                  <strong>
                    <?= $monthly_stats['total_records'] > 0 ? 
                         round(($monthly_stats['present_count'] / $monthly_stats['total_records']) * 100, 1) : 0 ?>%
                  </strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                  <span>Records This Month:</span>
                  <strong><?= $monthly_stats['total_records'] ?? 0 ?></strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                  <span>Days Completed:</span>
                  <strong><?= $today_count > 0 ? 'Today' : 'Not Started' ?></strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Live clock
    function updateClock() {
      const now = new Date();
      const timeString = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit',
        hour12: true 
      });
      document.getElementById('liveClock').textContent = timeString;
    }
    
    setInterval(updateClock, 1000);
    updateClock();
    
    // Add badge classes dynamically
    document.addEventListener('DOMContentLoaded', function() {
      const badges = document.querySelectorAll('.badge-present, .badge-absent, .badge-late');
      badges.forEach(badge => {
        const status = badge.classList[0].replace('badge-', '');
        badge.classList.add('badge', 'rounded-pill');
        
        if (status === 'present') {
          badge.classList.add('bg-success');
        } else if (status === 'absent') {
          badge.classList.add('bg-warning');
        } else if (status === 'late') {
          badge.classList.add('bg-danger');
        }
      });
    });
  </script>
</body>
</html>