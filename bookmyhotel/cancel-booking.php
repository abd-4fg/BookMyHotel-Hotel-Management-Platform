<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$booking_id = (int)($_GET['id'] ?? 0);

if ($booking_id) {
    $stmt = $conn->prepare("SELECT room_id, check_in, payment_status FROM bookings WHERE id = ? AND user_id = ? AND status != 'cancelled'");
    $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $booking = $result->fetch_assoc();
        
        // Check if cancellation is allowed (48 hours before check-in)
        $check_in = new DateTime($booking['check_in']);
        $now = new DateTime();
        $hours_until_checkin = ($check_in->getTimestamp() - $now->getTimestamp()) / 3600;
        
        if ($hours_until_checkin < 48) {
            $_SESSION['message'] = t('cancel_48_hours');
        } else {
            // Update booking status
            $conn->query("UPDATE bookings SET status = 'cancelled' WHERE id = $booking_id");
            
            // If payment was made, process refund
            if ($booking['payment_status'] === 'paid') {
                $conn->query("UPDATE bookings SET payment_status = 'refunded' WHERE id = $booking_id");
                $_SESSION['message'] = t('booking_cancelled_refund');
            } else {
                $_SESSION['message'] = t('booking_cancelled');
            }
        }
    }
}

header('Location: my-bookings.php');
exit;
?>