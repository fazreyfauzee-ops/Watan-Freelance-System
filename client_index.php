<!DOCTYPE html>
<html>
<head>
  <?php include_once 'nav_bar.php'; ?>

  <title>Client Portal</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: url('background_dashboard.jpg') no-repeat center center/cover;
      background-attachment: fixed;
      font-family: 'Poppins', sans-serif;
      text-align: center;
      color: #4b296b;
      margin: 0;
      min-height: 100vh;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(248, 245, 252, 0.85);
      z-index: 0;
    }

    .logo-wrapper {
      margin-top: 30px;
      margin-bottom: 10px;
    }

    .logo-wrapper img {
      max-height: 280px;
      width: auto;
    }

    h2 {
      font-family: 'Montserrat', sans-serif;
      font-weight: bold;
      margin-top: 10px;
      margin-bottom: 30px;
      color: #5a2f91;
    }

    .btn-browse {
      background-color: #5a2f91;
      color: white;
      padding: 12px 30px;
      font-size: 16px;
      border: none;
      border-radius: 8px;
    }

    .btn-browse:hover {
      background-color: #3d1c66;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(90, 47, 145, 0.4);
    }

    /* Ads Section */
    .ads-section {
      padding: 40px 60px;
      background: rgba(255, 255, 255, 0.95);
      position: relative;
      margin: 40px 0;
      border-radius: 20px;
      max-width: 1200px;
      margin-left: auto;
      margin-right: auto;
    }

    .ads-title {
      font-size: 1.6rem;
      font-weight: 600;
      margin-bottom: 16px;
      color: #5a2f91;
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
      box-shadow: 0 8px 20px rgba(90, 47, 145, 0.3);
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
      left: 20px;
      right: 20px;
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

    .ads-nav button:hover { 
      background: #fff; 
      transform: scale(1.1);
    }

    .logo-wrapper, h2, .btn-browse {
      position: relative;
      z-index: 1;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .logo-wrapper {
      animation: fadeIn 0.8s ease-out;
    }

    h2 {
      animation: fadeIn 1s ease-out;
    }

    .btn-browse {
      animation: fadeIn 1.2s ease-out;
      transition: all 0.3s ease;
    }
  </style>
</head>
<body>

<div class="logo-wrapper">
  <img src="watan_logo.png" alt="Watan Logo">
</div>

<h2>Discover Our Talented Freelancers Now</h2>
<a href="browse_services.php" class="btn btn-browse">Start Browsing for Services Now</a>

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

<script>
  // Ads slider controls and auto-scroll
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
</script>

</body>
</html>
