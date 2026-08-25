<?php
require_once 'config/database.php';

// Only Maintenance Staff or Admin can access this page
if (!isMaintenanceStaff() && !isAdmin()) {
    header('Location: maintenance-login.php');
    exit;
}

$conn = getConnection();
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Get maintenance tickets
$tickets = $conn->query("SELECT t.*, h.name as hotel_name, u.full_name as created_by_name 
                         FROM maintenance_tickets t 
                         JOIN hotels h ON t.hotel_id = h.id 
                         JOIN users u ON t.created_by = u.id 
                         ORDER BY 
                             CASE t.status 
                                 WHEN 'pending' THEN 0 
                                 WHEN 'in_progress' THEN 1 
                                 ELSE 2 
                             END, 
                         t.created_at DESC");

// Stats
$stats = [];
$stats['pending'] = $conn->query("SELECT COUNT(*) as count FROM maintenance_tickets WHERE status = 'pending'")->fetch_assoc()['count'];
$stats['in_progress'] = $conn->query("SELECT COUNT(*) as count FROM maintenance_tickets WHERE status = 'in_progress'")->fetch_assoc()['count'];
$stats['completed'] = $conn->query("SELECT COUNT(*) as count FROM maintenance_tickets WHERE status = 'completed'")->fetch_assoc()['count'];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM maintenance_tickets")->fetch_assoc()['count'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $resolution = trim($_POST['resolution'] ?? '');
    
    if ($action === 'update_ticket' && $ticket_id) {
        $stmt = $conn->prepare("UPDATE maintenance_tickets SET status = ?, resolution_notes = ?, completed_by = ?, completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssii", $status, $resolution, $_SESSION['user_id'], $ticket_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Ticket updated successfully!';
            header('Location: maintenance-dashboard.php');
            exit;
        }
    }
}

// No header include - standalone page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Dashboard - BookMyHotel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .maintenance-header {
            background: #8e44ad;
            color: #fff;
            padding: 15px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .maintenance-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .maintenance-header .logo {
            color: #fff;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
        }
        .maintenance-header .logo i {
            color: #f7c948;
            margin-right: 8px;
        }
        .maintenance-header .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .maintenance-header .user-info span {
            color: rgba(255,255,255,0.8);
        }
        .maintenance-header .logout-btn {
            color: #fff;
            background: rgba(255,255,255,0.15);
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .maintenance-header .logout-btn:hover {
            background: rgba(255,255,255,0.25);
        }
        .maintenance-footer {
            background: #8e44ad;
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
            background: #8e44ad;
            color: #fff;
        }
        .btn-primary:hover {
            background: #6c3483;
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
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .ticket-card {
            border-left: 5px solid #8e44ad;
            transition: transform 0.2s;
        }
        .ticket-card:hover {
            transform: translateX(5px);
        }
        .ticket-card.status-pending { border-left-color: #e74c3c; }
        .ticket-card.status-in_progress { border-left-color: #f39c12; }
        .ticket-card.status-completed { border-left-color: #27ae60; }
    </style>
</head>
<body>
    <!-- Maintenance Header -->
    <header class="maintenance-header">
        <div class="container">
            <a href="maintenance-dashboard.php" class="logo">
                <i class="fas fa-tools"></i> BookMyHotel Maintenance
            </a>
            <div class="user-info">
                <span><i class="fas fa-user-cog"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Maintenance'); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <h2><i class="fas fa-clipboard-list"></i> Maintenance Dashboard</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-success animate__animated animate__fadeIn"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:15px; margin:20px 0;">
                <div class="stat-card" style="background:#fff; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#e74c3c; margin:0;"><?php echo $stats['pending']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.85rem; color:#666;">Pending</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#f39c12; margin:0;"><?php echo $stats['in_progress']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.85rem; color:#666;">In Progress</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#27ae60; margin:0;"><?php echo $stats['completed']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.85rem; color:#666;">Completed</p>
                </div>
                <div class="stat-card" style="background:#fff; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h3 style="color:#8e44ad; margin:0;"><?php echo $stats['total']; ?></h3>
                    <p style="margin:5px 0 0; font-size:0.85rem; color:#666;">Total</p>
                </div>
            </div>
            
            <!-- Tickets List -->
            <h3 style="margin:30px 0 15px;"><i class="fas fa-tasks"></i> Maintenance Tickets</h3>
            
            <?php if ($tickets->num_rows > 0): ?>
                <div style="display:grid; gap:15px;">
                    <?php while ($ticket = $tickets->fetch_assoc()): ?>
                        <div class="ticket-card status-<?php echo $ticket['status']; ?>" style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
                            <div style="display:flex; justify-content:space-between; flex-wrap:wrap;">
                                <div>
                                    <h4 style="margin:0;"><?php echo htmlspecialchars($ticket['hotel_name']); ?> - Room <?php echo htmlspecialchars($ticket['room_number']); ?></h4>
                                    <p style="margin:5px 0; color:#666;">
                                        <strong>Issue:</strong> <?php echo htmlspecialchars($ticket['issue']); ?>
                                    </p>
                                    <p style="margin:5px 0; font-size:0.85rem; color:#888;">
                                        Created by: <?php echo htmlspecialchars($ticket['created_by_name']); ?> | 
                                        Created: <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?>
                                    </p>
                                    <?php if ($ticket['resolution_notes']): ?>
                                        <p style="margin:5px 0; font-size:0.85rem; color:#27ae60;">
                                            <strong>Resolution:</strong> <?php echo htmlspecialchars($ticket['resolution_notes']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span style="padding:4px 12px; border-radius:20px; background:<?php 
                                        echo $ticket['status'] === 'pending' ? '#f8d7da' : 
                                             ($ticket['status'] === 'in_progress' ? '#fff3cd' : '#d4edda'); 
                                    ?>; color:<?php 
                                        echo $ticket['status'] === 'pending' ? '#721c24' : 
                                             ($ticket['status'] === 'in_progress' ? '#856404' : '#155724'); 
                                    ?>;">
                                        <?php echo ucfirst($ticket['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($ticket['status'] !== 'completed'): ?>
                                <form method="POST" style="margin-top:15px; border-top:1px solid #eee; padding-top:15px;">
                                    <input type="hidden" name="action" value="update_ticket">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                                        <select name="status" style="padding:8px 12px; border:1px solid #ddd; border-radius:6px;">
                                            <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>
                                                In Progress
                                            </option>
                                            <option value="completed">Completed</option>
                                        </select>
                                        <input type="text" name="resolution" placeholder="Resolution notes" 
                                               style="flex:1; padding:8px 12px; border:1px solid #ddd; border-radius:6px; min-width:150px;">
                                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No maintenance tickets found.</div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="maintenance-footer">
        <div class="container">
            <p>&copy; 2026 BookMyHotel Maintenance Panel. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>