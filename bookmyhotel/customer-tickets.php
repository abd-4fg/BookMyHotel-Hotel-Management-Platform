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
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    
    if ($action === 'update_ticket' && $ticket_id) {
        $status = $_POST['status'] ?? '';
        $resolution_notes = trim($_POST['resolution_notes'] ?? '');
        
        $stmt = $conn->prepare("UPDATE customer_tickets SET status = ?, resolution_notes = ?, resolved_by = ?, resolved_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssii", $status, $resolution_notes, $_SESSION['user_id'], $ticket_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = t('ticket_updated');
        }
        header('Location: customer-tickets.php');
        exit;
    }
}

// Get all tickets
$tickets = $conn->query("SELECT t.*, u.full_name as resolver_name 
                        FROM customer_tickets t 
                        LEFT JOIN users u ON t.resolved_by = u.id 
                        ORDER BY 
                            CASE t.status 
                                WHEN 'new' THEN 0 
                                WHEN 'in_progress' THEN 1 
                                ELSE 2 
                            END, 
                        t.created_at DESC");

include 'includes/header.php';
?>

<div class="container">
    <h2><i class="fas fa-ticket-alt"></i> Customer Tickets</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success animate__animated animate__fadeIn"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($tickets->num_rows > 0): ?>
        <div style="display:grid; gap:15px;">
            <?php while ($ticket = $tickets->fetch_assoc()): ?>
                <div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.06); 
                            border-left: 5px solid <?php 
                                echo $ticket['status'] === 'new' ? '#e74c3c' : 
                                     ($ticket['status'] === 'in_progress' ? '#f39c12' : '#27ae60'); 
                            ?>;">
                    <div style="display:flex; justify-content:space-between; flex-wrap:wrap;">
                        <div>
                            <h4 style="margin:0;"><?php echo htmlspecialchars($ticket['subject']); ?></h4>
                            <p style="margin:5px 0; color:#666;">
                                <strong><?php echo t('from'); ?>:</strong> <?php echo htmlspecialchars($ticket['full_name']); ?> 
                                (<?php echo htmlspecialchars($ticket['email']); ?>)
                            </p>
                            <p style="margin:5px 0; color:#666;">
                                <strong><?php echo t('message'); ?>:</strong> <?php echo htmlspecialchars($ticket['message']); ?>
                            </p>
                            <p style="margin:5px 0; font-size:0.85rem; color:#888;">
                                <?php echo t('created'); ?>: <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?>
                            </p>
                            <?php if ($ticket['resolution_notes']): ?>
                                <p style="margin:5px 0; font-size:0.85rem; color:#27ae60;">
                                    <strong><?php echo t('resolution'); ?>:</strong> <?php echo htmlspecialchars($ticket['resolution_notes']); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($ticket['resolved_by']): ?>
                                <p style="margin:5px 0; font-size:0.85rem; color:#888;">
                                    <?php echo t('resolved_by'); ?>: <?php echo htmlspecialchars($ticket['resolver_name']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span style="padding:4px 12px; border-radius:20px; background:<?php 
                                echo $ticket['status'] === 'new' ? '#f8d7da' : 
                                     ($ticket['status'] === 'in_progress' ? '#fff3cd' : '#d4edda'); 
                            ?>; color:<?php 
                                echo $ticket['status'] === 'new' ? '#721c24' : 
                                     ($ticket['status'] === 'in_progress' ? '#856404' : '#155724'); 
                            ?>;">
                                <?php echo ucfirst(t($ticket['status'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($ticket['status'] !== 'resolved'): ?>
                        <form method="POST" style="margin-top:15px; border-top:1px solid #eee; padding-top:15px;">
                            <input type="hidden" name="action" value="update_ticket">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                                <select name="status" style="padding:8px 12px; border:1px solid #ddd; border-radius:6px;">
                                    <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>
                                        <?php echo t('in_progress'); ?>
                                    </option>
                                    <option value="resolved"><?php echo t('resolved'); ?></option>
                                </select>
                                <input type="text" name="resolution_notes" placeholder="<?php echo t('resolution_notes'); ?>" 
                                       style="flex:1; padding:8px 12px; border:1px solid #ddd; border-radius:6px; min-width:150px;">
                                <button type="submit" class="btn btn-primary btn-sm"><?php echo t('update'); ?></button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info"><?php echo t('no_tickets'); ?></div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>