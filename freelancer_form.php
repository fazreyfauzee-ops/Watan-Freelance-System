<?php
session_start();
include_once 'database.php'; 

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$editrow = [];
$user_info = [];

// Fetch user information from users table
try {
    $stmt = $conn->prepare("SELECT fullname, email, phone FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_info) {
        die("Error: User not found.");
    }
} catch (PDOException $e) {
    die("Error fetching user information: " . $e->getMessage());
}

// Fetch existing freelancer profile based on user_id
try {
    $stmt = $conn->prepare("SELECT * FROM freelancers WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $editrow = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching freelancer profile: " . $e->getMessage());
}

$is_edit_mode = !empty($editrow);

// Set avatar source
$avatar_source = 'default_pic.jpg'; // Default fallback in root
if (!empty($editrow['profile_picture'])) {
    $avatar_source = 'uploads/' . htmlspecialchars($editrow['profile_picture']);
} elseif (file_exists('default_pic.jpg')) {
    $avatar_source = 'default_pic.jpg';
} elseif (file_exists('default_pic.png')) {
    $avatar_source = 'default_pic.png';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Your Freelancer Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        
        :root {
            --primary-purple: #7a5af8; 
            --primary-dark: #6948f0;    
            --secondary-bg: #f5f7fa;   
            --card-border: #e4e4e4;
            --text-dark: #1f1f1f;
        }

        body { 
            padding-top:70px; 
            font-family:Poppins, sans-serif; 
            background: url('background_selakaukm.png') no-repeat center center/cover;
            background-attachment: fixed;
            min-height: 100vh;
            position: relative;
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
            transition: box-shadow .2s ease, background-color .2s ease;
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
            transition: color .2s ease;
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

        .link-signin {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            padding: 6px 10px;
            border-radius: 8px;
            transition: color .2s ease, background-color .2s ease;
        }
        .link-signin:hover { color: var(--primary-purple); background-color: #f3f1ff; }

        
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

        .form-control:disabled, .form-control[readonly] {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 255, 0.95) 100%);
            opacity: 1;
            cursor: not-allowed;
        }

        .form-select {
            border: 2px solid rgba(240, 147, 251, 0.3);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 255, 0.95) 100%);
            backdrop-filter: blur(10px);
        }

        .form-select:focus {
            border-color: rgba(240, 147, 251, 0.6);
            box-shadow: 0 0 0 0.2rem rgba(240, 147, 251, 0.25);
            background: rgba(255, 255, 255, 0.98);
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

        .form-control[type="file"] {
            background-color: #f8f8f8;
            border-style: dashed;
            cursor: pointer;
        }

        .avatar-preview-container {
            text-align: center;
            margin-bottom: 25px;
            width: 150px; 
            height: 150px; 
            border-radius: 50%; 
            overflow: hidden;
            margin-left: auto; 
            margin-right: auto; 
            border: 4px solid var(--primary-purple); 
            box-shadow: 0 0 0 2px rgba(122, 90, 248, 0.2); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            flex-shrink: 0;
        }

        .avatar-preview-container .avatar-preview {
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            border-radius: 50%; 
            border: none;
            box-shadow: none; 
            margin-bottom: 0; 
        }

        .avatar-preview-container .fa-building {
            font-size: 3rem; 
            color: var(--primary-purple);
        }

        .avatar-name-display {
            text-align: center;
            margin-top: 15px; 
            margin-bottom: 25px;
        }
        .avatar-name-display h4 {
            font-weight: 600;
            color: var(--primary-purple);
        }

        .info-badge {
            display: inline-block;
            background-color: #f3f1ff;
            color: var(--primary-purple);
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            margin-left: 8px;
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


<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="profile-card text-center mb-4">
            <h2><i class="fas fa-briefcase me-2"></i>UPDATE YOUR FREELANCER PROFILE</h2>
        </div>
            
            <form action="freelancer_process.php" method="post" enctype="multipart/form-data" class="form-section">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id) ?>">

                <div class="d-flex flex-column align-items-center mb-4">
                    <div class="avatar-preview-container">
                        <img id="avatar-preview" 
                             src="<?= $avatar_source ?>" 
                             alt="Profile Picture" 
                             class="avatar-preview">
                    </div>
                    <h4 class="mt-3" style="color: var(--primary-purple);">
                        <?= htmlspecialchars($user_info['fullname'] ?? 'Your Name') ?>
                    </h4>
                </div>
                
                <hr class="mb-4">

                <h5 class="text-secondary mb-3"><i class="fas fa-info-circle me-2"></i> Account Information <span class="info-badge">Read Only</span></h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="fullname" class="form-label"><i class="fas fa-user-tie"></i> Full Name</label>
                        <input type="text" id="fullname" name="fullname" class="form-control" 
                               value="<?= htmlspecialchars($user_info['fullname'] ?? '') ?>" 
                               autocomplete="name"
                               readonly disabled>
                        <div class="form-text">Update this in your account settings</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label"><i class="fas fa-at"></i> Email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($user_info['email']) ?>"
                               autocomplete="email"
                               readonly disabled>
                        <div class="form-text">Update this in your account settings</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="text" id="phone" name="phone" class="form-control" 
                           value="<?= htmlspecialchars($user_info['phone']) ?>"
                           autocomplete="tel"
                           readonly disabled>
                    <div class="form-text">Update this in your account settings</div>
                </div>

                <hr class="my-4">

                <h5 class="text-secondary mb-3"><i class="fas fa-briefcase me-2"></i> Professional Profile</h5>
                <div class="mb-3">
                    <label for="bio" class="form-label"><i class="fas fa-file-alt"></i> Professional Bio</label>
                    <textarea id="bio" name="bio" class="form-control" rows="4" 
                              placeholder="A brief summary of your experience and what you offer (max 500 characters)"><?= htmlspecialchars($editrow['bio'] ?? '') ?></textarea>
                    <div class="form-text">Describe your professional background and services</div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="skills" class="form-label"><i class="fas fa-tools"></i> Key Skills</label>
                        <input type="text" id="skills" name="skills" class="form-control" 
                               value="<?= htmlspecialchars($editrow['skills'] ?? '') ?>"
                               placeholder="e.g., Web Design, SEO, Copywriting">
                        <div class="form-text">Comma separated list of your top skills</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="availability" class="form-label"><i class="fas fa-clock"></i> Availability <span class="text-danger">*</span></label>
                        <select id="availability" name="availability" class="form-select" required>
                            <option value="">-- Select Availability --</option>
                            <?php foreach (['Full-time', 'Part-time', 'Occasional'] as $opt): ?>
                                <option value="<?= htmlspecialchars($opt) ?>" 
                                    <?= (isset($editrow['availability']) && $editrow['availability'] == $opt) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($opt) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="text-secondary mb-3"><i class="fas fa-upload me-2"></i> Profile Media</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="profile_picture" class="form-label"><i class="fas fa-image"></i> Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept="image/*">
                        <?php if ($is_edit_mode && !empty($editrow['profile_picture'])): ?>
                            <div class="form-text mt-1">Current: <?= htmlspecialchars($editrow['profile_picture']) ?></div>
                        <?php else: ?>
                            <div class="form-text mt-1">Upload a professional profile picture</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="qr_code" class="form-label"><i class="fas fa-qrcode"></i> Payment QR Code </label>
                        <input type="file" id="qr_code" name="qr_code" class="form-control" accept=".png,.jpg,.jpeg,.gif">
                        <?php if ($is_edit_mode && !empty($editrow['qr_code'])): ?>
                            <div class="form-text mt-1">Current: <?= htmlspecialchars($editrow['qr_code']) ?></div>
                        <?php else: ?>
                            <div class="form-text mt-1">Upload your payment QR code for clients</div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="mt-4 mb-3">

                <div class="d-flex justify-content-end pt-2">
                    <a href="freelancer_dashboard.php" class="btn btn-secondary me-3">
                        <i class="fas fa-times-circle me-1"></i> Cancel
                    </a>
                    <button type="submit" name="<?= $is_edit_mode ? 'update' : 'create' ?>" 
                            class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        <?= $is_edit_mode ? 'Update Profile' : 'Create Profile' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // JavaScript for Instant Image Preview
    document.getElementById('profile_picture').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatar-preview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
</script>

</body>
</html>
