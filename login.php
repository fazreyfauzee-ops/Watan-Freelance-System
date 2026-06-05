<?php
session_start();
require 'database.php'; // PDO connection

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'];
    $password = $_POST['password'];

    // System admin login
    if ($username === 'admin123' && $password === 'admin123') {
        $_SESSION['id'] = 0;
        $_SESSION['username'] = 'admin123';
        $_SESSION['role'] = 'admin';
        $_SESSION['userid'] = 0;
        header("Location: admin_dashboard.php");
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);

        if ($stmt->rowCount() == 1) {

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $user['password'])) {

                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['userid'] = $user['id'];

                if (strtolower($user['role']) === "admin") {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();

            } else {
                $error = "Incorrect password!";
            }

        } else {
            $error = "User not found!";
        }

    } catch (PDOException $e) {
        $error = "Database error!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Watan Freelance</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    min-height: 100vh;
    display: flex;
    overflow: hidden;
    background: url('background_login.jpg') no-repeat center center/cover;
    position: relative;
}

body::before {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.4);
    z-index: 1;
}

.main-container {
    display: flex;
    width: 100%;
    height: 100vh;
    position: relative;
    z-index: 2;
}

.left-section {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

.left-content {
    text-align: center;
    color: white;
    max-width: 500px;
}

.welcome-title {
    font-size: 48px;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 0 4px 20px rgba(0,0,0,0.5);
}

.welcome-subtitle {
    font-size: 20px;
    font-weight: 400;
    opacity: 0.9;
    text-shadow: 0 2px 10px rgba(0,0,0,0.5);
    line-height: 1.6;
}

.right-section {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    overflow: hidden;
    position: relative;
}

.form-container {
    background: rgba(255,255,255,0.98);
    backdrop-filter: blur(30px);
    padding: 60px;
    border-radius: 30px;
    box-shadow: 0 25px 80px rgba(0,0,0,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    width: 100%;
    height: 100%;
    max-width: none;
    display: flex;
    flex-direction: column;
    justify-content: center;
    overflow-y: auto;
    position: relative;
    animation: slideInRight 0.8s ease-out;
}

.form-container::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #ffd4e5, #d4a5ff, #ffd4e5, #d4a5ff);
    border-radius: 30px;
    z-index: -1;
    animation: borderGlow 3s ease-in-out infinite;
}

.logo-container {
    text-align: center;
    margin-bottom: 40px;
    animation: fadeInDown 0.8s ease-out;
}

.logo-container img {
    max-width: 140px;
    height: auto;
    border-radius: 20px;
    box-shadow: 0 12px 30px rgba(212, 175, 229, 0.3);
    transition: transform 0.3s ease;
}

.logo-container img:hover {
    transform: scale(1.05) rotate(2deg);
}

.title {
    font-size: 36px;
    font-weight: 700;
    color: #1e293b;
    text-align: center;
    margin-bottom: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    animation: fadeInUp 0.8s ease-out 0.3s both;
}

.title-icon {
    width: 55px;
    height: 55px;
    background: linear-gradient(135deg, #ffd4e5 0%, #d4a5ff 100%);
    border-radius: 15px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    box-shadow: 0 6px 20px rgba(212, 175, 229, 0.4);
    animation: pulse 2s ease-in-out infinite;
}

label {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
    font-size: 15px;
    animation: fadeInUp 0.8s ease-out 0.5s both;
}

.icon-box {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.icon-box:hover {
    transform: translateY(-2px) scale(1.1);
}

.username-icon {
    background: linear-gradient(135deg, #a8e6cf 0%, #c8b6ff 100%);
    animation: iconFloat 3s ease-in-out infinite;
}

.password-icon {
    background: linear-gradient(135deg, #b8e6cf 0%, #a8c4ff 100%);
    animation: iconFloat 3s ease-in-out infinite 1s;
}

input {
    width: 100%;
    padding: 16px 20px;
    border-radius: 15px;
    border: none;
    margin-bottom: 25px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: rgba(255,255,255,0.95);
    color: #1e293b;
    animation: fadeInUp 0.8s ease-out 0.7s both;
}

input::placeholder {
    color: rgba(212, 175, 229, 0.5);
    font-style: italic;
}

input:focus {
    outline: none;
    border: none;
    box-shadow: 0 0 0 6px rgba(212, 175, 229, 0.15);
    background: rgba(255,255,255,1);
    transform: translateY(-2px);
}

.btn {
    width: 100%;
    background: linear-gradient(135deg, #ffd4e5 0%, #d4a5ff 100%);
    color: #fff;
    border: none;
    padding: 18px;
    border-radius: 15px;
    font-weight: 600;
    cursor: pointer;
    font-size: 17px;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(212, 175, 229, 0.4);
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.8s ease-out 0.9s both;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}

.btn:hover::before {
    left: 100%;
}

.btn:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 12px 35px rgba(212, 175, 229, 0.6);
}

.error {
    color: #e8a4a4;
    text-align: center;
    margin-bottom: 25px;
    font-weight: 600;
    padding: 15px;
    background: rgba(232, 164, 174, 0.1);
    border-radius: 12px;
    border: 1px solid rgba(232, 164, 174, 0.2);
    animation: shake 0.5s ease-in-out;
}

.register-text {
    text-align: center;
    margin-top: 30px;
    font-size: 15px;
    color: #000000;
    animation: fadeInUp 0.8s ease-out 1.1s both;
}

.register-text a {
    color: #000000;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.register-text a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: #d4a5ff;
    transition: width 0.3s ease;
}

.register-text a:hover::after {
    width: 100%;
}

.register-text a:hover {
    color: #a8c4ff;
}

.copyright {
    text-align: center;
    margin-top: 25px;
    font-size: 12px;
    color: #64748b;
    font-weight: 400;
    animation: fadeIn 0.8s ease-out 1.3s both;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes fadeInDown {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes fadeInUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

@keyframes iconFloat {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}

@keyframes borderGlow {
    0%, 100% {
        opacity: 0.5;
    }
    50% {
        opacity: 1;
    }
}

@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-5px);
    }
    75% {
        transform: translateX(5px);
    }
}

@media (max-width: 768px) {
    .left-section {
        display: none;
    }
    
    .right-section {
        flex: 1;
    }
    
    .form-container {
        padding: 30px;
    }
}
</style>
</head>

<body>

<div class="main-container">
    <div class="left-section">
        <div class="left-content">
            <h1 class="welcome-title">Welcome To Watan Freelance </h1>
            <p class="welcome-subtitle">Where clients and talents connect to create meaningful work</p>
        </div>
    </div>
    
    <div class="right-section">
        <div class="form-container">
            <div class="logo-container">
                <img src="watan_logo.png" alt="Watan Freelance Logo">
            </div>
            
            <div class="title">
                <span class="title-icon">🔐</span>
                Login Back
            </div>

            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">

                <label>
                    <span class="icon-box username-icon">👤</span>
                    Username
                </label>
                <input type="text" name="username" placeholder="Enter your username" required>

                <label>
                    <span class="icon-box password-icon">🔐</span>
                    Password
                </label>
                <input type="password" name="password" placeholder="Enter your password" required>

                <button type="submit" class="btn">Login</button>

                <div class="register-text">
                    Don't have an account?
                    <a href="registration.php">Register Now</a>
                </div>

                <div class="copyright">
                    © 2024 Watan Freelance Platform. All rights reserved.
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>