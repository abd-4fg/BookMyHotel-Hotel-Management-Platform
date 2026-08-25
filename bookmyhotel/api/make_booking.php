<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$conn = getConnection();
$data = json_decode(file_get_contents('php://input'), true);

$room_id = (int)($data['room_id'] ?? 0);
$check_in = $data['check_in'] ?? '';
$check_out = $data['check_out'] ?? '';

if (!$room_id || !$check_in || !$check_out) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Calculate nights
$start = new DateTime($check_in);
$end = new DateTime($check_out);
$nights = $end->diff($start)->days;

if ($nights <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid dates']);
    exit;
}

// Get room price
$stmt = $conn->prepare("SELECT price_per_night FROM rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    echo json_encode(['success' => false, 'message' => 'Room not found']);
    exit;
}

$total = $nights * $room['price_per_night'];

// Create booking
$stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, total_price, status, payment_status) 
                       VALUES (?, ?, ?, ?, ?, 'pending', 'pending')");
$stmt->bind_param("iissd", $_SESSION['user_id'], $room_id, $check_in, $check_out, $total);

if ($stmt->execute()) {
    // Update available rooms
    $conn->query("UPDATE rooms SET available_rooms = available_rooms - 1 WHERE id = $room_id");
    echo json_encode(['success' => true, 'message' => 'Booking created successfully', 'booking_id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking failed']);
}
?>