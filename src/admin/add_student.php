<?php
require_once '../common/check_auth.php';

if($current_user['role'] != 'admin') {
    header('Location: ../../index.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $student_id = $_POST['student_id'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if(empty($name) || empty($student_id) || empty($email) || empty($password)) {
        die("❌ Please fill all fields");
    }
    
    try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, student_id, email, password, role) 
                              VALUES (?, ?, ?, ?, 'student')");
        $stmt->execute([$name, $student_id, $email, $hashed_password]);
        
        header('Location: admin.php?success=Student added successfully');
        exit;
        
    } catch(PDOException $e) {
        header('Location: admin.php?error=Failed to add student: ' . $e->getMessage());
        exit;
    }
} else {
    header('Location: admin.php');
    exit;
}
?>