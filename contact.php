<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "db.php";

function findClientService(PDO $conn, string $serviceName) {
    $serviceName = trim($serviceName);
    if ($serviceName === '') {
        return null;
    }

    $aliases = [
        'Construction Works' => 'Construction',
        'Supply' => 'Supply Works',
    ];
    $lookupName = $aliases[$serviceName] ?? $serviceName;
    $likeName = '%' . str_replace(' Works', '', $lookupName) . '%';

    $stmt = $conn->prepare("
        SELECT id, title
        FROM services
        WHERE is_visible_to_clients = 1
          AND (
            lower(title) = lower(:lookup)
            OR lower(title) = lower(:requested)
            OR lower(title) LIKE lower(:like_name)
          )
        ORDER BY sort_order ASC, id ASC
        LIMIT 1
    ");
    $stmt->execute([
        ':lookup' => $lookupName,
        ':requested' => $serviceName,
        ':like_name' => $likeName,
    ]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    return $service ?: null;
}

$requestedService = trim($_GET['service'] ?? '');
if ($requestedService !== '') {
    $service = findClientService($conn, $requestedService);
    if ($service) {
        header("Location: service_detail.php?id=" . (int)$service['id'] . "#contact-form");
        exit;
    }
}

$services = [];
$loadError = '';
try {
    $stmt = $conn->query("
        SELECT id, title, description, image_url, features, price_range, duration
        FROM services
        WHERE is_visible_to_clients = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $services = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    error_log("Quick quote service load error: " . $e->getMessage());
    $loadError = 'Unable to load services for quick quote. Please try again later.';
}

$pageTitle = "Quick Quote & Inquiries";
include "header.php";
?>

<div class="contact-page-shell">
    <div class="section-container">
        <!-- BREADCRUMB -->
        <nav class="breadcrumb-bar" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span class="bc-sep" aria-hidden="true">/</span>
            <span class="bc-current">Quick Quote & Inquiries</span>
        </nav>

        <!-- HEADER BANNER -->
        <div class="contact-header-card">
            <div class="contact-header-content">
                <span class="section-eyebrow light">Direct Client Inquiries</span>
                <h1 class="contact-header-title">Request a Formal Quote</h1>
                <p class="contact-header-subtitle">
                    Select the business division below that best matches your project or requirements. You will be routed directly to the dedicated specification and inquiry form.
                </p>
            </div>
            <div class="contact-header-badge">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Reviewed within 24 business hours</span>
            </div>
        </div>

        <?php if ($loadError !== ''): ?>
            <div class="service-card error-state-card mt-30">
                <h3><i class="fa-solid fa-triangle-exclamation"></i> Service Catalog Unavailable</h3>
                <p><?php echo htmlspecialchars($loadError); ?></p>
            </div>
        <?php elseif (empty($services)): ?>
            <div class="service-card empty-state-card mt-30">
                <h3><i class="fa-solid fa-folder-open"></i> No Services Currently Available</h3>
                <p>There are no client-visible services active at this time. Please contact our administrative desk directly.</p>
            </div>
        <?php else: ?>
            <div class="contact-divisions-grid">
                <?php 
                $iconMap = [
                    'Bakery' => 'fa-cake-candles',
                    'Construction' => 'fa-trowel-bricks',
                    'Consultancy' => 'fa-chart-line',
                    'Supply Works' => 'fa-boxes-stacked'
                ];
                ?>
                <?php foreach ($services as $svc): ?>
                    <?php 
                        $sId = (int)$svc['id'];
                        $sTitle = trim((string)$svc['title']);
                        $iconClass = $iconMap[$sTitle] ?? 'fa-briefcase';
                    ?>
                    <a class="division-quote-card" href="service_detail.php?id=<?php echo $sId; ?>#contact-form">
                        <div class="division-icon-wrap">
                            <i class="fa-solid <?php echo $iconClass; ?>" aria-hidden="true"></i>
                        </div>
                        <div class="division-info">
                            <h3 class="division-title"><?php echo htmlspecialchars($sTitle); ?></h3>
                            <p class="division-desc"><?php echo htmlspecialchars($svc['description'] ?: 'Open inquiry form and view packages.'); ?></p>
                            <div class="division-meta-tags">
                                <?php if (!empty($svc['duration'])): ?>
                                    <span class="meta-tag"><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($svc['duration']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($svc['price_range'])): ?>
                                    <span class="meta-tag"><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($svc['price_range']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="division-arrow">
                            <span>Open Form</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- GENERAL INQUIRY / CUSTOM SCOPE FALLBACK -->
        <div class="general-inquiry-box">
            <div class="general-inquiry-copy">
                <i class="fa-solid fa-circle-question general-icon" aria-hidden="true"></i>
                <div>
                    <h3>Have a Multi-Division or Custom Project?</h3>
                    <p>If your project spans multiple requirements or needs specialized coordination, open our flexible scope inquiry desk.</p>
                </div>
            </div>
            <a href="service_detail.php?service=Many%20More#contact-form" class="btn-primary">
                <span>Custom Multi-Service Inquiry</span>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>

        <!-- LOCATION REASSURANCE STRIP -->
        <div class="contact-location-bar">
            <div class="loc-bar-item">
                <i class="fa-solid fa-building-flag" aria-hidden="true"></i>
                <span><strong>Office:</strong> Kwakeithel, Thokchom Leikai, Imphal West, Manipur, India</span>
            </div>
            <div class="loc-bar-item">
                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                <span><strong>Schedule:</strong> Monday – Saturday, 09:00 AM – 06:00 PM IST</span>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.division-quote-card, .general-inquiry-box a').forEach(function (link) {
        link.addEventListener('click', function () {
            link.classList.add('is-loading');
            link.setAttribute('aria-busy', 'true');
        });
    });
});
</script>

<?php include "footer.php"; ?>
