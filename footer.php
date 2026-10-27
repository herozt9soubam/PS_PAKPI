</main>

<!-- CORPORATE MULTI-COLUMN FOOTER -->
<footer class="site-footer">
    <div class="footer-top-accent" aria-hidden="true"></div>
    <div class="footer-container">
        <div class="footer-grid">
            <!-- COLUMN 1: CORPORATE IDENTITY -->
            <div class="footer-col brand-col">
                <div class="footer-brand">
                    <img src="/ps-pakpi/assets/images/logo.png" alt="P.S. Pakpi Enterprises Logo" class="footer-logo" width="48" height="48" onerror="this.style.display='none'">
                    <div>
                        <h3 class="footer-title">P.S. Pakpi Enterprises</h3>
                        <p class="footer-subtitle">Quality • Trust • Excellence</p>
                    </div>
                </div>
                <p class="footer-description">
                    A registered multi-disciplinary business enterprise headquartered in Kwakeithel, Imphal West, Manipur. Delivering trusted execution across construction works, institutional procurement, commercial baking, and professional advisory.
                </p>
                <div class="footer-meta-badges">
                    <span class="meta-tag"><i class="fa-solid fa-shield-halved"></i> Verified Operations</span>
                    <span class="meta-tag"><i class="fa-solid fa-building-flag"></i> Manipur, India</span>
                </div>
            </div>

            <!-- COLUMN 2: BUSINESS DIVISIONS -->
            <div class="footer-col">
                <h4 class="footer-col-title">Business Divisions</h4>
                <ul class="footer-nav-list">
                    <?php if (!empty($headerNavServices)): ?>
                        <?php foreach (array_slice($headerNavServices, 0, 5) as $footSvc): ?>
                            <li><a href="/ps-pakpi/service_detail.php?id=<?php echo (int)$footSvc['id']; ?>"><i class="fa-solid fa-arrow-right"></i> <?php echo htmlspecialchars($footSvc['title']); ?></a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a href="/ps-pakpi/index.php#services"><i class="fa-solid fa-arrow-right"></i> All Services</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- COLUMN 3: NAVIGATION & PORTAL -->
            <div class="footer-col">
                <h4 class="footer-col-title">Navigation</h4>
                <ul class="footer-nav-list">
                    <li><a href="/ps-pakpi/index.php"><i class="fa-solid fa-arrow-right"></i> Home</a></li>
                    <li><a href="/ps-pakpi/index.php#about"><i class="fa-solid fa-arrow-right"></i> About Us</a></li>
                    <li><a href="/ps-pakpi/index.php#services"><i class="fa-solid fa-arrow-right"></i> Services Directory</a></li>
                    <li><a href="/ps-pakpi/index.php#products"><i class="fa-solid fa-arrow-right"></i> Products & Works</a></li>
                    <li><a href="/ps-pakpi/index.php#process"><i class="fa-solid fa-arrow-right"></i> How We Work</a></li>
                    <li><a href="/ps-pakpi/contact.php"><i class="fa-solid fa-arrow-right"></i> Request a Quote</a></li>
                    <li><a href="/ps-pakpi/admin/login.php"><i class="fa-solid fa-lock"></i> Administration Portal</a></li>
                </ul>
            </div>

            <!-- COLUMN 4: REGISTERED LOCATION & OFFICE -->
            <div class="footer-col contact-col">
                <h4 class="footer-col-title">Official Office</h4>
                <div class="footer-contact-block">
                    <div class="contact-item">
                        <i class="fa-solid fa-map-location-dot contact-icon" aria-hidden="true"></i>
                        <div>
                            <strong>Registered Location:</strong>
                            <p>Kwakeithel, Thokchom Leikai,<br>Imphal West, Manipur — 795001, India</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fa-solid fa-business-time contact-icon" aria-hidden="true"></i>
                        <div>
                            <strong>Working Schedule:</strong>
                            <p>Monday to Saturday<br>09:00 AM – 06:00 PM IST</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fa-solid fa-headset contact-icon" aria-hidden="true"></i>
                        <div>
                            <strong>Client Direct Helpdesk:</strong>
                            <p><a href="/ps-pakpi/contact.php" class="footer-cta-link">Submit a formal inquiry →</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FOOTER BOTTOM STRIP -->
        <div class="footer-bottom">
            <p class="copyright-text">
                &copy; <?php echo date("Y"); ?> <strong>P.S. Pakpi Enterprises</strong>. All rights reserved.
            </p>
            <div class="footer-bottom-links">
                <span>Serving Government, Commercial & Private Sectors</span>
                <span class="sep">•</span>
                <a href="#site-header" class="back-to-top" aria-label="Back to top">
                    Back to Top <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- HEADER & NAVIGATION LOGIC SCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Drawer Navigation Toggle
    const toggleBtn = document.getElementById('mobileMenuToggle');
    const closeBtn = document.getElementById('mobileDrawerClose');
    const drawer = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileDrawerOverlay');

    function openDrawer() {
        if (!drawer) return;
        drawer.classList.add('is-open');
        if (overlay) overlay.classList.add('is-open');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        if (!drawer) return;
        drawer.classList.remove('is-open');
        if (overlay) overlay.classList.remove('is-open');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);

    // Close mobile drawer when clicking internal anchor links
    document.querySelectorAll('.mobile-nav-item').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 960) {
                closeDrawer();
            }
        });
    });

    // 2. Subtle Sticky Header Shadow on Scroll
    const header = document.getElementById('site-header');
    window.addEventListener('scroll', () => {
        if (!header) return;
        if (window.scrollY > 20) {
            header.classList.add('is-scrolled');
        } else {
            header.classList.remove('is-scrolled');
        }
    }, { passive: true });
});
</script>

</body>
</html>
