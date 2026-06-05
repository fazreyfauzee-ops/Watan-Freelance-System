<?php
session_start();
require_once "database.php";

// Get freelancer ID from session (user_id from users table)
$freelancerID = isset($_SESSION['id']) ? (int)$_SESSION['id'] : null;

if (!$freelancerID) {
    die("Error: You must be logged in to view bookings.");
}

// Optional: Verify user is a freelancer
if (isset($_SESSION['role']) && strtolower($_SESSION['role']) !== 'freelancer') {
    // Allow access but show warning (or redirect if preferred)
    // die("Error: This page is for freelancers only.");
}

// Get user info for navigation
$isLoggedIn = isset($_SESSION['username']);
$currentUsername = $_SESSION['username'] ?? '';
$role = strtolower($_SESSION['role'] ?? $_SESSION['user_type'] ?? '');
$profileLink = ($role === 'client') ? 'client.php' : 'freelancer_form.php';
$profileLabel = ($role === 'client') ? 'Edit Client Profile' : 'Edit Freelancer Profile';

// Fetch bookings for this freelancer with feedback information
try {
    // First check if feedback table has booking_id column
    $feedbackColumns = $conn->query("SHOW COLUMNS FROM feedback LIKE 'booking_id'")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($feedbackColumns)) {
        // Use booking_id join if column exists - CRITICAL for correct feedback linking
        $stmt = $conn->prepare("
            SELECT 
                b.booking_id,
                b.client_id,
                b.freelancer_id,
                b.service_title,
                b.description,
                b.deadline,
                b.calculated_total_price,
                b.work_mode,
                b.location,
                b.booking_status,
                b.created_at,
                f.id as feedback_id,
                f.rating as feedback_rating,
                f.comment as feedback_comment,
                f.created_at as feedback_date,
                u.fullname as client_name
            FROM booking b
            LEFT JOIN feedback f ON b.booking_id = f.booking_id  -- CRITICAL: Join by booking_id
            LEFT JOIN users u ON b.client_id = u.id
            WHERE b.freelancer_id = :fid
            ORDER BY b.created_at DESC
        ");
    } else {
        // Fallback if booking_id doesn't exist yet
        $stmt = $conn->prepare("
            SELECT 
                b.booking_id,
                b.client_id,
                b.freelancer_id,
                b.service_title,
                b.description,
                b.deadline,
                b.calculated_total_price,
                b.work_mode,
                b.location,
                b.booking_status,
                b.created_at,
                f.id as feedback_id,
                f.rating as feedback_rating,
                f.comment as feedback_comment,
                f.created_at as feedback_date,
                u.fullname as client_name
            FROM booking b
            LEFT JOIN feedback f ON b.freelancer_id = f.freelancer_id  -- Fallback join
            LEFT JOIN users u ON b.client_id = u.id
            WHERE b.freelancer_id = :fid
            ORDER BY b.created_at DESC
        ");
    }
    
    $stmt->bindParam(":fid", $freelancerID);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Fallback query without feedback join if there are issues
    $stmt = $conn->prepare("
        SELECT 
            booking_id,
            client_id,
            freelancer_id,
            service_title,
            description,
            deadline,
            calculated_total_price,
            work_mode,
            location,
            booking_status,
            created_at
        FROM booking
        WHERE freelancer_id = :fid
        ORDER BY created_at DESC
    ");
    $stmt->bindParam(":fid", $freelancerID);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add empty feedback fields to maintain consistency
    foreach ($bookings as &$booking) {
        $booking['feedback_id'] = null;
        $booking['feedback_rating'] = null;
        $booking['feedback_comment'] = null;
        $booking['feedback_date'] = null;
        $booking['client_name'] = null;
    }
}

// Status labels
$status_labels = [
    "Job In Review"            => "Job In Review",
    "Job In Progress"          => "Job In Progress",
    "Job Pending Verification" => "Pending Verification",
    "Job Completed"            => "Job Completed",
    "Job Rejected"             => "Job Rejected"
];

// Split bookings into separate status groups
$jobInReview = [];
$jobInProgress = [];
$jobPendingVerification = [];
$jobCompleted = [];
$jobRejected = [];
$jobCancelled = [];

foreach($bookings as $b){
    $status = $b["booking_status"];
    
    if ($status == "Job In Review") {
        $jobInReview[] = $b;
    } elseif ($status == "Job In Progress") {
        $jobInProgress[] = $b;
    } elseif ($status == "Job Pending Verification") {
        $jobPendingVerification[] = $b;
    } elseif ($status == "Job Completed") {
        $jobCompleted[] = $b;
    } elseif ($status == "Job Rejected") {
        $jobRejected[] = $b;
    } elseif ($status == "Job Cancelled") {
        $jobCancelled[] = $b;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings | Watan Freelance System</title>

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
    --card-shadow:0 10px 30px rgba(0,0,0,0.1);
    --hover-shadow:0 20px 40px rgba(0,0,0,0.15);
}

body{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    background-attachment:fixed;
    font-family:'Poppins',sans-serif;
    padding-top:80px; /* Space for fixed nav */
    min-height:100vh;
    color: #334155;
}

/* NAVBAR */
.glass-nav {
    position:fixed;
    top:0;width:100%;
    background:rgba(255,255,255,0.95);
    backdrop-filter:blur(10px);
    box-shadow:0 2px 20px rgba(0,0,0,0.08);
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:14px 60px;
    z-index:1000;
}

.logo {
    font-size:1.6rem;
    font-weight:700;
    background:var(--gradient-1);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
}

.nav-menu {
    display: flex;
    list-style: none;
    gap: 25px;
    align-items: center;
    margin: 0;
}

.nav-menu li a {
    text-decoration: none;
    color: #475569;
    font-weight: 500;
    position: relative;
    padding-bottom: 4px;
    transition: color .2s ease;
}

.nav-menu li a:hover { 
    color: var(--primary-purple); 
}

.nav-actions { 
    display: flex; 
    align-items: center; 
    gap: 15px; 
}

.btn-join-nav {
    background: var(--gradient-1);
    color: #fff;
    border: none;
    padding: 8px 20px;
    border-radius: 999px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    box-shadow: 0 4px 10px rgba(122, 90, 248, 0.3);
}

.btn-join-nav:hover { 
    transform: translateY(-2px);
    color: #fff;
    box-shadow: 0 6px 15px rgba(122, 90, 248, 0.4);
}

.link-signin {
    color: #475569;
    text-decoration: none;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 12px;
    transition: all 0.2s ease;
    background: rgba(255,255,255,0.5);
    border: 1px solid rgba(0,0,0,0.05);
}

.link-signin:hover { 
    color: var(--primary-purple); 
    background-color: #f3f1ff; 
    transform: translateY(-1px);
}

/* CONTAINER & TITLE */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
}

.page-title {
    text-align: center;
    color: #fff;
    font-weight: 700;
    font-size: 2.2rem;
    margin-bottom: 40px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

/* CARDS */
.card {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255,255,255,0.2);
    margin-bottom: 35px;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: var(--hover-shadow);
}

.card-header {
    background: var(--gradient-1);
    color: #fff;
    font-weight: 700;
    padding: 18px 25px;
    font-size: 1.1rem;
    position: relative;
    overflow: hidden;
    border: none;
}

.card-header::after {
    content: '';
    position: absolute;
    top: -50%; right: -50%;
    width: 100%; height: 100%;
    background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
    animation: shimmer 5s infinite;
}

@keyframes shimmer {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* TABLES */
.table-responsive {
    border-radius: 0 0 20px 20px;
}

.table {
    margin: 0;
    background: transparent;
}

.table th {
    background: rgba(122,90,248,0.08);
    font-weight: 700;
    color: #475569;
    border: none;
    padding: 18px 20px;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    padding: 18px 20px;
    border-top: 1px solid rgba(226,232,240,0.6);
    vertical-align: middle;
    font-weight: 500;
    color: #334155;
    font-size: 0.95rem;
}

.table tbody tr:hover td {
    background: rgba(122,90,248,0.03);
}

/* STATUS BADGES */
.status { 
    padding: 6px 14px; 
    border-radius: 12px; 
    font-weight: 600; 
    font-size: 0.75rem; 
    display: inline-block; 
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status.needs-review { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.status.job-in-progress { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.status.booking-request { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
.status.job-completed { background: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe; }
.status.job-rejected { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.status.job-cancelled { background: #1e293b; color: #fff; }

/* ACTION BUTTONS */
.btn-action-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-custom {
    padding: 6px 16px;
    border: none;
    border-radius: 10px;
    color: white;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.btn-custom:hover {
    transform: translateY(-2px);
    color: white;
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.btn-primary-gradient { background: var(--gradient-1); }
.btn-success-solid { background: var(--success-green); }
.btn-danger-solid { background: var(--danger-red); }
.btn-warning-solid { background: var(--warning-orange); }
.btn-info-solid { background: var(--secondary-cyan); }
.btn-purple-solid { background: #7a5af8; }

/* FEEDBACK STYLE */
.feedback-info { font-size: 0.9rem; }
.feedback-rating { color: #f59e0b; font-size: 1rem; margin-bottom: 2px; }
.feedback-date { color: #94a3b8; font-size: 0.75rem; display: block; }
.feedback-comment { color: #475569; font-style: italic; font-size: 0.85rem; margin-top: 4px; }
.no-feedback { color: #cbd5e1; font-style: italic; font-size: 0.85rem; }

/* RESPONSIVE */
@media (max-width: 992px) {
    .glass-nav { padding: 12px 20px; }
    .nav-menu { gap: 15px; }
}

@media (max-width: 768px) {
    .glass-nav { flex-direction: column; gap: 15px; }
    .nav-menu { flex-wrap: wrap; justify-content: center; }
    .nav-actions { flex-wrap: wrap; justify-content: center; }
    .btn-action-group { flex-direction: column; width: 100%; }
    .btn-custom { width: 100%; justify-content: center; }
}
</style>
</head>
<body>

<nav class="glass-nav">
    <a href="dashboard.php" class="logo text-decoration-none">Watan Freelance</a>
    <ul class="nav-menu">
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="browse_services.php">Services</a></li>
        <li><a href="about.php">About</a></li>
        <?php if ($isLoggedIn && $role === 'freelancer'): ?>
            <li><a href="freelancer_booking_list.php" class="fw-bold text-primary">Booking List</a></li>
        <?php elseif ($isLoggedIn && $role === 'client'): ?>
            <li><a href="client_booking_list.php">Booking List</a></li>
        <?php endif; ?>
    </ul>
    <div class="nav-actions">
        <?php if ($isLoggedIn): ?>
            <span class="d-none d-md-inline text-muted small">Welcome, <strong><?php echo htmlspecialchars($currentUsername); ?></strong></span>
            <a class="link-signin" href="<?php echo $profileLink; ?>">
                <i class="bi bi-person-gear"></i> <?php echo $profileLabel; ?>
            </a>
            <a class="link-signin" href="logout.php">Logout</a>
        <?php else: ?>
            <a class="link-signin" href="login.php">Sign in</a>
            <button class="btn-join-nav" onclick="location.href='registration.php'">Join</button>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <h1 class="page-title">My Bookings</h1>

    <?php if(count($jobInReview)): ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-hourglass-split me-2"></i>Job In Review</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th width="25%">Description</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($jobInReview as $b): 
                            $statusLabel = $status_labels[$b["booking_status"]] ?? $b["booking_status"];
                            $statusClass = "needs-review"; 
                        ?>
                        <tr>
                            <td>#<?= $b["booking_id"] ?></td>
                            <td><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($b["client_name"] ?? "ID: ".$b["client_id"]) ?></td>
                            <td><?= htmlspecialchars($b["service_title"]) ?></td>
                            <td><?= htmlspecialchars($b["description"]) ?></td>
                            <td><?= date('M d, Y', strtotime($b["deadline"])) ?></td>
                            <td><span class="status <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                            <td>
                                <div class="btn-action-group">
                                    <a href="accept_booking.php?bid=<?= $b['booking_id'] ?>" class="btn-custom btn-success-solid"><i class="bi bi-check-lg"></i> Accept</a>
                                    <a href="reject_booking.php?bid=<?= $b['booking_id'] ?>" class="btn-custom btn-danger-solid"><i class="bi bi-x-lg"></i> Reject</a>
                                    <a href="chat.php?booking_id=<?= $b['booking_id'] ?>" class="btn-custom btn-primary-gradient"><i class="bi bi-chat-dots"></i> Chat</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(count($jobInProgress)): ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-gear-wide-connected me-2"></i>Job In Progress</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th width="25%">Description</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($jobInProgress as $b): 
                             $statusLabel = $status_labels[$b["booking_status"]] ?? $b["booking_status"];
                             $statusClass = "job-in-progress";
                        ?>
                        <tr>
                            <td>#<?= $b["booking_id"] ?></td>
                            <td><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($b["client_name"] ?? "ID: ".$b["client_id"]) ?></td>
                            <td><?= htmlspecialchars($b["service_title"]) ?></td>
                            <td><?= htmlspecialchars($b["description"]) ?></td>
                            <td><?= date('M d, Y', strtotime($b["deadline"])) ?></td>
                            <td><span class="status <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                            <td>
                                <div class="btn-action-group">
                                    <a href="upload_deliverables.php?bid=<?= $b['booking_id'] ?>" class="btn-custom btn-info-solid"><i class="bi bi-cloud-upload"></i> Upload</a>
                                    <a href="chat.php?booking_id=<?= $b['booking_id'] ?>" class="btn-custom btn-primary-gradient"><i class="bi bi-chat-dots"></i> Chat</a>
                                    <a href="cancel_booking.php?bid=<?= $b['booking_id'] ?>" class="btn-custom btn-danger-solid" onclick="return confirm('Are you sure?')"><i class="bi bi-x-circle"></i> Cancel</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(count($jobPendingVerification)): ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-clipboard-check me-2"></i>Pending Verification</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th width="30%">Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($jobPendingVerification as $b): 
                             $statusLabel = $status_labels[$b["booking_status"]] ?? $b["booking_status"];
                             $statusClass = "booking-request";
                        ?>
                        <tr>
                            <td>#<?= $b["booking_id"] ?></td>
                            <td><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($b["client_name"] ?? "ID: ".$b["client_id"]) ?></td>
                            <td><?= htmlspecialchars($b["service_title"]) ?></td>
                            <td><?= htmlspecialchars($b["description"]) ?></td>
                            <td><span class="status <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                            <td>
                                <div class="btn-action-group">
                                    <a href="view_deliverable.php?bid=<?= $b['booking_id'] ?>" class="btn-custom btn-primary-gradient"><i class="bi bi-eye"></i> View Work</a>
                                    <span class="text-muted small ms-2 align-self-center"><i class="bi bi-clock"></i> Awaiting Client</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(count($jobCompleted)): ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-check-circle-fill me-2"></i>Completed Jobs</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Feedback</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($jobCompleted as $b): 
                             $statusLabel = $status_labels[$b["booking_status"]] ?? $b["booking_status"];
                             $statusClass = "job-completed";
                        ?>
                        <tr>
                            <td>#<?= $b["booking_id"] ?></td>
                            <td><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($b["client_name"] ?? "ID: ".$b["client_id"]) ?></td>
                            <td><?= htmlspecialchars($b["service_title"]) ?></td>
                            <td><span class="status <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                            <td>
                                <?php if($b["feedback_id"]): ?>
                                    <div class="feedback-info">
                                        <div class="feedback-rating"><?= str_repeat('★', $b['feedback_rating']) ?><?= str_repeat('☆', 5 - $b['feedback_rating']) ?></div>
                                        <span class="feedback-date"><?= date('M j, Y', strtotime($b['feedback_date'])) ?></span>
                                        <div class="feedback-comment">"<?= htmlspecialchars(substr($b['feedback_comment'], 0, 40)) ?>..."</div>
                                    </div>
                                <?php else: ?>
                                    <span class="no-feedback">No feedback yet</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-action-group">
                                    <a href="view_deliverable.php?bid=<?= $b['booking_id'] ?>" class="btn-custom btn-primary-gradient"><i class="bi bi-eye"></i> Work</a>
                                    <?php if($b["feedback_id"]): ?>
                                        <a href="view_feedback.php?booking_id=<?= $b['booking_id'] ?>" class="btn-custom btn-purple-solid"><i class="bi bi-star"></i> Feedback</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(count($jobRejected) > 0 || count($jobCancelled) > 0): ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-archive me-2"></i>Rejected / Cancelled Jobs</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $allClosed = array_merge($jobRejected, $jobCancelled);
                        foreach($allClosed as $b): 
                            $statusLabel = $status_labels[$b["booking_status"]] ?? $b["booking_status"];
                            $statusClass = ($b["booking_status"] == 'Job Rejected') ? "job-rejected" : "job-cancelled";
                        ?>
                        <tr>
                            <td>#<?= $b["booking_id"] ?></td>
                            <td><?= htmlspecialchars($b["client_name"] ?? "ID: ".$b["client_id"]) ?></td>
                            <td><?= htmlspecialchars($b["service_title"]) ?></td>
                            <td><?= htmlspecialchars($b["description"]) ?></td>
                            <td><span class="status <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                            <td>
                                <a href="view_jobdetails.php?bid=<?= $b['booking_id'] ?>" class="btn-custom btn-primary-gradient"><i class="bi bi-info-circle"></i> Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(empty($bookings)): ?>
        <div class="card text-center p-5">
            <div class="text-muted">
                <i class="bi bi-journal-x" style="font-size: 3rem;"></i>
                <h3 class="mt-3">No Bookings Found</h3>
                <p>You haven't received any bookings yet.</p>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>