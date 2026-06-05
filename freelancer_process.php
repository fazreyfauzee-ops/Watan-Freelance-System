asal


<?php
session_start();
include_once 'database.php';

// Configuration
$upload_dir = 'uploads/';
$allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
$allowed_qr_types = array_merge($allowed_image_types, ['application/pdf']);
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB limit

// Reusable redirect with session message
function redirectWithStatus($type, $message) {
    // On success send users to the shared dashboard (role-aware UI there).
    // On error, send them back to the referring form page.
    $target_page = ($type === 'error') ? $_SERVER['HTTP_REFERER'] : 'dashboard.php';
    $_SESSION[$type] = $message;
    header("Location: " . $target_page);
    exit();
}

/**
 * Handles file upload logic and returns the new filename or null.
 * @param string $fileKey The key in the $_FILES array.
 * @param array $allowedTypes Array of allowed MIME types.
 * @param string $filenamePrefix Prefix for the unique filename.
 * @return string|null The new filename or null on failure/no file.
 */
function handleFileUpload($fileKey, $allowedTypes, $filenamePrefix = '') {
    global $upload_dir;

    if (empty($_FILES[$fileKey]['name'])) {
        return null; // No file uploaded
    }

    $file = $_FILES[$fileKey];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        redirectWithStatus('error', "File upload error for {$fileKey}: Code {$file['error']}");
    }

    // 1. Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        redirectWithStatus('error', "The uploaded file '{$fileKey}' is too large. Max size is 5MB.");
    }

    // 2. Check file type (MIME type)
    if (!in_array($file['type'], $allowedTypes)) {
        redirectWithStatus('error', "Invalid file format for {$fileKey}.");
    }

    // 3. Check directory
    if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
        redirectWithStatus('error', "Upload folder is missing or not writable.");
    }

    // 4. Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid($filenamePrefix, true) . "." . $extension;
    $target_file = $upload_dir . $filename;

    // 5. Move file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $filename;
    } else {
        redirectWithStatus('error', "Failed to save the uploaded file for {$fileKey}.");
    }
}

/**
 * Deletes a file from the server if it exists.
 * @param string|null $filename The filename to delete.
 */
function deleteOldFile($filename) {
    global $upload_dir;
    if ($filename && file_exists($upload_dir . $filename)) {
        unlink($upload_dir . $filename);
    }
}


