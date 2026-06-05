<?php
session_start();
include_once 'database.php';

// Get booking_id from URL
$booking_id = isset($_GET['booking_id']) ? trim($_GET['booking_id']) : '';

if (empty($booking_id)) {
  header("Location: booking.php");
  exit;
}

// Fetch booking data from database
try {
  // booking table uses booking_id (no FLD_ prefix)
  $stmt = $conn->prepare("SELECT * FROM booking WHERE booking_id = :bid");
  $stmt->execute([':bid' => $booking_id]);
  $booking = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$booking) {
    echo "<script>alert('Booking not found!'); window.location.href='booking.php';</script>";
    exit;
  }
  
  // Extract booking data aligned to booking table schema
  $freelancer_id = $booking['freelancer_id'];
  $client_id = $booking['client_id'];
  $service_title = $booking['service_title'];
  $deadline = $booking['deadline'] ?? '';
  $work_mode = $booking['work_mode'] ?? '';
  $total_hours = (int)($booking['total_booking_hours'] ?? 0);
  $total_price = (float)($booking['calculated_total_price'] ?? 0);
  $booking_status = $booking['booking_status'] ?? 'Pending';
  

  // Fetch freelancer name from users table
  $freelancer_name = "Freelancer"; // Default
  try {
    $stmt = $conn->prepare("SELECT fullname FROM users WHERE id = :fid AND role = 'freelancer'");
    $stmt->execute([':fid' => $freelancer_id]);
    $freelancer_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($freelancer_data) {
      $freelancer_name = $freelancer_data['fullname'];
    }
  } catch (PDOException $e) {
    // If table doesn't exist, use default
    $freelancer_name = $freelancer_id;
  }
  
  // Fetch freelancer QR code only
  $freelancer_profile = null;
  $qr_code_path = null;
  try {
    $stmt = $conn->prepare("SELECT qr_code FROM freelancers WHERE user_id = :id");
    $stmt->execute([':id' => $freelancer_id]);
    $freelancer_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Build QR code image path if qr_code is not empty
    if (!empty($freelancer_profile['qr_code'])) {
      $qr_code_path = "uploads/" . htmlspecialchars($freelancer_profile['qr_code']);
    }
  } catch (PDOException $e) {
    $freelancer_profile = ['qr_code' => null];
    $qr_code_path = null;
  }
  
  // Fetch client details from clients table
$client_name = 'Unknown Client';

