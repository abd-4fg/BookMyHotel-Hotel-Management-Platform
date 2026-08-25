<?php
require_once 'config/database.php';

if (!isAdmin() && !isHotelStaff()) {
    header('Location: index.php');
    exit;
}

$conn = getConnection();

// Stats
$stats = [];
$stats['total_bookings'] = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$stats['total_hotels'] = $conn->query("SELECT COUNT(*) as count FROM hotels")->fetch_assoc()['count'];
$stats['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$stats['total_revenue'] = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE status != 'cancelled'")->fetch_assoc()['total'] ?? 0;
$stats['pending_bookings'] = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch_assoc()['count'];

// Recent bookings
$recent = $conn->query("SELECT b.*, u.full_name, r.room_type, h.name as hotel_name 
                        FROM bookings b 
                        JOIN users u ON b.user_id = u.id 
                        JOIN rooms r ON b.room_id = r.id 
                        JOIN hotels h ON r.hotel_id = h.id 
                        ORDER BY b.booking_date DESC LIMIT 10");

// Maintenance tickets
$tickets = $conn->query("SELECT t.*, h.name as hotel_name, u.full_name as created_by_name 
                         FROM maintenance_tickets t 
                         JOIN hotels h ON t.hotel_id = h.id 
                         JOIN users u ON t.created_by = u.id 
                         ORDER BY t.created_at DESC LIMIT 20");

// Handle maintenance ticket creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_ticket') {
        $hotel_id = (int)$_POST['hotel_id'];
        $room_number = trim($_POST['room_number']);
        $issue = trim($_POST['issue']);
        
        if ($hotel_id && $room_number && $issue) {
            $stmt = $conn->prepare("INSERT INTO maintenance_tickets (hotel_id, room_number, issue, created_by, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("issi", $hotel_id, $room_number, $issue, $_SESSION['user_id']);
            $stmt->execute();
            $_SESSION['message'] = 'Maintenance ticket created successfully.';
        }
    } elseif ($_POST['action'] === 'complete_ticket') {
        $ticket_id = (int)$_POST['ticket_id'];
        $resolution = trim($_POST['resolution']);
        $stmt = $conn->prepare("UPDATE maintenance_tickets SET status = 'completed', completed_by = ?, resolution_notes = ?, completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("isi", $_SESSION['user_id'], $resolution, $ticket_id);
        $stmt->execute();
        $_SESSION['message'] = 'Ticket marked as completed.';
    } elseif ($_POST['action'] === 'confirm_booking') {
        $booking_id = (int)$_POST['booking_id'];
        $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        // Reduce room availability
        $conn->query("UPDATE rooms r JOIN bookings b ON r.id = b.room_id SET r.available_rooms = r.available_rooms - 1 WHERE b.id = $booking_id");
        $_SESSION['message'] = 'Booking confirmed successfully.';
    }
}

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

include 'includes/header.php';
?>

<div class="container">
    <h2>Dashboard</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <!-- Stats -->
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:15px; margin:20px 0;">
        <div style="background:#fff; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <h3 style="color:#1a2a6c; margin:0;"><?php echo $stats['total_bookings']; ?></h3>
            <p style="margin:5px 0 0; font-size:0.85rem; color:#666;">Total Bookings</p>
        </div>
        <div style="background:#fff; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <h3 style="color:#f39c12; margin:0;"><?php echo $stats['pending_bookings']; ?></h3>
            <p style="margin:5px 0 0; font-size:0.85rem; color:#666;">Pending</p>
        </div>
        <div style="background:#fff; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <h3 style="color:#1a2a6c; margin:0;"><?php echo $stats['total_hotels']; ?></h3>
            <p style="margin:5px 0 0; font-size:0.85rem; color:#666;">Hotels</p>
        </div>
        <div style="background:#fff; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <h3 style="color:#27ae60; margin:0;">$<?php echo number_format($stats['total_revenue'], 0); ?></h3>
            <p style="margin:5px 0 0; font-size:0.85rem; color:#666;">Revenue</p>
        </div>
    </div>
    
    <!-- Pending Bookings (Staff Confirmation) -->
    <h3 style="margin:30px 0 15px;">Pending Bookings <span style="font-size:0.9rem; color:#888; font-weight:normal;">(Requires Confirmation)</span></h3>
    <?php
    $pending = $conn->query("SELECT b.*, u.full_name, r.room_type, h.name as hotel_name 
                            FROM bookings b 
                            JOIN users u ON b.user_id = u.id 
                            JOIN rooms r ON b.room_id = r.id 
                            JOIN hotels h ON r.hotel_id = h.id 
                            WHERE b.status = 'pending' 
                            ORDER BY b.booking_date DESC");
    if ($pending->num_rows > 0): ?>
        <div style="background:#fff; border-radius:10px; overflow-x:auto; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:20px;">
            <table style="width:100%; border-collapse:collapse; min-width:500px;">
                <thead>
                    <tr style="background:#f8f9fa;">
                        <th style="padding:10px 15px; text-align:left;">Guest</th>
                        <th style="padding:10px 15px; text-align:left;">Hotel</th>
                        <th style="padding:10px 15px; text-align:left;">Room</th>
                        <th style="padding:10px 15px; text-align:left;">Dates</th>
                        <th style="padding:10px 15px; text-align:left;">Total</th>
                        <th style="padding:10px 15px; text-align:left;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $pending->fetch_assoc()): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px 15px;"><?php echo htmlspecialchars($booking['full_name']); ?></td>
                            <td style="padding:10px 15px;"><?php echo htmlspecialchars($booking['hotel_name']); ?></td>
                            <td style="padding:10px 15px;"><?php echo htmlspecialchars($booking['room_type']); ?></td>
                            <td style="padding:10px 15px; font-size:0.85rem;">
                                <?php echo date('M d', strtotime($booking['check_in'])); ?> - 
                                <?php echo date('M d', strtotime($booking['check_out'])); ?>
                            </td>
                            <td style="padding:10px 15px;">$<?php echo number_format($booking['total_price'], 2); ?></td>
                            <td style="padding:10px 15px;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="confirm_booking">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Confirm this booking?');">
                                        <i class="fas fa-check"></i> Confirm
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color:#888;">No pending bookings.</p>
    <?php endif; ?>
    
    <!-- Recent Bookings -->
    <h3 style="margin:30px 0 15px;">Recent Bookings</h3>
    <div style="background:#fff; border-radius:10px; overflow-x:auto; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <table style="width:100%; border-collapse:collapse; min-width:500px;">
            <thead>
                <tr style="background:#f8f9fa;">
                    <th style="padding:10px 15px; text-align:left;">Guest</th>
                    <th style="padding:10px 15px; text-align:left;">Hotel</th>
                    <th style="padding:10px 15px; text-align:left;">Room</th>
                    <th style="padding:10px 15px; text-align:left;">Check-in</th>
                    <th style="padding:10px 15px; text-align:left;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($booking = $recent->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:10px 15px;"><?php echo htmlspecialchars($booking['full_name']); ?></td>
                        <td style="padding:10px 15px;"><?php echo htmlspecialchars($booking['hotel_name']); ?></td>
                        <td style="padding:10px 15px;"><?php echo htmlspecialchars($booking['room_type']); ?></td>
                        <td style="padding:10px 15px;"><?php echo date('M d', strtotime($booking['check_in'])); ?></td>
                        <td style="padding:10px 15px;">
                            <span style="padding:3px 10px; border-radius:15px; background:<?php 
                                echo $booking['status'] === 'confirmed' ? '#d4edda' : 
                                     ($booking['status'] === 'cancelled' ? '#f8d7da' : '#fff3cd'); 
                            ?>; color:<?php 
                                echo $booking['status'] === 'confirmed' ? '#155724' : 
                                     ($booking['status'] === 'cancelled' ? '#721c24' : '#856404'); 
                            ?>;">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Maintenance Tickets -->
    <h3 style="margin:30px 0 15px;">Maintenance Tickets</h3>
    
    <!-- Create Ticket -->
    <div style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:20px;">
        <h4>Create New Ticket</h4>
        <form method="POST">
            <input type="hidden" name="action" value="create_ticket">
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
    
    <!-- Ticket List -->
    <div style="background:#fff; border-radius:10px; overflow-x:auto; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <table style="width:100%; border-collapse:collapse; min-width:500px;">
            <thead>
                <tr style="background:#f8f9fa;">
                    <th style="padding:10px 15px; text-align:left;">Hotel</th>
                    <th style="padding:10px 15px; text-align:left;">Room</th>
                    <th style="padding:10px 15px; text-align:left;">Issue</th>
                    <th style="padding:10px 15px; text-align:left;">Status</th>
                    <th style="padding:10px 15px; text-align:left;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tickets->num_rows > 0): ?>
                    <?php while ($ticket = $tickets->fetch_assoc()): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px 15px;"><?php echo htmlspecialchars($ticket['hotel_name']); ?></td>
                            <td style="padding:10px 15px;"><?php echo htmlspecialchars($ticket['room_number']); ?></td>
                            <td style="padding:10px 15px;"><?php echo htmlspecialchars($ticket['issue']); ?></td>
                            <td style="padding:10px 15px;">
                                <span style="padding:3px 10px; border-radius:15px; background:<?php 
                                    echo $ticket['status'] === 'completed' ? '#d4edda' : 
                                         ($ticket['status'] === 'in_progress' ? '#fff3cd' : '#f8f9fa'); 
                                ?>; color:<?php 
                                    echo $ticket['status'] === 'completed' ? '#155724' : 
                                         ($ticket['status'] === 'in_progress' ? '#856404' : '#666'); 
                                ?>;">
                                    <?php echo ucfirst($ticket['status']); ?>
                                </span>
                            </td>
                            <td style="padding:10px 15px;">
                                <?php if ($ticket['status'] !== 'completed'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="complete_ticket">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        <input type="text" name="resolution" placeholder="Resolution notes" style="padding:4px 8px; width:120px; border:1px solid #ddd; border-radius:4px;">
                                        <button type="submit" class="btn btn-success btn-sm">Complete</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:0.85rem; color:#888;">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="padding:15px; text-align:center; color:#888;">No maintenance tickets found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>