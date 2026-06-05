<?php
session_start();
include_once 'database.php';

try {
  $required = ['freelancer_id', 'client_name', 'booking_date', 'location', 'service_description'];
  foreach ($required as $field) {
    if (empty($_POST[$field])) {
      $_SESSION['booking_error'] = "All fields are required.";
      header("Location: booking.php?freelancer_id=" . urlencode($_POST['freelancer_id'] ?? ''));
      exit();
    }
  }

  $stmt = $conn->prepare("INSERT INTO bookings 
    (freelancer_id, client_name, booking_date, location, service_description, created_at)
    VALUES (:freelancer_id, :client_name, :booking_date, :location, :service_description, NOW())");

  $stmt->bindParam(':freelancer_id', $_POST['freelancer_id']);
  $stmt->bindParam(':client_name', $_POST['client_name']);
  $stmt->bindParam(':booking_date', $_POST['booking_date']);
  $stmt->bindParam(':location', $_POST['location']);
  $stmt->bindParam(':service_description', $_POST['service_description']);
  $stmt->execute();

  $booking_id = $conn->lastInsertId();
  header("Location: payment.php?booking_id=" . $booking_id);
  exit();
} catch (PDOException $e) {
  error_log("Booking error: " . $e->getMessage());
  $_SESSION['booking_error'] = "Something went wrong. Please try again.";
  header("Location: booking.php?freelancer_id=" . urlencode($_POST['freelancer_id']));
  exit();
}
