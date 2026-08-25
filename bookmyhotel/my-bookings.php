<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Handle cancel booking
if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $booking_id = (int)$_GET['id'];
    
    // Check if booking belongs to user and is cancellable
    $stmt = $conn->prepare("SELECT b.*, r.image_url FROM bookings b 
                           JOIN rooms r ON b.room_id = r.id 
                           WHERE b.id = ? AND b.user_id = ? AND b.status != 'cancelled'");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    
    if ($booking) {
        $check_in = new DateTime($booking['check_in']);
        $now = new DateTime();
        $hours_until_checkin = ($check_in->getTimestamp() - $now->getTimestamp()) / 3600;
        
        if ($hours_until_checkin < 48) {
            $_SESSION['message'] = 'Cancellations must be made at least 48 hours before check-in.';
        } else {
            $conn->query("UPDATE bookings SET status = 'cancelled' WHERE id = $booking_id");
            
            // If payment was made, mark as refunded
            if ($booking['payment_status'] === 'paid') {
                $conn->query("UPDATE bookings SET payment_status = 'refunded' WHERE id = $booking_id");
                
                // Notify user about refund
                createNotification($conn, $user_id, 'booking_refunded', 
                    'Your booking #' . $booking_id . ' has been cancelled and refunded. The refund will appear in your account within 5-7 business days.', 
                    'booking', $booking_id);
            } else {
                createNotification($conn, $user_id, 'booking_cancelled', 
                    'Your booking #' . $booking_id . ' has been cancelled.', 
                    'booking', $booking_id);
            }
            
            // Notify staff
            $staff = $conn->query("SELECT id FROM users WHERE role IN ('admin', 'hotel_staff')");
            while ($staff_user = $staff->fetch_assoc()) {
                createNotification($conn, $staff_user['id'], 'booking_cancelled', 
                    'Booking #' . $booking_id . ' cancelled by ' . $_SESSION['full_name'] . 
                    ($booking['payment_status'] === 'paid' ? ' (Refund processed)' : ''), 
                    'booking', $booking_id);
            }
            
            $_SESSION['message'] = 'Booking cancelled successfully.' . 
                ($booking['payment_status'] === 'paid' ? ' Refund will be processed within 5-7 business days.' : '');
        }
    }
    header('Location: my-bookings.php');
    exit;
}

$query = "SELECT b.*, r.room_type, r.image_url, h.name as hotel_name, h.id as hotel_id 
          FROM bookings b 
          JOIN rooms r ON b.room_id = r.id 
          JOIN hotels h ON r.hotel_id = h.id 
          WHERE b.user_id = $user_id 
          ORDER BY b.booking_date DESC";
$result = $conn->query($query);
$bookings = $result->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container">
    <h2>My Bookings</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success animate__animated animate__fadeIn"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if (empty($bookings)): ?>
        <div class="alert alert-info">You have no bookings yet. <a href="hotels.php">Browse Hotels</a></div>
    <?php else: ?>
        <div style="display:grid; gap:20px;">
            <?php foreach ($bookings as $booking): ?>
                <div style="background:#fff; padding:20px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.06); 
                            border-left: 5px solid <?php echo $booking['status'] === 'cancelled' ? '#e74c3c' : ($booking['status'] === 'confirmed' ? '#27ae60' : '#f39c12'); ?>;">
                    <div style="display:flex; gap:20px; flex-wrap:wrap;">
                        <?php if ($booking['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($booking['image_url']); ?>" alt="Room" style="width:150px; height:150px; object-fit:cover; border-radius:8px;">
                        <?php endif; ?>
                        <div style="flex:1;">
                            <h3><?php echo htmlspecialchars($booking['hotel_name']); ?></h3>
                            <p><strong>Room:</strong> <?php echo htmlspecialchars($booking['room_type']); ?></p>
                            <p><strong>Guests:</strong> <?php echo $booking['guests'] ?? 2; ?></p>
                            <p><strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($booking['check_in'])); ?></p>
                            <p><strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($booking['check_out'])); ?></p>
                            <p><strong>Total:</strong> $<?php echo number_format($booking['total_price'], 2); ?></p>
                            <p><strong>Status:</strong> 
                                <span style="padding:4px 12px; border-radius:20px; background:<?php 
                                    echo $booking['status'] === 'confirmed' ? '#d4edda' : 
                                         ($booking['status'] === 'cancelled' ? '#f8d7da' : '#fff3cd'); 
                                ?>; color:<?php 
                                    echo $booking['status'] === 'confirmed' ? '#155724' : 
                                         ($booking['status'] === 'cancelled' ? '#721c24' : '#856404'); 
                                ?>;">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </p>
                            <p><strong>Payment:</strong> <?php echo ucfirst(str_replace('_', ' ', $booking['payment_method'] ?? 'Not specified')); ?> 
                                <span style="padding:2px 10px; border-radius:12px; background:<?php 
                                    echo $booking['payment_status'] === 'paid' ? '#d4edda' : 
                                         ($booking['payment_status'] === 'refunded' ? '#f8d7da' : '#e9ecef'); 
                                ?>; color:<?php 
                                    echo $booking['payment_status'] === 'paid' ? '#155724' : 
                                         ($booking['payment_status'] === 'refunded' ? '#721c24' : '#666'); 
                                ?>;">
                                    <?php echo ucfirst($booking['payment_status'] ?? 'pending'); ?>
                                </span>
                            </p>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:10px; align-items:flex-start;">
                            <?php if ($booking['status'] === 'pending'): ?>
                                <span style="background:#fff3cd; padding:4px 12px; border-radius:20px; font-size:0.9rem;">
                                    <i class="fas fa-clock"></i> Waiting for staff confirmation
                                </span>
                            <?php endif; ?>
                            <?php if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'completed'): ?>
                                <a href="?cancel=1&id=<?php echo $booking['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to cancel this booking?');">
                                    <i class="fas fa-times"></i> Cancel Booking
                                </a>
                            <?php endif; ?>
                            <?php if ($booking['status'] === 'cancelled' && $booking['payment_status'] === 'refunded'): ?>
                                <span style="background:#d4edda; padding:4px 12px; border-radius:20px; color:#155724; font-size:0.9rem;">
                                    <i class="fas fa-check-circle"></i> Refunded
                                </span>
                            <?php endif; ?>
                            <?php if ($booking['status'] === 'completed'): ?>
                                <a href="hotel-detail.php?id=<?php echo $booking['hotel_id']; ?>#review" class="btn btn-outline btn-sm">
                                    <i class="fas fa-star"></i> Write a Review
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Refund info if cancelled -->
                    <?php if ($booking['status'] === 'cancelled'): ?>
                        <div style="margin-top:10px; padding:10px; background:#f8f9fa; border-radius:8px; font-size:0.9rem; color:#888;">
                            <i class="fas fa-info-circle"></i> 
                            <?php if ($booking['payment_status'] === 'refunded'): ?>
                                Refund has been processed. Please allow 5-7 business days for the refund to appear in your account.
                            <?php elseif ($booking['payment_status'] === 'paid'): ?>
                                Refund will be processed within 5-7 business days.
                            <?php else: ?>
                                No payment was processed for this booking.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>