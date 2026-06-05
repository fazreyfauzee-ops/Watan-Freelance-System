<?php
session_start(); // Added to support the Navbar logic
require_once "database.php";

// --- 1. ORIGINAL LOGIC START ---
$bid = isset($_GET['bid']) ? $_GET['bid'] : null;
if (!$bid) {
    exit("Missing booking ID.");
}

$stmt = $conn->prepare("
    SELECT 
        booking_id,
        client_id,
        freelancer_id,
        service_title,
        description,
        deadline,
        calculated_total_price,
        location,
        booking_status
    FROM booking
    WHERE booking_id = :bid
");
$stmt->bindParam(':bid', $bid);
$stmt->execute();
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    exit("Booking not found.");
}
// --- 1. ORIGINAL LOGIC END ---

// --- 2. NAVBAR SESSION LOGIC (From reference file) ---
$clientID = isset($_SESSION['id']) ? (int)$_SESSION['id'] : null;
$isLoggedIn = isset($_SESSION['username']);
$currentUsername = $_SESSION['username'] ?? '';
$role = strtolower($_SESSION['role'] ?? $_SESSION['user_type'] ?? '');
$profileLink = ($role === 'client') ? 'client.php' : 'freelancer_form.php';
$profileLabel = ($role === 'client') ? 'Edit Client Profile' : 'Edit Freelancer Profile';

// Helper for status styling (Matches your list page)
$status_labels = [
    "Job In Review"            => "Job In Review",
    "Job In Progress"          => "Job In Progress",
    "Job Pending Verification" => "Pending Verification",
    "Job Completed"            => "Job Completed",
    "Job Rejected"             => "Job Rejected"
];

$rawStatus = $booking['booking_status'];
$statusLabel = $status_labels[$rawStatus] ?? $rawStatus;

