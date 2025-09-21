<?php
$host = "cloud-database-db.mysql.database.azure.com";
$user = "zameer";   // FIXED: Azure variable is DB_USER, not DB_USERNAME
$password = "ZAIDISGAY*123";
$dbname = "attendance_db";
$port = "3306";

// Path to CA certificate inside Azure
$cert = "/home/site/wwwroot/DigiCertGlobalRootCA.crt.pem";

// Init MySQLi
$conn = mysqli_init();

// Configure SSL with CA cert
mysqli_ssl_set($conn, NULL, NULL, $cert, NULL, NULL);

// Connect with SSL
if (!mysqli_real_connect($conn, $host, $user, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}
?>