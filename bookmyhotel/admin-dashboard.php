<?php
require_once 'config/database.php';

// Only Admin can access this page
if (!isAdmin()) {
    header('Location: admin-login.php');
    exit;
}

$conn = getConnection();
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Stats
$stats = [];
$stats['total_bookings'] = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$stats['total_hotels'] = $conn->query("SELECT COUNT(*) as count FROM hotels")->fetch_assoc()['count'];
$stats['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$stats['total_revenue'] = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE status != 'cancelled'")->fetch_assoc()['total'] ?? 0;
$stats['pending_bookings'] = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch_assoc()['count'];
$stats['completed_bookings'] = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'completed'")->fetch_assoc()['count'];
$stats['cancelled_bookings'] = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'cancelled'")->fetch_assoc()['count'];
$stats['total_tickets'] = $conn->query("SELECT COUNT(*) as count FROM customer_tickets WHERE status != 'resolved'")->fetch_assoc()['count'];
$stats['total_promotions'] = $conn->query("SELECT COUNT(*) as count FROM promotions")->fetch_assoc()['count'];
$stats['total_customers'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'];

// Get selected date for room availability (default to today)
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Get all rooms with hotel names
$rooms = $conn->query("SELECT r.*, h.name as hotel_name FROM rooms r JOIN hotels h ON r.hotel_id = h.id ORDER BY h.name, r.room_type");

// Search functionality for bookings
$search_term = $_GET['search'] ?? '';
$search_where = '';
if (!empty($search_term)) {
    $search_term = $conn->real_escape_string($search_term);
    $search_where = " AND (u.full_name LIKE '%$search_term%' OR u.email LIKE '%$search_term%' OR h.name LIKE '%$search_term%' OR r.room_type LIKE '%$search_term%')";
}

// Get recent bookings with search
$recent_bookings = $conn->query("SELECT b.*, u.full_name, u.email, r.room_type, h.name as hotel_name 
                                FROM bookings b 
                                JOIN users u ON b.user_id = u.id 
                                JOIN rooms r ON b.room_id = r.id 
                                JOIN hotels h ON r.hotel_id = h.id 
                                WHERE 1=1 $search_where
                                ORDER BY b.booking_date DESC LIMIT 50");

// Maintenance tickets
$tickets = $conn->query("SELECT t.*, h.name as hotel_name, u.full_name as created_by_name 
                         FROM maintenance_tickets t 
                         JOIN hotels h ON t.hotel_id = h.id 
                         JOIN users u ON t.created_by = u.id 
                         ORDER BY t.created_at DESC LIMIT 20");

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_room_availability') {
        $room_id = (int)$_POST['room_id'];
        $available_rooms = (int)$_POST['available_rooms'];
        $date = $_POST['date'] ?? date('Y-m-d');
        
        // Check if record exists for this date
        $stmt = $conn->prepare("SELECT id FROM room_availability_log WHERE room_id = ? AND date = ?");
        $stmt->bind_param("is", $room_id, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt2 = $conn->prepare("UPDATE room_availability_log SET available_rooms = ?, is_available = ? WHERE id = ?");
            $is_available = $available_rooms > 0;
            $stmt2->bind_param("iii", $available_rooms, $is_available, $row['id']);
            $stmt2->execute();
        } else {
            $stmt2 = $conn->prepare("INSERT INTO room_availability_log (room_id, date, available_rooms, is_available) VALUES (?, ?, ?, ?)");
            $is_available = $available_rooms > 0;
            $stmt2->bind_param("isii", $room_id, $date, $available_rooms, $is_available);
            $stmt2->execute();
        }
        
        $_SESSION['message'] = 'Room availability updated for ' . date('M d, Y', strtotime($date));
        header('Location: admin-dashboard.php?date=' . $date);
        exit;
    } elseif ($_POST['action'] === 'confirm_booking') {
        $booking_id = (int)$_POST['booking_id'];
        $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'paid' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        // Notify customer
        $bookingData = $conn->query("SELECT user_id FROM bookings WHERE id = $booking_id")->fetch_assoc();
        if ($bookingData) {
            createNotification($conn, $bookingData['user_id'], 'booking_confirmed', 
                'Your booking #' . $booking_id . ' has been confirmed!', 
                'my-bookings.php', $booking_id);
        }
        
        $_SESSION['message'] = 'Booking confirmed successfully!';
        header('Location: admin-dashboard.php');
        exit;
    } elseif ($_POST['action'] === 'cancel_booking') {
        $booking_id = (int)$_POST['booking_id'];
        $conn->query("UPDATE bookings SET status = 'cancelled' WHERE id = $booking_id");
        
        // Notify customer
        $bookingData = $conn->query("SELECT user_id FROM bookings WHERE id = $booking_id")->fetch_assoc();
        if ($bookingData) {
            createNotification($conn, $bookingData['user_id'], 'booking_cancelled', 
                'Your booking #' . $booking_id . ' has been cancelled by staff.', 
                'my-bookings.php', $booking_id);
        }
        
        $_SESSION['message'] = 'Booking cancelled!';
        header('Location: admin-dashboard.php');
        exit;
    } elseif ($_POST['action'] === 'refund_booking') {
        $booking_id = (int)$_POST['booking_id'];
        $conn->query("UPDATE bookings SET payment_status = 'refunded' WHERE id = $booking_id");
        
        // Notify customer
        $bookingData = $conn->query("SELECT user_id FROM bookings WHERE id = $booking_id")->fetch_assoc();
        if ($bookingData) {
            createNotification($conn, $bookingData['user_id'], 'refund_processed', 
                'Your booking #' . $booking_id . ' has been refunded. Please allow 5-7 business days for the refund to appear in your account.', 
                'my-bookings.php', $booking_id);
        }
        
        $_SESSION['message'] = 'Refund processed successfully!';
        header('Location: admin-dashboard.php');
        exit;
    } elseif ($_POST['action'] === 'create_maintenance_ticket') {
        $hotel_id = (int)$_POST['hotel_id'];
        $room_number = trim($_POST['room_number']);
        $issue = trim($_POST['issue']);
        
        if ($hotel_id && $room_number && $issue) {
            $stmt = $conn->prepare("INSERT INTO maintenance_tickets (hotel_id, room_number, issue, created_by, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("issi", $hotel_id, $room_number, $issue, $_SESSION['user_id']);
            $stmt->execute();
            $ticket_id = $conn->insert_id;
            
            // Notify maintenance staff
            $maintenance_staff = $conn->query("SELECT id FROM users WHERE role = 'maintenance_staff'");
            while ($staff = $maintenance_staff->fetch_assoc()) {
                createNotification($conn, $staff['id'], 'ticket_created', 
                    'New maintenance ticket #' . $ticket_id . ' created for ' . $room_number . ' - ' . $issue, 
                    'maintenance-dashboard.php', $ticket_id);
            }
            
            $_SESSION['message'] = 'Maintenance ticket created successfully!';
        }
        header('Location: admin-dashboard.php');
        exit;
    } elseif ($_POST['action'] === 'complete_ticket') {
        $ticket_id = (int)$_POST['ticket_id'];
        $resolution = trim($_POST['resolution']);
        $stmt = $conn->prepare("UPDATE maintenance_tickets SET status = 'completed', completed_by = ?, resolution_notes = ?, completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("isi", $_SESSION['user_id'], $resolution, $ticket_id);
        $stmt->execute();
        
        // Notify creator
        $ticketData = $conn->query("SELECT created_by FROM maintenance_tickets WHERE id = $ticket_id")->fetch_assoc();
        if ($ticketData) {
            createNotification($conn, $ticketData['created_by'], 'ticket_completed', 
                'Maintenance ticket #' . $ticket_id . ' has been completed. Resolution: ' . $resolution, 
                'maintenance-dashboard.php', $ticket_id);
        }
        
        $_SESSION['message'] = 'Ticket marked as completed!';
        header('Location: admin-dashboard.php');
        exit;
    }
}

// No header include - standalone page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BookMyHotel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        /* ... (keep existing styles) ... */
        .search-bar {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-bar input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            min-width: 250px;
            flex: 1;
        }
        .search-bar input:focus {
            outline: none;
            border-color: #1a2a6c;
        }
        .btn-sm {
            padding: 5px 12px;
            font-size: 0.85rem;
        }
        .btn-success {
            background: #27ae60;
            color: #fff;
        }
        .btn-success:hover {
            background: #1e8449;
        }
        .btn-danger {
            background: #e74c3c;
            color: #fff;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .btn-warning {
            background: #f39c12;
            color: #fff;
        }
        .btn-warning:hover {
            background: #d68910;
            color: #fff;
        }
        .btn-primary {
            background: #1a2a6c;
            color: #fff;
        }
        .btn-primary:hover {
            background: #0f1a4a;
        }
        .btn-outline {
            background: transparent;
            border: 1px solid #1a2a6c;
            color: #1a2a6c;
        }
        .btn-outline:hover {
            background: #1a2a6c;
            color: #fff;
        }
        .table-container {
            overflow-x: auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        table th {
            background: #f8f9fa;
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
        }
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
            font-size: 0.85rem;
        }
        .admin-header { background: #1a2a6c; color: #fff; padding: 15px 0; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .admin-header .container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .admin-header .logo { color: #fff; font-size: 1.5rem; font-weight: bold; text-decoration: none; }
        .admin-header .logo i { color: #f7c948; margin-right: 8px; }
        .admin-header .user-info { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .admin-header .user-info span { color: rgba(255,255,255,0.8); }
        .admin-header .nav-link { color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 4px; transition: background 0.3s; }
        .admin-header .nav-link:hover { background: rgba(255,255,255,0.15); }
        .admin-header .logout-btn { color: #fff; background: rgba(255,255,255,0.15); padding: 8px 16px; border-radius: 6px; text-decoration: none; transition: background 0.3s; }
        .admin-header .logout-btn:hover { background: rgba(255,255,255,0.25); }
        .admin-footer { background: #1a2a6c; color: #fff; text-align: center; padding: 15px 0; margin-top: 40px; }
        .stat-card { transition: transform 0.3s, box-shadow 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .alert { padding: 12px 20px; border-radius: 6px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #1a2a6c; }
        .quick-action-card { transition: transform 0.3s; }
        .quick-action-card:hover { transform: translateY(-3px); }
        .date-nav { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .date-nav input[type="date"] { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; }
        .chart-container { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .status-badge { padding: 3px 10px; border-radius: 15px; font-size: 0.75rem; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-completed { background: #cce5ff; color: #004085; }
        .payment-badge { padding: 2px 10px; border-radius: 12px; font-size: 0.7rem; }
        .payment-paid { background: #d4edda; color: #155724; }
        .payment-refunded { background: #f8d7da; color: #721c24; }
        .payment-pending { background: #e9ecef; color: #666; }
        .action-buttons { display: flex; gap: 4px; flex-wrap: wrap; }
        .action-buttons form { display: inline; }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="container">
            <a href="admin-dashboard.php" class="logo">
                <i class="fas fa-hotel"></i> BookMyHotel Admin
            </a>
            <div class="user-info">
                <a href="manage-customers.php" class="nav-link"><i class="fas fa-users-cog"></i> Manage Customers</a>
                <a href="admin-hotels.php" class="nav-link"><i class="fas fa-building"></i> Hotel Management</a>
                <a href="customer-tickets.php" class="nav-link"><i class="fas fa-ticket-alt"></i> Customer Tickets</a>
                <a href="promotions.php" class="nav-link"><i class="fas fa-tags"></i> Promotions</a>
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <h2><i class="fas fa-chart-pie"></i> Admin Dashboard</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-success animate__animated animate__fadeIn"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:15px; margin:20px 0;">
                <a href="manage-customers.php" class="quick-action-card" style="background:linear-gradient(135deg, #1a2a6c, #2d4373); color:#fff; padding:20px; border-radius:12px; text-align:center; text-decoration:none; box-shadow:0 4px 15px rgba(0,0,0,0.1);">
                    <i class="fas fa-users-cog" style="font-size:2rem;"></i>
                    <p style="margin-top:8px; font-weight:600; font-size:0.85rem;">Manage Customers</p>
                </a>
                <a href="admin-hotels.php" class="quick-action-card" style="background:linear-gradient(135deg, #e67e22, #d35400); color:#fff; padding:20px; border-radius:12px; text-align:center; text-decoration:none; box-shadow:0 4px 15px rgba(0,0,0,0.1);">
                    <i class="fas fa-building" style="font-size:2rem;"></i>
                    <p style="margin-top:8px; font-weight:600; font-size:0.85rem;">Hotel Management</p>
                </a>
                <a href="customer-tickets.php" class="quick-action-card" style="background:linear-gradient(135deg, #e74c3c, #c0392b); color:#fff; padding:20px; border-radius:12px; text-align:center; text-decoration:none; box-shadow:0 4px 15px rgba(0,0,0,0.1);">
                    <i class="fas fa-ticket-alt" style="font-size:2rem;"></i>
                    <p style="margin-top:8px; font-weight:600; font-size:0.85rem;">Customer Tickets</p>
                </a>
                <a href="promotions.php" class="quick-action-card" style="background:linear-gradient(135deg, #27ae60, #1e8449); color:#fff; padding:20px; border-radius:12px; text-align:center; text-decoration:none; box-shadow:0 4px 15px rgba(0,0,0,0.1);">
                    <i class="fas fa-tags" style="font-size:2rem;"></i>
                    <p style="margin-top:8px; font-weight:600; font-size:0.85rem;">Promotions</p>
                </a>
            </div>
            
            <!-- Stats -->
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:10px; margin:20px 0;">
                <div class="stat-card" style="background:#fff; padding:10px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#1a2a6c; margin:0; font-size:1.1rem;"><?php echo $stats['total_bookings']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.7rem; color:#666;">Total Bookings</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:10px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#f39c12; margin:0; font-size:1.1rem;"><?php echo $stats['pending_bookings']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.7rem; color:#666;">Pending</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:10px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#27ae60; margin:0; font-size:1.1rem;"><?php echo $stats['completed_bookings']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.7rem; color:#666;">Completed</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:10px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#e74c3c; margin:0; font-size:1.1rem;"><?php echo $stats['cancelled_bookings']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.7rem; color:#666;">Cancelled</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:10px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#1a2a6c; margin:0; font-size:1.1rem;"><?php echo $stats['total_hotels']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.7rem; color:#666;">Hotels</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:10px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#27ae60; margin:0; font-size:1.1rem;">$<?php echo number_format($stats['total_revenue'], 0); ?></h3>
                    <p style="margin:5px 0 0; font-size:0.7rem; color:#666;">Revenue</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:10px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#e74c3c; margin:0; font-size:1.1rem;"><?php echo $stats['total_tickets']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.7rem; color:#666;">Tickets</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:10px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#8e44ad; margin:0; font-size:1.1rem;"><?php echo $stats['total_promotions']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.7rem; color:#666;">Promotions</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:10px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#1a2a6c; margin:0; font-size:1.1rem;"><?php echo $stats['total_customers']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.7rem; color:#666;">Customers</p>
                </div>
            </div>
            
            <!-- Room Availability Management - Date Based -->
            <h3 style="margin:30px 0 15px;"><i class="fas fa-door-open"></i> Room Availability Management</h3>
            
            <!-- Date Selector -->
            <div style="background:#fff; padding:15px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:20px;">
                <form method="GET" class="date-nav">
                    <label style="font-weight:600;">Select Date:</label>
                    <input type="date" name="date" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
                    <span style="color:#888; font-size:0.9rem;">
                        Showing availability for: <strong><?php echo date('l, F d, Y', strtotime($selected_date)); ?></strong>
                    </span>
                    <?php if (isset($_GET['date'])): ?>
                        <a href="admin-dashboard.php" class="btn btn-outline btn-sm">Today</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div style="background:#fff; border-radius:10px; overflow-x:auto; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:20px;">
                <table>
                    <thead>
                        <tr style="background:#f8f9fa;">
                            <th style="padding:10px 15px; text-align:left;">Hotel</th>
                            <th style="padding:10px 15px; text-align:left;">Room Type</th>
                            <th style="padding:10px 15px; text-align:left;">Total Rooms</th>
                            <th style="padding:10px 15px; text-align:left;">Available for <?php echo date('M d', strtotime($selected_date)); ?></th>
                            <th style="padding:10px 15px; text-align:left;">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rooms = $conn->query("SELECT r.*, h.name as hotel_name FROM rooms r JOIN hotels h ON r.hotel_id = h.id ORDER BY h.name, r.room_type");
                        while ($room = $rooms->fetch_assoc()): 
                            $stmt = $conn->prepare("SELECT available_rooms, is_available FROM room_availability_log WHERE room_id = ? AND date = ?");
                            $stmt->bind_param("is", $room['id'], $selected_date);
                            $stmt->execute();
                            $avail_result = $stmt->get_result();
                            $avail_data = $avail_result->fetch_assoc();
                            
                            $available = $avail_data ? $avail_data['available_rooms'] : $room['available_rooms'];
                            $is_available = $avail_data ? $avail_data['is_available'] : ($available > 0);
                            $is_future = strtotime($selected_date) >= strtotime(date('Y-m-d'));
                        ?>
                            <tr style="border-bottom:1px solid #eee; <?php echo !$is_future ? 'opacity:0.6;' : ''; ?>">
                                <td style="padding:10px 15px;"><?php echo htmlspecialchars($room['hotel_name']); ?></td>
                                <td style="padding:10px 15px;"><?php echo htmlspecialchars($room['room_type']); ?></td>
                                <td style="padding:10px 15px;"><?php echo $room['total_rooms']; ?></td>
                                <td style="padding:10px 15px;">
                                    <span style="color:<?php echo $is_available ? '#27ae60' : '#e74c3c'; ?>; font-weight:bold;">
                                        <?php echo $available; ?>
                                    </span>
                                    <?php if (!$is_future): ?>
                                        <span style="font-size:0.75rem; color:#888; margin-left:5px;">(past date)</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:10px 15px;">
                                    <?php if ($is_future): ?>
                                        <form method="POST" style="display:flex; gap:5px; align-items:center;">
                                            <input type="hidden" name="action" value="update_room_availability">
                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                            <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                                            <input type="number" name="available_rooms" value="<?php echo $available; ?>" 
                                                   min="0" max="<?php echo $room['total_rooms']; ?>" 
                                                   style="width:70px; padding:5px; border:1px solid #ddd; border-radius:4px;">
                                            <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:#888; font-size:0.85rem;">Cannot modify past dates</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Create Maintenance Ticket -->
            <h3 style="margin:30px 0 15px;"><i class="fas fa-tools"></i> Create Maintenance Ticket</h3>
            <div style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:20px;">
                <form method="POST">
                    <input type="hidden" name="action" value="create_maintenance_ticket">
                    <div style="display:grid; grid-template-columns:1fr 1fr 2fr; gap:15px;">
                        <div class="form-group" style="margin:0;">
                            <select name="hotel_id" required>
                                <option value="">Select Hotel</option>
                                <?php
                                $hotels = $conn->query("SELECT id, name FROM hotels");
                                while ($h = $hotels->fetch_assoc()): ?>
                                    <option value="<?php echo $h['id']; ?>"><?php echo htmlspecialchars($h['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <input type="text" name="room_number" placeholder="Room Number" required>
                        </div>
                        <div class="form-group" style="margin:0; display:flex; gap:10px;">
                            <input type="text" name="issue" placeholder="Issue Description" required style="flex:1;">
                            <button type="submit" class="btn btn-primary btn-sm">Create Ticket</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Maintenance Tickets List -->
            <h3 style="margin:30px 0 15px;"><i class="fas fa-list"></i> Maintenance Tickets</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Hotel</th>
                            <th>Room</th>
                            <th>Issue</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tickets->num_rows > 0): ?>
                            <?php while ($ticket = $tickets->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ticket['hotel_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['room_number']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['issue']); ?></td>
                                    <td>
                                        <span class="status-badge <?php 
                                            echo $ticket['status'] === 'completed' ? 'status-completed' : 
                                                 ($ticket['status'] === 'in_progress' ? 'status-pending' : 'status-pending'); 
                                        ?>">
                                            <?php echo ucfirst($ticket['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($ticket['status'] !== 'completed'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="complete_ticket">
                                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                <input type="text" name="resolution" placeholder="Resolution" style="padding:4px 8px; width:100px; border:1px solid #ddd; border-radius:4px;">
                                                <button type="submit" class="btn btn-success btn-sm">Complete</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size:0.85rem; color:#888;">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:20px; color:#888;">No maintenance tickets found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Recent Bookings with Search -->
            <h3 style="margin:30px 0 15px;"><i class="fas fa-list"></i> Recent Customer Bookings</h3>
            
            <!-- Search Bar -->
            <div style="background:#fff; padding:15px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:20px;">
                <form method="GET" class="search-bar">
                    <input type="text" name="search" placeholder="Search by guest name, email, hotel, or room type..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($search_term)): ?>
                        <a href="admin-dashboard.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                    <span style="color:#888; font-size:0.85rem; margin-left:auto;">
                        <?php echo $recent_bookings->num_rows; ?> bookings found
                    </span>
                </form>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Guest</th>
                            <th>Email</th>
                            <th>Hotel</th>
                            <th>Room</th>
                            <th>Guests</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_bookings->num_rows > 0): ?>
                            <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($booking['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($booking['email']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['hotel_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['room_type']); ?></td>
                                    <td><?php echo $booking['guests'] ?? 2; ?></td>
                                    <td><?php echo date('M d', strtotime($booking['check_in'])); ?></td>
                                    <td><?php echo date('M d', strtotime($booking['check_out'])); ?></td>
                                    <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php 
                                            echo $booking['status'] === 'confirmed' ? 'status-confirmed' : 
                                                 ($booking['status'] === 'cancelled' ? 'status-cancelled' : 
                                                 ($booking['status'] === 'completed' ? 'status-completed' : 'status-pending')); 
                                        ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="payment-badge <?php 
                                            echo $booking['payment_status'] === 'paid' ? 'payment-paid' : 
                                                 ($booking['payment_status'] === 'refunded' ? 'payment-refunded' : 'payment-pending'); 
                                        ?>">
                                            <?php echo ucfirst($booking['payment_status'] ?? 'pending'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- Confirm Button (only for pending) -->
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="confirm_booking">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Confirm this booking?');" title="Confirm Booking">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <!-- Cancel Button (for pending/confirmed, not cancelled/completed) -->
                                            <?php if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'completed'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="cancel_booking">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this booking?');" title="Cancel Booking">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <!-- Refund Button (for cancelled but paid) -->
                                            <?php if ($booking['status'] === 'cancelled' && $booking['payment_status'] === 'paid'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="refund_booking">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Process refund for this booking?');" title="Process Refund">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <!-- Refunded Status -->
                                            <?php if ($booking['status'] === 'cancelled' && $booking['payment_status'] === 'refunded'): ?>
                                                <span style="color:#27ae60; font-size:0.75rem; display:flex; align-items:center; height:28px;">
                                                    <i class="fas fa-check-circle"></i>&nbsp;Refunded
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- Completed Status -->
                                            <?php if ($booking['status'] === 'completed'): ?>
                                                <span style="color:#004085; font-size:0.75rem; display:flex; align-items:center; height:28px;">
                                                    <i class="fas fa-check-circle"></i>&nbsp;Done
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" style="text-align:center; padding:20px; color:#888;">
                                    <?php echo !empty($search_term) ? 'No bookings found matching your search.' : 'No bookings found.'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="admin-footer">
        <div class="container">
            <p>&copy; 2026 BookMyHotel Admin Panel. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>