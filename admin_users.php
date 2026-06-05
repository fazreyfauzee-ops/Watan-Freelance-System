<?php
require_once 'admin_access.php';
ensure_admin_access();
require_once 'database.php';

// Get admin profile data
$admin_id = $_SESSION['id'];
$admin_profile = null;

try {
    // Get admin user info
    $stmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = :id AND role = 'admin'");
    $stmt->execute([':id' => $admin_id]);
    $admin_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get admin profile picture and QR code if they exist in freelancers table
    $stmt = $conn->prepare("SELECT profile_picture, qr_code FROM freelancers WHERE user_id = :id");
    $stmt->execute([':id' => $admin_id]);
    $freelancer_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freelancer_data) {
        $admin_profile['profile_picture'] = $freelancer_data['profile_picture'];
        $admin_profile['qr_code'] = $freelancer_data['qr_code'];
    }
} catch (PDOException $e) {
    $admin_profile = ['fullname' => 'Admin', 'email' => ''];
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = $_POST['user_id'] ?? '';
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM users WHERE id = :user_id");
                $stmt->execute([':user_id' => $userId]);
                $message = "User deleted successfully";
                break;
                
            case 'change_role':
                $newRole = $_POST['new_role'] ?? '';
                if (in_array($newRole, ['client', 'freelancer', 'admin'])) {
                    $stmt = $conn->prepare("UPDATE users SET role = :role WHERE id = :user_id");
                    $stmt->execute([':role' => $newRole, ':user_id' => $userId]);
                    $message = "User role updated successfully";
                }
                break;
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all users with their details
try {
    $stmt = $conn->query("
        SELECT 
            u.*,
            c.bio as client_bio,
            f.bio as freelancer_bio,
            f.skills,
            f.availability
        FROM users u
        LEFT JOIN clients c ON u.id = c.user_id
        LEFT JOIN freelancers f ON u.id = f.user_id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching users: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Users | Watan Freelance System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root{
            --primary-purple:#7a5af8;
            --primary-dark:#6948f0;
            --secondary-cyan:#06b6d4;
            --success-green:#10b981;
            --warning-orange:#f59e0b;
            --danger-red:#ef4444;
            --gradient-1:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2:linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-3:linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-4:linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --gradient-5:linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --gradient-6:linear-gradient(135deg, #30cfd0 0%, #330867 100%);
            --secondary-bg:#f8fafc;
            --card-shadow:0 10px 30px rgba(0,0,0,0.1);
            --hover-shadow:0 20px 40px rgba(0,0,0,0.15);
        }
        body{
            background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment:fixed;
            font-family:'Poppins',sans-serif;
            padding-top:70px;
            min-height:100vh;
        }

        /* NAVBAR */
        .admin-nav{
            position:fixed;
            top:0;width:100%;
            background:rgba(255,255,255,0.98);
            backdrop-filter:blur(10px);
            box-shadow:0 2px 20px rgba(0,0,0,0.08);
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:16px 60px;
            z-index:1000;
        }
        .logo{
            font-size:1.6rem;
            font-weight:700;
            background:var(--gradient-1);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
            background-clip:text;
        }
        .nav-right{list-style:none;display:flex;gap:8px;margin:0;align-items:center;}
        .nav-right a{
            text-decoration:none;
            color:#475569;
            font-weight:500;
            padding:10px 20px;
            border-radius:12px;
            transition:all 0.2s ease;
            font-size:0.95rem;
        }
        .nav-right a:hover{
            background:var(--gradient-1);
            color:#fff;
            transform:translateY(-1px);
            box-shadow:0 4px 12px rgba(102,126,234,0.3);
        }
        .admin-badge{
            background:var(--gradient-2);
            color:#fff;
            padding:8px 16px;
            border-radius:12px;
            font-size:0.85rem;
            font-weight:600;
            margin-right:8px;
        }

        /* PROFILE SECTION */
        .profile-section{
            display:flex;
            align-items:center;
            gap:12px;
            padding:8px 16px;
            background:rgba(255,255,255,0.1);
            border-radius:15px;
            border:1px solid rgba(255,255,255,0.2);
        }
        .admin-avatar{
            width:40px;
            height:40px;
            border-radius:50%;
            object-fit:cover;
            border:2px solid var(--gradient-1);
            box-shadow:0 2px 8px rgba(0,0,0,0.1);
        }
        .admin-avatar-placeholder{
            width:40px;
            height:40px;
            border-radius:50%;
            background:var(--gradient-1);
            display:flex;
            align-items:center;
            justify-content:center;
            color:#fff;
            font-size:1.2rem;
            border:2px solid var(--gradient-1);
            box-shadow:0 2px 8px rgba(0,0,0,0.1);
        }
        .admin-info{
            display:flex;
            flex-direction:column;
            gap:4px;
        }
        .admin-name{
            font-weight:600;
            color:#334155;
            font-size:0.9rem;
        }
        .admin-qr{
            width:24px;
            height:24px;
            border-radius:4px;
            object-fit:cover;
            border:1px solid rgba(0,0,0,0.1);
        }

        /* CONTENT */
        .container{
            max-width:1400px;
            margin:0 auto;
            padding:30px;
        }
        .card{
            background:rgba(255,255,255,0.95);
            backdrop-filter:blur(20px);
            border-radius:20px;
            box-shadow:var(--card-shadow);
            overflow:hidden;
            transition:all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border:1px solid rgba(255,255,255,0.2);
        }
        .card:hover{
            transform:translateY(-5px);
            box-shadow:var(--hover-shadow);
        }
        .card-header{
            background:var(--gradient-1);
            color:#fff;
            font-weight:700;
            padding:20px;
            font-size:1.1rem;
            text-shadow:0 2px 10px rgba(0,0,0,0.2);
            position:relative;
            overflow:hidden;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }
        .card-header::after{
            content:'';
            position:absolute;
            top:-50%;right:-50%;
            width:100%;height:100%;
            background:radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation:shimmer 3s infinite;
        }
        @keyframes shimmer{
            0%{transform:rotate(0deg);}
            100%{transform:rotate(360deg);}
        }
        .card-body{
            padding:0;
        }
        .table{
            margin:0;
            background:rgba(255,255,255,0.5);
        }
        .table th{
            background:rgba(122,90,248,0.1);
            font-weight:700;
            color:#475569;
            border:none;
            padding:15px;
            font-size:.85rem;
            text-transform:uppercase;
            letter-spacing:0.5px;
        }
        .table td{
            padding:15px;
            border-top:1px solid rgba(226,232,240,0.5);
            vertical-align:middle;
            font-weight:500;
            color:#334155;
            transition:all 0.2s ease;
        }
        .table tbody tr:hover td{
            background:rgba(122,90,248,0.05);
        }

        /* ROLE BADGES */
        .role-badge{
            padding:6px 12px;
            border-radius:12px;
            font-size:0.75rem;
            font-weight:600;
            text-transform:uppercase;
            white-space:nowrap;
            display:inline-block;
        }
        .role-admin{background:var(--gradient-2);color:#fff;box-shadow:0 2px 8px rgba(245,87,108,0.3);}
        .role-client{background:var(--gradient-3);color:#fff;box-shadow:0 2px 8px rgba(79,172,254,0.3);}
        .role-freelancer{background:var(--gradient-4);color:#fff;box-shadow:0 2px 8px rgba(67,233,123,0.3);}

        /* BUTTONS */
        .btn-action{
            background:var(--gradient-1);
            color:#fff;
            border:none;
            padding:8px 16px;
            border-radius:10px;
            font-size:0.85rem;
            font-weight:600;
            text-decoration:none;
            transition:all 0.2s ease;
            margin:0 2px;
            display:inline-block;
            box-shadow:0 2px 8px rgba(102,126,234,0.3);
        }
        .btn-action:hover{
            transform:translateY(-2px);
            box-shadow:0 4px 12px rgba(102,126,234,0.4);
            color:#fff;
        }
        .btn-danger{
            background:var(--gradient-2);
            box-shadow:0 2px 8px rgba(245,87,108,0.3);
        }
        .btn-danger:hover{
            box-shadow:0 4px 12px rgba(245,87,108,0.4);
        }

        /* ALERT */
        .alert{
            background:rgba(255,255,255,0.95);
            backdrop-filter:blur(20px);
            border-radius:15px;
            border:none;
            box-shadow:var(--card-shadow);
            margin-bottom:20px;
        }
        .alert-success{
            background:rgba(16,185,129,0.1);
            color:var(--success-green);
            border:1px solid rgba(16,185,129,0.2);
        }
        .alert-danger{
            background:rgba(239,68,68,0.1);
            color:var(--danger-red);
            border:1px solid rgba(239,68,68,0.2);
        }

        /* USER INFO */
        .user-info{
            font-size:0.85rem;
        }
        .user-info .name{
            font-weight:600;
            color:#334155;
        }
        .user-info .email{
            color:#64748b;
            font-size:0.8rem;
        }
        .user-info .bio{
            color:#64748b;
            font-size:0.8rem;
            max-width:200px;
            line-height:1.3;
        }

        /* RESPONSIVE */
        @media(max-width:992px){
            .admin-nav{padding:12px 20px;}
            .container{padding:20px;}
        }
        @media(max-width:576px){
            .nav-right{flex-wrap:wrap;gap:10px;}
            .nav-right a{padding:6px 12px;font-size:.9rem;}
            .btn-action{padding:6px 12px;font-size:.8rem;}
        }
    </style>
</head>
<body>
    <nav class="admin-nav">
    <div class="logo">Watan Admin</div>
    <ul class="nav-right">
        <li><a href="admin_dashboard.php">Dashboard</a></li>
        <li><a href="admin_users.php">Users</a></li>
        <li><a href="admin_booking_list.php">Bookings</a></li>
        <li><a href="admin_feedback.php">Feedback</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-people me-2"></i>Manage Users</span>
                <span><?= count($users) ?> Total</span>
            </div>
            <div class="card-body">
                <?php if (isset($message)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Contact</th>
                                <th>Profile</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-details">
                                        <div class="user-name"><?= htmlspecialchars($user['fullname'] ?: $user['username']) ?></div>
                                        <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge role-<?= $user['role'] ?>">
                                        <?= htmlspecialchars($user['role']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($user['phone']) ?></td>
                                <td>
                                    <div class="user-bio" title="<?= htmlspecialchars($user['client_bio'] ?: $user['freelancer_bio'] ?: 'No bio') ?>">
                                        <?= htmlspecialchars(substr($user['client_bio'] ?: $user['freelancer_bio'] ?: 'No bio', 0, 30)) ?>...
                                    </div>
                                    <?php if ($user['skills']): ?>
                                        <small class="text-muted">Skills: <?= htmlspecialchars(substr($user['skills'], 0, 20)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <?php if ($user['id'] != 0): // Don't allow deleting system admin ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            
                                            <select name="new_role" class="form-select form-select-sm" style="width: auto; display: inline-block; margin-right: 5px;">
                                                <option value="<?= $user['role'] ?>" selected><?= ucfirst($user['role']) ?></option>
                                                <?php if ($user['role'] !== 'client'): ?>
                                                    <option value="client">Client</option>
                                                <?php endif; ?>
                                                <?php if ($user['role'] !== 'freelancer'): ?>
                                                    <option value="freelancer">Freelancer</option>
                                                <?php endif; ?>
                                                <?php if ($user['role'] !== 'admin'): ?>
                                                    <option value="admin">Admin</option>
                                                <?php endif; ?>
                                            </select>
                                            
                                            <button type="submit" name="action" value="change_role" class="btn-action">
                                                <i class="bi bi-pencil"></i> Update
                                            </button>
                                            
                                            <button type="submit" name="action" value="delete" class="btn-action btn-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">System Admin</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
