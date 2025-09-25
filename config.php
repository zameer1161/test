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

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    // echo "✅ Connected successfully to Azure MySQL with PDO + SSL!";
} catch (PDOException $e) {
    die("❌ PDO Connection Failed: " . $e->getMessage());
}
?>
