<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ✅ Azure MySQL Flexible Server credentials
$host = "cloud-database-db.mysql.database.azure.com";
$user = "zameer";   // Must include @servername
$password = "ZAIDISGAY*123";          // Your real password
$dbname = "attendance_db";
$port = 3306;

// Initialize MySQLi
$con = mysqli_init();

mysqli_ssl_set($con,NULL,NULL, "{DigiCertGlobalRootG2.crt.pem}", NULL, NULL);
mysqli_real_connect($conn, "cloud-database-db.mysql.database.azure.com", "zameer", "{ZAIDISGAY*123}", "{attendance_db}", 3306, MYSQLI_CLIENT_SSL);

echo "✅ Connected successfully to Azure MySQL (SSL without cert verification)!";
?>
