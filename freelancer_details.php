<?php
session_start();
include_once 'database.php';

// Accept both 'id' and 'user_id' for compatibility
$freelancer_id = isset($_GET['user_id']) ? $_GET['user_id'] : (isset($_GET['id']) ? $_GET['id'] : null);

if (!$freelancer_id) {
    $_SESSION['error'] = "No freelancer ID provided.";
    header("Location: browse_services.php");
    exit();
}

// Validate freelancer_id is numeric
if (!is_numeric($freelancer_id)) {
    $_SESSION['error'] = "Invalid freelancer ID.";
    header("Location: browse_services.php");
    exit();
}

try {
    // Correct: Join freelancers.user_id ↔ users.id
    $stmt = $conn->prepare("
        SELECT 
            freelancers.*, 
            users.fullname AS name,
            users.email AS user_email,
            users.phone AS user_phone,
            users.username AS username
        FROM freelancers
        INNER JOIN users ON freelancers.user_id = users.id
        WHERE freelancers.user_id = :id
    ");

    $stmt->bindParam(':id', $freelancer_id, PDO::PARAM_INT);
    $stmt->execute();
    $freelancer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$freelancer) {
        $_SESSION['error'] = "Freelancer not found.";
        header("Location: browse_services.php");
        exit();
    }

    // Fetch freelancer reviews with safe defaults
    $reviews = [];
    $totalReviews = 0;
    $averageRating = 0;
    
    try {
        // First check if feedback table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'feedback'")->fetchColumn();
        if ($tableCheck) {
            $reviewsStmt = $conn->prepare("
                SELECT 
                    f.rating,
                    f.comment,
                    f.created_at,
                    c.fullname AS client_name,
                    c.username AS client_username
                FROM feedback f
                INNER JOIN users c ON f.client_id = c.id
                WHERE f.freelancer_id = :freelancer_id
                ORDER BY f.created_at DESC
            ");
            $reviewsStmt->bindParam(':freelancer_id', $freelancer_id, PDO::PARAM_INT);
            $reviewsStmt->execute();
            $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate rating statistics
            $totalReviews = count($reviews);
            if ($totalReviews > 0) {
                $totalRating = array_sum(array_column($reviews, 'rating'));
                $averageRating = round($totalRating / $totalReviews, 1);
            }
        }
    } catch (PDOException $e) {
        // If feedback table doesn't exist or query fails, continue with empty reviews
        $reviews = [];
        $totalReviews = 0;
        $averageRating = 0;
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "An error occurred while loading the freelancer profile. Please try again.";
    header("Location: browse_services.php");
    exit();
}

// Safe default image
$profile_img = !empty($freelancer['profile_picture']) 
                ? "uploads/" . htmlspecialchars($freelancer['profile_picture'])
                : (file_exists('default_pic.jpg') ? 'default_pic.jpg' : (file_exists('default_pic.png') ? 'default_pic.png' : 'default_pic.jpg'));

$qr_img = !empty($freelancer['qr_code']) 
                ? "uploads/" . htmlspecialchars($freelancer['qr_code'])
                : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($freelancer['name']) ?> – Freelancer Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">

    <style>
        /* ====== your original CSS (unchanged) ====== */
        :root {
            --primary-purple: #7a5af8;
            --primary-dark: #6948f0;
            --secondary-bg: #f5f7fa;
            --card-border: #e4e4e4;
            --text-dark: #1f1f1f;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 0;
            pointer-events: none;
        }
        nav {
            background-color: rgba(255,255,255,0.9);
            backdrop-filter: saturate(180%) blur(6px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 60px;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1001;
        }
        nav .logo {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary-purple);
        }
        nav ul {
            display: flex;
            list-style: none;
            gap: 25px;
            margin: 0;
            padding: 0;
        }
        nav ul li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            position: relative;
            padding-bottom: 4px;
        }
        nav ul li a:hover { color: var(--primary-purple); }
        nav ul li a::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 2px;
            background: var(--primary-purple);
            transition: width .2s ease;
        }
        nav ul li a:hover::after { width: 100%; }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .btn-join-nav {
            background: var(--primary-purple);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 999px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s ease;
            text-decoration: none;
        }
        .btn-join-nav:hover { background: var(--primary-dark); }
        .link-signin:hover { color: var(--primary-purple); background-color: #f3f1ff; }
        .hero-banner {
            width: 100%;
            min-height: 450px;
            background: url('browse.jpg') center center/cover no-repeat;
            position: relative;
            margin-bottom: 50px;
            overflow: hidden;
            border-radius: 0 0 20px 20px;
            z-index: 1;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.55);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            min-height: 450px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 60px 20px;
        }

        .hero-text {
            font-size: 3rem;
            font-weight: 700;
            color: #cbb2ff;
            margin-bottom: 40px;
            animation: fadeInDown 0.8s ease-out;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: #f8f8f8;
            margin-bottom: 50px;
            font-weight: 400;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

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
        .profile-card {
            max-width: 900px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 255, 0.95) 100%);
            backdrop-filter: blur(20px);
            margin: 0 auto;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(31, 38, 135, 0.15);
            border: 2px solid transparent;
            background-image: linear-gradient(rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.95)), linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-origin: border-box;
            background-clip: padding-box, border-box;
            position: relative;
            overflow: hidden;
            z-index: 3;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            border-radius: 25px 25px 0 0;
        }
        .profile-pic {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid transparent;
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(135deg, #667eea 0%, #764ba2 100%) border-box;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .profile-pic:hover {
            transform: scale(1.05) rotate(5deg);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
        }
        .skill-tag {
            display: inline-block;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin: 5px 5px 5px 0;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: default;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
            border: 1px solid rgba(79, 172, 254, 0.2);
        }

        .skill-tag:nth-child(even) {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            box-shadow: 0 4px 15px rgba(250, 112, 154, 0.3);
            border: 1px solid rgba(250, 112, 154, 0.2);
        }

        .skill-tag:nth-child(3n) {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
            box-shadow: 0 4px 15px rgba(48, 207, 208, 0.3);
            border: 1px solid rgba(48, 207, 208, 0.2);
        }

        .skill-tag:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.5);
        }
        .qr-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }
        .qr-section .info-label {
            font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .qr-section .info-label i {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .qr-img {
            width: 200px;
            height: 200px;
            object-fit: contain;
            border: 3px solid transparent;
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(135deg, #667eea 0%, #764ba2 100%) border-box;
            border-radius: 15px;
            padding: 15px;
            background: white;
            display: block;
            margin: 0 auto;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .qr-img:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
        }

        /* Reviews Section */
        .reviews-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 255, 0.95) 100%);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(31, 38, 135, 0.15);
            border: 2px solid transparent;
            background-image: linear-gradient(rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.95)), linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            background-origin: border-box;
            background-clip: padding-box, border-box;
            position: relative;
            overflow: hidden;
        }

        .reviews-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #f093fb 0%, #f5576c 50%, #fa709a 100%);
            border-radius: 25px 25px 0 0;
        }

        .reviews-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .reviews-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rating-summary {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .rating-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 4px 8px rgba(255, 215, 0, 0.3));
        }

        .rating-stars {
            display: flex;
            gap: 3px;
        }

        .star {
            font-size: 1.3rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 2px 4px rgba(255, 215, 0, 0.3));
            transition: all 0.3s ease;
        }

        .star.empty {
            background: linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: none;
        }

        .total-reviews {
            color: #666;
            font-size: 1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #666 0%, #888 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .review-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(240, 240, 255, 0.9) 100%);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .review-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 20px 20px 0 0;
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(31, 38, 135, 0.2);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reviewer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .reviewer-name {
            font-weight: 700;
            background: linear-gradient(135deg, #2c3e50 0%, #667eea 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .review-date {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .review-rating {
            display: flex;
            gap: 2px;
            margin-bottom: 8px;
        }

        .review-text {
            color: #444;
            font-weight: 500;
            line-height: 1.6;
            margin-top: 15px;
            font-style: italic;
        }

        .no-reviews {
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .no-reviews i {
            font-size: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }

        .no-reviews p {
            color: #666;
            font-weight: 500;
        }

        /* Button Styles - Override Bootstrap */
        .btn.btn-back,
        .btn.btn-chat,
        .btn.btn-book {
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            padding: 10px 20px !important;
            border-radius: 8px !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            border: none !important;
            color: white !important;
            cursor: pointer !important;
        }

        .btn-back {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3) !important;
        }

        .btn-back:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(79, 172, 254, 0.4) !important;
        }

        .btn-chat {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3) !important;
        }

        .btn-chat:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
        }

        .btn-book {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
            box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3) !important;
        }

        .btn-book:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(240, 147, 251, 0.4) !important;
        }
    </style>
