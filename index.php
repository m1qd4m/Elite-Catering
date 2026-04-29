<?php
require_once 'config/db.php';

$featured = $conn->query("SELECT * FROM menu_items WHERE available=1 ORDER BY RAND() LIMIT 6");
$packages = $conn->query("SELECT * FROM packages WHERE available=1 ORDER BY price ASC");

$isLoggedIn = isset($_SESSION['user_id']);
$ctaHref    = $isLoggedIn ? $_SESSION['role'].'/dashboard.php' : 'auth/signup.php';
$ctaLabel   = $isLoggedIn ? 'Go to Dashboard' : 'Book Your Event';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Élite Catering — Premium Catering Services</title>
  <meta name="description" content="Premium catering for weddings, corporate events & celebrations.">
  <link rel="stylesheet" href="assets/css/variables.css">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/landing.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════════════
     NAVBAR
     ═══════════════════════════════════════════════════════ -->
<nav class="navbar-catering" id="navbar">
  <a href="index.php" class="nav-logo">Élite<span> Catering</span></a>

  <div class="nav-links">
    <a href="#services">Services</a>
    <a href="#menu">Menu</a>
    <a href="#packages">Packages</a>
    <a href="#testimonials">Reviews</a>
    <a href="#contact">Contact</a>
  </div>

  <div class="nav-actions">
    <?php if ($isLoggedIn): ?>
      <a href="<?= $_SESSION['role'] ?>/dashboard.php" class="btn-outline-gold"
         style="border-color:rgba(200,155,60,0.5);color:var(--accent)">
        Dashboard
      </a>
      <a href="auth/logout.php" class="btn-ghost"
         style="color:rgba(255,255,255,0.65);border-color:rgba(255,255,255,0.18)">
        Logout
      </a>
    <?php else: ?>
      <a href="auth/login.php" class="btn-ghost"
         style="color:rgba(255,255,255,0.7);border-color:rgba(255,255,255,0.2)">
        Login
      </a>
      <a href="auth/signup.php" class="btn-gold btn-sm">Get Started</a>
    <?php endif; ?>
  </div>

  <button class="nav-toggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
</nav>


<!-- ═══════════════════════════════════════════════════════
     HERO
     ═══════════════════════════════════════════════════════ -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-overlay"></div>
  <div class="container-custom hero-content">
    <div class="hero-eyebrow">Est. 2019 · Premium Catering</div>
    <h1 class="hero-title">
      Where Every Meal<br>Becomes a <em>Memory</em>
    </h1>
    <p class="hero-desc">
      Extraordinary dining experiences for weddings, corporate events, and celebrations —
      crafted with passion and served with elegance across Pakistan.
    </p>
    <div class="hero-actions">
      <a href="<?= $ctaHref ?>" class="btn-gold btn-lg">
        <i class="fas fa-calendar-check"></i> <?= $ctaLabel ?>
      </a>
      <a href="#packages" class="hero-ghost-link">
        <i class="fas fa-play"></i> View Packages
      </a>
    </div>
  </div>
  <div class="hero-scroll-hint">
    <span>Scroll</span>
    <i class="fas fa-chevron-down"></i>
  </div>
</section>


<!-- ═══════════════════════════════════════════════════════
     STATS BAR
     ═══════════════════════════════════════════════════════ -->
<div class="stats-bar">
  <div class="stat-item">
    <span class="stat-num" data-count="500">0</span>
    <span class="stat-text">Events Catered</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" data-count="50000">0</span>
    <span class="stat-text">Guests Served</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" data-count="98">0</span>
    <span class="stat-text">% Satisfaction</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" data-count="12">0</span>
    <span class="stat-text">Years Experience</span>
  </div>
</div>


<!-- ═══════════════════════════════════════════════════════
     SERVICES
     ═══════════════════════════════════════════════════════ -->
<section class="section services-section" id="services">
  <div class="container-custom">
    <div class="reveal" style="text-align:center;margin-bottom:var(--space-2xl)">
      <span class="section-label">What We Offer</span>
      <h2 class="section-title">Catering for Every Occasion</h2>
      <div class="gold-line center"></div>
    </div>
    <div class="services-grid">
      <?php
      $services = [
        ['🎊','Wedding Catering',  'From intimate nikkahs to grand receptions — every detail handled with grace.'],
        ['🏢','Corporate Events',  'Professional catering for conferences, launches, and board dinners.'],
        ['🎂','Birthday Parties',  'Make their day unforgettable with custom menus and themed setups.'],
        ['🎓','Graduations',       'Celebrate milestones with food as special as the achievement itself.'],
        ['🌙','Eid Gatherings',    'Traditional feasts prepared with authentic recipes and cultural pride.'],
        ['🍽️','Private Dining',    'Exclusive chef-at-home and intimate fine-dining experiences.'],
      ];
      foreach ($services as $s): ?>
        <div class="service-card reveal">
          <div class="service-icon"><?= $s[0] ?></div>
          <h3><?= $s[1] ?></h3>
          <p><?= $s[2] ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════════════════
     MENU
     ═══════════════════════════════════════════════════════ -->
