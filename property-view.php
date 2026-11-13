<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cebu Skyline Townhouse — HomeBid</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <header class="site-header">
    <div class="nav-wrap">
      <a class="brand" href="index.html">
        <span class="brand-mark">⌂</span>HomeBid
      </a>
      <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">☰</button>
      <nav class="main-nav" id="mainNav">
        <a href="index.html#home">Home</a>
        <a href="index.html#properties">Browse Properties</a>
        <a href="index.html#how-it-works">How It Works</a>
        <a href="index.html#about">About Us</a>
        <a href="index.html#contact">Contact</a>
      </nav>
      <div class="nav-actions">
        <button class="btn btn-gold btn-small" data-modal="signup">Sign Up</button>
        <button class="btn btn-outline btn-small" data-modal="login">Log In</button>
      </div>
    </div>
  </header>

  <main class="detail-main">
    <a class="back-link" href="index.html#properties">← Browse Properties</a>
    <h1>Cebu Skyline Townhouse</h1>
    <p class="location">Lahug, Cebu City</p>

    <div class="detail-layout">
      <section class="detail-content">
        <img class="detail-photo" src="assets/property-cebu-detail.jpg" alt="Cebu Skyline Townhouse exterior">
        <div class="detail-specs">
          <span>3 bedrooms</span>
          <b>·</b>
          <span>2 bathrooms</span>
          <b>·</b>
          <span>165 m²</span>
        </div>
        <h2>About this property</h2>
        <p class="description">A contemporary city townhouse with private balconies, generous windows, and a two-car driveway. A practical, light-filled home for your next chapter in Cebu.</p>
        <p class="sample-note">Sample property · Listing details are for illustration only.</p>
      </section>

      <aside class="bid-panel">
        <p class="live-status">● &nbsp; LIVE AUCTION</p>
        <p class="panel-label">Current highest bid</p>
        <strong class="highest-bid">₱6,850,000</strong>
        <p class="starting-bid">Starting bid: ₱6,200,000</p>
        <p class="panel-label">Auction ends in</p>
        <div class="countdown" id="countdown">03d : 05h : 18m</div>
        <p class="bidder-count">9 bidders participating</p>
        <button class="btn btn-gold bid-button" data-modal="signup">Sign Up to Bid</button>
        <p class="registered">
          Already registered? <button class="inline-login" data-modal="login">Log In</button>
        </p>
        <p class="bid-disclaimer">Create a verified bidder profile and review the auction terms before placing a bid.</p>
      </aside>
    </div>
  </main>

  <div class="modal-backdrop" id="modalBackdrop">
    <section class="modal" role="dialog" aria-modal="true">
      <button class="modal-close" id="modalClose">×</button>
      <p class="eyebrow">WELCOME TO HOMEBID</p>
      <h2 id="modalTitle">Create your account</h2>
      <p class="muted" id="modalDescription">Join HomeBid and discover your next home.</p>
      <form id="accountForm">
        <label>
          Full name
          <input name="name" placeholder="Your name" required>
        </label>
        <label>
          Email address
          <input type="email" name="email" placeholder="you@example.com" required>
        </label>
        <label>
          Password
          <input type="password" name="password" minlength="8" placeholder="At least 8 characters" required>
        </label>
        <button class="btn btn-gold form-submit">Create Account</button>
      </form>
      <p id="formMessage" class="form-message"></p>
    </section>
  </div>

  <script src="script.js"></script>
</body>
</html>