<?php
session_start();
require_once "database.php";

// --------------------
// INITIAL VARIABLES & LOGIC
// --------------------
$bid = $_GET['bid'] ?? $_POST['bid'] ?? null;
$isReupload = isset($_GET['reupload']) || isset($_POST['reupload']);
$description = $_POST['description'] ?? '';

if (!$bid) {
    echo "<script>alert('Missing booking ID.'); window.location.href='freelancer_booking_list.php';</script>";
    exit;
}

// Fetch booking info
try {
    $stmt = $conn->prepare("SELECT * FROM booking WHERE booking_id = :bid");
    $stmt->execute([':bid' => $bid]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo "<script>alert('Booking not found.'); window.location.href='freelancer_booking_list.php';</script>";
        exit;
    }
} catch (PDOException $e) {
    echo "<script>alert('Error fetching booking: " . htmlspecialchars($e->getMessage()) . "'); window.location.href='freelancer_booking_list.php';</script>";
    exit;
}

$message = "";
$success = false;

// --------------------
// UPLOAD HANDLER
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_deliverable'])) {
    // Check if file was uploaded
    if (!isset($_FILES['deliverable']) || $_FILES['deliverable']['error'] === UPLOAD_ERR_NO_FILE) {
        $message = "Please make sure to submit your deliverables or job proof here";
        $success = false;
    } elseif ($_FILES['deliverable']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $original = $_FILES['deliverable']['name'];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowedTypes = ["png", "jpg", "jpeg", "pdf", "doc", "docx", "zip", "rar"];

        if (!in_array($ext, $allowedTypes)) {
            $message = "Invalid file type. Allowed: PNG, JPG, JPEG, PDF, DOC, DOCX, ZIP, RAR";
        } elseif ($_FILES['deliverable']['size'] > 10 * 1024 * 1024) {
            $message = "File too large. Maximum size is 10MB.";
        } else {
            $sanitizedBid = preg_replace("/[^A-Za-z0-9_-]/", "_", $bid);
            $targetName = "deliverable_" . $sanitizedBid . "_" . time() . ($ext ? "." . $ext : "");
            $targetPath = $uploadDir . "/" . $targetName;

            if (move_uploaded_file($_FILES['deliverable']['tmp_name'], $targetPath)) {
                $relativePath = "uploads/" . $targetName;

                try {
                    $conn->beginTransaction();

                    $stmt = $conn->prepare("
                        UPDATE booking
                        SET deliverables = :deliverable_path,
                            booking_status = 'Job Pending Verification'
                        WHERE booking_id = :bid
                    ");
                    $stmt->bindParam(':deliverable_path', $relativePath);
                    $stmt->bindParam(':bid', $bid);
                    $stmt->execute();

                    if ($stmt->rowCount() === 0) throw new Exception("Booking not found or no rows updated.");

                    $conn->commit();
                    $success = true;
                    $message = $isReupload ? "Deliverable re-uploaded successfully!" : "Deliverable uploaded successfully!";
                    echo "<script>alert('" . addslashes($message) . "'); window.location.href='freelancer_booking_list.php';</script>";
                    exit;

                } catch (Exception|PDOException $e) {
                    $conn->rollBack();
                    if (file_exists($targetPath)) unlink($targetPath);
                    $message = "Error updating database: " . htmlspecialchars($e->getMessage());
                }
            } else {
                $message = "Failed to move uploaded file.";
            }
        }
    } else {
        $message = "File upload error occurred.";
    }
}

// --------------------
// MARK AS COMPLETED HANDLER
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_completed'])) {
    try {
        $stmt = $conn->prepare("
            UPDATE booking
            SET booking_status = 'Job Pending Verification'
            WHERE booking_id = :bid
        ");
        $stmt->bindParam(':bid', $bid);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $success = true;
            $message = "Job marked as completed. Admin will verify this job.";
            // Update booking array to reflect new status for UI
            $booking['booking_status'] = 'Job Pending Verification';
        } else {
            $success = false;
            $message = "Booking not found or cannot update status.";
        }
    } catch (PDOException $e) {
        $success = false;
        $message = "Database error: " . htmlspecialchars($e->getMessage());
    }
}

