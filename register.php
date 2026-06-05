<?php
session_start();
// Include the database file only if needed for initial checks, but for a simple selection screen, it's often not required.
// We'll keep it commented out for performance if not used.
// include_once 'database.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Join Watan Freelance - Select Role</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">

    <style>
        /* Inherit the existing style elements */
        :root {
            --primary-purple: #7a5af8;
            --secondary-bg: #f5f7fa;
            --card-shadow: rgba(0,0,0,0.05);
            --text-dark: #1f1f1f;
        }

        body {
            background: var(--secondary-bg); /* Use the light background for the whole page */
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        /* Apply the existing style to the navigation bar (if you include it) */
        /* If you include a separate nav_bar.php, you can remove this style block */
        .header-container {
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 10;
        }
        /* Nav bar style from your example */
        nav {
            background-color: rgba(255,255,255,0.9);
            backdrop-filter: saturate(180%) blur(6px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 60px;
        }
        nav .logo {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary-purple);
        }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .link-signin { color: #333; text-decoration: none; font-weight: 500; padding: 6px 10px; border-radius: 8px; transition: color .2s ease, background-color .2s ease; }
        .btn-join-nav {
            background: var(--primary-purple);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 999px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s ease;
        }
        .btn-join-nav:hover { background: #6948f0; }

        /* --- New Styles for Role Selection --- */
        .role-card-container {
            max-width: 900px;
            width: 90%;
            padding: 40px 0;
        }

        .role-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-purple);
            margin-bottom: 30px;
        }

        .role-card {
            background-color: #fff;
            padding: 30px;
            border-radius: 15px;
            border: 1px solid #e4e4e4;
            box-shadow: 0 4px 12px var(--card-shadow);
            text-align: center;
            transition: all 0.3s ease; /* For animation */
            cursor: pointer;
            text-decoration: none; /* Remove hyperlink underline */
            color: var(--text-dark);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* The Animated Hover Effect! */
        .role-card:hover {
            border-color: var(--primary-purple);
            box-shadow: 0 8px 20px rgba(122, 90, 248, 0.2); /* Purple glow shadow */
            transform: translateY(-8px); /* Lift the card */
        }

        .role-icon {
            font-size: 3.5rem;
            color: var(--primary-purple);
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }

        .role-card:hover .role-icon {
            color: #5d34e6; /* Slightly darker purple on hover */
        }

        .role-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .role-card p {
            color: #6c757d;
            font-size: 1rem;
            margin-bottom: 0;
        }
    </style>
</head>

<body>

<div class="header-container">
    <nav>
        <div class="logo">Watan Freelance</div>
        <div class="nav-actions">
            <a href="login.php" class="link-signin">Sign In</a>
            <a href="register.php" class="btn-join-nav">Join Now</a> 
        </div>
    </nav>
</div>


<div class="container role-card-container">
    <h2 class="text-center role-title">How do you want to join?</h2>
    <p class="text-center text-muted mb-5">Select the option that best describes you to start your journey.</p>
    
    <div class="row g-4">
        
        <div class="col-md-6">
            <a href="freelancer_form.php" class="role-card">
                <i class="fas fa-palette role-icon"></i>
                <h3>Register as Freelancer</h3>
                <p>I want to offer my skills and find exciting projects.</p>
                <p class="text-secondary mt-3"><i class="fas fa-arrow-right"></i> Start Earning</p>
            </a>
        </div>

        <div class="col-md-6">
            <a href="register_client.php" class="role-card">
                <i class="fas fa-handshake role-icon"></i>
                <h3>Register as Client</h3>
                <p>I want to hire talent for my business or personal needs.</p>
                <p class="text-secondary mt-3"><i class="fas fa-arrow-right"></i> Start Hiring</p>
            </a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>