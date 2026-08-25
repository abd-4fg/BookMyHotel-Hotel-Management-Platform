<?php
require_once 'config/database.php';
$conn = getConnection();

$location = trim($_GET['location'] ?? '');
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';
$guests = (int)($_GET['guests'] ?? 2);

$sql = "SELECT DISTINCT h.* FROM hotels h 
        JOIN rooms r ON h.id = r.hotel_id 
        WHERE r.max_occupancy >= $guests";
        
if ($location) {
    $sql .= " AND (h.name LIKE '%" . $conn->real_escape_string($location) . "%' OR h.location LIKE '%" . $conn->real_escape_string($location) . "%')";
}

$result = $conn->query($sql);
$hotels = $result->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container">
    <h2><?php echo t('search_results'); ?></h2>
    <?php if ($location): ?>
        <p><?php echo t('showing_results'); ?> "<?php echo htmlspecialchars($location); ?>"</p>
    <?php endif; ?>
    
    <?php if (empty($hotels)): ?>
        <div class="alert alert-info"><?php echo t('no_hotels_found'); ?></div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($hotels as $hotel): ?>
                <div class="card">
                    <div class="card-body">
                        <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hotel['location']); ?></p>
                        <p><?php echo substr(htmlspecialchars($hotel['description']), 0, 100); ?>...</p>
                        <div class="card-footer">
                            <a href="hotel-detail.php?id=<?php echo $hotel['id']; ?>" class="btn btn-primary"><?php echo t('view_details'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>