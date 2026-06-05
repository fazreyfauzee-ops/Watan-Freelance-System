<?php
require_once 'database.php';

echo "<h2>Creating Feedback Database Table</h2>";

try {
    // Create feedback table
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `feedback` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `client_id` int(11) NOT NULL,
            `freelancer_id` int(11) NOT NULL,
            `rating` int(11) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
            `comment` text NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    $conn->exec($createTableSQL);
    echo "✅ Feedback table created successfully!<br>";
    
    // Insert sample data
    $sampleData = [
        [2, 1, 5, "Excellent work! Very professional and delivered on time."],
        [2, 3, 4, "Good quality work, communication could be better."],
        [1, 1, 5, "Amazing freelancer! Highly recommended."]
    ];
    
    foreach ($sampleData as $data) {
        $stmt = $conn->prepare("INSERT INTO feedback (client_id, freelancer_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute($data);
    }
    echo "✅ Sample feedback data inserted!<br>";
    
    // Verify
    $count = $conn->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
    echo "✅ Total feedback records: {$count}<br>";
    
    echo "<h3 style='color: green;'>Setup Complete!</h3>";
    echo "<p><a href='admin_dashboard.php'>Test Admin Dashboard</a></p>";
    echo "<p><a href='freelancer_details.php?id=1'>Test Freelancer Profile</a></p>";
    echo "<p><a href='feedback_form.php?freelancer_id=1'>Test Feedback Form</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
}
?>
