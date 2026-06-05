<?php
session_start();
include_once 'database.php'; 

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Get POST data
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$profile_picture_name = '';

// Handle profile picture upload
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $file_tmp_path = $_FILES['profile_picture']['tmp_name'];
    $file_name = $_FILES['profile_picture']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg','jpeg','png','gif'];

    if (in_array($file_ext, $allowed_ext)) {
        $profile_picture_name = uniqid('client_', true) . '.' . $file_ext;
        $dest_path = 'uploads/' . $profile_picture_name;

        if (!move_uploaded_file($file_tmp_path, $dest_path)) {
            die("Error: Failed to upload profile picture.");
        }
    } else {
        die("Error: Invalid file type.");
    }
}

// Fetch name and email from users table
try {
    $stmt = $conn->prepare("SELECT fullname AS name, email FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user_info) die("Error: User not found.");
} catch(PDOException $e){
    die("Error fetching user info: ".$e->getMessage());
}

$name = $user_info['name'];
$email = $user_info['email'];

// Check if client profile exists
try {
    $stmt = $conn->prepare("SELECT profile_picture FROM clients WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e){
    die("Error checking profile: ".$e->getMessage());
}

try {
    if ($existing) {
        // Update
        if ($profile_picture_name) {
            // Delete old picture if exists
            if (!empty($existing['profile_picture']) && file_exists('uploads/'.$existing['profile_picture'])) {
                unlink('uploads/'.$existing['profile_picture']);
            }
            $sql = "UPDATE clients SET name=:name, email=:email, bio=:bio, address=:address, profile_picture=:profile_picture WHERE user_id=:user_id";
        } else {
            $sql = "UPDATE clients SET name=:name, email=:email, bio=:bio, address=:address WHERE user_id=:user_id";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':address', $address);
        if ($profile_picture_name) $stmt->bindParam(':profile_picture', $profile_picture_name);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $_SESSION['success'] = "Profile updated successfully!";
    } else {
        // Insert
        $sql = "INSERT INTO clients (user_id, name, email, bio, address, profile_picture) VALUES (:user_id, :name, :email, :bio, :address, :profile_picture)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':profile_picture', $profile_picture_name);
        $stmt->execute();
        $_SESSION['success'] = "Profile created successfully!";
    }
    header("Location: dashboard.php");
    exit();
} catch(PDOException $e){
    die("Error saving client profile: ".$e->getMessage());
}
