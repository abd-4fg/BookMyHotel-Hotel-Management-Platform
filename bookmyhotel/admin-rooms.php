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
$error = '';
$hotel_id = (int)($_GET['hotel_id'] ?? 0);
$edit_room = null;
$room_id = (int)($_GET['edit_room'] ?? 0);

// Get hotel details
$hotel = null;
if ($hotel_id) {
    $stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $hotel = $stmt->get_result()->fetch_assoc();
}

if ($room_id) {
    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $edit_room = $stmt->get_result()->fetch_assoc();
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $room_id = (int)($_POST['room_id'] ?? 0);
    $hotel_id = (int)($_POST['hotel_id'] ?? 0);
    $room_type = trim($_POST['room_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_per_night = (float)($_POST['price_per_night'] ?? 0);
    $max_occupancy = (int)($_POST['max_occupancy'] ?? 2);
    $total_rooms = (int)($_POST['total_rooms'] ?? 10);
    $available_rooms = (int)($_POST['available_rooms'] ?? 10);
    $image_url = trim($_POST['image_url'] ?? '');
    
    if ($action === 'add_room') {
        if (empty($room_type) || $price_per_night <= 0) {
            $error = 'Please fill in all required fields.';
        } else {
            $stmt = $conn->prepare("INSERT INTO rooms (hotel_id, room_type, description, price_per_night, max_occupancy, total_rooms, available_rooms, image_url) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdiii", $hotel_id, $room_type, $description, $price_per_night, $max_occupancy, $total_rooms, $available_rooms, $image_url);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Room added successfully!';
                header('Location: admin-rooms.php?hotel_id=' . $hotel_id);
                exit;
            } else {
                $error = 'Failed to add room: ' . $conn->error;
            }
        }
    } elseif ($action === 'update_room' && $room_id) {
        if (empty($room_type) || $price_per_night <= 0) {
            $error = 'Please fill in all required fields.';
        } else {
            $stmt = $conn->prepare("UPDATE rooms SET room_type = ?, description = ?, price_per_night = ?, max_occupancy = ?, total_rooms = ?, available_rooms = ?, image_url = ? WHERE id = ?");
            $stmt->bind_param("ssdiiisi", $room_type, $description, $price_per_night, $max_occupancy, $total_rooms, $available_rooms, $image_url, $room_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Room updated successfully!';
                header('Location: admin-rooms.php?hotel_id=' . $hotel_id);
                exit;
            } else {
                $error = 'Failed to update room: ' . $conn->error;
            }
        }
    } elseif ($action === 'delete_room' && $room_id) {
        $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Room deleted successfully!';
            header('Location: admin-rooms.php?hotel_id=' . $hotel_id);
            exit;
        } else {
            $error = 'Failed to delete room: ' . $conn->error;
        }
    }
}

// Get rooms for this hotel
$rooms = $conn->query("SELECT * FROM rooms WHERE hotel_id = $hotel_id ORDER BY room_type");

include 'includes/header.php';
?>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:20px;">
        <h2><i class="fas fa-door-open"></i> Rooms Management</h2>
        <a href="admin-hotels.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back to Hotels
        </a>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success animate__animated animate__fadeIn"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($hotel): ?>
        <div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.06); margin-bottom:20px;">
            <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
            <p><?php echo htmlspecialchars($hotel['location']); ?></p>
        </div>
    <?php endif; ?>
    
    <div style="display:grid; grid-template-columns: 1fr 2fr; gap:30px;">
        <!-- Left: Room List -->
        <div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
            <h3><i class="fas fa-list"></i> All Rooms</h3>
            <p style="color:#888; font-size:0.9rem;"><?php echo $rooms->num_rows; ?> rooms found</p>
            
            <?php if ($rooms->num_rows > 0): ?>
                <div style="max-height:500px; overflow-y:auto;">
                    <?php while ($room = $rooms->fetch_assoc()): ?>
                        <a href="admin-rooms.php?hotel_id=<?php echo $hotel_id; ?>&edit_room=<?php echo $room['id']; ?>" 
                           style="display:block; padding:12px 15px; margin-bottom:8px; background:<?php echo ($room_id == $room['id']) ? '#e8f0fe' : '#f8f9fa'; ?>; 
                                  border-radius:8px; text-decoration:none; color:#333; transition:background 0.3s;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong><?php echo htmlspecialchars($room['room_type']); ?></strong>
                                    <p style="margin:2px 0 0; font-size:0.85rem; color:#666;">
                                        $<?php echo number_format($room['price_per_night'], 0); ?>/night
                                    </p>
                                </div>
                                <div style="font-size:0.85rem;">
                                    <?php echo $room['available_rooms']; ?>/<?php echo $room['total_rooms']; ?> avail
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color:#888;">No rooms found for this hotel.</p>
            <?php endif; ?>
        </div>
        
        <!-- Right: Room Form -->
        <div style="background:#fff; border-radius:12px; padding:25px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
            <h3><?php echo $edit_room ? 'Edit Room' : 'Add New Room'; ?></h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_room ? 'update_room' : 'add_room'; ?>">
                <?php if ($edit_room): ?>
                    <input type="hidden" name="room_id" value="<?php echo $edit_room['id']; ?>">
                <?php endif; ?>
                <input type="hidden" name="hotel_id" value="<?php echo $hotel_id; ?>">
                
                <div class="form-group">
                    <label>Room Type <span style="color:red;">*</span></label>
                    <input type="text" name="room_type" required value="<?php echo htmlspecialchars($edit_room['room_type'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="2"><?php echo htmlspecialchars($edit_room['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Price per Night <span style="color:red;">*</span></label>
                    <input type="number" name="price_per_night" required step="0.01" value="<?php echo $edit_room['price_per_night'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Max Occupancy</label>
                    <input type="number" name="max_occupancy" value="<?php echo $edit_room['max_occupancy'] ?? 2; ?>" min="1">
                </div>
                <div class="form-group">
                    <label>Total Rooms</label>
                    <input type="number" name="total_rooms" value="<?php echo $edit_room['total_rooms'] ?? 10; ?>" min="0">
                </div>
                <div class="form-group">
                    <label>Available Rooms</label>
                    <input type="number" name="available_rooms" value="<?php echo $edit_room['available_rooms'] ?? 10; ?>" min="0">
                </div>
                <div class="form-group">
                    <label>Image URL</label>
                    <input type="text" name="image_url" value="<?php echo htmlspecialchars($edit_room['image_url'] ?? ''); ?>" 
                           placeholder="https://example.com/room.jpg">
                </div>
                
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $edit_room ? 'Update Room' : 'Add Room'; ?>
                    </button>
                    <?php if ($edit_room): ?>
                        <a href="admin-rooms.php?hotel_id=<?php echo $hotel_id; ?>" class="btn btn-outline">Cancel</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this room?');">
                            <input type="hidden" name="action" value="delete_room">
                            <input type="hidden" name="room_id" value="<?php echo $edit_room['id']; ?>">
                            <input type="hidden" name="hotel_id" value="<?php echo $hotel_id; ?>">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete Room
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>