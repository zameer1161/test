<?php
$host = "cloud-database-db.mysql.database.azure.com";
$user = "zameer";   // Azure requires username@servername
$password = "ZAIDISGAY*123";
$dbname = "attendances_db";
$port = 3306;

// Path to SSL certificate required by Azure MySQL
$ssl_ca = __DIR__ . "/DigiCertGlobalRootG2.crt.pem";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4";

    $pdo = new PDO(
        $dsn,
        $user,
        $password,
        [
            PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Throw exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Fetch as assoc array
            PDO::ATTR_EMULATE_PREPARES => false                 // Use native prepared statements
        ]
    );

    // Debug message
    // echo "✅ Connected successfully to Azure MySQL with PDO + SSL<br>";

    // Test query to confirm connection
    $stmt = $pdo->query("SELECT NOW() AS server_time");
    $row = $stmt->fetch();
    echo "⏰ Server Time: " . $row['server_time'] . "<br>";

} catch (PDOException $e) {
    echo "❌ Database connection failed<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";

    // Optional: print full stack trace (comment this out in production)
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}
?>
