<?php
try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'username', 'password');
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS itcs333_course");
    $pdo->exec("USE itcs333_course");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'student') DEFAULT 'student',
        student_id VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (name, email, password, role) 
                VALUES ('Admin User', 'admin@course.edu', '$hashed_password', 'admin')");
    
    echo "✅ Database and tables created successfully!";
    
} catch(PDOException $e) {
    die("❌ Database creation error: " . $e->getMessage());
}
?>