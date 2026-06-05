<?php
require_once "database.php";

$bid = isset($_GET['bid']) ? $_GET['bid'] : null;

if (!$bid) {
    exit("Missing booking ID.");
}

$stmt = $conn->prepare("UPDATE booking SET booking_status = 'Job Completed' WHERE booking_id = :bid");
$stmt->bindParam(':bid', $bid);
$stmt->execute();

header("Location: admin_booking_list.php");
exit;

