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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    $prc_number = trim($_POST['prc_number'] ?? '');
    
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!in_array($role, ['doctor', 'patient'])) {
        $error = 'Invalid role selected';
    } else {
        $users = readJSON(USERS_FILE);
        
        // Check if email already exists
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                $error = 'Email already registered';
                break;
            }
        }
        
        if (empty($error)) {
            $newUser = [
                'id' => uniqid(),
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'phone' => $phone,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($role === 'doctor') {
                $newUser['specialty'] = $specialty;
            }
            
            $users[] = $newUser;
            writeJSON(USERS_FILE, $users);
            
            $success = 'Registration successful! You can now login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Clinic </title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card register-card">
            <div class="login-header">
                <h1>🏥 Create Account</h1>
                <p>Register as a doctor or patient</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form" id="registerForm">
                <div class="form-group">
                    <label for="role">Register as</label>
                    <select id="role" name="role" required onchange="toggleSpecialty()">
                        <option value="">Select Role</option>
                        <option value="patient">Patient</option>
                        <option value="doctor">Doctor</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone">
                </div>

                <div class="form-group" id="specialtyGroup" style="display: none;">
                    <label for="prc_number">PRC Number</label>
                    <input type="tel" id="prc_number" name="prc_number">
                </div>
                
                <div class="form-group" id="specialtyGroup" style="display: none;">
                    <label for="specialty">Specialty</label>
                    <input type="text" id="specialty" name="specialty" placeholder="e.g., Cardiology, Pediatrics">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            
            <div class="login-footer">
                <p>Already have an account? <a href="/index.php">Login here</a></p>
            </div>
        </div>
    </div>
    
    <script>
        function toggleSpecialty() {
            const role = document.getElementById('role').value;
            const specialtyGroup = document.getElementById('specialtyGroup');
            if (role === 'doctor') {
                specialtyGroup.style.display = 'block';
            } else {
                specialtyGroup.style.display = 'none';
            }
        }
    </script>
</body>
</html>
