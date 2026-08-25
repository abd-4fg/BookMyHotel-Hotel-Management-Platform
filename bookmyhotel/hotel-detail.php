<?php
require_once 'config/database.php';
$conn = getConnection();

$hotel_id = (int)($_GET['id'] ?? 0);
if (!$hotel_id) {
    header('Location: hotels.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$hotel = $stmt->get_result()->fetch_assoc();

if (!$hotel) {
    header('Location: hotels.php');
    exit;
}

$roomsQuery = $conn->prepare("SELECT * FROM rooms WHERE hotel_id = ?");
$roomsQuery->bind_param("i", $hotel_id);
$roomsQuery->execute();
$rooms = $roomsQuery->get_result()->fetch_all(MYSQLI_ASSOC);

$servicesQuery = $conn->prepare("SELECT * FROM services WHERE hotel_id = ?");
$servicesQuery->bind_param("i", $hotel_id);
$servicesQuery->execute();
$services = $servicesQuery->get_result()->fetch_all(MYSQLI_ASSOC);

// Get hotel reviews
$reviewsQuery = $conn->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.hotel_id = ? ORDER BY r.created_at DESC LIMIT 10");
$reviewsQuery->bind_param("i", $hotel_id);
$reviewsQuery->execute();
$reviews = $reviewsQuery->get_result()->fetch_all(MYSQLI_ASSOC);

// Get average rating
$avgQuery = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE hotel_id = ?");
$avgQuery->bind_param("i", $hotel_id);
$avgQuery->execute();
$avgResult = $avgQuery->get_result()->fetch_assoc();

// Check if user can review (has confirmed booking)
$can_review = false;
if (isLoggedIn()) {
    $can_review = hasConfirmedBooking($conn, $_SESSION['user_id'], $hotel_id);
}

include 'includes/header.php';
?>

<div class="container">
    <a href="hotels.php" class="btn btn-outline" style="margin-bottom:20px;">
        <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
    </a>
    
    <div style="background:#fff; border-radius:12px; padding:30px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
        <h2><?php echo htmlspecialchars($hotel['name']); ?></h2>
        <p>
            <?php echo str_repeat('★', $hotel['star_rating']); ?>
            <i class="fas fa-map-marker-alt" style="margin-left:15px;"></i>
            <?php echo htmlspecialchars($hotel['location']); ?>
        </p>
        
        <?php if ($avgResult && $avgResult['total_reviews'] > 0): ?>
            <p>
                <?php echo number_format($avgResult['avg_rating'], 1); ?> / 5 
                (<?php echo $avgResult['total_reviews']; ?> <?php echo t('reviews'); ?>)
            </p>
        <?php endif; ?>
        
        <p style="margin:15px 0;"><?php echo htmlspecialchars($hotel['description']); ?></p>
        <p><strong><?php echo t('facilities'); ?>:</strong> <?php echo htmlspecialchars($hotel['facilities']); ?></p>
        
        <!-- Sustainability Badge -->
        <div style="background:#e8f5e9; padding:10px 15px; border-radius:8px; margin-top:15px;">
            <i class="fas fa-leaf" style="color:#2e7d32;"></i>
            <span style="color:#2e7d32;"><?php echo t('sustainable_practices'); ?></span>
        </div>
    </div>
    
    <!-- Rooms with Full Details -->
    <h3 style="margin:30px 0 15px;"><?php echo t('rooms'); ?></h3>
    <div class="card-grid">
        <?php foreach ($rooms as $room): ?>
            <div class="card room-card" style="cursor:pointer;" onclick="toggleRoomDetails(<?php echo $room['id']; ?>)">
                <?php if ($room['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($room['image_url']); ?>" alt="<?php echo htmlspecialchars($room['room_type']); ?>" style="width:100%; height:200px; object-fit:cover;">
                <?php else: ?>
                    <div class="card-img" style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); display:flex; align-items:center; justify-content:center; color:#fff; font-size:3rem; height:200px;">
                        <i class="fas fa-bed"></i>
                    </div>
                <?php endif; ?>
                <div class="card-body">
                    <h4><?php echo htmlspecialchars($room['room_type']); ?></h4>
                    <p><?php echo htmlspecialchars($room['description']); ?></p>
                    <p><i class="fas fa-users"></i> <?php echo t('max_occupancy'); ?>: <?php echo $room['max_occupancy']; ?> <?php echo t('guests'); ?></p>
                    
                    <!-- Room Details Accordion -->
                    <div id="room-details-<?php echo $room['id']; ?>" style="display:none; margin-top:15px; padding:15px; background:#f8f9fa; border-radius:8px;">
                        <h5><?php echo t('amenities'); ?></h5>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:0.9rem;">
                            <div>
                                <strong><?php echo t('bedding'); ?>:</strong>
                                <ul style="margin:5px 0; padding-left:20px;">
                                    <li>2 Double Beds</li>
                                    <li>Rollaway beds not permitted</li>
                                    <li>Cribs permitted: 1</li>
                                </ul>
                            </div>
                            <div>
                                <strong><?php echo t('bathroom'); ?>:</strong>
                                <ul style="margin:5px 0; padding-left:20px;">
                                    <li>Marble bathroom</li>
                                    <li>Shower</li>
                                    <li>Bathtub</li>
                                    <li>Hair dryer</li>
                                </ul>
                            </div>
                            <div>
                                <strong><?php echo t('internet'); ?>:</strong>
                                <ul style="margin:5px 0; padding-left:20px;">
                                    <li>Wireless internet, complimentary</li>
                                    <li>Wi-Fi is always free</li>
                                </ul>
                            </div>
                            <div>
                                <strong><?php echo t('entertainment'); ?>:</strong>
                                <ul style="margin:5px 0; padding-left:20px;">
                                    <li>Premium movie channels</li>
                                    <li>Cable/satellite</li>
                                    <li>CNN, ESPN, and HBO</li>
                                </ul>
                            </div>
                        </div>
                        <div style="margin-top:10px;">
                            <strong><?php echo t('room_features'); ?>:</strong>
                            <ul style="margin:5px 0; padding-left:20px;">
                                <li>185sqft/17sqm</li>
                                <li>Air-conditioned</li>
                                <li>This room is non-smoking</li>
                                <li>Safe, in room, for a fee</li>
                                <li>Desk, writing/work, electrical outlet</li>
                            </ul>
                        </div>
                    </div>
                    <button class="btn btn-outline btn-sm" onclick="event.stopPropagation(); toggleRoomDetails(<?php echo $room['id']; ?>);" style="margin-top:10px;">
                        <i class="fas fa-chevron-down"></i> <?php echo t('view_details'); ?>
                    </button>
                    
                    <div class="card-footer" style="margin-top:15px;">
                        <span class="price">$<?php echo number_format($room['price_per_night'], 0); ?>/<?php echo t('night'); ?></span>
                        <?php if (isLoggedIn() && $room['available_rooms'] > 0): ?>
                            <a href="booking.php?room_id=<?php echo $room['id']; ?>" class="btn btn-success"><?php echo t('book_now'); ?></a>
                        <?php elseif (!isLoggedIn()): ?>
                            <a href="login.php" class="btn btn-primary"><?php echo t('login'); ?></a>
                        <?php else: ?>
                            <span class="btn btn-danger" style="opacity:0.6; cursor:not-allowed;"><?php echo t('sold_out'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Calendar View for Room Availability -->
    <h3 style="margin:30px 0 15px;"><?php echo t('availability_calendar'); ?></h3>
    <div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap;">
            <h4 style="margin:0;"><?php echo t('room_availability'); ?></h4>
            <div>
                <button onclick="changeCalendarMonth(-1)" class="btn btn-outline btn-sm">‹</button>
                <span id="calendar_month" style="margin:0 15px; font-weight:600;"></span>
                <button onclick="changeCalendarMonth(1)" class="btn btn-outline btn-sm">›</button>
            </div>
        </div>
        <div id="calendar_grid" style="display:grid; grid-template-columns:repeat(7,1fr); gap:5px;">
            <!-- Calendar will be rendered by JavaScript -->
        </div>
        <div style="margin-top:10px; font-size:0.85rem; color:#888; display:flex; gap:15px; flex-wrap:wrap;">
            <span><span style="display:inline-block; width:15px; height:15px; background:#e8f5e9; border-radius:3px;"></span> <?php echo t('available'); ?></span>
            <span><span style="display:inline-block; width:15px; height:15px; background:#f5f5f5; border-radius:3px; border:1px solid #e0e0e0;"></span> <?php echo t('unavailable'); ?></span>
            <span><span style="display:inline-block; width:15px; height:15px; background:#1a2a6c; border:2px solid #1a2a6c; border-radius:3px;"></span> <?php echo t('today'); ?></span>
        </div>
    </div>
    
    <!-- Services -->
    <?php if ($services): ?>
        <h3 style="margin:30px 0 15px;"><?php echo t('services'); ?></h3>
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:15px;">
            <?php foreach ($services as $service): ?>
                <div style="background:#fff; padding:15px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <strong><?php echo htmlspecialchars($service['service_name']); ?></strong>
                    <p style="font-size:0.9rem; color:#666;"><?php echo htmlspecialchars($service['description']); ?></p>
                    <?php if ($service['price']): ?>
                        <p style="font-weight:600; color:#1a2a6c;">$<?php echo number_format($service['price'], 0); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Reviews -->
    <h3 style="margin:30px 0 15px;"><?php echo t('reviews'); ?></h3>
    <?php if (isLoggedIn() && $can_review): ?>
        <div style="background:#fff; padding:20px; border-radius:12px; margin-bottom:20px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
            <h4><?php echo t('write_review'); ?></h4>
            <p style="color:#27ae60; font-size:0.9rem;">
                <i class="fas fa-check-circle"></i> <?php echo t('review_eligible'); ?>
            </p>
            <form method="POST" action="submit-review.php">
                <input type="hidden" name="hotel_id" value="<?php echo $hotel_id; ?>">
                <div class="form-group">
                    <label><?php echo t('rating'); ?></label>
                    <select name="rating" required>
                        <option value="5">★★★★★ <?php echo t('excellent'); ?></option>
                        <option value="4">★★★★ <?php echo t('good'); ?></option>
                        <option value="3">★★★ <?php echo t('average'); ?></option>
                        <option value="2">★★ <?php echo t('poor'); ?></option>
                        <option value="1">★ <?php echo t('terrible'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo t('comment'); ?></label>
                    <textarea name="comment" rows="3" required placeholder="<?php echo t('share_experience'); ?>"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo t('submit'); ?></button>
            </form>
        </div>
    <?php elseif (isLoggedIn() && !$can_review): ?>
        <div style="background:#fff3cd; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ffc107;">
            <p style="margin:0;">
                <i class="fas fa-info-circle"></i> 
                <?php echo t('review_requires_booking'); ?>
            </p>
        </div>
    <?php endif; ?>
    
    <?php if ($reviews): ?>
        <?php foreach ($reviews as $review): ?>
            <div style="background:#fff; padding:15px; border-radius:8px; margin-bottom:10px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                <div style="display:flex; justify-content:space-between; flex-wrap:wrap;">
                    <strong><?php echo htmlspecialchars($review['full_name']); ?></strong>
                    <span>
                        <?php echo str_repeat('★', $review['rating']); ?>
                        <?php echo str_repeat('☆', 5 - $review['rating']); ?>
                    </span>
                </div>
                <p style="margin-top:8px;"><?php echo htmlspecialchars($review['comment']); ?></p>
                <small style="color:#888;"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:#888;"><?php echo t('be_first_review'); ?></p>
    <?php endif; ?>
</div>

<script>
function toggleRoomDetails(roomId) {
    const details = document.getElementById('room-details-' + roomId);
    if (details) {
        if (details.style.display === 'none') {
            details.style.display = 'block';
        } else {
            details.style.display = 'none';
        }
    }
}

// Calendar functionality
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
const hotelId = <?php echo $hotel_id; ?>;

function changeCalendarMonth(delta) {
    currentMonth += delta;
    if (currentMonth > 11) { currentMonth = 0; currentYear++; }
    if (currentMonth < 0) { currentMonth = 11; currentYear--; }
    renderCalendar(currentYear, currentMonth);
}

function renderCalendar(year, month) {
    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    
    document.getElementById('calendar_month').textContent = monthNames[month] + ' ' + year;
    
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date().toISOString().split('T')[0];
    
    // Fetch availability data from server
    fetch('api/get_room_availability.php?hotel_id=' + hotelId + '&year=' + year + '&month=' + (month+1))
        .then(response => response.json())
        .then(data => {
            let html = '';
            // Day headers
            dayNames.forEach(day => {
                html += `<div style="text-align:center; font-weight:bold; padding:5px; background:#f8f9fa; border-radius:4px; font-size:0.85rem;">${day}</div>`;
            });
            
            // Empty cells before first day
            for (let i = 0; i < firstDay; i++) {
                html += `<div style="padding:5px; background:#f8f9fa; border-radius:4px;"></div>`;
            }
            
            // Days
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                const isPast = dateStr < today;
                const isToday = dateStr === today;
                
                // Check availability from data
                let isAvailable = false;
                let availCount = 0;
                if (data[dateStr] !== undefined) {
                    availCount = data[dateStr];
                    isAvailable = availCount > 0 && !isPast;
                } else if (!isPast) {
                    // If no data, assume available (will be updated when booked)
                    isAvailable = true;
                    availCount = 1;
                }
                
                let bgColor = '#f5f5f5';
                let textColor = '#999';
                let borderColor = '#e0e0e0';
                let cursorStyle = 'default';
                let statusText = 'Unavailable';
                let statusColor = '#bbb';
                
                if (isPast) {
                    bgColor = '#f5f5f5';
                    textColor = '#ccc';
                    borderColor = '#e8e8e8';
                    cursorStyle = 'default';
                    statusText = 'Past';
                    statusColor = '#ccc';
                } else if (isAvailable) {
                    bgColor = '#e8f5e9';
                    textColor = '#2e7d32';
                    borderColor = '#a5d6a7';
                    cursorStyle = 'pointer';
                    statusText = '✓ Available';
                    statusColor = '#2e7d32';
                } else {
                    bgColor = '#f5f5f5';
                    textColor = '#bbb';
                    borderColor = '#e0e0e0';
                    cursorStyle = 'not-allowed';
                    statusText = '✗ Unavailable';
                    statusColor = '#bbb';
                }
                
                html += `<div style="padding:10px 5px; text-align:center; border-radius:4px; background:${bgColor}; 
                              border:${isToday ? '2px solid #1a2a6c' : '1px solid ' + borderColor};
                              cursor:${cursorStyle};
                              transition: all 0.2s;
                              opacity:${isPast ? '0.6' : '1'};"
                              onclick="${isAvailable && !isPast ? "selectDate('" + dateStr + "')" : ''}"
                              onmouseover="${isAvailable && !isPast ? "this.style.transform='scale(1.05)'" : ''}"
                              onmouseout="${isAvailable && !isPast ? "this.style.transform='scale(1)'" : ''}">
                            <div style="font-weight:${isToday ? 'bold' : 'normal'}; color:${textColor};">${day}</div>
                            <div style="font-size:0.7rem; color:${statusColor}; margin-top:4px;">
                                ${statusText}
                            </div>
                        </div>`;
            }
            
            document.getElementById('calendar_grid').innerHTML = html;
        })
        .catch(() => {
            // Fallback: render without data
            let html = '';
            dayNames.forEach(day => {
                html += `<div style="text-align:center; font-weight:bold; padding:5px; background:#f8f9fa; border-radius:4px; font-size:0.85rem;">${day}</div>`;
            });
            for (let i = 0; i < firstDay; i++) {
                html += `<div style="padding:5px; background:#f8f9fa; border-radius:4px;"></div>`;
            }
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                const isPast = dateStr < today;
                const isToday = dateStr === today;
                const isAvailable = !isPast;
                
                html += `<div style="padding:10px 5px; text-align:center; border-radius:4px; background:${isAvailable ? '#e8f5e9' : '#f5f5f5'}; 
                              border:${isToday ? '2px solid #1a2a6c' : '1px solid #e0e0e0'};
                              cursor:${isAvailable ? 'pointer' : 'default'};
                              opacity:${isPast ? '0.6' : '1'};">
                            <div style="font-weight:${isToday ? 'bold' : 'normal'}; color:${isAvailable ? '#2e7d32' : '#bbb'};">${day}</div>
                            <div style="font-size:0.7rem; color:${isAvailable ? '#2e7d32' : '#bbb'}; margin-top:4px;">
                                ${isPast ? 'Past' : (isAvailable ? '✓ Available' : '✗ Unavailable')}
                            </div>
                        </div>`;
            }
            document.getElementById('calendar_grid').innerHTML = html;
        });
}

function selectDate(dateStr) {
    // Redirect to booking page with selected date
    window.location.href = 'booking.php?room_id=<?php echo $rooms[0]['id'] ?? 0; ?>&check_in=' + dateStr;
}

// Initial render
renderCalendar(currentYear, currentMonth);
</script>

<style>
.room-card {
    transition: transform 0.3s;
}
.room-card:hover {
    transform: translateY(-3px);
}
</style>

<?php include 'includes/footer.php'; ?>