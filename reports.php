<?php
session_start();
require 'config.php';

// Only teacher/admin can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher','admin'])) {
    header("Location: login.php");
    exit;
}

$classFilter = $_GET['class'] ?? '';
$dateFilter  = $_GET['date'] ?? '';
$subjectFilter = $_GET['subject'] ?? '';
$monthFilter = $_GET['month'] ?? '';

// Get available classes
$classesStmt = $pdo->query("SELECT DISTINCT class FROM students ORDER BY class");
$classes = $classesStmt->fetchAll(PDO::FETCH_COLUMN);

// Get available subjects
$subjectsStmt = $pdo->query("SELECT DISTINCT subject FROM attendance WHERE subject IS NOT NULL ORDER BY subject");
$subjects = $subjectsStmt->fetchAll(PDO::FETCH_COLUMN);

// Get available months for filter
$monthsStmt = $pdo->query("SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') as month FROM attendance ORDER BY month DESC");
$months = $monthsStmt->fetchAll(PDO::FETCH_COLUMN);

// Build SQL query with filters
$sql = "SELECT s.student_id, s.fullname, s.roll_no, s.class, 
               COUNT(a.attendance_id) as total_records,
               SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_count,
               SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
               SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_count,
               SUM(CASE WHEN a.status = 'Sick' THEN 1 ELSE 0 END) as sick_count
        FROM students s
        LEFT JOIN attendance a ON s.student_id = a.student_id
        WHERE 1";

$params = [];
if ($classFilter) {
    $sql .= " AND s.class = ?";
    $params[] = $classFilter;
}
if ($dateFilter) {
    $sql .= " AND a.date = ?";
    $params[] = $dateFilter;
}
if ($subjectFilter) {
    $sql .= " AND a.subject = ?";
    $params[] = $subjectFilter;
}
if ($monthFilter) {
    $sql .= " AND DATE_FORMAT(a.date, '%Y-%m') = ?";
    $params[] = $monthFilter;
}

$sql .= " GROUP BY s.student_id, s.fullname, s.roll_no, s.class ORDER BY s.class, s.roll_no";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$summary = [
    'total_students' => count($attendanceRecords),
    'total_records' => 0,
    'total_present' => 0,
    'total_absent' => 0,
    'total_late' => 0,
    'total_sick' => 0,
    'avg_percentage' => 0
];

foreach ($attendanceRecords as $record) {
    $summary['total_records'] += $record['total_records'];
    $summary['total_present'] += $record['present_count'];
    $summary['total_absent'] += $record['absent_count'];
    $summary['total_late'] += $record['late_count'];
    $summary['total_sick'] += $record['sick_count'];
    
    $total = $record['present_count'] + $record['absent_count'] + $record['late_count'];
    $percentage = $total > 0 ? ($record['present_count'] / $total) * 100 : 0;
    $summary['avg_percentage'] += $percentage;
}