// Map classes based on text
$statusClass = "booking-request"; // default
if($rawStatus == "Job In Review") $statusClass = "needs-review";
elseif($rawStatus == "Job In Progress") $statusClass = "job-in-progress";
elseif($rawStatus == "Job Completed") $statusClass = "job-completed";
elseif($rawStatus == "Job Rejected" || $rawStatus == "Job Cancelled") $statusClass = "job-rejected";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details | Watan Freelance</title>

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
            --card-shadow:0 10px 30px rgba(0,0,0,0.1);
            --hover-shadow:0 20px 40px rgba(0,0,0,0.15);
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            font-family: 'Poppins', sans-serif;
            padding-top: 80px; /* Space for fixed nav */
            min-height: 100vh;
            color: #334155;
            padding-bottom: 40px;
        }

        /* NAVBAR STYLES */
        .glass-nav {
            position: fixed; top: 0; width: 100%;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 60px; z-index: 1000;
        }
        .logo {
            font-size: 1.6rem; font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            text-decoration: none;
        }
        .nav-menu { display: flex; list-style: none; gap: 25px; align-items: center; margin: 0; }
        .nav-menu li a { text-decoration: none; color: #475569; font-weight: 500; transition: color .2s ease; }
        .nav-menu li a:hover { color: var(--primary-purple); }
        .nav-actions { display: flex; align-items: center; gap: 15px; }
        .btn-join-nav {
            background: var(--gradient-1); color: #fff; border: none; padding: 8px 20px;
            border-radius: 999px; font-weight: 600; text-decoration: none;
            box-shadow: 0 4px 10px rgba(122, 90, 248, 0.3); transition: all 0.3s ease;
        }
        .btn-join-nav:hover { transform: translateY(-2px); color: #fff; box-shadow: 0 6px 15px rgba(122, 90, 248, 0.4); }
        .link-signin {
            color: #475569; text-decoration: none; font-weight: 500; padding: 8px 16px;
            border-radius: 12px; transition: all 0.2s ease; background: rgba(255,255,255,0.5);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .link-signin:hover { color: var(--primary-purple); background-color: #f3f1ff; transform: translateY(-1px); }

        /* CONTENT STYLES */
        .page-title {
            text-align: center; color: #fff; font-weight: 700;
            font-size: 2.2rem; margin-bottom: 30px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .glass-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 25px;
        }

        .card-header-custom {
            background: var(--gradient-1);
            color: #fff;
            font-weight: 700;
            padding: 18px 25px;
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        
        /* Shimmer Effect */
        .card-header-custom::after {
            content: ''; position: absolute; top: -50%; right: -50%; width: 100%; height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: shimmer 5s infinite;
        }
        @keyframes shimmer { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .card-body-custom { padding: 30px; }

        /* Typography for Details */
        .detail-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
            display: block;
        }
        .detail-value {
            font-size: 1rem;
            color: #1e293b;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .detail-value.highlight { color: var(--primary-purple); font-weight: 700; font-size: 1.1rem; }
        
        /* Status Badges */
        .status { 
            padding: 6px 14px; border-radius: 12px; font-weight: 600; font-size: 0.85rem; 
            display: inline-block; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .status.needs-review { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .status.job-in-progress { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .status.booking-request { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
        .status.job-completed { background: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe; }
        .status.job-rejected { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        /* Buttons */
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.4);
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        .btn-back:hover { background: rgba(255,255,255,0.3); color: #fff; transform: translateX(-3px); }

        .file-link {
            display: inline-flex; align-items: center; gap: 8px;
            background: #f1f5f9; color: var(--primary-dark);
            padding: 10px 15px; border-radius: 8px; text-decoration: none;
            font-weight: 600; transition: background 0.2s;
        }
        .file-link:hover { background: #e2e8f0; color: var(--primary-purple); }

        @media (max-width: 992px) { .glass-nav { padding: 12px 20px; } }
        @media (max-width: 768px) {
            .glass-nav { flex-direction: column; gap: 15px; }
            .nav-menu, .nav-actions { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>

    <nav class="glass-nav">
        <a href="dashboard.php" class="logo">Watan Freelance</a>
        <ul class="nav-menu">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="browse_services.php">Services</a></li>
            <li><a href="about.php">About</a></li>
            <?php if ($isLoggedIn && $role === 'freelancer'): ?>
                <li><a href="freelancer_booking_list.php">Booking List</a></li>
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

    <div class="container" style="max-width: 1000px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="javascript:history.back()" class="btn-back"><i class="bi bi-arrow-left"></i> Back</a>
        </div>

        <h1 class="page-title">
            Job Details 
            <span style="display:block; font-size: 1rem; opacity: 0.8; font-weight: 400;">Booking #<?php echo htmlspecialchars($booking['booking_id']); ?></span>
        </h1>

        <div class="glass-card">
            <div class="card-header-custom">
                <i class="bi bi-info-circle-fill me-2"></i> General Information
            </div>
            <div class="card-body-custom">
                <div class="row">
                    <div class="col-md-6">
                        <span class="detail-label">Service Title</span>
                        <div class="detail-value highlight"><?php echo htmlspecialchars($booking['service_title']); ?></div>

                        <span class="detail-label">Budget</span>
                        <div class="detail-value">
                            <?php if (!empty($booking['calculated_total_price'])): ?>
                                RM <?php echo number_format((float)$booking['calculated_total_price'], 2); ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </div>

                        <span class="detail-label">Location</span>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['location'] ?? 'Remote/Not specified'); ?></div>
                    </div>

                    <div class="col-md-6">
                        <span class="detail-label">Current Status</span>
                        <div class="detail-value">
                            <span class="status <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($statusLabel); ?>
                            </span>
                        </div>

                        <span class="detail-label">Deadline</span>
                        <div class="detail-value">
                            <i class="bi bi-calendar-event me-1 text-muted"></i>
                            <?php echo htmlspecialchars($booking['deadline']); ?>
                        </div>

                        <span class="detail-label">IDs involved</span>
                        <div class="detail-value small text-muted">
                            Client ID: #<?php echo htmlspecialchars($booking['client_id']); ?> <br>
                            Freelancer ID: #<?php echo htmlspecialchars($booking['freelancer_id']); ?>
                        </div>
                    </div>

                    <div class="col-12 mt-2">
                        <hr class="opacity-25 my-3">
                        <span class="detail-label">Description</span>
                        <div class="detail-value" style="white-space: pre-line; color: #475569;">
                            <?php if (!empty($booking['description'])): ?>
                                <?php echo nl2br(htmlspecialchars($booking['description'])); ?>
                            <?php else: ?>
                                <span class="text-muted fst-italic">No description provided.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="glass-card">
            <div class="card-header-custom">
                <i class="bi bi-box-seam-fill me-2"></i> Deliverables
            </div>
            <div class="card-body-custom">
                <?php
                    $historyFile = __DIR__ . "/uploads/history.json";
                    $history = [];
                    if (file_exists($historyFile)) {
                        $history = json_decode(file_get_contents($historyFile), true) ?: [];
                    }
                    $latest = !empty($history[$bid]) ? end($history[$bid]) : null;
                ?>

                <?php if ($latest && !empty($latest['file'])): ?>
                    <div class="d-flex flex-column gap-2">
                        <span class="detail-label">Latest Upload</span>
                        <div>
                            <a href="<?php echo htmlspecialchars($latest['file']); ?>" target="_blank" class="file-link">
                                <i class="bi bi-file-earmark-arrow-down"></i>
                                <?php echo htmlspecialchars($latest['original'] ?: basename($latest['file'])); ?>
                            </a>
                        </div>
                        
                        <?php if (!empty($latest['uploaded_at'])): ?>
                            <div class="text-muted small mt-1">
                                <i class="bi bi-clock"></i> Uploaded: <?php echo htmlspecialchars($latest['uploaded_at']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($latest['description'])): ?>
                            <div class="alert alert-light mt-3 mb-0 border">
                                <strong>Note:</strong> <?php echo nl2br(htmlspecialchars($latest['description'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-folder2-open display-4 opacity-50 mb-2 d-block"></i>
                        No deliverable uploaded yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script>
        window.addEventListener('scroll', function() {
            var nav = document.querySelector('nav');
            if (window.scrollY > 10) {
                nav.style.boxShadow = '0 4px 12px rgba(0,0,0,0.12)';
                nav.style.backgroundColor = 'rgba(255,255,255,0.95)';
            } else {
                nav.style.boxShadow = '0 2px 5px rgba(0,0,0,0.08)';
                nav.style.backgroundColor = 'rgba(255,255,255,0.95)';
            }
        });
    </script>
</body>
</html>