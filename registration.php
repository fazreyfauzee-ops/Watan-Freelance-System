<?php 
session_start(); 
require 'database.php'; // using PDO connection

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        // check if username already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $check->execute(['username' => $username]);

        if ($check->rowCount() > 0) {
            $message = "❌ Username already exists!";
        } else {
            // insert into DB
            $stmt = $conn->prepare("
                INSERT INTO users (fullname, username, email, phone, role, password) 
                VALUES (:fullname, :username, :email, :phone, :role, :password)
            ");
            $stmt->execute([
                ':fullname' => $fullname,
                ':username' => $username,
                ':email' => $email,
                ':phone' => $phone,
                ':role' => $role,
                ':password' => $password
            ]);
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Watan Freelance Registration</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
    overflow-y: auto;
    position: relative;
}

.form-container {
    background: rgba(255,255,255,0.98);
    backdrop-filter: blur(30px);
    padding: 50px;
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
    background: linear-gradient(45deg, #667eea, #764ba2, #667eea, #764ba2);
    border-radius: 30px;
    z-index: -1;
    animation: borderGlow 3s ease-in-out infinite;
}

.logo-container {
    text-align: center;
    margin-bottom: 30px;
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
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
    text-align: center;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    animation: fadeInUp 0.8s ease-out 0.3s both;
}

.title-icon {
    display: inline-flex;
    width: 55px;
    height: 55px;
    background: linear-gradient(135deg, #ffd4e5 0%, #d4a5ff 100%);
    border-radius: 15px;
    color: white;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 20px rgba(212, 175, 229, 0.4);
    font-size: 1.4rem;
    animation: pulse 2s ease-in-out infinite;
}

label { 
    font-weight: 600; 
    color: #334155; 
    display: flex; 
    align-items: center;
    gap: 12px;
    text-align: left; 
    margin: 12px 0 8px; 
    font-size: 15px;
    animation: fadeInUp 0.8s ease-out 0.5s both;
}

input, select {
    width: 100%;
    padding: 16px 20px;
    border-radius: 15px;
    border: 2px solid rgba(212, 175, 229, 0.2);
    margin-bottom: 20px;
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

input:focus, select:focus {
    outline: none;
    border-color: #d4a5ff;
    box-shadow: 0 0 0 6px rgba(212, 175, 229, 0.15);
    background: rgba(255,255,255,1);
    transform: translateY(-2px);
}

.role-buttons { 
    display: flex; 
    justify-content: space-between; 
    margin-bottom: 25px; 
    animation: fadeInUp 0.8s ease-out 0.9s both;
}

.role-btn {
    width: 48%;
    padding: 15px;
    border-radius: 15px;
    font-weight: 600;
    cursor: pointer;
    border: 2px solid rgba(212, 175, 229, 0.3);
    background: rgba(255,255,255,0.9);
    transition: all 0.3s ease;
    color: #334155;
    font-size: 15px;
    position: relative;
    overflow: hidden;
}

.role-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(212, 175, 229, 0.1), transparent);
    transition: left 0.5s ease;
}

.role-btn:hover::before {
    left: 100%;
}

.role-btn.active {
    background: linear-gradient(135deg, #ffd4e5 0%, #d4a5ff 100%);
    color: white;
    border: none;
    box-shadow: 0 6px 20px rgba(212, 175, 229, 0.4);
    transform: translateY(-2px);
}

.btn {
    background: linear-gradient(135deg, #ffd4e5 0%, #d4a5ff 100%);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 15px;
    font-weight: 600;
    width: 100%;
    cursor: pointer;
    font-size: 17px;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(212, 175, 229, 0.4);
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.8s ease-out 1.1s both;
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

.message { 
    color: #e8a4a4; 
    font-weight: 600; 
    text-align: center; 
    margin-bottom: 20px;
    padding: 15px;
    background: rgba(232, 164, 174, 0.1);
    border-radius: 12px;
    border: 1px solid rgba(232, 164, 174, 0.2);
    animation: shake 0.5s ease-in-out;
}

.text { 
    text-align: center; 
    margin-top: 25px; 
    font-size: 15px; 
    color: #64748b;
    animation: fadeInUp 0.8s ease-out 1.3s both;
}

.text a { 
    color: #d4a5ff; 
    text-decoration: none; 
    font-weight: 600; 
    transition: all 0.3s ease; 
    position: relative;
}

.text a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: #d4a5ff;
    transition: width 0.3s ease;
}

.text a:hover::after {
    width: 100%;
}

.text a:hover { 
    color: #a8c4ff; 
}

.copyright {
    text-align: center;
    margin-top: 25px;
    font-size: 12px;
    color: #64748b;
    font-weight: 400;
    animation: fadeIn 0.8s ease-out 1.5s both;
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
        padding: 20px;
    }
    
    .form-container {
        padding: 30px;
    }
    
    .title {
        font-size: 24px;
    }
}
</style>
</head>
<body>

<div class="main-container">
    <div class="left-section">
        <div class="left-content">
            <h1 class="welcome-title">Welcome to Watan Freelance</h1>
            <p class="welcome-subtitle">Create your account. Start collaborating today.</p>
        </div>
    </div>
    
    <div class="right-section">
        <div class="form-container">
            <div class="logo-container">
                <img src="watan_logo.png" alt="Watan Freelance Logo">
            </div>
            
            <div class="title">
                <span class="title-icon">📋</span>
                Create Your Account
            </div>
            
            <?php if (!empty($message)): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php endif; ?>
            
            <form method="POST">
                <!-- Full Name -->
                <label>
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px;">
                        👤
                    </span> Full Name
                </label>
                <input type="text" name="fullname" placeholder="Enter your full name" required>

                <!-- Username -->
                <label>
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border-radius: 10px;">
                        🆔
                    </span> Username
                </label>
                <input type="text" name="username" placeholder="Choose a username" required>

                <!-- Email -->
                <label>
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; border-radius: 10px;">
                        ✉️
                    </span> Email
                </label>
                <input type="email" name="email" placeholder="Enter your email" required>

                <!-- Phone -->
                <label>
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 10px;">
                        📞
                    </span> Phone Number
                </label>
                <input type="text" name="phone" placeholder="Enter your phone number" required>

                <!-- Role -->
                <label>
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; border-radius: 10px;">
                        🎯
                    </span> Sign up as:
                </label>
                <div class="role-buttons">
                    <button type="button" class="role-btn" id="clientBtn">Client</button>
                    <button type="button" class="role-btn" id="freelancerBtn">Freelancer</button>
                </div>
                <input type="hidden" name="role" id="roleInput" required>

                <!-- Password -->
                <label>
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; border-radius: 10px;">
                        🔐
                    </span> Password
                </label>
                <input type="password" name="password" placeholder="Create a password" required>

                <button type="submit" class="btn">Register</button>
                <div class="text">
                    Already have an account? <a href="login.php" style="color: #000000; font-weight: 700;">Login now</a>
                </div>

                <div class="copyright">
                    &copy; 2024 Watan Freelance Platform. All rights reserved.
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const clientBtn = document.getElementById("clientBtn");
const freelancerBtn = document.getElementById("freelancerBtn");
const roleInput = document.getElementById("roleInput");

// Default
roleInput.value = "client";
clientBtn.classList.add("active");

clientBtn.onclick = () => {
    roleInput.value = "client";
    clientBtn.classList.add("active");
    freelancerBtn.classList.remove("active");
};

freelancerBtn.onclick = () => {
    roleInput.value = "freelancer";
    freelancerBtn.classList.add("active");
    clientBtn.classList.remove("active");
};
</script>

</body>
</html>