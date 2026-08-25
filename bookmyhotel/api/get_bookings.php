<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

$query = "SELECT b.*, r.room_type, h.name as hotel_name, h.id as hotel_id 
          FROM bookings b 
          JOIN rooms r ON b.room_id = r.id 
          JOIN hotels h ON r.hotel_id = h.id 
          WHERE b.user_id = $user_id 
          ORDER BY b.booking_date DESC";

$result = $conn->query($query);
$bookings = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $bookings]);
?>