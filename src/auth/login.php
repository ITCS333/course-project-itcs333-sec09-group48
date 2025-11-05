<?php
require_once '../common/config.php';

if(isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if(!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            if($user['role'] == 'admin') {
                header('Location: ../admin/admin.php');
            } else {
                header('Location: ../../index.php');
            }
            exit;
        } else {
            $error = "❌ Invalid email or password";
        }
    } else {
        $error = "❌ Please fill in all fields";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../common/styles.css">
</head>
<body class="body_color">
    <main>
        <section>
            <h1>Welcome Back!</h1>
            
            <?php if($error): ?>
                <div style="color: red; text-align: center; margin-bottom: 15px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <fieldset>
                    <legend>Secure Login</legend>
                    
                    <p>
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="email" required>
                    </p>

                    <p>
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" minlength="8" required>
                    </p>

                    <p>
                        <button class="button" type="submit" id="login">Log In</button>
                    </p>
                </fieldset>
            </form>
        </section>
    </main>
</body>
</html>