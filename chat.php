<?php
session_start();
include_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['username'];
$selected_user = isset($_GET['user']) ? trim($_GET['user']) : null;

// User Validation Logic
if ($selected_user) {
    try {
        $stmt = $conn->prepare("SELECT id, username, fullname, role FROM users WHERE username = :username");
        $stmt->bindParam(':username', $selected_user);
        $stmt->execute();
        $selected_user_info = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$selected_user_info) { $selected_user = null; }
    } catch (PDOException $e) {
        error_log("Error validating selected user: " . $e->getMessage());
        $selected_user = null;
    }
}

// Fetch Users Logic - MODIFIED TO SHOW ONLY RELEVANT USERS
try {
    // First, get the current user's ID
    $stmt_user = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $stmt_user->bindParam(':username', $current_user);
    $stmt_user->execute();
    $current_user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_user_data) {
        throw new Exception("Current user not found");
    }
    
    $current_user_id = $current_user_data['id'];
    
    // This query fetches users who have either:
    // 1. Exchanged messages with the current user
    // 2. Have booking relationships with the current user (as client or freelancer)
    $sql = "SELECT DISTINCT u.id, u.username, u.fullname, u.role,
            MAX(cm.created_at) as last_message_time
            FROM users u
            LEFT JOIN (
                SELECT sender as username, created_at
                FROM chat_messages
                WHERE receiver = :current_user
                UNION
                SELECT receiver as username, created_at
                FROM chat_messages
                WHERE sender = :current_user
            ) cm ON u.username = cm.username
            LEFT JOIN (
                SELECT u2.username
                FROM booking b
                INNER JOIN users u2 ON (
                    (b.client_id = :current_user_id AND u2.id = b.freelancer_id)
                    OR (b.freelancer_id = :current_user_id AND u2.id = b.client_id)
                )
            ) b ON u.username = b.username
            WHERE u.username != :current_user
            AND (cm.username IS NOT NULL OR b.username IS NOT NULL)
            GROUP BY u.id, u.username, u.fullname, u.role
            ORDER BY last_message_time DESC, u.fullname ASC, u.username ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':current_user', $current_user);
    $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    error_log("Error fetching users: " . $e->getMessage());
} catch (Exception $e) {
    $users = [];
    error_log("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat | Watan Freelance System</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  
  <style>
    :root {
        --primary-purple:#6A4DF4;
        --primary-light-purple:#F0EDFF;
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --accent-gradient: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-border: rgba(255, 255, 255, 0.4);
        --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        --text-dark: #2d3748;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }

    /* 1. VIBRANT ANIMATED BACKGROUND */
    body {
        background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
        min-height: 100vh;
        overflow: hidden;
        color: var(--text-dark);
        padding-top: 80px;
    }
    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* NAVBAR STYLES - Matched to Image */
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

    /* MAIN LAYOUT */
    .chat-layout {
        height: calc(100vh - 100px); max-width: 1400px; margin: 10px auto; padding: 0 20px;
        display: flex; gap: 20px;
    }

    /* GLASS PANEL (Common Style) */
    .glass-panel {
        background: var(--glass-bg); backdrop-filter: blur(25px) saturate(200%);
        -webkit-backdrop-filter: blur(25px) saturate(200%); border: 1px solid var(--glass-border);
        border-radius: 24px; box-shadow: var(--glass-shadow); overflow: hidden; display: flex; flex-direction: column;
    }

    /* SIDEBAR */
    .users-sidebar { width: 320px; flex-shrink: 0; }
    .sidebar-header { padding: 25px; background: rgba(255, 255, 255, 0.3); border-bottom: 1px solid rgba(255, 255, 255, 0.3); }
    .sidebar-header h3 { font-size: 1.2rem; font-weight: 700; color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 0; display: flex; align-items: center; gap: 10px;}
    .users-list { flex: 1; overflow-y: auto; padding: 15px; }
    .users-list::-webkit-scrollbar, .messages-box::-webkit-scrollbar { width: 6px; }
    .users-list::-webkit-scrollbar-thumb, .messages-box::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.5); border-radius: 10px; }

    .user-card {
        display: flex; align-items: center; gap: 15px; padding: 12px 15px; margin-bottom: 10px;
        background: rgba(255, 255, 255, 0.6); border-radius: 16px; text-decoration: none; color: var(--text-dark);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid transparent;
    }
    .user-card:hover { transform: translateY(-3px) scale(1.02); background: white; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    .user-card.active { background: white; border-left: 5px solid #764ba2; box-shadow: 0 10px 25px rgba(118, 75, 162, 0.2); }

    .avatar-circle {
        width: 45px; height: 45px; border-radius: 50%; background: var(--secondary-gradient);
        display: flex; justify-content: center; align-items: center; color: white; font-weight: 700; font-size: 1.1rem;
        box-shadow: 0 4px 10px rgba(245, 87, 108, 0.4);
    }
    .user-card:nth-child(2n) .avatar-circle { background: var(--primary-gradient); box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4); }
    .user-card:nth-child(3n) .avatar-circle { background: var(--accent-gradient); box-shadow: 0 4px 10px rgba(132, 250, 176, 0.4); }
    .user-info h4 { font-size: 0.95rem; font-weight: 700; margin: 0; }
    .user-role { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

    /* CHAT WINDOW */
    .chat-window { flex: 1; position: relative; }
    .chat-header { padding: 15px 30px; background: rgba(255, 255, 255, 0.5); border-bottom: 1px solid rgba(255,255,255,0.3); display: flex; align-items: center; gap: 15px; }
    .chat-header h2 { font-size: 1.3rem; font-weight: 700; margin: 0; color: #333; }
    .status-dot { width: 10px; height: 10px; background: #2ecc71; border-radius: 50%; box-shadow: 0 0 10px #2ecc71; }

    .messages-box { flex: 1; padding: 30px; background: rgba(255, 255, 255, 0.3); display: flex; flex-direction: column; gap: 18px; overflow-y: auto; scroll-behavior: smooth; }
    .bubble { max-width: 70%; padding: 14px 20px; border-radius: 20px; font-size: 0.95rem; line-height: 1.6; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.05); animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    @keyframes popIn { 0% { opacity: 0; transform: scale(0.8); } 100% { opacity: 1; transform: scale(1); } }
    .me { align-self: flex-end; background: var(--primary-gradient); color: white; border-bottom-right-radius: 4px; background-size: 200% auto; transition: 0.5s; }
    .me:hover { background-position: right center; }
    .other { align-self: flex-start; background: white; color: #4a5568; border-bottom-left-radius: 4px; }
    .timestamp { font-size: 0.7rem; margin-top: 5px; opacity: 0.7; display: block; text-align: right; }
    .other .timestamp { text-align: left; }

    .input-area { padding: 20px 30px; background: rgba(255, 255, 255, 0.6); border-top: 1px solid rgba(255, 255, 255, 0.4); }
    .input-wrapper { background: white; border-radius: 50px; padding: 5px 10px 5px 25px; display: flex; align-items: center; gap: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); transition: 0.3s; border: 2px solid transparent; }
    .input-wrapper:focus-within { border-color: #764ba2; box-shadow: 0 5px 25px rgba(118, 75, 162, 0.15); }
    .input-wrapper input { border: none; outline: none; flex: 1; font-size: 1rem; color: #4a5568; background: transparent; }
    .btn-send { width: 45px; height: 45px; border-radius: 50%; border: none; background: var(--secondary-gradient); color: white; font-size: 1.2rem; cursor: pointer; transition: 0.3s; display: flex; justify-content: center; align-items: center; padding-left: 4px; }
    .btn-send:hover { transform: rotate(15deg) scale(1.1); box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4); }

    .empty-state { height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; color: white; text-align: center; }
    .empty-icon { font-size: 5rem; margin-bottom: 20px; background: rgba(255,255,255,0.2); width: 120px; height: 120px; border-radius: 50%; display: flex; justify-content: center; align-items: center; backdrop-filter: blur(10px); }

    .empty-conversations {
        text-align: center; padding: 30px 20px; color: rgba(255,255,255,0.9);
    }
    .empty-conversations i {
        font-size: 3.5rem; display: block; margin-bottom: 15px; opacity: 0.6;
    }
    .empty-conversations h4 {
        font-weight: 600; font-size: 1.1rem; margin-bottom: 8px;
    }
    .empty-conversations p {
        font-size: 0.85rem; opacity: 0.8; line-height: 1.5;
    }

    @media (max-width: 768px) {
        body { padding-top: 0; overflow: auto; background: #f0f2f5; }
        .custom-nav { position: relative; }
        .chat-layout { flex-direction: column; height: auto; margin-top: 0; padding-bottom: 30px; }
        .users-sidebar { width: 100%; height: 250px; }
        .chat-window { height: 600px; }
        .nav-actions { display: none; }
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

<nav class="custom-nav">
    <a href="dashboard.php" class="logo">Watan Freelance System</a>
    <ul class="nav-menu">
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

<div class="chat-layout">
    
    <aside class="glass-panel users-sidebar">
        <div class="sidebar-header">
            <h3><i class="bi bi-chat-heart-fill" style="color: #ffd1ff;"></i> Messages</h3>
        </div>
        <div class="users-list">
            <?php if (empty($users)): ?>
                <div class="empty-conversations">
                    <i class="bi bi-inbox"></i>
                    <h4>No conversations yet</h4>
                    <p>Start chatting with people you've booked with or browse services to find freelancers!</p>
                </div>
            <?php else: ?>
                <?php foreach ($users as $user): 
                    $display_name = !empty($user['fullname']) ? htmlspecialchars($user['fullname']) : htmlspecialchars($user['username']);
                    $is_active = $selected_user === $user['username'];
                    $initial = strtoupper(substr($display_name, 0, 1));
                ?>
                <a href="chat.php?user=<?= urlencode($user['username']) ?>" class="user-card <?= $is_active ? 'active' : '' ?>">
                    <div class="avatar-circle"><?= $initial ?></div>
                    <div class="user-info">
                        <h4><?= $display_name ?></h4>
                        <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <main class="glass-panel chat-window">
        <?php if (!$selected_user): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-chat-square-text-fill"></i></div>
                <h2 style="font-weight: 700; font-size: 2rem;">Pick a conversation</h2>
                <p style="opacity: 0.8; font-size: 1.1rem;">Select a user from the left to start chatting!</p>
            </div>
        <?php elseif (!$selected_user_info): ?>
            <div class="empty-state"><h2>User not found</h2></div>
        <?php else: 
             $selected_display_name = !empty($selected_user_info['fullname']) ? htmlspecialchars($selected_user_info['fullname']) : htmlspecialchars($selected_user_info['username']);
        ?>
            <div class="chat-header">
                <div class="avatar-circle" style="width: 40px; height: 40px; font-size: 1rem; background: var(--accent-gradient);">
                    <?= strtoupper(substr($selected_display_name, 0, 1)) ?>
                </div>
                <div>
                    <h2><?= $selected_display_name ?></h2>
                    <div style="display:flex; align-items:center; gap:6px; font-size: 0.8rem; color: #555;"><span class="status-dot"></span> Online</div>
                </div>
            </div>

            <div class="messages-box" id="messagesBox">
                <div style="text-align: center; color: #555; padding-top: 50px;">
                    <div class="spinner-border" role="status" style="width: 3rem; height: 3rem; color: #fff;"></div>
                </div>
            </div>

            <form class="input-area" id="chatForm">
                <input type="hidden" name="sender" id="senderInput" value="<?= htmlspecialchars($current_user ?? '') ?>">
                <input type="hidden" name="receiver" id="receiverInput" value="<?= htmlspecialchars($selected_user ?? '') ?>">
                <div class="input-wrapper">
                    <i class="bi bi-emoji-smile" style="font-size: 1.2rem; color: #f093fb; cursor: pointer;"></i>
                    <input type="text" name="message" id="messageInput" placeholder="Type a colorful message..." autocomplete="off" required>
                    <button type="submit" id="sendButton" class="btn-send"><i class="bi bi-send-fill"></i></button>
                </div>
            </form>
        <?php endif; ?>
    </main>

</div>

<script>
  const messagesBox = document.getElementById('messagesBox');
  const chatForm = document.getElementById('chatForm');
  const messageInput = document.getElementById('messageInput');
  const sendButton = document.getElementById('sendButton');

  <?php if ($selected_user): ?>
    loadMessages();
    setInterval(loadMessages, 2000);

    if (chatForm) {
      chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = messageInput.value.trim();
        if (!message) return;
        sendButton.style.transform = "scale(0.9)";
        setTimeout(() => sendButton.style.transform = "scale(1)", 150);
        sendButton.disabled = true;
        const formData = new FormData();
        formData.append('sender', document.getElementById('senderInput').value);
        formData.append('receiver', document.getElementById('receiverInput').value);
        formData.append('message', message);
        fetch('submit_messages.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
          if (data.success) { messageInput.value = ''; messageInput.focus(); loadMessages(); } else { alert('Error: ' + data.error); }
        })
        .catch(err => console.error(err))
        .finally(() => { sendButton.disabled = false; });
      });
    }

    function loadMessages() {
      const formData = new FormData();
      formData.append('sender', '<?= htmlspecialchars($current_user) ?>');
      formData.append('receiver', '<?= htmlspecialchars($selected_user) ?>');
      fetch('fetch_messages.php', { method: 'POST', body: formData })
      .then(response => response.text())
      .then(html => {
        const isScrolledToBottom = messagesBox.scrollHeight - messagesBox.clientHeight <= messagesBox.scrollTop + 50;
        messagesBox.innerHTML = html;
        if (messagesBox.innerHTML.trim() !== "" && (isScrolledToBottom || messagesBox.scrollTop === 0)) {
             messagesBox.scrollTop = messagesBox.scrollHeight;
        }
      })
      .catch(error => console.error(error));
    }
  <?php endif; ?>
</script>

</body>
</html>