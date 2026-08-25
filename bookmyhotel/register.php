<?php
require_once 'config/database.php';
$conn = getConnection();
$error = '';
$success = '';

// Only admin can create staff accounts
$can_create_staff = isAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    
    // Only allow staff role if admin is creating
    if ($role === 'hotel_staff' && !$can_create_staff) {
        $role = 'customer';
    }
    
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = t('fill_all_fields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('valid_email');
    } elseif (strlen($password) < 6) {
        $error = t('password_min');
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = t('email_exists');
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $full_name, $email, $hashed_password, $phone, $address, $role);
            if ($stmt->execute()) {
                if ($role === 'customer') {
                    $_SESSION['user_id'] = $conn->insert_id;
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['role'] = 'customer';
                    header('Location: index.php');
                } else {
                    $success = t('staff_account_created');
                }
                exit;
            } else {
                $error = t('registration_failed');
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width:500px;">
    <h2 style="text-align:center; margin-bottom:25px;"><?php echo t('create_account'); ?></h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success animate__animated animate__fadeIn"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
        <div class="form-group">
            <label><?php echo t('full_name'); ?> <span style="color:red;">*</span></label>
            <input type="text" name="full_name" required placeholder="John Doe">
        </div>
        <div class="form-group">
            <label><?php echo t('email'); ?> <span style="color:red;">*</span></label>
            <input type="email" name="email" required placeholder="john@example.com">
        </div>
        <div class="form-group">
            <label><?php echo t('password'); ?> <span style="color:red;">*</span></label>
            <input type="password" name="password" required placeholder="<?php echo t('password_min'); ?>">
        </div>
        <div class="form-group">
            <label><?php echo t('phone'); ?></label>
            <input type="tel" name="phone" placeholder="+971 50 123 4567">
        </div>
        <div class="form-group">
            <label><?php echo t('address'); ?></label>
            <textarea name="address" rows="2" placeholder="<?php echo t('enter_address'); ?>"></textarea>
        </div>
        
        <?php if ($can_create_staff): ?>
            <div class="form-group">
                <label><?php echo t('role'); ?></label>
                <select name="role">
                    <option value="customer"><?php echo t('customer'); ?></option>
                    <option value="hotel_staff"><?php echo t('hotel_staff'); ?></option>
                    <option value="admin"><?php echo t('admin'); ?></option>
                </select>
            </div>
        <?php endif; ?>
        
        <button type="submit" class="btn btn-success" style="width:100%; padding:14px; font-size:1.1rem;">
            <i class="fas fa-user-plus"></i> <?php echo t('register'); ?>
        </button>
        <p style="text-align:center; margin-top:15px;">
            <?php echo t('already_have_account'); ?> <a href="login.php"><?php echo t('login_here'); ?></a>
        </p>
    </form>
</div>

<?php include 'includes/footer.php'; ?>