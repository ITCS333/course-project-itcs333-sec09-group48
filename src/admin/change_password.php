<?php
require_once '../common/check_auth.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
        header('Location: admin.php?error=All fields are required');
        exit;
    }
    
    if($new_password !== $confirm_password) {
        header('Location: admin.php?error=New passwords do not match');
        exit;
    }
    
    if(strlen($new_password) < 8) {
        header('Location: admin.php?error=New password must be at least 8 characters');
        exit;
    }
    
    // Verify current password
    if(!password_verify($current_password, $current_user['password'])) {
        header('Location: admin.php?error=Current password is incorrect');
        exit;
    }
    
    try {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $current_user['id']]);
        
        header('Location: admin.php?success=Password updated successfully');
        exit;
        
    } catch(PDOException $e) {
        header('Location: admin.php?error=Failed to update password');
        exit;
    }
} else {
    header('Location: admin.php');
    exit;
}
?>