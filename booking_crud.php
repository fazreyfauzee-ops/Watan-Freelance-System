<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

include_once 'database.php';

try {
  // Reuse existing connection from database.php if available, otherwise create new one
  if (!isset($conn) || $conn === null) {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }

  /**
   * Unified status updater for booking actions.
   * Accept/Reject → freelancer only, Cancel → client only.
   */
  if (isset($_GET['action'], $_GET['bid'])) {
    $action = strtolower(trim($_GET['action']));
    $bid = trim($_GET['bid']);
    $role = strtolower($_SESSION['role'] ?? $_SESSION['user_type'] ?? '');

    $statusMap = [
      'accept' => ['status' => 'Job In Progress', 'role' => 'freelancer'],
      'reject' => ['status' => 'Job Rejected',    'role' => 'freelancer'],
      'cancel' => ['status' => 'Job Cancelled',   'role' => 'client'],
    ];

    if (!isset($statusMap[$action])) {
      echo "<script>alert('Invalid action.'); window.history.back();</script>";
      exit;
    }

    $requiredRole = $statusMap[$action]['role'];
    if ($role !== $requiredRole) {
      echo "<script>alert('You are not allowed to perform this action.'); window.history.back();</script>";
      exit;
    }

    // Ensure booking exists
    $check = $conn->prepare("SELECT booking_status FROM booking WHERE booking_id = :bid");
    $check->execute([':bid' => $bid]);
    $booking = $check->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
      echo "<script>alert('Booking not found.'); window.history.back();</script>";
      exit;
    }

    // Update status
    $stmt = $conn->prepare("UPDATE booking SET booking_status = :status WHERE booking_id = :bid");
    $stmt->execute([
      ':status' => $statusMap[$action]['status'],
      ':bid' => $bid,
    ]);

    $redirect = $_GET['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php');
    header("Location: " . $redirect);
    exit;
  }

  // Fungsi upload file (attachment)
  function handleFileUpload($booking_id) {
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
      $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx'];
      if (!in_array($ext, $allowed)) {
        echo "<script>alert('File type not allowed. Only JPG, PNG, GIF, PDF, DOCX allowed');</script>";
        return '';
      }
      if ($_FILES['attachment']['size'] > 5 * 1024 * 1024) {
        echo "<script>alert('File too large (max 5MB)');</script>";
        return '';
      }

      $target_dir = "uploads/";
      if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
      $target_file = $target_dir . $booking_id . "." . $ext;

      // Delete old file with same ID but different extension
      foreach (['jpg','jpeg','png','gif','pdf','docx'] as $old_ext) {
        $old_file = $target_dir . $booking_id . "." . $old_ext;
        if (file_exists($old_file)) unlink($old_file);
      }

      if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
        return basename($target_file);
      } else {
        echo "<script>alert('Failed to upload file');</script>";
      }
    }
    return '';
  }

  // GET BOOKING DATA FOR MODAL (AJAX)
  if (isset($_GET['get_booking'])) {
    header('Content-Type: application/json');
    $stmt = $conn->prepare("SELECT * FROM booking WHERE booking_id = :bid");
    $stmt->execute([':bid' => $_GET['get_booking']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
      echo json_encode(['success' => true, 'booking' => $booking]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Booking not found']);
    }
    exit;
  }

  // Constants for booking calculations
  $MAX_BOOKING_HOURS = 8;
  $RATE_PER_HOUR = 20; // RM

  // CREATE
  if (isset($_POST['create'])) {
    $booking_id = isset($_POST['bid']) && !empty($_POST['bid']) ? $_POST['bid'] : 'BK' . date('YmdHis') . rand(100,999);

    // Auto-set status to 'Job In Review' if not provided or if it's a new booking (matches database default)
    $status = isset($_POST['status']) && !empty($_POST['status']) ? $_POST['status'] : 'Job In Review';
    
    // Get service title and description
    $service_title = isset($_POST['service']) && !empty($_POST['service']) ? $_POST['service'] : 'Booking Service';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $work_mode = isset($_POST['work_mode']) && !empty($_POST['work_mode']) ? trim($_POST['work_mode']) : 'Online';
    
    // Get client ID
    $client_id = isset($_POST['clientid']) ? trim($_POST['clientid']) : (isset($_POST['clientname']) ? trim($_POST['clientname']) : 'CLIENT001');
    $freelancer_id = isset($_POST['freelancerid']) ? trim($_POST['freelancerid']) : '';
    $deadline = isset($_POST['deadline']) && !empty($_POST['deadline']) ? $_POST['deadline'] : date('Y-m-d');
    $total_booking_hours = isset($_POST['total_booking_hours']) && !empty($_POST['total_booking_hours']) ? intval($_POST['total_booking_hours']) : 0;

    // Validate hours and work mode
    if ($total_booking_hours <= 0 || $total_booking_hours > $MAX_BOOKING_HOURS) {
      echo "<script>alert('Please enter booking hours between 1 and {$MAX_BOOKING_HOURS}.'); window.history.back();</script>";
      exit;
    }

    if (!in_array($work_mode, ['Online', 'Offline'])) {
      echo "<script>alert('Please choose a valid work mode.'); window.history.back();</script>";
      exit;
    }

    // Calculate price on the server to avoid tampering
    $calculated_total_price = $total_booking_hours * $RATE_PER_HOUR;

    // Validate required fields
    if (empty($client_id) || empty($freelancer_id) || empty($service_title) || empty($description) || empty($deadline)) {
      echo "<script>alert('Please fill in all required fields'); window.history.back();</script>";
      exit;
    }

    $stmt = $conn->prepare("INSERT INTO booking (
      booking_id, client_id, freelancer_id, service_title, 
      description, deadline, total_booking_hours, calculated_total_price, 
      work_mode, booking_status
    ) VALUES (:bid, :client, :freelancer, :service, :description, :deadline, :hours, :price, :work_mode, :status)");

    $stmt->execute([
      ':bid' => $booking_id,
      ':client' => $client_id,
      ':freelancer' => $freelancer_id,
      ':service' => $service_title,
      ':description' => $description,
      ':deadline' => $deadline,
      ':hours' => $total_booking_hours,
      ':price' => $calculated_total_price,
      ':work_mode' => $work_mode,
      ':status' => $status
    ]);

    // Redirect to payment page with booking ID
    header("Location: payment.php?booking_id=" . urlencode($booking_id));
    exit;
  }

  // UPDATE
  if (isset($_POST['update'])) {
    $booking_id = $_POST['bid'];
    
    $service_title = isset($_POST['service']) && !empty($_POST['service']) ? trim($_POST['service']) : 'Booking Service';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $work_mode = isset($_POST['work_mode']) && !empty($_POST['work_mode']) ? trim($_POST['work_mode']) : 'Online';
    $client_id = isset($_POST['clientid']) ? trim($_POST['clientid']) : '';
    $freelancer_id = isset($_POST['freelancerid']) ? trim($_POST['freelancerid']) : '';
    $deadline = isset($_POST['deadline']) && !empty($_POST['deadline']) ? $_POST['deadline'] : date('Y-m-d');
    $total_booking_hours = isset($_POST['total_booking_hours']) && !empty($_POST['total_booking_hours']) ? intval($_POST['total_booking_hours']) : 0;
    $status = isset($_POST['status']) && !empty($_POST['status']) ? $_POST['status'] : 'Job In Review';

    // Validate hours and work mode
    if ($total_booking_hours <= 0 || $total_booking_hours > $MAX_BOOKING_HOURS) {
      echo "<script>alert('Please enter booking hours between 1 and {$MAX_BOOKING_HOURS}.'); window.history.back();</script>";
      exit;
    }

    if (!in_array($work_mode, ['Online', 'Offline'])) {
      echo "<script>alert('Please choose a valid work mode.'); window.history.back();</script>";
      exit;
    }

    $calculated_total_price = $total_booking_hours * $RATE_PER_HOUR;

    $stmt = $conn->prepare("UPDATE booking SET
      client_id = :client,
      freelancer_id = :freelancer,
      service_title = :service,
      description = :description,
      deadline = :deadline,
      total_booking_hours = :hours,
      calculated_total_price = :price,
      work_mode = :work_mode,
      booking_status = :status
    WHERE booking_id = :bid");

    $stmt->execute([
      ':client' => $client_id,
      ':freelancer' => $freelancer_id,
      ':service' => $service_title,
      ':description' => $description,
      ':deadline' => $deadline,
      ':hours' => $total_booking_hours,
      ':price' => $calculated_total_price,
      ':work_mode' => $work_mode,
      ':status' => $status,
      ':bid' => $booking_id
    ]);

    header("Location: booking.php");
    exit;
  }

  // DELETE
  if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM booking WHERE booking_id = :bid");
    $stmt->execute([':bid' => $_GET['delete']]);
    header("Location: booking.php");
    exit;
  }

  // EDIT (fetch data) - Legacy support for direct page edit (not used with modal)
  if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM booking WHERE booking_id = :bid");
    $stmt->execute([':bid' => $_GET['edit']]);
    $editrow = $stmt->fetch(PDO::FETCH_ASSOC);
    // If booking not found, unset editrow to prevent errors
    if ($editrow === false) {
      $editrow = null;
    }
  }

} catch (PDOException $e) {
  echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
  // Don't close connection on error, let it be handled by the calling script
}

// Note: Connection is not closed here to allow reuse in booking.php
// If you need to close it, do so at the end of booking.php
?>
