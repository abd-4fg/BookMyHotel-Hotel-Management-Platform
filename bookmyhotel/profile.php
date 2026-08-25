<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($full_name)) {
        $error = t('fill_all_fields');
    } else {
        $update_sql = "UPDATE users SET full_name = ?, phone = ?, address = ?";
        $params = [$full_name, $phone, $address];
        $types = "sss";
        
        // Handle password change
        if (!empty($current_password) && !empty($new_password)) {
            if (password_verify($current_password, $user['password'])) {
                if ($new_password === $confirm_password && strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_sql .= ", password = ?";
                    $params[] = $hashed_password;
                    $types .= "s";
                } else {
                    $error = t('password_mismatch');
                }
            } else {
                $error = t('current_password_incorrect');
            }
        }
        
        if (empty($error)) {
            $update_sql .= " WHERE id = ?";
            $params[] = $user_id;
            $types .= "i";
            
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $success = t('profile_updated');
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $_SESSION['full_name'] = $user['full_name'];
            } else {
                $error = t('update_failed');
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width:600px;">
    <h2><?php echo t('my_profile'); ?></h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success animate__animated animate__fadeIn"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
        <div class="form-group">
            <label><?php echo t('full_name'); ?> <span style="color:red;">*</span></label>
            <input type="text" name="full_name" required value="<?php echo htmlspecialchars($user['full_name']); ?>">
        </div>
        <div class="form-group">
            <label><?php echo t('email'); ?></label>
            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            <small style="color:#888;"><?php echo t('email_cannot_change'); ?></small>
        </div>
        <div class="form-group">
            <label><?php echo t('phone'); ?></label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label><?php echo t('address'); ?></label>
            <textarea name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
        </div>
        
        <hr style="margin:25px 0; border-color:#eee;">
        <h4><?php echo t('change_password'); ?></h4>
        <p style="color:#888; font-size:0.9rem;"><?php echo t('keep_blank'); ?></p>
        
        <div class="form-group">
            <label><?php echo t('current_password'); ?></label>
            <input type="password" name="current_password">
        </div>
        <div class="form-group">
            <label><?php echo t('new_password'); ?></label>
            <input type="password" name="new_password">
        </div>
        <div class="form-group">
            <label><?php echo t('confirm_new_password'); ?></label>
            <input type="password" name="confirm_password">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; font-size:1.1rem;">
            <i class="fas fa-save"></i> <?php echo t('update'); ?>
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>