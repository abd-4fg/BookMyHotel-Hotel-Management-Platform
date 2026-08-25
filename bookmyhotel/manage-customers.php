<?php
require_once 'config/database.php';

// Only Staff or Admin can access this page
if (!isStaffOrAdmin()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$error = '';

// Get all customers
$customers = $conn->query("SELECT id, full_name, email, phone, address, role, created_at, updated_at FROM users WHERE role = 'customer' ORDER BY id DESC");

// Get customer details for editing
$edit_customer = null;
$edit_bookings = [];
$customer_id = (int)($_GET['edit'] ?? 0);

if ($customer_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $edit_customer = $stmt->get_result()->fetch_assoc();
    
    if ($edit_customer) {
        // Get customer bookings
        $bookingsQuery = $conn->prepare("SELECT b.*, r.room_type, h.name as hotel_name, h.id as hotel_id 
                                        FROM bookings b 
                                        JOIN rooms r ON b.room_id = r.id 
                                        JOIN hotels h ON r.hotel_id = h.id 
                                        WHERE b.user_id = ? 
                                        ORDER BY b.booking_date DESC");
        $bookingsQuery->bind_param("i", $customer_id);
        $bookingsQuery->execute();
        $edit_bookings = $bookingsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    
    // Update customer profile
    if ($action === 'update_customer' && $customer_id) {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($full_name) || empty($email)) {
            $error = 'Please fill in all required fields.';
        } else {
            // Check if email exists for other user
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->bind_param("si", $email, $customer_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = 'Email already in use by another account.';
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $customer_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Customer account updated successfully!';
                    header('Location: manage-customers.php?edit=' . $customer_id);
                    exit;
                } else {
                    $error = 'Update failed. Please try again.';
                }
            }
        }
    }
    
    // Reset customer password
    if ($action === 'reset_password' && $customer_id) {
        $new_password = $_POST['new_password'] ?? '';
        if (strlen($new_password) >= 6) {
            // Plain text for staff/admin created accounts
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_password, $customer_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Password reset successfully! New password: ' . $new_password;
                header('Location: manage-customers.php?edit=' . $customer_id);
                exit;
            } else {
                $error = 'Password reset failed.';
            }
        } else {
            $error = 'Password must be at least 6 characters.';
        }
    }
    
    // Delete customer
    if ($action === 'delete_customer' && $customer_id) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'");
        $stmt->bind_param("i", $customer_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Customer account deleted successfully!';
            header('Location: manage-customers.php');
            exit;
        } else {
            $error = 'Delete failed. Please try again.';
        }
    }
    
    // Cancel booking (admin/staff)
    if ($action === 'cancel_booking' && $customer_id) {
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        if ($booking_id) {
            $conn->query("UPDATE bookings SET status = 'cancelled' WHERE id = $booking_id");
            
            // Notify customer
            $bookingData = $conn->query("SELECT user_id FROM bookings WHERE id = $booking_id")->fetch_assoc();
            if ($bookingData) {
                createNotification($conn, $bookingData['user_id'], 'booking_cancelled', 
                    'Your booking #' . $booking_id . ' has been cancelled by staff.', 
                    'my-bookings.php', $booking_id);
            }
            
            $_SESSION['message'] = 'Booking cancelled successfully!';
            header('Location: manage-customers.php?edit=' . $customer_id);
            exit;
        }
    }
    
    // Confirm booking (admin/staff)
    if ($action === 'confirm_booking' && $customer_id) {
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        if ($booking_id) {
            $conn->query("UPDATE bookings SET status = 'confirmed', payment_status = 'paid' WHERE id = $booking_id");
            
            // Notify customer
            $bookingData = $conn->query("SELECT user_id FROM bookings WHERE id = $booking_id")->fetch_assoc();
            if ($bookingData) {
                createNotification($conn, $bookingData['user_id'], 'booking_confirmed', 
                    'Your booking #' . $booking_id . ' has been confirmed!', 
                    'my-bookings.php', $booking_id);
            }
            
            $_SESSION['message'] = 'Booking confirmed successfully!';
            header('Location: manage-customers.php?edit=' . $customer_id);
            exit;
        }
    }
    
    // Refund booking (admin/staff)
    if ($action === 'refund_booking' && $customer_id) {
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        if ($booking_id) {
            $conn->query("UPDATE bookings SET payment_status = 'refunded' WHERE id = $booking_id");
            
            // Notify customer
            $bookingData = $conn->query("SELECT user_id FROM bookings WHERE id = $booking_id")->fetch_assoc();
            if ($bookingData) {
                createNotification($conn, $bookingData['user_id'], 'refund_processed', 
                    'Your booking #' . $booking_id . ' has been refunded. Please allow 5-7 business days for the refund to appear in your account.', 
                    'my-bookings.php', $booking_id);
            }
            
            $_SESSION['message'] = 'Refund processed successfully!';
            header('Location: manage-customers.php?edit=' . $customer_id);
            exit;
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <h2><i class="fas fa-users-cog"></i> Customer Management</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success animate__animated animate__fadeIn"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Customer List -->
    <div style="display:grid; grid-template-columns: 1fr 2fr; gap:30px;">
        <!-- Left: Customer List -->
        <div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
            <h3><i class="fas fa-list"></i> All Customers</h3>
            <p style="color:#888; font-size:0.9rem;"><?php echo $customers->num_rows; ?> customers found</p>
            
            <?php if ($customers->num_rows > 0): ?>
                <div style="max-height:600px; overflow-y:auto;">
                    <?php while ($customer = $customers->fetch_assoc()): ?>
                        <a href="manage-customers.php?edit=<?php echo $customer['id']; ?>" 
                           style="display:block; padding:12px 15px; margin-bottom:8px; background:<?php echo ($customer_id == $customer['id']) ? '#e8f0fe' : '#f8f9fa'; ?>; 
                                  border-radius:8px; text-decoration:none; color:#333; transition:background 0.3s;"
                           onmouseover="this.style.background='#e8f0fe'" onmouseout="this.style.background='<?php echo ($customer_id == $customer['id']) ? '#e8f0fe' : '#f8f9fa'; ?>'">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong>
                                    <p style="margin:2px 0 0; font-size:0.85rem; color:#666;"><?php echo htmlspecialchars($customer['email']); ?></p>
                                </div>
                                <i class="fas fa-chevron-right" style="color:#1a2a6c;"></i>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color:#888;">No customers registered yet.</p>
            <?php endif; ?>
        </div>
        
        <!-- Right: Customer Details -->
        <div>
            <?php if ($edit_customer): ?>
                <!-- Customer Profile -->
                <div style="background:#fff; border-radius:12px; padding:25px; box-shadow:0 2px 10px rgba(0,0,0,0.06); margin-bottom:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                        <h3 style="margin:0;"><i class="fas fa-user"></i> Customer Profile</h3>
                        <span style="background:#e8f5e9; padding:4px 15px; border-radius:20px; font-size:0.85rem; color:#2e7d32;">
                            <i class="fas fa-check-circle"></i> Active
                        </span>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_customer">
                        <input type="hidden" name="customer_id" value="<?php echo $edit_customer['id']; ?>">
                        
                        <div class="form-group">
                            <label>Full Name <span style="color:red;">*</span></label>
                            <input type="text" name="full_name" required value="<?php echo htmlspecialchars($edit_customer['full_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Email <span style="color:red;">*</span></label>
                            <input type="email" name="email" required value="<?php echo htmlspecialchars($edit_customer['email']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($edit_customer['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" rows="2"><?php echo htmlspecialchars($edit_customer['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                            <a href="manage-customers.php" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                    
                    <hr style="margin:20px 0; border-color:#eee;">
                    
                    <!-- Reset Password -->
                    <h4><i class="fas fa-key"></i> Reset Password</h4>
                    <form method="POST" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="customer_id" value="<?php echo $edit_customer['id']; ?>">
                        <input type="text" name="new_password" required placeholder="Enter new password (min 6 chars)" style="flex:1; min-width:200px; padding:8px 12px; border:1px solid #ddd; border-radius:6px;">
                        <button type="submit" class="btn btn-warning" style="background:#f39c12; color:#fff;">
                            <i class="fas fa-sync-alt"></i> Reset Password
                        </button>
                    </form>
                    
                    <hr style="margin:20px 0; border-color:#eee;">
                    
                    <!-- Delete Account -->
                    <h4 style="color:#e74c3c;"><i class="fas fa-trash"></i> Delete Account</h4>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this customer account? This action cannot be undone.');">
                        <input type="hidden" name="action" value="delete_customer">
                        <input type="hidden" name="customer_id" value="<?php echo $edit_customer['id']; ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Customer Account
                        </button>
                    </form>
                </div>
                
                <!-- Customer Bookings -->
                <div style="background:#fff; border-radius:12px; padding:25px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
                    <h3><i class="fas fa-calendar-check"></i> Customer Bookings</h3>
                    
                    <?php if (empty($edit_bookings)): ?>
                        <p style="color:#888;">This customer has no bookings yet.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                                <thead>
                                    <tr style="background:#f8f9fa; border-bottom:2px solid #ddd;">
                                        <th style="padding:10px 12px; text-align:left;">Hotel</th>
                                        <th style="padding:10px 12px; text-align:left;">Room</th>
                                        <th style="padding:10px 12px; text-align:left;">Guests</th>
                                        <th style="padding:10px 12px; text-align:left;">Check-in</th>
                                        <th style="padding:10px 12px; text-align:left;">Check-out</th>
                                        <th style="padding:10px 12px; text-align:left;">Total</th>
                                        <th style="padding:10px 12px; text-align:left;">Status</th>
                                        <th style="padding:10px 12px; text-align:left;">Payment</th>
                                        <th style="padding:10px 12px; text-align:left;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($edit_bookings as $booking): ?>
                                        <tr style="border-bottom:1px solid #eee;">
                                            <td style="padding:10px 12px;"><?php echo htmlspecialchars($booking['hotel_name']); ?></td>
                                            <td style="padding:10px 12px;"><?php echo htmlspecialchars($booking['room_type']); ?></td>
                                            <td style="padding:10px 12px;"><?php echo $booking['guests'] ?? 2; ?></td>
                                            <td style="padding:10px 12px;"><?php echo date('M d', strtotime($booking['check_in'])); ?></td>
                                            <td style="padding:10px 12px;"><?php echo date('M d', strtotime($booking['check_out'])); ?></td>
                                            <td style="padding:10px 12px;">$<?php echo number_format($booking['total_price'], 2); ?></td>
                                            <td style="padding:10px 12px;">
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
                                            <td style="padding:10px 12px;">
                                                <span style="padding:2px 10px; border-radius:12px; background:<?php 
                                                    echo $booking['payment_status'] === 'paid' ? '#d4edda' : 
                                                         ($booking['payment_status'] === 'refunded' ? '#f8d7da' : '#e9ecef'); 
                                                ?>; color:<?php 
                                                    echo $booking['payment_status'] === 'paid' ? '#155724' : 
                                                         ($booking['payment_status'] === 'refunded' ? '#721c24' : '#666'); 
                                                ?>; font-size:0.7rem;">
                                                    <?php echo ucfirst($booking['payment_status'] ?? 'pending'); ?>
                                                </span>
                                            </td>
                                            <td style="padding:10px 12px;">
                                                <?php if ($booking['status'] === 'pending'): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="confirm_booking">
                                                        <input type="hidden" name="customer_id" value="<?php echo $edit_customer['id']; ?>">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Confirm this booking?');">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'completed'): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="cancel_booking">
                                                        <input type="hidden" name="customer_id" value="<?php echo $edit_customer['id']; ?>">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this booking?');">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($booking['status'] === 'cancelled' && $booking['payment_status'] === 'paid'): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="refund_booking">
                                                        <input type="hidden" name="customer_id" value="<?php echo $edit_customer['id']; ?>">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Process refund for this booking?');">
                                                            <i class="fas fa-money-bill-wave"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($booking['status'] === 'cancelled' && $booking['payment_status'] === 'refunded'): ?>
                                                    <span style="color:#27ae60; font-size:0.75rem;">Refunded</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <div style="background:#fff; border-radius:12px; padding:40px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
                    <i class="fas fa-users" style="font-size:4rem; color:#ddd;"></i>
                    <h3 style="color:#888; margin-top:15px;">Select a customer from the list</h3>
                    <p style="color:#aaa;">Click on any customer to view and manage their account</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.btn-warning {
    background: #f39c12;
    color: #fff;
}
.btn-warning:hover {
    background: #d68910;
    color: #fff;
}
.btn-sm {
    padding: 4px 10px;
    font-size: 0.8rem;
}
</style>

<?php include 'includes/footer.php'; ?>