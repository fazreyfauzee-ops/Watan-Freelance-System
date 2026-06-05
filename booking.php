<?php
session_start();
include_once 'booking_crud.php';

// Allow only clients to proceed. Others see a prompt.
$isLoggedIn = isset($_SESSION['username']);
$role = strtolower($_SESSION['role'] ?? $_SESSION['user_type'] ?? '');
if ($isLoggedIn && $role !== 'client') {
  echo "<script>alert('You are not allowed to access this page');</script>";
  exit;
}

// Resolve freelancer ID (prefer POST when submitting, otherwise from query)
$freelancer_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $freelancer_id = isset($_POST['freelancerid']) ? (int) $_POST['freelancerid'] : null;
  if (!$freelancer_id && isset($_POST['freelancer_id'])) {
    $freelancer_id = (int) $_POST['freelancer_id'];
  }
}
if (!$freelancer_id && isset($_GET['freelancer_id'])) {
  $freelancer_id = (int) $_GET['freelancer_id'];
}
if (!$freelancer_id) {
  echo "<script>alert('No freelancer selected.'); window.location.href='browse_services.php';</script>";
  exit;
}

// Load freelancer info from DB
try {
  $stmt = $conn->prepare("
    SELECT 
      f.*, 
      u.fullname AS name,
      u.username,
      u.email,
      u.phone
    FROM freelancers f
    INNER JOIN users u ON f.user_id = u.id
    WHERE f.user_id = :fid
    LIMIT 1
  ");
  $stmt->execute([':fid' => $freelancer_id]);
  $freelancer = $stmt->fetch(PDO::FETCH_ASSOC);
  
  // Load client info from users table
  $client_id = $_SESSION['id'] ?? $_SESSION['userid'] ?? null;
  $stmt_client = $conn->prepare("SELECT fullname, email FROM users WHERE id = :cid");
  $stmt_client->execute([':cid' => $client_id]);
  $client_data = $stmt_client->fetch(PDO::FETCH_ASSOC);
  
  // Load client profile picture from clients table
  $client_profile = null;
  if ($client_data) {
      $stmt_profile = $conn->prepare("SELECT profile_picture FROM clients WHERE user_id = :cid");
      $stmt_profile->execute([':cid' => $client_id]);
      $client_profile_data = $stmt_profile->fetch(PDO::FETCH_ASSOC);
      if ($client_profile_data) {
          $client_profile = $client_profile_data['profile_picture'] ?? null;
      }
  }
  
  if (!$freelancer) {
    echo "<script>alert('Freelancer not found.'); window.location.href='browse_freelancer.php';</script>";
    exit;
  }
} catch (PDOException $e) {
  echo "<script>alert('Error loading freelancer data.'); window.location.href='browse_freelancer.php';</script>";
  exit;
}

// Set freelancer display values
$freelancerName = $freelancer['name'] ?? 'Freelancer';
$freelancerSkills = $freelancer['skills'] ?? 'Not specified';
$rate_per_hour = isset($freelancer['rate_per_hour']) && $freelancer['rate_per_hour'] > 0
  ? (float) $freelancer['rate_per_hour']
  : ($RATE_PER_HOUR ?? 20);
$max_booking_hours = $MAX_BOOKING_HOURS ?? 8;

// Current client info
$clientUserId = $_SESSION['id'] ?? $_SESSION['userid'] ?? null;
$clientName = $_SESSION['username'] ?? 'Client';
if ($clientUserId) {
  try {
    $stmt = $conn->prepare("
      SELECT c.*, u.fullname AS name, u.email, u.username 
      FROM clients c 
      INNER JOIN users u ON c.user_id = u.id
      WHERE c.user_id = :cid
      LIMIT 1
    ");
    $stmt->execute([':cid' => $clientUserId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($client) {
      $clientName = $client['name'] ?? $clientName;
    }
  } catch (PDOException $e) {
    // Fallback to session username if client lookup fails
  }
}

// Display success message if booking was created
if (isset($_GET['success']) && $_GET['success'] == 1) {
  echo "<script>alert('Booking created successfully!');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Book <?= htmlspecialchars($freelancerName) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root {
  --primary:#7a5af8;
  --primary-dark:#6948f0;
  --bg:#f5f7fb;
  --card:#ffffff;
  --border:#e5e7eb;
  --gradient-1:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --gradient-2:linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  --gradient-3:linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  --gradient-4:linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  --gradient-5:linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  --gradient-6:linear-gradient(135deg, #30cfd0 0%, #330867 100%);
  --secondary-bg:#f8fafc;
  --card-shadow:0 10px 30px rgba(0,0,0,0.1);
  --hover-shadow:0 20px 40px rgba(0,0,0,0.15);
}

*{box-sizing:border-box;font-family:'Poppins',sans-serif}
body {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 255, 0.95) 100%);
      min-height: 100vh;
      font-family: 'Poppins', sans-serif;
      color: #333;
      position: relative;
    }


/* Navigation Bar */
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

/* MAIN */
.main-container{
  max-width:1200px;
  margin:40px auto;
  padding:0 20px;
  position: relative;
  z-index: 2;
}
.page-header{
  text-align:center;
  margin-bottom:40px;
}

.page-header .quote {
  font-style: italic;
  color: rgba(240, 147, 251, 0.8);
  font-size: 1.1rem;
  margin-top: 15px;
  padding: 15px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(240, 147, 251, 0.1) 100%);
  border-radius: 12px;
  border-left: 4px solid rgba(240, 147, 251, 0.3);
}
.page-header h1{
  font-size:2.2rem;
  font-weight:800;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  color: transparent;
  text-shadow: 0 2px 8px rgba(240,147,251,0.3);
  padding: 15px 30px;
  border-radius: 12px;
  display: inline-block;
}

/* GRID */
.booking-layout{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:30px;
}

/* CARD */
.booking-info {
      background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(248,250,252,0.9) 100%);
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 30px;
      border: 1px solid rgba(240,147,251,0.1);
      transition: all 0.3s ease;
      position: relative;
    }

.card{
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 255, 0.95) 100%);
  backdrop-filter: blur(20px);
  border-radius:25px;
  padding:30px;
  box-shadow:0 20px 60px rgba(31, 38, 135, 0.15);
  border: 2px solid transparent;
  background-image: linear-gradient(rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.95)), linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  background-origin: border-box;
  background-clip: padding-box, border-box;
  position: relative;
  overflow: hidden;
  z-index: 3;
  animation: cardGlow 3s ease-in-out infinite;
}

