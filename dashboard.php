<?php
session_start();
require 'config.php';

// Define constants FIRST before any function calls
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
}
if (!defined('ALLOWED_TYPES')) {
    define('ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
}
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', 'uploads/profile_photos/');
}

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Function to handle profile photo upload - DEFINE THIS BEFORE USING IT
function handleProfilePhotoUpload($user_id) {
    global $pdo;
    
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Please select a valid image file.'];
    }
    
    $file = $_FILES['profile_photo'];
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size must be less than 5MB.'];
    }
    
    // Check file type
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, ALLOWED_TYPES)) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG, and GIF files are allowed.'];
    }
    
    // Generate unique filename
    $filename = 'profile_' . $user_id . '_' . time() . '.' . $fileExtension;
    $filepath = UPLOAD_DIR . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Failed to upload file. Please try again.'];
    }
    
    // Delete old profile photo if exists
    $oldPhotoStmt = $pdo->prepare("SELECT profile_photo FROM students WHERE user_id = ?");
    $oldPhotoStmt->execute([$user_id]);
    $oldPhoto = $oldPhotoStmt->fetchColumn();
    
    if ($oldPhoto && file_exists(UPLOAD_DIR . $oldPhoto)) {
        unlink(UPLOAD_DIR . $oldPhoto);
    }
    
    // Update database
    $updateStmt = $pdo->prepare("UPDATE students SET profile_photo = ? WHERE user_id = ?");
    if ($updateStmt->execute([$filename, $user_id])) {
        return ['success' => true, 'filename' => $filename];
    } else {
        // If database update fails, delete the uploaded file
        unlink($filepath);
        return ['success' => false, 'message' => 'Failed to update profile in database.'];
    }
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Handle profile photo upload - NOW THIS CAN BE CALLED SAFELY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    $uploadResult = handleProfilePhotoUpload($_SESSION['user_id']);
    if ($uploadResult['success']) {
        $_SESSION['success'] = "Profile photo updated successfully!";
    } else {
        $_SESSION['error'] = $uploadResult['message'];
    }
    header("Location: dashboard.php");
    exit;
}

