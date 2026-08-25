<?php
require_once 'config/database.php';
$conn = getConnection();

// Get featured hotels
$result = $conn->query("SELECT * FROM hotels LIMIT 4");
$hotels = $result->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container">
    <div class="hero">
        <h1><i class="fas fa-hotel"></i> <?php echo t('welcome'); ?></h1>
        <p><?php echo t('choose_hotel'); ?></p>
        <p style="font-size:0.9rem; margin-top:10px; opacity:0.8;">
            <i class="fas fa-check-circle"></i> <?php echo t('best_price_guarantee'); ?>
            <i class="fas fa-check-circle" style="margin-left:15px;"></i> <?php echo t('free_cancellation'); ?>
            <i class="fas fa-check-circle" style="margin-left:15px;"></i> <?php echo t('support_24_7'); ?>
        </p>
    </div>

    <!-- Quick Search -->
    <div class="search-bar">
        <form action="search.php" method="GET">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> <?php echo t('location'); ?></label>
                    <input type="text" name="location" placeholder="<?php echo t('city_hotel'); ?>" value="<?php echo htmlspecialchars($_GET['location'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-check"></i> <?php echo t('check_in'); ?></label>
                    <input type="date" name="check_in" id="check_in">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-check"></i> <?php echo t('check_out'); ?></label>
                    <input type="date" name="check_out" id="check_out">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> <?php echo t('guests'); ?></label>
                    <select name="guests">
                        <option value="1">1 <?php echo t('guest'); ?></option>
                        <option value="2" selected>2 <?php echo t('guests'); ?></option>
                        <option value="3">3 <?php echo t('guests'); ?></option>
                        <option value="4">4 <?php echo t('guests'); ?></option>
                    </select>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        <i class="fas fa-search"></i> <?php echo t('search_hotels'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Featured Hotels -->
    <h2 style="margin: 30px 0 15px;"><?php echo t('featured_hotels'); ?></h2>
    <div class="card-grid" id="featured-hotels">
        <?php foreach ($hotels as $hotel): ?>
            <div class="card">
                <div class="card-img" style="background:linear-gradient(135deg, #2d4373, #1a2a6c); display:flex; align-items:center; justify-content:center; color:#fff; font-size:3rem;">
                    <i class="fas fa-hotel"></i>
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo htmlspecialchars($hotel['name']); ?></h3>
                    <p class="card-text">
                        <i class="fas fa-star" style="color: #f7c948;"></i>
                        <?php echo str_repeat('★', $hotel['star_rating']); ?>
                        <i class="fas fa-map-marker-alt" style="margin-left:10px;"></i>
                        <?php echo htmlspecialchars($hotel['location']); ?>
                    </p>
                    <p class="card-text" style="font-size:0.85rem; color:#666;">
                        <?php echo substr(htmlspecialchars($hotel['facilities']), 0, 60); ?>...
                    </p>
                    <div class="card-footer">
                        <span class="price"><?php echo t('from'); ?> $<?php 
                            $roomQuery = $conn->query("SELECT MIN(price_per_night) as min_price FROM rooms WHERE hotel_id = " . $hotel['id']);
                            $room = $roomQuery->fetch_assoc();
                            echo number_format($room['min_price'] ?? 200, 0);
                        ?> <?php echo t('per_night'); ?></span>
                        <a href="hotel-detail.php?id=<?php echo $hotel['id']; ?>" class="btn btn-primary"><?php echo t('view_details'); ?></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Quick Links -->
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:20px; margin-top:40px;">
        <a href="hotels.php?hotel=marriott" style="background:#fff; padding:20px; border-radius:12px; text-align:center; text-decoration:none; color:#333; box-shadow:0 2px 10px rgba(0,0,0,0.06); transition:transform 0.3s;" 
           onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
            <i class="fas fa-building" style="font-size:2rem; color:#1a2a6c;"></i>
            <p style="margin-top:10px; font-weight:600;">Marriott <?php echo t('hotels'); ?></p>
        </a>
        <a href="hotels.php?hotel=hilton" style="background:#fff; padding:20px; border-radius:12px; text-align:center; text-decoration:none; color:#333; box-shadow:0 2px 10px rgba(0,0,0,0.06); transition:transform 0.3s;"
           onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
            <i class="fas fa-building" style="font-size:2rem; color:#1a2a6c;"></i>
            <p style="margin-top:10px; font-weight:600;">Hilton <?php echo t('hotels'); ?></p>
        </a>
        <a href="hotels.php?hotel=hyatt" style="background:#fff; padding:20px; border-radius:12px; text-align:center; text-decoration:none; color:#333; box-shadow:0 2px 10px rgba(0,0,0,0.06); transition:transform 0.3s;"
           onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
            <i class="fas fa-building" style="font-size:2rem; color:#1a2a6c;"></i>
            <p style="margin-top:10px; font-weight:600;">Hyatt <?php echo t('hotels'); ?></p>
        </a>
        <a href="hotels.php?hotel=fourseasons" style="background:#fff; padding:20px; border-radius:12px; text-align:center; text-decoration:none; color:#333; box-shadow:0 2px 10px rgba(0,0,0,0.06); transition:transform 0.3s;"
           onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
            <i class="fas fa-building" style="font-size:2rem; color:#1a2a6c;"></i>
            <p style="margin-top:10px; font-weight:600;">Four Seasons <?php echo t('hotels'); ?></p>
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>