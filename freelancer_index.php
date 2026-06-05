<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once 'database.php';

$user_name = $_SESSION['username'] ?? 'Guest';
?>

<!DOCTYPE html>
<html>
<head>
  <?php include_once 'nav_bar.php'; ?>

  <meta charset="utf-8">
  <title>Freelancer Module</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Montserrat|Poppins&display=swap" rel="stylesheet">
  <style>
    :root {
      --lilac-light: #f8f5fc;
      --lilac-soft: #e8ddfa;
      --lilac-accent: #d1baf4;
      --lilac-deep: #5a2f91;
      --lilac-darkest: #3d1c66;
    }

    body {
      background-color: var(--lilac-light);
      font-family: 'Poppins', sans-serif;
      color: var(--lilac-darkest);
      margin: 0;
    }

    .logo-wrapper {
      text-align: center;
      margin-top: 30px;
      margin-bottom: 10px;
    }

    .logo-wrapper img {
      max-height: 260px;
      width: auto;
    }

    h1, h2, .panel-heading {
      font-family: 'Montserrat', sans-serif;
      font-weight: 700;
      color: var(--lilac-deep);
    }

    .btn-purple {
      background-color: var(--lilac-deep);
      color: white;
      border: none;
      padding: 12px 20px;
      font-size: 16px;
      border-radius: 8px;
      transition: background-color 0.3s ease;
    }

    .btn-purple:hover {
      background-color: var(--lilac-darkest);
    }

    .container {
      text-align: center;
      margin-top: 30px;
    }

    .row {
      margin-top: 40px;
    }

    p {
      font-weight: 500;
    }
  </style>
</head>
<body>

  <div class="logo-wrapper">
    <img src="watan_logo.png" alt="Watan Logo">
  </div>

  <div class="container">
    <h1>Add Freelancer Profile</h1>
    <p>Register as one of our freelancer now</p>

    <div class="row">
      <div class="col-sm-12">
        <a href="freelancer_form.php" class="btn btn-purple btn-lg btn-block">➕ Add Freelancer Profile</a>
      </div>
    </div>
  </div>

</body>
</html>
