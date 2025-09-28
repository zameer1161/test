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
    $month = str_pad($i, 2, '0', STR_PAD_LEFT);
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

// Calculate daily patterns (day of week)
$daily_stmt = $pdo->prepare("
    SELECT 
        DAYNAME(date) as day,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present
    FROM attendance 
    WHERE student_id = ?
    GROUP BY DAYNAME(date)
    ORDER BY FIELD(DAYNAME(date), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
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
      max-width: 1400px;
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
    
    .analytics-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      padding: 25px;
      margin-bottom: 25px;
      transition: transform 0.3s ease;
      height: 100%;
    }
    
    .analytics-card:hover {
      transform: translateY(-3px);
    }
    
    .stats-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      padding: 20px;
      height: 100%;
      text-align: center;
    }
    
    .icon-circle {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin: 0 auto 15px;
    }
    
    .icon-present {
      background: rgba(46, 204, 113, 0.1);
      color: #2ecc71;
    }
    
    .icon-absent {
      background: rgba(231, 76, 60, 0.1);
      color: #e74c3c;
    }
    
    .icon-late {
      background: rgba(241, 196, 15, 0.1);
      color: #f1c40f;
    }
    
    .icon-rate {
      background: rgba(67, 97, 238, 0.1);
      color: var(--primary);
    }
    
    .attendance-rate {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--primary);
    }
    
    h2, h3, h4 {
      color: var(--dark);
      font-weight: 700;
    }
    
    .chart-container {
      position: relative;
      height: 300px;
      width: 100%;
    }
    
    .subject-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .subject-table th {
      background-color: #f1f5fd;
      padding: 12px 15px;
      text-align: left;
    }
    
    .subject-table td {
      padding: 12px 15px;
      border-bottom: 1px solid #eaeaea;
    }
    
    .progress {
      height: 10px;
      border-radius: 10px;
    }
    
    .badge-present {
      background-color: #2ecc71;
    }
    
    .badge-absent {
      background-color: #e74c3c;
    }
    
    .badge-late {
      background-color: #f39c12;
    }
    
    .trend-up {
      color: #2ecc71;
    }
    
    .trend-down {
      color: #e74c3c;
    }
    
    .tab-content {
      background: white;
      border-radius: 0 0 15px 15px;
      padding: 20px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .nav-tabs .nav-link.active {
      background: var(--primary);
      color: white;
      border: none;
    }
    
    .nav-tabs .nav-link {
      border: none;
      border-radius: 10px 10px 0 0;
      margin-right: 5px;
      color: var(--dark);
    }
    
    .filter-options {
      background: #f8f9ff;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
      .chart-container {
        height: 250px;
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
          <a href="dashboard.php" class="btn btn-light me-2"><i class="fas fa-home me-1"></i> Dashboard</a>
          <a href="student_attendance.php" class="btn btn-light me-2"><i class="fas fa-calendar-alt me-1"></i> My Attendance</a>
          <a href="logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </div>
      </div>
    </nav>

    <!-- Analytics Header -->
    <div class="container mt-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2>Attendance Analytics</h2>
          <p class="text-muted">Detailed insights into your attendance patterns</p>
        </div>
        <div class="attendance-rate"><?= $attendance_rate ?>%</div>
      </div>
      
      <!-- Overall Stats -->
      <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
          <div class="stats-card">
            <div class="icon-circle icon-present">
              <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="text-success"><?= $stats['present'] ?></h3>
            <p class="text-muted">Present</p>
            <span class="badge bg-success"><?= $attendance_rate ?>%</span>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stats-card">
            <div class="icon-circle icon-absent">
              <i class="fas fa-times-circle"></i>
            </div>
            <h3 class="text-danger"><?= $stats['absent'] ?></h3>
            <p class="text-muted">Absent</p>
            <span class="badge bg-danger"><?= $absent_rate ?>%</span>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stats-card">
            <div class="icon-circle icon-late">
              <i class="fas fa-clock"></i>
            </div>
            <h3 class="text-warning"><?= $stats['late'] ?></h3>
            <p class="text-muted">Late</p>
            <span class="badge bg-warning"><?= $late_rate ?>%</span>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stats-card">
            <div class="icon-circle icon-rate">
              <i class="fas fa-chart-line"></i>
            </div>
            <h3><?= $stats['total'] ?></h3>
            <p class="text-muted">Total Classes</p>
            <div class="progress mt-2">
              <div class="progress-bar bg-success" role="progressbar" style="width: <?= $attendance_rate ?>%" 
                   aria-valuenow="<?= $attendance_rate ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Charts and Visualizations -->
      <div class="row g-4">
        <!-- Monthly Trend -->
        <div class="col-lg-8">
          <div class="analytics-card">
            <h4><i class="fas fa-chart-line me-2"></i>Monthly Attendance Trend</h4>
            <p class="text-muted">Your attendance pattern throughout the year</p>
            <div class="chart-container">
              <canvas id="monthlyChart"></canvas>
            </div>
          </div>
        </div>
        
        <!-- Daily Pattern -->
        <div class="col-lg-4">
          <div class="analytics-card">
            <h4><i class="fas fa-calendar-day me-2"></i>Attendance by Day</h4>
            <p class="text-muted">Your attendance distribution across weekdays</p>
            <div class="chart-container">
              <canvas id="dailyChart"></canvas>
            </div>
          </div>
        </div>
        
        <!-- Subject-wise Analysis -->
        <div class="col-12">
          <div class="analytics-card">
            <h4><i class="fas fa-book me-2"></i>Subject-wise Attendance</h4>
            <p class="text-muted">Your attendance performance across different subjects</p>
            
            <ul class="nav nav-tabs" id="subjectTab" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="table-tab" data-bs-toggle="tab" data-bs-target="#table" type="button" role="tab">Table View</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="chart-tab" data-bs-toggle="tab" data-bs-target="#chart" type="button" role="tab">Chart View</button>
              </li>
            </ul>
            
            <div class="tab-content" id="subjectTabContent">
              <div class="tab-pane fade show active" id="table" role="tabpanel">
                <div class="table-responsive">
                  <table class="subject-table">
                    <thead>
                      <tr>
                        <th>Subject</th>
                        <th>Total Classes</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Attendance Rate</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($subject_data as $subject): ?>
                        <?php 
                        $subject_rate = $subject['total'] > 0 ? round(($subject['present'] / $subject['total']) * 100, 1) : 0;
                        $progress_class = $subject_rate >= 80 ? 'bg-success' : ($subject_rate >= 60 ? 'bg-warning' : 'bg-danger');
                        ?>
                        <tr>
                          <td><strong><?= htmlspecialchars($subject['subject']) ?></strong></td>
                          <td><?= $subject['total'] ?></td>
                          <td><span class="badge badge-present"><?= $subject['present'] ?></span></td>
                          <td><span class="badge badge-absent"><?= $subject['absent'] ?></span></td>
                          <td><span class="badge badge-late"><?= $subject['late'] ?></span></td>
                          <td>
                            <div class="d-flex align-items-center">
                              <span class="me-2"><?= $subject_rate ?>%</span>
                              <div class="progress" style="width: 100px;">
                                <div class="progress-bar <?= $progress_class ?>" role="progressbar" style="width: <?= $subject_rate ?>%" 
                                     aria-valuenow="<?= $subject_rate ?>" aria-valuemin="0" aria-valuemax="100"></div>
                              </div>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="tab-pane fade" id="chart" role="tabpanel">
                <div class="chart-container">
                  <canvas id="subjectChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="col-lg-6">
          <div class="analytics-card">
            <h4><i class="fas fa-history me-2"></i>Recent Attendance</h4>
            <p class="text-muted">Your last 10 attendance records</p>
            <div class="table-responsive">
              <table class="subject-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $recent_records = array_slice($attendance_records, -10);
                  foreach (array_reverse($recent_records) as $record): ?>
                    <tr>
                      <td><?= date('M j, Y', strtotime($record['date'])) ?></td>
                      <td><?= htmlspecialchars($record['subject']) ?></td>
                      <td>
                        <?php 
                        $badge_class = '';
                        if ($record['status'] == 'Present') $badge_class = 'badge-present';
                        if ($record['status'] == 'Absent') $badge_class = 'badge-absent';
                        if ($record['status'] == 'Late') $badge_class = 'badge-late';
                        ?>
                        <span class="badge <?= $badge_class ?>"><?= $record['status'] ?></span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        <!-- Insights -->
        <div class="col-lg-6">
          <div class="analytics-card">
            <h4><i class="fas fa-lightbulb me-2"></i>Attendance Insights</h4>
            <p class="text-muted">Key takeaways from your attendance data</p>
            
            <?php if ($attendance_rate >= 90): ?>
              <div class="alert alert-success">
                <i class="fas fa-trophy me-2"></i>
                <strong>Excellent!</strong> Your attendance rate is outstanding. Keep up the good work!
              </div>
            <?php elseif ($attendance_rate >= 75): ?>
              <div class="alert alert-info">
                <i class="fas fa-thumbs-up me-2"></i>
                <strong>Good job!</strong> Your attendance rate is satisfactory. Try to maintain or improve it.
              </div>
            <?php else: ?>
              <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Needs improvement.</strong> Your attendance rate is below the recommended level. Try to attend more classes.
              </div>
            <?php endif; ?>
            
            <?php 
            // Find subject with lowest attendance
            $min_rate = 100;
            $worst_subject = '';
            foreach ($subject_data as $subject) {
              $rate = $subject['total'] > 0 ? round(($subject['present'] / $subject['total']) * 100, 1) : 100;
              if ($rate < $min_rate) {
                $min_rate = $rate;
                $worst_subject = $subject['subject'];
              }
            }
            
            if ($min_rate < 80 && count($subject_data) > 1): ?>
              <div class="alert alert-warning">
                <i class="fas fa-book me-2"></i>
                <strong>Focus area:</strong> Your attendance in <strong><?= $worst_subject ?></strong> is lower than other subjects.
              </div>
            <?php endif; ?>
            
            <?php 
            // Check for recent improvement/decline
            $last_month = $monthly_data[count($monthly_data)-1]['rate'];
            $prev_month = count($monthly_data) > 1 ? $monthly_data[count($monthly_data)-2]['rate'] : $last_month;
            
            if ($last_month > $prev_month): ?>
              <div class="alert alert-success">
                <i class="fas fa-chart-line me-2"></i>
                <strong>Improving!</strong> Your attendance has improved compared to last month.
              </div>
            <?php elseif ($last_month < $prev_month): ?>
              <div class="alert alert-danger">
                <i class="fas fa-arrow-down me-2"></i>
                <strong>Decline noticed:</strong> Your attendance has decreased compared to last month.
              </div>
            <?php endif; ?>
            
            <div class="mt-3">
              <h5>Recommendations</h5>
              <ul>
                <li>Try to maintain at least 80% attendance in all subjects</li>
                <li>Set reminders for classes with lower attendance</li>
                <li>Review your schedule to identify patterns in absences</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Monthly Attendance Chart
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
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: {
              callback: function(value) {
                return value + '%';
              }
            }
          }
        },
        plugins: {
          legend: {
            display: false
          }
        }
      }
    });
    
    // Daily Pattern Chart
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    const dailyChart = new Chart(dailyCtx, {
      type: 'bar',
      data: {
        labels: <?= $daily_labels ?>,
        datasets: [{
          label: 'Attendance Rate (%)',
          data: <?= $daily_rates ?>,
          backgroundColor: [
            'rgba(67, 97, 238, 0.7)',
            'rgba(67, 97, 238, 0.7)',
            'rgba(67, 97, 238, 0.7)',
            'rgba(67, 97, 238, 0.7)',
            'rgba(67, 97, 238, 0.7)',
            'rgba(200, 200, 200, 0.7)',
            'rgba(200, 200, 200, 0.7)'
          ],
          borderColor: '#4361ee',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: {
              callback: function(value) {
                return value + '%';
              }
            }
          }
        },
        plugins: {
          legend: {
            display: false
          }
        }
      }
    });
    
    // Subject-wise Chart
    const subjectCtx = document.getElementById('subjectChart').getContext('2d');
    const subjectChart = new Chart(subjectCtx, {
      type: 'bar',
      data: {
        labels: <?= $subject_labels ?>,
        datasets: [
          {
            label: 'Present',
            data: <?= $subject_present ?>,
            backgroundColor: 'rgba(46, 204, 113, 0.7)',
            borderColor: '#2ecc71',
            borderWidth: 1
          },
          {
            label: 'Absent',
            data: <?= $subject_absent ?>,
            backgroundColor: 'rgba(231, 76, 60, 0.7)',
            borderColor: '#e74c3c',
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  </script>
</body>
</html>