<?php
session_start();
require 'config.php';

// Check if teacher/admin is logged in
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher','admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$student_id = $_GET['student_id'] ?? '';

if (!$student_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Student ID required']);
    exit;
}

try {
    // Get overall statistics
    $stmt = $pdo->prepare("SELECT status FROM attendance WHERE student_id = ? ORDER BY date DESC");
    $stmt->execute([$student_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate overall statistics
    $total = count($records);
    $present = 0;
    $absent = 0;
    $late = 0;
    $sick = 0;
    
    foreach ($records as $record) {
        switch ($record['status']) {
            case 'Present':
                $present++;
                break;
            case 'Absent':
                $absent++;
                break;
            case 'Late':
                $late++;
                break;
            case 'Sick':
                $sick++;
                break;
        }
    }
    
    $considered = $present + $absent + $late;
    $percentage = $considered > 0 ? round(($present / $considered) * 100, 2) : 0;
    
    // Get subject-wise statistics
    $subjectStmt = $pdo->prepare("
        SELECT subject,
               COUNT(*) as total_records,
               SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
               SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
               SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count,
               SUM(CASE WHEN status = 'Sick' THEN 1 ELSE 0 END) as sick_count
        FROM attendance 
        WHERE student_id = ? 
        GROUP BY subject 
        ORDER BY subject
    ");
    $subjectStmt->execute([$student_id]);
    $subjectStats = $subjectStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate percentages for each subject
    foreach ($subjectStats as &$subject) {
        $subjectTotal = $subject['present_count'] + $subject['absent_count'] + $subject['late_count'];
        $subject['percentage'] = $subjectTotal > 0 ? round(($subject['present_count'] / $subjectTotal) * 100, 2) : 0;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'total' => $total,
        'present' => $present,
        'absent' => $absent,
        'late' => $late,
        'sick' => $sick,
        'percentage' => $percentage,
        'subject_wise' => $subjectStats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
