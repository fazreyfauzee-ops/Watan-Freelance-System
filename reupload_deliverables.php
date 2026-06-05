<?php
session_start();
require_once "database.php";

$bid = $_GET['bid'] ?? $_POST['bid'] ?? null;
$description = $_POST['description'] ?? '';

if (!$bid) {
    echo "<script>alert('Missing booking ID.'); window.location.href='freelancer_booking_list.php';</script>";
    exit;
}

// Fetch booking info
try {
    $stmt = $conn->prepare("SELECT * FROM booking WHERE booking_id = :bid");
    $stmt->execute([':bid' => $bid]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo "<script>alert('Booking not found.'); window.location.href='freelancer_booking_list.php';</script>";
        exit;
    }
} catch (PDOException $e) {
    echo "<script>alert('Error fetching booking: " . htmlspecialchars($e->getMessage()) . "'); window.location.href='freelancer_booking_list.php';</script>";
    exit;
}

$message = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['deliverable']) && $_FILES['deliverable']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . "/uploads";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $original = $_FILES['deliverable']['name'];
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowedTypes = ["png", "jpg", "jpeg", "pdf", "doc", "docx", "zip", "rar"];
    
    if (!in_array($ext, $allowedTypes)) {
        $message = "Invalid file type. Allowed: PNG, JPG, JPEG, PDF, DOC, DOCX, ZIP, RAR";
    } elseif ($_FILES['deliverable']['size'] > 10 * 1024 * 1024) {
        $message = "File too large. Maximum size is 10MB.";
    } else {
        $sanitizedBid = preg_replace("/[^A-Za-z0-9_-]/", "_", $bid);
        $targetName = "deliverable_" . $sanitizedBid . "_" . time() . ($ext ? "." . $ext : "");
        $targetPath = $uploadDir . "/" . $targetName;

        if (move_uploaded_file($_FILES['deliverable']['tmp_name'], $targetPath)) {
            $relativePath = "uploads/" . $targetName;

            // Start transaction
            try {
                $conn->beginTransaction();
                
                // Update booking with deliverables file path and change status to "Job Pending Verification"
                $stmt = $conn->prepare("
                    UPDATE booking
                    SET deliverables = :deliverable_path,
                        booking_status = 'Job Pending Verification'
                    WHERE booking_id = :bid
                ");
                $stmt->bindParam(':deliverable_path', $relativePath);
                $stmt->bindParam(':bid', $bid);
                $stmt->execute();

                // Check if update was successful
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Booking not found or no rows updated.");
                }

                // Commit transaction
                $conn->commit();
                
                $success = true;
                $message = "Deliverable re-uploaded successfully!";
                
                // Redirect after success
                echo "<script>alert('" . addslashes($message) . "'); window.location.href='freelancer_booking_list.php';</script>";
                exit;
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollBack();
                
                // Delete uploaded file if it exists (cleanup)
                if (file_exists($targetPath)) {
                    unlink($targetPath);
                }
                
                $message = "Error updating database: " . htmlspecialchars($e->getMessage());
            } catch (PDOException $e) {
                // Rollback transaction on database error
                $conn->rollBack();
                
                // Delete uploaded file if it exists (cleanup)
                if (file_exists($targetPath)) {
                    unlink($targetPath);
                }
                
                $message = "Database error: " . htmlspecialchars($e->getMessage());
            }
        } else {
            $message = "Failed to move uploaded file.";
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = "Please choose a file to upload.";
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
    <title>Re-Upload Deliverable | Watan Freelance System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: url('img/background.png') no-repeat center center/cover;
            background-attachment: fixed;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(248, 249, 250, 0.85);
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
            text-decoration: none;
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

        /* Container */
        .upload-container {
            max-width: 600px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .upload-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 20px;
        }

        .upload-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .upload-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #7a5af8;
            margin-bottom: 10px;
        }

        .upload-header p {
            color: #666;
            font-size: 1rem;
        }

        .booking-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .info-value {
            color: #333;
            font-weight: 500;
        }

        /* Upload Section */
        .upload-section {
            margin-top: 30px;
        }

        .upload-section label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            resize: vertical;
            min-height: 100px;
            transition: all .2s ease;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #7a5af8;
            box-shadow: 0 0 0 4px rgba(122,90,248,0.1);
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed #7a5af8;
            border-radius: 8px;
            background: #f8f9fa;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all .2s ease;
        }

        .file-input-wrapper input[type="file"]:hover {
            background: #f3f1ff;
            border-color: #6948f0;
        }

        .file-input-wrapper input[type="file"]::file-selector-button {
            background: #7a5af8;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 10px;
            transition: background .2s ease;
        }

        .file-input-wrapper input[type="file"]::file-selector-button:hover {
            background: #6948f0;
        }

        .btn-submit {
            width: 100%;
            background: #7a5af8;
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s ease, transform .1s ease;
            margin-top: 20px;
        }

        .btn-submit:hover {
            background: #6948f0;
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        .btn-back {
            display: inline-block;
            color: #7a5af8;
            text-decoration: none;
            font-weight: 500;
            margin-top: 15px;
            transition: color .2s ease;
        }

        .btn-back:hover {
            color: #6948f0;
            text-decoration: underline;
        }

        .file-hint {
            font-size: 0.85rem;
            color: #888;
            margin-top: 8px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .upload-container {
                margin: 30px auto;
                padding: 0 15px;
            }

            .upload-card {
                padding: 25px;
            }

            .upload-header h1 {
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

    <!-- Upload Container -->
    <div class="upload-container">
        <div class="upload-card">
            <div class="upload-header">
                <h1>Re-Upload Deliverable</h1>
                <p>Booking #<?php echo htmlspecialchars($bid); ?></p>
            </div>

            <!-- Booking Information -->
            <div class="booking-info">
                <div class="info-row">
                    <span class="info-label">Service:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['service_title'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['booking_status'] ?? 'N/A'); ?></span>
                </div>
                <?php if (!empty($booking['deliverables'])): ?>
                <div class="info-row">
                    <span class="info-label">Current Deliverable:</span>
                    <span class="info-value"><a href="<?php echo htmlspecialchars($booking['deliverables']); ?>" target="_blank">View</a></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Upload Form -->
            <form method="post" enctype="multipart/form-data" class="upload-section">
                <input type="hidden" name="bid" value="<?php echo htmlspecialchars($bid); ?>">

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" placeholder="Describe your deliverable..." required><?php echo htmlspecialchars($description); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="deliverable">Upload File</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="deliverable" id="deliverable" required>
                    </div>
                    <p class="file-hint">Accepted formats: PNG, JPG, JPEG, PDF, DOC, DOCX, ZIP, RAR (Max 10MB)</p>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="bi bi-upload"></i> Re-Upload Deliverable
                </button>
                
                <a href="freelancer_booking_list.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i> Back to Bookings
                </a>
            </form>
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
