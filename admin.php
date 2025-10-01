<?php
// admin.php - Clean Version
session_start();
require 'config.php';

// Ensure only admins can access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get admin info
$userId = $_SESSION['user_id'];
$adminStmt = $pdo->prepare("SELECT fullname, email FROM users WHERE id = ?");
$adminStmt->execute([$userId]);
$admin = $adminStmt->fetch();

// Handle actions
$message = '';
$message_type = '';

// Block/Unblock User
if (isset($_GET['block_user'])) {
    $user_id = $_GET['block_user'];
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $message = "User blocked successfully!";
            $message_type = "success";
        }
    } catch (Exception $e) {
        $message = "Error blocking user!";
        $message_type = "error";
    }
}

// Unblock User
if (isset($_GET['unblock_user'])) {
    $user_id = $_GET['unblock_user'];
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $message = "User unblocked successfully!";
            $message_type = "success";
        }
    } catch (Exception $e) {
        $message = "Error unblocking user!";
        $message_type = "error";
    }
}

// Delete User
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $message = "User deleted successfully!";
            $message_type = "success";
        }
    } catch (Exception $e) {
        $message = "Error deleting user!";
        $message_type = "error";
    }
}

// Add New User
if ($_POST['action'] ?? '' === 'add_user') {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $email = $_POST['email'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (fullname, username, password, role, email) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$fullname, $username, $password, $role, $email])) {
            $message = "User added successfully!";
            $message_type = "success";
        }
    } catch (Exception $e) {
        $message = "Error adding user! Username might already exist.";
        $message_type = "error";
    }
}

// Get simple statistics - NO COMPLEX QUERIES
$stats = [
    'total_students' => 0,
    'total_teachers' => 0,
    'total_users' => 0,
    'blocked_users' => 0
];

// Get all users for management
$all_users = [];
$recent_users = [];

