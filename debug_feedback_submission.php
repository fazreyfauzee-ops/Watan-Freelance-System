<?php
session_start();
require_once 'database.php';

echo "<h2>🔍 Feedback Submission Debug</h2>";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>📤 Form Submission Detected</h3>";
    echo "<pre>";
    echo "POST Data:\n";
    print_r($_POST);
    echo "\n\nSession Data:\n";
    print_r($_SESSION);
    echo "</pre>";
    
    // Get form data
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $freelancer_id = filter_input(INPUT_POST, 'freelancer_id', FILTER_VALIDATE_INT);
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_SANITIZE_STRING);
    
    echo "<h3>📋 Form Data Analysis</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Value</th><th>Status</th></tr>";
    echo "<tr><td>rating</td><td>" . ($rating ?? 'NULL') . "</td><td>" . ($rating ? '✅ Valid' : '❌ Invalid') . "</td></tr>";
    echo "<tr><td>comment</td><td>" . ($comment ? substr($comment, 0, 50) . '...' : 'NULL') . "</td><td>" . ($comment ? '✅ Valid' : '❌ Invalid') . "</td></tr>";
    echo "<tr><td>freelancer_id</td><td>" . ($freelancer_id ?? 'NULL') . "</td><td>" . ($freelancer_id ? '✅ Valid' : '❌ Invalid') . "</td></tr>";
    echo "<tr><td>booking_id</td><td>" . ($booking_id ?? 'NULL') . "</td><td>" . ($booking_id ? '✅ Valid' : '❌ Invalid') . "</td></tr>";
    echo "</table>";
    
    // Check session
    $client_id = $_SESSION['userid'] ?? null;
    echo "<h3>👤 Session Check</h3>";
    echo "<p><strong>Client ID:</strong> " . ($client_id ? $client_id : '❌ NOT LOGGED IN') . "</p>";
    echo "<p><strong>Username:</strong> " . ($_SESSION['username'] ?? 'NOT SET') . "</p>";
    echo "<p><strong>Role:</strong> " . ($_SESSION['role'] ?? 'NOT SET') . "</p>";
    
    if (!$client_id) {
        echo "<p style='color: red;'>❌ ERROR: User not logged in!</p>";
        exit;
    }
    
    // Validate booking
    echo "<h3>📋 Booking Validation</h3>";
    try {
        $bookingCheck = $conn->prepare("SELECT * FROM booking WHERE booking_id = :booking_id AND client_id = :client_id");
        $bookingCheck->execute([':booking_id' => $booking_id, ':client_id' => $client_id]);
        $booking = $bookingCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            echo "<p style='color: red;'>❌ ERROR: Booking not found or doesn't belong to user!</p>";
            echo "<p>Looking for booking_id: " . $booking_id . "</p>";
            echo "<p>Client ID: " . $client_id . "</p>";
            
            // Show user's bookings
            $userBookings = $conn->prepare("SELECT booking_id, service_title, booking_status FROM booking WHERE client_id = :client_id");
            $userBookings->execute([':client_id' => $client_id]);
            $bookings = $userBookings->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>Your Bookings:</h4>";
            echo "<ul>";
            foreach ($bookings as $b) {
                echo "<li>" . $b['booking_id'] . " - " . $b['service_title'] . " (" . $b['booking_status'] . ")</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>✅ Booking found: " . $booking['service_title'] . "</p>";
            echo "<p>Status: " . $booking['booking_status'] . "</p>";
            
            if ($booking['booking_status'] !== 'Job Completed') {
                echo "<p style='color: orange;'>⚠️ WARNING: Booking not completed!</p>";
            }
            
            // Check freelancer match
            if ($booking['freelancer_id'] != $freelancer_id) {
                echo "<p style='color: red;'>❌ ERROR: Freelancer ID mismatch!</p>";
                echo "<p>Booking freelancer: " . $booking['freelancer_id'] . "</p>";
                echo "<p>Form freelancer: " . $freelancer_id . "</p>";
            } else {
                echo "<p>✅ Freelancer ID matches</p>";
            }
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    }
    
    // Check existing feedback
    echo "<h3>🔍 Existing Feedback Check</h3>";
    try {
        $existingFeedback = $conn->prepare("SELECT * FROM feedback WHERE booking_id = :booking_id");
        $existingFeedback->execute([':booking_id' => $booking_id]);
        $feedback = $existingFeedback->fetch(PDO::FETCH_ASSOC);
        
        if ($feedback) {
            echo "<p style='color: orange;'>⚠️ Feedback already exists for this booking!</p>";
            echo "<p>Rating: " . $feedback['rating'] . "/5</p>";
            echo "<p>Comment: " . $feedback['comment'] . "</p>";
            echo "<p>Created: " . $feedback['created_at'] . "</p>";
        } else {
            echo "<p>✅ No existing feedback for this booking</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error checking feedback: " . $e->getMessage() . "</p>";
    }
    
    // Try to insert feedback
    echo "<h3>🧪 Feedback Insertion Test</h3>";
    try {
        // Check if feedback table exists and has correct structure
        $tableCheck = $conn->query("SHOW TABLES LIKE 'feedback'")->fetchColumn();
        if (!$tableCheck) {
            echo "<p style='color: red;'>❌ Feedback table doesn't exist!</p>";
            
            // Create table
            $createTableSQL = "
                CREATE TABLE `feedback` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `client_id` int(11) NOT NULL,
                    `freelancer_id` int(11) NOT NULL,
                    `booking_id` VARCHAR(20) NOT NULL,
                    `rating` int(11) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
                    `comment` text NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    INDEX `idx_booking` (`booking_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ";
            $conn->exec($createTableSQL);
            echo "<p>✅ Created feedback table</p>";
        }
        
        // Check if booking_id column exists
        $columnCheck = $conn->query("SHOW COLUMNS FROM feedback LIKE 'booking_id'")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($columnCheck)) {
            echo "<p style='color: red;'>❌ booking_id column missing!</p>";
            
            // Add column
            $alterSQL = "ALTER TABLE feedback ADD COLUMN booking_id VARCHAR(20) AFTER freelancer_id";
            $conn->exec($alterSQL);
            echo "<p>✅ Added booking_id column</p>";
        }
        
        // Try insertion
        $insertSQL = "INSERT INTO feedback (client_id, freelancer_id, booking_id, rating, comment) VALUES (:client_id, :freelancer_id, :booking_id, :rating, :comment)";
        $stmt = $conn->prepare($insertSQL);
        
        $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        $stmt->bindParam(':freelancer_id', $freelancer_id, PDO::PARAM_INT);
        $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_STR);
        $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
        $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
        
        $result = $stmt->execute();
        
        if ($result) {
            echo "<p style='color: green;'>✅ Feedback inserted successfully!</p>";
            echo "<p>Inserted ID: " . $conn->lastInsertId() . "</p>";
            
            // Verify insertion
            $verifyStmt = $conn->prepare("SELECT * FROM feedback WHERE id = :id");
            $verifyStmt->execute([':id' => $conn->lastInsertId()]);
            $inserted = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h4>Inserted Feedback:</h4>";
            echo "<pre>";
            print_r($inserted);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Insert failed!</p>";
            echo "<p>Error info: ";
            print_r($stmt->errorInfo());
            echo "</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Insertion error: " . $e->getMessage() . "</p>";
        echo "<p>SQL State: " . $e->getCode() . "</p>";
    }
    
} else {
    echo "<h3>📋 Usage</h3>";
    echo "<p>This script debugs feedback submission issues.</p>";
    echo "<p>Submit the feedback form first, then check this page for detailed debugging information.</p>";
    
    echo "<h3>🔗 Quick Links</h3>";
    echo "<ul>";
    echo "<li><a href='feedback_form.php?freelancer_id=1&booking_id=BK20251208163025328' target='_blank'>📝 Test Feedback Form</a></li>";
    echo "<li><a href='emergency_feedback_fix.php' target='_blank'>🔧 Run Emergency Fix</a></li>";
    echo "<li><a href='test_feedback_form.php' target='_blank'>🧪 Test Form Status</a></li>";
    echo "</ul>";
}
?>
