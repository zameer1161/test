<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "cloud-database-db.mysql.database.azure.com";   // your Azure server name
$dbname = "attendances_db";                          // your database
$username = "zameer";                 // Azure requires username@servername
$password = "ZAIDISGAY*123";                // your DB password

// Path to SSL certificate
$ssl_ca = __DIR__ . "/DigiCertGlobalRootG2.crt.pem";     // put DigiCertGlobalRootG2.pem in your project folder

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,       // SSL required by Azure
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

} catch (PDOException $e) {
    echo "<script>console.log('error');</script>";
}
?>
