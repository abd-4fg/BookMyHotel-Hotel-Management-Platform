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
$edit_hotel = null;
$hotel_id = (int)($_GET['edit'] ?? 0);

if ($hotel_id) {
    $stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_hotel = $result->fetch_assoc();
    
    if (!$edit_hotel) {
        $error = 'Hotel not found.';
        $edit_hotel = null;
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $hotel_id = (int)($_POST['hotel_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $star_rating = (int)($_POST['star_rating'] ?? 5);
    $facilities = trim($_POST['facilities'] ?? '');
    $image = trim($_POST['image'] ?? '');
    
    // Debug: Log the action
    error_log("=== FORM SUBMITTED ===");
    error_log("Action: " . $action);
    error_log("Hotel ID: " . $hotel_id);
    error_log("Name: " . $name);
    
    // UPDATE HOTEL
    if ($action === 'update_hotel') {
        if (empty($name) || empty($location)) {
            $error = 'Please fill in all required fields.';
        } elseif ($hotel_id <= 0) {
            $error = 'Invalid hotel ID.';
        } else {
            // Verify the hotel exists
            $check = $conn->query("SELECT id FROM hotels WHERE id = $hotel_id");
            if ($check->num_rows === 0) {
                $error = 'Hotel not found in database.';
            } else {
                // Update the hotel using prepared statement
                $stmt = $conn->prepare("UPDATE hotels SET name = ?, description = ?, location = ?, star_rating = ?, facilities = ?, image = ? WHERE id = ?");
                $stmt->bind_param("sssissi", $name, $description, $location, $star_rating, $facilities, $image, $hotel_id);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Hotel updated successfully!';
                    header('Location: admin-hotels.php');
                    exit;
                } else {
                    $error = 'Failed to update hotel: ' . $stmt->error;
                }
            }
        }
    } 
    // ADD HOTEL
    elseif ($action === 'add_hotel') {
        if (empty($name) || empty($location)) {
            $error = 'Please fill in all required fields.';
        } else {
            $stmt = $conn->prepare("INSERT INTO hotels (name, description, location, star_rating, facilities, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiss", $name, $description, $location, $star_rating, $facilities, $image);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Hotel added successfully!';
                header('Location: admin-hotels.php');
                exit;
            } else {
                $error = 'Failed to add hotel: ' . $conn->error;
            }
        }
    } 
    // DELETE HOTEL
    elseif ($action === 'delete_hotel') {
        if ($hotel_id <= 0) {
            $error = 'Invalid hotel ID.';
        } else {
            // Check if hotel has rooms
            $check = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE hotel_id = $hotel_id");
            $room_count = $check->fetch_assoc()['count'];
            
            if ($room_count > 0) {
                // Delete rooms first
                $conn->query("DELETE FROM rooms WHERE hotel_id = $hotel_id");
            }
            
            // Delete the hotel
            $stmt = $conn->prepare("DELETE FROM hotels WHERE id = ?");
            $stmt->bind_param("i", $hotel_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Hotel and all associated rooms deleted successfully!';
                header('Location: admin-hotels.php');
                exit;
            } else {
                $error = 'Failed to delete hotel: ' . $conn->error;
            }
        }
    }
}

// Get all hotels
$hotels = $conn->query("SELECT * FROM hotels ORDER BY name");

include 'includes/header.php';
?>

<div class="container">
    <h2><i class="fas fa-building"></i> Hotel Management</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success animate__animated animate__fadeIn"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div style="display:grid; grid-template-columns: 1fr 2fr; gap:30px;">
        <!-- Left: Hotel List -->
        <div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
            <h3><i class="fas fa-list"></i> All Hotels</h3>
            <p style="color:#888; font-size:0.9rem;"><?php echo $hotels->num_rows; ?> hotels found</p>
            
            <?php if ($hotels->num_rows > 0): ?>
                <div style="max-height:500px; overflow-y:auto;">
                    <?php while ($hotel = $hotels->fetch_assoc()): ?>
                        <a href="admin-hotels.php?edit=<?php echo $hotel['id']; ?>" 
                           style="display:block; padding:12px 15px; margin-bottom:8px; background:<?php echo ($hotel_id == $hotel['id']) ? '#e8f0fe' : '#f8f9fa'; ?>; 
                                  border-radius:8px; text-decoration:none; color:#333; transition:background 0.3s;"
                           onmouseover="this.style.background='#e8f0fe'" onmouseout="this.style.background='<?php echo ($hotel_id == $hotel['id']) ? '#e8f0fe' : '#f8f9fa'; ?>'">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong><?php echo htmlspecialchars($hotel['name']); ?></strong>
                                    <p style="margin:2px 0 0; font-size:0.85rem; color:#666;"><?php echo htmlspecialchars($hotel['location']); ?></p>
                                </div>
                                <div>
                                    <?php echo str_repeat('★', $hotel['star_rating']); ?>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color:#888;">No hotels found.</p>
            <?php endif; ?>
            
            <hr style="margin:15px 0;">
            <a href="admin-hotels.php" class="btn btn-primary" style="width:100%; text-align:center;">
                <i class="fas fa-plus"></i> Add New Hotel
            </a>
        </div>
        
        <!-- Right: Hotel Form -->
        <div style="background:#fff; border-radius:12px; padding:25px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
            <h3><?php echo $edit_hotel ? '✏️ Edit Hotel' : '➕ Add New Hotel'; ?></h3>
            
            <?php if ($edit_hotel): ?>
                <div style="background:#e8f0fe; padding:10px 15px; border-radius:8px; margin-bottom:15px; border:1px solid #1a2a6c;">
                    <strong>Editing:</strong> <?php echo htmlspecialchars($edit_hotel['name']); ?> (ID: <?php echo $edit_hotel['id']; ?>)
                </div>
            <?php endif; ?>
            
            <!-- MAIN FORM - Update Hotel -->
            <form method="POST" id="hotelForm" onsubmit="return confirm('<?php echo $edit_hotel ? 'Update this hotel?' : 'Add this hotel?'; ?>');">
                <?php if ($edit_hotel): ?>
                    <input type="hidden" name="action" value="update_hotel">
                    <input type="hidden" name="hotel_id" value="<?php echo $edit_hotel['id']; ?>">
                <?php else: ?>
                    <input type="hidden" name="action" value="add_hotel">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Hotel Name <span style="color:red;">*</span></label>
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($edit_hotel['name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Location <span style="color:red;">*</span></label>
                    <input type="text" name="location" required value="<?php echo htmlspecialchars($edit_hotel['location'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"><?php echo htmlspecialchars($edit_hotel['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Star Rating</label>
                    <select name="star_rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($edit_hotel && $edit_hotel['star_rating'] == $i) ? 'selected' : ''; ?>>
                                <?php echo str_repeat('★', $i); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Facilities</label>
                    <input type="text" name="facilities" value="<?php echo htmlspecialchars($edit_hotel['facilities'] ?? ''); ?>" 
                           placeholder="Comma separated values (e.g. Pool, Spa, Gym)">
                </div>
                <div class="form-group">
                    <label>Image URL</label>
                    <input type="text" name="image" value="<?php echo htmlspecialchars($edit_hotel['image'] ?? ''); ?>" 
                           placeholder="https://example.com/hotel.jpg">
                </div>
                
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $edit_hotel ? 'Update Hotel' : 'Add Hotel'; ?>
                    </button>
                    <?php if ($edit_hotel): ?>
                        <a href="admin-hotels.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- DELETE FORM - Separate form to avoid action confusion -->
            <?php if ($edit_hotel): ?>
                <hr style="margin:25px 0; border-color:#eee;">
                <div style="border:1px solid #f8d7da; padding:15px; border-radius:8px; background:#f8f9fa;">
                    <h4 style="color:#e74c3c;"><i class="fas fa-trash"></i> Danger Zone</h4>
                    <p style="font-size:0.9rem; color:#888;">This action cannot be undone. This will permanently delete the hotel and all associated rooms.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this hotel? This will also delete all associated rooms and bookings.');">
                        <input type="hidden" name="action" value="delete_hotel">
                        <input type="hidden" name="hotel_id" value="<?php echo $edit_hotel['id']; ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Hotel Permanently
                        </button>
                    </form>
                </div>
                
                <!-- Rooms Management for this hotel -->
                <hr style="margin:25px 0; border-color:#eee;">
                <h4><i class="fas fa-door-open"></i> Rooms for <?php echo htmlspecialchars($edit_hotel['name']); ?></h4>
                
                <?php
                $rooms = $conn->query("SELECT * FROM rooms WHERE hotel_id = " . $edit_hotel['id']);
                if ($rooms->num_rows > 0): ?>
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                            <thead>
                                <tr style="background:#f8f9fa;">
                                    <th style="padding:8px 10px; text-align:left;">Room Type</th>
                                    <th style="padding:8px 10px; text-align:left;">Price</th>
                                    <th style="padding:8px 10px; text-align:left;">Total</th>
                                    <th style="padding:8px 10px; text-align:left;">Available</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($room = $rooms->fetch_assoc()): ?>
                                    <tr style="border-bottom:1px solid #eee;">
                                        <td style="padding:8px 10px;"><?php echo htmlspecialchars($room['room_type']); ?></td>
                                        <td style="padding:8px 10px;">$<?php echo number_format($room['price_per_night'], 0); ?></td>
                                        <td style="padding:8px 10px;"><?php echo $room['total_rooms']; ?></td>
                                        <td style="padding:8px 10px;"><?php echo $room['available_rooms']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:#888;">No rooms added for this hotel yet.</p>
                <?php endif; ?>
                
                <a href="admin-rooms.php?hotel_id=<?php echo $edit_hotel['id']; ?>" class="btn btn-outline btn-sm" style="margin-top:10px;">
                    <i class="fas fa-plus"></i> Manage Rooms
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Debug: Show current hotel data -->
<?php if ($edit_hotel): ?>
    <div style="background:#f8f9fa; padding:15px; border-radius:8px; margin-top:20px; border:1px solid #ddd; font-size:0.85rem;">
        <strong>Debug Info:</strong><br>
        Hotel ID: <?php echo $edit_hotel['id']; ?><br>
        Name: <?php echo htmlspecialchars($edit_hotel['name']); ?><br>
        Location: <?php echo htmlspecialchars($edit_hotel['location']); ?><br>
        Description: <?php echo htmlspecialchars($edit_hotel['description']); ?><br>
        Star Rating: <?php echo $edit_hotel['star_rating']; ?><br>
        Facilities: <?php echo htmlspecialchars($edit_hotel['facilities']); ?><br>
        Image: <?php echo htmlspecialchars($edit_hotel['image']); ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>