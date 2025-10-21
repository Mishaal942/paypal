<?php
$host = "localhost";
$dbname = "dbb4wjkrczzdlb";
$username = "uppbmi0whibtc";
$password = "bjgew6ykgu1v";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>
