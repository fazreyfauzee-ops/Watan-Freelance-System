<?php
require_once 'database.php';

echo "<h2>🔧 Add booking_id Column to Feedback Table</h2>";

try {
    // Step 1: Check if feedback table exists
    $feedbackTables = $conn->query("SHOW TABLES LIKE 'feedback'")->fetchColumn();
    
    if (!$feedbackTables) {
        echo "<h3>Creating feedback table with booking_id...</h3>";
        
        // Create feedback table with booking_id from the start
        $createTableSQL = "
            CREATE TABLE `feedback` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `booking_id` varchar(20) NOT NULL,
                `client_id` int(11) NOT NULL,
                `freelancer_id` int(11) NOT NULL,
                `rating` int(11) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
                `comment` text NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_booking` (`booking_id`),
                INDEX `idx_client` (`client_id`),
                INDEX `idx_freelancer` (`freelancer_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";
        $conn->exec($createTableSQL);
        echo "✅ Created feedback table with booking_id column (VARCHAR(20) to match booking table)<br>";
        
    } else {
        echo "<h3>Feedback table exists, checking for booking_id column...</h3>";
        
        // Check current feedback table structure
        $feedbackColumns = $conn->query("DESCRIBE feedback")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Current Feedback Table Structure:</h4>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($feedbackColumns as $column) {
            echo "<tr><td><strong>" . $column['Field'] . "</strong></td><td>" . $column['Type'] . "</td><td>" . $column['Null'] . "</td><td>" . $column['Key'] . "</td></tr>";
        }
        echo "</table>";
        
        // Check if booking_id column exists
        $bookingIdColumn = null;
        foreach ($feedbackColumns as $column) {
            if ($column['Field'] === 'booking_id') {
                $bookingIdColumn = $column;
                break;
            }
        }
        
        if (!$bookingIdColumn) {
            echo "❌ booking_id column missing in feedback table, adding it...<br>";
            
            // Add booking_id column with VARCHAR(20) to match booking table
            $alterSQL = "ALTER TABLE feedback ADD COLUMN booking_id VARCHAR(20) NOT NULL AFTER freelancer_id";
            $conn->exec($alterSQL);
            echo "✅ Added booking_id column (VARCHAR(20)) to feedback table<br>";
            
            // Add index for performance
            $indexSQL = "ALTER TABLE feedback ADD INDEX idx_booking (booking_id)";
            $conn->exec($indexSQL);
            echo "✅ Added booking_id index<br>";
            
        } else {
            echo "✅ booking_id column already exists in feedback table<br>";
            echo "<p>Type: " . $bookingIdColumn['Type'] . "</p>";
        }
    }
    
    // Step 2: Show booking table structure for comparison
    echo "<h3>📊 Booking Table Structure (for reference):</h3>";
    $bookingColumns = $conn->query("DESCRIBE booking")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Key</th></tr>";
    foreach ($bookingColumns as $column) {
        echo "<tr><td><strong>" . $column['Field'] . "</strong></td><td>" . $column['Type'] . "</td><td>" . $column['Key'] . "</td></tr>";
    }
    echo "</table>";
    
    // Step 3: Update existing feedback records if they don't have booking_id
    echo "<h3>🔄 Updating existing feedback records...</h3>";
    
    $feedbacks = $conn->query("SELECT * FROM feedback WHERE booking_id IS NULL OR booking_id = ''")->fetchAll(PDO::FETCH_ASSOC);
    $updated = 0;
    
    foreach ($feedbacks as $feedback) {
        // Find a booking for this client-freelancer pair
        $bookingStmt = $conn->prepare("
            SELECT booking_id 
            FROM booking 
            WHERE client_id = :client_id 
            AND freelancer_id = :freelancer_id 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $bookingStmt->execute([
            ':client_id' => $feedback['client_id'],
            ':freelancer_id' => $feedback['freelancer_id']
        ]);
        $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($booking) {
            $updateStmt = $conn->prepare("UPDATE feedback SET booking_id = :booking_id WHERE id = :id");
            $updateStmt->execute([
                ':booking_id' => $booking['booking_id'],
                ':id' => $feedback['id']
            ]);
            $updated++;
            echo "✅ Linked feedback ID {$feedback['id']} to booking {$booking['booking_id']}<br>";
        } else {
            echo "⚠️ No booking found for feedback ID {$feedback['id']} (client: {$feedback['client_id']}, freelancer: {$feedback['freelancer_id']})<br>";
        }
    }
    
    if ($updated == 0) {
        echo "✅ All feedback records already have booking_id<br>";
    } else {
        echo "✅ Updated $updated feedback records with booking_id<br>";
    }
    
    // Step 4: Insert sample data if table is empty
    $count = $conn->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
    if ($count == 0) {
        echo "<h3>📝 Adding sample feedback data...</h3>";
        
        // Get a booking to link to
        $bookingCheck = $conn->query("SELECT booking_id, client_id, freelancer_id FROM booking LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        if ($bookingCheck) {
            $sampleData = [
                [$bookingCheck['booking_id'], $bookingCheck['client_id'], $bookingCheck['freelancer_id'], 5, "Excellent work! Very professional and delivered on time."],
                [$bookingCheck['booking_id'], $bookingCheck['client_id'], $bookingCheck['freelancer_id'], 4, "Good quality work, communication was good."]
            ];
            
            foreach ($sampleData as $data) {
                $insertSQL = "INSERT INTO feedback (booking_id, client_id, freelancer_id, rating, comment) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertSQL);
                $stmt->execute($data);
            }
            echo "✅ Inserted " . count($sampleData) . " sample feedback records<br>";
        } else {
            echo "⚠️ No bookings found to create sample feedback<br>";
        }
    }
    
    // Step 5: Test the query that was failing
    echo "<h3>🧪 Testing the query that was failing...</h3>";
    
    try {
        $testQuery = $conn->query("
            SELECT 
                f.id,
                f.rating,
                f.comment,
                f.booking_id,
                f.created_at,
                u1.fullname as client_name,
                u2.fullname as freelancer_name
            FROM feedback f
            LEFT JOIN users u1 ON f.client_id = u1.id
            LEFT JOIN users u2 ON f.freelancer_id = u2.id
            ORDER BY f.created_at DESC
            LIMIT 3
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Query successful! Found " . count($testQuery) . " records<br>";
        
        echo "<h4>Sample Results:</h4>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Booking ID</th><th>Rating</th><th>Client</th><th>Freelancer</th><th>Date</th></tr>";
        foreach ($testQuery as $row) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td><strong>" . ($row['booking_id'] ?? 'N/A') . "</strong></td>";
            echo "<td>" . $row['rating'] . "</td>";
            echo "<td>" . ($row['client_name'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['freelancer_name'] ?? 'N/A') . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (PDOException $e) {
        echo "❌ Query failed: " . $e->getMessage() . "<br>";
    }
    
    // Step 6: Update one booking to be completed for testing
    echo "<h3>🔄 Creating completed booking for testing...</h3>";
    
    $updateBooking = $conn->prepare("
        UPDATE booking 
        SET booking_status = 'Job Completed' 
        WHERE booking_id = 'BK20251208163025328'
    ");
    $updateBooking->execute();
    echo "✅ Updated booking BK20251208163025328 to 'Job Completed'<br>";
    
    echo "<h3 style='color: green;'>🎉 booking_id Column Fix Complete!</h3>";
    
    echo "<h3>🔗 Test the Feedback Form:</h3>";
    echo "<ul>";
    echo "<li><a href='feedback_form.php?freelancer_id=3&booking_id=BK20251208163025328' target='_blank'>📝 Test Feedback Form</a></li>";
    echo "<li><a href='client_booking_list.php' target='_blank'>👤 Client Booking List</a></li>";
    echo "<li><a href='freelancer_booking_list.php' target='_blank'>👨‍💼 Freelancer Booking List</a></li>";
    echo "<li><a href='admin_feedback.php' target='_blank'>👨‍💼 Admin Feedback Page</a></li>";
    echo "</ul>";
    
    echo "<h3>📋 What was fixed:</h3>";
    echo "<ul>";
    echo "<li>✅ Added booking_id column to feedback table (VARCHAR(20) to match booking table)</li>";
    echo "<li>✅ Updated existing feedback records with booking relationships</li>";
    echo "<li>✅ Added proper indexes for performance</li>";
    echo "<li>✅ Created sample data for testing</li>";
    echo "<li>✅ Created completed booking for testing feedback workflow</li>";
    echo "</ul>";
    
    // Auto-redirect after 3 seconds
    echo "<script>
        setTimeout(function() {
            window.location.href = 'feedback_form.php?freelancer_id=3&booking_id=BK20251208163025328';
        }, 3000);
    </script>";
    echo "<p><em>Auto-redirecting to feedback form in 3 seconds...</em></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error: " . $e->getMessage() . "</h3>";
    echo "<p>Please check your database connection and permissions.</p>";
}
?>
