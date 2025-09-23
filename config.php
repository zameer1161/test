<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Azure MySQL Flexible Server credentials
$host = "cloud-database-db.mysql.database.azure.com";
$user = "zameer@cloud-database-db";  // Must include @servername
$password = "ZAIDISGAY*123";         // Your real password
$dbname = "attendance_db";
$port = 3306;

// SSL certificate path (must be in same folder)
$ssl_ca = __DIR__ . "/DigiCertGlobalRootCA.crt.pem";

// Initialize MySQLi
$conn = mysqli_init();

// Attach SSL certificate
if (!mysqli_ssl_set($conn, NULL, NULL, $ssl_ca, NULL, NULL)) {
    die("❌ SSL setup failed: " . mysqli_connect_error() . " | Error number: " . mysqli_connect_errno());
}

// Attempt SSL connection
if (!mysqli_real_connect(
    $conn,
    $host,
    $user,
    $password,
    $dbname,
    $port,
    NULL,
    MYSQLI_CLIENT_SSL
)) {
    // Full debug info
    die(
        "❌ Connection failed!\n" .
        "Error: " . mysqli_connect_error() . "\n" .
        "Error number: " . mysqli_connect_errno() . "\n" .
        "Host: $host\n" .
        "User: $user\n" .
        "DB: $dbname\n" .
        "Port: $port\n" .
        "SSL CA file exists: " . (file_exists($ssl_ca) ? "Yes" : "No")
    );
}

echo "✅ Connected successfully to Azure MySQL!";
?>
