<?php
require 'config.php';

try {
    echo "<h2>Adding Subject Column to Attendance Table...</h2>";
    
    // Check if subject column already exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM attendance LIKE 'subject'");
    if ($checkColumn->rowCount() == 0) {
        // Add subject column to attendance table
        $sql = "ALTER TABLE attendance ADD COLUMN subject VARCHAR(100) DEFAULT 'General' AFTER date";
        $pdo->exec($sql);
        echo "✅ Subject column added successfully to attendance table!<br>";
    } else {
        echo "✅ Subject column already exists in attendance table.<br>";
    }
    
    // Update existing records to have a default subject
    $updateSql = "UPDATE attendance SET subject = 'General' WHERE subject IS NULL OR subject = ''";
    $affectedRows = $pdo->exec($updateSql);
    echo "✅ Updated $affectedRows existing records with default subject 'General'<br>";
    
    // Create subjects table if it doesn't exist
    $createSubjectsTable = "
        CREATE TABLE IF NOT EXISTS subjects (
            subject_id INT AUTO_INCREMENT PRIMARY KEY,
            subject_name VARCHAR(100) NOT NULL,
            subject_code VARCHAR(20) UNIQUE NOT NULL,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createSubjectsTable);
    echo "✅ Subjects table created/verified<br>";
    
    // Insert default subjects if they don't exist
    $subjects = [
        ['English', 'ENG', 'English Language and Literature'],
        ['PHP', 'PHP', 'PHP Programming Language'],
        ['Web Development', 'WEB', 'Web Development Technologies'],
        ['Full Stack', 'FST', 'Full Stack Development']
    ];
    
    foreach ($subjects as $subject) {
        $checkSubject = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_code = ?");
        $checkSubject->execute([$subject[1]]);
        
        if ($checkSubject->rowCount() == 0) {
            $insertSubject = $pdo->prepare("INSERT INTO subjects (subject_name, subject_code, description) VALUES (?, ?, ?)");
            $insertSubject->execute($subject);
            echo "✅ Added subject: {$subject[0]}<br>";
        }
    }
    
    echo "<br><h3>✅ Database update completed successfully!</h3>";
    echo "<p><a href='login.php'>Go to Login Page</a> | <a href='reports.php'>View Reports</a></p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
