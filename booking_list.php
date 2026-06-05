<?php
session_start();
require_once 'session_check.php';
ensure_client_access();
include_once 'database.php';

// Fetch all bookings from database with feedback information
try {
    $stmt = $conn->prepare("
        SELECT 
            b.*,
            u1.fullname as client_name,
            u2.fullname as freelancer_name,
            f.id as feedback_id,
            f.rating as feedback_rating,
            f.comment as feedback_comment,
            f.created_at as feedback_date
        FROM booking b
        LEFT JOIN users u1 ON b.client_id = u1.id
        LEFT JOIN users u2 ON b.freelancer_id = u2.id
        LEFT JOIN feedback f ON b.booking_id = f.booking_id
        ORDER BY b.created_at DESC
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bookings = [];
    $error_message = "Error fetching bookings: " . htmlspecialchars($e->getMessage());
}

// Function to get freelancer name
function getFreelancerName($conn, $freelancer_id) {
  try {
    $stmt = $conn->prepare("SELECT FLD_FREELANCER_NAME FROM tbl_freelancer WHERE FLD_FREELANCER_ID = :fid LIMIT 1");
    $stmt->execute([':fid' => $freelancer_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    return $data ? $data['FLD_FREELANCER_NAME'] : $freelancer_id;
  } catch (PDOException $e) {
    return $freelancer_id;
  }
}

// Function to get client name
function getClientName($conn, $client_id) {
  try {
    $stmt = $conn->prepare("SELECT FLD_CLIENT_NAME FROM tbl_client WHERE FLD_CLIENT_ID = :cid LIMIT 1");
    $stmt->execute([':cid' => $client_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    return $data ? $data['FLD_CLIENT_NAME'] : $client_id;
  } catch (PDOException $e) {
    return $client_id;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking List | Watan Freelance System</title>
  
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

    /* Navigation Bar - Matching dashboard.php */
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

    /* Main Container */
    .main-container {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .page-header {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      padding: 30px 40px;
      margin-bottom: 30px;
      text-align: center;
    }

    .page-header h1 {
      font-size: 2rem;
      font-weight: 700;
      color: #7a5af8;
      margin-bottom: 10px;
    }

    .page-header p {
      color: #64748b;
      font-size: 1.1rem;
      font-weight: 500;
      position: relative;
      z-index: 1;
    }

    /* Table Container */
    .table-container {
      background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,0.95) 100%);
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
      padding: 30px;
      overflow-x: auto;
      border: 1px solid rgba(255,255,255,0.2);
      position: relative;
      backdrop-filter: blur(20px);
    }

    /* Table Styling */
    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
      border-radius: 12px;
      overflow: hidden;
    }

    thead {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    th {
      padding: 18px;
      text-align: left;
      font-weight: 700;
      color: #fff;
      border: none;
      font-size: 0.95rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      position: relative;
    }

    th::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: rgba(255,255,255,0.3);
    }

    tbody tr {
      background: rgba(255,255,255,0.8);
      transition: all 0.3s ease;
      border-bottom: 1px solid rgba(102,126,234,0.1);
    }

    tbody tr:hover {
      background: linear-gradient(135deg, rgba(102,126,234,0.05) 0%, rgba(118,75,162,0.05) 100%);
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(102,126,234,0.15);
    }

    td {
      padding: 18px;
      border: none;
      color: #334155;
      font-weight: 500;
      position: relative;
    }

    /* Status Badges */
    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .booking-confirmed {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      box-shadow: 0 4px 12px rgba(16,185,129,0.3);
    }

    .job-in-progress {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      color: white;
      box-shadow: 0 4px 12px rgba(59,130,246,0.3);
    }

    .job-completed {
      background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
      color: white;
      box-shadow: 0 4px 12px rgba(139,92,246,0.3);
    }

    .job-cancelled {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
      box-shadow: 0 4px 12px rgba(239,68,68,0.3);
    }

    /* Booking ID */
    .booking-id {
      font-family: 'Courier New', monospace;
      font-weight: 700;
      color: #667eea;
      background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
      padding: 4px 8px;
      border-radius: 6px;
      border: 1px solid rgba(102,126,234,0.2);
    }

    /* Amount Display */
    .amount {
      font-weight: 700;
      color: #059669;
      font-size: 1.1rem;
      background: linear-gradient(135deg, rgba(16,185,129,0.1) 0%, rgba(5,150,105,0.1) 100%);
      padding: 4px 8px;
      border-radius: 6px;
      border: 1px solid rgba(16,185,129,0.2);
    }

    /* Feedback Section */
    .feedback-section {
      max-width: 200px;
    }

    .feedback-rating {
      color: #fbbf24;
      font-size: 1rem;
      margin-bottom: 5px;
    }

    .feedback-comment {
      font-size: 0.85rem;
      color: #64748b;
      font-style: italic;
      line-height: 1.4;
    }

    /* No Bookings Message */
    .no-bookings {
      text-align: center;
      padding: 80px 20px;
      background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,0.95) 100%);
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
      border: 1px solid rgba(255,255,255,0.2);
    }

    .no-bookings i {
      font-size: 4rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 20px;
    }

    .no-bookings p {
      font-size: 1.2rem;
      color: #64748b;
      font-weight: 500;
    }

    /* Error Message */
    .error-message {
      background: linear-gradient(135deg, rgba(239,68,68,0.1) 0%, rgba(220,38,38,0.1) 100%);
      color: #dc2626;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      border-left: 4px solid #dc2626;
      border: 1px solid rgba(239,68,68,0.2);
      font-weight: 500;
    }

    @media (max-width: 768px) {
      .main-container {
        margin: 20px auto;
        padding: 0 15px;
      }

      .page-header {
        padding: 20px;
      }

      .page-header h1 {
        font-size: 1.5rem;
      }

      .table-container {
        padding: 15px;
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

      table {
        font-size: 0.85rem;
      }

      th, td {
        padding: 10px 8px;
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

  <!-- Main Container -->
  <div class="main-container">
    <div class="page-header">
      <h1><i class="bi bi-calendar-check"></i> All Booking Records</h1>
      <p>View and manage all your bookings</p>
    </div>

    <?php if (isset($error_message)): ?>
      <div class="error-message">
        <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
      </div>
    <?php endif; ?>

    <div class="table-container">
      <?php if (count($bookings) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Client</th>
              <th>Freelancer</th>
              <th>Service</th>
              <th>Deadline</th>
              <th>Work Mode</th>
              <th>Hours</th>
              <th>Total Price</th>
              <th>Status</th>
              <th>Feedback</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $booking): ?>
              <tr>
              <td class="booking-id"><?php echo htmlspecialchars($booking['booking_id']); ?></td>
              <td><?php echo htmlspecialchars($booking['client_name']); ?></td>
              <td><?php echo htmlspecialchars($booking['freelancer_name']); ?></td>
              <td><?php echo htmlspecialchars($booking['service_title']); ?></td>
              <td><?php echo date('d M Y', strtotime($booking['deadline'])); ?></td>
              <td><?php echo htmlspecialchars($booking['work_mode']); ?></td>
              <td><?php echo htmlspecialchars($booking['total_booking_hours']); ?> hrs</td>
              <td class="amount">RM <?php echo number_format($booking['calculated_total_price'], 2); ?></td>
                <td>
                <span class="status-badge booking-confirmed">
                  Booking Confirmed
                  </span>
                </td>
                <td>
                  <?php if($booking['feedback_id']): ?>
                    <div style="font-size: 0.85rem;">
                      <div style="color: #ffc107; margin-bottom: 3px;">
                        <?php echo str_repeat('★', $booking['feedback_rating']); ?><?php echo str_repeat('☆', 5 - $booking['feedback_rating']); ?>
                        <span style="color: #666; font-size: 0.8rem;"> (<?php echo $booking['feedback_rating']; ?>/5)</span>
                      </div>
                      <div style="font-size: 0.8rem; color: #666; margin-bottom: 5px;">
                        <?php echo date('M j, Y', strtotime($booking['feedback_date'])); ?>
                      </div>
                      <a href="view_feedback.php?booking_id=<?php echo urlencode($booking['booking_id']); ?>" class="btn-action" style="font-size: 0.8rem; padding: 4px 8px;">
                        <i class="bi bi-eye"></i> View Feedback
                      </a>
                    </div>
                  <?php else: ?>
                    <span style="color: #999; font-size: 0.85rem;">No feedback yet</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="no-bookings">
          <i class="bi bi-inbox"></i>
          <p>No bookings found</p>
        </div>
      <?php endif; ?>
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
