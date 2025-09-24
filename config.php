<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Azure MySQL Flexible Server credentials
$host = "cloud-database-db.mysql.database.azure.com";
$user = "zameer";  // Must include @servername
$password = "ZAIDISGAY*123";
$dbname = "attendance_db";
$port = 3306;

// ✅ Correct SSL CA path
$ssl_ca = __DIR__ . "/DigiCertGlobalRootG2.crt.pem";

// Initialize MySQLi
$conn = mysqli_init();

// Attach SSL certificate
mysqli_ssl_set($conn, NULL, NULL, $ssl_ca, NULL, NULL);

// Connect with SSL
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
    die(
        "❌ Connection failed!\n" .
        "Error: " . mysqli_connect_error() . "\n" .
        "Error number: " . mysqli_connect_errno() . "\n" .
        "Host: $host\n" .
        "User: $user\n" .
        "DB: $dbname\n" .
        "Port: $port\n" .
        "SSL CA exists: " . (file_exists($ssl_ca) ? "Yes" : "No")
    );
}


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    // Set common PDO options
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}


// echo "✅ Connected successfully to Azure MySQL with SSL!";
?>
