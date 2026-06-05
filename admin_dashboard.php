<?php
require_once 'admin_access.php';
ensure_admin_access();
require_once 'database.php';

// Get admin profile data
$admin_id = $_SESSION['id'];
$admin_profile = null;
$admin_qr = null;

try {
    // Get admin user info
    $stmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = :id AND role = 'admin'");
    $stmt->execute([':id' => $admin_id]);
    $admin_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get admin profile picture and QR code if they exist in freelancers table (some admins might have freelancer profiles too)
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

// Initialize variables with safe defaults
$userStats = [];
$bookingStats = [];
$totalFeedback = 0;
$avgRating = 0.0;
$recentBookings = [];
$recentFeedback = [];
$error = null;

try {
    $totalUsers = $conn->query("SELECT COUNT(*) FROM users WHERE role IN ('client','freelancer')")->fetchColumn() ?: 0;
    $totalBookings = $conn->query("SELECT COUNT(*) FROM booking")->fetchColumn() ?: 0;
    $totalClients = $conn->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn() ?: 0;
    $totalFreelancers = $conn->query("SELECT COUNT(*) FROM users WHERE role='freelancer'")->fetchColumn() ?: 0;
    $jobCompleted = $conn->query("SELECT COUNT(*) FROM booking WHERE booking_status='Job Completed'")->fetchColumn() ?: 0;

    $feedbackTableExists = $conn->query("SHOW TABLES LIKE 'feedback'")->fetchColumn();
    if ($feedbackTableExists) {
        $totalFeedback = $conn->query("SELECT COUNT(*) FROM feedback")->fetchColumn() ?: 0;
        $avgRating = $conn->query("SELECT AVG(rating) FROM feedback")->fetchColumn() ?: 0.0;

        $recentBookings = $conn->query("
            SELECT b.booking_id, b.service_title,
                   u1.fullname AS client_name, u2.fullname AS freelancer_name
            FROM booking b
            LEFT JOIN users u1 ON b.client_id = u1.id
            LEFT JOIN users u2 ON b.freelancer_id = u2.id
            ORDER BY b.created_at DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        $recentFeedback = $conn->query("
            SELECT f.rating, f.comment, u.fullname AS client_name
            FROM feedback f
            LEFT JOIN users u ON f.client_id = u.id
            ORDER BY f.created_at DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | Watan Freelance System</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

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

/* STATS */
.stats-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:25px;
    margin-bottom:40px;
}
.stat-card{
    background:rgba(255,255,255,0.95);
    backdrop-filter:blur(20px);
    padding:30px;
    border-radius:20px;
    box-shadow:var(--card-shadow);
    display:flex;
    align-items:center;
    gap:20px;
    transition:all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border:1px solid rgba(255,255,255,0.2);
    position:relative;
    overflow:hidden;
}
.stat-card::before{
    content:'';
    position:absolute;
    top:-50%;left:-50%;
    width:200%;height:200%;
    background:radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    opacity:0;
    transition:opacity 0.3s ease;
}
.stat-card:hover::before{
    opacity:1;
}
.stat-card:hover{
    transform:translateY(-10px) scale(1.02);
    box-shadow:var(--hover-shadow);
}
.stat-card:nth-child(1) .stat-icon{background:var(--gradient-1);color:#fff;}
.stat-card:nth-child(2) .stat-icon{background:var(--gradient-2);color:#fff;}
.stat-card:nth-child(3) .stat-icon{background:var(--gradient-3);color:#fff;}
.stat-card:nth-child(4) .stat-icon{background:var(--gradient-4);color:#fff;}
.stat-card:nth-child(5) .stat-icon{background:var(--gradient-5);color:#fff;}
.stat-card:nth-child(6) .stat-icon{background:var(--gradient-6);color:#fff;}
.stat-icon{
    width:65px;
    height:65px;
    border-radius:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.8rem;
    box-shadow:0 8px 25px rgba(0,0,0,0.15);
    transition:all 0.3s ease;
}
.stat-card:hover .stat-icon{
    transform:rotate(10deg) scale(1.1);
}
.stat-number{
    font-size:2.2rem;
    font-weight:800;
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
    line-height:1;
}
.stat-label{
    font-size:.85rem;
    color:#64748b;
    text-transform:uppercase;
    letter-spacing:1px;
    font-weight:600;
}

/* CONTENT */
.content-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:30px;
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
.rating{
    background:linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
    font-size:1.2rem;
    font-weight:700;
    text-shadow:0 2px 10px rgba(251,191,36,0.3);
}

/* ALERT */
.alert{
    background:rgba(255,255,255,0.95);
    backdrop-filter:blur(20px);
    border-radius:15px;
    border:none;
    box-shadow:var(--card-shadow);
}

/* CONTAINER */
.container{
    max-width:1400px;
    margin:0 auto;
    padding:30px;
}

/* RESPONSIVE */
@media(max-width:992px){
    .stats-grid{grid-template-columns:1fr;}
    .content-grid{grid-template-columns:1fr;}
    .admin-nav{padding:12px 20px;}
    .container{padding:20px;}
}

@media(max-width:576px){
    .stat-card{flex-direction:column;text-align:center;}
    .nav-right{flex-wrap:wrap;gap:10px;}
    .nav-right a{padding:6px 12px;font-size:.9rem;}
}
</style>
</head>

<body>

<nav class="admin-nav">
    <div class="logo">Watan Admin</div>
    <ul class="nav-right">
        <li><a href="admin_dashboard.php">Dashboard</a></li>
        <li><a href="admin_users.php">Users</a></li>
        <li><a href="admin_booking_list.php">Bookings</a></li>
        <li><a href="admin_feedback.php">Feedback</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<div class="container">

<?php if($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- STATS 3 TOP + 3 BOTTOM -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-people"></i></div>
        <div><div class="stat-number"><?= $totalUsers ?></div><div class="stat-label">Users</div></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-person"></i></div>
        <div><div class="stat-number"><?= $totalClients ?></div><div class="stat-label">Clients</div></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-briefcase"></i></div>
        <div><div class="stat-number"><?= $totalFreelancers ?></div><div class="stat-label">Freelancers</div></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-journal-text"></i></div>
        <div><div class="stat-number"><?= $totalBookings ?></div><div class="stat-label">Bookings</div></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
        <div><div class="stat-number"><?= $jobCompleted ?></div><div class="stat-label">Completed</div></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-star-fill"></i></div>
        <div><div class="stat-number"><?= number_format($avgRating,1) ?></div><div class="stat-label">Avg Rating</div></div>
    </div>
</div>

<div class="content-grid">

<div class="card">
<div class="card-header">Recent Bookings</div>
<table class="table table-sm mb-0">
<thead><tr><th>ID</th><th>Service</th><th>Client</th><th>Freelancer</th></tr></thead>
<tbody>
<?php if(empty($recentBookings)): ?>
<tr><td colspan="4" class="text-center text-muted">No bookings</td></tr>
<?php else: foreach($recentBookings as $b): ?>
<tr>
<td>#<?= $b['booking_id'] ?></td>
<td><?= htmlspecialchars($b['service_title']) ?></td>
<td><?= htmlspecialchars($b['client_name'] ?? '-') ?></td>
<td><?= htmlspecialchars($b['freelancer_name'] ?? '-') ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>

<div class="card">
<div class="card-header">Recent Feedback</div>
<table class="table table-sm mb-0">
<thead><tr><th>Rating</th><th>Comment</th><th>Client</th></tr></thead>
<tbody>
<?php if(empty($recentFeedback)): ?>
<tr><td colspan="3" class="text-center text-muted">No feedback</td></tr>
<?php else: foreach($recentFeedback as $f): ?>
<tr>
<td class="rating"><?= str_repeat('★',(int)$f['rating']) ?></td>
<td><?= htmlspecialchars($f['comment']) ?></td>
<td><?= htmlspecialchars($f['client_name'] ?? '-') ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>

</div>
</div>

</body>
</html>
