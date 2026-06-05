<?php
session_start();
include_once 'database.php';

// Redirect helper
function failAndRedirect($booking_id, $message) {
  $_SESSION['booking_error'] = $message;
  header("Location: payment.php?booking_id=" . urlencode($booking_id));
  exit();
}

try {
  // Validate booking ID
  if (empty($_POST['booking_id'])) {
    die("Invalid access: booking ID not provided.");
  }

  $booking_id = $_POST['booking_id'];

  // Ensure file is uploaded
  if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
    failAndRedirect($booking_id, "Please upload a valid receipt (PNG or PDF).");
  }

  $allowed_types = ['image/png', 'image/jpeg', 'application/pdf'];
  $file_type = $_FILES['receipt']['type'];

  if (!in_array($file_type, $allowed_types)) {
    failAndRedirect($booking_id, "Only PNG, JPG, or PDF files are accepted.");
  }

  $upload_dir = 'uploads/';
  if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
    failAndRedirect($booking_id, "Upload directory is missing or not writable.");
  }

  $filename = uniqid('receipt_') . "_" . basename($_FILES['receipt']['name']);
  $target = $upload_dir . $filename;

  if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $target)) {
    failAndRedirect($booking_id, "Failed to save receipt file.");
  }

  // Save filename to bookings table
  $stmt = $conn->prepare("UPDATE bookings SET receipt_file = :file WHERE id = :id");
  $stmt->bindParam(':file', $filename);
  $stmt->bindParam(':id', $booking_id);
  $stmt->execute();

  $_SESSION['success'] = "Receipt uploaded successfully!";
header("Location: booking_list.php");

  exit();
} catch (PDOException $e) {
  error_log("Payment upload error: " . $e->getMessage());
  failAndRedirect($booking_id ?? 0, "Something went wrong while saving your receipt.");
}