try {
    // Debug logging
    error_log("POST data received: " . print_r($_POST, true));
    error_log("Files data: " . print_r($_FILES, true));
    
    if (isset($_POST['create']) || isset($_POST['update'])) {
        $isUpdate = isset($_POST['update']);
        
        error_log("Mode: " . ($isUpdate ? 'UPDATE' : 'CREATE'));
        
        // --- Input Sanitation and Validation ---
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
        
        error_log("User ID from form: " . $user_id);
        error_log("Session ID: " . ($_SESSION['id'] ?? 'not set'));
        
        // Ensure user is logged in and matches the user_id
        if (!isset($_SESSION['id']) || $_SESSION['id'] != $user_id) {
            redirectWithStatus('error', "Unauthorized access. Please log in.");
        }
        
        // Get raw values first, then sanitize
        $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
        $skills = isset($_POST['skills']) ? trim($_POST['skills']) : '';
        $availability = isset($_POST['availability']) ? trim($_POST['availability']) : '';
        
        // Sanitize for database storage
        $bio = filter_var($bio, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
        $skills = filter_var($skills, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
        $availability = filter_var($availability, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);

        error_log("Form values - Bio: " . substr($bio ?? '', 0, 50) . ", Skills: " . $skills . ", Availability: " . $availability);

        // Required field validation
        if (empty($availability)) {
            redirectWithStatus('error', "Please select your availability.");
        }

        // Fetch user info from users table for backward compatibility (name/email in freelancers table)
        $stmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user_info) {
            redirectWithStatus('error', "User not found.");
        }
        
        $name = $user_info['fullname'];
        $email = $user_info['email'];

        // --- Handle Existing Data (for Update) ---
        $existing_data = ['profile_picture' => null, 'qr_code' => null];
        if ($isUpdate) {
            $stmt = $conn->prepare("SELECT profile_picture, qr_code FROM freelancers WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $existing_data = $stmt->fetch(PDO::FETCH_ASSOC);
            // If profile doesn't exist, treat it as create instead
            if (!$existing_data) {
                $isUpdate = false;
                $existing_data = ['profile_picture' => null, 'qr_code' => null];
            } else {
                // Ensure null values are handled
                if (!isset($existing_data['profile_picture'])) {
                    $existing_data['profile_picture'] = null;
                }
                if (!isset($existing_data['qr_code'])) {
                    $existing_data['qr_code'] = null;
                }
            }
        }

        // --- File Uploads ---
        $new_profile_picture = handleFileUpload('profile_picture', $allowed_image_types, 'pfp_');
        $new_qr_code = handleFileUpload('qr_code', $allowed_qr_types, 'qr_');

        // Determine final filenames, and delete old files if a new one was uploaded
        $profile_picture = $existing_data['profile_picture'] ?? null;
        if ($new_profile_picture) {
            // Only delete old file if it exists
            if (!empty($existing_data['profile_picture'])) {
                deleteOldFile($existing_data['profile_picture']);
            }
            $profile_picture = $new_profile_picture;
        }

        $qr_code = $existing_data['qr_code'] ?? null;
        if ($new_qr_code) {
            // Only delete old file if it exists
            if (!empty($existing_data['qr_code'])) {
                deleteOldFile($existing_data['qr_code']);
            }
            $qr_code = $new_qr_code;
        }

        // --- Database Operation ---
        if (!$isUpdate) {
            // INSERT Operation - Only insert freelancer-specific fields
            // Note: name and email are kept for backward compatibility but come from users table
            $sql = "INSERT INTO freelancers (user_id, name, email, bio, skills, availability, profile_picture, qr_code) 
                    VALUES (:user_id, :name, :email, :bio, :skills, :availability, :profile_picture, :qr_code)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $user_id);
            
            $message = 'Profile created successfully';
            
        } else {
            // UPDATE Operation - Only update freelancer-specific fields
            // Note: name and email are updated from users table for backward compatibility
            $sql = "UPDATE freelancers SET 
                    name = :name, email = :email, bio = :bio, skills = :skills, availability = :availability, 
                    profile_picture = :profile_picture, qr_code = :qr_code
                    WHERE user_id = :user_id";
            $stmt = $conn->prepare($sql);
            
            $message = 'Profile updated successfully';
        }

        // Bind common parameters
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':bio', $bio, PDO::PARAM_STR);
        $stmt->bindParam(':skills', $skills, PDO::PARAM_STR);
        $stmt->bindParam(':availability', $availability, PDO::PARAM_STR);
        $stmt->bindParam(':profile_picture', $profile_picture, PDO::PARAM_STR);
        $stmt->bindParam(':qr_code', $qr_code, PDO::PARAM_STR);

        error_log("Executing SQL: " . $sql);
        error_log("Parameters - user_id: $user_id, name: $name, email: $email, bio: " . substr($bio ?? '', 0, 30) . ", skills: $skills, availability: $availability");
        
        $result = $stmt->execute();
        
        if ($result) {
            error_log("Query executed successfully. Rows affected: " . $stmt->rowCount());
            redirectWithStatus('success', $message);
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Failed to execute query: " . print_r($errorInfo, true));
            redirectWithStatus('error', "Failed to save profile. Error: " . ($errorInfo[2] ?? 'Unknown error'));
        }

    } else {
        redirectWithStatus('error', "Invalid request: Form submission method not recognized.");
    }

} catch (PDOException $e) {
    // Log the error for developer inspection
    error_log("Database error: " . $e->getMessage()); 
    redirectWithStatus('error', "An unexpected database error occurred. Please try again later.");
}
?>