<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Database credentials
$host = "cloud-database-db.mysql.database.azure.com";
$user = "zameer@cloud-database-db";   // IMPORTANT: username@servername format
$password = "ZAIDISGAY*123"; // put your real password
$dbname = "attendance_db";
$port = 3306;

// ✅ SSL certificate path (make sure DigiCertGlobalRootCA.crt.pem is in the same folder)
$ssl_ca = __DIR__ . "/DigiCertGlobalRootCA.crt.pem";

// ✅ Initialize MySQLi
$conn = mysqli_init();

// Attach SSL cert
if (!mysqli_ssl_set($conn, NULL, NULL, $ssl_ca, NULL, NULL)) {
    die("❌ SSL setup failed: " . mysqli_error($conn));
}

// ✅ Connect with SSL
if (!mysqli_real_connect($conn, $host, $user, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("❌ Connection failed: " . mysqli_connect_error());
}

// ✅ If connected
// echo "✅ Connected successfully!";
?>
