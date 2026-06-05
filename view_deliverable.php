<?php
session_start();
$bid = isset($_GET['bid']) ? $_GET['bid'] : null;

if (!$bid) {
    echo "<script>alert('Missing booking ID.'); window.location.href='dashboard.php';</script>";
    exit;
}

require_once "database.php";

// Fetch booking data from database
try {
    $stmt = $conn->prepare("SELECT * FROM booking WHERE booking_id = :bid");
    $stmt->execute([':bid' => $bid]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo "<script>alert('Booking not found.'); window.location.href='dashboard.php';</script>";
        exit;
    }
    
    $deliverablePath = $booking['deliverables'] ?? null;
    $serviceTitle = $booking['service_title'] ?? 'N/A';
    $bookingStatus = $booking['booking_status'] ?? 'N/A';
    $description = $booking['description'] ?? '';
    $deadline = $booking['deadline'] ?? null;
    $clientId = $booking['client_id'] ?? null;
    
    // Check if feedback already exists for this booking (client-freelancer pair)
    $hasFeedback = false;
    if ($clientId && $booking['freelancer_id']) {
        try {
            $feedbackStmt = $conn->prepare("SELECT COUNT(*) as count FROM feedback WHERE client_id = :client_id AND freelancer_id = :freelancer_id");
            $feedbackStmt->execute([':client_id' => $clientId, ':freelancer_id' => $booking['freelancer_id']]);
            $feedbackCount = $feedbackStmt->fetch(PDO::FETCH_ASSOC)['count'];
            $hasFeedback = $feedbackCount > 0;
        } catch (PDOException $e) {
            // If feedback table doesn't exist, assume no feedback
            $hasFeedback = false;
        }
    }
    
} catch (PDOException $e) {
    echo "<script>alert('Error fetching booking: " . htmlspecialchars($e->getMessage()) . "'); window.location.href='dashboard.php';</script>";
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
    <title>View Deliverable | Watan Freelance System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
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
            --border-radius: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #ffffff;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
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
            align-items: center;
        }

        nav ul li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            position: relative;
            padding-bottom: 4px;
            transition: color .2s ease;
        }

        nav ul li a:hover { 
            color: #7a5af8; 
        }

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

        nav ul li a:hover::after { 
            width: 100%; 
        }

        .nav-actions { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }

        .btn-join-nav {
            background: #7a5af8;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 999px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-join-nav:hover { 
            background: #6948f0; 
        }

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
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* Header Section */
        .header-section {
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

        .header-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .header-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
        }

        /* Container */
        .view-container {
            max-width: 700px;
            margin: 60px auto;
            padding: 0 20px;
        }

        /* Cards */
        .view-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.9) 100%);
            border: 2px solid transparent;
            border-image: linear-gradient(135deg, #667eea, #764ba2, #f093fb, #f5576c) 1;
            border-radius: var(--border-radius);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15), 0 15px 20px rgba(118, 75, 162, 0.08);
            overflow: hidden;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.8s ease;
            transition: all 0.3s ease;
            position: relative;
            padding: 2rem;
        }

        .view-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #f5576c, #ffc107);
            animation: gradient-shift 3s ease-in-out infinite;
        }

        .view-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.25), 0 20px 30px rgba(118, 75, 162, 0.15);
        }

        .view-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .view-header h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(45deg, #667eea, #764ba2, #f093fb, #f5576c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            animation: colorful-text 3s ease-in-out infinite;
        }

        .view-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .card-header {
            padding: 1.2rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
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
            font-size: 1.5rem;
            color: #ffffff;
            text-shadow: 0 0 8px rgba(255,255,255,0.5);
            animation: icon-glow 2s ease-in-out infinite alternate;
        }

        .booking-info-header {
            text-align: center;
            padding: 1rem 0;
        }

        .booking-id-display {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #f0f4ff 0%, #ffffff 50%, #e8f0ff 100%);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            border: 2px solid transparent;
            background-clip: padding-box;
            position: relative;
            overflow: hidden;
        }

        .booking-id-display::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #f5576c);
            z-index: -1;
            padding: 2px;
            mask: linear-gradient(#fff 0 0 content-box, #000 0 0);
            -webkit-mask: linear-gradient(#fff 0 0 content-box, #000 0 0);
        }

        .booking-id-display i {
            font-size: 1.1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: icon-glow 2s ease-in-out infinite alternate;
        }

        .booking-id-display span {
            font-size: 1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .booking-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 50%, #f1f3f5 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
            position: relative;
            overflow: hidden;
        }

        .booking-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent, rgba(102, 126, 234, 0.05), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .booking-info:hover::before {
            opacity: 1;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
        }

        .info-row:hover {
            padding-left: 10px;
            background: rgba(102, 126, 234, 0.02);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-label i {
            font-size: 1.1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .info-value {
            color: #2d3748;
            font-weight: 600;
            font-size: 1.05rem;
        }

        .info-value a {
            color: #7a5af8;
            text-decoration: none;
            font-weight: 600;
            transition: color .2s ease;
        }

        .info-value a:hover {
            color: #6948f0;
            text-decoration: underline;
        }

        /* Deliverable Section */
        .deliverable-section {
            margin-top: 30px;
            text-align: center;
            padding: 3rem;
            background: linear-gradient(135deg, #f0f4ff 0%, #ffffff 50%, #e8f0ff 100%);
            border-radius: var(--border-radius);
            border: 2px solid transparent;
            border-image: linear-gradient(135deg, #4facfe, #00f2fe) 1;
            position: relative;
            overflow: hidden;
        }

        .deliverable-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4facfe, #00f2fe, #4facfe);
            animation: gradient-shift 3s ease-in-out infinite;
        }

        .deliverable-section .icon {
            font-size: 4rem;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            animation: icon-glow 2s ease-in-out infinite alternate;
        }

        .deliverable-section h3 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }

        .deliverable-section p {
            color: #4a5568;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        .btn-download {
            display: inline-block;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
        }

        .btn-download::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-download:hover::before {
            left: 100%;
        }

        .btn-download:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 35px rgba(79, 172, 254, 0.4);
            background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
        }

        .btn-feedback {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            margin: 10px 5px;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }

        .btn-feedback::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-feedback:hover::before {
            left: 100%;
        }

        .btn-feedback:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 35px rgba(40, 167, 69, 0.4);
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
        }

        .btn-feedback:disabled {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            cursor: not-allowed;
            transform: none;
            opacity: 0.6;
            box-shadow: none;
        }

        .btn-back {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 25%, #c44569 50%, #a8395a 75%, #8b2f4c 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border: none;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.4), 0 15px 25px rgba(196, 69, 105, 0.3);
            z-index: 5;
        }

        .btn-back::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.6s ease;
        }

        .btn-back::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .btn-back:hover::before {
            left: 100%;
        }

        .btn-back:hover::after {
            transform: translateX(100%);
        }

        .btn-back:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 40px rgba(255, 107, 107, 0.5), 0 25px 35px rgba(196, 69, 105, 0.4);
            background: linear-gradient(135deg, #ee5a6f 0%, #c44569 25%, #a8395a 50%, #8b2f4c 75%, #6b2439 100%);
        }

        .btn-back:active {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.4), 0 12px 25px rgba(196, 69, 105, 0.3);
        }

        .btn-back i {
            font-size: 1.3rem;
            margin-right: 0.75rem;
            animation: icon-bounce 2s ease-in-out infinite;
        }

        /* Action Buttons Container */
        .action-buttons {
            display: flex;
            justify-content: center;
            margin-top: 3rem;
            padding: 2rem 0;
        }

        .no-deliverable {
            color: #888;
            font-style: italic;
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

        @keyframes icon-glow {
            0% { filter: brightness(1); }
            50% { filter: brightness(1.2); }
            100% { filter: brightness(1); }
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        @media (max-width: 768px) {
            .view-container {
                margin: 30px auto;
                padding: 0 15px;
            }

            .view-card {
                padding: 25px;
            }

            .view-header h1 {
                font-size: 1.5rem;
            }

            nav {
                padding: 12px 20px;
            }

            nav ul {
                gap: 15px;
            }

            nav .logo {
                font-size: 1.3rem;
            }
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

    <!-- View Container -->
    <div class="container">
        <!-- Header Card -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-file-earmark-check-fill"></i>
                <h2 class="card-title">VIEW DELIVERABLE</h2>
            </div>
            <div class="card-body">
                <div class="booking-info-header">
                    <div class="booking-id-display">
                        <i class="bi bi-hash"></i>
                        <span>Booking <?php echo htmlspecialchars($bid); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="view-card">
            <div class="view-header">
                <h1>Deliverable Details</h1>
                <p>Service Information</p>
            </div>

            <!-- Booking Information -->
            <div class="booking-info">
                <div class="info-row">
                    <span class="info-label"><i class="bi bi-briefcase"></i> Service:</span>
                    <span class="info-value"><?php echo htmlspecialchars($serviceTitle); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="bi bi-info-circle"></i> Status:</span>
                    <span class="info-value"><?php echo htmlspecialchars($bookingStatus); ?></span>
                </div>
                <?php if ($deadline): ?>
                <div class="info-row">
                    <span class="info-label"><i class="bi bi-calendar"></i> Deadline:</span>
                    <span class="info-value"><?php echo htmlspecialchars(date('d M Y', strtotime($deadline))); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Deliverable Section -->
            <div class="deliverable-section">
                <?php if ($deliverablePath && file_exists($deliverablePath)): ?>
                    <div class="icon">
                        <i class="bi bi-file-earmark-check"></i>
                    </div>
                    <h3>Deliverable Available</h3>
                    <p>The deliverable has been uploaded and is ready for review.</p>
                    <a href="<?php echo htmlspecialchars($deliverablePath); ?>" target="_blank" class="btn-download">
                        <i class="bi bi-download"></i> Download Deliverable
                    </a>
                    
                    <!-- Feedback Section -->
                    <?php if ($isLoggedIn && $role === 'client'): ?>
                        <?php if ($hasFeedback): ?>
                            <div class="feedback-submitted">
                                <i class="bi bi-check-circle-fill"></i> Feedback submitted
                            </div>
                        <?php else: ?>
                            <?php if ($bookingStatus === 'Job Completed'): ?>
                                <a href="feedback_form.php?freelancer_id=<?= htmlspecialchars($booking['freelancer_id']) ?>" class="btn-feedback">
                                    <i class="bi bi-star-fill"></i> Rate Freelancer
                                </a>
                            <?php else: ?>
                                <button class="btn-feedback" disabled title="Available after job is completed">
                                    <i class="bi bi-star-fill"></i> Rate Freelancer
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="icon">
                        <i class="bi bi-file-earmark-x"></i>
                    </div>
                    <h3>No Deliverable Yet</h3>
                    <p class="no-deliverable">The freelancer has not uploaded a deliverable for this booking.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="javascript:history.back()" class="btn-back">
                <i class="bi bi-arrow-left-circle-fill"></i>
                Back
            </a>
        </div>
    </div>

    <script>
        // Enhance nav shadow on scroll
        (function() {
            var nav = document.querySelector('nav');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 10) {
                    nav.style.boxShadow = '0 4px 12px rgba(0,0,0,0.12)';
                    nav.style.backgroundColor = 'rgba(255,255,255,0.95)';
                } else {
                    nav.style.boxShadow = '0 2px 5px rgba(0,0,0,0.08)';
                    nav.style.backgroundColor = 'rgba(255,255,255,0.9)';
                }
            });
        })();
    </script>

</body>
</html>
