<?php
session_start();
require 'config.php';

// Ensure only students can access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: login.php');
    exit;
}

// Get the student's record
$userId = $_SESSION['user_id'];
$studentStmt = $pdo->prepare("SELECT student_id, fullname, roll_no, class FROM students WHERE user_id = ?");
$studentStmt->execute([$userId]);
$student = $studentStmt->fetch();
if (!$student) {
    die('Student profile not found.');
}

$dateFilterStart = $_GET['start'] ?? '';
$dateFilterEnd   = $_GET['end'] ?? '';

$sql = "SELECT date, status FROM attendance WHERE student_id = ?";
$params = [$student['student_id']];
if ($dateFilterStart) { $sql .= " AND date >= ?"; $params[] = $dateFilterStart; }
if ($dateFilterEnd)   { $sql .= " AND date <= ?"; $params[] = $dateFilterEnd; }
$sql .= " ORDER BY date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$totalDays = count($records);
$present = 0; $absent = 0; $late = 0; $sick = 0;
foreach ($records as $r) {
    if ($r['status'] === 'Present') { $present++; }
    elseif ($r['status'] === 'Absent') { $absent++; }
    elseif ($r['status'] === 'Late') { $late++; }
    elseif ($r['status'] === 'Sick') { $sick++; }
}
$considered = ($present + $absent + $late);
$attendancePercent = $considered ? round(($present / $considered) * 100, 2) : 0;

// Calculate streak data
$currentStreak = 0;
$longestStreak = 0;
$tempStreak = 0;
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Sort records by date ascending for streak calculation
$streakRecords = array_reverse($records);
foreach ($streakRecords as $r) {
    if ($r['status'] === 'Present' || $r['status'] === 'Late') {
        $tempStreak++;
        if ($tempStreak > $longestStreak) {
            $longestStreak = $tempStreak;
        }
        
        // Check if this is the current streak
        if ($r['date'] == $today || $r['date'] == $yesterday) {
            $currentStreak = $tempStreak;
        }
    } else {
        $tempStreak = 0;
    }
}

