<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db.php";

if (!$conn) {
    die("<h1 style='color: red; text-align: left;'>Database connection failed!</h1>");
}

if (!function_exists('clientServiceImage')) {
    function clientServiceImage(array $row): string {
        $imageUrl = trim((string)($row['image_url'] ?? ''));
        if ($imageUrl === '') {
            return 'assets/images/default-service.jpg';
        }
        if (strpos($imageUrl, 'data:image') === 0 || strpos($imageUrl, 'http') === 0 || strpos($imageUrl, '/') === 0) {
            return $imageUrl;
        }
        $cleanName = basename($imageUrl);
        if (file_exists("assets/images/services/" . $cleanName)) {
            return "assets/images/services/" . $cleanName;
        }
        if (file_exists("uploads/" . $cleanName)) {
            return "uploads/" . $cleanName;
        }
        if (file_exists("assets/images/" . $cleanName)) {
            return "assets/images/" . $cleanName;
        }
        return 'assets/images/default-service.jpg';
    }
}

if (!function_exists('clientServiceFeatures')) {
    function clientServiceFeatures($features): array {
        if (empty($features)) {
            return [];
        }
        $jsonFeatures = json_decode((string)$features, true);
        if (is_array($jsonFeatures)) {
            return array_values(array_filter(array_map('trim', $jsonFeatures)));
        }

        $legacy = trim((string)$features, '{}');
        if ($legacy === '') {
            return [];
        }

        $items = [];
        foreach (explode(',', $legacy) as $feature) {
            $clean = trim($feature, "\" \t\n\r\0\x0B");
            if ($clean !== '') {
                $items[] = $clean;
            }
        }
        return $items;
    }
}

