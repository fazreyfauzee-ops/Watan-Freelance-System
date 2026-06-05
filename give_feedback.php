<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['userid'];
$bid = $_GET['bid'] ?? null;

if (!$bid) {
    die("Missing booking ID.");
}

/* =======================
   VERIFY BOOKING
======================= */
$stmt = $conn->prepare("
    SELECT b.*, f.id AS freelancer_id
    FROM booking b
    JOIN freelancer f ON b.freelancer_id = f.id
    WHERE b.booking_id = :bid AND b.client_id = :client_id
");
$stmt->execute([
    ':bid' => $bid,
    ':client_id' => $client_id
]);

$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("Booking not found or access denied.");
}

/* =======================
   CHECK EXISTING FEEDBACK (PER BOOKING)
======================= */
$stmt = $conn->prepare("
    SELECT id FROM feedback WHERE booking_id = :bid
");
$stmt->execute([':bid' => $bid]);

$already_feedback = $stmt->fetch();

/* =======================
   SUBMIT FEEDBACK
======================= */
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_feedback) {

    $rating = $_POST['rating'] ?? '';
    $comment = trim($_POST['comment'] ?? '');

    if ($rating && $comment) {

        $stmt = $conn->prepare("
            INSERT INTO feedback 
            (booking_id, client_id, freelancer_id, rating, comment)
            VALUES 
            (:booking_id, :client_id, :freelancer_id, :rating, :comment)
        ");

        $stmt->execute([
            ':booking_id'   => $bid,
            ':client_id'    => $client_id,
            ':freelancer_id'=> $booking['freelancer_id'],
            ':rating'       => $rating,
            ':comment'      => $comment
        ]);

        $message = "Feedback submitted successfully.";
        $already_feedback = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Give Feedback</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">

        <h3 class="text-center mb-4">Give Feedback</h3>

        <div class="alert alert-info">
            <strong>Booking ID:</strong> <?= htmlspecialchars($bid) ?><br>
            <strong>Service:</strong> <?= htmlspecialchars($booking['service_title'] ?? 'N/A') ?>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($already_feedback): ?>
            <div class="alert alert-warning">
                You have already submitted feedback for this booking.
            </div>
        <?php else: ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">Rating</label>
                <select name="rating" class="form-select" required>
                    <option value="">Select</option>
                    <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                    <option value="4">⭐⭐⭐⭐ Very Good</option>
                    <option value="3">⭐⭐⭐ Good</option>
                    <option value="2">⭐⭐ Fair</option>
                    <option value="1">⭐ Poor</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Comment</label>
                <textarea name="comment" class="form-control" rows="4" required></textarea>
            </div>

            <button class="btn btn-primary w-100">Submit Feedback</button>
        </form>

        <?php endif; ?>

        <div class="text-center mt-3">
            <a href="booking.php">← Back to Booking</a>
        </div>

    </div>
</div>

</body>
</html>