</head>

<body>

<?php
    $isLoggedIn = isset($_SESSION['username']);
    $currentUsername = $_SESSION['username'] ?? '';
    $role = strtolower($_SESSION['role'] ?? $_SESSION['user_type'] ?? '');
    $profileLink = ($role === 'client') ? 'client.php' : 'freelancer_form.php';
    $profileLabel = ($role === 'client') ? 'Edit Client Profile' : 'Edit Freelancer Profile';
?>

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

<div class="hero-banner">
    <div class="hero-content">
        <h1 class="hero-text">Freelancer Profile</h1>
        <p class="hero-subtitle">Connect with talented professionals for your next project</p>
    </div>
</div>

<div class="container mb-5">

    <div class="d-flex justify-content-between align-items-center mb-4" style="max-width: 900px; margin: 0 auto;">
        <a href="browse_services.php" class="btn btn-back"><i class="bi bi-arrow-left me-2"></i> Back to Browse</a>
        <div class="d-flex gap-2" style="gap: 10px;">
            <?php if (isset($_SESSION['username'])): ?>
                <a href="chat.php?user=<?= urlencode($freelancer['username']) ?>" class="btn btn-chat">
                    <i class="bi bi-chat-dots"></i> Chat
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-chat">
                    <i class="bi bi-chat-dots"></i> Chat
                </a>
            <?php endif; ?>
            <a href="booking.php?freelancer_id=<?= $freelancer['user_id'] ?>" class="btn btn-book">
                <i class="bi bi-calendar-check"></i> Book
            </a>
        </div>
    </div>

    <div class="profile-card">
        <div class="row">

            <div class="col-lg-4 text-center border-end">
                <img src="<?= $profile_img ?>" class="profile-pic">

                <div class="profile-name mb-1"><?= htmlspecialchars($freelancer['name']) ?></div>
                <div class="profile-tagline">
                    <?= htmlspecialchars(explode('.', $freelancer['bio'])[0] ?? 'Professional Freelancer') ?>
                </div>

                <div class="availability-tag mt-3">
                    <i class="bi bi-clock me-2" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i> <?= htmlspecialchars($freelancer['availability']) ?>
                </div>

            </div>

            <div class="col-lg-8 ps-lg-4">
                <h4 class="mb-4" style="color: var(--primary-purple);"><i class="bi bi-info-circle me-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i> Detailed Information</h4>

                <div class="info-row">
                    <p class="info-label"><i class="bi bi-envelope me-1" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i> Email:</p>
                    <p><?= htmlspecialchars($freelancer['user_email']) ?></p>
                </div>

                <div class="info-row">
                    <p class="info-label"><i class="bi bi-telephone me-1" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i> Contact:</p>
                    <p><?= htmlspecialchars($freelancer['user_phone']) ?></p>
                </div>

                <h5 class="mt-4 mb-3" style="color: var(--primary-purple);"><i class="bi bi-file-text me-2" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i> Professional Bio</h5>
                <p style="white-space: pre-wrap;"><?= htmlspecialchars($freelancer['bio']) ?></p>

                <h5 class="mt-4 mb-3" style="color: var(--primary-purple);"><i class="bi bi-code-slash me-2" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i> Key Skills</h5>
                <div>
                    <?php foreach (explode(',', $freelancer['skills']) as $s): ?>
                        <span class="skill-tag"><?= htmlspecialchars(trim($s)) ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($qr_img): ?>
                <div class="qr-section">
                    <p class="info-label"><i class="bi bi-qr-code me-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i> Payment QR Code</p>
                    <img src="<?= $qr_img ?>" alt="Payment QR Code" class="qr-img">
                </div>
                <?php endif; ?>

                <p class="text-muted mt-4 text-end" style="font-size:13px;">
                    Profile Last updated: <?= htmlspecialchars($freelancer['updated_at']) ?>
                </p>
            </div>

        </div>

        <!-- Reviews Section -->
        <div class="reviews-section">
            <div class="reviews-header">
                <h4 style="color: var(--primary-purple); margin: 0;">
                    <i class="bi bi-star-fill me-2"></i>Client Reviews
                </h4>
                <div class="rating-summary">
                    <div class="rating-number"><?= $averageRating ?></div>
                    <div>
                        <div class="rating-stars">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= $averageRating ? '' : 'empty' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <div class="total-reviews"><?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?></div>
                    </div>
                </div>
            </div>

            <?php if ($totalReviews > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <?= strtoupper(substr($review['client_name'] ?? $review['client_username'] ?? 'A', 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="reviewer-name"><?= htmlspecialchars($review['client_name'] ?? $review['client_username']) ?></div>
                                    <div class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="review-rating">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= $review['rating'] ? '' : 'empty' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <div class="review-text">
                            <?= htmlspecialchars($review['comment']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-reviews">
                    <i class="bi bi-chat-square-x" style="font-size: 2rem; color: #ddd; margin-bottom: 10px;"></i>
                    <p>No reviews yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
