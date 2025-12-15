<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectByRole();
}

$error = '';
$message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $result = loginUser($username, $password);
        
        if ($result === true) {
            redirectByRole();
        } elseif ($result === "account_pending") {
            $message = "Your account is pending approval. Please wait for staff approval.";
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Login</title>
    <link rel="stylesheet" href="http://localhost/smart-library/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2><i class="fas fa-book"></i> Library System Login</h2>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="message info"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Login</button>
            </form>
            
            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="../../landing.php">← Back to Homepage</a></p>
                <p><a href="forgot_password.php">Forgot Password?</a></p>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>Library Management System &copy; <?php echo date('Y'); ?></p>
        </div>
    </footer>
    
    <script src="http://localhost/smart-library/assets/js/scripts.js"></script>
</body>
</html>