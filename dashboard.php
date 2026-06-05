<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Redirect admin users to admin dashboard
if (strtolower($_SESSION['role'] ?? '') === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

// --- 1. EXPANDED MOCK DATABASE (To ensure NO empty results) ---
$all_services_db = [
    // --- Programming & Tech ---
    ['title' => 'PHP Tutor & Coaching', 'category' => 'Programming & Tech', 'icon' => 'bi-code-square', 'desc' => 'Master PHP and Web Development with 1-on-1 tutoring.'],
    ['title' => 'Full Stack Web Development', 'category' => 'Programming & Tech', 'icon' => 'bi-laptop', 'desc' => 'Custom websites using React, Node, and PHP.'],
    ['title' => 'Python Scripting Automation', 'category' => 'Programming & Tech', 'icon' => 'bi-filetype-py', 'desc' => 'Automate boring tasks with Python scripts.'],
    ['title' => 'Mobile App Development', 'category' => 'Programming & Tech', 'icon' => 'bi-phone', 'desc' => 'iOS and Android apps built with Flutter.'],
    ['title' => 'WordPress Website Setup', 'category' => 'Programming & Tech', 'icon' => 'bi-wordpress', 'desc' => 'Fast and secure WordPress installation and design.'],

    // --- Graphics & Design ---
    ['title' => 'Modern Logo Design', 'category' => 'Graphics & Design', 'icon' => 'bi-palette', 'desc' => 'Minimalist and professional logos for startups.'],
    ['title' => 'Interior Architecture Design', 'category' => 'Graphics & Design', 'icon' => 'bi-house-heart', 'desc' => '3D modeling and floor plans for homes.'],
    ['title' => 'Social Media Banners', 'category' => 'Graphics & Design', 'icon' => 'bi-images', 'desc' => 'Eye-catching banners for FB, Instagram, and LinkedIn.'],
    ['title' => 'Flyer & Brochure Design', 'category' => 'Graphics & Design', 'icon' => 'bi-file-earmark-richtext', 'desc' => 'Print-ready marketing materials.'],

    // --- Digital Marketing ---
    ['title' => 'SEO Optimization', 'category' => 'Digital Marketing', 'icon' => 'bi-google', 'desc' => 'Rank higher on Google with on-page SEO.'],
    ['title' => 'Social Media Management', 'category' => 'Digital Marketing', 'icon' => 'bi-instagram', 'desc' => 'Manage your accounts and grow your audience.'],
    ['title' => 'Google Ads Setup', 'category' => 'Digital Marketing', 'icon' => 'bi-graph-up', 'desc' => 'Targeted ad campaigns to drive traffic.'],

    // --- Writing & Translation ---
    ['title' => 'English to Malay Translation', 'category' => 'Writing & Translation', 'icon' => 'bi-translate', 'desc' => 'Accurate document and article translation.'],
    ['title' => 'Resume & Cover Letter', 'category' => 'Writing & Translation', 'icon' => 'bi-file-person', 'desc' => 'Professional writing to help you get hired.'],
    ['title' => 'Blog Content Writing', 'category' => 'Writing & Translation', 'icon' => 'bi-pen', 'desc' => 'Engaging articles for your company blog.'],

    // --- Video & Animation ---
    ['title' => 'Professional Video Editing', 'category' => 'Video & Animation', 'icon' => 'bi-camera-video', 'desc' => 'Youtube, TikTok, and corporate video editing.'],
    ['title' => 'UGC Video Creation', 'category' => 'Video & Animation', 'icon' => 'bi-phone-vibrate', 'desc' => 'Authentic user-generated content for brands.'],
    ['title' => '3D Product Animation', 'category' => 'Video & Animation', 'icon' => 'bi-box', 'desc' => 'Showcase your product in 3D.'],

    // --- AI Services ---
    ['title' => 'AI Chatbot Integration', 'category' => 'AI Services', 'icon' => 'bi-robot', 'desc' => 'Customer support bots for your website.'],
    ['title' => 'Midjourney Art Generation', 'category' => 'AI Services', 'icon' => 'bi-stars', 'desc' => 'Custom AI-generated artwork and assets.'],

    // --- Music & Audio ---
    ['title' => 'Voice Over Talent', 'category' => 'Music & Audio', 'icon' => 'bi-mic', 'desc' => 'Professional male/female voice overs.'],
    ['title' => 'Audio Mixing & Mastering', 'category' => 'Music & Audio', 'icon' => 'bi-music-note', 'desc' => 'Clean up your podcast or song tracks.'],

    // --- Business ---
    ['title' => 'Data Entry Specialist', 'category' => 'Business', 'icon' => 'bi-keyboard', 'desc' => 'Fast and accurate Excel/Word data entry.'],
    ['title' => 'Virtual Assistant', 'category' => 'Business', 'icon' => 'bi-headset', 'desc' => 'Email management, scheduling, and admin tasks.'],
    ['title' => 'Market Research', 'category' => 'Business', 'icon' => 'bi-search', 'desc' => 'Detailed research on your competitors.'],

    // --- Consulting ---
    ['title' => 'Financial Consulting', 'category' => 'Consulting', 'icon' => 'bi-cash-coin', 'desc' => 'Budgeting and tax advice for freelancers.'],
    ['title' => 'Business Strategy', 'category' => 'Consulting', 'icon' => 'bi-briefcase', 'desc' => 'Plan your startup growth path.'],

    // --- Data ---
    ['title' => 'Data Analysis & Visualization', 'category' => 'Data', 'icon' => 'bi-bar-chart', 'desc' => 'Turn raw data into PowerBI dashboards.'],
    ['title' => 'Database Management', 'category' => 'Data', 'icon' => 'bi-database', 'desc' => 'SQL database setup and maintenance.'],

    // --- Photography ---
    ['title' => 'Event Photography', 'category' => 'Photography', 'icon' => 'bi-camera', 'desc' => 'Weddings, corporate events, and parties.'],
    ['title' => 'Product Photography', 'category' => 'Photography', 'icon' => 'bi-box-seam', 'desc' => 'High-quality studio shots for e-commerce.'],
];

// --- 2. SEARCH & CATEGORY LOGIC ---
$search_query = "";
$search_results = [];
$is_search_active = false;
$search_title = "";

// CASE A: User used the Search Bar
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $is_search_active = true;
    $search_query = trim($_GET['search']);
    $search_title = "Search Results for \"" . htmlspecialchars($search_query) . "\"";

    foreach ($all_services_db as $service) {
        // Search in Title OR Category OR Description (Case Insensitive)
        // This ensures "Development" finds "Web Development"
        if (stripos($service['title'], $search_query) !== false || 
            stripos($service['category'], $search_query) !== false ||
            stripos($service['desc'], $search_query) !== false) {
            $search_results[] = $service;
        }
    }
} 
// CASE B: User clicked a Category Box
elseif (isset($_GET['category']) && !empty(trim($_GET['category']))) {
    $is_search_active = true;
    $search_query = trim($_GET['category']);
    $search_title = "Category: " . htmlspecialchars($search_query);

    foreach ($all_services_db as $service) {
        // Strict match for category name
        if (strtolower($service['category']) === strtolower($search_query)) {
            $search_results[] = $service;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Watan Freelance System</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

    /* Navigation Bar */
    nav {
      background-color: rgba(255,255,255,0.9);
      backdrop-filter: saturate(180%) blur(6px);
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
      display: flex; justify-content: space-between; align-items: center;
      padding: 14px 60px; position: sticky; top: 0; z-index: 10;
      transition: box-shadow .2s ease, background-color .2s ease;
    }
    nav .logo { font-size: 1.6rem; font-weight: 700; color: #7a5af8; }
    nav ul { display: flex; list-style: none; gap: 25px; }
    nav ul li a { text-decoration: none; color: #333; font-weight: 500; position: relative; padding-bottom: 4px; transition: color .2s ease; }
    nav ul li a:hover { color: #7a5af8; }
    nav ul li a::after { content: ''; position: absolute; left: 0; bottom: 0; width: 0; height: 2px; background: #7a5af8; transition: width .2s ease; }
    nav ul li a:hover::after { width: 100%; }
    .nav-actions { display: flex; align-items: center; gap: 10px; }
    .btn-join-nav { background: #7a5af8; color: #fff; border: none; padding: 8px 16px; border-radius: 999px; font-weight: 600; cursor: pointer; transition: background .2s ease; }
    .btn-join-nav:hover { background: #6948f0; }
    .link-signin { color: #333; text-decoration: none; font-weight: 500; padding: 6px 10px; border-radius: 8px; transition: color .2s ease, background-color .2s ease; }
    .link-signin:hover { color: #7a5af8; background-color: #f3f1ff; }

    /* Hero Section */
    .hero {
      background: url('background_dashboard.jpg') no-repeat center center/cover;
      height: 85vh; display: flex; align-items: center; color: white;
      position: relative; padding: 0 60px; animation: fadeIn 1s ease-in;
    }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .hero::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.55); }
    .hero-content { position: relative; z-index: 1; max-width: 600px; }
    .hero h2 { font-size: 3rem; font-weight: 700; color: #cbb2ff; margin-bottom: 15px; }
    .hero p { font-size: 1.3rem; margin-bottom: 25px; color: #f8f8f8; }

    /* SEARCH BAR FORM */
    form.search-bar {
      display: flex; align-items: center; background-color: #fff;
      border-radius: 999px; overflow: hidden; width: 100%; max-width: 600px;
      border: 1px solid #e6e6e6; transition: box-shadow .2s ease, border-color .2s ease;
    }
    form.search-bar:hover { box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
    form.search-bar:focus-within { border-color: #7a5af8; box-shadow: 0 0 0 4px rgba(122,90,248,0.12); }
    form.search-bar input { border: none; padding: 14px 18px; width: 100%; font-size: 1rem; outline: none; color: #333; flex: 1; }
    form.search-bar button {
      background: #7a5af8; border: none; color: #fff; padding: 0 22px; font-size: 1rem;
      cursor: pointer; display: flex; align-items: center; gap: 8px; justify-content: center;
      transition: background .15s ease, transform .1s ease; height: 48px; min-width: 120px;
      font-weight: 600; border-left: 1px solid #6948f0; border-radius: 0 999px 999px 0;
    }
    form.search-bar button:hover { background: #6948f0; }

    /* Suggestions */
    .suggestions-bar { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
    .suggestion-chip {
      display: inline-flex; align-items: center; gap: 6px; color: #fff;
      background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.6);
      padding: 8px 14px; border-radius: 999px; font-size: 0.95rem; cursor: pointer;
      transition: background .2s ease, border-color .2s ease; text-decoration: none;
    }
    .suggestion-chip:hover { background: rgba(0,0,0,0.6); border-color: #fff; }

    /* Search/Category Results Section */
    .search-results-section { padding: 40px 60px; background-color: #f0f2f5; border-bottom: 1px solid #e0e0e0; }
    .search-results-section h2 { font-size: 1.8rem; margin-bottom: 20px; color: #333; }
    .search-empty { text-align: center; color: #666; font-size: 1.1rem; padding: 20px; }
    
    .category-result {
        background-color: #fff; border: 1px solid #ddd; padding: 25px;
        border-radius: 15px; transition: 0.3s; text-align: left; display: flex;
        align-items: center; gap: 15px; text-decoration: none; color: inherit;
    }
    .category-result:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(122,90,248,0.15); border-color: #7a5af8; }
    .category-result i {
        font-size: 2rem; color: #7a5af8; background: #f3f1ff; width: 60px; height: 60px;
        display: grid; place-items: center; border-radius: 50%; flex-shrink: 0;
    }
    .result-info h3 { font-size: 1.1rem; margin-bottom: 5px; }
    .result-info p { font-size: 0.9rem; color: #666; }

    /* Categories Grid */
    .categories { padding: 60px 60px; text-align: center; }
    .categories h2 { font-size: 2rem; margin-bottom: 40px; font-weight: 600; }
    .category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 25px; }
    
    /* UPDATED: Category Boxes are now Links */
    a.category {
      background-color: #f8f9fa; padding: 25px 15px; border-radius: 15px;
      transition: 0.3s; cursor: pointer; border: 2px solid transparent;
      text-decoration: none; color: #333; display: block;
    }
    a.category:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0,0,0,0.15); }
    a.category i { font-size: 2rem; margin-bottom: 10px; display: block; }
    
    /* Category Colors */
    a.category:nth-child(1) { background-color: #e3f2fd; border-color: #2196f3; }
    a.category:nth-child(2) { background-color: #f3e5f5; border-color: #9c27b0; }
    a.category:nth-child(3) { background-color: #fff3e0; border-color: #ff9800; }
    a.category:nth-child(4) { background-color: #e8f5e9; border-color: #4caf50; }
    a.category:nth-child(5) { background-color: #fce4ec; border-color: #e91e63; }
    a.category:nth-child(6) { background-color: #e0f2f1; border-color: #009688; }
    a.category:nth-child(7) { background-color: #fff9c4; border-color: #fbc02d; }
    a.category:nth-child(8) { background-color: #ede7f6; border-color: #673ab7; }
    a.category:nth-child(9) { background-color: #ffebee; border-color: #f44336; }

    /* Popular Section */
    .popular { padding: 60px 60px; text-align: center; background-color: #f5f5f5; }
    .popular h2 { font-size: 2rem; margin-bottom: 30px; font-weight: 600; }
    .popular-buttons { display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; }
    .popular-buttons button {
      background-color: white; border: 2px solid #7a5af8; color: #7a5af8; padding: 10px 20px;
      border-radius: 50px; font-size: 1rem; font-weight: 500; cursor: pointer; transition: 0.3s;
    }
    .popular-buttons button:hover { background-color: #7a5af8; color: white; }

    /* Ads Section */
    .ads-section { padding: 40px 60px; background: #fff; position: relative; }
    .ads-title { font-size: 1.6rem; font-weight: 600; margin-bottom: 16px; }
    .ads-slider {
      display: grid; grid-auto-flow: column; grid-auto-columns: minmax(260px, 1fr); gap: 16px;
      overflow-x: auto; padding-bottom: 6px; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch;
    }
    .ads-slider::-webkit-scrollbar { height: 8px; }
    .ads-slider::-webkit-scrollbar-thumb { background: #cfcfcf; border-radius: 8px; }
    .ad-box {
      background: #f8f9fa; border-radius: 12px; overflow: hidden; scroll-snap-align: start;
      border: 1px solid #eee; transition: transform 0.3s ease, box-shadow 0.3s ease; cursor: pointer;
    }
    .ad-box:hover { transform: translateY(-8px) scale(1.02); box-shadow: 0 8px 20px rgba(122, 90, 248, 0.3); }
    .ad-box img { width: 100%; height: 160px; object-fit: cover; display: block; }
    
    /* Testimonials */
    .made-on { padding: 60px 60px; background: #f5f5f5; }
    .section-heading { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; font-size: 1.8rem; font-weight: 600; }
    .testimonials { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
    .testimonial { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 20px; }
    .testimonial .stars { color: #ffc107; margin-bottom: 8px; }
    .testimonial .meta { display: flex; align-items: center; gap: 10px; margin-top: 12px; color: #666; font-size: 0.9rem; }

    /* CTA */
    .cta-services { padding: 60px 60px; background: #0d084d; color: #fff; text-align: center; }
    .cta-services h2 { font-size: 2.2rem; margin-bottom: 14px; }
    .cta-services p { color: #e6e6e6; margin-bottom: 22px; font-size: 1.05rem; }
    .btn-primary-join { background: #7a5af8; color: #fff; border: none; padding: 12px 26px; border-radius: 999px; cursor: pointer; font-weight: 600; transition: background .2s ease; }
    .btn-primary-join:hover { background: #6948f0; }

    /* MEGA CATEGORIES (EXPLORE) - Updated CSS */
    .mega-categories { padding: 60px 60px 20px; background: #fff; border-top: 1px solid #eee; }
    .mega-links {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 30px;
    }
    .mega-links h4 { font-size: 1.1rem; margin-bottom: 15px; color: #111; font-weight: 700; }
    .mega-links ul { list-style: none; padding: 0; }
    .mega-links li { margin-bottom: 10px; }
    .mega-links a { color: #555; text-decoration: none; font-size: 0.95rem; transition: color 0.2s; }
    .mega-links a:hover { color: #7a5af8; text-decoration: underline; }

    /* Footer */
    footer { background-color: #111; color: #ccc; text-align: center; padding: 25px; font-size: 0.9rem; }
    footer a { color: #7a5af8; text-decoration: none; font-weight: 500; }

    /* Video & Audio */
    .video-section { padding: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); text-align: center; color: white; }
    .video-container { max-width: 800px; margin: 0 auto; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
    .audio-control { position: fixed; bottom: 20px; right: 20px; background: rgba(122, 90, 248, 0.9); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 1000; display: flex; align-items: center; justify-content: center; }

    /* Smooth Scroll & Animations */
    html { scroll-behavior: smooth; }
    .category, .testimonial, .ad-box { opacity: 0; transform: translateY(20px); transition: opacity 0.6s ease, transform 0.6s ease; }
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

  <nav>
    <div class="logo">Watan Freelance System</div>
    <ul>
      <li><a href="dashboard.php">Home</a></li>
      <li><a href="browse_services.php">Services</a></li>
      <li><a href="about.php">About</a></li>
      <?php if ($isLoggedIn && $role === 'freelancer'): ?>
        <li><a href="freelancer_booking_list.php">Booking List</a></li>
      <?php elseif ($isLoggedIn && $role === 'client'): ?>
        <li><a href="client_booking_list.php">Booking List</a></li>
      <?php endif; ?>
    </ul>
    <div class="nav-actions">
      <?php if ($isLoggedIn): ?>
          <span>Welcome, <?php echo htmlspecialchars($currentUsername); ?></span>
          <a class="link-signin" href="<?php echo $profileLink; ?>" style="background-color: #f3f1ff; color: #7a5af8; font-weight: 600;">
            <i class="bi bi-person-gear"></i> <?php echo $profileLabel; ?>
          </a>
          <a class="link-signin" href="logout.php">Logout</a>
      <?php else: ?>
          <a class="link-signin" href="login.php">Sign in</a>
          <button class="btn-join-nav" onclick="location.href='registration.php'">Join</button>
      <?php endif; ?>
    </div>
  </nav>

  <section class="hero">
    <div class="hero-content">
      <h2>Welcome to Watan Freelance System</h2>
      <p>Hire smart. Work faster. Achieve more.</p>
      
      <form class="search-bar" action="dashboard.php" method="GET">
        <input type="text" name="search" placeholder="Search for 'Data Entry', 'PHP Tutor'..." value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit"><i class="bi bi-search"></i> Search</button>
      </form>

      <div class="suggestions-bar">
        <a href="dashboard.php?search=website development" class="suggestion-chip">website development <i class="bi bi-arrow-right-short"></i></a>
        <a href="dashboard.php?search=architecture" class="suggestion-chip">architecture & interior design <i class="bi bi-arrow-right-short"></i></a>
        <a href="dashboard.php?search=UGC" class="suggestion-chip">UGC videos <i class="bi bi-arrow-right-short"></i></a>
        <a href="dashboard.php?search=PHP" class="suggestion-chip">Learn PHP <i class="bi bi-arrow-right-short"></i></a>
      </div>
    </div>
  </section>

  <?php if ($is_search_active): ?>
  <section class="search-results-section" id="results">
      <h2><?php echo $search_title; ?></h2>
      
      <?php if (count($search_results) > 0): ?>
          <div class="category-grid">
              <?php foreach ($search_results as $result): ?>
                  <a href="#" class="category-result">
                      <i class="bi <?php echo $result['icon']; ?>"></i>
                      <div class="result-info">
                          <h3><?php echo htmlspecialchars($result['title']); ?></h3>
                          <p><?php echo htmlspecialchars($result['desc']); ?></p>
                          <small style="color:#7a5af8; font-weight:600;"><?php echo htmlspecialchars($result['category']); ?></small>
                      </div>
                  </a>
              <?php endforeach; ?>
          </div>
          <div style="margin-top:20px; text-align:center;">
            <a href="dashboard.php" style="color:#7a5af8; text-decoration:none; font-weight:600;">Clear Search</a>
          </div>
      <?php else: ?>
          <div class="search-empty">
              <i class="bi bi-emoji-frown" style="font-size: 2rem; display:block; margin-bottom:10px;"></i>
              No services found matching your criteria. <br>
              Try searching for "Data Entry" or "PHP".
              <br><br>
              <a href="dashboard.php" style="color:#7a5af8; font-weight:600;">View All Categories</a>
          </div>
      <?php endif; ?>
  </section>
  <script>
      // Auto-scroll to results if searching
      window.onload = function() {
          const element = document.getElementById("results");
          if(element) element.scrollIntoView({behavior: "smooth", block: "start"});
      };
  </script>
  <?php endif; ?>

  <section class="categories">
    <h2>Browse by Category</h2>
    <div class="category-grid">
      <a href="dashboard.php?category=Programming %26 Tech" class="category"><i class="bi bi-code-slash"></i><br>Programming & Tech</a>
      <a href="dashboard.php?category=Graphics %26 Design" class="category"><i class="bi bi-brush"></i><br>Graphics & Design</a>
      <a href="dashboard.php?category=Digital Marketing" class="category"><i class="bi bi-megaphone"></i><br>Digital Marketing</a>
      <a href="dashboard.php?category=Writing %26 Translation" class="category"><i class="bi bi-pen"></i><br>Writing & Translation</a>
      <a href="dashboard.php?category=Video %26 Animation" class="category"><i class="bi bi-camera-reels"></i><br>Video & Animation</a>
      <a href="dashboard.php?category=AI Services" class="category"><i class="bi bi-cpu"></i><br>AI Services</a>
      <a href="dashboard.php?category=Music %26 Audio" class="category"><i class="bi bi-music-note-beamed"></i><br>Music & Audio</a>
      <a href="dashboard.php?category=Business" class="category"><i class="bi bi-briefcase"></i><br>Business</a>
      <a href="dashboard.php?category=Consulting" class="category"><i class="bi bi-chat-square-dots"></i><br>Consulting</a>
    </div>
  </section>

  <section class="popular">
    <h2>Popular Services</h2>
    <div class="popular-buttons">
      <button onclick="location.href='dashboard.php?search=Design'">Graphics & Design</button>
      <button onclick="location.href='dashboard.php?search=Website'">Website Development</button>
      <button onclick="location.href='dashboard.php?search=Video'">Video Editing</button>
      <button onclick="location.href='dashboard.php?search=Development'">Software Development</button>
      <button onclick="location.href='dashboard.php?search=Photography'">Photography</button>
      <button onclick="location.href='dashboard.php?search=Architecture'">Architecture & Interior Design</button>
    </div>
  </section>

  <section class="ads-section">
    <h2 class="ads-title"><i class="bi bi-megaphone-fill"></i> Sponsored Ads</h2>
    <div class="ads-slider">
      <div class="ad-box"><img src="Iklan/iklan_3danimation.jpg" alt="3D Animation Services"></div>
      <div class="ad-box"><img src="Iklan/iklan_emcee.jpg" alt="EMCEE Services"></div>
      <div class="ad-box"><img src="Iklan/iklan_graphicdesign.jpg" alt="Graphic Design Services"></div>
      <div class="ad-box"><img src="Iklan/iklan_servispengiklanan.jpg" alt="Advertising Services"></div>
      <div class="ad-box"><img src="Iklan/iklan_videoediting.jpg" alt="Video Editing Services"></div>
    </div>
    <div class="ads-nav">
      <button class="ads-prev" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>
      <button class="ads-next" aria-label="Next"><i class="bi bi-chevron-right"></i></button>
    </div>
  </section>

  <section class="made-on">
    <div class="section-heading"><i class="bi bi-chat-quote"></i> Made On Watan Freelance System</div>
    <div class="testimonials">
      <div class="testimonial">
        <div class="stars"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i></div>
        <p>We launched our brand visuals in record time. The designer understood our vision perfectly.</p>
        <div class="meta"><i class="bi bi-person-circle"></i> <span>Rasha • Startup Founder</span></div>
      </div>
      <div class="testimonial">
        <div class="stars"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
        <p>SEO results exceeded expectations. Organic traffic doubled within 3 months.</p>
        <div class="meta"><i class="bi bi-person-circle"></i> <span>Omar • E‑commerce Owner</span></div>
      </div>
      <div class="testimonial">
        <div class="stars"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star"></i></div>
        <p>Great communication and pixel‑perfect web app delivery. Will hire again.</p>
        <div class="meta"><i class="bi bi-person-circle"></i> <span>Lina • Product Manager</span></div>
      </div>
    </div>
  </section>

  <section class="cta-services">
    <h2>Great work begins with great freelancers</h2>
    <p>From design to development to marketing — hire experts to move your work forward.</p>
    <button class="btn-primary-join">Join us now</button>
  </section>

  <section class="mega-categories">
    <h2 class="ads-title">Explore</h2>
    <div class="mega-links">
      <div>
        <h4>Categories</h4>
        <ul>
          <li><a href="#">Graphics & Design</a></li>
          <li><a href="#">Digital Marketing</a></li>
          <li><a href="#">Writing & Translation</a></li>
          <li><a href="#">Video & Animation</a></li>
          <li><a href="#">Music & Audio</a></li>
          <li><a href="#">Programming & Tech</a></li>
          <li><a href="#">AI Services</a></li>
          <li><a href="#">Consulting</a></li>
          <li><a href="#">Data</a></li>
          <li><a href="#">Business</a></li>
          <li><a href="#">Personal Growth & Hobbies</a></li>
          <li><a href="#">Photography</a></li>
          <li><a href="#">Finance</a></li>
          <li><a href="#">End-to-End Projects</a></li>
          <li><a href="#">Service Catalog</a></li>
        </ul>
      </div>
      <div>
        <h4>For Clients</h4>
        <ul>
          <li><a href="#">How Watan Freelance System Works</a></li>
          <li><a href="#">Customer Success Stories</a></li>
          <li><a href="#">Trust & Safety</a></li>
          <li><a href="#">Quality Guide</a></li>
          <li><a href="#">Watan Freelance System Guides</a></li>
          <li><a href="#">Watan Freelance System Answers</a></li>
          <li><a href="#">Browse Freelance By Skill</a></li>
        </ul>
      </div>
      <div>
        <h4>For Freelancers</h4>
        <ul>
          <li><a href="#">Become a Watan Freelancer</a></li>
          <li><a href="#">Become an Agency</a></li>
          <li><a href="#">Freelancer Equity Program</a></li>
          <li><a href="#">Community Hub</a></li>
          <li><a href="#">Forum</a></li>
          <li><a href="#">Events</a></li>
        </ul>
      </div>
      <div>
        <h4>Business Solutions</h4>
        <ul>
          <li><a href="#">Watan Pro</a></li>
          <li><a href="#">Project Management Service</a></li>
          <li><a href="#">Expert Sourcing Service</a></li>
          <li><a href="#">ClearVoice - Content Marketing</a></li>
          <li><a href="#">AutoDS - Dropshipping Tool</a></li>
          <li><a href="#">AI store builder</a></li>
          <li><a href="#">Watan Logo Maker</a></li>
          <li><a href="#">Contact Sales</a></li>
        </ul>
      </div>
      <div>
        <h4>Company</h4>
        <ul>
          <li><a href="#">About Watan Freelance System</a></li>
          <li><a href="#">Help & Support</a></li>
          <li><a href="#">Social Impact</a></li>
          <li><a href="#">Careers</a></li>
          <li><a href="#">Terms of Service</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Do not sell or share my personal information</a></li>
          <li><a href="#">Partnerships</a></li>
          <li><a href="#">Creator Network</a></li>
          <li><a href="#">Affiliates</a></li>
          <li><a href="#">Invite a Friend</a></li>
          <li><a href="#">Press & News</a></li>
          <li><a href="#">Investor Relations</a></li>
        </ul>
      </div>
    </div>
  </section>

  <section class="video-section">
    <h2 style="margin-bottom: 30px; font-size: 2.5rem;">Discover Our Platform</h2>
    <p style="margin-bottom: 40px; font-size: 1.2rem; opacity: 0.9;">Watch how Watan Freelance System connects talented freelancers with clients worldwide</p>
    <div class="video-container">
      <iframe width="100%" height="450" src="https://www.youtube.com/embed/j8B8c-5yEeY" title="Watan Freelance System Platform Overview" frameborder="0" allowfullscreen style="border-radius: 15px;"></iframe>
    </div>
  </section>

  <button class="audio-control" id="audioToggle" title="Toggle Background Music">
    <i class="bi bi-music-note-beamed" id="audioIcon"></i>
  </button>
  <audio id="backgroundAudio" loop>
    </audio>

  <footer>
    &copy; 2025 <strong>Watan Freelance System</strong>. All Rights Reserved.  
    | <a href="#">Privacy Policy</a> | <a href="#">Terms of Use</a>
  </footer>

  <script>
    // Enhance nav shadow on scroll
    (function() {
      var nav = document.querySelector('nav');
      window.addEventListener('scroll', function() {
        if (window.scrollY > 10) {
          nav.style.boxShadow = '0 4px 12px rgba(0,0,0,0.12)';
          nav.style.backgroundColor = 'rgba(255,255,255,0.95)';
        } else {
          nav.style.boxShadow = '0 2px 5px rgba(0,0,0,0.08)';
          nav.style.backgroundColor = 'rgba(255,255,255,0.9)';
        }
      });
    })();

    // Ads slider controls and auto-scroll
    (function() {
      var slider = document.querySelector('.ads-slider');
      if (!slider) return;
      var prevBtn = document.querySelector('.ads-prev');
      var nextBtn = document.querySelector('.ads-next');
      var itemWidth = 300; 
      
      function scrollByAmount(amount) { slider.scrollBy({ left: amount, behavior: 'smooth' }); }
      
      prevBtn && prevBtn.addEventListener('click', function(){ scrollByAmount(-itemWidth); });
      nextBtn && nextBtn.addEventListener('click', function(){ scrollByAmount(itemWidth); });

      var auto; var intervalMs = 4000;
      function startAuto() { 
        auto = setInterval(function(){ 
          var currentScroll = slider.scrollLeft;
          var maxScroll = slider.scrollWidth - slider.clientWidth;
          if (currentScroll >= maxScroll - 10) { slider.scrollTo({ left: 0, behavior: 'smooth' }); } 
          else { scrollByAmount(itemWidth); }
        }, intervalMs); 
      }
      function stopAuto() { if (auto) clearInterval(auto); }
      slider.addEventListener('mouseenter', stopAuto);
      slider.addEventListener('mouseleave', startAuto);
      startAuto();
    })();

    // Audio control
    (function() {
      var audio = document.getElementById('backgroundAudio');
      var toggleBtn = document.getElementById('audioToggle');
      var audioIcon = document.getElementById('audioIcon');
      var isPlaying = false;

      toggleBtn.addEventListener('click', function() {
        if (isPlaying) {
          audio.pause(); audioIcon.className = 'bi bi-music-note-beamed'; isPlaying = false;
        } else {
          if (audio.src) {
            audio.play().catch(function(err) { console.log('Audio play failed:', err); });
            audioIcon.className = 'bi bi-pause-fill'; isPlaying = true;
          } else { alert('Background music will be available soon!'); }
        }
      });
    })();

    // Interactive category clicks
    document.querySelectorAll('.category').forEach(function(category) {
      category.addEventListener('click', function() {
        this.style.transform = 'scale(0.95)';
        setTimeout(() => { this.style.transform = ''; }, 200);
      });
    });

    // Intersection Observer for fade-in animations
    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.category, .testimonial, .ad-box').forEach(el => observer.observe(el));
  </script>

</body>
</html>