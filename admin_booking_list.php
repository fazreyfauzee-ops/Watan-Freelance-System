<?php
require_once 'admin_access.php';
ensure_admin_access();
require_once 'database.php';

// Get admin profile data
$admin_id = $_SESSION['id'];
$admin_profile = null;

try {
    // Get admin user info
    $stmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = :id AND role = 'admin'");
    $stmt->execute([':id' => $admin_id]);
    $admin_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get admin profile picture and QR code if they exist in freelancers table
    $stmt = $conn->prepare("SELECT profile_picture, qr_code FROM freelancers WHERE user_id = :id");
    $stmt->execute([':id' => $admin_id]);
    $freelancer_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freelancer_data) {
        $admin_profile['profile_picture'] = $freelancer_data['profile_picture'];
        $admin_profile['qr_code'] = $freelancer_data['qr_code'];
    }
} catch (PDOException $e) {
    $admin_profile = ['fullname' => 'Admin', 'email' => ''];
}

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bookingId = $_POST['booking_id'] ?? '';
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'accept':
                $stmt = $conn->prepare("UPDATE booking SET booking_status = 'Job In Progress' WHERE booking_id = :booking_id");
                $stmt->execute([':booking_id' => $bookingId]);
                $message = "Booking accepted successfully";
                break;
                
            case 'reject':
                $stmt = $conn->prepare("UPDATE booking SET booking_status = 'Job Rejected' WHERE booking_id = :booking_id");
                $stmt->execute([':booking_id' => $bookingId]);
                $message = "Booking rejected successfully";
                break;
                
            case 'complete':
                $stmt = $conn->prepare("UPDATE booking SET booking_status = 'Job Completed' WHERE booking_id = :booking_id");
                $stmt->execute([':booking_id' => $bookingId]);
                $message = "Booking marked as completed";
                break;
                
            case 'verify':
                $stmt = $conn->prepare("UPDATE booking SET booking_status = 'Job Completed' WHERE booking_id = :booking_id");
                $stmt->execute([':booking_id' => $bookingId]);
                $message = "Booking verified successfully";
                break;
                
            case 'redo':
                $stmt = $conn->prepare("UPDATE booking SET booking_status = 'Job In Progress' WHERE booking_id = :booking_id");
                $stmt->execute([':booking_id' => $bookingId]);
                $message = "Booking sent back for revision";
                break;
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all bookings with user details
try {
    $stmt = $conn->query("
        SELECT 
            b.*,
            u1.fullname as client_name,
            u1.email as client_email,
            u2.fullname as freelancer_name,
            u2.email as freelancer_email
        FROM booking b
        LEFT JOIN users u1 ON b.client_id = u1.id
        LEFT JOIN users u2 ON b.freelancer_id = u2.id
        ORDER BY b.created_at DESC
    ");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching bookings: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Bookings | Watan Freelance System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
:root{
    --primary-purple:#7a5af8;
    --primary-dark:#6948f0;
    --secondary-cyan:#06b6d4;
    --success-green:#10b981;
    --warning-orange:#f59e0b;
    --danger-red:#ef4444;
    --gradient-1:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-2:linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-3:linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-4:linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    --gradient-5:linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    --gradient-6:linear-gradient(135deg, #30cfd0 0%, #330867 100%);
    --secondary-bg:#f8fafc;
    --card-shadow:0 10px 30px rgba(0,0,0,0.1);
    --hover-shadow:0 20px 40px rgba(0,0,0,0.15);
}
body{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    background-attachment:fixed;
    font-family:'Poppins',sans-serif;
    padding-top:70px;
    min-height:100vh;
}

/* NAVBAR */
.admin-nav{
    position:fixed;
    top:0;width:100%;
    background:rgba(255,255,255,0.98);
    backdrop-filter:blur(10px);
    box-shadow:0 2px 20px rgba(0,0,0,0.08);
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:16px 60px;
    z-index:1000;
}
.logo{
    font-size:1.6rem;
    font-weight:700;
    background:var(--gradient-1);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
}
.nav-right{list-style:none;display:flex;gap:8px;margin:0;align-items:center;}
.nav-right a{
    text-decoration:none;
    color:#475569;
    font-weight:500;
    padding:10px 20px;
    border-radius:12px;
    transition:all 0.2s ease;
    font-size:0.95rem;
}
.nav-right a:hover{
    background:var(--gradient-1);
    color:#fff;
    transform:translateY(-1px);
    box-shadow:0 4px 12px rgba(102,126,234,0.3);
}
.admin-badge{
    background:var(--gradient-2);
    color:#fff;
    padding:8px 16px;
    border-radius:12px;
    font-size:0.85rem;
    font-weight:600;
    margin-right:8px;
}

/* PROFILE SECTION */
.profile-section{
    display:flex;
    align-items:center;
    gap:12px;
    padding:8px 16px;
    background:rgba(255,255,255,0.1);
    border-radius:15px;
    border:1px solid rgba(255,255,255,0.2);
}
.admin-avatar{
    width:40px;
    height:40px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid var(--gradient-1);
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}
.admin-avatar-placeholder{
    width:40px;
    height:40px;
    border-radius:50%;
    background:var(--gradient-1);
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-size:1.2rem;
    border:2px solid var(--gradient-1);
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}
.admin-info{
    display:flex;
    flex-direction:column;
    gap:4px;
}
.admin-name{
    font-weight:600;
    color:#334155;
    font-size:0.9rem;
}
.admin-qr{
    width:24px;
    height:24px;
    border-radius:4px;
    object-fit:cover;
    border:1px solid rgba(0,0,0,0.1);
}

/* CONTENT */
.container{
    max-width:1400px;
    margin:0 auto;
    padding:30px;
}
.card{
    background:rgba(255,255,255,0.95);
    backdrop-filter:blur(20px);
    border-radius:20px;
    box-shadow:var(--card-shadow);
    overflow:hidden;
    transition:all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border:1px solid rgba(255,255,255,0.2);
}
.card:hover{
    transform:translateY(-5px);
    box-shadow:var(--hover-shadow);
}
.card-header{
    background:var(--gradient-1);
    color:#fff;
    font-weight:700;
    padding:20px;
    font-size:1.1rem;
    text-shadow:0 2px 10px rgba(0,0,0,0.2);
    position:relative;
    overflow:hidden;
}
.card-header::after{
    content:'';
    position:absolute;
    top:-50%;right:-50%;
    width:100%;height:100%;
    background:radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
    animation:shimmer 3s infinite;
}
@keyframes shimmer{
    0%{transform:rotate(0deg);}
    100%{transform:rotate(360deg);}
}
.card-body{
    padding:0;
}
.table{
    margin:0;
    background:rgba(255,255,255,0.5);
}
.table th{
    background:rgba(122,90,248,0.1);
    font-weight:700;
    color:#475569;
    border:none;
    padding:15px;
    font-size:.85rem;
    text-transform:uppercase;
    letter-spacing:0.5px;
}
.table td{
    padding:15px;
    border-top:1px solid rgba(226,232,240,0.5);
    vertical-align:middle;
    font-weight:500;
    color:#334155;
    transition:all 0.2s ease;
}
.table tbody tr:hover td{
    background:rgba(122,90,248,0.05);
}

/* STATUS BADGES */
.status-badge{
    padding:6px 12px;
    border-radius:12px;
    font-size:0.75rem;
    font-weight:600;
    text-transform:uppercase;
    display:inline-block;
}
.status-review{background:rgba(59,130,246,0.1);color:var(--secondary-cyan);}
.status-progress{background:rgba(245,158,11,0.1);color:var(--warning-orange);}
.status-rejected{background:rgba(239,68,68,0.1);color:var(--danger-red);}
.status-cancelled{background:rgba(107,114,128,0.1);color:#64748b;}
.status-verification{background:rgba(122,90,248,0.1);color:var(--primary-purple);}
.status-completed{background:rgba(16,185,129,0.1);color:var(--success-green);}

/* BUTTONS */
.btn-action{
    background:var(--gradient-1);
    color:#fff;
    border:none;
    padding:8px 16px;
    border-radius:10px;
    font-size:0.85rem;
    font-weight:600;
    text-decoration:none;
    transition:all 0.2s ease;
    margin:0 2px;
    display:inline-block;
    box-shadow:0 2px 8px rgba(102,126,234,0.3);
}
.btn-action:hover{
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(102,126,234,0.4);
    color:#fff;
}
.btn-success{
    background:var(--gradient-4);
    box-shadow:0 2px 8px rgba(67,233,123,0.3);
}
.btn-success:hover{
    box-shadow:0 4px 12px rgba(67,233,123,0.4);
}
.btn-danger{
    background:var(--gradient-2);
    box-shadow:0 2px 8px rgba(245,87,108,0.3);
}
.btn-danger:hover{
    box-shadow:0 4px 12px rgba(245,87,108,0.4);
}

/* ALERT */
.alert{
    background:rgba(255,255,255,0.95);
    backdrop-filter:blur(20px);
    border-radius:15px;
    border:none;
    box-shadow:var(--card-shadow);
    margin-bottom:20px;
}
.alert-success{
    background:rgba(16,185,129,0.1);
    color:var(--success-green);
    border:1px solid rgba(16,185,129,0.2);
}
.alert-danger{
    background:rgba(239,68,68,0.1);
    color:var(--danger-red);
    border:1px solid rgba(239,68,68,0.2);
}

/* USER INFO */
.user-info{
    font-size:0.85rem;
}
.user-info .name{
    font-weight:600;
    color:#334155;
}
.user-info .email{
    color:#64748b;
    font-size:0.8rem;
}

/* RESPONSIVE */
@media(max-width:992px){
    .admin-nav{padding:12px 20px;}
    .container{padding:20px;}
}
@media(max-width:576px){
    .nav-right{flex-wrap:wrap;gap:10px;}
    .nav-right a{padding:6px 12px;font-size:.9rem;}
    .btn-action{padding:6px 12px;font-size:.8rem;}
}
        
        .booking-details {
            max-width: 200px;
        }
        
        .user-info {
            font-size: 0.85rem;
        }
        
        .user-info .name {
            font-weight: 600;
            color: #333;
        }
        
        .user-info .email {
            color: #666;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <nav class="admin-nav">
    <div class="logo">Watan Admin</div>
    <ul class="nav-right">
        <li><a href="admin_dashboard.php">Dashboard</a></li>
        <li><a href="admin_users.php">Users</a></li>
        <li><a href="admin_feedback.php">Feedback</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-calendar-check me-2"></i>Manage Bookings</span>
                <span><?= count($bookings) ?> Total</span>
            </div>
            <div class="card-body">
                <?php if (isset($message)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (count($bookings) == 0): ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <i class="bi bi-inbox" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <p>No bookings found in the system.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Service</th>
                                    <th>Client</th>
                                    <th>Freelancer</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($booking['booking_id']) ?></strong></td>
                                    <td class="booking-details">
                                        <div><?= htmlspecialchars($booking['service_title']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($booking['total_booking_hours']) ?>h • RM<?= number_format($booking['calculated_total_price'], 2) ?></small>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <div class="name"><?= htmlspecialchars($booking['client_name']) ?></div>
                                            <div class="email"><?= htmlspecialchars($booking['client_email']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <div class="name"><?= htmlspecialchars($booking['freelancer_name']) ?></div>
                                            <div class="email"><?= htmlspecialchars($booking['freelancer_email']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= str_replace([' ', 'Job'], ['', ''], strtolower($booking['booking_status'])) ?>">
                                            <?= htmlspecialchars($booking['booking_status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($booking['created_at'])) ?></td>
                                    <td>
                                        <?php if ($booking['booking_status'] === 'Job In Review'): ?>
                                            <span class="status-badge status-review">Awaiting Freelancer Action</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['booking_status'] === 'Job In Progress'): ?>
                                            <span class="status-badge status-progress">Work in Progress</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['booking_status'] === 'Job Pending Verification'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['booking_id']) ?>">
                                                <button type="submit" name="action" value="verify" class="btn-action btn-success">
                                                    <i class="bi bi-shield-check"></i> Verify
                                                </button>
                                                <button type="submit" name="action" value="redo" class="btn-action btn-danger">
                                                    <i class="bi bi-arrow-clockwise"></i> Redo
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <a href="view_deliverable.php?bid=<?= htmlspecialchars($booking['booking_id']) ?>" class="btn-action">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
