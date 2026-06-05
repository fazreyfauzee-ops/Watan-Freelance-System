<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Get booking_id from URL
$booking_id = $_GET['booking_id'] ?? null;
if (!$booking_id) {
    echo "<script>alert('Missing booking ID.'); window.location.href='booking_list.php';</script>";
    exit;
}

try {
    // Fetch booking and feedback information
    $stmt = $conn->prepare("
        SELECT 
            b.*,
            u1.fullname as client_name,
            u1.email as client_email,
            u2.fullname as freelancer_name,
            u2.email as freelancer_email,
            f.rating,
            f.comment,
            f.created_at as feedback_date
        FROM booking b
        LEFT JOIN users u1 ON b.client_id = u1.id
        LEFT JOIN users u2 ON b.freelancer_id = u2.id
        LEFT JOIN feedback f ON b.booking_id = f.booking_id
        WHERE b.booking_id = :booking_id
        LIMIT 1
    ");
    $stmt->execute([':booking_id' => $booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo "<script>alert('Booking not found.'); window.location.href='booking_list.php';</script>";
        exit;
    }
    
} catch (PDOException $e) {
    echo "<script>alert('Error loading booking data.'); window.location.href='booking_list.php';</script>";
    exit;
}

// Get user info for navigation
$isLoggedIn = isset($_SESSION['username']);
$currentUsername = $_SESSION['username'] ?? '';
$role = strtolower($_SESSION['role'] ?? $_SESSION['user_type'] ?? '');
$profileLink = ($role === 'client') ? 'client.php' : 'freelancer_form.php';
$profileLabel = ($role === 'client') ? 'Edit Client Profile' : 'Edit Freelancer Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback for Booking <?php echo htmlspecialchars($booking['booking_id']); ?> | Watan Freelance System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #4facfe;
            --warning-color: #fa709a;
            --text-dark: #2d3748;
            --text-light: #718096;
            --bg-light: #f7fafc;
            --white: #ffffff;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 25px rgba(0,0,0,0.1);
            --border-radius: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: url('background_roundabout.jpg') no-repeat center center/cover;
            background-attachment: fixed;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(102, 126, 234, 0.15);
            z-index: -1;
        }
        
        /* Navigation Bar */
        nav {
            background-color: rgba(255,255,255,0.9);
            backdrop-filter: saturate(180%) blur(6px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 60px;
            position: sticky;
            top: 0;
            z-index: 10;
            transition: box-shadow .2s ease, background-color .2s ease;
        }

        nav .logo {
            font-size: 1.6rem;
            font-weight: 700;
            color: #7a5af8;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 25px;
        }

        nav ul li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            position: relative;
            padding-bottom: 4px;
            transition: color .2s ease;
        }

        nav ul li a:hover { color: #7a5af8; }

        nav ul li a::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 2px;
            background: #7a5af8;
            transition: width .2s ease;
        }

        nav ul li a:hover::after { width: 100%; }

        .nav-actions { display: flex; align-items: center; gap: 10px; }

        .btn-join-nav {
            background: #7a5af8;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 999px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s ease;
        }

        .btn-join-nav:hover { background: #6948f0; }

        .link-signin {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            padding: 6px 10px;
            border-radius: 8px;
            transition: color .2s ease, background-color .2s ease;
        }

        .link-signin:hover { 
            color: #7a5af8; 
            background-color: #f3f1ff; 
        }
        
        /* Main Container */
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Header Section */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInDown 0.8s ease;
        }

        .header-icon {
            width: 80px;
            height: 80px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: var(--shadow-lg);
            animation: pulse 2s infinite;
        }

        .header-icon i {
            font-size: 2rem;
            color: white;
        }

        /* Cards */
        .card {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.9) 100%);
            border: 2px solid transparent;
            border-image: linear-gradient(135deg, #667eea, #764ba2, #f093fb, #f5576c) 1;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2), 0 20px 25px rgba(118, 75, 162, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease;
            transition: all 0.3s ease;
            position: relative;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #f5576c, #ffc107);
            animation: gradient-shift 3s ease-in-out infinite;
        }

        .card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px rgba(102, 126, 234, 0.3), 0 30px 40px rgba(118, 75, 162, 0.2);
        }

        .card-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }

        .card-header i {
            font-size: 1.8rem;
            color: #ffffff;
            text-shadow: 0 0 10px rgba(255,255,255,0.5);
            animation: icon-glow 2s ease-in-out infinite alternate;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .card-body {
            padding: 2rem;
        }

        /* Booking Info Grid */
        .booking-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 50%, #f1f3f5 100%);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border-left: 4px solid;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .info-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent, rgba(255,255,255,0.5), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .info-item:hover::before {
            opacity: 1;
        }

        .info-item:hover {
            transform: translateX(8px) scale(1.02);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .info-item.primary {
            border-left-color: #667eea;
            background: linear-gradient(135deg, #f0f4ff 0%, #ffffff 50%, #e8f0ff 100%);
        }

        .info-item.success {
            border-left-color: #4facfe;
            background: linear-gradient(135deg, #e6f7ff 0%, #ffffff 50%, #d6f5ff 100%);
        }

        .info-item.warning {
            border-left-color: #fa709a;
            background: linear-gradient(135deg, #fff0f5 0%, #ffffff 50%, #ffe0e6 100%);
        }

        .info-label i {
            font-size: 1.2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .info-label {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Feedback Section */
        .feedback-display {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .feedback-display::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stars {
            display: flex;
            gap: 0.25rem;
        }

        .star {
            font-size: 1.5rem;
            color: #ddd;
            transition: color 0.3s ease, transform 0.2s ease;
        }

        .star.filled {
            color: #ffc107;
            animation: starPop 0.3s ease;
        }

        .star:hover {
            transform: scale(1.2);
        }

        .rating-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .rating-badge {
            background: var(--success-gradient);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .comment-box {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            position: relative;
        }

        .comment-box::before {
            content: '"';
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            font-size: 3rem;
            color: var(--primary-color);
            opacity: 0.2;
        }

        .comment-text {
            font-size: 1.05rem;
            line-height: 1.6;
            color: var(--text-dark);
            margin-left: 1rem;
        }

        .feedback-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* No Feedback State */
        .no-feedback {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-light);
        }

        .no-feedback-icon {
            width: 100px;
            height: 100px;
            background: var(--bg-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .no-feedback-icon i {
            font-size: 3rem;
            color: var(--text-light);
        }

        .no-feedback h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #764ba2 0%, #f093fb 50%, #f5576c 100%);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 50%, #e9ecef 100%);
            color: #667eea;
            border: 2px solid #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
        }

        .btn-secondary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .btn i {
            font-size: 1.2rem;
            animation: icon-bounce 2s ease-in-out infinite;
        }
        
        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes starPop {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }

        @keyframes colorful-text {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        @keyframes icon-glow {
            0% { text-shadow: 0 0 10px rgba(255,255,255,0.5); }
            100% { text-shadow: 0 0 20px rgba(255,255,255,0.8), 0 0 30px rgba(255,255,255,0.5); }
        }

        @keyframes icon-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-container {
                padding: 1rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .booking-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        @keyframes colorful-text {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav>
    <div class="logo">Watan Freelance System</div>
    <ul>
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
            <span>Welcome, <?php echo htmlspecialchars($currentUsername); ?></span>
            <a class="link-signin" href="<?php echo $profileLink; ?>" style="background-color: #f3f1ff; color: #7a5af8; font-weight: 600;">
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
        <!-- Header Section -->
        <div class="page-header">
            <div class="header-icon">
                <i class="bi bi-star-fill"></i>
            </div>
            <h1 style="background: linear-gradient(45deg, #667eea, #764ba2, #f093fb, #f5576c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-size: 3rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; animation: colorful-text 3s ease-in-out infinite; margin-bottom: 0.5rem;">VIEW FEEDBACK</h1>
            <p style="color: rgba(255, 255, 255, 0.9); font-size: 1.1rem;">Booking #<?php echo htmlspecialchars($booking['booking_id']); ?></p>
        </div>

        <!-- Booking Information Card -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle-fill"></i>
                <h2 class="card-title">Booking Information</h2>
            </div>
            <div class="card-body">
                <div class="booking-grid">
                    <div class="info-item primary">
                        <div class="info-label">
                            <i class="bi bi-hash"></i>
                            Booking ID
                        </div>
                        <div class="info-value">#<?php echo htmlspecialchars($booking['booking_id']); ?></div>
                    </div>
                    
                    <div class="info-item success">
                        <div class="info-label">
                            <i class="bi bi-briefcase"></i>
                            Service
                        </div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['service_title']); ?></div>
                    </div>
                    
                    <div class="info-item warning">
                        <div class="info-label">
                            <i class="bi bi-person"></i>
                            Client
                        </div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['client_name']); ?></div>
                    </div>
                    
                    <div class="info-item primary">
                        <div class="info-label">
                            <i class="bi bi-tools"></i>
                            Freelancer
                        </div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['freelancer_name']); ?></div>
                    </div>
                </div>
                
                <div class="info-item" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                    <div class="info-label">
                        <i class="bi bi-file-text"></i>
                        Description
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($booking['description']); ?></div>
                </div>
            </div>
        </div>

        <!-- Feedback Card -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-star-fill"></i>
                <h2 class="card-title">Client Feedback</h2>
            </div>
            <div class="card-body">
                <?php if ($booking['rating']): ?>
                    <div class="feedback-display">
                        <div class="rating-display">
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $booking['rating'] ? 'filled' : ''; ?>">
                                        <?php echo $i <= $booking['rating'] ? '★' : '☆'; ?>
                                    </span>
                                <?php endfor; ?>
                            </div>
                            <div class="rating-text"><?php echo $booking['rating']; ?>/5.0</div>
                            <div class="rating-badge">Excellent</div>
                        </div>
                        
                        <?php if ($booking['comment']): ?>
                            <div class="comment-box">
                                <div class="comment-text">
                                    <?php echo htmlspecialchars($booking['comment']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="feedback-meta">
                            <div class="meta-item">
                                <i class="bi bi-calendar-check"></i>
                                <?php echo date('F j, Y', strtotime($booking['feedback_date'])); ?>
                            </div>
                            <div class="meta-item">
                                <i class="bi bi-clock"></i>
                                <?php echo date('g:i A', strtotime($booking['feedback_date'])); ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-feedback">
                        <div class="no-feedback-icon">
                            <i class="bi bi-star"></i>
                        </div>
                        <h3>No Feedback Yet</h3>
                        <p>Feedback has not been submitted for this booking.</p>
                        <p style="font-size: 0.9rem; margin-top: 1rem;">
                            The client will be able to provide feedback once the service is completed.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle-fill"></i>
                Back
            </a>
            <a href="<?php echo ($role === 'freelancer') ? 'freelancer_booking_list.php' : 'client_booking_list.php'; ?>" class="btn btn-primary">
                <i class="bi bi-calendar-check-fill"></i>
                All Bookings
            </a>
        </div>
    </div>

</body>
</html>

