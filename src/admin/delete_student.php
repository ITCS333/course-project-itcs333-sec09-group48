<?php
require_once '../common/check_auth.php';

if($current_user['role'] != 'admin') {
    header('Location: ../../index.php');
    exit;
}

$student_id = $_GET['id'] ?? 0;

if($student_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
        $stmt->execute([$student_id]);
        
        header('Location: admin.php?success=Student deleted successfully');
        exit;
        
    } catch(PDOException $e) {
        header('Location: admin.php?error=Failed to delete student');
        exit;
    }
} else {
    header('Location: admin.php');
    exit;
}
?>