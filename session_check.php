<?php
// Centralized session guard for client-only pages.
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function ensure_client_access(): void {
  $userId = $_SESSION['userid'] ?? null;
  $role = $_SESSION['role'] ?? $_SESSION['user_type'] ?? 'client';

  // Require a logged-in user
  if (empty($userId)) {
    header('Location: login.php');
    exit;
  }

  // Enforce client role when specified
  if (!empty($role) && strtolower($role) !== 'client') {
    header('Location: login.php');
    exit;
  }
}

