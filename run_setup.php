<?php
require_once 'database.php';

echo "<h2>🔧 Running Feedback System Setup</h2>";

try {
    // Step 1: Drop existing feedback table if it exists
    $conn->exec("DROP TABLE IF EXISTS feedback");
    echo "✅ Cleared any existing feedback table<br>";
    
    // Step 2: Create feedback table
    $createTableSQL = "
        CREATE TABLE `feedback` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `client_id` int(11) NOT NULL,
            `freelancer_id` int(11) NOT NULL,
            `rating` int(11) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
            `comment` text NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_freelancer` (`freelancer_id`),
            INDEX `idx_client` (`client_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    $conn->exec($createTableSQL);
    echo "✅ Created feedback table successfully<br>";
    
    // Step 3: Insert sample feedback data
    $sampleFeedback = [
        [2, 1, 5, "Excellent work! Very professional and delivered on time. Highly recommended!"],
        [2, 3, 4, "Good quality work, communication could be better but overall satisfied."],
        [1, 1, 5, "Amazing freelancer! Exceeded expectations. Will definitely hire again."],
        [2, 1, 4, "Great work on the project. Met all requirements on time."]
    ];
    
    foreach ($sampleFeedback as $feedback) {
        $insertSQL = "INSERT INTO feedback (client_id, freelancer_id, rating, comment) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSQL);
        $stmt->execute($feedback);
    }
    echo "✅ Inserted " . count($sampleFeedback) . " sample feedback records<br>";
    
    // Step 4: Verify setup
    $count = $conn->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
    echo "✅ Total feedback records: {$count}<br>";
    
    // Step 5: Test queries
    echo "<h3>Testing System Components:</h3>";
    
    // Test freelancer feedback query
    $freelancerFeedback = $conn->query("
        SELECT f.rating, f.comment, f.created_at, u.fullname as client_name 
        FROM feedback f 
        LEFT JOIN users u ON f.client_id = u.id 
        WHERE f.freelancer_id = 1 
        ORDER BY f.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Freelancer 1 feedback: " . count($freelancerFeedback) . " records<br>";
    
    // Test admin dashboard query
    $allFeedback = $conn->query("
        SELECT f.*, u1.fullname as client_name, u2.fullname as freelancer_name 
        FROM feedback f 
        LEFT JOIN users u1 ON f.client_id = u1.id 
        LEFT JOIN users u2 ON f.freelancer_id = u2.id 
        ORDER BY f.created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Admin dashboard query: " . count($allFeedback) . " records<br>";
    
    echo "<h3 style='color: green;'>🎉 Setup Complete!</h3>";
    echo "<p><strong>System is now ready for testing:</strong></p>";
    echo "<ul>";
    echo "<li><a href='admin_dashboard.php' target='_blank'>👨‍💼 Admin Dashboard</a></li>";
    echo "<li><a href='admin_feedback.php' target='_blank'>📊 Admin Feedback Page</a></li>";
    echo "<li><a href='freelancer_details.php?id=1' target='_blank'>👤 Freelancer Profile</a></li>";
    echo "<li><a href='feedback_form.php?freelancer_id=1' target='_blank'>📝 Feedback Form</a></li>";
    echo "<li><a href='view_deliverable.php?bid=BK20251208163025328' target='_blank'>📁 Deliverable View</a></li>";
    echo "</ul>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Log in as client: <strong>dania123</strong> / <strong>123456</strong></li>";
    echo "<li>Navigate to a completed job deliverable</li>";
    echo "<li>Click 'Rate Freelancer' to submit feedback</li>";
    echo "<li>Check freelancer profile and admin dashboard to see feedback</li>";
    echo "</ol>";
    
    // Auto-redirect after 5 seconds
    echo "<script>
        setTimeout(function() {
            window.location.href = 'admin_dashboard.php';
        }, 5000);
    </script>";
    echo "<p><em>Redirecting to admin dashboard in 5 seconds...</em></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Setup Error: " . $e->getMessage() . "</h3>";
    echo "<p>Please check your database connection and permissions.</p>";
    
    // Show database info for debugging
    echo "<h3>Database Debug Info:</h3>";
    echo "<pre>";
    echo "Server: " . $conn->getAttribute(PDO::ATTR_SERVER_INFO) . "\n";
    echo "Database: " . $conn->getAttribute(PDO::ATTR_DB_NAME) . "\n";
    echo "</pre>";
}
?>
