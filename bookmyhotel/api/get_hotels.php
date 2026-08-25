<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$conn = getConnection();

$result = $conn->query("SELECT * FROM hotels ORDER BY name");
$hotels = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $hotels]);
?>