// Calculate recent activity (last 7 days)
$recentActivity = [];
$last7Days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $last7Days[] = $date;
    
    // Find record for this date
    $recordForDate = null;
    foreach ($records as $r) {
        if ($r['date'] == $date) {
            $recordForDate = $r;
            break;
        }
    }
    
    $recentActivity[] = [
        'date' => $date,
        'status' => $recordForDate ? $recordForDate['status'] : 'No Record',
        'day' => date('D', strtotime($date))
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Attendance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #4361ee;
      --secondary: #3f37c9;
      --success: #4cc9f0;
      --danger: #f72585;
      --warning: #f8961e;
      --info: #4895ef;
      --light: #f8f9fa;
      --dark: #212529;
    }
    
    body {
      background-color: #f5f7fb;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #333;
    }
    
    .navbar {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .wrapper {
      background: white;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
      padding: 2rem;
      margin: 2rem 0;
      width: 100%;
    }
    
    h2 {
      color: var(--primary);
      font-weight: 700;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    
    .student-info {
      background: linear-gradient(135deg, #e9ecef, #f8f9fa);
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 2rem;
      text-align: center;
      border-left: 4px solid var(--primary);
    }
    
    .stats-card {
      border-radius: 12px;
      padding: 1.25rem;
      text-align: center;
      color: white;
      margin-bottom: 1rem;
      transition: transform 0.3s;
    }
    
    .stats-card:hover {
      transform: translateY(-5px);
    }
    
    .stats-card i {
      font-size: 1.8rem;
      margin-bottom: 0.5rem;
    }
    
    .stats-card .number {
      font-size: 1.8rem;
      font-weight: 700;
      margin: 0.5rem 0;
    }
    
    .stats-card .label {
      font-size: 0.9rem;
      opacity: 0.9;
    }
    
    .attendance-percent {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 12px;
      padding: 1.5rem;
      color: white;
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .attendance-percent .percent {
      font-size: 3rem;
      font-weight: 700;
      margin: 0;
    }
    
    .attendance-percent .label {
      font-size: 1.2rem;
      opacity: 0.9;
    }
    
    .progress {
      height: 12px;
      border-radius: 10px;
      margin-top: 1rem;
      background-color: rgba(255, 255, 255, 0.3);
    }
    
    .progress-bar {
      border-radius: 10px;
    }
    
    .recent-activity {
      margin-bottom: 2rem;
    }
    
    .day-indicator {
      display: flex;
      justify-content: space-between;
      margin-bottom: 1rem;
    }
    
    .day {
      text-align: center;
      flex: 1;
      padding: 0.5rem;
    }
    
    .day .date {
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    
    .day .status {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto;
      color: white;
      font-size: 0.8rem;
    }
    
    .status-present { background-color: var(--success); }
    .status-absent { background-color: var(--danger); }
    .status-late { background-color: var(--warning); }
    .status-sick { background-color: var(--info); }
    .status-norecord { background-color: #adb5bd; }
    
    .table-container {
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .table thead {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
    }
    
    .table th {
      border: none;
      padding: 1rem;
      font-weight: 600;
    }
    
    .table td {
      padding: 1rem;
      vertical-align: middle;
    }
    
    .status-badge {
      padding: 0.5rem 1rem;
      border-radius: 50px;
      font-weight: 600;
      font-size: 0.85rem;
    }
    
    .badge-present { background-color: rgba(76, 201, 240, 0.2); color: #0d6efd; }
    .badge-absent { background-color: rgba(247, 37, 133, 0.2); color: #dc3545; }
    .badge-late { background-color: rgba(248, 150, 30, 0.2); color: #fd7e14; }
    .badge-sick { background-color: rgba(72, 149, 239, 0.2); color: #0dcaf0; }
    
    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border: none;
      border-radius: 8px;
      padding: 0.6rem 1.5rem;
      font-weight: 600;
    }
    
    .btn-primary:hover {
      background: linear-gradient(135deg, var(--secondary), var(--primary));
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .form-control {
      border-radius: 8px;
      padding: 0.75rem;
      border: 1px solid #dee2e6;
    }
    
    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
    }
    
    .filter-section {
      background-color: #f8f9fa;
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 2rem;
    }
    
    .streak-info {
      display: flex;
      justify-content: space-between;
      margin-bottom: 2rem;
    }
    
    .streak-card {
      background: white;
      border-radius: 12px;
      padding: 1.25rem;
      text-align: center;
      flex: 1;
      margin: 0 0.5rem;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }
    
    .streak-card:first-child {
      margin-left: 0;
    }
    
    .streak-card:last-child {
      margin-right: 0;
    }
    
    .streak-card .number {
      font-size: 2rem;
      font-weight: 700;
      color: var(--primary);
      margin: 0.5rem 0;
    }
    
    .streak-card .label {
      font-size: 0.9rem;
      color: #6c757d;
    }
    
    @media (max-width: 768px) {
      .streak-info {
        flex-direction: column;
      }
      
      .streak-card {
        margin: 0.5rem 0;
      }
      
      .day-indicator {
        flex-wrap: wrap;
      }
      
      .day {
        flex: 0 0 25%;
        margin-bottom: 1rem;
      }
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg container mt-3">
    <div class="container-fluid">
      <span class="navbar-brand fw-bold text-white"><i class="fas fa-graduation-cap me-2"></i>Attendance Tracker</span>
      <div class="d-flex">
        <a href="dashboard.php" class="btn btn-light me-2"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
        <a href="logout.php" class="btn btn-light"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
      </div>
    </div>
  </nav>

  <div class="container d-flex justify-content-center align-items-start">
    <div class="wrapper">
      <h2><i class="fas fa-calendar-check me-2"></i>My Attendance</h2>
      
      <div class="student-info">
        <h4 class="mb-2"><?= htmlspecialchars($student['fullname']) ?></h4>
        <p class="mb-0">Roll No: <?= htmlspecialchars($student['roll_no']) ?> | Class: <?= htmlspecialchars($student['class']) ?></p>
      </div>
      
      <div class="filter-section">
        <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Records</h5>
        <form class="row g-3" method="GET">
          <div class="col-md-4">
            <label class="form-label">From Date</label>
            <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($dateFilterStart) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">To Date</label>
            <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($dateFilterEnd) ?>">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Apply Filter</button>
          </div>
        </form>
      </div>
      
      <div class="streak-info">
        <div class="streak-card">
          <div class="label">Current Streak</div>
          <div class="number"><?= $currentStreak ?> days</div>
          <i class="fas fa-fire text-warning"></i>
        </div>
        <div class="streak-card">
          <div class="label">Longest Streak</div>
          <div class="number"><?= $longestStreak ?> days</div>
          <i class="fas fa-trophy text-warning"></i>
        </div>
        <div class="streak-card">
          <div class="label">Total Records</div>
          <div class="number"><?= $totalDays ?></div>
          <i class="fas fa-calendar-alt text-primary"></i>
        </div>
      </div>
      
      <div class="row mb-4">
        <div class="col-md-8">
          <div class="row">
            <div class="col-md-6">
              <div class="stats-card" style="background: linear-gradient(135deg, #4cc9f0, #4895ef);">
                <i class="fas fa-check-circle"></i>
                <div class="number"><?= $present ?></div>
                <div class="label">Present</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="stats-card" style="background: linear-gradient(135deg, #f8961e, #f9c74f);">
                <i class="fas fa-clock"></i>
                <div class="number"><?= $late ?></div>
                <div class="label">Late</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="stats-card" style="background: linear-gradient(135deg, #f72585, #b5179e);">
                <i class="fas fa-times-circle"></i>
                <div class="number"><?= $absent ?></div>
                <div class="label">Absent</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="stats-card" style="background: linear-gradient(135deg, #4895ef, #4361ee);">
                <i class="fas fa-procedures"></i>
                <div class="number"><?= $sick ?></div>
                <div class="label">Sick Leave</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="attendance-percent">
            <div class="percent"><?= $attendancePercent ?>%</div>
            <div class="label">Attendance Rate</div>
            <div class="progress">
              <div class="progress-bar bg-success" role="progressbar" style="width: <?= $attendancePercent ?>%" 
                   aria-valuenow="<?= $attendancePercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small>Excluding sick leave</small>
          </div>
        </div>
      </div>
      
      <div class="recent-activity">
        <h5 class="mb-3"><i class="fas fa-chart-line me-2"></i>Recent Activity (Last 7 Days)</h5>
        <div class="day-indicator">
          <?php foreach ($recentActivity as $activity): ?>
            <div class="day">
              <div class="date"><?= $activity['day'] ?></div>
              <div class="status status-<?= strtolower(str_replace(' ', '', $activity['status'])) ?>">
                <?php 
                $icon = 'question';
                if ($activity['status'] === 'Present') $icon = 'check';
                elseif ($activity['status'] === 'Absent') $icon = 'times';
                elseif ($activity['status'] === 'Late') $icon = 'clock';
                elseif ($activity['status'] === 'Sick') $icon = 'procedures';
                ?>
                <i class="fas fa-<?= $icon ?>"></i>
              </div>
              <small class="text-muted"><?= date('m/d', strtotime($activity['date'])) ?></small>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      
      <h5 class="mb-3"><i class="fas fa-history me-2"></i>Attendance History</h5>
      <div class="table-container">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Date</th>
              <th>Day</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($records): ?>
              <?php foreach ($records as $i => $r): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($r['date']) ?></td>
                  <td><?= date('D', strtotime($r['date'])) ?></td>
                  <td>
                    <?php 
                    $statusClass = 'badge-' . strtolower($r['status']);
                    ?>
                    <span class="status-badge <?= $statusClass ?>">
                      <i class="fas fa-<?= 
                        $r['status'] === 'Present' ? 'check' : 
                        ($r['status'] === 'Absent' ? 'times' : 
                        ($r['status'] === 'Late' ? 'clock' : 'procedures')) 
                      ?> me-1"></i>
                      <?= htmlspecialchars($r['status']) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="text-center py-4">
                  <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                  <p class="text-muted">No attendance records found.</p>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>