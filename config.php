<?php
// // config.php

// $host = "localhost";   // Database host
// $db   = "attendance_db"; // Database name
// $user = "root";        // Database username
// $pass = "";            // Database password

// try {
//     $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
//     // Set common PDO options
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//     $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
// } catch (PDOException $e) {
//     die("Database Connection Failed: " . $e->getMessage());
// }
?>

<?php
// $host = "cloud-database-db.mysql.database.azure.com";
// $user = "zameer@cloud-database-db";  // must include @servername
// $password = "ZAIDISGAY*123";
// $dbname = "attendance_db";
// $port = 3306;

// // Path to SSL certificate required by Azure MySQL
// $ssl_ca = __DIR__ . "/DigiCertGlobalRootCA.crt.pem";

// // Start mysqli connection
// $conn = mysqli_init();

// // Tell MySQLi to use SSL
// mysqli_ssl_set($conn, NULL, NULL, $ssl_ca, NULL, NULL);

// // Try to connect
// if (!mysqli_real_connect($conn, $host, $user, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
//     die("❌ Connection failed: " . mysqli_connect_error());
// } else {
//     echo "✅ Connected successfully to Azure MySQL!";
// }
?>
<?php
// =========================
// PDO Config for Azure MySQL
// =========================

// Change these according to your Azure details
$host = "cloud-database-db.mysql.database.azure.com";   // your Azure server name
$dbname = "attendances_db";                          // your database
$username = "zameer";                 // Azure requires username@servername
$password = "ZAIDISGAY*123";                // your DB password

// Path to SSL certificate
$ssl_ca = __DIR__ . "/DigiCertGlobalRootG2.crt";     // put DigiCertGlobalRootG2.pem in your project folder

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

    // echo "✅ Connected securely to Azure MySQL!";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}
?>
