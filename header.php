<?php
// Ensure db connection is available for dynamic header navigation
if (!isset($conn) || !($conn instanceof PDO)) {
    @include_once __DIR__ . '/db.php';
}

$headerNavServices = [];
if (isset($conn) && $conn instanceof PDO) {
    try {
        $hdrStmt = $conn->query("
            SELECT id, title, description 
            FROM services 
            WHERE is_visible_to_clients = 1 
            ORDER BY sort_order ASC, id ASC
        ");
        if ($hdrStmt) {
            $headerNavServices = $hdrStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Header services query failed: " . $e->getMessage());
    }
}

// Icon helper function for header
if (!function_exists('getServiceNavIcon')) {
    function getServiceNavIcon($title) {
        $lower = strtolower($title);
        if (strpos($lower, 'construct') !== false) return ['icon' => 'fa-trowel-bricks', 'color' => '#1e3a8a'];
        if (strpos($lower, 'supply') !== false) return ['icon' => 'fa-boxes-stacked', 'color' => '#0284c7'];
        if (strpos($lower, 'bakery') !== false || strpos($lower, 'cake') !== false) return ['icon' => 'fa-cake-candles', 'color' => '#d97706'];
        if (strpos($lower, 'consult') !== false) return ['icon' => 'fa-chart-line', 'color' => '#16a34a'];
        return ['icon' => 'fa-briefcase', 'color' => '#4b5563'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : ''; ?>P.S. Pakpi Enterprises — Construction, Supply, Bakery & Consultancy | Imphal, Manipur</title>
    <meta name="description" content="P.S. Pakpi Enterprises is a multi-sector corporate enterprise based in Kwakeithel, Imphal West, Manipur. Delivering excellence in Construction Works, Institutional Supply, Commercial Bakery, and Strategic Consultancy.">
    <meta name="keywords" content="P.S. Pakpi Enterprises, Construction Imphal, Supply Works Manipur, Bakery Imphal, Consultancy Manipur, Government Contractors Imphal">
    <meta name="author" content="P.S. Pakpi Enterprises">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Meta -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="P.S. Pakpi Enterprises — Building Trust. Delivering Quality.">
    <meta property="og:description" content="Reliable multi-sector delivery in Construction, Institutional Supplies, Commercial Bakery, and Business Consultancy in Manipur, India.">
    <meta property="og:image" content="/ps-pakpi/assets/images/logo.png">
    
    <!-- Google Fonts: Plus Jakarta Sans, Inter, JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Enterprise Main CSS -->
    <link rel="stylesheet" href="/ps-pakpi/assets/style.css?v=2026-enterprise-v1">
    <link rel="stylesheet" href="/ps-pakpi/assets/enterprise-theme.css?v=2026-enterprise-v1">
    
    <!-- Structured Data (JSON-LD) for Local Business & Corporate Identity -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "LocalBusiness",
      "name": "P.S. Pakpi Enterprises",
      "image": "/ps-pakpi/assets/images/logo.png",
      "description": "Multi-division corporate enterprise providing Construction Works, Supply Works, Bakery products, and Professional Consultancy.",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Kwakeithel, Thokchom Leikai",
        "addressLocality": "Imphal West",
        "addressRegion": "Manipur",
        "postalCode": "795001",
        "addressCountry": "IN"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": "24.7869",
        "longitude": "93.9317"
      },
      "openingHoursSpecification": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
        "opens": "09:00",
        "closes": "18:00"
      },
      "priceRange": "$$"
    }
    </script>
</head>
<body>

<!-- ACCESSIBILITY: SKIP LINK -->
<a href="#main-content" class="skip-to-content">Skip to primary content</a>

<!-- TOP CORPORATE UTILITY STRIP -->
<div class="top-utility-bar">
    <div class="top-utility-inner">
        <div class="top-utility-item location">
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            <span>Kwakeithel, Thokchom Leikai, Imphal West, Manipur, India</span>
        </div>
        <div class="top-utility-right">
            <div class="top-utility-item hours">
                <i class="fa-solid fa-clock" aria-hidden="true"></i>
                <span>Mon – Sat: 09:00 AM – 06:00 PM IST</span>
            </div>
            <span class="utility-divider" aria-hidden="true">|</span>
            <a href="/ps-pakpi/admin/login.php" class="top-utility-link">
                <i class="fa-solid fa-lock" aria-hidden="true"></i> Admin Portal
            </a>
        </div>
    </div>
</div>

<!-- PRIMARY CORPORATE HEADER & NAVIGATION -->
<header class="site-header" id="site-header">
    <div class="header-inner">
        <!-- BRAND LOGO -->
        <a href="/ps-pakpi/index.php" class="brand-link" aria-label="P.S. Pakpi Enterprises Home">
            <div class="brand-logo-plate">
                <img src="/ps-pakpi/assets/images/logo.png" alt="P.S. Pakpi Enterprises Logo" width="46" height="46" onerror="this.style.display='none'">
            </div>
            <div class="brand-identity">
                <span class="brand-name">P.S. Pakpi Enterprises</span>
                <span class="brand-tagline">Quality • Integrity • Professional Execution</span>
            </div>
        </a>

        <!-- DESKTOP EDITORIAL NAVIGATION -->
        <nav class="desktop-nav" aria-label="Main Navigation">
            <ul class="nav-links">
                <li><a href="/ps-pakpi/index.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Home</a></li>
                <li><a href="/ps-pakpi/index.php#about" class="nav-link">About</a></li>
                <li class="nav-dropdown-wrapper">
                    <a href="/ps-pakpi/index.php#services" class="nav-link nav-has-dropdown">
                        Services <i class="fa-solid fa-chevron-down nav-caret" aria-hidden="true"></i>
                    </a>
                    <div class="nav-dropdown-menu">
                        <?php if (!empty($headerNavServices)): ?>
                            <?php foreach ($headerNavServices as $navSvc): 
                                $sId = (int)$navSvc['id'];
                                $sTitle = $navSvc['title'];
                                $sDesc = !empty($navSvc['description']) ? $navSvc['description'] : 'View service specifications & quote options';
                                $iconData = getServiceNavIcon($sTitle);
                            ?>
                                <a href="/ps-pakpi/service_detail.php?id=<?php echo $sId; ?>" class="nav-dropdown-item">
                                    <i class="fa-solid <?php echo $iconData['icon']; ?>" style="color: <?php echo $iconData['color']; ?>;"></i>
                                    <div>
                                        <strong><?php echo htmlspecialchars($sTitle); ?></strong>
                                        <small><?php echo htmlspecialchars(mb_strimwidth($sDesc, 0, 48, '...')); ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <a href="/ps-pakpi/index.php#services" class="nav-dropdown-item">
                                <i class="fa-solid fa-boxes-stacked" style="color: var(--primary);"></i>
                                <div>
                                    <strong>All Services</strong>
                                    <small>Explore enterprise divisions</small>
                                </div>
                            </a>
                        <?php endif; ?>
                    </div>
                </li>
                <li><a href="/ps-pakpi/index.php#products" class="nav-link">Products & Works</a></li>
                <li><a href="/ps-pakpi/index.php#process" class="nav-link">Process</a></li>
                <li><a href="/ps-pakpi/contact.php" class="nav-link">Contact</a></li>
            </ul>
        </nav>

        <!-- HEADER CTAS & MOBILE TOGGLE -->
        <div class="header-action-group">
            <a href="/ps-pakpi/contact.php" class="btn-quote-header">
                <span>Request a Quote</span>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>

            <!-- MOBILE HAMBURGER -->
            <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="mobileDrawer">
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
            </button>
        </div>
    </div>
</header>

<!-- ACCESSIBLE MOBILE DRAWER -->
<div class="mobile-drawer" id="mobileDrawer" aria-hidden="true">
    <div class="mobile-drawer-inner">
        <div class="mobile-drawer-header">
            <div class="brand-identity">
                <span class="brand-name">P.S. Pakpi Enterprises</span>
                <span class="brand-tagline">Imphal West, Manipur</span>
            </div>
            <button type="button" class="mobile-drawer-close" id="mobileDrawerClose" aria-label="Close menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <nav class="mobile-nav-list" aria-label="Mobile Navigation">
            <div class="mobile-nav-section-title">Navigation</div>
            <a href="/ps-pakpi/index.php" class="mobile-nav-item">
                <i class="fa-solid fa-house"></i> Home
            </a>
            <a href="/ps-pakpi/index.php#about" class="mobile-nav-item">
                <i class="fa-solid fa-circle-info"></i> About
            </a>
            <div class="mobile-nav-section-title">Business Divisions</div>
            <?php if (!empty($headerNavServices)): ?>
                <?php foreach ($headerNavServices as $navSvc): 
                    $sId = (int)$navSvc['id'];
                    $sTitle = $navSvc['title'];
                    $iconData = getServiceNavIcon($sTitle);
                ?>
                    <a href="/ps-pakpi/service_detail.php?id=<?php echo $sId; ?>" class="mobile-nav-item sub">
                        <i class="fa-solid <?php echo $iconData['icon']; ?>"></i> <?php echo htmlspecialchars($sTitle); ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <a href="/ps-pakpi/index.php#services" class="mobile-nav-item sub">
                    <i class="fa-solid fa-boxes-stacked"></i> All Services
                </a>
            <?php endif; ?>
            
            <div class="mobile-nav-section-title">Explore</div>
            <a href="/ps-pakpi/index.php#products" class="mobile-nav-item">
                <i class="fa-solid fa-cubes"></i> Products & Works
            </a>
            <a href="/ps-pakpi/index.php#process" class="mobile-nav-item">
                <i class="fa-solid fa-diagram-project"></i> How We Work
            </a>
            <a href="/ps-pakpi/contact.php" class="mobile-nav-item">
                <i class="fa-solid fa-envelope"></i> Contact Us
            </a>
            <a href="/ps-pakpi/admin/login.php" class="mobile-nav-item admin">
                <i class="fa-solid fa-lock"></i> Admin Portal
            </a>
        </nav>

        <div class="mobile-drawer-footer">
            <a href="/ps-pakpi/contact.php" class="btn-primary w-100 text-center">
                <i class="fa-solid fa-paper-plane"></i> Quick Quote
            </a>
            <p class="mobile-drawer-contact">
                <i class="fa-solid fa-map-pin"></i> Kwakeithel, Thokchom Leikai, Imphal West, Manipur
            </p>
        </div>
    </div>
</div>
<div class="mobile-drawer-overlay" id="mobileDrawerOverlay"></div>

<main id="main-content" tabindex="-1">