// --------------------
// USER INFO FOR NAV
// --------------------
$isLoggedIn = isset($_SESSION['username']);
$currentUsername = $_SESSION['username'] ?? '';
$role = strtolower($_SESSION['role'] ?? $_SESSION['user_type'] ?? '');
$profileLink = ($role === 'client') ? 'client.php' : 'freelancer_form.php';
$profileLabel = ($role === 'client') ? 'Edit Client Profile' : 'Edit Freelancer Profile';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $isReupload ? "Re-Upload Deliverable" : "Upload Deliverable" ?> | Watan Freelance</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#f4f6fa;min-height:100vh;}
nav{background:rgba(255,255,255,.9);backdrop-filter:blur(6px);box-shadow:0 2px 5px rgba(0,0,0,.08);display:flex;justify-content:space-between;align-items:center;padding:14px 60px;position:sticky;top:0;z-index:10;}
nav .logo{font-size:1.6rem;font-weight:700;color:#7a5af8}
nav ul{display:flex;list-style:none;gap:25px}
nav ul li a{text-decoration:none;color:#333;font-weight:500}
nav ul li a:hover{color:#7a5af8}
.container{max-width:700px;margin:60px auto;padding:0 20px}
.view-card{background:linear-gradient(135deg,#ffffff 0%,#f8f9ff 100%);border-radius:24px;box-shadow:0 10px 30px rgba(102,126,234,.15);padding:30px;margin-bottom:30px;position:relative;}
.view-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#667eea,#764ba2,#f093fb,#f5576c);}
.view-header{text-align:center;margin-bottom:30px}
.view-header h1{font-size:2rem;font-weight:700;background:linear-gradient(45deg,#667eea,#764ba2,#f093fb);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.view-header p{color:#666}
.booking-info{background:#fff;border-radius:20px;padding:25px;margin-bottom:30px;}
.info-row{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid #eee;}
.info-row:last-child{border-bottom:none}
.info-label{font-weight:600;color:#667eea}
.info-value{font-weight:600;color:#333}
.status{padding:6px 14px;border-radius:999px;font-size:.85rem;font-weight:600;color:#fff;}
.status.booking-request{background:#FFA500}
.status.job-in-progress{background:#3B82F6}
.status.job-completed{background:#10b981}
.status.job-cancelled{background:#6B7280}
.deliverable{margin-top:30px;text-align:center;padding:30px;background:linear-gradient(135deg,#f0f4ff,#ffffff);border-radius:24px;}
.deliverable i{font-size:3rem;color:#4facfe;}
.deliverable h3{margin:15px 0;color:#333}
.alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:0.9rem;}
.alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
.alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
.upload-section label{display:block;font-weight:600;margin-bottom:8px;color:#333;}
.upload-section input[type="file"]{width:100%;padding:12px;border:2px dashed #7a5af8;border-radius:8px;background:#f8f9fa;cursor:pointer}
.upload-section input[type="file"]:hover{background:#f3f1ff;border-color:#6948f0}
.upload-section input[type="file"]::file-selector-button{background:#7a5af8;color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-weight:600;margin-right:10px}
.upload-section input[type="file"]::file-selector-button:hover{background:#6948f0}
.upload-section textarea{width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:8px;resize:vertical;min-height:100px}
.upload-section textarea:focus{outline:none;border-color:#7a5af8;box-shadow:0 0 0 4px rgba(122,90,248,0.1)}
.btn-submit{width:100%;background:#7a5af8;color:#fff;border:none;padding:14px 24px;border-radius:8px;font-size:1rem;font-weight:600;margin-top:20px;cursor:pointer;transition:all 0.3s ease;}
.btn-submit:hover:not(:disabled){background:#6948f0}
.btn-submit:disabled{background:#ccc;cursor:not-allowed;opacity:0.6;}
.btn-mark{width:100%;background:#10b981;color:#fff;border:none;padding:14px 24px;border-radius:8px;font-size:1rem;font-weight:600;margin-top:10px;cursor:pointer;transition:all 0.3s ease;}
.btn-mark:hover:not(:disabled){background:#0f9e6e}
.btn-mark:disabled{background:#ccc;cursor:not-allowed;opacity:0.6;}
.btn-back{text-align:center;margin-top:20px}
.btn-back a{text-decoration:none;background:linear-gradient(135deg,#ff6b6b,#c44569);color:#fff;padding:12px 30px;border-radius:999px;font-weight:600}
.btn-back a:hover{opacity:.9}
.file-name-display{margin-top:10px;padding:8px 12px;background:#f0f9ff;border-left:3px solid #7a5af8;border-radius:4px;font-size:0.9rem;color:#333;display:none;}
.file-name-display i{color:#10b981;margin-right:8px;}
</style>
</head>
<body>

<div class="container">

<div class="view-card">
    <div class="view-header">
        <h1><?= $isReupload ? "Re-Upload Deliverable" : "Upload Deliverable" ?></h1>
        <p>Booking ID: <?= htmlspecialchars($bid) ?></p>
    </div>

    <div class="booking-info">
        <div class="info-row">
            <span class="info-label">Service</span>
            <span class="info-value"><?= htmlspecialchars($booking['service_title'] ?? 'N/A') ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Status</span>
            <?php
                $statusClass = match($booking['booking_status'] ?? '') {
                    'Booking Request' => 'booking-request',
                    'Job In Progress' => 'job-in-progress',
                    'Job Pending Verification' => 'job-in-progress',
                    'Job Completed' => 'job-completed',
                    default => 'job-cancelled'
                };
            ?>
            <span class="status <?= $statusClass ?>"><?= htmlspecialchars($booking['booking_status'] ?? 'N/A') ?></span>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- UPLOAD FORM -->
    <form method="post" enctype="multipart/form-data" class="upload-section" id="uploadForm">
        <input type="hidden" name="bid" value="<?= htmlspecialchars($bid) ?>">
        <?php if($isReupload): ?>
            <input type="hidden" name="reupload" value="1">
        <?php endif; ?>

        <label for="description">Description</label>
        <textarea name="description" id="description" placeholder="Describe your deliverable..." required><?= htmlspecialchars($description) ?></textarea>

        <label for="deliverable">Upload File</label>
        <input type="file" name="deliverable" id="deliverable" required>
        <p style="font-size:0.85rem;color:#888;margin-top:5px;">Accepted: PNG, JPG, JPEG, PDF, DOC, DOCX, ZIP, RAR (Max 10MB)</p>
        
        <div class="file-name-display" id="fileNameDisplay">
            <i class="bi bi-file-earmark-check"></i>
            <span id="fileName"></span>
        </div>

        <button type="submit" name="submit_deliverable" class="btn-submit" id="uploadBtn" disabled>
            <?= $isReupload ? "Re-Upload Deliverable" : "Upload Deliverable" ?>
        </button>
    </form>

    <!-- MARK AS COMPLETED FORM -->
    <form method="post" class="upload-section" id="markCompletedForm">
        <input type="hidden" name="bid" value="<?= htmlspecialchars($bid) ?>">
        <input type="hidden" name="mark_completed" value="1">
        <button type="submit" class="btn-mark" id="markBtn" disabled>
            <i class="bi bi-check2-circle"></i> Mark as Completed
        </button>
    </form>

    <div class="btn-back">
        <a href="freelancer_booking_list.php">← Back to Bookings</a>
    </div>
</div>

</div>

<script>
// Enable buttons only when file is selected
const fileInput = document.getElementById('deliverable');
const uploadBtn = document.getElementById('uploadBtn');
const markBtn = document.getElementById('markBtn');
const fileNameDisplay = document.getElementById('fileNameDisplay');
const fileName = document.getElementById('fileName');

fileInput.addEventListener('change', function() {
    if (this.files && this.files.length > 0) {
        // Enable buttons
        uploadBtn.disabled = false;
        markBtn.disabled = false;
        
        // Show file name
        fileName.textContent = this.files[0].name;
        fileNameDisplay.style.display = 'block';
    } else {
        // Disable buttons
        uploadBtn.disabled = true;
        markBtn.disabled = true;
        
        // Hide file name
        fileNameDisplay.style.display = 'none';
    }
});

// Validate form submission
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    if (!fileInput.files || fileInput.files.length === 0) {
        e.preventDefault();
        alert('Please make sure to submit your deliverables or job proof here');
    }
});

document.getElementById('markCompletedForm').addEventListener('submit', function(e) {
    if (!fileInput.files || fileInput.files.length === 0) {
        e.preventDefault();
        alert('Please make sure to submit your deliverables or job proof here');
    }
});
</script>

</body>
</html>