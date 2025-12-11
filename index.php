<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isDoctor()) {
        header('Location: /pages/doctor/dashboard.php');
    } else {
        header('Location: /pages/patient/dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $users = readJSON(USERS_FILE);
        $found = false;
        
        foreach ($users as $user) {
            if ($user['email'] === $email && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                
                $found = true;
                
                if ($user['role'] === 'doctor') {
                    header('Location: /pages/doctor/dashboard.php');
                } else {
                    header('Location: /pages/patient/dashboard.php');
                }
                exit;
            }
        }
        
        if (!$found) {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCall- Login</title>
    <link rel="stylesheet" href="/assets/css/style.css">

    <link rel="icon" type="image/x-icon" href="/assets/img/red_pill.svg">

</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🏥 Medi<img src = "/assets/img/red_telephone.svg" class = "img_telephone";>all</h1>
                <p>Please sign in to continue</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>
            
            <div class="login-footer">
                <p>Don't have an account? <a href="/register.php">Register here</a></p>
            </div>
        </div>
    </div>

    
</body>
</html>
