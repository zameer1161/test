<?php
// admin_update_attendance.php
session_start();
require 'config.php';

// Ensure only admins can access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get admin info
$userId = $_SESSION['user_id'];
$adminStmt = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
$adminStmt->execute([$userId]);
$admin = $adminStmt->fetch();

// Handle form actions
$message = '';
$message_type = '';

// Update attendance status
if ($_POST['action'] ?? '' === 'update_attendance') {
    $attendance_id = $_POST['attendance_id'];
    $new_status = $_POST['status'];
    $justification = $_POST['justification'];
    $updated_by = $admin['fullname'];
    
    try {
        // Update attendance record
        $stmt = $pdo->prepare("UPDATE attendance SET status = ?, updated_by = ?, updated_at = NOW() WHERE attendance_id = ?");
        $stmt->execute([$new_status, $updated_by, $attendance_id]);
        
        // Log the justification
        $log_stmt = $pdo->prepare("INSERT INTO attendance_justifications (attendance_id, previous_status, new_status, justification, updated_by) VALUES (?, ?, ?, ?, ?)");
        
        // Get previous status for logging
        $prev_stmt = $pdo->prepare("SELECT status FROM attendance WHERE attendance_id = ?");
        $prev_stmt->execute([$attendance_id]);
        $previous_status = $prev_stmt->fetch()['status'];
        
        $log_stmt->execute([$attendance_id, $previous_status, $new_status, $justification, $updated_by]);
        
        $message = "Attendance updated successfully!";
        $message_type = "success";
        
    } catch (Exception $e) {
        $message = "Error updating attendance: " . $e->getMessage();
        $message_type = "error";
    }
}

