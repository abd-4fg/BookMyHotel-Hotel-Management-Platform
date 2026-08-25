<?php
require_once 'config/database.php';
$conn = getConnection();

$hotel_filter = $_GET['hotel'] ?? '';
$sql = "SELECT * FROM hotels";

if ($hotel_filter) {
    $sql .= " WHERE LOWER(name) LIKE '%" . $conn->real_escape_string(strtolower($hotel_filter)) . "%'";
}

$result = $conn->query($sql);
$hotels = $result->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container">
    <h2><?php echo t('all_hotels'); ?></h2>
    <p><?php echo t('choose_hotel'); ?></p>
    
    <div class="card-grid">
        <?php if (empty($hotels)): ?>
            <div class="alert alert-info"><?php echo t('no_hotels_found'); ?></div>
        <?php else: ?>
            <?php foreach ($hotels as $hotel): ?>
                <div class="card">
                    <div class="card-img" style="background:linear-gradient(135deg, #2d4373, #1a2a6c); display:flex; align-items:center; justify-content:center; color:#fff; font-size:3rem;">
                        <i class="fas fa-hotel"></i>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($hotel['name']); ?></h3>
                        <p class="card-text">
                            <?php echo str_repeat('★', $hotel['star_rating']); ?>
                            <i class="fas fa-map-marker-alt" style="margin-left:10px;"></i>
                            <?php echo htmlspecialchars($hotel['location']); ?>
                        </p>
                        <p class="card-text"><?php echo substr(htmlspecialchars($hotel['description']), 0, 100); ?>...</p>
                        <div class="card-footer">
                            <a href="hotel-detail.php?id=<?php echo $hotel['id']; ?>" class="btn btn-primary"><?php echo t('view_details'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>