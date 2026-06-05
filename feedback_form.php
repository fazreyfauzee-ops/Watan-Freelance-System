<?php
session_start();
require_once 'database.php';

// Re-enable client access check
require_once 'session_check.php';
ensure_client_access();

$message = '';
$error = '';

// Check for session messages
if (isset($_SESSION['feedback_success'])) {
    $message = $_SESSION['feedback_success'];
    unset($_SESSION['feedback_success']);
}

if (isset($_SESSION['feedback_error'])) {
    $error = $_SESSION['feedback_error'];
    unset($_SESSION['feedback_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'feedback_process.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Form - Watan Freelancer</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: url('background_roundabout.jpg') no-repeat center center/cover;
            background-attachment: fixed;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            position: relative;
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
            color: #7a5af8; /* soft purple */
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

        .link-signin:hover { color: #7a5af8; background-color: #f3f1ff; }

        .feedback-container {
            position: relative;
            z-index: 1;
            max-width: 600px;
            margin: 40px auto;
            padding: 40px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 240, 255, 0.95) 100%);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(138, 43, 226, 0.3);
            border: 2px solid transparent;
            background-image: linear-gradient(rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.95)), linear-gradient(135deg, #8a2be2 0%, #4b0082 100%);
            background-origin: border-box;
            background-clip: padding-box, border-box;
            transition: all 0.3s ease;
        }

        .feedback-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 80px rgba(138, 43, 226, 0.4);
        }

        .feedback-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .feedback-header h2 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #8a2be2 0%, #4b0082 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
            text-shadow: 0 2px 8px rgba(138, 43, 226, 0.3);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .feedback-header h2 i {
            background: linear-gradient(135deg, #8a2be2 0%, #4b0082 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
            font-size: 1.8rem;
        }

        .feedback-header p {
            color: rgba(138, 43, 226, 0.8);
            font-size: 16px;
            font-weight: 500;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #8a2be2 0%, #4b0082 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        .form-label i {
            background: linear-gradient(135deg, #8a2be2 0%, #4b0082 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
            font-size: 1.1rem;
        }

        .form-control, .form-select {
            border: 2px solid rgba(138, 43, 226, 0.3);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 240, 255, 0.95) 100%);
            backdrop-filter: blur(10px);
        }

        .form-control:focus, .form-select:focus {
            border-color: rgba(138, 43, 226, 0.6);
            box-shadow: 0 0 0 0.2rem rgba(138, 43, 226, 0.25);
            background: rgba(255, 255, 255, 0.98);
        }

        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }

        .star-rating {
            font-size: 40px;
            color: rgba(255, 193, 7, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        .star-rating:hover {
            color: #ffc107;
            transform: scale(1.1);
            filter: drop-shadow(0 4px 8px rgba(255, 193, 7, 0.4));
        }

        .star-rating.active {
            color: #ff9800;
            animation: starPulse 1s ease-in-out infinite;
        }

        @keyframes starPulse {
            0%, 100% {
                transform: scale(1);
                filter: drop-shadow(0 2px 4px rgba(255, 152, 0, 0.3));
            }
            50% {
                transform: scale(1.05);
                filter: drop-shadow(0 4px 8px rgba(255, 152, 0, 0.5));
            }
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(90, 103, 216, 0.5);
        }

        .btn-submit:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(90, 103, 216, 0.4);
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-back-booking {
            display: inline-block;
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(67, 233, 123, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-back-booking::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-back-booking:hover::before {
            left: 100%;
        }

        .btn-back-booking:hover {
            background: linear-gradient(135deg, #38f9d7 0%, #43e97b 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(56, 249, 215, 0.5);
        }

        .btn-back-booking:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(56, 249, 215, 0.4);
        }

        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border: none;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #5a2f91;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #3d1c66;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feedback-container {
            animation: fadeIn 0.6s ease-out;
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

<div class="feedback-container">
        <div class="feedback-header">
            <img src="watan_logo.png" alt="Watan Logo" style="width: 80px; height: 80px; border-radius: 8px; box-shadow: 0 4px 12px rgba(138, 43, 226, 0.3); margin-bottom: 15px; border: 3px solid transparent; background-image: linear-gradient(white, white), linear-gradient(135deg, #f093fb 0%, #f5576c 100%); background-origin: border-box; background-clip: padding-box, border-box;">
            <h2 style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 20%, #667eea 40%, #764ba2 60%, #43e97b 80%, #38f9d7 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; color: transparent; text-shadow: 0 2px 8px rgba(240, 147, 251, 0.3);"><i class="bi bi-chat-heart" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; color: transparent;"></i> SHARE YOUR FEEDBACK</h2>
            <p>We value your opinion and would love to hear about your experience</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" id="feedbackForm">
            <input type="hidden" name="freelancer_id" value="<?= htmlspecialchars($_GET['freelancer_id'] ?? 1) ?>" required>
            <input type="hidden" name="booking_id" value="<?= htmlspecialchars($_GET['booking_id'] ?? '') ?>" required>
            <div class="mb-4">
                <label for="rating" class="form-label">
                    <i class="bi bi-star"></i> Rating <span class="text-danger">*</span>
                </label>
                <div class="rating-stars">
                    <span class="star-rating" data-rating="1">★</span>
                    <span class="star-rating" data-rating="2">★</span>
                    <span class="star-rating" data-rating="3">★</span>
                    <span class="star-rating" data-rating="4">★</span>
                    <span class="star-rating" data-rating="5">★</span>
                </div>
                <input type="hidden" name="rating" id="rating" required>
                <small class="text-muted">Click on the stars to rate your experience</small>
            </div>

            <div class="mb-4">
                <label for="comment" class="form-label">
                    <i class="bi bi-chat-text"></i> Your Feedback <span class="text-danger">*</span>
                </label>
                <textarea 
                    class="form-control" 
                    id="comment" 
                    name="comment" 
                    rows="5" 
                    placeholder="Tell us about your experience with our service..."
                    required
                    maxlength="1000"
                ></textarea>
                <small class="text-muted">Maximum 1000 characters</small>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="bi bi-send-fill"></i> Submit Feedback
            </button>
        </form>

        <div class="text-center">
            <a href="client_booking_list.php" class="btn-back-booking">
                <i class="bi bi-arrow-left"></i> BACK TO BOOKING LIST
            </a>
        </div>
    </div>

    <script>
        // Star rating functionality
        const stars = document.querySelectorAll('.star-rating');
        const ratingInput = document.getElementById('rating');
        let selectedRating = 0;

        stars.forEach(star => {
            star.addEventListener('click', function() {
                selectedRating = parseInt(this.dataset.rating);
                ratingInput.value = selectedRating;
                updateStars(selectedRating);
            });

            star.addEventListener('mouseenter', function() {
                const hoverRating = parseInt(this.dataset.rating);
                updateStars(hoverRating);
            });
        });

        document.querySelector('.rating-stars').addEventListener('mouseleave', function() {
            updateStars(selectedRating);
        });

        function updateStars(rating) {
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        // Character counter for comment
        const commentTextarea = document.getElementById('comment');
        const maxLength = 1000;

        commentTextarea.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            if (remaining < 100) {
                this.style.borderColor = '#ffc107';
            } else {
                this.style.borderColor = '#e9ecef';
            }
        });

        // Form submission validation and handling
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            const rating = document.getElementById('rating').value;
            const comment = document.getElementById('comment').value.trim();
            const submitBtn = document.getElementById('submitBtn');
            
            // Validate rating
            if (!rating || rating < 1 || rating > 5) {
                e.preventDefault();
                alert('Please select a rating by clicking on the stars.');
                return false;
            }
            
            // Validate comment
            if (!comment) {
                e.preventDefault();
                alert('Please provide your feedback comment.');
                return false;
            }
            
            if (comment.length < 10) {
                e.preventDefault();
                alert('Comment must be at least 10 characters long.');
                return false;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Submitting...';
            
            // Allow form to submit normally
            return true;
        });
        
        // Initialize stars
        updateStars(0);
    </script>
</body>
</html>
