<?php
require_once 'config/database.php';

// Only Staff or Admin can access this page
if (!isStaffOrAdmin()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_promo') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discount_percent = (int)($_POST['discount_percent'] ?? 10);
        $hotel_id = (int)($_POST['hotel_id'] ?? 0);
        $valid_from = $_POST['valid_from'] ?? '';
        $valid_to = $_POST['valid_to'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $usage_limit = (int)($_POST['usage_limit'] ?? 0);
        
        if (empty($code) || $discount_percent <= 0 || $discount_percent > 100) {
            $error = t('invalid_promo_data');
        } else {
            $stmt = $conn->prepare("INSERT INTO promotions (code, discount_percent, hotel_id, valid_from, valid_to, description, usage_limit, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siisssii", $code, $discount_percent, $hotel_id, $valid_from, $valid_to, $description, $usage_limit, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $_SESSION['message'] = t('promo_created');
                header('Location: promotions.php');
                exit;
            } else {
                $error = t('promo_create_failed');
            }
        }
    } elseif ($action === 'delete_promo') {
        $promo_id = (int)($_POST['promo_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM promotions WHERE id = ?");
        $stmt->bind_param("i", $promo_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = t('promo_deleted');
            header('Location: promotions.php');
            exit;
        }
    }
}

// Get all promotions with hotel names
$promotions = $conn->query("SELECT p.*, h.name as hotel_name, u.full_name as creator_name 
                           FROM promotions p 
                           LEFT JOIN hotels h ON p.hotel_id = h.id 
                           LEFT JOIN users u ON p.created_by = u.id 
                           ORDER BY p.created_at DESC");

// Get hotels for dropdown
$hotels = $conn->query("SELECT id, name FROM hotels ORDER BY name");

include 'includes/header.php';
?>

<div class="container">
    <h2><i class="fas fa-tags"></i> <?php echo t('promotion_management'); ?></h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success animate__animated animate__fadeIn"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div style="display:grid; grid-template-columns: 1fr 2fr; gap:30px;">
        <!-- Left: Create Promotion -->
        <div style="background:#fff; border-radius:12px; padding:25px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
            <h3><i class="fas fa-plus-circle"></i> <?php echo t('generate_promo_code'); ?></h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="generate_promo">
                
                <div class="form-group">
                    <label><?php echo t('promo_code'); ?> <span style="color:red;">*</span></label>
                    <input type="text" name="code" required placeholder="WELCOME10" style="text-transform:uppercase;">
                </div>
                <div class="form-group">
                    <label><?php echo t('discount_percent'); ?> <span style="color:red;">*</span></label>
                    <input type="number" name="discount_percent" required min="1" max="100" value="10">
                </div>
                <div class="form-group">
                    <label><?php echo t('hotel'); ?></label>
                    <select name="hotel_id">
                        <option value="0"><?php echo t('all_hotels'); ?></option>
                        <?php while ($hotel = $hotels->fetch_assoc()): ?>
                            <option value="<?php echo $hotel['id']; ?>"><?php echo htmlspecialchars($hotel['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo t('valid_from'); ?></label>
                    <input type="date" name="valid_from" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label><?php echo t('valid_to'); ?></label>
                    <input type="date" name="valid_to" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                </div>
                <div class="form-group">
                    <label><?php echo t('description'); ?></label>
                    <input type="text" name="description" placeholder="<?php echo t('promo_description'); ?>">
                </div>
                <div class="form-group">
                    <label><?php echo t('usage_limit'); ?></label>
                    <input type="number" name="usage_limit" min="0" value="0" placeholder="0 = unlimited">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-ticket-alt"></i> <?php echo t('generate'); ?>
                </button>
            </form>
        </div>
        
        <!-- Right: Promotion List -->
        <div style="background:#fff; border-radius:12px; padding:25px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
            <h3><i class="fas fa-list"></i> <?php echo t('active_promotions'); ?></h3>
            
            <?php if ($promotions->num_rows > 0): ?>
                <div style="max-height:500px; overflow-y:auto;">
                    <?php while ($promo = $promotions->fetch_assoc()): ?>
                        <div style="padding:15px; margin-bottom:10px; background:#f8f9fa; border-radius:8px; border-left:4px solid #27ae60;">
                            <div style="display:flex; justify-content:space-between; flex-wrap:wrap;">
                                <div>
                                    <strong style="font-size:1.1rem; color:#1a2a6c;"><?php echo htmlspecialchars($promo['code']); ?></strong>
                                    <p style="margin:3px 0; font-size:0.85rem;">
                                        <?php echo $promo['discount_percent']; ?>% <?php echo t('discount'); ?>
                                        <?php if ($promo['hotel_name']): ?>
                                            - <?php echo htmlspecialchars($promo['hotel_name']); ?>
                                        <?php else: ?>
                                            - <?php echo t('all_hotels'); ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($promo['description']): ?>
                                        <p style="margin:3px 0; font-size:0.8rem; color:#666;"><?php echo htmlspecialchars($promo['description']); ?></p>
                                    <?php endif; ?>
                                    <p style="margin:3px 0; font-size:0.75rem; color:#888;">
                                        <?php echo t('valid'); ?>: <?php echo date('M d, Y', strtotime($promo['valid_from'])); ?> - <?php echo date('M d, Y', strtotime($promo['valid_to'])); ?>
                                        <?php if ($promo['usage_limit'] > 0): ?>
                                            | <?php echo t('used'); ?>: <?php echo $promo['used_count']; ?>/<?php echo $promo['usage_limit']; ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div>
                                    <form method="POST" onsubmit="return confirm('<?php echo t('confirm_delete_promo'); ?>');">
                                        <input type="hidden" name="action" value="delete_promo">
                                        <input type="hidden" name="promo_id" value="<?php echo $promo['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color:#888;"><?php echo t('no_promotions'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>