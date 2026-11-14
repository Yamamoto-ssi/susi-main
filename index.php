<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Discover verified properties and take part in transparent online house bidding with HomeBid.">
  <title>HomeBid — Find Your Dream Home</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="site-header">
    <div class="nav-wrap">
      <a class="brand" href="#home" aria-label="HomeBid home"><span class="brand-mark">⌂</span><span>HomeBid</span></a>
      <button class="menu-toggle" id="menuToggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
      <nav class="main-nav" id="mainNav">
        <a href="#home" class="active">Home</a>
        <a href="#properties">Browse Properties</a>
        <a href="#how-it-works">How It Works</a>
        <a href="#about">About Us</a>
        <a href="#contact">Contact</a>
      </nav>
      <div class="nav-actions">
        <button class="btn btn-gold btn-small" data-modal="signup">Sign Up</button>
        <button class="btn btn-outline btn-small" data-modal="login">Log In</button>
      </div>
    </div>
  </header>

  <main>
    <section class="hero" id="home">
      <div class="hero-shade"></div>
      <div class="hero-content">
        <p class="eyebrow light">✦ &nbsp; A NEW WAY HOME</p>
        <h1>Find Your Dream<br class="desktop-break"> Home Through<br class="desktop-break"> Smart Bidding.</h1>
        <p class="hero-copy">Discover amazing properties, participate in transparent online auctions, and place your bids to secure your next home — all in one convenient platform.</p>
        <div class="hero-actions">
          <a class="btn btn-gold" href="#properties">Explore Properties</a>
          <a class="btn btn-glass" href="#how-it-works">How It Works</a>
        </div>
        <p class="hero-note">♧ &nbsp; Your next home is just one bid away.</p>
      </div>
      <div class="hero-caption"><strong>EXCEPTIONAL HOMES.</strong><br>Extraordinary possibilities.</div>
    </section>

    <section class="trust-strip" aria-label="HomeBid benefits">
      <div>♧ <span>Verified properties</span></div>
      <div>▣ <span>Transparent bidding</span></div>
      <div>◷ <span>Real-time auction updates</span></div>
    </section>

    <section class="section properties-section" id="properties">
      <div class="section-heading">
        <div><p class="eyebrow">CURATED OPPORTUNITIES</p><h2>Featured Properties</h2><p class="muted">Exceptional homes. Your next opportunity.</p></div>
        <a class="text-link" href="#properties">Browse all properties <span>↗</span></a>
      </div>
      <div class="property-grid">
        <article class="property-card">
          <div class="property-image"><img src="assets/property-alabang.jpg" alt="Modern villa with garden and pool"><span class="live-tag">● LIVE AUCTION</span><span class="verified">✓ Verified</span></div>
          <div class="property-body"><h3>The Alabang Modern Villa</h3><p class="location">⌖ Alabang, Muntinlupa</p><div class="specs"><span>4 beds</span><span>4 baths</span><span>320 m²</span></div><div class="price-row"><div><small>Starting bid</small><strong>₱18,500,000</strong></div><div class="current"><small>Current highest bid</small><strong>₱20,250,000</strong></div></div><div class="card-foot"><span>18 bidders</span><span>Ends in 02h : 14m : 32s</span></div><button class="btn btn-navy view-property" data-property="The Alabang Modern Villa">View Property</button></div>
        </article>
        <article class="property-card">
          <div class="property-image"><img src="assets/property-nuvali.jpg" alt="Contemporary two-storey garden residence"><span class="live-tag">● LIVE AUCTION</span><span class="verified">✓ Verified</span></div>
          <div class="property-body"><h3>Nuvali Garden Residence</h3><p class="location">⌖ Santa Rosa, Laguna</p><div class="specs"><span>3 beds</span><span>3 baths</span><span>210 m²</span></div><div class="price-row"><div><small>Starting bid</small><strong>₱8,500,000</strong></div><div class="current"><small>Current highest bid</small><strong>₱9,150,000</strong></div></div><div class="card-foot"><span>12 bidders</span><span>Ends in 01h : 09m : 45s</span></div><button class="btn btn-navy view-property" data-property="Nuvali Garden Residence">View Property</button></div>
        </article>
        <article class="property-card">
          <div class="property-image"><img src="assets/property-cebu.jpg" alt="Contemporary townhouse in Cebu"><span class="live-tag">● LIVE AUCTION</span><span class="verified">✓ Verified</span></div>
          <div class="property-body"><h3>Cebu Skyline Townhouse</h3><p class="location">⌖ Lahug, Cebu City</p><div class="specs"><span>3 beds</span><span>2 baths</span><span>165 m²</span></div><div class="price-row"><div><small>Starting bid</small><strong>₱6,200,000</strong></div><div class="current"><small>Current highest bid</small><strong>₱6,850,000</strong></div></div><div class="card-foot"><span>9 bidders</span><span>Ends in 00h : 48m : 06s</span></div><button class="btn btn-navy view-property" data-property="Cebu Skyline Townhouse">View Property</button></div>
        </article>
      </div>
      <p class="disclaimer">Sample listings for illustration. Auction terms and eligibility apply.</p>
    </section>

    <section class="how-section" id="how-it-works">
      <div class="section centered"><p class="eyebrow">A SIMPLE PATH TO YOUR NEXT HOME</p><h2>How It Works</h2><p class="muted">From your first look to your winning bid.</p>
        <div class="steps">
          <article class="step"><div class="step-top"><span class="step-icon">♙</span><b>01</b></div><h3>Create Your Account</h3><p>Sign up and create your bidder profile. A few simple steps, a world of possibilities.</p></article>
          <article class="step"><div class="step-top"><span class="step-icon">⚒</span><b>02</b></div><h3>Explore &amp; Bid</h3><p>Browse available houses and place competitive bids with clarity and confidence.</p></article>
          <article class="step"><div class="step-top"><span class="step-icon">♧</span><b>03</b></div><h3>Win Your Dream Home</h3><p>Complete the required process after winning an auction. Your next chapter begins here.</p></article>
        </div>
      </div>
    </section>

    <section class="about-section section" id="about">
      <div class="about-photo"><img src="assets/why-house.jpg" alt="Warmly lit modern home at dusk"><div>More than a property.<br>A place to call yours.</div></div>
      <div class="about-copy"><p class="eyebrow">BUILT AROUND YOUR PEACE OF MIND</p><h2>Why Choose HomeBid?</h2><p class="muted">A smarter way to bid. A more confident way home.</p>
        <ul class="benefit-list">
          <li><span>♧</span><div><strong>Transparent Bidding Process</strong><p>Every move you make and every bid is clear and easy to follow.</p></div></li>
          <li><span>♢</span><div><strong>Verified Property Listings</strong><p>Explore properties with reviewed listing details.</p></div></li>
          <li><span>ϟ</span><div><strong>Real-Time Auction Updates</strong><p>Stay in the loop as the bidding unfolds.</p></div></li>
          <li><span>♙</span><div><strong>Secure User Accounts</strong><p>Your profile and account access stay protected.</p></div></li>
          <li><span>⌕</span><div><strong>Easy Property Discovery</strong><p>Find the right home, in the right neighborhood.</p></div></li>
        </ul>
      </div>
    </section>

    <section class="cta-section"><p class="eyebrow light">YOUR NEXT CHAPTER STARTS HERE</p><h2>Great homes.<br>Even greater possibilities.</h2><p>Discover a home you love. Make your next move with HomeBid.</p><a class="btn btn-gold" href="#properties">Explore Properties</a></section>
  </main>

  <footer class="site-footer" id="contact">
    <div class="footer-main">
      <div class="footer-brand"><a class="brand brand-light" href="#home"><span class="brand-mark">⌂</span><span>HomeBid</span></a><p>A smarter, more transparent way to discover and bid on your next home in the Philippines.</p></div>
      <div><h4>Quick links</h4><a href="#home">Home</a><a href="#properties">Browse Properties</a><a href="#how-it-works">How It Works</a><a href="#about">About Us</a></div>
      <div><h4>Resources</h4><a href="#properties">Bidding guide</a><a href="#contact">Privacy Policy</a><a href="#contact">Terms and Conditions</a></div>
      <div><h4>Get in touch</h4><a href="mailto:hello@homebid.example">hello@homebid.example</a><a href="tel:+630000000000">+63 (0) 000 000 0000</a><span>Metro Manila, Philippines</span></div>
    </div>
    <div class="footer-bottom"><span>© 2025 HomeBid. All rights reserved.</span><span>Find your place. Make your bid.</span></div>
  </footer>

  <div class="modal-backdrop" id="modalBackdrop" aria-hidden="true">
    <section class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <button class="modal-close" id="modalClose" aria-label="Close dialog">×</button>
      <p class="eyebrow">WELCOME TO HOMEBID</p><h2 id="modalTitle">Create your account</h2><p class="muted" id="modalDescription">Join HomeBid and discover your next home.</p>
      <form id="accountForm" novalidate>
        <label id="nameField">Full name<input type="text" name="name" placeholder="Your full name" autocomplete="name"></label>
        <label>Email address<input type="email" name="email" placeholder="you@example.com" autocomplete="email" required></label>
        <label>Password<input type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required></label>
        <button class="btn btn-gold form-submit" type="submit">Create Account</button>
      </form>
      <p class="form-message" id="formMessage" role="status" aria-live="polite"></p>
      <p class="auth-switch" id="authSwitch">Already have an account? <button type="button" data-modal="login">Log in</button></p>
    </section>
  </div>
  <div class="toast" id="toast" role="status"></div>
  <script src="script.js"></script>
</body>
</html>