@keyframes cardGlow {
  0%, 100% { 
    box-shadow: 0 20px 60px rgba(31, 38, 135, 0.15);
  }
  50% { 
    box-shadow: 0 25px 80px rgba(240, 147, 251, 0.3);
  }
}

.card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 6px;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
  border-radius: 25px 25px 0 0;
}

/* SKILLS DISPLAY */
.skills-display {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
}

.sk.info-label i {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      width: 22px;
      text-align: center;
      border-radius: 50%;
      padding: 4px;
      box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
      transition: all 0.3s ease;
      animation: iconPulse 2s ease-in-out infinite;
      filter: drop-shadow(0 0 8px rgba(240, 147, 251, 0.5));
    }

    @keyframes iconPulse {
      0%, 100% { 
        transform: scale(1);
        box-shadow: 0 4px 12px rgba(79, 172, 254, 0.3);
      }
      50% { 
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(79, 172, 254, 0.5);
      }
    }

.skill-tag {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    animation: float 3s ease-in-out infinite;
    box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
    transition: all 0.3s ease;
    border: 1px solid rgba(240, 147, 251, 0.2);
}

.skill-tag:nth-child(even) {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    box-shadow: 0 4px 12px rgba(79, 172, 254, 0.3);
    border: 1px solid rgba(79, 172, 254, 0.2);
}

.skill-tag:nth-child(3n) {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    box-shadow: 0 4px 12px rgba(67, 233, 215, 0.3);
    border: 1px solid rgba(67, 233, 215, 0.2);
}

.skill-tag:nth-child(even) {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    box-shadow: 0 4px 12px rgba(250, 112, 154, 0.3);
    border: 1px solid rgba(250, 112, 154, 0.2);
}

.skill-tag:nth-child(3n) {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    box-shadow: 0 4px 12px rgba(67, 233, 215, 0.3);
    border: 1px solid rgba(67, 233, 215, 0.2);
}

