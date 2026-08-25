<?php
require_once 'config/database.php';
$success = '';
$error = '';
$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = t('fill_all_fields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('valid_email');
    } else {
        $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
        $stmt = $conn->prepare("INSERT INTO customer_tickets (user_id, full_name, email, subject, message, status) VALUES (?, ?, ?, ?, ?, 'new')");
        $stmt->bind_param("issss", $user_id, $name, $email, $subject, $message);
        if ($stmt->execute()) {
            $success = t('ticket_submitted');
        } else {
            $error = t('ticket_submit_failed');
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width:600px;">
    <h2><?php echo t('contact_us'); ?></h2>
    <p><?php echo t('have_questions'); ?></p>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success animate__animated animate__fadeIn"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
        <div class="form-group">
            <label><?php echo t('your_name'); ?> <span style="color:red;">*</span></label>
            <input type="text" name="name" required value="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['full_name'] ?? '') : ''; ?>">
        </div>
        <div class="form-group">
            <label><?php echo t('your_email'); ?> <span style="color:red;">*</span></label>
            <input type="email" name="email" required value="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['email'] ?? '') : ''; ?>">
        </div>
        <div class="form-group">
            <label><?php echo t('subject'); ?> <span style="color:red;">*</span></label>
            <input type="text" name="subject" required placeholder="<?php echo t('enter_subject'); ?>">
        </div>
        <div class="form-group">
            <label><?php echo t('your_message'); ?> <span style="color:red;">*</span></label>
            <textarea name="message" rows="4" required placeholder="<?php echo t('describe_issue'); ?>"></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; font-size:1.1rem;">
            <i class="fas fa-paper-plane"></i> <?php echo t('send_message'); ?>
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>