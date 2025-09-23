<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ✅ Azure MySQL Flexible Server credentials
$host = "cloud-database-db.mysql.database.azure.com";
$user = "zameer@cloud-database-db";   // Must include @servername
$password = "ZAIDISGAY*123";          // Your real password
$dbname = "attendance_db";
$port = 3306;

// Initialize MySQLi
$conn = mysqli_init();

// Attach SSL (skip CA verification for now)
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// Connect with SSL, but do not verify certificate
if (!mysqli_real_connect(
    $conn,
    $host,
    $user,
    $password,
    $dbname,
    $port,
    NULL,
    MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT
)) {
    die(
        "❌ Connection failed!\n" .
        "Error: " . mysqli_connect_error() . "\n" .
        "Error number: " . mysqli_connect_errno() . "\n" .
        "Host: $host\n" .
        "User: $user\n" .
        "DB: $dbname\n" .
        "Port: $port\n"
    );
}

echo "✅ Connected successfully to Azure MySQL (SSL without cert verification)!";
?>
