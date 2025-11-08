<?php
require_once '../common/check_auth.php';

if($current_user['role'] != 'admin') {
    header('Location: ../../index.php');
    exit;
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal</title>
    <link rel="stylesheet" href="../common/styles.css">
</head>
<body class="body_color">
    <header>
        <h1>Admin Portal</h1>
        <p>Welcome, <?php echo $current_user['name']; ?> | 
           <a href="../auth/logout.php">Logout</a>
        </p>
    </header>

    <main>
        <!-- Display success/error messages -->
        <?php if($success): ?>
            <div style="color: green; text-align: center; padding: 10px; background: #e8f5e8;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div style="color: red; text-align: center; padding: 10px; background: #ffe8e8;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Password Management Section -->
        <section>
            <h2>Change Your Password</h2>
            <form action="change_password.php" method="POST">
                <fieldset>
                    <legend>Password Update</legend>
                    
                    <p>
                        <label for="current-password">Current Password:</label>
                        <input type="password" id="current-password" name="current_password" required>
                    </p>

                    <p>
                        <label for="new-password">New Password:</label>
                        <input type="password" id="new-password" name="new_password" minlength="8" required>
                    </p>

                    <p>
                        <label for="confirm-password">Confirm Password:</label>
                        <input type="password" id="confirm-password" name="confirm_password" required>
                    </p>

                    <p>
                        <button class="button" type="submit" id="change">Update Password</button>
                    </p>
                </fieldset>
            </form>
        </section>

        <!-- Student Management Section -->
        <section>
            <h2>Manage Students</h2>
            
            <!-- Add New Student Form (Collapsible) -->
            <details>
                <summary>Add New Student</summary>
                <form action="add_student.php" method="POST">
                    <fieldset>
                        <legend>New Student Information</legend>
                        
                        <p>
                            <label for="student-name">Full Name:</label>
                            <input id="student-name" name="name" type="text" required>
                        </p>

                        <p>
                            <label for="student-id">Student ID:</label>
                            <input id="student-id" name="student_id" type="text" required>
                        </p>

                        <p>
                            <label for="student-email">Email:</label>
                            <input id="student-email" name="email" type="email" required>
                        </p>

                        <p>
                            <label for="default-password">Default Password:</label>
                            <input id="default-password" name="password" type="text" value="password123" required>
                        </p>

                        <p>
                            <button type="submit" id="add">Add Student</button>
                        </p>
                    </fieldset>
                </form>
            </details>

            <!-- Registered Students List -->
            <h3>Registered Students</h3>
            <?php
            $stmt = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC");
            $students = $stmt->fetchAll();
            ?>
            
            <table id="student-table" border="2">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Student ID</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($students) > 0): ?>
                        <?php foreach($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td>
                                <button class="small-button" onclick="editStudent(<?php echo $student['id']; ?>)">Edit</button>
                                <button class="small-button" onclick="deleteStudent(<?php echo $student['id']; ?>)">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No students registered</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <script>
    function editStudent(id) {
        if(confirm('Do you want to edit this student?')) {
            window.location.href = 'update_student.php?id=' + id;
        }
    }
    
    function deleteStudent(id) {
        if(confirm('⚠️ Are you sure you want to delete this student? This action cannot be undone.')) {
            window.location.href = 'delete_student.php?id=' + id;
        }
    }
    </script>
</body>
</html>