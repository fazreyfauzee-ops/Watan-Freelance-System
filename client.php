<?php
session_start();
include_once 'database.php'; 

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$editrow = [];
$user_info = [];

// Fetch user info from users table (name, email, phone)
try {
    $stmt = $conn->prepare("SELECT fullname AS name, email, phone FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user_info) {
        die("Error: User not found.");
    }
} catch (PDOException $e) {
    die("Error fetching user info: " . $e->getMessage());
}

// Fetch existing client profile from clients table
try {
    $stmt = $conn->prepare("SELECT * FROM clients WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $editrow = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching client profile: " . $e->getMessage());
}

$is_edit_mode = !empty($editrow);

// Avatar
$avatar_source = 'default_client.png';
if (!empty($editrow['profile_picture']) && file_exists('uploads/' . $editrow['profile_picture'])) {
    $avatar_source = 'uploads/' . $editrow['profile_picture'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Client Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
<style>
body { 
  font-family:Poppins, sans-serif; 
  background: url('background_selakaukm.png') no-repeat center center/cover;
  background-attachment: fixed;
  min-height: 100vh;
  position: relative;
}

.form-section { 
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 255, 0.95) 100%);
  backdrop-filter: blur(20px);
  padding:40px; 
  border-radius:20px; 
  box-shadow:0 20px 60px rgba(102, 126, 234, 0.3);
  border: 2px solid transparent;
  background-image: linear-gradient(rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.95)), linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  background-origin: border-box;
  background-clip: padding-box, border-box;
  transition: all 0.3s ease;
}

.form-section:hover {
  transform: translateY(-5px);
  box-shadow: 0 25px 80px rgba(240, 147, 251, 0.4);
}

.avatar-preview-container { 
  width:150px; 
  height:150px; 
  border-radius:50%; 
  overflow:hidden; 
  margin:auto; 
  margin-bottom:20px; 
  border:4px solid transparent;
  background-image: linear-gradient(white, white), linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  background-origin: border-box;
  background-clip: padding-box, border-box;
  display:flex; 
  justify-content:center; 
  align-items:center;
  box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3);
  transition: all 0.3s ease;
}

.avatar-preview-container:hover {
  transform: scale(1.05);
  box-shadow: 0 12px 35px rgba(240, 147, 251, 0.5);
}

.avatar-preview-container img { 
  width:100%; 
  height:100%; 
  object-fit:cover; 
  border-radius:50%; 
}

h2 {
  font-size: 2rem;
  font-weight: 700;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  color: transparent;
  text-shadow: 0 2px 8px rgba(240, 147, 251, 0.3);
  margin-bottom: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

h2 i {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  color: transparent;
  font-size: 1.8rem;
}

.form-label {
  font-weight: 600;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  color: transparent;
}

.form-label i {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  color: transparent;
  font-size: 1.1rem;
}

.form-control {
  border: 2px solid rgba(240, 147, 251, 0.3);
  border-radius: 12px;
  padding: 12px 16px;
  font-size: 16px;
  transition: all 0.3s ease;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 255, 0.95) 100%);
  backdrop-filter: blur(10px);
}

.form-control:focus {
  border-color: rgba(240, 147, 251, 0.6);
  box-shadow: 0 0 0 0.2rem rgba(240, 147, 251, 0.25);
  background: rgba(255, 255, 255, 0.98);
}

.btn-primary {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  border: none;
  color: white;
  padding: 12px 30px;
  border-radius: 12px;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
}

.btn-primary:hover {
  background: linear-gradient(135deg, #e91e63 0%, #f5576c 100%);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(233, 30, 99, 0.5);
}

.btn-secondary {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  border: none;
  color: white;
  padding: 12px 30px;
  border-radius: 12px;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(79, 172, 254, 0.3);
}

.btn-secondary:hover {
  background: linear-gradient(135deg, #00b4d8 0%, #00f2fe 100%);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 180, 216, 0.5);
}

.profile-card {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 255, 0.95) 100%);
  backdrop-filter: blur(20px);
  padding: 25px 40px;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(102, 126, 234, 0.3);
  border: 2px solid transparent;
  background-image: linear-gradient(rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.95)), linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  background-origin: border-box;
  background-clip: padding-box, border-box;
  transition: all 0.3s ease;
  margin-bottom: 30px;
}

.profile-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 25px 80px rgba(240, 147, 251, 0.4);
}

.profile-card h2 {
  font-size: 2rem;
  font-weight: 700;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  color: transparent;
  text-shadow: 0 2px 8px rgba(240, 147, 251, 0.3);
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.profile-card h2 i {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  color: transparent;
  font-size: 1.8rem;
}

nav {
  background-color: rgba(255,255,255,0.9);
  backdrop-filter: saturate(180%) blur(6px);
  box-shadow: 0 2px 5px rgba(0,0,0,0.08);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 60px;
  position: relative;
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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="profile-card text-center mb-4">
            <h2><i class="fas fa-id-card me-2"></i>CLIENT PROFILE</h2>
        </div>

            <form action="client_process.php" method="post" enctype="multipart/form-data" class="form-section">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id) ?>">

                <div class="avatar-preview-container">
                    <img id="avatar-preview" src="<?= $avatar_source ?>" alt="Profile Picture">
                </div>

                <h4 class="text-center mb-4"><?= htmlspecialchars($user_info['name']) ?></h4>

                <div class="mb-3">
                    <label for="name" class="form-label"><i class="fas fa-user-tie"></i> Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user_info['name']) ?>" autocomplete="name">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label"><i class="fas fa-at"></i> Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user_info['email']) ?>" autocomplete="email">
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user_info['phone'] ?? '') ?>" autocomplete="tel">
                </div>

                <div class="mb-3">
                    <label for="bio" class="form-label"><i class="fas fa-file-alt"></i> Bio</label>
                    <textarea id="bio" name="bio" class="form-control" rows="4" placeholder="A short bio about yourself"><?= htmlspecialchars($editrow['bio'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label"><i class="fas fa-home"></i> Address</label>
                    <input type="text" id="address" name="address" class="form-control" value="<?= htmlspecialchars($editrow['address'] ?? '') ?>" autocomplete="street-address">
                </div>

                <div class="mb-3">
                    <label for="profile_picture" class="form-label"><i class="fas fa-image"></i> Profile Picture</label>
                    <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept="image/*">
                    <?php if ($is_edit_mode && !empty($editrow['profile_picture'])): ?>
                        <div class="form-text mt-1">Current: <?= htmlspecialchars($editrow['profile_picture']) ?></div>
                    <?php else: ?>
                        <div class="form-text mt-1">Upload a profile picture</div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="dashboard.php" class="btn btn-secondary me-3"><i class="fas fa-times me-1"></i>Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?= $is_edit_mode ? 'Update Profile' : 'Create Profile' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('profile_picture').addEventListener('change', function(e){
    const file = e.target.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = function(e){
            document.getElementById('avatar-preview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});
</script>
</body>
</html>
