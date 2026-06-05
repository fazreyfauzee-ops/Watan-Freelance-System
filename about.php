<?php
session_start();

// Navigation Logic
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
    <title>About | Watan Freelance System</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root{
            --primary-purple:#7a5af8;
            --primary-dark:#6948f0;
            --primary-light-purple:#F0EDFF;
            --gradient-1:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            /* STRONGER SHADOW for dark mode feel */
            --card-shadow-dark: 0 20px 50px rgba(0,0,0,0.5);
            --hover-shadow-dark: 0 30px 60px rgba(0,0,0,0.6);
        }

        body{
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            font-family: 'Poppins', sans-serif;
            padding-top: 80px;
            min-height: 100vh;
            color: #334155;
            display: flex;
            flex-direction: column;
        }

        /* --- NAVBAR (Consistent) --- */
        .custom-nav {
            position: fixed; top: 0; width: 100%;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 30px; z-index: 1000;
        }
        .logo {
            font-size: 1.5rem; font-weight: 700;
            color: var(--primary-purple);
            text-decoration: none;
        }
        .nav-menu { display: flex; list-style: none; gap: 30px; align-items: center; margin: 0; }
        .nav-menu li a { text-decoration: none; color: #333; font-weight: 500; transition: color .2s ease; }
        .nav-menu li a:hover { color: var(--primary-purple); }
        
        .nav-actions { display: flex; align-items: center; gap: 20px; font-weight: 500; color: #333; }
        
        .btn-profile-edit {
            background-color: var(--primary-light-purple);
            color: var(--primary-purple);
            border: none; padding: 10px 20px; border-radius: 8px;
            font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px;
            transition: background-color 0.3s ease;
        }
        .btn-profile-edit:hover { background-color: #E0D9FF; color: var(--primary-purple); }

        .link-logout { color: #333; text-decoration: none; font-weight: 500; transition: color .2s ease; }
        .link-logout:hover { color: var(--primary-purple); }

        /* --- MAIN CONTAINER --- */
        /* Increased max-width for a "bigger" feel */
        .container { max-width: 1100px; margin: 0 auto; padding: 50px 20px; flex: 1; }

        .page-title {
            text-align: center; color: #fff; font-weight: 700;
            font-size: 3rem; margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .page-subtitle {
            text-align: center; color: rgba(255,255,255,0.95); font-size: 1.2rem;
            margin-bottom: 50px; font-weight: 400;
        }

        /* --- DARKER GLASS CARD --- */
        .glass-card {
            /* Darker, semi-transparent background */
            background: rgba(30, 41, 59, 0.85); 
            backdrop-filter: blur(20px);
            border-radius: 25px; /* Slightly more rounded */
            box-shadow: var(--card-shadow-dark);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 35px;
            transition: all 0.3s ease;
        }
        .glass-card:hover { 
            transform: translateY(-5px); 
            box-shadow: var(--hover-shadow-dark);
            border-color: rgba(255,255,255,0.2);
        }

        /* --- HERO IMAGE --- */
        .about-cover {
            width: 100%;
            height: 400px; /* Even taller */
            background: url('ftsmview.jpg') center center/cover no-repeat;
            position: relative;
        }
        .about-cover::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 150px;
            /* Fade into the new dark card background color */
            background: linear-gradient(to top, rgba(30, 41, 59, 1), transparent);
        }
        
        .about-badge {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--gradient-1);
            color: white;
            padding: 12px 35px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
            z-index: 2;
            white-space: nowrap;
        }

        /* --- CONTENT BODY (Light Text on Dark BG) --- */
        /* Increased padding for "bigger" feel */
        .card-body { padding: 70px 50px 50px 50px; line-height: 1.8; color: #e2e8f0; font-size: 1.1rem; }

        .section-title {
            font-size: 1.6rem;
            color: #fff; /* White title */
            font-weight: 700;
            margin-bottom: 25px;
            display: flex; align-items: center; gap: 15px;
        }
        .section-title i { color: #a78bfa; /* Lighter purple icon */ font-size: 1.8rem; }

        .highlight-box {
            /* Darker, translucent purple background */
            background: rgba(122, 90, 248, 0.15);
            border-left: 5px solid #a78bfa;
            padding: 30px;
            border-radius: 0 15px 15px 0;
            margin: 40px 0;
            font-style: italic;
            color: #e2e8f0; /* Light text */
        }
        .highlight-box i { color: #a78bfa; }

        /* --- FOOTER --- */
        footer {
            text-align: center; color: rgba(255,255,255,0.8);
            padding: 20px; font-size: 0.9rem; margin-top: auto;
        }
        footer a { color: #fff; text-decoration: none; font-weight: 600; border-bottom: 1px dotted rgba(255,255,255,0.5); }
        footer a:hover { color: #fff; border-bottom: 1px solid #fff; }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .about-cover { height: 250px; }
            .card-body { padding: 60px 25px 30px 25px; }
            .page-title { font-size: 2.5rem; }
            .nav-actions { display: none; }
        }
    </style>
</head>
<body>

<nav class="custom-nav">
    <a href="dashboard.php" class="logo">Watan Freelance System</a>
    <ul class="nav-menu">
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="browse_services.php">Services</a></li>
        <li><a href="about.php" style="color:var(--primary-purple);">About</a></li>
        <?php if ($isLoggedIn && $role === 'freelancer'): ?>
            <li><a href="freelancer_booking_list.php">Booking List</a></li>
        <?php elseif ($isLoggedIn && $role === 'client'): ?>
            <li><a href="client_booking_list.php">Booking List</a></li>
        <?php endif; ?>
    </ul>
    <div class="nav-actions">
        <?php if ($isLoggedIn): ?>
            <span>Welcome, <?php echo htmlspecialchars($currentUsername); ?></span>
            <a class="btn-profile-edit" href="<?php echo $profileLink; ?>">
                <i class="bi bi-person-gear"></i> <?php echo $profileLabel; ?>
            </a>
            <a class="link-logout" href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php" style="text-decoration: none; color: #333; font-weight: 500; margin-right: 15px;">Sign in</a>
            <a href="registration.php" style="background-color: var(--primary-purple); color: white; padding: 8px 20px; border-radius: 5px; text-decoration: none; font-weight: 500;">Join</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    
    <h1 class="page-title">About Us</h1>
    <p class="page-subtitle">Empowering the UKM Community through Freelance Opportunities.</p>

    <div class="glass-card">
        <div class="about-cover">
            <div class="about-badge">
                <i class="bi bi-mortarboard-fill"></i> Proudly FTSM UKM
            </div>
        </div>

        <div class="card-body">
            <div class="section-title">
                <i class="bi bi-info-circle-fill"></i> What is Watan Freelance Platform?
            </div>
            <p>
                The <strong>Watan Freelance Platform</strong> is an initiative designed specifically for <strong>Universiti Kebangsaan Malaysia (UKM)</strong> students. We provide a secure, dedicated online marketplace where students can connect for temporary or project-based work. 
            </p>
            <p>
                This platform serves as a vital bridge, allowing clients within the UKM community—whether fellow students, lecturers, or staff—to easily browse and engage with student talent.
            </p>

            <div class="highlight-box">
                <i class="bi bi-quote fs-2"></i>
                "Designed to provide a mutually beneficial space... helping students secure part-time work relevant to their academic goals while enabling clients to access a pool of skilled individuals."
            </div>

            <div class="section-title mt-5">
                <i class="bi bi-briefcase-fill"></i> Our Services & Skills
            </div>
            <p>
                Freelancers on our platform offer a diverse array of services tailored to modern demands. From <strong>content writing</strong> and <strong>graphic design</strong> to <strong>web development</strong>, <strong>videography</strong> and <strong>photography</strong>.
            </p>
            <p>
                This diversity allows students to refine their skills in real-world scenarios, building a robust portfolio before they even graduate. By connecting talent with opportunity, Watan ensures that every project contributes to the growth of our student community.
            </p>

            <div class="text-center mt-5">
                <a href="browse_services.php" class="btn btn-lg px-5 py-3 rounded-pill fw-bold" style="background: var(--gradient-1); border:none; color:white; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                    Browse Services Now <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </div>

</div>

<footer>
    &copy; 2025 <strong>Watan Freelance System</strong>. All Rights Reserved.<br>
    <a href="#">Privacy Policy</a> | <a href="#">Terms of Use</a>
</footer>

</body>
</html>