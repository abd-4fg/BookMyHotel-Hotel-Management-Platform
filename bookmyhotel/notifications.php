<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $conn->query("UPDATE notifications SET is_read = TRUE WHERE user_id = $user_id");
    header('Location: notifications.php');
    exit;
}

// Mark single as read
if (isset($_GET['read']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE notifications SET is_read = TRUE WHERE id = $id AND user_id = $user_id");
    if (isset($_GET['redirect'])) {
        header('Location: ' . $_GET['redirect']);
    } else {
        header('Location: notifications.php');
    }
    exit;
}

// Get all notifications
$notifications = $conn->query("SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC");

include 'includes/header.php';
?>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:20px;">
        <h2><i class="fas fa-bell"></i> Notifications</h2>
        <div style="display:flex; gap:10px;">
            <a href="?mark_all_read=1" class="btn btn-outline btn-sm" onclick="return confirm('Mark all notifications as read?');">
                <i class="fas fa-check-double"></i> Mark All Read
            </a>
            <a href="notifications.php?refresh=1" class="btn btn-outline btn-sm">
                <i class="fas fa-sync"></i> Refresh
            </a>
        </div>
    </div>
    
    <?php if ($notifications->num_rows > 0): ?>
        <?php while ($notif = $notifications->fetch_assoc()): ?>
            <div style="background:#fff; padding:15px 20px; margin-bottom:10px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); 
                        border-left: 4px solid <?php echo $notif['is_read'] ? '#ddd' : '#1a2a6c'; ?>;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div style="flex:1;">
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <strong style="font-size:1rem;"><?php echo htmlspecialchars($notif['title']); ?></strong>
                            <?php if (!$notif['is_read']): ?>
                                <span style="background:#e74c3c; color:#fff; padding:2px 10px; border-radius:12px; font-size:0.7rem;">New</span>
                            <?php endif; ?>
                        </div>
                        <p style="margin:5px 0; color:#555;"><?php echo htmlspecialchars($notif['message']); ?></p>
                        <small style="color:#999;"><?php echo date('l, F d, Y H:i', strtotime($notif['created_at'])); ?></small>
                    </div>
                    <div style="display:flex; gap:8px; flex-shrink:0; margin-left:10px;">
                        <?php if (!$notif['is_read']): ?>
                            <a href="?read=1&id=<?php echo $notif['id']; ?><?php echo $notif['link'] ? '&redirect=' . urlencode($notif['link']) : ''; ?>" 
                               class="btn btn-outline btn-sm" title="Mark as read">
                                <i class="fas fa-check"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($notif['link']): ?>
                            <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-right"></i> View
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="background:#fff; padding:40px; text-align:center; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
            <i class="fas fa-bell-slash" style="font-size:3rem; color:#ddd;"></i>
            <h3 style="color:#888; margin-top:15px;">No Notifications</h3>
            <p style="color:#aaa;">You're all caught up!</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>