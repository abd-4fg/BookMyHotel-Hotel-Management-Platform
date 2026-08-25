<?php
require_once 'config/database.php';
$conn = getConnection();
$error = '';

// If already logged in as staff or admin, redirect to staff dashboard
if (isHotelStaff() || isAdmin()) {
    header('Location: staff-dashboard.php');
    exit;
}

// If logged in as customer, logout first
if (isLoggedIn()) {
    session_destroy();
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password.';
    } else {
        // Check for staff or admin role
        $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ? AND role IN ('hotel_staff', 'admin')");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Plain text password comparison for staff/admin
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                header('Location: staff-dashboard.php');
                exit;
            } else {
                $error = 'Invalid password.';
            }
        } else {
            $error = 'Staff account not found.';
        }
    }
}

// No header include - standalone page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - BookMyHotel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body style="background: linear-gradient(135deg, #2d4373 0%, #1a2a6c 100%); min-height:100vh; display:flex; align-items:center; justify-content:center;">
    <div class="container" style="max-width:450px; margin:0 auto;">
        <div style="background:#fff; border-radius:12px; padding:40px; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="text-align:center; margin-bottom:30px;">
                <i class="fas fa-user-cog" style="font-size:3rem; color:#2d4373;"></i>
                <h2 style="margin-top:10px; color:#2d4373;">Staff Login</h2>
                <p style="color:#888;">Hotel staff management access</p>
                <div style="width:50px; height:3px; background:#2d4373; margin:10px auto;"></div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger animate__animated animate__fadeIn"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Staff Email</label>
                    <input type="email" name="email" required placeholder="staff@bookmyhotel.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" required placeholder="Enter your password">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; font-size:1.1rem; background:#2d4373;">
                    <i class="fas fa-sign-in-alt"></i> Login as Staff
                </button>
            </form>
            
            <div style="text-align:center; margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
                <a href="login.php" style="color:#666; text-decoration:none; font-size:0.9rem;">
                    <i class="fas fa-arrow-left"></i> Back to Customer Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>