// Get filter parameters
$date_filter = $_GET['date'] ?? date('Y-m-d');
$class_filter = $_GET['class'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query with filters
$query = "
    SELECT a.attendance_id, a.student_id, a.date, a.status, a.subject, 
           s.fullname, s.roll_no, s.class, a.updated_by, a.updated_at
    FROM attendance a 
    JOIN students s ON a.student_id = s.student_id 
    WHERE 1=1
";

$params = [];

if ($date_filter) {
    $query .= " AND a.date = ?";
    $params[] = $date_filter;
}

if ($class_filter) {
    $query .= " AND s.class = ?";
    $params[] = $class_filter;
}

if ($status_filter) {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY a.date DESC, s.class, s.roll_no";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $attendance_records = $stmt->fetchAll();
} catch (Exception $e) {
    $attendance_records = [];
    error_log("Attendance query error: " . $e->getMessage());
}

// Get unique classes for filter dropdown
try {
    $class_stmt = $pdo->query("SELECT DISTINCT class FROM students ORDER BY class");
    $classes = $class_stmt->fetchAll();
} catch (Exception $e) {
    $classes = [];
}

// Get attendance statistics for the filtered date
try {
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
            SUM(CASE WHEN status = 'Sick' THEN 1 ELSE 0 END) as sick
        FROM attendance 
        WHERE date = ?
    ");
    $stats_stmt->execute([$date_filter]);
    $stats = $stats_stmt->fetch();
} catch (Exception $e) {
    $stats = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'sick' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Attendance - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
            --light: #ecf0f1;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .table thead {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .badge-present { background-color: rgba(39, 174, 96, 0.15); color: var(--success); border: 1px solid var(--success); }
        .badge-absent { background-color: rgba(231, 76, 60, 0.15); color: var(--danger); border: 1px solid var(--danger); }
        .badge-late { background-color: rgba(243, 156, 18, 0.15); color: var(--warning); border: 1px solid var(--warning); }
        .badge-sick { background-color: rgba(52, 152, 219, 0.15); color: var(--info); border: 1px solid var(--info); }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
        }
        
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .update-btn {
            padding: 0.3rem 0.8rem;
            font-size: 0.8rem;
        }
        
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 12px 12px 0 0;
            border: none;
        }
        
        .justification-text {
            min-height: 100px;
            resize: vertical;
        }
        
        .nav-breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Navigation -->
        <nav class="nav-breadcrumb">
            <a href="admin.php" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </nav>

        <!-- Header -->
        <div class="header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-calendar-check me-2"></i>Update Student Attendance</h1>
                    <p class="text-muted mb-0">Manage and update student attendance records with proper justification</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="user-info">
                        <span class="fw-bold">Welcome, <?= htmlspecialchars($admin['fullname']) ?></span>
                        <span class="badge bg-primary ms-2">Administrator</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-2 col-sm-6">
                <div class="stats-card">
                    <div class="number text-primary"><?= $stats['total'] ?></div>
                    <div class="label">Total Records</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="stats-card">
                    <div class="number text-success"><?= $stats['present'] ?></div>
                    <div class="label">Present</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="stats-card">
                    <div class="number text-danger"><?= $stats['absent'] ?></div>
                    <div class="label">Absent</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="stats-card">
                    <div class="number text-warning"><?= $stats['late'] ?></div>
                    <div class="label">Late</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="stats-card">
                    <div class="number text-info"><?= $stats['sick'] ?></div>
                    <div class="label">Sick</div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="stats-card">
                    <div class="number" style="color: #9b59b6;"><?= $stats['total'] > 0 ? round(($stats['present'] / $stats['total']) * 100, 1) : 0 ?>%</div>
                    <div class="label">Attendance Rate</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5><i class="fas fa-filter me-2"></i>Filter Records</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date_filter) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="class" class="form-select">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= htmlspecialchars($class['class']) ?>" <?= $class_filter === $class['class'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($class['class']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Present" <?= $status_filter === 'Present' ? 'selected' : '' ?>>Present</option>
                        <option value="Absent" <?= $status_filter === 'Absent' ? 'selected' : '' ?>>Absent</option>
                        <option value="Late" <?= $status_filter === 'Late' ? 'selected' : '' ?>>Late</option>
                        <option value="Sick" <?= $status_filter === 'Sick' ? 'selected' : '' ?>>Sick</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Attendance Records Table -->
        <div class="table-container">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Attendance Records 
                    <?php if ($date_filter): ?>
                        for <?= date('F j, Y', strtotime($date_filter)) ?>
                    <?php endif; ?>
                </h5>
                <span class="badge bg-primary"><?= count($attendance_records) ?> records found</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Roll No</th>
                            <th>Subject</th>
                            <th>Current Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($attendance_records): ?>
                            <?php foreach ($attendance_records as $record): ?>
                                <tr>
                                    <td>
                                        <strong><?= date('M j, Y', strtotime($record['date'])) ?></strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-2" style="width: 32px; height: 32px; background: #3498db; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">
                                                <?= strtoupper(substr($record['fullname'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($record['fullname']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($record['class']) ?></td>
                                    <td><strong><?= htmlspecialchars($record['roll_no']) ?></strong></td>
                                    <td><?= htmlspecialchars($record['subject'] ?? 'General') ?></td>
                                    <td>
                                        <span class="badge badge-<?= strtolower($record['status']) ?>">
                                            <?= $record['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php if ($record['updated_by']): ?>
                                                By <?= htmlspecialchars($record['updated_by']) ?><br>
                                                <?= $record['updated_at'] ? date('M j, g:i A', strtotime($record['updated_at'])) : 'Never' ?>
                                            <?php else: ?>
                                                Never updated
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm update-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateModal"
                                                data-attendance-id="<?= $record['attendance_id'] ?>"
                                                data-student-name="<?= htmlspecialchars($record['fullname']) ?>"
                                                data-current-status="<?= $record['status'] ?>"
                                                data-date="<?= $record['date'] ?>"
                                                data-subject="<?= htmlspecialchars($record['subject'] ?? 'General') ?>">
                                            <i class="fas fa-edit me-1"></i>Update
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No attendance records found for the selected filters.</p>
                                    <a href="?date=<?= date('Y-m-d') ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-calendar-day me-1"></i>View Today's Records
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Update Attendance Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">
                        <i class="fas fa-edit me-2"></i>Update Attendance Status
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="updateForm">
                    <input type="hidden" name="action" value="update_attendance">
                    <input type="hidden" name="attendance_id" id="attendance_id">
                    
                    <div class="modal-body">
                        <!-- Student Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Student Information</h6>
                                <div class="student-info bg-light p-3 rounded">
                                    <strong id="studentName"></strong><br>
                                    <small class="text-muted" id="studentDetails"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Attendance Details</h6>
                                <div class="attendance-info bg-light p-3 rounded">
                                    <strong>Date: </strong><span id="attendanceDate"></span><br>
                                    <strong>Subject: </strong><span id="attendanceSubject"></span><br>
                                    <strong>Current Status: </strong><span id="currentStatus"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Status Update -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">New Status *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="">Select Status</option>
                                        <option value="Present">Present</option>
                                        <option value="Absent">Absent</option>
                                        <option value="Late">Late</option>
                                        <option value="Sick">Sick Leave</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status Legend</label>
                                    <div class="status-legend">
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge badge-present">Present</span>
                                            <span class="badge badge-absent">Absent</span>
                                            <span class="badge badge-late">Late</span>
                                            <span class="badge badge-sick">Sick Leave</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Justification -->
                        <div class="mb-3">
                            <label for="justification" class="form-label">
                                Justification / Reason for Change *
                            </label>
                            <textarea class="form-control justification-text" 
                                      id="justification" 
                                      name="justification" 
                                      placeholder="Please provide a detailed reason for updating the attendance status. This will be logged for audit purposes."
                                      required></textarea>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                This justification will be permanently recorded in the system logs.
                            </div>
                        </div>

                        <!-- Common Justifications -->
                        <div class="mb-3">
                            <label class="form-label">Common Justifications (Click to use)</label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setJustification('Medical certificate provided')">
                                    Medical Certificate
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setJustification('Family emergency verified')">
                                    Family Emergency
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setJustification('Official school activity')">
                                    School Activity
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setJustification('Transportation issue')">
                                    Transport Issue
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Attendance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle modal show event
        const updateModal = document.getElementById('updateModal');
        updateModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            
            // Extract info from data-bs-* attributes
            const attendanceId = button.getAttribute('data-attendance-id');
            const studentName = button.getAttribute('data-student-name');
            const currentStatus = button.getAttribute('data-current-status');
            const date = button.getAttribute('data-date');
            const subject = button.getAttribute('data-subject');
            const classInfo = button.closest('tr').querySelector('td:nth-child(3)').textContent;
            const rollNo = button.closest('tr').querySelector('td:nth-child(4)').textContent;
            
            // Update modal content
            document.getElementById('attendance_id').value = attendanceId;
            document.getElementById('studentName').textContent = studentName;
            document.getElementById('studentDetails').textContent = `${classInfo} | Roll No: ${rollNo}`;
            document.getElementById('attendanceDate').textContent = new Date(date).toLocaleDateString('en-US', { 
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
            });
            document.getElementById('attendanceSubject').textContent = subject;
            document.getElementById('currentStatus').textContent = currentStatus;
            
            // Reset form
            document.getElementById('status').value = '';
            document.getElementById('justification').value = '';
        });

        // Set common justification
        function setJustification(text) {
            document.getElementById('justification').value = text;
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Form validation
        document.getElementById('updateForm').addEventListener('submit', function(e) {
            const status = document.getElementById('status').value;
            const justification = document.getElementById('justification').value;
            
            if (!status || !justification.trim()) {
                e.preventDefault();
                alert('Please select a status and provide a justification.');
            }
        });
    </script>
</body>
</html>