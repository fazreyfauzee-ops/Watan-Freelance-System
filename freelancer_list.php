<?php
session_start();
include_once 'database.php';

try {
  $stmt = $conn->query("SELECT * FROM freelancers ORDER BY created_at DESC");
  $freelancers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Error: " . $e->getMessage());
}
?>
<?php include_once 'nav_bar.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>Freelancer Directory</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Montserrat|Poppins&display=swap" rel="stylesheet">
  <style>
    :root {
      --lilac-light: #f8f5fc;
      --lilac-soft: #ece3f9;
      --lilac-accent: #d1baf4;
      --lilac-deep: #5a2f91;
      --lilac-darkest: #3d1c66;
    }

    body {
      background-color: var(--lilac-light);
      font-family: 'Poppins', sans-serif;
      color: var(--lilac-darkest);
    }

    h2 {
      font-family: 'Montserrat', sans-serif;
      font-weight: 700;
      color: var(--lilac-deep);
    }

    .freelancer-card {
      background-color: #ffffff;
      border: 1px solid #e2d6f5;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(90, 47, 145, 0.08);
      padding: 20px;
      margin-bottom: 20px;
      transition: transform 0.2s ease;
    }

    .freelancer-card:hover {
      transform: translateY(-4px);
    }

    .freelancer-avatar {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 50%;
      border: 3px solid var(--lilac-accent);
    }

    .btn-action {
      font-size: 14px;
      margin-right: 8px;
    }

    .name {
      color: var(--lilac-deep);
      font-weight: 600;
    }

    .skills {
      font-style: italic;
      color: #6f6294;
    }

    .card-footer {
      border-top: 1px solid #eee;
      padding-top: 10px;
    }
  </style>
</head>
<body>

<div class="container py-5">
  <h2 class="mb-4 text-center">Freelancer Directory</h2>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= $_SESSION['success']; unset($_SESSION['success']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <?php foreach ($freelancers as $row): ?>
      <div class="col-md-6 col-lg-4">
        <div class="freelancer-card">
          <div class="d-flex align-items-center mb-3">
            <?php if (!empty($row['profile_picture'])): ?>
              <img src="uploads/<?= htmlspecialchars($row['profile_picture']) ?>" class="freelancer-avatar me-3" alt="Photo">
            <?php else: ?>
              <div class="freelancer-avatar me-3 bg-light d-flex align-items-center justify-content-center text-muted">?</div>
            <?php endif; ?>
            <div>
              <div class="name"><?= htmlspecialchars($row['name']) ?></div>
              <div class="skills"><?= htmlspecialchars($row['skills']) ?></div>
            </div>
          </div>
          <p class="mb-1"><strong>Availability:</strong> <?= htmlspecialchars($row['availability']) ?></p>
          <p class="mb-2"><strong>Contact:</strong> <?= htmlspecialchars($row['contact_number']) ?></p>
          <div class="card-footer d-flex justify-content-start">
            <a href="freelancer_details.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-info btn-action">Details</a>
            <a href="freelancer_form.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning btn-action">Edit</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>
