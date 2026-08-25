<?php
// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Language support
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirect_url);
    exit;
}
$lang = $_SESSION['lang'] ?? 'en';

// Include database for functions if not already included
if (!function_exists('isLoggedIn')) {
    require_once 'config/database.php';
}

// Get unread notifications count for logged in users
$unread_count = 0;
$notifications = [];
if (isLoggedIn()) {
    $conn = getConnection();
    $unread_count = getUnreadNotificationsCount($conn, $_SESSION['user_id']);
    $notifications = getUserNotifications($conn, $_SESSION['user_id'], 10);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="BookMyHotel - Book hotels across Asia and Europe. Best price guarantee.">
    <title>BookMyHotel - <?php echo t('home'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .notification-bell {
            position: relative;
            cursor: pointer;
        }
        .notification-bell .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: #fff;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.65rem;
            min-width: 18px;
            text-align: center;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .notification-dropdown {
            display: none;
            position: absolute;
            top: 35px;
            right: 0;
            background: #fff;
            min-width: 350px;
            max-width: 400px;
            max-height: 400px;
            overflow-y: auto;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 1000;
        }
        .notification-dropdown.show {
            display: block;
        }
        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }
        .notification-item:hover {
            background: #f8f9fa;
        }
        .notification-item.unread {
            background: #e8f0fe;
        }
        .notification-item .notif-title {
            font-weight: 600;
            font-size: 0.9rem;
        }
        .notification-item .notif-message {
            font-size: 0.85rem;
            color: #666;
            margin-top: 3px;
        }
        .notification-item .notif-time {
            font-size: 0.7rem;
            color: #999;
            margin-top: 5px;
        }
        .notification-item .notif-link {
            color: #1a2a6c;
            text-decoration: none;
        }
        .notification-item .notif-link:hover {
            text-decoration: underline;
        }
        .notification-empty {
            padding: 20px;
            text-align: center;
            color: #888;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="container">
                <a href="index.php" class="logo">
                    <i class="fas fa-hotel"></i> BookMyHotel
                </a>
                <button class="nav-toggle" aria-label="Toggle navigation">
                    <i class="fas fa-bars"></i>
                </button>
                <ul class="nav-links">
                    <li><a href="index.php"><i class="fas fa-home"></i> <?php echo t('home'); ?></a></li>
                    <li><a href="hotels.php"><i class="fas fa-building"></i> <?php echo t('hotels'); ?></a></li>
                    <li><a href="search.php"><i class="fas fa-search"></i> <?php echo t('search'); ?></a></li>
                    <li><a href="contact.php"><i class="fas fa-envelope"></i> <?php echo t('contact'); ?></a></li>
                    <li>
                        <a href="?lang=<?php echo $lang === 'en' ? 'es' : 'en'; ?>" class="lang-switch">
                            <i class="fas fa-globe"></i> <?php echo $lang === 'en' ? '🇪🇸 Español' : '🇬🇧 English'; ?>
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="notification-bell" onclick="toggleNotifications(event)">
                            <i class="fas fa-bell" style="font-size:1.2rem;"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                            <!-- Notification Dropdown -->
                            <div class="notification-dropdown" id="notificationDropdown">
                                <div style="padding:10px 15px; border-bottom:1px solid #eee; font-weight:600; background:#f8f9fa; border-radius:8px 8px 0 0;">
                                    Notifications
                                    <?php if ($unread_count > 0): ?>
                                        <span style="float:right; font-size:0.8rem; font-weight:normal; color:#e74c3c;"><?php echo $unread_count; ?> unread</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($notifications)): ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                                            <div class="notif-title">
                                                <?php if ($notif['link']): ?>
                                                    <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="notif-link">
                                                        <?php echo htmlspecialchars($notif['title']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($notif['title']); ?>
                                                <?php endif; ?>
                                                <?php if (!$notif['is_read']): ?>
                                                    <span style="font-size:0.6rem; background:#e74c3c; color:#fff; padding:2px 6px; border-radius:10px; margin-left:5px;">New</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="notif-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                                            <div class="notif-time"><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="notification-empty">
                                        <i class="fas fa-bell-slash" style="font-size:2rem; color:#ddd;"></i>
                                        <p style="margin-top:10px;">No notifications</p>
                                    </div>
                                <?php endif; ?>
                                <div style="padding:8px 15px; border-top:1px solid #eee; text-align:center; font-size:0.85rem; background:#f8f9fa; border-radius:0 0 8px 8px;">
                                    <a href="notifications.php" style="color:#1a2a6c;">View All Notifications</a>
                                </div>
                            </div>
                        </li>
                        <li><a href="my-bookings.php"><i class="fas fa-calendar-check"></i> <?php echo t('my_bookings'); ?></a></li>
                        <li><a href="profile.php"><i class="fas fa-user"></i> <?php echo t('profile'); ?></a></li>
                        <li><a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?></a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> <?php echo t('login'); ?></a></li>
                        <li><a href="register.php" class="btn-register"><i class="fas fa-user-plus"></i> <?php echo t('register'); ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>
    <main>

<script>
function toggleNotifications(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
}

// Close notification dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationDropdown');
    const bell = document.querySelector('.notification-bell');
    if (dropdown && bell && !bell.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});
</script>