<?php

$servername = "lrgs.ftsm.ukm.my";

$username = "a201648";

$password = "tinyyellowgoat";

$dbname = "a201648";

try {

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {

    die("Connection failed: " . $e->getMessage());

}

?>
