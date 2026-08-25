<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$conn = getConnection();
$hotel_id = (int)($_GET['hotel_id'] ?? 0);

if (!$hotel_id) {
    echo json_encode(['success' => false, 'message' => 'Hotel ID required']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM rooms WHERE hotel_id = ?");
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $rooms]);
?>