<?php
session_start();
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
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    /* Navigation Bar */
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

    /* Hero Section */
    .hero {
      background: url('background_dashboard.jpg') no-repeat center center/cover;
      height: 85vh;
      display: flex;
      align-items: center;
      color: white;
      position: relative;
      padding: 0 60px;
      animation: fadeIn 1s ease-in;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .hero::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.55);
    }

    .hero-content {
      position: relative;
      z-index: 1;
      max-width: 600px;
    }

    .hero h2 {
      font-size: 3rem;
      font-weight: 700;
      color: #cbb2ff;
      margin-bottom: 15px;
    }

    .hero p {
      font-size: 1.3rem;
      margin-bottom: 25px;
      color: #f8f8f8;
    }

    /* CLEAN SEARCH BAR */
    .search-bar {
      display: flex;
      align-items: center;
      background-color: #fff;
      border-radius: 999px;
      overflow: hidden;
      width: 100%;
      max-width: 600px;
      border: 1px solid #e6e6e6;
      transition: box-shadow .2s ease, border-color .2s ease;
    }

    .search-bar:hover { box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
    .search-bar:focus-within { border-color: #7a5af8; box-shadow: 0 0 0 4px rgba(122,90,248,0.12); }

    .search-bar input {
      border: none;
      padding: 14px 18px;
      width: 100%;
      font-size: 1rem;
      outline: none;
      color: #333;
      flex: 1;
    }

    .search-bar input::placeholder { color: #8a8a8a; }

    .search-bar button {
      background: #7a5af8;
      border: none;
      color: #fff;
      padding: 0 22px;
      font-size: 1rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      justify-content: center;
      transition: background .15s ease, transform .1s ease;
      height: 48px;
      min-width: 120px;
      font-weight: 600;
      border-left: 1px solid #6948f0;
      border-radius: 0 999px 999px 0;
    }

    .search-bar button i { font-size: 1.1rem; }

    .search-bar button:hover { background: #6948f0; }
    .search-bar button:active { transform: translateY(1px); }

    /* Suggestions under search */
    .suggestions-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 14px;
    }
    .suggestion-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #fff;
      background: rgba(0,0,0,0.45);
      border: 1px solid rgba(255,255,255,0.6);
      padding: 8px 14px;
      border-radius: 999px;
      font-size: 0.95rem;
      cursor: pointer;
      transition: background .2s ease, border-color .2s ease;
    }
    .suggestion-chip:hover { background: rgba(0,0,0,0.6); border-color: #fff; }

    /* Categories */
    .categories {
      padding: 60px 60px;
      text-align: center;
    }

    .categories h2 {
      font-size: 2rem;
      margin-bottom: 40px;
      font-weight: 600;
    }

    .category-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 25px;
    }

    .category {
      background-color: #f8f9fa;
      padding: 25px 15px;
      border-radius: 15px;
      transition: 0.3s;
      cursor: pointer;
      border: 2px solid transparent;
    }

    .category:nth-child(1) {
      background-color: #e3f2fd;
      border-color: #2196f3;
    }

    .category:nth-child(2) {
      background-color: #f3e5f5;
      border-color: #9c27b0;
    }

    .category:nth-child(3) {
      background-color: #fff3e0;
      border-color: #ff9800;
    }

    .category:nth-child(4) {
      background-color: #e8f5e9;
      border-color: #4caf50;
    }

    .category:nth-child(5) {
      background-color: #fce4ec;
      border-color: #e91e63;
    }

    .category:nth-child(6) {
      background-color: #e0f2f1;
      border-color: #009688;
    }

    .category:nth-child(7) {
      background-color: #fff9c4;
      border-color: #fbc02d;
    }

    .category:nth-child(8) {
      background-color: #ede7f6;
      border-color: #673ab7;
    }

    .category:nth-child(9) {
      background-color: #ffebee;
      border-color: #f44336;
    }

    .category:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }

    .category i {
      font-size: 2rem;
      margin-bottom: 10px;
    }

    /* Popular */
    .popular {
      padding: 60px 60px;
      text-align: center;
      background-color: #f5f5f5;
    }

    .popular h2 {
      font-size: 2rem;
      margin-bottom: 30px;
      font-weight: 600;
    }

    .popular-buttons {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 15px;
    }

    .popular-buttons button {
      background-color: white;
      border: 2px solid #7a5af8;
      color: #7a5af8;
      padding: 10px 20px;
      border-radius: 50px;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: 0.3s;
    }

    .popular-buttons button:hover {
      background-color: #7a5af8;
      color: white;
    }

    /* Footer */
    footer {
      background-color: #111;
      color: #ccc;
      text-align: center;
      padding: 25px;
      font-size: 0.9rem;
    }

    footer a {
      color: #7a5af8;
      text-decoration: none;
      font-weight: 500;
    }

    footer a:hover {
      text-decoration: underline;
    }

    /* Ads - horizontal scroll */
    .ads-section {
      padding: 40px 60px;
      background: #fff;
      position: relative;
    }

    .ads-title {
      font-size: 1.6rem;
      font-weight: 600;
      margin-bottom: 16px;
    }

    .ads-slider {
      display: grid;
      grid-auto-flow: column;
      grid-auto-columns: minmax(260px, 1fr);
      gap: 16px;
      overflow-x: auto;
      padding-bottom: 6px;
      scroll-snap-type: x mandatory;
      -webkit-overflow-scrolling: touch;
    }

    .ads-slider::-webkit-scrollbar {
      height: 8px;
    }

    .ads-slider::-webkit-scrollbar-thumb {
      background: #cfcfcf;
      border-radius: 8px;
    }

    .ad-box {
      background: #f8f9fa;
      border-radius: 12px;
      overflow: hidden;
      scroll-snap-align: start;
      border: 1px solid #eee;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      cursor: pointer;
      position: relative;
    }

    .ad-box:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 8px 20px rgba(122, 90, 248, 0.3);
    }

    .ad-box img {
      width: 100%;
      height: 160px;
      object-fit: cover;
      display: block;
      transition: transform 0.5s ease;
    }

    .ad-box:hover img {
      transform: scale(1.1);
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(30px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .ad-box {
      animation: slideIn 0.6s ease-out;
    }

    .ad-box:nth-child(1) { animation-delay: 0.1s; }
    .ad-box:nth-child(2) { animation-delay: 0.2s; }
    .ad-box:nth-child(3) { animation-delay: 0.3s; }
    .ad-box:nth-child(4) { animation-delay: 0.4s; }
    .ad-box:nth-child(5) { animation-delay: 0.5s; }

    .ads-nav {
      position: absolute;
      top: 50%;
      left: 60px;
      right: 60px;
      display: flex;
      justify-content: space-between;
      transform: translateY(-50%);
      pointer-events: none;
    }

    .ads-nav button {
      pointer-events: all;
      background: rgba(255,255,255,0.9);
      border: 1px solid #e5e5e5;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      cursor: pointer;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
      transition: background .2s ease;
    }

    .ads-nav button:hover { background: #fff; }

    /* Made On Watan - testimonials */
    .made-on {
      padding: 60px 60px;
      background: #f5f5f5;
    }

    .section-heading {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 24px;
      font-size: 1.8rem;
      font-weight: 600;
    }

    .testimonials {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
    }

    .testimonial {
      background: #fff;
      border: 1px solid #eee;
      border-radius: 12px;
      padding: 20px;
    }

    .testimonial .stars {
      color: #ffc107;
      margin-bottom: 8px;
    }

    .testimonial .meta {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 12px;
      color: #666;
      font-size: 0.9rem;
    }

    /* CTA services */
    .cta-services {
      padding: 60px 60px;
      background: #0d084d;
      color: #fff;
      text-align: center;
    }

    .cta-services h2 {
      font-size: 2.2rem;
      margin-bottom: 14px;
    }

    .cta-services p {
      color: #e6e6e6;
      margin-bottom: 22px;
      font-size: 1.05rem;
    }

    .btn-primary-join {
      background: #7a5af8;
      color: #fff;
      border: none;
      padding: 12px 26px;
      border-radius: 999px;
      cursor: pointer;
      font-weight: 600;
      transition: background .2s ease;
    }

    .btn-primary-join:hover {
      background: #6948f0;
    }

    /* Mega categories (before footer) */
    .mega-categories {
      padding: 50px 60px 20px;
      background: #fff;
      border-top: 1px solid #eee;
    }

    .mega-categories .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 10px 16px;
      margin-bottom: 34px;
    }

    .mega-categories .grid a {
      color: #333;
      text-decoration: none;
      padding: 10px 12px;
      border-radius: 10px;
      background: #f8f9fa;
      display: block;
      border: 1px solid #eee;
    }

    .mega-categories .grid a:hover {
      background: #7a5af8;
      color: #fff;
      border-color: #7a5af8;
    }

    .mega-links {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 28px 22px;
      padding-top: 10px;
      padding-bottom: 20px;
    }

    .mega-links h4 {
      font-size: 1.05rem;
      margin-bottom: 10px;
      color: #111;
    }

    .mega-links ul {
      list-style: none;
    }

    .mega-links li {
      margin: 8px 0;
    }

    .mega-links a {
      color: #555;
      text-decoration: none;
    }

    .mega-links a:hover {
      color: #7a5af8;
      text-decoration: underline;
    }
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

  <!-- Navigation -->
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

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-content">
      <h2>Welcome to Watan Freelance System</h2>
      <p>Hire smart. Work faster. Achieve more.</p>
      <?php if(isset($_SESSION['username']) && isset($_SESSION['role']) && $_SESSION['role'] === 'freelancer'): ?>
        <div style="margin-bottom: 20px;">
          <a href="freelancer_form.php" class="btn-primary-join" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; margin-right: 10px;">
            <i class="bi bi-person-gear"></i> Edit My Profile
          </a>
          <a href="browse_services.php" class="btn-primary-join" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4);">
            <i class="bi bi-search"></i> Browse Services
          </a>
        </div>
      <?php endif; ?>
      <div class="search-bar">
        <input type="text" placeholder="Search for any service...">
        <button><i class="bi bi-search"></i> Search</button>
      </div>
      <div class="suggestions-bar">
        <button class="suggestion-chip">website development <i class="bi bi-arrow-right-short"></i></button>
        <button class="suggestion-chip">architecture & interior design <i class="bi bi-arrow-right-short"></i></button>
        <button class="suggestion-chip">UGC videos <i class="bi bi-arrow-right-short"></i></button>
        <button class="suggestion-chip">video editing <i class="bi bi-arrow-right-short"></i></button>
        <button class="suggestion-chip">Learn PHP <i class="bi bi-arrow-right-short"></i></button>
      </div>
    </div>
  </section>

 <!-- Categories -->
  <section class="categories">
    <h2>Browse by Category</h2>
    <div class="category-grid">
      <div class="category"><i class="bi bi-code-slash"></i><br>Programming & Tech</div>
      <div class="category"><i class="bi bi-brush"></i><br>Graphics & Design</div>
      <div class="category"><i class="bi bi-megaphone"></i><br>Digital Marketing</div>
      <div class="category"><i class="bi bi-pen"></i><br>Writing & Translation</div>
      <div class="category"><i class="bi bi-camera-reels"></i><br>Video & Animation</div>
      <div class="category"><i class="bi bi-cpu"></i><br>AI Services</div>
      <div class="category"><i class="bi bi-music-note-beamed"></i><br>Music & Audio</div>
      <div class="category"><i class="bi bi-briefcase"></i><br>Business</div>
      <div class="category"><i class="bi bi-chat-square-dots"></i><br>Consulting</div>
    </div>
  </section>

  <!-- Popular Services -->
  <section class="popular">
    <h2>Popular Services</h2>
    <div class="popular-buttons">
      <button>Graphics & Design</button>
      <button>Website Development</button>
      <button>Video Editing</button>
      <button>Software Development</button>
      <button>Photography</button>
      <button>Architecture & Interior Design</button>
    </div>
  </section>

  <!-- Ads Section -->
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

  <!-- Made On Watan Freelance System (testimonials) -->
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

  <!-- CTA Services -->
  <section class="cta-services">
    <h2>Great work begins with great freelancers</h2>
    <p>From design to development to marketing — hire experts to move your work forward.</p>
    <button class="btn-primary-join">Join us now</button>
  </section>

  <!-- Mega Categories (before footer) -->
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
  </section>  <!-- Remaining sections (Categories, Popular Services, Ads, etc.) remain the same -->

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

    // Ads slider controls and auto-scroll with enhanced animations
    (function() {
      var slider = document.querySelector('.ads-slider');
      if (!slider) return;
      var prevBtn = document.querySelector('.ads-prev');
      var nextBtn = document.querySelector('.ads-next');
      var itemWidth = 300;
      
      function scrollByAmount(amount) {
        slider.scrollBy({ left: amount, behavior: 'smooth' });
      }
      
      prevBtn && prevBtn.addEventListener('click', function(){ scrollByAmount(-itemWidth); });
      nextBtn && nextBtn.addEventListener('click', function(){ scrollByAmount(itemWidth); });

      var auto; var intervalMs = 4000;
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
      
      function stopAuto() { if (auto) clearInterval(auto); }
      slider.addEventListener('mouseenter', stopAuto);
      slider.addEventListener('mouseleave', startAuto);
      startAuto();

      document.addEventListener('visibilitychange', function(){
        if (document.hidden) { stopAuto(); } else { startAuto(); }
      });
    })();

    // Interactive category clicks
    document.querySelectorAll('.category').forEach(function(category) {
      category.addEventListener('click', function() {
        this.style.transform = 'scale(0.95)';
        setTimeout(() => { this.style.transform = ''; }, 200);
      });
    });
  </script>

</body>
</html>
