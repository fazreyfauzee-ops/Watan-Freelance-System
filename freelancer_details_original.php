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
            background: var(--secondary-bg);
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            padding-top: 70px;
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
            z-index: 1000;
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
        .hero-header {
            width: 100%;
            height: 220px;
            background: url('browse_service.jpg') center center/cover no-repeat;
            position: relative;
            border-radius: 0 0 20px 20px;
            margin-bottom: 40px;
        }
        .hero-header .overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.55);
            border-radius: 0 0 20px 20px;
        }
        .profile-card {
            max-width: 900px;
            background: white;
            margin: 0 auto;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--card-border);
        }
        .profile-pic {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--primary-purple);
            margin-bottom: 15px;
        }
        .skill-tag {
            display: inline-block;
            background: #f3f1ff;
            color: var(--primary-purple);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin: 5px 5px 5px 0;
            border: 1px solid var(--primary-purple);
        }
        .qr-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--card-border);
        }
        .qr-section .info-label {
            font-weight: 600;
            color: var(--primary-purple);
            margin-bottom: 15px;
            font-size: 16px;
        }
        .qr-img {
            width: 200px;
            height: 200px;
            object-fit: contain;
            border: 2px solid var(--card-border);
            border-radius: 8px;
            padding: 10px;
            background: #fff;
            display: block;
            margin: 0 auto;
        }

        /* Reviews Section */
        .reviews-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid var(--card-border);
        }

        .reviews-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .rating-summary {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .rating-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-purple);
        }

        .rating-stars {
            display: flex;
            gap: 2px;
        }

        .star {
            color: #ffc107;
            font-size: 1.2rem;
        }

        .star.empty {
            color: #ddd;
        }

        .total-reviews {
            color: #666;
            font-size: 0.9rem;
        }

        .review-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
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
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-purple);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .reviewer-name {
            font-weight: 600;
            color: #333;
        }

        .review-date {
            color: #666;
            font-size: 0.85rem;
        }

        .review-rating {
            display: flex;
            gap: 2px;
            margin-bottom: 8px;
        }

        .review-text {
            color: #555;
            line-height: 1.5;
        }

        .no-reviews {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e9ecef;
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

<!-- Hero Header -->
<div class="hero-header">
    <div class="overlay"></div>
</div>

<!-- Profile Card -->
<div class="profile-card">
    <div class="row">
        <div class="col-md-4 text-center">
            <img src="<?= $profile_img ?>" alt="Profile Picture" class="profile-pic">
            <h2><?= htmlspecialchars($freelancer['name']) ?></h2>
            <p class="speciality"><?= htmlspecialchars(explode('.', $freelancer['bio'])[0]) ?></p>
        </div>
        <div class="col-md-8">
            <div class="info-section">
                <h3><i class="fas fa-user"></i> Contact Information</h3>
                <div class="info-content">
                    <p><strong>Email:</strong> <?= htmlspecialchars($freelancer['user_email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($freelancer['user_phone']) ?></p>
                    <p><strong>Username:</strong> <?= htmlspecialchars($freelancer['username']) ?></p>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-briefcase"></i> Professional Details</h3>
                <div class="info-content">
                    <p><strong>Experience:</strong> <?= htmlspecialchars($freelancer['experience']) ?></p>
                    <p><strong>Education:</strong> <?= htmlspecialchars($freelancer['education']) ?></p>
                    <p><strong>Availability:</strong> <?= htmlspecialchars($freelancer['availability']) ?></p>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-cogs"></i> Skills</h3>
                <div class="info-content">
                    <div class="skills-container">
                        <?php 
                        $skills = explode(',', $freelancer['skills']);
                        foreach ($skills as $skill): ?>
                            <span class="skill-tag"><?= htmlspecialchars(trim($skill)) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-info-circle"></i> Bio</h3>
                <div class="info-content">
                    <p><?= nl2br(htmlspecialchars($freelancer['bio'])) ?></p>
                </div>
            </div>

            <?php if ($qr_img): ?>
            <div class="qr-section">
                <h3 class="info-label"><i class="fas fa-qrcode"></i> QR Code</h3>
                <div class="qr-container">
                    <img src="<?= $qr_img ?>" alt="QR Code" class="qr-img">
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reviews Section -->
<div class="reviews-section">
    <div class="reviews-header">
        <h2><i class="fas fa-star"></i> Client Reviews</h2>
        <div class="rating-summary">
            <div class="rating-number"><?= $averageRating ?></div>
            <div>
                <div class="rating-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star star <?= $i <= $averageRating ? '' : 'empty' ?>"></i>
                    <?php endfor; ?>
                </div>
                <div class="total-reviews"><?= $totalReviews ?> reviews</div>
            </div>
        </div>
    </div>

    <?php if (!empty($reviews)): ?>
        <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="reviewer-info">
                        <div class="reviewer-avatar">
                            <?= strtoupper(substr(htmlspecialchars($review['client_name']), 0, 2)) ?>
                        </div>
                        <div>
                            <div class="reviewer-name"><?= htmlspecialchars($review['client_name']) ?></div>
                            <div class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></div>
                        </div>
                    </div>
                    <div class="review-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star star <?= $i <= $review['rating'] ? '' : 'empty' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="review-text">
                    <?= htmlspecialchars($review['comment']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-reviews">
            <i class="fas fa-info-circle"></i> No reviews yet. Be the first to review this freelancer!
        </div>
    <?php endif; ?>
</div>

<div class="text-center mt-4">
    <a href="booking.php?freelancer_id=<?= $freelancer['user_id'] ?>" class="btn btn-primary btn-lg">
        <i class="fas fa-calendar-plus"></i> Book This Freelancer
    </a>
    <a href="browse_services.php" class="btn btn-outline-secondary btn-lg ms-2">
        <i class="fas fa-arrow-left"></i> Back to Browse
    </a>
</div>

</body>
</html>
