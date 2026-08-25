<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$room_id = (int)($_GET['room_id'] ?? 0);
$error = '';
$success = '';
$room = null;
$check_in = $_POST['check_in'] ?? $_GET['check_in'] ?? '';
$check_out = $_POST['check_out'] ?? $_GET['check_out'] ?? '';
$guests = (int)($_POST['guests'] ?? $_GET['guests'] ?? 2);
$nights = 0;
$subtotal = 0;
$discount = 0;
$total = 0;
$promotion_code = '';
$payment_method = 'credit_card';
$room_available = true;
$availability_message = '';

if ($room_id) {
    $stmt = $conn->prepare("SELECT r.*, h.name as hotel_name, h.id as hotel_id FROM rooms r JOIN hotels h ON r.hotel_id = h.id WHERE r.id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
}

// Calculate initial totals if dates are set
if (!empty($check_in) && !empty($check_out) && $room) {
    $start = new DateTime($check_in);
    $end = new DateTime($check_out);
    $nights = $end->diff($start)->days;
    // Price is per night per room (base price for 2 guests)
    $base_price = $room['price_per_night'];
    // If more than 2 guests, add extra charges (50% per extra guest)
    $extra_guests = max(0, $guests - 2);
    $price_per_night = $base_price + ($base_price * 0.5 * $extra_guests);
    $subtotal = $nights * $price_per_night;
    $total = $subtotal;
    
    // Check availability for the date range
    $availability = checkDateRangeAvailability($conn, $room_id, $check_in, $check_out);
    if (!$availability['available']) {
        $room_available = false;
        $availability_message = $availability['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $room_id = (int)$_POST['room_id'];
    $guests = (int)($_POST['guests'] ?? 2);
    $promotion_code = trim($_POST['promotion_code'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'credit_card';
    
    if (empty($check_in) || empty($check_out)) {
        $error = 'Please select check-in and check-out dates.';
    } else {
        $start = new DateTime($check_in);
        $end = new DateTime($check_out);
        $nights = $end->diff($start)->days;
        
        if ($nights <= 0) {
            $error = 'Check-out must be after check-in.';
        } else {
            // Check availability first
            $availability = checkDateRangeAvailability($conn, $room_id, $check_in, $check_out);
            if (!$availability['available']) {
                $error = $availability['message'];
            } else {
                // Get room price
                $stmt = $conn->prepare("SELECT price_per_night FROM rooms WHERE id = ?");
                $stmt->bind_param("i", $room_id);
                $stmt->execute();
                $roomData = $stmt->get_result()->fetch_assoc();
                
                // Calculate price with guests
                $base_price = $roomData['price_per_night'];
                $extra_guests = max(0, $guests - 2);
                $price_per_night = $base_price + ($base_price * 0.5 * $extra_guests);
                $subtotal = $nights * $price_per_night;
                $total = $subtotal;
                $discount = 0;
                
                // Check promotion
                if (!empty($promotion_code)) {
                    $promo = validatePromotion($conn, $promotion_code, $room['hotel_id']);
                    if ($promo) {
                        $discount = ($subtotal * $promo['discount_percent']) / 100;
                        $total = $subtotal - $discount;
                    } else {
                        $error = 'Invalid or expired promotion code.';
                    }
                }
                
                if (empty($error)) {
                    // INSTANT BOOKING - No pending status
                    $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, total_price, status, payment_status, payment_method, guests) 
                                           VALUES (?, ?, ?, ?, ?, 'confirmed', 'paid', ?, ?)");
                    $stmt->bind_param("iissdsi", $_SESSION['user_id'], $room_id, $check_in, $check_out, $total, $payment_method, $guests);
                    
                    if ($stmt->execute()) {
                        $booking_id = $conn->insert_id;
                        
                        // Update room availability for each date in the range
                        updateRoomAvailabilityForDates($conn, $room_id, $check_in, $check_out);
                        
                        // Create notification
                        createNotification($conn, $_SESSION['user_id'], 'booking_confirmed', 
                            'Your booking #' . $booking_id . ' has been confirmed!', 
                            'booking', $booking_id);
                        
                        // Notify staff
                        $staff = $conn->query("SELECT id FROM users WHERE role IN ('admin', 'hotel_staff')");
                        while ($staff_user = $staff->fetch_assoc()) {
                            createNotification($conn, $staff_user['id'], 'new_booking', 
                                'New booking #' . $booking_id . ' from ' . $_SESSION['full_name'], 
                                'booking', $booking_id);
                        }
                        
                        $success = 'Booking confirmed instantly! Your room is reserved.';
                        
                        echo '<meta http-equiv="refresh" content="3;url=my-bookings.php">';
                    } else {
                        $error = 'Booking failed. Please try again.';
                    }
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width:700px;">
    <h2>Booking</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger animate__animated animate__fadeIn"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success animate__animated animate__fadeIn">
            <?php echo htmlspecialchars($success); ?>
            <p style="margin-top:10px;">Redirecting to <a href="my-bookings.php">My Bookings</a>...</p>
        </div>
    <?php endif; ?>
    
    <?php if ($room && !$success): ?>
        <div style="background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
            <div style="display:flex; gap:20px; flex-wrap:wrap;">
                <?php if ($room['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($room['image_url']); ?>" alt="<?php echo htmlspecialchars($room['room_type']); ?>" style="width:200px; height:200px; object-fit:cover; border-radius:8px;">
                <?php endif; ?>
                <div style="flex:1;">
                    <h3><?php echo htmlspecialchars($room['room_type']); ?></h3>
                    <p><strong>Hotel:</strong> <?php echo htmlspecialchars($room['hotel_name']); ?></p>
                    <p><strong>Base Price:</strong> $<?php echo number_format($room['price_per_night'], 0); ?>/night (for 2 guests)</p>
                    <p><strong>Max Occupancy:</strong> <?php echo $room['max_occupancy']; ?> guests</p>
                    <?php if (!$room_available): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($availability_message); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <form method="POST" style="margin-top:20px;" id="bookingForm">
                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Check-in</label>
                        <input type="date" name="check_in" id="check_in" required value="<?php echo htmlspecialchars($check_in); ?>" onchange="updatePrice()">
                    </div>
                    <div class="form-group">
                        <label>Check-out</label>
                        <input type="date" name="check_out" id="check_out" required value="<?php echo htmlspecialchars($check_out); ?>" onchange="updatePrice()">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Number of Guests <span style="color:red;">*</span></label>
                    <select name="guests" id="guests" required onchange="updatePrice()">
                        <?php for ($i = 1; $i <= $room['max_occupancy']; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == $guests ? 'selected' : ''; ?>>
                                <?php echo $i; ?> <?php echo $i == 1 ? 'Guest' : 'Guests'; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <small style="color:#888; display:block; margin-top:5px;">
                        <i class="fas fa-info-circle"></i> Base price is for 2 guests. Extra guests: 50% surcharge per guest.
                    </small>
                </div>
                
                <!-- Promotion Code -->
                <div class="form-group">
                    <label><i class="fas fa-ticket-alt"></i> Promotion Code</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" name="promotion_code" id="promotion_code" placeholder="Enter promo code (e.g. WELCOME10)" style="flex:1;" 
                               value="<?php echo htmlspecialchars($promotion_code); ?>" oninput="updatePrice()">
                        <button type="button" class="btn btn-outline" onclick="applyPromotion()">Apply</button>
                    </div>
                    <div id="promo_message" style="margin-top:5px;"></div>
                </div>
                
                <!-- Payment Methods -->
                <div class="form-group">
                    <label><i class="fas fa-credit-card"></i> Payment Method</label>
                    <select name="payment_method" id="payment_method" required>
                        <option value="credit_card">💳 Credit Card</option>
                        <option value="debit_card">🏦 Debit Card</option>
                        <option value="paypal">🅿️ PayPal</option>
                        <option value="apple_pay">🍎 Apple Pay</option>
                        <option value="google_pay">🤖 Google Pay</option>
                        <option value="bank_transfer">🏛️ Bank Transfer</option>
                    </select>
                    <small style="color:#888; display:block; margin-top:5px;">
                        <i class="fas fa-lock"></i>
                    </small>
                </div>
                
                <!-- Price Breakdown - REAL TIME -->
                <div style="background:#f8f9fa; padding:15px; border-radius:8px; margin:15px 0;">
                    <p><strong>Price Breakdown:</strong></p>
                    <p id="price_per_night_display">Price per night: $<?php echo number_format($room['price_per_night'], 2); ?></p>
                    <p id="guests_display">Guests: <?php echo $guests; ?></p>
                    <p id="nights_display">Nights: <?php echo $nights; ?></p>
                    <p id="subtotal_display">Subtotal: $<?php echo number_format($subtotal, 2); ?></p>
                    <p id="discount_display" style="color:#27ae60;">Discount: -$<?php echo number_format($discount, 2); ?></p>
                    <p id="total_display" style="font-size:1.2rem; font-weight:700; color:#1a2a6c;">Total: $<?php echo number_format($total, 2); ?></p>
                </div>
                
                <button type="submit" class="btn btn-success" style="width:100%; padding:14px; font-size:1.1rem;" id="bookButton" <?php echo !$room_available ? 'disabled' : ''; ?>>
                    <i class="fas fa-check-circle"></i> Confirm Booking
                </button>
                
                <div style="margin-top:15px; padding:12px; background:#fff3cd; border-radius:8px; border:1px solid #ffc107;">
                    <p style="margin:0; font-size:0.9rem;">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Cancellation Policy:</strong> Free cancellation up to 48 hours before check-in.
                    </p>
                </div>
            </form>
        </div>
    <?php elseif (!$room && !$success): ?>
        <div class="alert alert-danger">Room not found.</div>
    <?php endif; ?>
</div>

<script>
function updatePrice() {
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    const guests = parseInt(document.getElementById('guests').value) || 2;
    const basePrice = <?php echo $room['price_per_night'] ?? 0; ?>;
    
    if (checkIn && checkOut) {
        const start = new Date(checkIn);
        const end = new Date(checkOut);
        const nights = Math.max(0, Math.ceil((end - start) / (1000 * 60 * 60 * 24)));
        
        // Calculate price based on guests
        const extraGuests = Math.max(0, guests - 2);
        const pricePerNight = basePrice + (basePrice * 0.5 * extraGuests);
        let subtotal = nights * pricePerNight;
        
        let discount = 0;
        const promoInput = document.getElementById('promotion_code');
        if (promoInput.dataset.discount) {
            discount = (subtotal * parseFloat(promoInput.dataset.discount)) / 100;
        }
        
        const total = subtotal - discount;
        
        document.getElementById('price_per_night_display').textContent = `Price per night: $${pricePerNight.toFixed(2)} (${guests} guests)`;
        document.getElementById('guests_display').textContent = `Guests: ${guests}`;
        document.getElementById('nights_display').textContent = `Nights: ${nights}`;
        document.getElementById('subtotal_display').textContent = `Subtotal: $${subtotal.toFixed(2)}`;
        document.getElementById('discount_display').textContent = `Discount: -$${discount.toFixed(2)}`;
        document.getElementById('total_display').textContent = `Total: $${total.toFixed(2)}`;
    }
}

function applyPromotion() {
    const code = document.getElementById('promotion_code').value.trim();
    if (!code) {
        document.getElementById('promo_message').innerHTML = '<span style="color:#888;">Please enter a promotion code.</span>';
        return;
    }
    
    // Valid promotion codes
    const validCodes = {
        'WELCOME10': 10,
        'SUMMER20': 20,
        'SUMMER25': 25,
        'HILTON15': 15,
        'HILTON20': 20,
        'HYATT10': 10,
        'PARIS15': 15,
        'FOURSEASONS25': 25,
        'THAILAND30': 30,
        'MARRIOTT10': 10
    };
    
    if (validCodes[code]) {
        document.getElementById('promo_message').innerHTML = `
            <span style="color:#27ae60;">
                <i class="fas fa-check-circle"></i> Promotion applied! ${validCodes[code]}% discount.
            </span>
        `;
        document.getElementById('promotion_code').dataset.discount = validCodes[code];
    } else {
        document.getElementById('promo_message').innerHTML = `
            <span style="color:#e74c3c;">
                <i class="fas fa-times-circle"></i> Invalid or expired promotion code.
            </span>
        `;
        document.getElementById('promotion_code').dataset.discount = '0';
    }
    updatePrice();
}

document.addEventListener('DOMContentLoaded', function() {
    const checkIn = document.getElementById('check_in');
    const checkOut = document.getElementById('check_out');
    
    if (checkIn && checkOut) {
        const today = new Date().toISOString().split('T')[0];
        checkIn.setAttribute('min', today);
        checkOut.setAttribute('min', today);
        
        checkIn.addEventListener('change', function() {
            checkOut.setAttribute('min', this.value);
            if (checkOut.value && checkOut.value < this.value) {
                checkOut.value = this.value;
            }
            updatePrice();
        });
        
        checkOut.addEventListener('change', updatePrice);
        document.getElementById('guests').addEventListener('change', updatePrice);
        updatePrice();
    }
});
</script>

<?php include 'includes/footer.php'; ?>