// Fetch user info from DB
$stmt = $pdo->prepare("
    SELECT u.fullname, u.username, u.role, s.profile_photo, s.roll_no, s.class 
    FROM users u 
    LEFT JOIN students s ON u.id = s.user_id 
    WHERE u.id = ?
");
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

// Get default avatar based on name
function getDefaultAvatar($name) {
    $colors = ['#4361ee', '#3a0ca3', '#7209b7', '#f72585', '#4cc9f0'];
    $initial = strtoupper(substr($name, 0, 1));
    $colorIndex = crc32($name) % count($colors);
    $color = $colors[$colorIndex];
    
    return [
        'initial' => $initial,
        'color' => $color
    ];
}

$defaultAvatar = getDefaultAvatar($user['fullname']);
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
    
    /* Profile Photo Styles */
    .profile-section {
      text-align: center;
      padding: 20px;
    }
    
    .profile-photo-container {
      position: relative;
      display: inline-block;
      margin-bottom: 20px;
    }
    
    .profile-photo {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      border: 5px solid white;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }
    
    .profile-photo:hover {
      transform: scale(1.05);
    }
    
    .profile-avatar {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      font-weight: bold;
      color: white;
      border: 5px solid white;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .photo-upload-btn {
      position: absolute;
      bottom: 10px;
      right: 10px;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    }
    
    .photo-upload-btn:hover {
      background: var(--secondary);
      transform: scale(1.1);
    }
    
    .upload-modal .modal-content {
      border-radius: 15px;
      border: none;
    }
    
    .upload-preview {
      width: 200px;
      height: 200px;
      border-radius: 50%;
      object-fit: cover;
      margin: 0 auto 20px;
      display: none;
      border: 3px solid var(--primary);
    }
    
    .file-input-wrapper {
      position: relative;
      display: inline-block;
      width: 100%;
    }
    
    .file-input-wrapper input[type="file"] {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    
    .file-input-label {
      display: block;
      padding: 12px 20px;
      background: var(--primary);
      color: white;
      border-radius: 8px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .file-input-label:hover {
      background: var(--secondary);
    }
    
    @media (max-width: 768px) {
      .quick-actions {
        flex-direction: column;
      }
      
      .quick-actions a {
        width: 100%;
        text-align: center;
      }
      
      .profile-photo, .profile-avatar {
        width: 120px;
        height: 120px;
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
      
      <!-- Display Messages -->
      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="fas fa-check-circle me-2"></i>
          <?= $_SESSION['success'] ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <?= $_SESSION['error'] ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <!-- Welcome Card -->
      <div class="welcome-card">
        <div class="row align-items-center">
          <div class="col-md-2 text-center">
            <div class="profile-section">
              <div class="profile-photo-container">
                <?php if (!empty($user['profile_photo'])): ?>
                  <img src="<?= UPLOAD_DIR . $user['profile_photo'] ?>" 
                       alt="Profile Photo" 
                       class="profile-photo"
                       onerror="this.style.display='none'; document.getElementById('defaultAvatar').style.display='flex';">
                  <div id="defaultAvatar" class="profile-avatar" style="display: none; background: <?= $defaultAvatar['color'] ?>;">
                    <?= $defaultAvatar['initial'] ?>
                  </div>
                <?php else: ?>
                  <div class="profile-avatar" style="background: <?= $defaultAvatar['color'] ?>;">
                    <?= $defaultAvatar['initial'] ?>
                  </div>
                <?php endif; ?>
                <button class="photo-upload-btn" data-bs-toggle="modal" data-bs-target="#uploadModal">
                  <i class="fas fa-camera"></i>
                </button>
              </div>
              <small class="text-muted">Click camera to update photo</small>
            </div>
          </div>
          <div class="col-md-6">
            <h2>Welcome back, <?= htmlspecialchars($user['fullname']); ?>! <span class="wave">👋</span></h2>
            <div class="user-info">
              <p class="mb-1"><strong><i class="fas fa-user me-2"></i>Username:</strong> <?= htmlspecialchars($user['username']); ?></p>
              <p class="mb-1"><strong><i class="fas fa-id-card me-2"></i>Roll No:</strong> <?= htmlspecialchars($user['roll_no'] ?? 'N/A'); ?></p>
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
        </div>
      </div>
    </div>
  </div>

  <!-- Photo Upload Modal -->
  <div class="modal fade upload-modal" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="uploadModalLabel">Update Profile Photo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="photoUploadForm" method="POST" enctype="multipart/form-data">
            <div class="text-center mb-4">
              <img id="uploadPreview" class="upload-preview" alt="Preview">
            </div>
            <div class="file-input-wrapper mb-3">
              <input type="file" id="profilePhotoInput" name="profile_photo" accept="image/*" required>
              <label for="profilePhotoInput" class="file-input-label">
                <i class="fas fa-cloud-upload-alt me-2"></i>Choose Photo
              </label>
            </div>
            <div class="form-text">
              <small>Supported formats: JPG, JPEG, PNG, GIF. Max size: 5MB</small>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" form="photoUploadForm" class="btn btn-custom">
            <i class="fas fa-upload me-2"></i>Upload Photo
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Photo upload preview
    document.getElementById('profilePhotoInput').addEventListener('change', function(e) {
      const file = e.target.files[0];
      const preview = document.getElementById('uploadPreview');
      
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
          preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
      }
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
      });
    }, 5000);

    // Handle modal hidden event to reset form
    document.getElementById('uploadModal').addEventListener('hidden.bs.modal', function () {
      document.getElementById('photoUploadForm').reset();
      document.getElementById('uploadPreview').style.display = 'none';
    });
  </script>
</body>
</html>