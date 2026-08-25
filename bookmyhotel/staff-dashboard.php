<?php
require_once 'config/database.php';

// Only Staff or Admin can access this page
if (!isHotelStaff() && !isAdmin()) {
    header('Location: staff-login.php');
    exit;
}

$conn = getConnection();
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Stats for staff
$stats = [];
$stats['total_bookings'] = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$stats['pending_bookings'] = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch_assoc()['count'];
$stats['total_revenue'] = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE status != 'cancelled'")->fetch_assoc()['total'] ?? 0;
$stats['total_hotels'] = $conn->query("SELECT COUNT(*) as count FROM hotels")->fetch_assoc()['count'];
$stats['total_customers'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'];

// Get all rooms with hotel names
$rooms = $conn->query("SELECT r.*, h.name as hotel_name FROM rooms r JOIN hotels h ON r.hotel_id = h.id ORDER BY h.name, r.room_type");

// Get selected date for room availability (default to today)
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Get recent bookings (instead of pending)
$recent_bookings = $conn->query("SELECT b.*, u.full_name, r.room_type, h.name as hotel_name 
                                FROM bookings b 
                                JOIN users u ON b.user_id = u.id 
                                JOIN rooms r ON b.room_id = r.id 
                                JOIN hotels h ON r.hotel_id = h.id 
                                ORDER BY b.booking_date DESC LIMIT 30");

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
            // Update existing record
            $row = $result->fetch_assoc();
            $stmt2 = $conn->prepare("UPDATE room_availability_log SET available_rooms = ?, is_available = ? WHERE id = ?");
            $is_available = $available_rooms > 0;
            $stmt2->bind_param("iii", $available_rooms, $is_available, $row['id']);
            $stmt2->execute();
        } else {
            // Insert new record
            $stmt2 = $conn->prepare("INSERT INTO room_availability_log (room_id, date, available_rooms, is_available) VALUES (?, ?, ?, ?)");
            $is_available = $available_rooms > 0;
            $stmt2->bind_param("isii", $room_id, $date, $available_rooms, $is_available);
            $stmt2->execute();
        }
        
        $_SESSION['message'] = 'Room availability updated for ' . date('M d, Y', strtotime($date));
        header('Location: staff-dashboard.php?date=' . $date);
        exit;
    } elseif ($_POST['action'] === 'confirm_booking') {
        $booking_id = (int)$_POST['booking_id'];
        $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'paid' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $_SESSION['message'] = 'Booking confirmed successfully!';
        header('Location: staff-dashboard.php');
        exit;
    } elseif ($_POST['action'] === 'create_maintenance_ticket') {
        $hotel_id = (int)$_POST['hotel_id'];
        $room_number = trim($_POST['room_number']);
        $issue = trim($_POST['issue']);
        
        if ($hotel_id && $room_number && $issue) {
            $stmt = $conn->prepare("INSERT INTO maintenance_tickets (hotel_id, room_number, issue, created_by, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("issi", $hotel_id, $room_number, $issue, $_SESSION['user_id']);
            $stmt->execute();
            $_SESSION['message'] = 'Maintenance ticket created successfully!';
        }
        header('Location: staff-dashboard.php');
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
    <title>Staff Dashboard - BookMyHotel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .staff-header {
            background: #2d4373;
            color: #fff;
            padding: 15px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .staff-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .staff-header .logo {
            color: #fff;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
        }
        .staff-header .logo i {
            color: #f7c948;
            margin-right: 8px;
        }
        .staff-header .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .staff-header .user-info span {
            color: rgba(255,255,255,0.8);
        }
        .staff-header .nav-link {
            color: #fff;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .staff-header .nav-link:hover {
            background: rgba(255,255,255,0.15);
        }
        .staff-header .logout-btn {
            color: #fff;
            background: rgba(255,255,255,0.15);
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .staff-header .logout-btn:hover {
            background: rgba(255,255,255,0.25);
        }
        .staff-footer {
            background: #2d4373;
            color: #fff;
            text-align: center;
            padding: 15px 0;
            margin-top: 40px;
        }
        .stat-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
        .btn-primary {
            background: #2d4373;
            color: #fff;
        }
        .btn-primary:hover {
            background: #1a2a6c;
        }
        .btn-danger {
            background: #e74c3c;
            color: #fff;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .alert {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2d4373;
        }
        .quick-action-card {
            transition: transform 0.3s;
        }
        .quick-action-card:hover {
            transform: translateY(-3px);
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
            min-width: 600px;
        }
        table th {
            background: #f8f9fa;
            padding: 10px 15px;
            text-align: left;
            font-weight: 600;
        }
        table td {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }
        .date-nav {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .date-nav input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <!-- Staff Header -->
    <header class="staff-header">
        <div class="container">
            <a href="staff-dashboard.php" class="logo">
                <i class="fas fa-hotel"></i> BookMyHotel Staff
            </a>
            <div class="user-info">
                <a href="manage-customers.php" class="nav-link">
                    <i class="fas fa-users-cog"></i> Manage Customers
                </a>
                <a href="customer-tickets.php" class="nav-link">
                    <i class="fas fa-ticket-alt"></i> Customer Tickets
                </a>
                <a href="promotions.php" class="nav-link">
                    <i class="fas fa-tags"></i> Promotions
                </a>
                <span><i class="fas fa-user-cog"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Staff'); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <h2><i class="fas fa-clipboard-list"></i> Staff Dashboard</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-success animate__animated animate__fadeIn"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:15px; margin:20px 0;">
                <a href="manage-customers.php" class="quick-action-card" style="background:linear-gradient(135deg, #2d4373, #1a2a6c); color:#fff; padding:20px; border-radius:12px; text-align:center; text-decoration:none; box-shadow:0 4px 15px rgba(0,0,0,0.1);">
                    <i class="fas fa-users-cog" style="font-size:2rem;"></i>
                    <p style="margin-top:8px; font-weight:600; font-size:0.9rem;">Manage Customers</p>
                </a>
                <a href="customer-tickets.php" class="quick-action-card" style="background:linear-gradient(135deg, #e67e22, #d35400); color:#fff; padding:20px; border-radius:12px; text-align:center; text-decoration:none; box-shadow:0 4px 15px rgba(0,0,0,0.1);">
                    <i class="fas fa-ticket-alt" style="font-size:2rem;"></i>
                    <p style="margin-top:8px; font-weight:600; font-size:0.9rem;">Customer Tickets</p>
                </a>
                <a href="promotions.php" class="quick-action-card" style="background:linear-gradient(135deg, #27ae60, #1e8449); color:#fff; padding:20px; border-radius:12px; text-align:center; text-decoration:none; box-shadow:0 4px 15px rgba(0,0,0,0.1);">
                    <i class="fas fa-tags" style="font-size:2rem;"></i>
                    <p style="margin-top:8px; font-weight:600; font-size:0.9rem;">Promotions</p>
                </a>
            </div>
            
            <!-- Stats -->
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:15px; margin:20px 0;">
                <div class="stat-card" style="background:#fff; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#2d4373; margin:0;"><?php echo $stats['total_bookings']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.85rem; color:#666;">Total Bookings</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#f39c12; margin:0;"><?php echo $stats['pending_bookings']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.85rem; color:#666;">Pending</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#27ae60; margin:0;">$<?php echo number_format($stats['total_revenue'], 0); ?></h3>
                    <p style="margin:5px 0 0; font-size:0.85rem; color:#666;">Total Revenue</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#2d4373; margin:0;"><?php echo $stats['total_hotels']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.85rem; color:#666;">Total Hotels</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#2d4373; margin:0;"><?php echo $stats['total_customers']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.85rem; color:#666;">Total Customers</p>
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
                        <a href="staff-dashboard.php" class="btn btn-outline btn-sm">Today</a>
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
                            // Get availability for selected date
                            $stmt = $conn->prepare("SELECT available_rooms, is_available FROM room_availability_log WHERE room_id = ? AND date = ?");
                            $stmt->bind_param("is", $room['id'], $selected_date);
                            $stmt->execute();
                            $avail_result = $stmt->get_result();
                            $avail_data = $avail_result->fetch_assoc();
                            
                            $available = $avail_data ? $avail_data['available_rooms'] : $room['available_rooms'];
                            $is_available = $avail_data ? $avail_data['is_available'] : ($available > 0);
                            
                            // Check if this date is in the future
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
            
            <!-- Recent Bookings -->
            <h3 style="margin:30px 0 15px;"><i class="fas fa-list"></i> Recent Customer Bookings</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Guest</th>
                            <th>Hotel</th>
                            <th>Room Type</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_bookings->num_rows > 0): ?>
                            <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['hotel_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['room_type']); ?></td>
                                    <td><?php echo date('M d', strtotime($booking['check_in'])); ?></td>
                                    <td><?php echo date('M d', strtotime($booking['check_out'])); ?></td>
                                    <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                    <td>
                                        <span style="padding:3px 10px; border-radius:15px; background:<?php 
                                            echo $booking['status'] === 'confirmed' ? '#d4edda' : 
                                                 ($booking['status'] === 'cancelled' ? '#f8d7da' : '#fff3cd'); 
                                        ?>; color:<?php 
                                            echo $booking['status'] === 'confirmed' ? '#155724' : 
                                                 ($booking['status'] === 'cancelled' ? '#721c24' : '#856404'); 
                                        ?>; font-size:0.8rem;">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="confirm_booking">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Confirm this booking?');">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:20px; color:#888;">No bookings found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="staff-footer">
        <div class="container">
            <p>&copy; 2026 BookMyHotel Staff Panel. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>