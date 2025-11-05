<?php
session_start();

$host = 'localhost';
$dbname = 'itcs333_course';
$username = 'root'; // Change according to your setup
$password = ''; // Change according to your setup

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>