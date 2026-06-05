<?php
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: feedback_form.php');
    exit;
}

// Get client ID from session
$client_id = $_SESSION['userid'] ?? null;
if (!$client_id) {
    $_SESSION['feedback_error'] = 'You must be logged in to submit feedback.';
    header('Location: login.php');
    exit;
}

// Validate and sanitize input
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
$freelancer_id = filter_input(INPUT_POST, 'freelancer_id', FILTER_VALIDATE_INT);
$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_SANITIZE_STRING);

// Validation
$errors = [];

if (!$rating || $rating < 1 || $rating > 5) {
    $errors[] = 'Please select a valid rating between 1 and 5.';
}

if (empty($comment)) {
    $errors[] = 'Comment is required.';
} elseif (strlen($comment) < 10) {
    $errors[] = 'Comment must be at least 10 characters long.';
} elseif (strlen($comment) > 1000) {
    $errors[] = 'Comment must not exceed 1000 characters.';
}

if (!$freelancer_id) {
    $errors[] = 'Freelancer ID is required.';
}

if (!$booking_id || $booking_id === '0' || $booking_id === '') {
    $errors[] = 'Booking ID is required and cannot be empty.';
}

// CRITICAL: Validate booking exists and belongs to this client
try {
    $bookingCheck = $conn->prepare("
        SELECT * FROM booking 
        WHERE booking_id = :booking_id 
        AND client_id = :client_id 
        AND freelancer_id = :freelancer_id
    ");
    $bookingCheck->execute([
        ':booking_id' => $booking_id, 
        ':client_id' => $client_id,
        ':freelancer_id' => $freelancer_id
    ]);
    $booking = $bookingCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        $errors[] = 'Invalid booking selected. This booking does not exist or does not belong to you.';
    } else {
        // Check if booking is completed
        if ($booking['booking_status'] !== 'Job Completed') {
            $errors[] = 'Feedback can only be given for completed bookings. Current status: ' . $booking['booking_status'];
        }
    }
    
} catch (PDOException $e) {
    $errors[] = 'Error validating booking: ' . $e->getMessage();
}

// CORRECT: Check if feedback already exists for this specific booking ONLY
// NOT client_id, NOT freelancer_id, ONLY booking_id
try {
    $existingFeedback = $conn->prepare("
        SELECT id FROM feedback 
        WHERE booking_id = :booking_id
    ");
    $existingFeedback->execute([':booking_id' => $booking_id]);
    
    if ($existingFeedback->fetch()) {
        $errors[] = 'Feedback for booking ' . $booking_id . ' already exists. Each booking can only have one feedback.';
    }
    
} catch (PDOException $e) {
    $errors[] = 'Error checking existing feedback: ' . $e->getMessage();
}

if (!empty($errors)) {
    $_SESSION['feedback_error'] = implode(' ', $errors);
    $redirectUrl = 'feedback_form.php?freelancer_id=' . $freelancer_id . '&booking_id=' . $booking_id;
    header('Location: ' . $redirectUrl);
    exit;
}

try {
    // Create feedback table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `feedback` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `booking_id` varchar(20) NOT NULL,
            `client_id` int(11) NOT NULL,
            `freelancer_id` int(11) NOT NULL,
            `rating` int(11) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
            `comment` text NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_booking_feedback` (`booking_id`),
            INDEX `idx_booking` (`booking_id`),
            INDEX `idx_client` (`client_id`),
            INDEX `idx_freelancer` (`freelancer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    $conn->exec($createTableSQL);

    // CRITICAL: Insert feedback with REAL booking_id
    $insertSQL = "
        INSERT INTO feedback (booking_id, client_id, freelancer_id, rating, comment) 
        VALUES (:booking_id, :client_id, :freelancer_id, :rating, :comment)
    ";
    $stmt = $conn->prepare($insertSQL);
    
    $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_STR);
    $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
    $stmt->bindParam(':freelancer_id', $freelancer_id, PDO::PARAM_INT);
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
    
    $stmt->execute();
    
    $_SESSION['feedback_success'] = 'Thank you for your feedback! We appreciate your input.';
    header('Location: client_booking_list.php');
    exit;
    
} catch (PDOException $e) {
    error_log('Feedback submission error: ' . $e->getMessage());
    
    // Check if it's a duplicate key error
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
        $_SESSION['feedback_error'] = 'Feedback for booking ' . $booking_id . ' already exists. Each booking can only have one feedback.';
    } else {
        $_SESSION['feedback_error'] = 'Database error: ' . $e->getMessage();
    }
    
    $redirectUrl = 'feedback_form.php?freelancer_id=' . $freelancer_id . '&booking_id=' . $booking_id;
    header('Location: ' . $redirectUrl);
    exit;
}
?>