<section class="section menu-section" id="menu">
  <div class="container-custom">
    <div class="reveal" style="text-align:center;margin-bottom:var(--space-2xl)">
      <span class="section-label">Our Cuisine</span>
      <h2 class="section-title light">Signature Dishes</h2>
      <div class="gold-line center"></div>
      <p class="section-desc" style="margin:0 auto;color:rgba(255,255,255,0.45)">
        Crafted by expert chefs using the finest ingredients — every dish tells a story.
      </p>
    </div>

    <?php
    $fallbacks = [
      ['Chicken BBQ',    'Main Course', 850,  'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?w=600&q=80'],
      ['Beef Biryani',   'Main Course', 1200, 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=600&q=80'],
      ['Mutton Karahi',  'Main Course', 1500, 'https://images.unsplash.com/photo-1574894709920-11b28e7367e3?w=600&q=80'],
      ['Fresh Salad',    'Sides',       200,  'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=600&q=80'],
      ['Gulab Jamun',    'Dessert',     150,  'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=600&q=80'],
      ['Fresh Juice',    'Beverages',   150,  'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=600&q=80'],
    ];
    $items_arr = [];
    while ($item = $featured->fetch_assoc()) { $items_arr[] = $item; }
    if (empty($items_arr)) {
      // use fallback rows
      foreach ($fallbacks as $fb) {
        $items_arr[] = ['name'=>$fb[0],'category'=>$fb[1],'price'=>$fb[2],'image_url'=>$fb[3]];
      }
    }
    ?>
    <div class="menu-grid">
      <?php foreach ($items_arr as $i => $item):
        $img = !empty($item['image_url']) ? $item['image_url'] : $fallbacks[$i % count($fallbacks)][3]; ?>
        <div class="menu-card reveal">
          <img src="<?= htmlspecialchars($img) ?>"
               alt="<?= htmlspecialchars($item['name']) ?>"
               loading="lazy"
               onerror="this.src='<?= $fallbacks[$i % count($fallbacks)][3] ?>'">
          <div class="menu-overlay">
            <div class="menu-cat"><?= htmlspecialchars($item['category']) ?></div>
            <div class="menu-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="menu-price">Rs. <?= number_format($item['price'],0) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-2xl">
      <a href="<?= $ctaHref ?>" class="btn-gold btn-lg">
        <i class="fas fa-utensils"></i> Book Full Menu Experience
      </a>
    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════════════════
     PACKAGES
     ═══════════════════════════════════════════════════════ -->
<section class="section packages-section" id="packages">
  <div class="container-custom">
    <div class="reveal" style="text-align:center;margin-bottom:var(--space-2xl)">
      <span class="section-label">Packages</span>
      <h2 class="section-title">Choose Your Package</h2>
      <div class="gold-line center"></div>
      <p class="section-desc" style="margin:0 auto">
        Tailored packages for every budget — all-inclusive, stress-free catering.
      </p>
    </div>

    <?php
    $pkg_arr = [];
    while ($p = $packages->fetch_assoc()) { $pkg_arr[] = $p; }
    $pkg_fallbacks = [
      ['Silver Package', 'Perfect for intimate gatherings.', 950,  50,  150, 'https://images.unsplash.com/photo-1555244162-803834f70033?w=600&q=80'],
      ['Gold Package',   'Our most popular choice.',        1800, 150, 400, 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&q=80'],
      ['Platinum Package','Full luxury experience.',        2500, 300, 1000,'https://images.unsplash.com/photo-1529543544282-ea669407fca3?w=600&q=80'],
    ];
    if (empty($pkg_arr)) {
      foreach ($pkg_fallbacks as $pf) {
        $pkg_arr[] = ['name'=>$pf[0],'description'=>$pf[1],'price'=>$pf[2],'min_guests'=>$pf[3],'max_guests'=>$pf[4],'image_url'=>$pf[5],'id'=>0];
      }
    }
    ?>
    <div class="packages-grid">
      <?php foreach ($pkg_arr as $i => $p):
        $img      = !empty($p['image_url']) ? $p['image_url'] : $pkg_fallbacks[$i % count($pkg_fallbacks)][5];
        $featured = ($i === 1); ?>
        <div class="pkg-card <?= $featured?'featured':'' ?> reveal" style="position:relative">
          <?php if ($featured): ?><div class="pkg-badge">⭐ Most Popular</div><?php endif; ?>
          <img src="<?= htmlspecialchars($img) ?>"
               alt="<?= htmlspecialchars($p['name']) ?>"
               loading="lazy"
               onerror="this.src='<?= $pkg_fallbacks[$i % count($pkg_fallbacks)][5] ?>'">
          <div class="pkg-body">
            <div class="pkg-name"><?= htmlspecialchars($p['name']) ?></div>
            <div class="pkg-desc"><?= htmlspecialchars($p['description'] ?: '') ?></div>
            <div class="pkg-price">
              Rs. <?= number_format($p['price'],0) ?> <span>/ per guest</span>
            </div>
            <div class="text-sm text-muted mb-md">
              👥 <?= $p['min_guests'] ?>–<?= $p['max_guests'] ?> guests
            </div>
            <a href="<?= $ctaHref ?>" class="<?= $featured?'btn-gold':'btn-outline-gold' ?> btn-full">
              Book This Package
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════════════════
     PROCESS
     ═══════════════════════════════════════════════════════ -->
<section class="section process-section">
  <div class="container-custom">
    <div class="reveal" style="text-align:center;margin-bottom:var(--space-2xl)">
      <span class="section-label" style="color:var(--accent)">How It Works</span>
      <h2 class="section-title light">3 Simple Steps</h2>
      <div class="gold-line center"></div>
    </div>
    <div class="process-grid">
      <?php
      $steps = [
        ['01','fas fa-user-plus',  'Create Account',  'Sign up in under a minute — no credit card needed.'],
        ['02','fas fa-box-open',   'Pick a Package',  'Browse our menus and choose what fits your event.'],
        ['03','fas fa-star',       'Enjoy Your Event', 'We handle everything. You enjoy every moment.'],
      ];
      foreach ($steps as $s): ?>
        <div class="process-step reveal">
          <div class="process-num"><?= $s[0] ?></div>
          <div class="process-icon"><i class="<?= $s[1] ?>"></i></div>
          <h3><?= $s[2] ?></h3>
          <p><?= $s[3] ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════════════════
     TESTIMONIALS
     ═══════════════════════════════════════════════════════ -->
<section class="section testimonials-section" id="testimonials">
  <div class="container-custom">
    <div class="reveal" style="text-align:center;margin-bottom:var(--space-2xl)">
      <span class="section-label">Reviews</span>
      <h2 class="section-title">What Our Clients Say</h2>
      <div class="gold-line center"></div>
    </div>
    <div class="testi-grid">
      <?php
      $testimonials = [
        ['★★★★★', 'The food at our wedding was incredible. Every single guest kept complimenting the biryani!', 'Fatima & Bilal', 'Wedding — Peshawar'],
        ['★★★★★', 'Professional team, delicious food, seamless service. We will absolutely book again for our next event.', 'Kamran Sheikh', 'Corporate Dinner'],
        ['★★★★★', "Our daughter's birthday was a massive hit. The desserts were simply out of this world.", 'Mrs. Ayesha R.', 'Birthday Party'],
      ];
      foreach ($testimonials as $t): ?>
        <div class="testi-card reveal">
          <div class="testi-stars"><?= $t[0] ?></div>
          <div class="testi-text">"<?= $t[1] ?>"</div>
          <div class="testi-author">
            <div class="testi-avatar"><?= strtoupper(substr($t[2],0,1)) ?></div>
            <div>
              <div class="testi-name"><?= $t[2] ?></div>
              <div class="testi-event"><?= $t[3] ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════════════════
     CTA
     ═══════════════════════════════════════════════════════ -->
<section class="cta-section" id="contact">
  <div class="container-custom cta-inner">
    <span class="section-label">Ready to Begin?</span>
    <h2 class="cta-title">Make Your Event<br><em>Extraordinary</em></h2>
    <p class="cta-desc">
      Join hundreds of satisfied clients. Book your event today and let us handle the rest.
    </p>
    <div class="cta-actions">
      <a href="<?= $ctaHref ?>" class="btn-gold btn-lg">
        <i class="fas fa-calendar-check"></i> Book Your Event
      </a>
      <div class="cta-contact">
        <i class="fas fa-phone"></i> +92 300 000 0000
      </div>
    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════════════════
     FOOTER
     ═══════════════════════════════════════════════════════ -->
<footer class="site-footer">
  <div class="container-custom">
    <div class="footer-grid">
      <div>
        <span class="footer-logo">Élite Catering</span>
        <p class="footer-about">
          Premium catering services for weddings, corporate events, and celebrations across Pakistan.
          Quality, elegance, and unforgettable flavors.
        </p>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="#services">Services</a></li>
          <li><a href="#menu">Our Menu</a></li>
          <li><a href="#packages">Packages</a></li>
          <li><a href="#testimonials">Reviews</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Account</h4>
        <ul>
          <li><a href="auth/login.php">Sign In</a></li>
          <li><a href="auth/signup.php">Register</a></li>
          <li><a href="<?= $ctaHref ?>">Book Now</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <ul>
          <li><a href="tel:+923000000000">📞 +92 300 000 0000</a></li>
          <li><a href="mailto:info@elitecatering.pk">✉️ info@elitecatering.pk</a></li>
          <li><a href="#">📍 Peshawar, Pakistan</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      © <?= date('Y') ?> Élite Catering. All rights reserved. Crafted for exceptional events.
    </div>
  </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
