<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$conn = getConnection();
$hotel_id = (int)($_GET['hotel_id'] ?? 0);
$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('m'));

if (!$hotel_id) {
    echo json_encode([]);
    exit;
}

// Get all rooms for this hotel
$roomsResult = $conn->query("SELECT id FROM rooms WHERE hotel_id = $hotel_id");
$roomIds = [];
while ($row = $roomsResult->fetch_assoc()) {
    $roomIds[] = $row['id'];
}

$availability = [];

if (!empty($roomIds)) {
    $roomIdsStr = implode(',', $roomIds);
    
    // Get availability for each day of the month
    $startDate = "$year-$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    // Get bookings that overlap with this month
    $bookingsQuery = $conn->query("SELECT r.id as room_id, b.check_in, b.check_out, b.status 
                                  FROM rooms r 
                                  LEFT JOIN bookings b ON r.id = b.room_id 
                                  WHERE r.id IN ($roomIdsStr) 
                                  AND b.status IN ('confirmed', 'pending')
                                  AND ((b.check_in <= '$endDate' AND b.check_out >= '$startDate')
                                  OR b.check_in IS NULL)");
    
    $bookedRooms = [];
    while ($row = $bookingsQuery->fetch_assoc()) {
        if ($row['check_in'] && $row['check_out']) {
            $checkIn = new DateTime($row['check_in']);
            $checkOut = new DateTime($row['check_out']);
            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($checkIn, $interval, $checkOut);
            
            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                if (!isset($bookedRooms[$dateStr])) {
                    $bookedRooms[$dateStr] = 0;
                }
                $bookedRooms[$dateStr]++;
            }
        }
    }
    
    // Get total rooms count
    $totalRooms = count($roomIds);
    
    // Build availability for each day
    $currentDate = new DateTime($startDate);
    $endDateTime = new DateTime($endDate);
    
    while ($currentDate <= $endDateTime) {
        $dateStr = $currentDate->format('Y-m-d');
        $booked = $bookedRooms[$dateStr] ?? 0;
        $available = $totalRooms - $booked;
        $availability[$dateStr] = max(0, $available);
        $currentDate->modify('+1 day');
    }
}

echo json_encode($availability);
?>