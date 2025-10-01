
<?php
$host = "cloud-database-db.mysql.database.azure.com";
$user = "zameer@cloud-database-db";  // must include @servername
$password = "ZAIDISGAY*123";
$dbname = "attendance_db";
$port = 3306;

// Path to SSL certificate required by Azure MySQL
$ssl_ca = __DIR__ . "/DigiCertGlobalRootCA.crt.pem";

// Start mysqli connection
$conn = mysqli_init();

// Tell MySQLi to use SSL
mysqli_ssl_set($conn, NULL, NULL, $ssl_ca, NULL, NULL);

// Try to connect
if (!mysqli_real_connect($conn, $host, $user, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("❌ Connection failed: " . mysqli_connect_error());
} else {
    // echo "✅ Connected successfully to Azure MySQL!";
}
?>
