<?php
require_once 'database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Feedback Process Debug</h2>";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<p>This script only handles POST requests from feedback forms.</p>";
    echo "<p><a href='feedback_form.php?freelancer_id=1&booking_id=BK20251208163025328'>Go to Feedback Form</a></p>";
    exit;
}

// Log everything
error_log("=== FEEDBACK SUBMISSION DEBUG ===");
error_log("POST Data: " . json_encode($_POST));
error_log("Session Data: " . json_encode($_SESSION));

// Get client ID from session
$client_id = $_SESSION['userid'] ?? null;
error_log("Client ID: " . $client_id);

if (!$client_id) {
    error_log("ERROR: Client not logged in");
    $_SESSION['feedback_error'] = 'You must be logged in to submit feedback.';
    header('Location: login.php');
    exit;
}

// Validate and sanitize input
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
$freelancer_id = filter_input(INPUT_POST, 'freelancer_id', FILTER_VALIDATE_INT);
$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_SANITIZE_STRING);

error_log("Form Data - Rating: $rating, Comment: " . substr($comment ?? '', 0, 50) . ", Freelancer: $freelancer_id, Booking: $booking_id");

// Validation
$errors = [];

if (!$rating || $rating < 1 || $rating > 5) {
    $errors[] = 'Please select a valid rating between 1 and 5.';
    error_log("ERROR: Invalid rating - $rating");
}

if (empty($comment)) {
    $errors[] = 'Comment is required.';
    error_log("ERROR: Empty comment");
} elseif (strlen($comment) < 10) {
    $errors[] = 'Comment must be at least 10 characters long.';
    error_log("ERROR: Comment too short - " . strlen($comment));
} elseif (strlen($comment) > 1000) {
    $errors[] = 'Comment must not exceed 1000 characters.';
    error_log("ERROR: Comment too long - " . strlen($comment));
}

if (!$freelancer_id) {
    $errors[] = 'Freelancer ID is required.';
    error_log("ERROR: Missing freelancer_id");
}

if (!$booking_id) {
    $errors[] = 'Booking ID is required.';
    error_log("ERROR: Missing booking_id");
}

if (!empty($errors)) {
    error_log("Validation errors: " . implode(', ', $errors));
    $_SESSION['feedback_error'] = implode(' ', $errors);
    $redirectUrl = 'feedback_form.php?freelancer_id=' . $freelancer_id;
    if ($booking_id) {
        $redirectUrl .= '&booking_id=' . $booking_id;
    }
    header('Location: ' . $redirectUrl);
    exit;
}

// Check if booking exists and belongs to this client
try {
    error_log("Checking booking: $booking_id for client: $client_id");
    
    $bookingCheck = $conn->prepare("SELECT * FROM booking WHERE booking_id = :booking_id AND client_id = :client_id");
    $bookingCheck->execute([':booking_id' => $booking_id, ':client_id' => $client_id]);
    $booking = $bookingCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        error_log("ERROR: Booking not found or doesn't belong to client");
        $_SESSION['feedback_error'] = 'Invalid booking selected.';
        header('Location: feedback_form.php?freelancer_id=' . $freelancer_id);
        exit;
    }
    
    error_log("Booking found: " . json_encode($booking));
    
    // Verify freelancer_id matches the booking
    if ($booking['freelancer_id'] != $freelancer_id) {
        error_log("ERROR: Freelancer ID mismatch - booking: {$booking['freelancer_id']}, form: $freelancer_id");
        $_SESSION['feedback_error'] = 'Freelancer ID does not match this booking.';
        header('Location: feedback_form.php?freelancer_id=' . $freelancer_id);
        exit;
    }
    
    // Check if booking is completed
    if ($booking['booking_status'] !== 'Job Completed') {
        error_log("ERROR: Booking not completed - status: {$booking['booking_status']}");
        $_SESSION['feedback_error'] = 'Feedback can only be given for completed bookings.';
        header('Location: feedback_form.php?freelancer_id=' . $freelancer_id);
        exit;
    }
    
} catch (PDOException $e) {
    error_log("ERROR: Booking validation failed - " . $e->getMessage());
    $_SESSION['feedback_error'] = 'Error validating booking.';
    header('Location: feedback_form.php?freelancer_id=' . $freelancer_id);
    exit;
}

// Check if feedback already exists for this booking
try {
    error_log("Checking existing feedback for booking: $booking_id");
    
    $existingFeedback = $conn->prepare("SELECT id FROM feedback WHERE booking_id = :booking_id");
    $existingFeedback->execute([':booking_id' => $booking_id]);
    
    if ($existingFeedback->fetch()) {
        error_log("ERROR: Feedback already exists for booking: $booking_id");
        $_SESSION['feedback_error'] = 'You have already submitted feedback for this booking.';
        header('Location: feedback_form.php?freelancer_id=' . $freelancer_id);
        exit;
    }
    
} catch (PDOException $e) {
    error_log("WARNING: Could not check existing feedback - " . $e->getMessage());
    // Continue anyway - table might not exist yet
}

// Try to insert feedback
try {
    error_log("Attempting to insert feedback...");
    
    // Ensure feedback table exists
    $conn->exec("CREATE TABLE IF NOT EXISTS `feedback` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `client_id` int(11) NOT NULL,
        `freelancer_id` int(11) NOT NULL,
        `booking_id` VARCHAR(20) NOT NULL,
        `rating` int(11) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
        `comment` text NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_booking` (`booking_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    
    error_log("Feedback table ensured to exist");
    
    // Check if booking_id column exists
    $columns = $conn->query("SHOW COLUMNS FROM feedback LIKE 'booking_id'")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($columns)) {
        error_log("Adding booking_id column...");
        $conn->exec("ALTER TABLE feedback ADD COLUMN booking_id VARCHAR(20) AFTER freelancer_id");
        error_log("booking_id column added");
    }
    
    // Insert feedback
    $insertSQL = "INSERT INTO feedback (client_id, freelancer_id, booking_id, rating, comment) VALUES (:client_id, :freelancer_id, :booking_id, :rating, :comment)";
    $stmt = $conn->prepare($insertSQL);
    
    $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
    $stmt->bindParam(':freelancer_id', $freelancer_id, PDO::PARAM_INT);
    $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_STR);
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
    
    error_log("Executing insert with parameters: client_id=$client_id, freelancer_id=$freelancer_id, booking_id=$booking_id, rating=$rating");
    
    $result = $stmt->execute();
    
    if ($result) {
        $insertedId = $conn->lastInsertId();
        error_log("SUCCESS: Feedback inserted with ID: $insertedId");
        
        $_SESSION['feedback_success'] = 'Thank you for your feedback! We appreciate your input.';
        header('Location: client_booking_list.php');
        exit;
    } else {
        error_log("ERROR: Insert failed - " . json_encode($stmt->errorInfo()));
        $_SESSION['feedback_error'] = 'Failed to insert feedback. Please try again.';
        header('Location: feedback_form.php?freelancer_id=' . $freelancer_id);
        exit;
    }
    
} catch (PDOException $e) {
    error_log("ERROR: Database insertion failed - " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $_SESSION['feedback_error'] = 'Database error: ' . $e->getMessage();
    $redirectUrl = 'feedback_form.php?freelancer_id=' . $freelancer_id;
    if ($booking_id) {
        $redirectUrl .= '&booking_id=' . $booking_id;
    }
    header('Location: ' . $redirectUrl);
    exit;
}
?>
