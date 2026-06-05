<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<nav class="navbar navbar-default" style="background-color: #dcd6f7; border-color: #8e8edd;">
  <div class="container-fluid">
    <!-- Branding -->
    <div class="navbar-header">
      <a class="navbar-brand" href="index.php" style="color: #5c4db1; font-weight: bold;">
        Watan Freelance System
      </a>
    </div>

    <!-- Navigation Links -->
    <ul class="nav navbar-nav">
      <li><a href="index.php" style="color:#5c4db1;">Dashboard</a></li>
      <li><a href="booking.php" style="color:#5c4db1;">Booking History</a></li>
      <li><a href="freelancer_list.php" style="color:#5c4db1;">Freelancers</a></li>
      <?php if (isset($_SESSION['userid']) && ($_SESSION['role'] ?? $_SESSION['user_type'] ?? 'client') === 'client'): ?>
        <li><a href="feedback_form.php" style="color:#5c4db1;">Feedback</a></li>
      <?php endif; ?>
      <li><a href="reports.php" style="color:#5c4db1;">Reports</a></li>
    </ul>

    <!-- Right Side: Auth -->
    <ul class="nav navbar-nav navbar-right">
      <?php if (!isset($_SESSION['user_id'])): ?>
        <li><a href="login.php" style="color:#5c4db1;"><span class="glyphicon glyphicon-log-in"></span> Login</a></li>
      <?php else: ?>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="color:#5c4db1;">
            <span class="glyphicon glyphicon-user"></span> Account <span class="caret"></span>
          </a>
          <ul class="dropdown-menu">
            <li><a href="logout.php"><span class="glyphicon glyphicon-log-out"></span> Logout</a></li>
          </ul>
        </li>
      <?php endif; ?>
    </ul>
  </div>
</nav>
