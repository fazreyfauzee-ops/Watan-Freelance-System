<?php
require_once "database.php";

$bid = $_GET["bid"] ?? null;
$action = $_GET["action"] ?? null;

if (!$bid || !$action) {
    die("Invalid request.");
}

if ($action == "approve") {
    // Change status to Verified Completion
    $sql = "UPDATE tbl_bookinglist 
            SET FLD_BOOKING_STATUS = 'Completed (Verified)'
            WHERE FLD_BOOKING_ID = :bid";
} 
elseif ($action == "reject") {
    // Return to revision stage
    $sql = "UPDATE tbl_bookinglist 
            SET FLD_BOOKING_STATUS = 'Revision Requested'
            WHERE FLD_BOOKING_ID = :bid";
}

$stmt = $conn->prepare($sql);
$stmt->bindParam(":bid", $bid);
$stmt->execute();

header("Location: admin_verify_jobs.php");
exit();
?>
