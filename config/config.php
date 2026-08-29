<?php
// config.php
// Update these credentials based on your local or hosting environment
$db_host = 'sql100.infinityfree.com';
$db_name = 'if0_41650456_login_thikana'; // Change this to your database name
$db_user = 'if0_41650456';       // Your database username
$db_pass = 'WrZyeiySYvUhlj';           // Your database password

try {
    // Set up PDO connection with error mode set to exception
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed. Please check your credentials.");
}
?>