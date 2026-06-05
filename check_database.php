<?php
require_once 'database.php';

echo "<h2>Database Structure Check</h2>";

try {
    // Check existing tables
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Existing Tables:</h3><pre>";
    print_r($tables);
    echo "</pre>";
    
    // Check if feedback table exists
    if (in_array('feedback', $tables)) {
        echo "<h3 style='color: green;'>✅ Feedback table exists</h3>";
        
        // Show feedback table structure
        $structure = $conn->query("DESCRIBE feedback")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Feedback Table Structure:</h4><pre>";
        print_r($structure);
        echo "</pre>";
        
        // Show feedback data
        $data = $conn->query("SELECT * FROM feedback LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Sample Feedback Data:</h4><pre>";
        print_r($data);
        echo "</pre>";
    } else {
        echo "<h3 style='color: red;'>❌ Feedback table does not exist</h3>";
    }
    
    // Check users table structure
    echo "<h3>Users Table Structure:</h3>";
    $usersStructure = $conn->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($usersStructure);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
}
?>
