<?php
require_once 'config/database.php';
$conn = getConnection();
$error = '';

// If already logged in as admin or staff, redirect to their dashboard
if (isAdmin()) {
    header('Location: admin-dashboard.php');
    exit;
}
if (isHotelStaff()) {
    header('Location: staff-dashboard.php');
    exit;
}
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = t('fill_all_fields');
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ? AND role = 'customer'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                header('Location: index.php');
                exit;
            } else {
                $error = t('invalid_credentials');
            }
        } else {
            $error = t('account_not_found');
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width:450px;">
    <div style="text-align:center; margin-bottom:25px;">
        <i class="fas fa-hotel" style="font-size:3rem; color:#1a2a6c;"></i>
        <h2 style="margin-top:10px;"><?php echo t('login'); ?></h2>
        <p style="color:#888;"><?php echo t('login_to_manage'); ?></p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger animate__animated animate__fadeIn"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> <?php echo t('email'); ?></label>
            <input type="email" name="email" required placeholder="john@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label><i class="fas fa-lock"></i> <?php echo t('password'); ?></label>
            <input type="password" name="password" required placeholder="<?php echo t('enter_password'); ?>">
        </div>
        <button type="submit" class="btn btn-success" style="width:100%; padding:14px; font-size:1.1rem;">
            <i class="fas fa-sign-in-alt"></i> <?php echo t('login'); ?>
        </button>
        <p style="text-align:center; margin-top:15px;">
            <?php echo t('dont_have_account'); ?> <a href="register.php"><?php echo t('register_here'); ?></a>
        </p>
    </form>
</div>

<?php include 'includes/footer.php'; ?>