try {
    $stmt = $conn->prepare("
        SELECT name
        FROM clients
        WHERE user_id = :cid
        LIMIT 1
    ");

    $stmt->execute([':cid' => $client_id]);
    $client_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($client_data) {
        $client_name = $client_data['name'] ?? 'Unknown Client';
    }

} catch (PDOException $e) {
    // Keep safe defaults
    $client_name = 'Unknown Client';
}


// Use QR code path from database if available, otherwise use fallback
if (!empty($qr_code_path) && file_exists($qr_code_path)) {
  $qr_path = $qr_code_path;
} else {
  // Fallback to default if file doesn't exist or no QR code in database
  $qr_path = "freelancer_qr/default_qr.png";
}

// Handle receipt upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_receipt'])) {
  if (isset($_FILES["receipt"]) && $_FILES["receipt"]["error"] == 0) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
      mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . "_" . basename($_FILES["receipt"]["name"]);
    $target_file = $target_dir . $file_name;
    
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowedTypes = ["png", "jpg", "jpeg", "pdf"];
    
    if (in_array($fileType, $allowedTypes)) {
      if ($_FILES["receipt"]["size"] > 5 * 1024 * 1024) {
        echo "<script>alert('File too large. Maximum size is 5MB.');</script>";
      } else {
        // Start transaction to ensure both file upload and database update succeed
        try {
          $conn->beginTransaction();
          
          // Move uploaded file to uploads directory
          if (!move_uploaded_file($_FILES["receipt"]["tmp_name"], $target_file)) {
            throw new Exception("Failed to move uploaded file.");
          }
          
          // Update booking table with receipt file path (varchar)
          // Status remains unchanged - stays as "Job In Review"
          $stmt_update = $conn->prepare("
            UPDATE booking 
            SET receipt = :receipt_path
            WHERE booking_id = :bid
          ");
          $stmt_update->bindParam(':receipt_path', $file_name);
          $stmt_update->bindParam(':bid', $booking_id);
          $stmt_update->execute();
          
          // Check if update was successful
          if ($stmt_update->rowCount() === 0) {
            throw new Exception("Booking not found or no rows updated.");
          }
          
          // Commit transaction
          $conn->commit();
          
          // Only show success if everything succeeded
          echo "<script>alert('Payment proof uploaded successfully!'); window.location.href='dashboard.php';</script>";
          exit;
          
        } catch (Exception $e) {
          // Rollback transaction on error
          $conn->rollBack();
          
          // Delete uploaded file if it exists (cleanup)
          if (file_exists($target_file)) {
            unlink($target_file);
          }
          
          echo "<script>alert('Error processing payment: " . htmlspecialchars($e->getMessage()) . "');</script>";
        } catch (PDOException $e) {
          // Rollback transaction on database error
          $conn->rollBack();
          
          // Delete uploaded file if it exists (cleanup)
          if (file_exists($target_file)) {
            unlink($target_file);
          }
          
          echo "<script>alert('Database error: " . htmlspecialchars($e->getMessage()) . "');</script>";
        }
      }
    } else {
      echo "<script>alert('Only PNG, JPG, JPEG, or PDF files allowed.');</script>";
    }
  } else {
    echo "<script>alert('Please select a file to upload.');</script>";
  }
}} catch (Exception $e) {
  echo "<script>
    alert('System error occurred. Please try again later.');
    window.location.href='booking.php';
  </script>";
  exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment for Booking #<?php echo htmlspecialchars($booking_id); ?> | Watan Freelance System</title>
  
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
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 255, 0.95) 100%);
      min-height: 100vh;
      font-family: 'Poppins', sans-serif;
      color: #333;
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

    /* Navigation Bar - Matching dashboard.php */
    nav {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 255, 0.95) 100%);
      backdrop-filter: saturate(180%) blur(6px);
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 60px;
      position: sticky;
      top: 0;
      z-index: 1001;
      transition: box-shadow .2s ease, background-color .2s ease;
      border: 2px solid transparent;
      background-image: linear-gradient(rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.95)), linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      background-origin: border-box;
      background-clip: padding-box, border-box;
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

    /* Payment Container - Two Column Layout */
    .payment-container {
      max-width: 1200px;
      margin: 60px auto;
      padding: 0 20px;
      display: flex;
      gap: 30px;
    }

    .payment-left {
      flex: 1;
      min-width: 0;
    }

    .payment-right {
      flex: 1;
      min-width: 0;
    }

    .payment-card {
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

    .payment-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, #f093fb 0%, #f5576c 50%, #fa709a 100%);
      border-radius: 25px 25px 0 0;
    }

    .payment-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .payment-header h1 {
      font-size: 2rem;
      font-weight: 700;
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      color: transparent;
      text-shadow: 0 2px 8px rgba(240,147,251,0.3);
      padding: 15px 30px;
      border-radius: 12px;
      display: inline-block;
      margin-bottom: 10px;
    }

    .payment-header p {
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

    .amount-highlight {
      font-size: 1.3rem;
      color: #7a5af8;
      font-weight: 700;
    }

    /* QR Code Section - Enhanced */
    .qr-section {
      text-align: center;
      padding: 30px;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      border-radius: 12px;
      margin-bottom: 30px;
      border: 2px solid #e9ecef;
      position: relative;
      overflow: hidden;
    }

    .qr-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #7a5af8, #6948f0);
    }

    .qr-section h3 {
      color: #7a5af8;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .qr-section img {
      max-width: 250px;
      width: 100%;
      height: auto;
      border-radius: 12px;
      margin-bottom: 15px;
      border: 3px solid white;
      padding: 15px;
      background: white;
      box-shadow: 0 8px 25px rgba(122,90,248,0.15);
      transition: transform 0.3s ease;
    }

    .qr-section img:hover {
      transform: scale(1.05);
    }

    .qr-section small {
      display: block;
      color: #666;
      font-size: 0.9rem;
      margin-top: 10px;
    }

    .qr-section .payment-instructions {
      background: rgba(122,90,248,0.1);
      padding: 15px;
      border-radius: 8px;
      margin-top: 15px;
      border-left: 4px solid #7a5af8;
    }

    .qr-section .payment-instructions p {
      margin: 0;
      font-size: 0.85rem;
      color: #555;
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
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      margin-right: 10px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
    }

    .file-input-wrapper input[type="file"]::file-selector-button:hover {
      background: #6948f0;
    }

    .btn-submit {
      width: 100%;
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      color: white;
      border: none;
      padding: 14px 24px;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 20px;
      box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3);
    }

    .btn-submit:hover {
      background: linear-gradient(135deg, #e91e63 0%, #f5576c 100%);
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(233, 30, 99, 0.5);
    }

    .btn-submit:active {
      transform: translateY(1px);
    }

    .btn-back {
      display: inline-block;
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
      color: white;
      text-decoration: none;
      font-weight: 500;
      margin-top: 15px;
      padding: 12px 24px;
      border-radius: 8px;
      transition: all 0.3s ease;
      box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
    }

    .btn-back:hover {
      background: linear-gradient(135deg, #00b4d8 0%, #00f2fe 100%);
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(0, 180, 216, 0.5);
    }

    .file-hint {
      font-size: 0.85rem;
      color: #888;
      margin-top: 8px;
    }

    @media (max-width: 968px) {
      .payment-container {
        flex-direction: column;
        margin: 30px auto;
        padding: 0 15px;
      }

      .payment-left,
      .payment-right {
        width: 100%;
      }

      .payment-card {
        padding: 25px;
      }

      .payment-header h1 {
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

  <!-- Payment Container - Two Column Layout -->
  <div class="payment-container">
    <!-- Left Column - Booking Info -->
    <div class="payment-left">
      <div class="payment-card">
        <div class="payment-header">
          <h1>Complete Your Payment</h1>
          <p>Booking #<?php echo htmlspecialchars($booking_id); ?></p>
        </div>

        <!-- Booking Information -->
        <div class="booking-info">
          <div class="info-row">
            <span class="info-label"><i class="bi bi-briefcase"></i> Service:</span>
            <span class="info-value"><?php echo htmlspecialchars($service_title); ?></span>
          </div>
          <div class="info-row">
            <span class="info-label"><i class="bi bi-person-badge"></i> Freelancer:</span>
            <span class="info-value"><?php echo htmlspecialchars($freelancer_name); ?></span>
          </div>
          <div class="info-row">
            <span class="info-label"><i class="bi bi-person"></i> Client:</span>
            <span class="info-value"><?php echo htmlspecialchars($client_name); ?></span>
          </div>
          <div class="info-row">
            <span class="info-label"><i class="bi bi-calendar-event"></i> Deadline:</span>
            <span class="info-value"><?php echo $deadline ? htmlspecialchars(date('d M Y', strtotime($deadline))) : '-'; ?></span>
          </div>
          <div class="info-row">
            <span class="info-label"><i class="bi bi-laptop"></i> Work Mode:</span>
            <span class="info-value"><?php echo htmlspecialchars($work_mode ?: '-'); ?></span>
          </div>
          <div class="info-row">
            <span class="info-label"><i class="bi bi-clock"></i> Hours:</span>
            <span class="info-value"><?php echo htmlspecialchars($total_hours); ?> hrs</span>
          </div>
          <div class="info-row">
            <span class="info-label"><i class="bi bi-info-circle"></i> Status:</span>
            <span class="info-value"><?php echo htmlspecialchars($booking_status); ?></span>
          </div>
          <div class="info-row">
            <span class="info-label"><i class="bi bi-currency-dollar"></i> Amount to Pay:</span>
            <span class="info-value amount-highlight">RM <?php echo number_format($total_price, 2); ?></span>
          </div>
        </div>

        <!-- Upload Receipt Form -->
        <form method="POST" enctype="multipart/form-data" class="upload-section">
          <label for="receipt">Upload Payment Receipt</label>
          <div class="file-input-wrapper">
            <input type="file" name="receipt" id="receipt" accept=".png,.jpg,.jpeg,.pdf" required>
          </div>
          <p class="file-hint">Accepted formats: PNG, JPG, JPEG, PDF (Max 5MB)</p>
          
          <button type="submit" name="upload_receipt" class="btn-submit">
            <i class="bi bi-upload"></i> Submit Payment Proof
          </button>
          
          <a href="booking.php?freelancer_id=<?= $freelancer_id ?>" class="btn-back">
            <i class="bi bi-arrow-left"></i> Back to Booking
          </a>
        </form>
      </div>
    </div>

    <!-- Right Column - QR Code -->
    <div class="payment-right">
      <div class="payment-card">
        <!-- QR Code Section -->
        <?php if (!empty($freelancer_profile['qr_code']) && !empty($qr_code_path) && file_exists($qr_code_path)): ?>
          <div class="qr-section">
            <h3><i class="bi bi-qr-code"></i> Scan to Pay</h3>
            <img src="<?php echo htmlspecialchars($qr_path); ?>" alt="QR Code for Payment">
            <small>Scan QR Code to pay and confirm booking</small>
            <div class="payment-instructions">
              <p><strong>Payment Instructions:</strong></p>
              <p>1. Scan the QR code with your banking app</p>
              <p>2. Enter the exact amount: RM <?php echo number_format($total_price, 2); ?></p>
              <p>3. Complete the payment</p>
              <p>4. Upload the receipt below</p>
            </div>
          </div>
        <?php else: ?>
          <div class="qr-section">
            <h3><i class="bi bi-exclamation-triangle"></i> QR Code Unavailable</h3>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 20px 0;">
              <p style="margin: 0; color: #856404; font-size: 0.9rem;">
                <strong>Payment Information:</strong><br>
                The freelancer hasn't uploaded their QR code yet. Please contact the freelancer directly for payment details.
              </p>
            </div>
            <small>Contact freelancer for payment instructions</small>
          </div>
        <?php endif; ?>
      </div>
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
