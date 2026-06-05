<?php 
session_start();
include_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    exit("Unauthorized");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $sender = isset($_POST['sender']) ? trim($_POST['sender']) : '';
    $receiver = isset($_POST['receiver']) ? trim($_POST['receiver']) : '';

    // Validate inputs
    if (empty($sender) || empty($receiver)) {
        echo '<div style="text-align: center; color: #999; padding: 20px;">Invalid request</div>';
        exit();
    }

    // Verify sender matches logged-in user
    if ($sender !== $_SESSION['username']) {
        echo '<div style="text-align: center; color: #999; padding: 20px;">Unauthorized</div>';
        exit();
    }

    try {
        // Fetch messages between sender and receiver
        $sql = "SELECT sender, receiver, message, created_at 
                FROM chat_messages 
                WHERE (sender = :sender AND receiver = :receiver) 
                   OR (sender = :receiver AND receiver = :sender)
                ORDER BY created_at ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':sender', $sender);
        $stmt->bindParam(':receiver', $receiver);
        $stmt->execute();
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($messages)) {
            echo '<div style="text-align: center; color: #999; padding: 40px;">
                    <i class="bi bi-chat-dots" style="font-size: 3rem; display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                    <p>No messages yet. Start the conversation!</p>
                  </div>';
            exit();
        }

        // Fetch user fullnames for display
        $user_names = [];
        try {
            $user_stmt = $conn->prepare("SELECT username, fullname FROM users WHERE username = :sender OR username = :receiver");
            $user_stmt->bindParam(':sender', $sender);
            $user_stmt->bindParam(':receiver', $receiver);
            $user_stmt->execute();
            $users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($users as $user) {
                $user_names[$user['username']] = !empty($user['fullname']) ? $user['fullname'] : $user['username'];
            }
        } catch (PDOException $e) {
            error_log("Error fetching user names: " . $e->getMessage());
            // Fallback to username
            $user_names[$sender] = $sender;
            $user_names[$receiver] = $receiver;
        }

        // Output messages
        foreach ($messages as $row) {
            $is_me = ($row['sender'] === $sender);
            $sender_display = isset($user_names[$row['sender']]) ? htmlspecialchars($user_names[$row['sender']]) : htmlspecialchars($row['sender']);
            
            // Format timestamp
            $timestamp = strtotime($row['created_at']);
            $time_display = date('H:i', $timestamp);
            $date_display = date('M d, Y', $timestamp);
            $is_today = (date('Y-m-d', $timestamp) === date('Y-m-d'));
            
            $time_label = $is_today ? $time_display : $date_display . ' ' . $time_display;
            
            echo '<div class="bubble ' . ($is_me ? 'me' : 'other') . '">';
            echo '<div>' . htmlspecialchars($row['message']) . '</div>';
            echo '<div class="message-time">' . htmlspecialchars($time_label) . '</div>';
            echo '</div>';
        }
        
    } catch (PDOException $e) {
        error_log("Error fetching messages: " . $e->getMessage());
        echo '<div style="text-align: center; color: #f44336; padding: 20px;">Error loading messages. Please refresh the page.</div>';
    }
} else {
    http_response_code(405);
    echo '<div style="text-align: center; color: #999; padding: 20px;">Method not allowed</div>';
}
?>
