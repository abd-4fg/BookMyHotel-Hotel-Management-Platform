<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$hotel_id = (int)($_POST['hotel_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$error = '';

if ($hotel_id && $rating && $comment) {
    // Check if user has a confirmed booking for this hotel
    $stmt = $conn->prepare("SELECT b.id FROM bookings b 
                           JOIN rooms r ON b.room_id = r.id 
                           WHERE b.user_id = ? AND r.hotel_id = ? 
                           AND b.status = 'confirmed' 
                           LIMIT 1");
    $stmt->bind_param("ii", $_SESSION['user_id'], $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, hotel_id, booking_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $_SESSION['user_id'], $hotel_id, $booking['id'], $rating, $comment);
        if ($stmt->execute()) {
            $_SESSION['message'] = t('review_submitted');
        } else {
            $error = t('review_failed');
        }
    } else {
        $error = t('review_requires_booking');
    }
}

if ($error) {
    $_SESSION['error'] = $error;
}

header("Location: hotel-detail.php?id=$hotel_id");
exit;
?>