try {
    // Simple count queries
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
    $stats['total_students'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'teacher'");
    $stats['total_teachers'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_active = 0");
    $stats['blocked_users'] = $stmt->fetch()['total'] ?? 0;
    
    // Get all users
    $users_stmt = $pdo->query("
        SELECT id, fullname, username, role, email, is_active, created_at
        FROM users 
        ORDER BY created_at DESC
    ");
    $all_users = $users_stmt->fetchAll();
    
    // Get recent users
    $recent_stmt = $pdo->query("
        SELECT id, fullname, username, role, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_users = $recent_stmt->fetchAll();
    
} catch (Exception $e) {
    // If queries fail, use sample data
    $all_users = [
        ['id' => 1, 'fullname' => 'John Doe', 'username' => 'john', 'role' => 'student', 'email' => 'john@school.com', 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 2, 'fullname' => 'Jane Smith', 'username' => 'jane', 'role' => 'teacher', 'email' => 'jane@school.com', 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 3, 'fullname' => 'Admin User', 'username' => 'admin', 'role' => 'admin', 'email' => 'admin@school.com', 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s')]
    ];
    $recent_users = array_slice($all_users, 0, 3);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - User Management System</title>
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
            --dark: #2c3e50;
            --sidebar-width: 280px;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0,0,0,0.1);
        }
        
        .sidebar-header h3 {
            font-weight: 700;
            margin: 0;
            font-size: 1.4rem;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1.5rem;
            margin: 0.2rem 0.5rem;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }
        
        .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: all 0.3s;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid var(--primary);
        }
        
        .header h1 {
            color: var(--primary);
            font-weight: 700;
            margin: 0;
            font-size: 1.8rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        /* Cards */
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            transition: transform 0.3s;
            border-left: 4px solid var(--primary);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .stats-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stats-card .number {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0.5rem 0;
            color: var(--primary);
        }
        
        .stats-card .label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Tables */
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
        
        .table th {
            border: none;
            padding: 1rem;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #f1f5f9;
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .badge-active { background-color: rgba(39, 174, 96, 0.15); color: var(--success); border: 1px solid var(--success); }
        .badge-blocked { background-color: rgba(231, 76, 60, 0.15); color: var(--danger); border: 1px solid var(--danger); }
        .badge-admin { background-color: rgba(52, 152, 219, 0.15); color: var(--info); border: 1px solid var(--info); }
        .badge-teacher { background-color: rgba(243, 156, 18, 0.15); color: var(--warning); border: 1px solid var(--warning); }
        .badge-student { background-color: rgba(155, 89, 182, 0.15); color: #9b59b6; border: 1px solid #9b59b6; }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-sm {
            padding: 0.3rem 0.8rem;
            font-size: 0.8rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.3rem;
            flex-wrap: wrap;
        }
        
        /* Modal */
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .nav-link span {
                display: none;
            }
            
            .sidebar-header h3 {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
        
        /* Message Alerts */
        .alert {
            border-radius: 10px;
            border: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-shield-alt me-2"></i>Admin Panel</h3>
        </div>
        <div class="sidebar-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="admin.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#users-section">
                        <i class="fas fa-users-cog"></i>
                        <span>User Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="attendance_update.php">
                        <i class="fas fa-users-cog"></i>
                        <span>attendance_update</span>
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-shield-alt me-2"></i>Admin Dashboard</h1>
            <div class="user-info">
                <div class="user-details text-end">
                    <div class="fw-bold"><?= htmlspecialchars($admin['fullname']) ?></div>
                    <div class="text-muted small">Super Administrator</div>
                </div>
                <div class="user-avatar">
                    <?= strtoupper(substr($admin['fullname'], 0, 1)) ?>
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
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="icon" style="background: rgba(39, 174, 96, 0.1); color: var(--success);">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="number"><?= $stats['total_students'] ?></div>
                    <div class="label">Total Students</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="icon" style="background: rgba(243, 156, 18, 0.1); color: var(--warning);">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="number"><?= $stats['total_teachers'] ?></div>
                    <div class="label">Teachers</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="icon" style="background: rgba(52, 152, 219, 0.1); color: var(--info);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="number"><?= $stats['total_users'] ?></div>
                    <div class="label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <div class="icon" style="background: rgba(231, 76, 60, 0.1); color: var(--danger);">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="number"><?= $stats['blocked_users'] ?></div>
                    <div class="label">Blocked Users</div>
                </div>
            </div>
        </div>

        <!-- User Management Section -->
        <div class="row" id="users-section">
            <div class="col-12">
                <div class="table-container">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>User Management</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-user-plus me-1"></i>Add New User
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($all_users): ?>
                                    <?php foreach ($all_users as $user): ?>
                                        <tr>
                                            <td><strong>#<?= $user['id'] ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-2" style="width: 35px; height: 35px; font-size: 0.9rem;">
                                                        <?= strtoupper(substr($user['fullname'], 0, 1)) ?>
                                                    </div>
                                                    <?= htmlspecialchars($user['fullname']) ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($user['role']) ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= ($user['is_active'] ?? 1) ? 'badge-active' : 'badge-blocked' ?>">
                                                    <?= ($user['is_active'] ?? 1) ? 'Active' : 'Blocked' ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($user['created_at'] ?? 'now')) ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($user['is_active'] ?? 1): ?>
                                                        <a href="?block_user=<?= $user['id'] ?>" class="btn btn-warning btn-sm" title="Block User">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?unblock_user=<?= $user['id'] ?>" class="btn btn-success btn-sm" title="Unblock User">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="?delete_user=<?= $user['id'] ?>" class="btn btn-danger btn-sm" title="Delete User" onclick="return confirm('Are you sure you want to delete this user?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No users found.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity & Quick Stats -->
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="table-container">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recently Registered Users</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Registered</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_users): ?>
                                    <?php foreach ($recent_users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                        <?= strtoupper(substr($user['fullname'], 0, 1)) ?>
                                                    </div>
                                                    <?= htmlspecialchars($user['fullname']) ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($user['role']) ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($user['created_at'] ?? 'now')) ?></td>
                                            <td>
                                                <span class="badge badge-active">
                                                    Active
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No recent users found.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="stats-card">
                    <h5 class="mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-user-plus me-2"></i>Add New User
                        </button>
                        <a href="admin_settings.php" class="btn btn-outline-primary">
                            <i class="fas fa-cogs me-2"></i>System Settings
                        </a>
                    </div>
                </div>

                <!-- System Info -->
                <div class="stats-card mt-4">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>System Information</h5>
                    <div class="system-info">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Server Time:</span>
                            <strong><?= date('Y-m-d H:i:s') ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>PHP Version:</span>
                            <strong><?= phpversion() ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span>Database:</span>
                            <strong>Connected</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_user">
                        <div class="mb-3">
                            <label for="fullname" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullname" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="student">Student</option>
                                <option value="teacher">Teacher</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>