.skill-tag:hover {
    transform: translateY(-2px) scale(1.05) rotate(2deg);
    box-shadow: 0 8px 25px rgba(102,126,234,0.5);
    filter: brightness(1.2);
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

/* ENHANCED FORM STYLING */
.form-group {
    margin-bottom: 20px;
    position: relative;
}

.form-group label {
    font-weight: 600;
    display: block;
    margin-bottom: 8px;
    color: #4a5568;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
}

.form-group label::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 30px;
    height: 2px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 1px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid transparent;
    border-radius: 12px;
    font-size: 0.9rem;
    outline: none;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(10px);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102,126,234,0.15);
    background: rgba(255,255,255,1);
    transform: translateY(-2px);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
    line-height: 1.6;
}

.form-group input[readonly] {
    background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
    cursor: not-allowed;
    color: #64748b;
    font-weight: 500;
}

/* ENHANCED BUTTONS */
.btn {
    width: 100%;
    padding: 16px 24px;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(102,126,234,0.4);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a67e8 0%, #764ba2 100%);
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(102,126,234,0.5);
}

.btn-danger {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(240,147,251,0.4);
}

.btn-danger:hover {
    background: linear-gradient(135deg, #e91e63 0%, #f5576c 100%);
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(233,30,99,0.5);
}

/* ENHANCED CARD STYLING */
.card {
    background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,0.95) 100%);
    border-radius: 20px;
    padding: 35px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.2);
    position: relative;
    overflow: hidden;
}

.card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(102,126,234,0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* PROFILE */
.profile-row{
  display:flex;
  gap:15px;
  margin-bottom:20px;
}
.avatar{
  width:60px;
  height:60px;
  border-radius:50%;
  background:var(--primary);
  display:flex;
  align-items:center;
  justify-content:center;
  color:#fff;
  font-size:1.5rem;
}
.profile-info h3{
  margin:0;
}
.profile-info small{
  color:#666;
}

/* INFO LIST */
.info-item{
  display:flex;
  gap:10px;
  margin-bottom:14px;
}
.info-item i{
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  width:22px;
  text-align:center;
  border-radius: 50%;
  padding: 4px;
  box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
  transition: all 0.3s ease;
  animation: freelancerIconPulse 2s ease-in-out infinite;
}

@keyframes freelancerIconPulse {
  0%, 100% { 
    transform: scale(1);
    box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
  }
  50% { 
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(240, 147, 251, 0.5);
  }
}

/* CLIENT REVIEW SECTION */
.client-reviews {
  margin-top: 30px;
}

.review-header {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  padding: 15px 20px;
  border-radius: 12px;
  font-weight: 700;
  margin-bottom: 20px;
  text-shadow: 0 2px 8px rgba(240,147,251,0.3);
}

.review-item {
  background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(248,250,252,0.9) 100%);
  border-radius: 15px;
  padding: 20px;
  margin-bottom: 15px;
  border: 1px solid rgba(102,126,234,0.1);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.review-item::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(102,126,234,0.05) 0%, transparent 70%);
  animation: shimmer 3s infinite;
}

.review-item:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(102,126,234,0.2);
  border-color: rgba(102,126,234,0.3);
}

.review-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 1.2rem;
  box-shadow: 0 4px 12px rgba(102,126,234,0.4);
}

.review-content {
  flex: 1;
}

.review-name {
  font-weight: 700;
  color: #4a5568;
  font-size: 1rem;
  margin-bottom: 5px;
}

.review-date {
  color: #64748b;
  font-size: 0.85rem;
  margin-bottom: 8px;
}

.review-rating {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 10px;
}

.review-rating .stars {
  color: #fbbf24;
  font-size: 1.2rem;
  text-shadow: 0 0 4px rgba(251,191,36,0.3);
}

.review-rating .stars i {
  color: #fbbf24;
  text-shadow: 0 0 4px rgba(251,191,36,0.3);
}

.review-rating .stars i.fa-star-empty {
  color: #fde68a;
}

.review-text {
  color: #334155;
  line-height: 1.6;
  font-size: 0.9rem;
  font-style: italic;
}

/* RESPONSIVE */
@media(max-width:900px){
  .booking-layout{grid-template-columns:1fr}
}
</style>
</head>

<body>

<nav class="admin-nav">
    <div class="logo">Watan Freelance System</div>
    <ul class="nav-right">
      <li><a href="dashboard.php">Home</a></li>
      <li><a href="browse_services.php">Services</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>