$summary['avg_percentage'] = $summary['total_students'] > 0 ? 
    round($summary['avg_percentage'] / $summary['total_students'], 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

 <style> 
 body { 
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #0d0d0d, #1a1a2e);
    color: #fff;
    min-height: 100vh;
}
.card { 
    border-radius: 20px; 
    box-shadow: 0px 6px 20px rgba(0,0,0,0.15);
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}
.navbar { 
    background: rgba(255,255,255,0.12); 
    backdrop-filter: blur(6px); 
    border-radius: 12px; 
    margin-bottom: 18px; 
}
.badge-present { background-color: #28a745; }
.badge-absent { background-color: #dc3545; }
.badge-late { background-color: #ffc107; color: black; }
.badge-sick { background-color: #0dcaf0; color: black; }
.table { 
    background: rgba(255,255,255,0.1);
    border-radius: 15px;
    overflow: hidden;
}
.table th {
    background: rgba(0,0,0,0.3);
    color: #fff;
    border: none;
    padding: 15px;
}
.table td {
    border-color: rgba(255,255,255,0.1);
    padding: 12px 15px;
    vertical-align: middle;
}
.wrapper {
    max-width: 1400px;
    margin: 0 auto;
}
.export-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 10px;
    padding: 10px 20px;
    color: white;
    transition: all 0.3s ease;
}
.export-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
.stats-card {
    background: rgba(255,255,255,0.1);
    border-radius: 15px;
    padding: 20px;
    text-align: center;
    border: 1px solid rgba(255,255,255,0.2);
}
.percentage-high { color: #28a745; }
.percentage-medium { color: #ffc107; }
.percentage-low { color: #dc3545; }
.filter-section {
    background: rgba(255,255,255,0.1);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid rgba(255,255,255,0.2);
}
</style>

</head>
<body>
<nav class="navbar navbar-expand-lg container mt-3">
<div class="container-fluid">
<span class="navbar-brand fw-bold text-white"><i class="fas fa-chart-bar me-2"></i>Attendance Reports</span>
<div class="d-flex">
<a href="teacher_dashboard.php" class="btn btn-light me-2"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
<a href="attendance_marking.php" class="btn btn-outline-light me-2"><i class="fas fa-calendar-check me-1"></i>Mark Attendance</a>
<a href="logout.php" class="btn btn-light"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
</div>
</div>
</nav>

<div class="container wrapper mt-4">
<h2 class="mb-4"><i class="fas fa-file-alt me-2"></i>Attendance Report</h2>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="stats-card">
            <h4><?= $summary['total_students'] ?></h4>
            <small>Total Students</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <h4><?= $summary['total_present'] ?></h4>
            <small>Total Present</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <h4><?= $summary['total_absent'] ?></h4>
            <small>Total Absent</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <h4><?= $summary['total_late'] ?></h4>
            <small>Total Late</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <h4><?= $summary['total_sick'] ?></h4>
            <small>Total Sick</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <h4 class="<?= $summary['avg_percentage'] >= 80 ? 'percentage-high' : ($summary['avg_percentage'] >= 60 ? 'percentage-medium' : 'percentage-low') ?>">
                <?= $summary['avg_percentage'] ?>%
            </h4>
            <small>Avg Attendance</small>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="filter-section">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label class="form-label text-white">Class</label>
            <select name="class" class="form-select">
                <option value="">All Classes</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= ($classFilter == $c) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label text-white">Subject</label>
            <select name="subject" class="form-select">
                <option value="">All Subjects</option>
                <?php foreach ($subjects as $s): ?>
                <option value="<?= htmlspecialchars($s) ?>" <?= ($subjectFilter == $s) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label text-white">Date</label>
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($dateFilter) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label text-white">Month</label>
            <select name="month" class="form-select">
                <option value="">All Months</option>
                <?php foreach ($months as $m): ?>
                <option value="<?= htmlspecialchars($m) ?>" <?= ($monthFilter == $m) ? 'selected' : '' ?>>
                    <?= date('F Y', strtotime($m . '-01')) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2 w-50"><i class="fas fa-filter me-1"></i>Filter</button>
            <a href="reports.php" class="btn btn-outline-light w-50"><i class="fas fa-refresh me-1"></i>Reset</a>
        </div>
    </form>
</div>



<!-- Attendance Table -->
<div class="table-responsive">
    <table class="table table-hover" id="attendanceTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Roll No</th>
                <th>Student Name</th>
                <th>Class</th>
                <th>Total Records</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Late</th>
                <th>Sick</th>
                <th>Attendance %</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($attendanceRecords): ?>
            <?php foreach($attendanceRecords as $i => $student): ?>
            <?php 
            $total = $student['present_count'] + $student['absent_count'] + $student['late_count'];
            $percentage = $total > 0 ? round(($student['present_count'] / $total) * 100, 2) : 0;
            $statusClass = $percentage >= 80 ? 'percentage-high' : ($percentage >= 60 ? 'percentage-medium' : 'percentage-low');
            ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($student['roll_no']) ?></td>
                <td><?= htmlspecialchars($student['fullname']) ?></td>
                <td><?= htmlspecialchars($student['class']) ?></td>
                <td><?= $student['total_records'] ?></td>
                <td class="text-success"><?= $student['present_count'] ?></td>
                <td class="text-danger"><?= $student['absent_count'] ?></td>
                <td class="text-warning"><?= $student['late_count'] ?></td>
                <td class="text-info"><?= $student['sick_count'] ?></td>
                <td class="<?= $statusClass ?>"><strong><?= $percentage ?>%</strong></td>
                <td>
                    <?php if ($percentage >= 80): ?>
                        <span class="badge bg-success">Excellent</span>
                    <?php elseif ($percentage >= 60): ?>
                        <span class="badge bg-warning">Good</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Needs Improvement</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary view-student-details" 
                            data-student-id="<?= $student['student_id'] ?>" 
                            data-student-name="<?= htmlspecialchars($student['fullname']) ?>">
                        <i class="fas fa-chart-line me-1"></i>Details
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="12" class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No attendance records found for the selected filters.</p>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Graph Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content" style="background: rgba(13, 13, 13, 0.95); border: 2px solid rgba(255, 255, 255, .2); backdrop-filter: blur(20px);">
      <div class="modal-header border-bottom-0">
        <h5 class="modal-title text-white" id="attendanceModalLabel">
            <i class="fas fa-chart-line me-2"></i>Student Attendance Analysis
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="studentInfo" class="mb-4 p-3 rounded" style="background: rgba(255,255,255,0.1);"></div>
        <div class="row">
          <div class="col-md-6">
            <canvas id="attendanceChart" width="400" height="300"></canvas>
          </div>
          <div class="col-md-6">
            <canvas id="percentageChart" width="400" height="300"></canvas>
          </div>
        </div>
        <div id="attendanceStats" class="mt-4"></div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('.view-student-details');
    const modal = new bootstrap.Modal(document.getElementById('attendanceModal'));
    
    // Export to Excel
    document.getElementById('exportExcel').addEventListener('click', function() {
        const table = document.getElementById('attendanceTable');
        const wb = XLSX.utils.table_to_book(table, {sheet: "Attendance Report"});
        XLSX.writeFile(wb, "attendance_report_<?= date('Y-m-d') ?>.xlsx");
    });
    
    // Export to PDF (basic implementation)
    document.getElementById('exportPDF').addEventListener('click', function() {
        window.print();
    });
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.dataset.studentId;
            const studentName = this.dataset.studentName;
            
            // Show loading state
            document.getElementById('studentInfo').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-white mt-2">Loading student data...</p>
                </div>
            `;
            
            fetch(`get_student_attendance.php?student_id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('studentInfo').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="text-white mb-1">${studentName}</h4>
                                <p class="text-muted mb-0">Student ID: ${studentId}</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <p class="text-white mb-1">Total Records: <strong>${data.total}</strong></p>
                                <p class="text-white mb-0">Overall Attendance: <strong>${data.percentage}%</strong></p>
                            </div>
                        </div>
                    `;
                    
                    createAttendanceChart(data);
                    createPercentageChart(data);
                    
                    // Create subject-wise table
                    let subjectTable = '';
                    if (data.subject_wise && data.subject_wise.length > 0) {
                        subjectTable = `
                            <div class="mt-4">
                                <h5 class="text-white mb-3"><i class="fas fa-book me-2"></i>Subject-wise Attendance</h5>
                                <div class="table-responsive">
                                    <table class="table table-dark table-striped">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Total</th>
                                                <th>Present</th>
                                                <th>Absent</th>
                                                <th>Late</th>
                                                <th>Sick</th>
                                                <th>Percentage</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                        `;
                        
                        data.subject_wise.forEach(subject => {
                            const statusClass = subject.percentage >= 80 ? 'text-success' : 
                                              subject.percentage >= 60 ? 'text-warning' : 'text-danger';
                            const statusText = subject.percentage >= 80 ? 'Excellent' : 
                                             subject.percentage >= 60 ? 'Good' : 'Needs Improvement';
                            
                            subjectTable += `
                                <tr>
                                    <td><strong>${subject.subject}</strong></td>
                                    <td>${subject.total_records}</td>
                                    <td class="text-success">${subject.present_count}</td>
                                    <td class="text-danger">${subject.absent_count}</td>
                                    <td class="text-warning">${subject.late_count}</td>
                                    <td class="text-info">${subject.sick_count}</td>
                                    <td><strong class="${statusClass}">${subject.percentage}%</strong></td>
                                    <td><span class="badge ${statusClass.replace('text-', 'bg-')}">${statusText}</span></td>
                                </tr>
                            `;
                        });
                        
                        subjectTable += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        `;
                    }
                    
                    document.getElementById('attendanceStats').innerHTML = `
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="alert alert-success d-flex align-items-center">
                                    <i class="fas fa-check-circle fa-2x me-3"></i>
                                    <div>
                                        <h5 class="mb-0">${data.present}</h5>
                                        <small>Present</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="alert alert-danger d-flex align-items-center">
                                    <i class="fas fa-times-circle fa-2x me-3"></i>
                                    <div>
                                        <h5 class="mb-0">${data.absent}</h5>
                                        <small>Absent</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="alert alert-warning d-flex align-items-center">
                                    <i class="fas fa-clock fa-2x me-3"></i>
                                    <div>
                                        <h5 class="mb-0">${data.late}</h5>
                                        <small>Late</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="fas fa-procedures fa-2x me-3"></i>
                                    <div>
                                        <h5 class="mb-0">${data.sick}</h5>
                                        <small>Sick</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-primary text-center">
                            <h4 class="mb-0">Overall Attendance Percentage: <strong>${data.percentage}%</strong></h4>
                        </div>
                        ${subjectTable}
                    `;
                    
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('studentInfo').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading student data. Please try again.
                        </div>
                    `;
                });
        });
    });
});

function createAttendanceChart(data) {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (window.attendanceChartInstance) {
        window.attendanceChartInstance.destroy();
    }
    
    window.attendanceChartInstance = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Present', 'Absent', 'Late', 'Sick'],
            datasets: [{
                data: [data.present, data.absent, data.late, data.sick],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(13, 202, 240, 0.8)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(13, 202, 240, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Attendance Distribution',
                    color: '#fff',
                    font: { size: 16 }
                },
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#fff',
                        padding: 20
                    }
                }
            }
        }
    });
}

function createPercentageChart(data) {
    const ctx = document.getElementById('percentageChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (window.percentageChartInstance) {
        window.percentageChartInstance.destroy();
    }
    
    window.percentageChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Attendance Rate'],
            datasets: [{
                label: 'Attendance %',
                data: [data.percentage],
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 2,
                borderRadius: 10,
                barPercentage: 0.6,
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        color: '#fff',
                        callback: function(value) {
                            return value + '%';
                        }
                    },
                    grid: {
                        color: 'rgba(255,255,255,0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#fff'
                    },
                    grid: {
                        color: 'rgba(255,255,255,0.1)'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Attendance Percentage',
                    color: '#fff',
                    font: { size: 16 }
                },
                legend: {
                    display: false
                }
            }
        }
    });
}
</script>
</div>
</div>
</body>
</html>