$services = [];
$serviceLoadError = '';
try {
    $stmt = $conn->query("
        SELECT *
        FROM services
        WHERE is_visible_to_clients = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $services = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    error_log("Client service load error: " . $e->getMessage());
    $serviceLoadError = "Unable to load services. Please check your database connection and try again.";
}

$pageTitle = "Home";
include "header.php";
?>

<!-- 1. EDITORIAL HERO SECTION WITH SUBTLE THREE.JS ABSTRACT BACKGROUND -->
<section class="corporate-hero">
    <!-- SUBTLE THREE.JS ABSTRACT SYNERGY BACKGROUND CANVAS LAYER -->
    <div id="hero-webgl-bg" class="hero-webgl-bg" aria-hidden="true"></div>
    <div class="hero-backdrop-pattern" aria-hidden="true"></div>
    
    <div class="corporate-hero-inner">
        <!-- LEFT: EDITORIAL MESSAGING COLUMN -->
        <div class="hero-content-col">
            <div class="hero-badge">
                <span class="badge-dot"></span>
                <span>P.S. Pakpi Enterprises • Imphal West</span>
            </div>
            
            <h1 class="hero-main-title">
                Building Better.<br>
                Supplying Smarter.<br>
                <span class="highlight-text">Growing Together.</span>
            </h1>

            <p class="hero-summary-text">
                Reliable construction, supply, bakery, and consultancy solutions built around quality, service, and trust for government, institutional, and private clients across Manipur.
            </p>

            <div class="hero-actions-row">
                <a href="#services" class="btn-primary hero-btn">
                    <span>Explore Our Services</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
                <a href="contact.php" class="btn-secondary hero-btn">
                    <span>Request a Quote</span>
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                </a>
            </div>

            <!-- TRUST METRICS STRIP -->
            <div class="hero-trust-metrics">
                <div class="metric-card">
                    <i class="fa-solid fa-landmark metric-icon" aria-hidden="true"></i>
                    <div>
                        <strong>Institutional & Public</strong>
                        <small>Strict statutory compliance</small>
                    </div>
                </div>
                <div class="metric-card">
                    <i class="fa-solid fa-layer-group metric-icon" aria-hidden="true"></i>
                    <div>
                        <strong>4 Core Pillars</strong>
                        <small>Integrated multidisciplinary desk</small>
                    </div>
                </div>
                <div class="metric-card">
                    <i class="fa-solid fa-map-pin metric-icon" aria-hidden="true"></i>
                    <div>
                        <strong>Manipur Operations</strong>
                        <small>Kwakeithel, Imphal West</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: 2D ENTERPRISE CREDENTIALS & SECTOR OVERVIEW CARD -->
        <div class="hero-visual-col">
            <div class="hero-credentials-card">
                <div class="credentials-card-header">
                    <span class="credentials-badge">Enterprise Profile</span>
                    <span class="credentials-reg">Imphal West • Registered</span>
                </div>
                
                <h3 class="credentials-title">Operational Sectors</h3>
                <p class="credentials-desc">Single-window administrative coordination across <?php echo count($services); ?> active business division<?php echo count($services) > 1 ? 's' : ''; ?>:</p>

                <div class="credentials-sectors-grid">
                    <?php if (!empty($services)): ?>
                        <?php foreach (array_slice($services, 0, 4) as $hSvc): 
                            $hsId = (int)$hSvc['id'];
                            $hsTitle = $hSvc['title'];
                            $hsDesc = !empty($hSvc['description']) ? $hSvc['description'] : 'Enterprise operations & execution';
                            $hsIcon = getServiceNavIcon($hsTitle);
                        ?>
                            <a href="service_detail.php?id=<?php echo $hsId; ?>" class="sector-mini-item">
                                <div class="sector-mini-icon" style="background: rgba(30, 58, 138, 0.08); color: <?php echo $hsIcon['color']; ?>;">
                                    <i class="fa-solid <?php echo $hsIcon['icon']; ?>"></i>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($hsTitle); ?></strong>
                                    <small><?php echo htmlspecialchars(mb_strimwidth($hsDesc, 0, 48, '...')); ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted p-3">Services will be listed here.</div>
                    <?php endif; ?>
                </div>

                <div class="credentials-card-footer">
                    <div class="credentials-stat">
                        <i class="fa-solid fa-shield-halved" style="color: var(--accent);"></i>
                        <span>Quality Assurance & Standardized Billing</span>
                    </div>
                    <a href="contact.php" class="credentials-link">
                        <span>Get Estimate</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. EDITORIAL TRUST & CREDIBILITY SECTION -->
<section class="trust-intro-section">
    <div class="section-container">
        <div class="trust-grid">
            <div class="trust-statement-card">
                <span class="section-eyebrow">Enterprise Hallmarks</span>
                <h2 class="trust-heading">An Established Single-Desk Partner for Multi-Disciplinary Delivery</h2>
                <p class="trust-paragraph">
                    P.S. Pakpi Enterprises operates with a mission to simplify project execution, procurement, and specialized commercial operations. Whether undertaking civil engineering works, managing bulk material logistics, baking commercial goods, or providing statutory consultancy, our teams adhere strictly to quality assurance, transparent documentation, and time-bound coordination.
                </p>
                <div class="trust-bullet-points">
                    <div class="trust-bullet">
                        <i class="fa-solid fa-check-circle" aria-hidden="true"></i>
                        <span>Single-point administrative accountability across all works</span>
                    </div>
                    <div class="trust-bullet">
                        <i class="fa-solid fa-check-circle" aria-hidden="true"></i>
                        <span>Registered enterprise operating from Kwakeithel, Imphal West</span>
                    </div>
                    <div class="trust-bullet">
                        <i class="fa-solid fa-check-circle" aria-hidden="true"></i>
                        <span>Transparent quotations, standardized billing & dedicated local support</span>
                    </div>
                </div>
            </div>
            
            <div class="trust-cards-cluster">
                <div class="stat-feature-box">
                    <div class="feature-icon-badge navy">
                        <i class="fa-solid fa-trowel-bricks" aria-hidden="true"></i>
                    </div>
                    <h3>Quality</h3>
                    <p>Adherence to national standards, verified structural execution, and rigorous material inspections.</p>
                </div>
                <div class="stat-feature-box">
                    <div class="feature-icon-badge sky">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    </div>
                    <h3>Reliability</h3>
                    <p>Prompt mobilization, milestone-based progress reporting, and dependable supply coordination.</p>
                </div>
                <div class="stat-feature-box">
                    <div class="feature-icon-badge amber">
                        <i class="fa-solid fa-award" aria-hidden="true"></i>
                    </div>
                    <h3>Integrity</h3>
                    <p>Clear rate breakdowns, comprehensive scope definition, and zero hidden charges.</p>
                </div>
                <div class="stat-feature-box">
                    <div class="feature-icon-badge emerald">
                        <i class="fa-solid fa-compass" aria-hidden="true"></i>
                    </div>
                    <h3>Local Reach</h3>
                    <p>Deep operational knowledge of terrain, administrative frameworks, and vendor networks in Manipur.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. EDITORIAL ABOUT SECTION -->
<section class="about-editorial-section" id="about">
    <div class="section-container">
        <div class="about-editorial-grid">
            <div>
                <span class="section-eyebrow">Who We Are</span>
                <h2 class="about-editorial-heading">
                    Built on quality.
                    <span>Driven by trust.</span>
                </h2>
            </div>
            <div>
                <p class="about-editorial-lead">
                    P.S. Pakpi Enterprises delivers integrated multi-disciplinary solutions tailored to government requirements, corporate buyers, and private clients across Manipur.
                </p>
                <p class="about-editorial-body">
                    We bridge the gap between complex logistical execution and client-friendly administration. By centralizing civil works, institutional supply chains, commercial bakery production, and statutory advisory under one registered corporate desk, we eliminate contractor friction and ensure consistent accountability at every milestone.
                </p>

                <div class="about-pillars-grid">
                    <div class="about-pillar-item">
                        <span class="about-pillar-num">Pillar 01</span>
                        <strong>Quality First</strong>
                        <p>Standardized processes and strict material benchmarks on every assignment.</p>
                    </div>
                    <div class="about-pillar-item">
                        <span class="about-pillar-num">Pillar 02</span>
                        <strong>Timely Execution</strong>
                        <p>Disciplined field coordination ensuring project schedules remain on track.</p>
                    </div>
                    <div class="about-pillar-item">
                        <span class="about-pillar-num">Pillar 03</span>
                        <strong>Transparent Pricing</strong>
                        <p>Itemized quotations, clear scope boundaries, and straightforward billing.</p>
                    </div>
                    <div class="about-pillar-item">
                        <span class="about-pillar-num">Pillar 04</span>
                        <strong>Dedicated Support</strong>
                        <p>Continuous communication from initial inquiry to final handover and beyond.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4. SERVICES DIRECTORY (WHAT WE DO) -->
<section class="services-catalog-section" id="services">
    <div class="section-container">
        <div class="section-header-row">
            <div>
                <span class="section-eyebrow">What We Do</span>
                <h2 class="section-title">Solutions built around your needs.</h2>
                <p class="section-subtitle">Explore our core divisions below to view detailed specifications, scope parameters, and online quote requests.</p>
            </div>
            <div>
                <a href="contact.php" class="btn-outline-dark">
                    <span>Request Custom Quote →</span>
                </a>
            </div>
        </div>

        <?php if ($serviceLoadError !== ''): ?>
            <div class="service-card error-state-card">
                <h3><i class="fa-solid fa-triangle-exclamation"></i> Database Notice</h3>
                <p><?php echo htmlspecialchars($serviceLoadError); ?></p>
            </div>
        <?php elseif (empty($services)): ?>
            <div class="service-card empty-state-card">
                <h3><i class="fa-solid fa-folder-open"></i> No Services Currently Listed</h3>
                <p>There are no client-visible services active at this time. Please contact our office directly.</p>
            </div>
        <?php else: ?>
            <div class="services-modern-grid">
                <?php 
                $counter = 1;
                $divisionTags = [
                    'Construction' => 'Infrastructure & Civil',
                    'Supply Works' => 'Logistics & Procurement',
                    'Bakery' => 'Food & Commercial Catering',
                    'Consultancy' => 'Advisory & Compliance'
                ];
                foreach ($services as $row): 
                    $serviceId = (int)($row['id'] ?? 0);
                    $serviceTitle = trim((string)($row['title'] ?? 'Service'));
                    $detailUrl = $serviceId > 0
                        ? 'service_detail.php?id=' . $serviceId
                        : 'service_detail.php?service=' . urlencode($serviceTitle);
                    $cardImage = clientServiceImage($row);
                    $features = clientServiceFeatures($row['features'] ?? '');
                    $numStr = str_pad((string)$counter++, 2, '0', STR_PAD_LEFT);
                    $tagStr = $divisionTags[$serviceTitle] ?? 'Specialized Service';
                    $iconData = getServiceNavIcon($serviceTitle);
                ?>
                    <article class="service-editorial-card">
                        <div class="service-card-image-wrap">
                            <img src="<?php echo htmlspecialchars($cardImage); ?>" 
                                 alt="<?php echo htmlspecialchars($serviceTitle); ?>" 
                                 class="service-card-img" 
                                 loading="lazy" 
                                 onerror="this.onerror=null; this.src='assets/images/default-service.jpg';">
                            <span class="service-card-num-badge"><?php echo $numStr; ?></span>
                            <?php if (!empty($row['is_featured'])): ?>
                                <span class="service-card-featured-badge"><i class="fa-solid fa-star"></i> Featured</span>
                            <?php endif; ?>
                            <div class="service-card-overlay"></div>
                        </div>

                        <div class="service-card-content">
                            <div class="service-card-meta-top">
                                <span class="service-card-tag"><?php echo htmlspecialchars($tagStr); ?></span>
                                <?php if (!empty($row['duration'])): ?>
                                    <span class="service-card-duration"><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($row['duration']); ?></span>
                                <?php endif; ?>
                            </div>

                            <h3 class="service-card-heading">
                                <a href="<?php echo htmlspecialchars($detailUrl); ?>"><?php echo htmlspecialchars($serviceTitle); ?></a>
                            </h3>

                            <p class="service-card-text">
                                <?php echo htmlspecialchars(mb_strimwidth($row['description'] ?: 'Open service for detailed specifications, pricing guidance, and online inquiry.', 0, 140, '...')); ?>
                            </p>

                            <?php if (!empty($features)): ?>
                                <ul class="service-card-feature-list">
                                    <?php foreach (array_slice($features, 0, 3) as $f): ?>
                                        <li><i class="fa-solid fa-check"></i> <?php echo htmlspecialchars($f); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <div class="service-card-bottom">
                                <?php if (!empty($row['price_range']) && !empty($row['show_price_to_clients'])): ?>
                                    <div class="service-card-price">
                                        <span class="price-label">Pricing</span>
                                        <strong><?php echo htmlspecialchars($row['price_range']); ?></strong>
                                    </div>
                                <?php else: ?>
                                    <div class="service-card-price">
                                        <span class="price-label">Consultation</span>
                                        <strong>Custom Quotes</strong>
                                    </div>
                                <?php endif; ?>

                                <a href="<?php echo htmlspecialchars($detailUrl); ?>" class="btn-service-view" aria-label="View specifications for <?php echo htmlspecialchars($serviceTitle); ?>">
                                    <span>View Details</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- 5. PRODUCTS & CONSTRUCTION SHOWCASE (IS 15658:2021 Concrete Pavers & Civil Works) -->
<section class="products-section" id="products">
    <div class="section-container">
        <div class="section-header-row">
            <div>
                <span class="section-eyebrow">Engineered Offerings</span>
                <h2 class="section-title">Products & Works Showcase</h2>
                <p class="section-subtitle">Engineered infrastructure materials and reliable institutional supplies across Manipur.</p>
            </div>
        </div>

        <div class="products-asymmetric-grid">
            <!-- HERO PRODUCT CARD: PAVER BLOCKS COMPLIANT WITH IS 15658:2021 -->
            <div class="product-hero-card">
                <div>
                    <span class="product-compliance-stamp">
                        <i class="fa-solid fa-certificate"></i> Compliance Benchmark: IS 15658:2021
                    </span>
                    <h3 class="product-hero-title">Precast Concrete Paver Blocks & Kerb Stones</h3>
                    <p class="product-hero-desc">
                        Manufactured for heavy-duty traffic corridors, urban footpaths, commercial parking plazas, and institutional compounds. Fabricated with high-grade aggregate mixes to meet rigorous compressive strength and durability specifications in strict compliance with <strong>IS 15658:2021</strong>.
                    </p>

                    <ul class="product-spec-list">
                        <li>
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>Complies with Indian Standard <strong>IS 15658:2021</strong></span>
                        </li>
                        <li>
                            <i class="fa-solid fa-cube"></i>
                            <span>High-density vibrated precast interlocking profiles</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-road"></i>
                            <span>Heavy, medium & light traffic grade classifications</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-truck-fast"></i>
                            <span>Direct bulk supply & site unloading across Manipur</span>
                        </li>
                    </ul>
                </div>

                <div>
                    <a href="service_detail.php?id=73" class="btn-primary">
                        <span>View Construction Specifications</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- SECONDARY PRODUCT STACK -->
            <div class="products-stack-col">
                <div class="product-secondary-card">
                    <span class="product-category-label">Institutional & Commercial</span>
                    <h4>Bulk Supply Logistics</h4>
                    <p>Organized procurement and structured delivery of office requisites, hardware, and specialized organizational supplies for public and private institutions.</p>
                    <div class="product-inline-specs">
                        <span>Procurement</span>
                        <span>Warehouse Dispatch</span>
                        <span>Verified Invoicing</span>
                    </div>
                </div>

                <div class="product-secondary-card">
                    <span class="product-category-label">Commercial Food Division</span>
                    <h4>Artisan Bakery & Event Supply</h4>
                    <p>Hygienic commercial bakery producing daily fresh breads, confectionery, and large-batch baked goods for corporate functions and public gatherings.</p>
                    <div class="product-inline-specs">
                        <span>Fresh Daily</span>
                        <span>Custom Orders</span>
                        <span>Event Supply</span>
                    </div>
                </div>

                <div class="product-secondary-card">
                    <span class="product-category-label">Advisory & Statutory</span>
                    <h4>Project & Statutory Consultancy</h4>
                    <p>Guidance in regulatory documentation, business compliance filings, rate analysis, and structured operational coordination.</p>
                    <div class="product-inline-specs">
                        <span>Documentation</span>
                        <span>Compliance Advisory</span>
                        <span>Local Execution</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 6. EDITORIAL PROCESS SECTION (HOW WE WORK) -->
<section class="process-section" id="process">
    <div class="section-container">
        <div class="section-header-row">
            <div>
                <span class="section-eyebrow">Standard Operating Procedure</span>
                <h2 class="section-title">How We Coordinate Your Project</h2>
                <p class="section-subtitle">A straightforward, milestone-based methodology ensuring total transparency from scope to completion.</p>
            </div>
        </div>

        <div class="process-timeline-grid">
            <div class="process-stage-card">
                <span class="process-stage-number">01</span>
                <h4>Understand</h4>
                <p>Initial consultation to assess technical requirements, location parameters, and schedule boundaries.</p>
            </div>
            
            <div class="process-stage-card">
                <span class="process-stage-number">02</span>
                <h4>Plan</h4>
                <p>Detailed quotation preparation outlining technical prerequisites, itemized pricing, and delivery schedules.</p>
            </div>

            <div class="process-stage-card">
                <span class="process-stage-number">03</span>
                <h4>Mobilize</h4>
                <p>Prompt allocation of skilled personnel, verified materials, and logistic dispatch to the work site.</p>
            </div>

            <div class="process-stage-card">
                <span class="process-stage-number">04</span>
                <h4>Inspect</h4>
                <p>Active field monitoring and benchmark quality checks to ensure compliance with standards.</p>
            </div>

            <div class="process-stage-card">
                <span class="process-stage-number">05</span>
                <h4>Deliver & Support</h4>
                <p>Formal handover with complete documentation, standardized billing, and dedicated post-service assistance.</p>
            </div>
        </div>
    </div>
</section>

<!-- 7. CLOSING STATEMENT CALL-TO-ACTION (Deep Charcoal / Warm Cream Typography) -->
<section class="closing-cta-section">
    <div class="section-container">
        <div class="closing-cta-card">
            <div>
                <span class="closing-cta-eyebrow">Start A Conversation</span>
                <h2 class="closing-cta-title">
                    Have a project in mind?<br>
                    Let's build something reliable.
                </h2>
                <p class="closing-cta-desc">
                    Whether you require civil works, bulk material supply, bakery orders, or professional advisory, our administrative desk is ready to assist.
                </p>
            </div>
            <div class="closing-cta-buttons">
                <a href="contact.php" class="btn-cta-light">
                    <span>Request a Quote →</span>
                </a>
                <a href="contact.php#location-contact" class="btn-cta-outline">
                    <span>Contact Our Office →</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- THREE.JS RUNTIME FOR ABSTRACT SYNERGY BACKGROUND -->
<script type="module" src="assets/js/three-scene.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Loading state on navigation buttons
    document.querySelectorAll('.btn-service-view, .hero-actions-row a, .closing-cta-buttons a, .sector-mini-item').forEach(function (link) {
        link.addEventListener('click', function () {
            if (this.getAttribute('href') && !this.getAttribute('href').startsWith('#')) {
                this.classList.add('is-loading');
                this.setAttribute('aria-busy', 'true');
            }
        });
    });

    // 2. Scroll Reveal Animations (IntersectionObserver)
    if ('IntersectionObserver' in window) {
        const revealElements = document.querySelectorAll(
            '.service-editorial-card, .stat-feature-box, .about-pillar-item, .product-hero-card, .product-secondary-card, .process-stage-card, .closing-cta-card'
        );

        revealElements.forEach((el, idx) => {
            el.classList.add('reveal-on-scroll');
            // Add stagger class based on index in row
            const staggerIdx = (idx % 4) + 1;
            el.classList.add('reveal-stagger-' + staggerIdx);
        });

        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px'
        });

        revealElements.forEach(el => revealObserver.observe(el));
    }
});
</script>

<?php include "footer.php"; ?>