<div class="main-container">
  <div class="page-header">
    <h1>Book <?= htmlspecialchars($freelancerName) ?></h1>
    <div class="quote">
      <i class="bi bi-quote" style="color: rgba(240, 147, 251, 0.6); margin-right: 8px;"></i>
      "Connecting talent with opportunity, one booking at a time."
    </div>
  </div>

  <div class="booking-layout">

    <!-- BOOKING FORM -->
    <div class="card">
      <form method="post">
        <input type="hidden" name="freelancerid" value="<?= $freelancer_id ?>">
        <input type="hidden" name="clientid" value="<?= $clientUserId ?>">
        <input type="hidden" name="status" value="Job In Review">

        <div class="form-group">
          <label><i class="bi bi-person-fill"></i> Your Name</label>
          <input type="text" value="<?= htmlspecialchars($clientName) ?>" readonly>
        </div>

        <div class="form-group">
          <label><i class="bi bi-calendar-event"></i> Preferred Date</label>
          <input type="date" name="deadline" required>
        </div>

        <div class="form-group">
          <label><i class="bi bi-currency-dollar"></i> Rate</label>
          <input type="text" value="RM <?= number_format($rate_per_hour,2) ?>" readonly>
        </div>

        <div class="form-group">
          <label for="total_booking_hours"><i class="bi bi-clock"></i> Total Booking Hours</label>
          <input type="number" name="total_booking_hours" id="total_booking_hours" min="1" step="1" max="<?php echo htmlspecialchars($max_booking_hours); ?>" placeholder="e.g. 2" required>
          <small style="color:#666;">Maximum <?php echo htmlspecialchars($max_booking_hours); ?> hours per booking.</small>
        </div>

        <div class="form-group">
          <label for="calculated_total_price_display"><i class="bi bi-calculator"></i> Total Price (RM)</label>
          <input type="text" id="calculated_total_price_display" value="RM 0.00" readonly>
          <input type="hidden" name="calculated_total_price" id="calculated_total_price" value="0">
        </div>

        <div class="form-group">
          <label for="work_mode"><i class="bi bi-laptop"></i> Work Mode</label>
          <select name="work_mode" id="work_mode" required onchange="toggleLocation()">
            <option value="">-- Select --</option>
            <option value="Online">Online</option>
            <option value="Offline">Offline</option>
          </select>
          <small id="online-note" style="color:#666; display:none;">Please chat with freelancer to determine which platform will be used.</small>
        </div>

        <div class="form-group" id="location-group" style="display:none;">
          <label for="location"><i class="bi bi-geo-alt"></i> Location</label>
          <select name="location" id="location">
            <option value="">-- Select --</option>
            <option value="Inside UKM">Inside UKM</option>
            <option value="Outside UKM">Outside UKM</option>
          </select>
        </div>

        <div class="form-group">
          <label><i class="bi bi-file-text"></i> Project Description</label>
          <textarea name="description" required placeholder="Describe what you'd like help with..."></textarea>
        </div>

        <div class="form-group">
          <label><i class="bi bi-tools"></i> Freelancer Skills</label>
          <div class="skills-display">
            <?php 
            $skills = explode(',', $freelancer['skills'] ?? '');
            foreach ($skills as $skill): ?>
              <span class="skill-tag"><?= htmlspecialchars(trim($skill)) ?></span>
            <?php endforeach; ?>
          </div>
        </div>

        <button class="btn btn-primary" name="create">
          <i class="fas fa-check-circle"></i> Confirm Booking
        </button>
        <br><br>
        <button type="button" class="btn btn-danger" onclick="history.back()">
          Cancel
        </button>
      </form>
    </div>

    <!-- FREELANCER PROFILE -->
    <div class="card">
      <h3 style="margin-bottom: 20px; color: white; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 15px 20px; border-radius: 12px; font-weight: 700; text-shadow: 0 2px 8px rgba(240,147,251,0.3);"><i class="bi bi-person-badge"></i> Freelancer Information</h3>
      
      <div class="info-item">
        <i class="fas fa-user"></i>
        <div>
          <strong>Name:</strong><br>
          <?= htmlspecialchars($freelancer['name']) ?>
        </div>
      </div>

      <div class="info-item">
        <i class="fas fa-envelope"></i>
        <div>
          <strong>Email:</strong><br>
          <?= htmlspecialchars($freelancer['email']) ?>
        </div>
      </div>

      <div class="info-item">
        <i class="fas fa-phone"></i>
        <div>
          <strong>Phone Number:</strong><br>
          <?= htmlspecialchars($freelancer['phone']) ?>
        </div>
      </div>

      <div class="info-item">
        <i class="fas fa-file-alt"></i>
        <div>
          <strong>Bio:</strong><br>
          <?= nl2br(htmlspecialchars($freelancer['bio'])) ?>
        </div>
      </div>

      <div class="info-item">
        <i class="fas fa-tools"></i>
        <div>
          <strong>Key Skills:</strong><br>
          <?= htmlspecialchars($freelancer['skills']) ?>
        </div>
      </div>

      <div class="info-item">
        <i class="fas fa-calendar-check"></i>
        <div>
          <strong>Availability:</strong><br>
          <?= htmlspecialchars($freelancer['availability']) ?>
        </div>
      </div>

      <!-- Client Reviews Section -->
      <div class="review-header">
        <h3><i class="fas fa-star"></i> Client Reviews</h3>
      </div>
      <div class="client-reviews">
        <?php
        // Fetch freelancer feedback from feedback table
        try {
          $stmt_feedback = $conn->prepare("
            SELECT 
              f.rating,
              f.comment,
              f.created_at,
              u.fullname as client_name
            FROM feedback f
            INNER JOIN users u ON f.client_id = u.id
            WHERE f.freelancer_id = :fid
            ORDER BY f.created_at DESC
            LIMIT 5
          ");
          $stmt_feedback->execute([':fid' => $freelancer_id]);
          $feedbacks = $stmt_feedback->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
          $feedbacks = [];
        }
        
        if (empty($feedbacks)): ?>
          <div class="review-item">
            <div class="review-avatar">
              <i class="fas fa-user"></i>
            </div>
            <div class="review-content">
              <div class="review-name">No reviews yet</div>
              <div class="review-date">Be the first to review this freelancer!</div>
              <div class="review-rating">
                <div class="stars">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                </div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($feedbacks as $feedback): ?>
            <div class="review-item">
              <div class="review-avatar">
                <?= strtoupper(substr($feedback['client_name'] ?? 'A', 0, 1)) ?>
              </div>
              <div class="review-content">
                <div class="review-name"><?= htmlspecialchars($feedback['client_name']) ?></div>
                <div class="review-date"><?= date('M j, Y', strtotime($feedback['created_at'])) ?></div>
                <div class="review-rating">
                  <div class="stars">
                    <?php 
                    $rating = $feedback['rating'] ?? 0;
                    for ($i = 0; $i < 5; $i++) {
                      echo '<i class="fas fa-star' . ($i < $rating ? '' : '-empty') . '"></i>';
                    }
                    ?>
                  </div>
                </div>
                <div class="review-text"><?= htmlspecialchars($feedback['comment']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
    // Toggle location field based on work mode
    function toggleLocation() {
        const workMode = document.getElementById('work_mode').value;
        const locationGroup = document.getElementById('location-group');
        const onlineNote = document.getElementById('online-note');
        
        if (workMode === 'Online') {
            locationGroup.style.display = 'none';
            onlineNote.style.display = 'block';
        } else if (workMode === 'Offline') {
            locationGroup.style.display = 'block';
            onlineNote.style.display = 'none';
        } else {
            locationGroup.style.display = 'none';
            onlineNote.style.display = 'none';
        }
    }

    // Handle booking calculations
    document.addEventListener('DOMContentLoaded', function() {
        const hoursInput = document.getElementById('total_booking_hours');
        const priceDisplay = document.getElementById('calculated_total_price_display');
        const priceHidden = document.getElementById('calculated_total_price');
        const ratePerHour = <?php echo json_encode($rate_per_hour); ?>;
        const maxHours = <?php echo json_encode($max_booking_hours); ?>;

        const updatePrice = () => {
            if (!hoursInput || !priceDisplay || !priceHidden) return;
            let hours = parseFloat(hoursInput.value) || 0;
            if (hours > maxHours) {
                hours = maxHours;
                hoursInput.value = maxHours;
            }
            if (hours < 0) {
                hours = 0;
                hoursInput.value = 0;
            }
            const total = hours * ratePerHour;
            priceDisplay.value = `RM ${total.toFixed(2)}`;
            priceHidden.value = total.toFixed(2);
        };

        if (hoursInput) {
            hoursInput.addEventListener('input', updatePrice);
            updatePrice();
        }

        // Set minimum date to today for date input
        const dateInput = document.querySelector('input[name="deadline"]');
        if (dateInput) {
            const today = new Date().toISOString().split('T')[0];
            dateInput.setAttribute('min', today);
        }
    });
</script>
