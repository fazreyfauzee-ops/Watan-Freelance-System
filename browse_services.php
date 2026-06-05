<?php
session_start();
include_once 'database.php';

// Enhanced search and filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$skill_filter = isset($_GET['skill']) ? $_GET['skill'] : '';
$availability_filter = isset($_GET['availability']) ? $_GET['availability'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'recent';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build base query
$base_query = "
    SELECT 
        freelancers.*, 
        users.fullname AS name,
        users.email,
        COALESCE(AVG(feedback.rating), 0) as avg_rating,
        COUNT(feedback.id) as review_count
    FROM freelancers
    JOIN users ON freelancers.user_id = users.id
    LEFT JOIN feedback ON freelancers.user_id = feedback.freelancer_id
    WHERE users.role = 'freelancer'
";

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(users.fullname LIKE :search OR freelancers.skills LIKE :search OR freelancers.bio LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($skill_filter) {
    $where_conditions[] = "freelancers.skills LIKE :skill";
    $params[':skill'] = "%$skill_filter%";
}

if ($availability_filter) {
    $where_conditions[] = "freelancers.availability = :availability";
    $params[':availability'] = $availability_filter;
}

if (!empty($where_conditions)) {
    $base_query .= " AND " . implode(" AND ", $where_conditions);
}

// Add GROUP BY and ORDER BY
$base_query .= " GROUP BY freelancers.user_id";

// Sorting
switch ($sort_by) {
    case 'rating':
        $base_query .= " ORDER BY avg_rating DESC, review_count DESC";
        break;
    case 'name':
        $base_query .= " ORDER BY users.fullname ASC";
        break;
    case 'availability':
        $base_query .= " ORDER BY CASE WHEN freelancers.availability = 'Available' THEN 1 ELSE 2 END";
        break;
    default:
        $base_query .= " ORDER BY freelancers.user_id DESC";
}

try {
    // Get total count for pagination - simpler and more accurate
    $count_query = "
        SELECT COUNT(DISTINCT freelancers.user_id) 
        FROM freelancers 
        JOIN users ON freelancers.user_id = users.id 
        WHERE users.role = 'freelancer'
    ";
    
    $count_params = [];
    if ($search) {
        $count_query .= " AND (users.fullname LIKE :search OR freelancers.skills LIKE :search OR freelancers.bio LIKE :search)";
        $count_params[':search'] = "%$search%";
    }
    if ($skill_filter) {
        $count_query .= " AND freelancers.skills LIKE :skill";
        $count_params[':skill'] = "%$skill_filter%";
    }
    if ($availability_filter) {
        $count_query .= " AND freelancers.availability = :availability";
        $count_params[':availability'] = $availability_filter;
    }
    
    $count_stmt = $conn->prepare($count_query);
    foreach ($count_params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_freelancers = $count_stmt->fetchColumn();
    $total_pages = ceil($total_freelancers / $per_page);

    // Get paginated results
    $query = $base_query . " LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $freelancers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all unique skills for filter dropdown
    $skills_query = "SELECT DISTINCT skills FROM freelancers WHERE skills IS NOT NULL AND skills != ''";
    $skills_stmt = $conn->query($skills_query);
    $all_skills = [];
    while ($row = $skills_stmt->fetch(PDO::FETCH_ASSOC)) {
        $skills = array_map('trim', explode(',', $row['skills']));
        $all_skills = array_merge($all_skills, $skills);
    }
    $all_skills = array_unique($all_skills);
    sort($all_skills);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Browse Freelancer Services | Watan Freelance</title>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap CSS -->
  <link href="css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  
  <!-- Font Awesome for additional icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      --accent-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
      --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
      --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
      --info-gradient: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
      --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: 'Poppins', sans-serif;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,106.7C1248,96,1344,96,1392,96L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
      background-size: cover;
      pointer-events: none;
      z-index: 0;
    }

    /* ================= NAVIGATION BAR ================= */
    nav {
      background-color: rgba(255,255,255,0.9);
      backdrop-filter: saturate(180%) blur(6px);
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 60px;
      position: sticky;
      top: 0;
      z-index: 10;
      transition: box-shadow .2s ease, background-color .2s ease;
    }

    nav .logo {
      font-size: 1.6rem;
      font-weight: 700;
      color: #7a5af8; /* soft purple */
    }

    nav ul {
      display: flex;
      list-style: none;
      gap: 25px;
    }

    nav ul li a {
      text-decoration: none;
      color: #333;
      font-weight: 500;
      position: relative;
      padding-bottom: 4px;
      transition: color .2s ease;
    }

    nav ul li a:hover { color: #7a5af8; }

    nav ul li a::after {
      content: '';
      position: absolute;
      left: 0;
      bottom: 0;
      width: 0;
      height: 2px;
      background: #7a5af8;
      transition: width .2s ease;
    }

    nav ul li a:hover::after { width: 100%; }

    nav ul li a.active {
      color: #7a5af8;
    }

    nav ul li a.active::after {
      width: 100%;
    }

    .nav-actions { display: flex; align-items: center; gap: 10px; }

    .btn-join-nav {
      background: #7a5af8;
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 999px;
      font-weight: 600;
      cursor: pointer;
      transition: background .2s ease;
    }

    .btn-join-nav:hover { background: #6948f0; }

    .link-signin {
      color: #333;
      text-decoration: none;
      font-weight: 500;
      padding: 6px 10px;
      border-radius: 8px;
      transition: color .2s ease, background-color .2s ease;
    }
    .link-signin:hover { color: #7a5af8; background-color: #f3f1ff; }

    /* ================= HERO BANNER ================= */
    .hero-banner {
      width: 100%;
      min-height: 450px;
      background: url('browse.jpg') center center/cover no-repeat;
      position: relative;
      margin-bottom: 50px;
      overflow: hidden;
      border-radius: 0 0 20px 20px;
    }

    .hero-banner::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.55);
    }

    .hero-content {
      position: relative;
      z-index: 2;
      min-height: 450px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 60px 20px;
    }

    .hero-text {
      font-size: 3rem;
      font-weight: 700;
      color: #cbb2ff;
      margin-bottom: 40px;
      animation: fadeInDown 0.8s ease-out;
    }

    .hero-subtitle {
      font-size: 1.3rem;
      color: #f8f8f8;
      margin-bottom: 50px;
      font-weight: 400;
      animation: fadeInUp 0.8s ease-out 0.2s both;
    }

    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ================= SEARCH AND FILTERS ================= */
    .search-section {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(25px);
      border-radius: 8px;
      padding: 40px;
      margin: -30px auto 50px;
      width: 100%;
      max-width: 1400px;
      box-shadow: 0 20px 60px rgba(31, 38, 135, 0.1);
      position: relative;
      z-index: 10;
      animation: slideUp 0.8s ease-out 0.4s both;
      min-height: 180px;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(50px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .search-container {
      display: flex;
      gap: 15px;
      margin-bottom: 25px;
      align-items: stretch;
    }

    .search-input-wrapper {
      position: relative;
      flex: 1;
    }

    .search-input-wrapper i {
      position: absolute;
      left: 20px;
      top: 50%;
      transform: translateY(-50%);
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-size: 1.3rem;
      font-weight: 700;
      z-index: 2;
    }

    .search-box {
      width: 100%;
      padding: 20px 24px 20px 55px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 8px;
      font-size: 1.1rem;
      font-weight: 500;
      transition: all 0.3s ease;
      background: rgba(255, 255, 255, 0.9);
      height: 60px;
      color: #2c3e50;
    }

    .search-box:focus {
      outline: none;
      border-color: rgba(102, 126, 234, 0.6);
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
      background: rgba(255, 255, 255, 0.95);
    }

    .search-box::placeholder {
      color: rgba(44, 62, 80, 0.6);
    }

    .search-btn {
      color: white;
      border: none;
      padding: 20px 32px;
      border-radius: 8px;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 140px;
      justify-content: center;
      height: 60px;
    }

    .search-btn.search-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    .search-btn.search-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
    }

    .search-btn.search-secondary {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      box-shadow: 0 8px 25px rgba(240, 147, 251, 0.4);
    }

    .search-btn.search-secondary:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 35px rgba(240, 147, 251, 0.6);
    }

    .search-btn i {
      font-size: 1rem;
    }

    .filter-section {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.3);
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .filter-label {
      font-weight: 600;
      color: white;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 8px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .filter-label i {
      font-size: 0.95rem;
      color: white;
      font-weight: 700;
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
    }

    .filter-select {
      padding: 12px 16px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 12px;
      font-size: 0.95rem;
      font-weight: 500;
      transition: all 0.3s ease;
      background: rgba(255, 255, 255, 0.9);
      cursor: pointer;
      color: #2c3e50;
    }

    .filter-select:focus {
      outline: none;
      border-color: rgba(102, 126, 234, 0.6);
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
      background: rgba(255, 255, 255, 0.95);
    }

    .filter-select option {
      background: white;
      color: #2c3e50;
    }

    /* ================= FREELANCER CARDS ================= */
    .main-content {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .results-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      flex-wrap: wrap;
      gap: 20px;
    }

    .results-count {
      font-size: 1.3rem;
      font-weight: 700;
      color: white;
      display: flex;
      align-items: center;
      gap: 12px;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
      padding: 12px 24px;
      border-radius: 30px;
      backdrop-filter: blur(15px);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
      transition: all 0.3s ease;
    }

    .results-count:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
    }

    .results-count i {
      font-size: 1.4rem;
      color: #ffd700;
      animation: pulse 2s infinite;
    }

    .results-count span {
      font-weight: 600;
      color: #ffffff;
    }

    .freelancer-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 30px;
      margin-bottom: 50px;
    }

    .freelancer-card {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 255, 0.95) 100%);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      padding: 30px;
      box-shadow: 0 10px 40px rgba(102, 126, 234, 0.15);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      height: 100%;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
      border: 2px solid transparent;
      background-image: linear-gradient(white, white), linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      background-origin: border-box;
      background-clip: padding-box, border-box;
    }

    .freelancer-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
      border-radius: 20px 20px 0 0;
    }

    .freelancer-card::after {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(45deg, transparent 30%, rgba(102, 126, 234, 0.05) 50%, transparent 70%);
      animation: shimmer 3s infinite;
      pointer-events: none;
    }

    @keyframes shimmer {
      0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
      100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }

    .freelancer-card:hover {
      transform: translateY(-10px) scale(1.02);
      box-shadow: 0 20px 60px rgba(102, 126, 234, 0.25);
      border-color: #667eea;
    }

    .freelancer-header {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 20px;
    }

    .avatar-container {
      position: relative;
      flex-shrink: 0;
    }

    .avatar {
      width: 85px;
      height: 85px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid transparent;
      background: linear-gradient(white, white) padding-box,
                  linear-gradient(135deg, #667eea 0%, #764ba2 100%) border-box;
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
      transition: all 0.3s ease;
    }

    .freelancer-card:hover .avatar {
      transform: scale(1.1) rotate(5deg);
      box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
    }

    .avatar-placeholder {
      width: 85px;
      height: 85px;
      border-radius: 50%;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 2.2rem;
      font-weight: 800;
      border: 4px solid white;
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
      transition: all 0.3s ease;
    }

    .freelancer-card:hover .avatar-placeholder {
      transform: scale(1.1) rotate(-5deg);
      box-shadow: 0 12px 35px rgba(102, 126, 234, 0.7);
    }

    .freelancer-info {
      flex: 1;
    }

    .name {
      font-size: 1.4rem;
      font-weight: 800;
      background: linear-gradient(135deg, #2c3e50 0%, #667eea 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 6px;
      line-height: 1.2;
      transition: all 0.3s ease;
    }

    .freelancer-card:hover .name {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .speciality {
      font-size: 1rem;
      font-weight: 600;
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 10px;
      padding: 4px 12px;
      background-color: rgba(240, 147, 251, 0.1);
      border-radius: 20px;
      display: inline-block;
      border: 1px solid rgba(240, 147, 251, 0.3);
    }

    .rating {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 15px;
    }

    .stars {
      display: flex;
      gap: 3px;
    }

    .star {
      font-size: 1rem;
      background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      filter: drop-shadow(0 2px 4px rgba(255, 215, 0, 0.3));
      transition: all 0.3s ease;
    }

    .star.empty {
      background: linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      filter: none;
    }

    .freelancer-card:hover .star {
      transform: scale(1.1);
    }

    .rating-text {
      font-size: 0.85rem;
      background: linear-gradient(135deg, #666 0%, #888 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 600;
    }

    .connect-text {
      font-size: 0.85rem;
      font-style: italic;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 15px;
      padding: 8px 0;
      border-top: 1px solid rgba(102, 126, 234, 0.2);
      border-bottom: 1px solid rgba(102, 126, 234, 0.2);
      text-align: center;
      font-weight: 500;
    }

    .skills-container {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 15px;
    }

    .skill-tag {
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
      color: white;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 700;
      transition: all 0.3s ease;
      cursor: default;
      box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
      border: 1px solid rgba(79, 172, 254, 0.2);
    }

    .skill-tag:nth-child(even) {
      background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
      box-shadow: 0 4px 15px rgba(250, 112, 154, 0.3);
      border: 1px solid rgba(250, 112, 154, 0.2);
    }

    .skill-tag:nth-child(3n) {
      background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
      box-shadow: 0 4px 15px rgba(48, 207, 208, 0.3);
      border: 1px solid rgba(48, 207, 208, 0.2);
    }

    .skill-tag:hover {
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 8px 25px rgba(79, 172, 254, 0.5);
    }

    .availability {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.9rem;
      font-weight: 700;
      margin-bottom: 20px;
      padding: 6px 14px;
      border-radius: 20px;
      border: 1px solid;
      transition: all 0.3s ease;
    }

    .availability.available {
      background: linear-gradient(135deg, rgba(39, 174, 96, 0.1) 0%, rgba(46, 204, 113, 0.1) 100%);
      border-color: rgba(39, 174, 96, 0.3);
      color: #27ae60;
    }

    .availability.busy {
      background: linear-gradient(135deg, rgba(231, 76, 60, 0.1) 0%, rgba(192, 57, 43, 0.1) 100%);
      border-color: rgba(231, 76, 60, 0.3);
      color: #e74c3c;
    }

    .availability-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      animation: pulse 2s infinite;
    }

    .availability.available .availability-dot {
      background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
      box-shadow: 0 0 10px rgba(39, 174, 96, 0.5);
    }

    .availability.busy .availability-dot {
      background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
      box-shadow: 0 0 10px rgba(231, 76, 60, 0.5);
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.6; transform: scale(1.2); }
    }

    .card-actions {
      margin-top: auto;
      display: flex;
      gap: 12px;
    }

    .btn-card {
      flex: 1;
      padding: 14px 20px;
      border-radius: 15px;
      font-weight: 700;
      font-size: 0.95rem;
      text-decoration: none;
      text-align: center;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      cursor: pointer;
      border: none;
      position: relative;
      overflow: hidden;
    }

    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .btn-primary::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s ease;
    }

    .btn-primary:hover::before {
      left: 100%;
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(102, 126, 234, 0.6);
    }

    .btn-secondary {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      color: white;
      box-shadow: 0 6px 20px rgba(240, 147, 251, 0.4);
    }

    .btn-secondary::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s ease;
    }

    .btn-secondary:hover::before {
      left: 100%;
    }

    .btn-secondary:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(240, 147, 251, 0.6);
    }

    /* ================= PAGINATION ================= */
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      margin: 50px 0;
      flex-wrap: wrap;
    }

    .pagination-btn {
      min-width: 45px;
      height: 45px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      background: rgba(255, 255, 255, 0.1);
      color: white;
      border-radius: 12px;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      backdrop-filter: blur(10px);
    }

    .pagination-btn:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2);
    }

    .pagination-btn.active {
      background: var(--primary-gradient);
      border-color: transparent;
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    .pagination-btn.disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .pagination-info {
      color: white;
      font-weight: 500;
      padding: 0 15px;
    }

    /* ================= NO RESULTS ================= */
    .no-results {
      text-align: center;
      padding: 80px 20px;
      color: white;
    }

    .no-results i {
      font-size: 4rem;
      margin-bottom: 20px;
      opacity: 0.7;
    }

    .no-results h3 {
      font-size: 1.8rem;
      margin-bottom: 10px;
      font-weight: 700;
    }

    .no-results p {
      font-size: 1.1rem;
      opacity: 0.8;
      margin-bottom: 30px;
    }

    .btn-reset {
      background: var(--secondary-gradient);
      color: white;
      padding: 15px 30px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      transition: all 0.3s ease;
      box-shadow: 0 8px 25px rgba(240, 147, 251, 0.4);
    }

    .btn-reset:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 35px rgba(240, 147, 251, 0.6);
    }

    /* ================= RESPONSIVE DESIGN ================= */
    @media (max-width: 1200px) {
      .search-container {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .search-btn {
        width: 100%;
        justify-content: center;
      }

      .freelancer-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
      }
    }

    @media (max-width: 768px) {
      nav {
        padding: 12px 20px;
        flex-wrap: wrap;
      }

      nav .logo {
        font-size: 1.4rem;
      }

      nav ul {
        gap: 15px;
      }

      nav ul li a {
        padding: 6px 12px;
        font-size: 0.85rem;
      }

      .nav-actions {
        gap: 10px;
      }

      .hero-text {
        font-size: 2.5rem;
      }

      .hero-subtitle {
        font-size: 1.1rem;
      }

      .search-section {
        padding: 30px 20px;
        margin: -20px 20px 30px;
      }

      .filter-section {
        grid-template-columns: 1fr;
      }

      .freelancer-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .results-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .freelancer-card {
        padding: 25px;
      }

      .card-actions {
        flex-direction: column;
      }

      .pagination {
        gap: 8px;
      }

      .pagination-btn {
        min-width: 40px;
        height: 40px;
        font-size: 0.85rem;
      }
    }

    @media (max-width: 480px) {
      .hero-text {
        font-size: 2rem;
      }

      .search-section {
        padding: 25px 15px;
      }

      .freelancer-card {
        padding: 20px;
      }

      .freelancer-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
      }

      .avatar {
        width: 70px;
        height: 70px;
      }

      .avatar-placeholder {
        width: 70px;
        height: 70px;
        font-size: 1.5rem;
      }
    }

    /* ================= LOADING ANIMATION ================= */
    .loading {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 50px;
    }

    .loading-spinner {
      width: 50px;
      height: 50px;
      border: 4px solid rgba(255, 255, 255, 0.3);
      border-top: 4px solid white;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* ================= ADS SECTION ================= */
    .ads-section {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 25px;
      padding: 40px;
      margin: 50px auto;
      max-width: 1400px;
      box-shadow: 0 20px 60px rgba(31, 38, 135, 0.15);
    }

    .ads-title {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 25px;
      background: var(--primary-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .ads-slider {
      display: grid;
      grid-auto-flow: column;
      grid-auto-columns: minmax(280px, 1fr);
      gap: 20px;
      overflow-x: auto;
      padding: 10px 0 20px;
      scroll-snap-type: x mandatory;
      -webkit-overflow-scrolling: touch;
    }

    .ads-slider::-webkit-scrollbar {
      height: 10px;
    }

    .ads-slider::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .ads-slider::-webkit-scrollbar-thumb {
      background: var(--primary-gradient);
      border-radius: 10px;
    }

    .ad-box {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      scroll-snap-align: start;
      border: 2px solid #e8e8e8;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      cursor: pointer;
      position: relative;
    }

    .ad-box:hover {
      transform: translateY(-10px) scale(1.05);
      box-shadow: 0 20px 60px rgba(31, 38, 135, 0.2);
      border-color: #667eea;
    }

    .ad-box img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      display: block;
      transition: transform 0.5s ease;
    }

    .ad-box:hover img {
      transform: scale(1.1);
    }

    .ads-nav {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 25px;
    }

    .ads-nav button {
      background: var(--primary-gradient);
      border: none;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      cursor: pointer;
      color: white;
      font-size: 1.2rem;
      transition: all 0.3s ease;
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    .ads-nav button:hover {
      transform: scale(1.1);
      box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
    }

    /* Enhanced card animations */
    .freelancer-card {
      animation: fadeInUp 0.6s ease-out;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .freelancer-card:nth-child(1) { animation-delay: 0.05s; }
    .freelancer-card:nth-child(2) { animation-delay: 0.1s; }
    .freelancer-card:nth-child(3) { animation-delay: 0.15s; }
    .freelancer-card:nth-child(4) { animation-delay: 0.2s; }
    .freelancer-card:nth-child(5) { animation-delay: 0.25s; }
    .freelancer-card:nth-child(6) { animation-delay: 0.3s; }
    .freelancer-card:nth-child(7) { animation-delay: 0.35s; }
    .freelancer-card:nth-child(8) { animation-delay: 0.4s; }
    .freelancer-card:nth-child(9) { animation-delay: 0.45s; }
    .freelancer-card:nth-child(10) { animation-delay: 0.5s; }
    .freelancer-card:nth-child(11) { animation-delay: 0.55s; }
    .freelancer-card:nth-child(12) { animation-delay: 0.6s; }
  </style>
</head>

<body>

<?php
  $isLoggedIn = isset($_SESSION['username']);
  $currentUsername = $_SESSION['username'] ?? '';
  $role = strtolower($_SESSION['role'] ?? $_SESSION['user_type'] ?? '');
  $profileLink = ($role === 'client') ? 'client.php' : 'freelancer_form.php';
  $profileLabel = ($role === 'client') ? 'Edit Client Profile' : 'Edit Freelancer Profile';
?>

<!-- NAVIGATION BAR -->
<nav>
    <div class="logo">Watan Freelance System</div>
    <ul>
      <li><a href="dashboard.php">Home</a></li>
      <li><a href="browse_services.php" class="active">Services</a></li>
      <li><a href="about.php">About</a></li>
      <?php if ($isLoggedIn && $role === 'freelancer'): ?>
        <li><a href="freelancer_booking_list.php">Booking List</a></li>
      <?php elseif ($isLoggedIn && $role === 'client'): ?>
        <li><a href="client_booking_list.php">Booking List</a></li>
      <?php endif; ?>
    </ul>
    <div class="nav-actions">
      <?php 
        $isLoggedIn = isset($_SESSION['username']);
        $currentUsername = $_SESSION['username'] ?? '';
        $role = strtolower($_SESSION['role'] ?? $_SESSION['user_type'] ?? '');
        $profileLink = ($role === 'client') ? 'client.php' : 'freelancer_form.php';
        $profileLabel = ($role === 'client') ? 'Edit Client Profile' : 'Edit Freelancer Profile';
      ?>
      <?php if ($isLoggedIn): ?>
          <span>Welcome, <?php echo htmlspecialchars($currentUsername); ?></span>
          <a class="link-signin" href="<?php echo $profileLink; ?>">
            <i class="bi bi-person-gear"></i> <?php echo $profileLabel; ?>
          </a>
          <a class="link-signin" href="logout.php">Logout</a>
      <?php else: ?>
          <a class="link-signin" href="login.php">Sign in</a>
          <button class="btn-join-nav" onclick="location.href='registration.php'">Join</button>
      <?php endif; ?>
    </div>
</nav>

<!-- HERO BANNER -->
<div class="hero-banner">
  <div class="hero-content">
    <h1 class="hero-text">Find Our Freelancers Now!</h1>


    <!-- SEARCH AND FILTER SECTION -->
    <div class="search-section">
      <form method="GET" class="search-container">
        <div class="search-input-wrapper">
          <i class="bi bi-search"></i>
          <input type="text" name="search" class="search-box" placeholder="Search freelancers, skills, or expertise..." 
                 value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="search-btn search-primary">
          <i class="bi bi-search"></i> Search
        </button>
        <button type="button" class="search-btn search-secondary" onclick="clearFilters()">
          <i class="bi bi-arrow-clockwise"></i> Reset
        </button>
      </form>

      <div class="filter-section">
        <div class="filter-group">
          <label class="filter-label">
            <i class="bi bi-funnel"></i> Skills
          </label>
          <select name="skill" class="filter-select" onchange="this.form.submit()">
            <option value="">All Skills</option>
            <?php foreach ($all_skills as $skill): ?>
              <option value="<?= htmlspecialchars($skill) ?>" <?= $skill_filter === $skill ? 'selected' : '' ?>>
                <?= htmlspecialchars($skill) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-group">
          <label class="filter-label">
            <i class="bi bi-calendar-check"></i> Availability
          </label>
          <select name="availability" class="filter-select" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="Available" <?= $availability_filter === 'Available' ? 'selected' : '' ?>>Available</option>
            <option value="Busy" <?= $availability_filter === 'Busy' ? 'selected' : '' ?>>Busy</option>
          </select>
        </div>

        <div class="filter-group">
          <label class="filter-label">
            <i class="bi bi-sort-down"></i> Sort By
          </label>
          <select name="sort" class="filter-select" onchange="this.form.submit()">
            <option value="recent" <?= $sort_by === 'recent' ? 'selected' : '' ?>>Most Recent</option>
            <option value="rating" <?= $sort_by === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
            <option value="name" <?= $sort_by === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
            <option value="availability" <?= $sort_by === 'availability' ? 'selected' : '' ?>>Available First</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
  <div class="results-header">
    <div class="results-count">
      <i class="bi bi-star-fill"></i>
      <span><?= number_format($total_freelancers) ?> Freelancers Found</span>
    </div>
  </div>

  <?php if (!empty($freelancers)): ?>
    <div class="freelancer-grid">
      <?php foreach ($freelancers as $f): 
          $bio = htmlspecialchars($f['bio']);
          $speciality = explode('.', $bio)[0];
          $avg_rating = round($f['avg_rating'], 1);
          $review_count = intval($f['review_count']);
      ?>
        <div class="freelancer-card">
          <div class="freelancer-header">
            <div class="avatar-container">
              <?php if (!empty($f['profile_picture']) && file_exists('uploads/' . $f['profile_picture'])): ?>
                <img src="uploads/<?= htmlspecialchars($f['profile_picture']) ?>" class="avatar" alt="<?= htmlspecialchars($f['name']) ?>">
              <?php else: ?>
                <div class="avatar-placeholder">
                  <?= strtoupper(substr(htmlspecialchars($f['name']), 0, 2)) ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="freelancer-info">
              <div class="name"><?= htmlspecialchars($f['name']) ?></div>
              <div class="speciality"><?= $speciality ?></div>
              <div class="rating">
                <div class="stars">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star-fill star <?= $i <= $avg_rating ? '' : 'empty' ?>"></i>
                  <?php endfor; ?>
                </div>
                <span class="rating-text"><?= $avg_rating ?> (<?= $review_count ?> reviews)</span>
              </div>
            </div>
          </div>

          <div class="connect-text">
            Connect with talented professionals for your next project
          </div>

          <div class="skills-container">
            <?php 
              $skills = array_slice(explode(',', $f['skills']), 0, 4);
              foreach ($skills as $s): ?>
                <span class="skill-tag"><?= htmlspecialchars(trim($s)) ?></span>
            <?php endforeach; ?>
          </div>

          <div class="availability <?= strtolower($f['availability']) === 'available' ? 'available' : 'busy' ?>">
            <span class="availability-dot"></span>
            <i class="bi bi-circle-fill"></i>
            <?= htmlspecialchars($f['availability']) ?>
          </div>

          <div class="card-actions">
            <a href="freelancer_details.php?user_id=<?= $f['user_id'] ?>" class="btn-card btn-primary">
              <i class="bi bi-eye"></i> View Profile
            </a>
            <a href="booking.php?freelancer_id=<?= $f['user_id'] ?>" class="btn-card btn-secondary">
              <i class="bi bi-calendar-plus"></i> Book Now
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-btn">
            <i class="bi bi-chevron-left"></i>
          </a>
        <?php else: ?>
          <span class="pagination-btn disabled">
            <i class="bi bi-chevron-left"></i>
          </span>
        <?php endif; ?>

        <?php 
          $start_page = max(1, $page - 2);
          $end_page = min($total_pages, $page + 2);
          
          for ($i = $start_page; $i <= $end_page; $i++): 
        ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
             class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-btn">
            <i class="bi bi-chevron-right"></i>
          </a>
        <?php else: ?>
          <span class="pagination-btn disabled">
            <i class="bi bi-chevron-right"></i>
          </span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <div class="no-results">
      <i class="bi bi-search"></i>
      <h3>No Freelancers Found</h3>
      <p>Try adjusting your search criteria or filters to find more matches.</p>
      <a href="browse_services.php" class="btn-reset">
        <i class="bi bi-arrow-clockwise"></i> Clear All Filters
      </a>
    </div>
  <?php endif; ?>
</div>

<!-- Ads Section -->
<section class="ads-section">
  <h2 class="ads-title">
    <i class="bi bi-megaphone-fill"></i> Featured Services
  </h2>
  <div class="ads-slider">
    <div class="ad-box">
      <img src="Iklan/iklan_3danimation.jpg" alt="3D Animation Services">
    </div>
    <div class="ad-box">
      <img src="Iklan/iklan_emcee.jpg" alt="EMCEE Services">
    </div>
    <div class="ad-box">
      <img src="Iklan/iklan_graphicdesign.jpg" alt="Graphic Design Services">
    </div>
    <div class="ad-box">
      <img src="Iklan/iklan_servispengiklanan.jpg" alt="Advertising Services">
    </div>
    <div class="ad-box">
      <img src="Iklan/iklan_videoediting.jpg" alt="Video Editing Services">
    </div>
  </div>
  <div class="ads-nav">
    <button class="ads-prev" aria-label="Previous">
      <i class="bi bi-chevron-left"></i>
    </button>
    <button class="ads-next" aria-label="Next">
      <i class="bi bi-chevron-right"></i>
    </button>
  </div>
</section>

<script>
// Clear filters function
function clearFilters() {
  window.location.href = 'browse_services.php';
}

// Ads slider controls and auto-scroll
(function() {
  var slider = document.querySelector('.ads-slider');
  if (!slider) return;
  var prevBtn = document.querySelector('.ads-prev');
  var nextBtn = document.querySelector('.ads-next');
  var itemWidth = 320;
  
  function scrollByAmount(amount) {
    slider.scrollBy({ left: amount, behavior: 'smooth' });
  }
  
  prevBtn && prevBtn.addEventListener('click', function(){ scrollByAmount(-itemWidth); });
  nextBtn && nextBtn.addEventListener('click', function(){ scrollByAmount(itemWidth); });

  var auto; 
  var intervalMs = 4000;
  function startAuto() { 
    auto = setInterval(function(){ 
      var currentScroll = slider.scrollLeft;
      var maxScroll = slider.scrollWidth - slider.clientWidth;
      
      if (currentScroll >= maxScroll - 10) {
        slider.scrollTo({ left: 0, behavior: 'smooth' });
      } else {
        scrollByAmount(itemWidth); 
      }
    }, intervalMs); 
  }
  
  function stopAuto() { 
    if (auto) clearInterval(auto); 
  }
  
  slider.addEventListener('mouseenter', stopAuto);
  slider.addEventListener('mouseleave', startAuto);
  startAuto();

  document.addEventListener('visibilitychange', function(){
    if (document.hidden) { 
      stopAuto(); 
    } else { 
      startAuto(); 
    }
  });
})();

// Enhanced card interactions and animations
document.addEventListener('DOMContentLoaded', function() {
  // Animate cards on scroll
  var observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, observerOptions);

  document.querySelectorAll('.freelancer-card').forEach(function(card) {
    card.style.opacity = '0';
    card.style.transform = 'translateY(30px)';
    card.style.transition = 'all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
    observer.observe(card);
  });

  // Enhanced hover effects
  document.querySelectorAll('.freelancer-card').forEach(function(card) {
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-10px) scale(1.02)';
    });
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0) scale(1)';
    });
  });

  // Search input focus effect
  var searchInput = document.querySelector('.search-box');
  if (searchInput) {
    searchInput.addEventListener('focus', function() {
      this.parentElement.parentElement.style.transform = 'scale(1.02)';
    });
    searchInput.addEventListener('blur', function() {
      this.parentElement.parentElement.style.transform = 'scale(1)';
    });
  }

  // Filter select animations
  document.querySelectorAll('.filter-select').forEach(function(select) {
    select.addEventListener('change', function() {
      this.style.transform = 'scale(0.95)';
      setTimeout(() => {
        this.style.transform = 'scale(1)';
      }, 150);
    });
  });

  // Button click effects
  document.querySelectorAll('.btn-card, .search-btn, .pagination-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      var ripple = document.createElement('span');
      ripple.style.position = 'absolute';
      ripple.style.borderRadius = '50%';
      ripple.style.background = 'rgba(255, 255, 255, 0.6)';
      ripple.style.width = ripple.style.height = '40px';
      ripple.style.top = (e.clientY - this.offsetTop - 20) + 'px';
      ripple.style.left = (e.clientX - this.offsetLeft - 20) + 'px';
      ripple.style.animation = 'ripple 0.6s ease-out';
      ripple.style.pointerEvents = 'none';
      
      this.style.position = 'relative';
      this.style.overflow = 'hidden';
      this.appendChild(ripple);
      
      setTimeout(() => ripple.remove(), 600);
    });
  });
});

// Add ripple animation
var style = document.createElement('style');
style.textContent = `
  @keyframes ripple {
    to {
      transform: scale(4);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);

// Smooth scroll for pagination
document.querySelectorAll('.pagination-btn').forEach(function(btn) {
  if (!btn.classList.contains('disabled')) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      window.location.href = this.href;
    });
  }
});
</script>

</body>
</html>
