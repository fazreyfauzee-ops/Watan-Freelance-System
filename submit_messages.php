<?php
// Start output buffering to prevent any accidental output
ob_start();
session_start();
include_once 'database.php';

// Clear any output that might have been generated
ob_clean();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Debug: Log what we're receiving
    error_log("POST data received: " . print_r($_POST, true));
    error_log("Raw input: " . file_get_contents('php://input'));
    
    $sender = isset($_POST['sender']) ? trim($_POST['sender']) : '';
    $receiver = isset($_POST['receiver']) ? trim($_POST['receiver']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Debug: Log individual values
    error_log("Sender: '" . $sender . "' (length: " . strlen($sender) . ")");
    error_log("Receiver: '" . $receiver . "' (length: " . strlen($receiver) . ")");
    error_log("Message: '" . $message . "' (length: " . strlen($message) . ")");

    // Validate inputs
    if (empty($sender) || empty($receiver) || empty($message)) {
        http_response_code(400);
        ob_clean();
        echo json_encode([
            'error' => 'All fields are required',
            'debug' => [
                'sender' => $sender ?: 'empty',
                'receiver' => $receiver ?: 'empty',
                'message' => $message ?: 'empty',
                'post_keys' => array_keys($_POST)
            ]
        ]);
        exit();
    }

    // Verify sender matches logged-in user
    if ($sender !== $_SESSION['username']) {
        http_response_code(403);
        echo json_encode(['error' => 'Sender mismatch']);
        exit();
    }

    // Verify receiver exists
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :receiver");
        $stmt->bindParam(':receiver', $receiver);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Receiver not found']);
            exit();
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
        error_log("Error verifying receiver: " . $e->getMessage());
        exit();
    }

    // Store original message (don't HTML encode here - we'll do it when displaying)
    $message_clean = trim($message);

    // Insert message into database
    try {
        $sql = "INSERT INTO chat_messages (sender, receiver, message) 
                VALUES (:sender, :receiver, :message)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':sender', $sender, PDO::PARAM_STR);
        $stmt->bindParam(':receiver', $receiver, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message_clean, PDO::PARAM_STR);
        
        $result = $stmt->execute();
        
        if ($result) {
            ob_clean(); // Ensure clean output
            echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
            exit();
        } else {
            http_response_code(500);
            $errorInfo = $stmt->errorInfo();
            error_log("Failed to execute query: " . print_r($errorInfo, true));
            ob_clean();
            echo json_encode(['error' => 'Failed to send message', 'details' => $errorInfo[2] ?? 'Unknown error']);
            exit();
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Error inserting message: " . $e->getMessage());
        error_log("SQL: " . $sql);
        error_log("Sender: " . $sender . ", Receiver: " . $receiver);
        ob_clean();
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
