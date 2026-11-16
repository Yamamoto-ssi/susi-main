<?php
// The public landing page should remain available even when the local database is offline.
define('HOMEBID_ALLOW_OFFLINE_DB', true);
require_once __DIR__ . '/config/db.php';

$featuredProperties = [];
$featuredLoadError = false;
try {
    $featuredQuery = hb_query($conn, "
        SELECT
            p.id, p.title, p.location, p.description, p.lot_area, p.floor_area,
            p.price, p.status, p.is_approved, p.image_path,
            a.starting_bid, a.current_bid,
            (SELECT COUNT(*) FROM bids b WHERE b.property_id = p.id) AS bidder_count
        FROM properties p
        LEFT JOIN auctions a ON a.id = (
            SELECT a2.id
            FROM auctions a2
            WHERE a2.property_id = p.id
            ORDER BY a2.id DESC
            LIMIT 1
        )
        WHERE p.is_approved = 1 AND p.review_status = 'approved'
          AND (p.status = 'Available' OR (p.status = 'Auction' AND a.review_status = 'approved' AND a.status IN ('active','upcoming')))
        ORDER BY p.created_at DESC, p.id DESC
        LIMIT 6
    ");
    $featuredProperties = hb_fetch_all($featuredQuery);
} catch (Throwable $e) {
    error_log('HomeBid featured properties: ' . 'Database operation failed');
    $featuredLoadError = true;
}
function homebid_money($amount): string {
    return '₱' . number_format((float)$amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Discover verified properties and take part in transparent online house bidding with HomeBid.">
    <title>HomeBid — Find Your Dream Home</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=20261116-3">
</head>

<body>
    <header class="site-header">
        <div class="nav-wrap">
            <a class="brand" href="#home" aria-label="HomeBid home"><span
                    class="brand-mark">⌂</span><span>HomeBid</span></a>
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
                <p class="hero-copy">Discover amazing properties, participate in transparent online auctions, and place
                    your bids to secure your next home — all in one convenient platform.</p>
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
                <div>
                    <p class="eyebrow">CURATED OPPORTUNITIES</p>
                    <h2>Featured Properties</h2>
                    <p class="muted">Exceptional homes. Your next opportunity.</p>
                </div>
                <a class="text-link" href="#properties">Browse all properties <span>↗</span></a>
            </div>
            <div class="property-grid">
                <?php if ($featuredProperties): ?>
                    <?php foreach ($featuredProperties as $property): ?>
                        <article class="property-card">
                            <div class="property-image">
                                <img src="<?php echo htmlspecialchars($property['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($property['title'], ENT_QUOTES, 'UTF-8') ?>">
                                <?php if (!empty($property['current_bid'])): ?>
                                    <span class="live-tag">● LIVE AUCTION</span>
                                <?php else: ?>
                                    <span class="live-tag">● AVAILABLE</span>
                                <?php endif; ?>
                                <span class="verified">✓ Verified</span>
                            </div>
                            <div class="property-body">
                                <h3><?= htmlspecialchars($property['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p class="location">⌖ <?= htmlspecialchars($property['location'], ENT_QUOTES, 'UTF-8') ?></p>
                                <div class="specs">
                                    <span><?= number_format((float)$property['lot_area'], 0) ?> m² lot</span>
                                    <span><?= number_format((float)$property['floor_area'], 0) ?> m² floor</span>
                                </div>
                                <div class="price-row">
                                    <div>
                                        <small>Starting price</small>
                                        <strong><?= homebid_money($property['starting_bid'] ?? $property['price']) ?></strong>
                                    </div>
                                    <div class="current">
                                        <small>Current highest bid</small>
                                        <strong><?= homebid_money($property['current_bid'] ?? $property['price']) ?></strong>
                                    </div>
                                </div>
                                <div class="card-foot">
                                    <span><?= (int)$property['bidder_count'] ?> bids</span>
                                    <span><?= !empty($property['current_bid']) ? 'Auction listing' : 'Open for bidding' ?></span>
                                </div>
                                <a class="btn btn-navy view-property"
                                   href="property-view.php?id=<?= (int)$property['id'] ?>">View Property</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php elseif ($featuredLoadError): ?>
                    <p class="muted">Featured properties are temporarily unavailable. Please try again later.</p>
                <?php else: ?>
                    <p class="muted">There are no approved properties available right now. Check back soon for new listings.</p>
                <?php endif; ?>
            </div>
            <p class="disclaimer">Showing approved and available properties from the HomeBid database.</p>
        </section>

        <section class="how-section" id="how-it-works">
            <div class="section centered">
                <p class="eyebrow">A SIMPLE PATH TO YOUR NEXT HOME</p>
                <h2>How It Works</h2>
                <p class="muted">From your first look to your winning bid.</p>
                <div class="steps">
                    <article class="step">
                        <div class="step-top"><span class="step-icon">♙</span><b>01</b></div>
                        <h3>Create Your Account</h3>
                        <p>Sign up and create your bidder profile. A few simple steps, a world of possibilities.</p>
                    </article>
                    <article class="step">
                        <div class="step-top"><span class="step-icon">⚒</span><b>02</b></div>
                        <h3>Explore &amp; Bid</h3>
                        <p>Browse available houses and place competitive bids with clarity and confidence.</p>
                    </article>
                    <article class="step">
                        <div class="step-top"><span class="step-icon">♧</span><b>03</b></div>
                        <h3>Win Your Dream Home</h3>
                        <p>Complete the required process after winning an auction. Your next chapter begins here.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="about-section section" id="about">
            <div class="about-photo"><img src="assets/why-house.jpg" alt="Warmly lit modern home at dusk">
                <div>More than a property.<br>A place to call yours.</div>
            </div>
            <div class="about-copy">
                <p class="eyebrow">BUILT AROUND YOUR PEACE OF MIND</p>
                <h2>Why Choose HomeBid?</h2>
                <p class="muted">A smarter way to bid. A more confident way home.</p>
                <ul class="benefit-list">
                    <li><span>♧</span>
                        <div><strong>Transparent Bidding Process</strong>
                            <p>Every move you make and every bid is clear and easy to follow.</p>
                        </div>
                    </li>
                    <li><span>♢</span>
                        <div><strong>Verified Property Listings</strong>
                            <p>Explore properties with reviewed listing details.</p>
                        </div>
                    </li>
                    <li><span>ϟ</span>
                        <div><strong>Real-Time Auction Updates</strong>
                            <p>Stay in the loop as the bidding unfolds.</p>
                        </div>
                    </li>
                    <li><span>♙</span>
                        <div><strong>Secure User Accounts</strong>
                            <p>Your profile and account access stay protected.</p>
                        </div>
                    </li>
                    <li><span>⌕</span>
                        <div><strong>Easy Property Discovery</strong>
                            <p>Find the right home, in the right neighborhood.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <section class="cta-section">
            <p class="eyebrow light">YOUR NEXT CHAPTER STARTS HERE</p>
            <h2>Great homes.<br>Even greater possibilities.</h2>
            <p>Discover a home you love. Make your next move with HomeBid.</p><a class="btn btn-gold"
                href="#properties">Explore Properties</a>
        </section>
    </main>

    <footer class="site-footer" id="contact">
        <div class="footer-main">
            <div class="footer-brand"><a class="brand brand-light" href="#home"><span
                        class="brand-mark">⌂</span><span>HomeBid</span></a>
                <p>A smarter, more transparent way to discover and bid on your next home in the Philippines.</p>
            </div>
            <div>
                <h4>Quick links</h4><a href="#home">Home</a><a href="#properties">Browse Properties</a><a
                    href="#how-it-works">How It Works</a><a href="#about">About Us</a>
            </div>
            <div>
                <h4>Resources</h4><a href="#properties">Bidding guide</a><a href="#contact">Privacy Policy</a><a
                    href="#contact">Terms and Conditions</a><a href="staff-login.php">Staff &amp; Admin Login</a>
            </div>
            <div>
                <h4>Get in touch</h4><a href="mailto:hello@homebid.example">hello@homebid.example</a><a
                    href="tel:+630000000000">+63 (0) 000 000 0000</a><span>Metro Manila, Philippines</span>
            </div>
        </div>
        <div class="footer-bottom"><span>© 2025 HomeBid. All rights reserved.</span><span>Find your place. Make your
                bid.</span></div>
    </footer>

    <div class="modal-backdrop" id="modalBackdrop" aria-hidden="true">
        <section class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <button class="modal-close" id="modalClose" aria-label="Close dialog">×</button>
            <p class="eyebrow">WELCOME TO HOMEBID</p>
            <h2 id="modalTitle">Create your account</h2>
            <p class="muted" id="modalDescription">Join HomeBid and discover your next home.</p>
            <form id="accountForm" novalidate>
                <label id="nameField" hidden>Full name<input type="text" name="name" placeholder="Your full name"
                        autocomplete="name"></label>
                <label id="usernameField" hidden>Username<input type="text" name="username" maxlength="50" placeholder="Choose a username"
                        autocomplete="username"></label>
                <label id="loginIdentifierLabel">Email address<input type="text" name="email" placeholder="you@example.com" autocomplete="username"
                        required></label>
                <label>Password<input type="password" name="password" placeholder="Enter your password"
                        autocomplete="current-password" required></label>
                <button class="btn btn-gold form-submit" type="submit">Create Account</button>
            </form>
            <div class="google-signin-section" id="googleSignInSection" hidden>
                <div class="auth-divider"><span>OR</span></div>
                <a class="google-oauth-button" id="googleSignInButton" href="google-start.php"><span class="google-mark" aria-hidden="true">G</span><span>Continue with Google</span></a>
                <p class="google-signin-note" id="googleSignInNote"></p>
            </div>
            <p class="form-message" id="formMessage" role="status" aria-live="polite"></p>
            <p class="auth-switch" id="authSwitch">Already have an account? <button type="button" data-modal="login">Log
                    in</button></p>
        </section>
    </div>
    <div class="toast" id="toast" role="status"></div>
    <script src="script.js?v=20261117-2"></script>
</body>

</html>