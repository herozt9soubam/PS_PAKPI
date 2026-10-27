<?php
/**
 * P.S PAKPI ENTERPRISES - SERVICE DETAIL PAGE
 * COMPLETE WITH FIELD VISIBILITY CONTROLS
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once "db.php";
require_once('admin/includes/functions.php');

// ============================================
// DATABASE CONNECTION CHECK
// ============================================
if (!$conn) {
    die("Database connection failed");
}

// ========== LOAD FIELD VISIBILITY SETTINGS ==========
$visibility = getFieldVisibility($conn);

// ============================================
// HELPER FUNCTIONS
// ============================================
function getOptions(PDO $conn, string $table, array $fallback) {
    try {
        $stmt = $conn->prepare("SELECT name FROM {$table} ORDER BY name");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return !empty($data) ? $data : $fallback;
    } catch (PDOException $e) {
        error_log("Error fetching {$table}: " . $e->getMessage());
        return $fallback;
    }
}

function parsePgArray($pgArray) {
    if (empty($pgArray) || $pgArray === '{}') return [];
    $json = json_decode($pgArray, true);
    if (is_array($json)) return array_filter($json);

    $content = trim($pgArray, '{}');
    if (empty($content)) return [];
    
    $items = [];
    $current = '';
    $inQuotes = false;
    $len = strlen($content);
    
    for ($i = 0; $i < $len; $i++) {
        $char = $content[$i];
        
        if ($char === '"' && ($i === 0 || $content[$i-1] !== '\\')) {
            $inQuotes = !$inQuotes;
            $current .= $char;
        } elseif ($char === ',' && !$inQuotes) {
            $items[] = trim($current);
            $current = '';
        } else {
            $current .= $char;
        }
    }
    
    if (!empty($current)) {
        $items[] = trim($current);
    }
    
    $items = array_map(function($item) {
        $item = trim($item);
        if (strlen($item) >= 2 && $item[0] === '"' && $item[strlen($item)-1] === '"') {
            $item = substr($item, 1, -1);
        }
        $item = str_replace('\\"', '"', $item);
        $item = str_replace('\\\\', '\\', $item);
        return $item;
    }, $items);
    
    return array_filter($items);
}

function parseServiceFeatures($features) {
    return parsePgArray($features);
}

function requestedServiceTitle() {
    $service = trim($_GET['service'] ?? $_GET['title'] ?? '');
    return $service !== '' ? $service : '';
}

// ============================================
// FETCH COUNTRIES, REGIONS, AND INDUSTRIES
// ============================================
$countries = [];
try {
    $stmt = $conn->prepare("SELECT name, code FROM countries ORDER BY name");
    $stmt->execute();
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching countries: " . $e->getMessage());
}

$industries = [];
try {
    $stmt = $conn->prepare("SELECT name FROM industries ORDER BY name");
    $stmt->execute();
    $industries = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("Error fetching industries: " . $e->getMessage());
}

$regions = [];
try {
    $stmt = $conn->prepare("SELECT DISTINCT region FROM countries WHERE region IS NOT NULL AND region != '' ORDER BY region");
    $stmt->execute();
    $regions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("Error fetching regions: " . $e->getMessage());
}

// ============================================
// GET SERVICE ID OR TITLE
// ============================================
$service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($service_id <= 0) {
    $requestedTitle = requestedServiceTitle();
    if ($requestedTitle !== '') {
        $lookupTitle = $requestedTitle;
        $aliases = [
            'Construction Works' => 'Construction',
            'Supply' => 'Supply Works',
        ];
        if (isset($aliases[$lookupTitle])) {
            $lookupTitle = $aliases[$lookupTitle];
        }

        $stmt = $conn->prepare("
            SELECT id
            FROM services
            WHERE is_visible_to_clients = 1
              AND (
                lower(title) = lower(:title)
                OR lower(title) = lower(:requested)
                OR lower(title) LIKE lower(:like_title)
              )
            ORDER BY sort_order ASC, id ASC
            LIMIT 1
        ");
        $likeTitle = '%' . str_replace(' Works', '', $lookupTitle) . '%';
        $stmt->execute([
            ':title' => $lookupTitle,
            ':requested' => $requestedTitle,
            ':like_title' => $likeTitle,
        ]);
        $service_id = (int)$stmt->fetchColumn();
    }

    if ($service_id <= 0) {
        header("Location: index.php");
        exit;
    }
}

// ============================================
// FETCH SERVICE DETAILS
// ============================================
try {
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = :id");
    $stmt->bindParam(':id', $service_id, PDO::PARAM_INT);
    $stmt->execute();
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        header("Location: index.php");
        exit;
    }
    
    if (empty($service['is_visible_to_clients'])) {
        header("Location: index.php");
        exit;
    }
    
    $display_image = 'assets/images/default-service.jpg';
    if (!empty($service['image_url'])) {
        $rawImg = trim((string)$service['image_url']);
        if (strpos($rawImg, 'data:image') === 0 || strpos($rawImg, 'http') === 0) {
            $display_image = $rawImg;
        } elseif (strpos($rawImg, '/') === 0) {
            $display_image = $rawImg;
        } else {
            $cleanName = basename($rawImg);
            if (file_exists("assets/images/services/" . $cleanName)) {
                $display_image = "assets/images/services/" . $cleanName;
            } elseif (file_exists("uploads/" . $cleanName)) {
                $display_image = "uploads/" . $cleanName;
            } elseif (file_exists("assets/images/" . $cleanName)) {
                $display_image = "assets/images/" . $cleanName;
            }
        }
    }
    
    // Fetch related services dynamically from database
    $related_services = [];
    try {
        $relStmt = $conn->prepare("
            SELECT id, title, description, image_url, price_range, duration 
            FROM services 
            WHERE is_visible_to_clients = 1 AND id != :current_id 
            ORDER BY sort_order ASC, id ASC 
            LIMIT 3
        ");
        $relStmt->execute([':current_id' => $service_id]);
        $related_services = $relStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching related services: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    error_log("Error fetching service: " . $e->getMessage());
    die("Database error");
}

// ============================================
// FETCH QUOTES (only visible ones)
// ============================================
$quotes = [];
$has_quotes = false;

if (!empty($service['allow_client_quotes'])) {
    try {
        $quote_stmt = $conn->prepare("
            SELECT * FROM service_quotes 
            WHERE service_id = :service_id 
              AND is_active = 1 
              AND is_visible_to_clients = 1
            ORDER BY sort_order ASC, id ASC
        ");
        $quote_stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
        $quote_stmt->execute();
        $quotes = $quote_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($quotes)) {
            $has_quotes = true;
            foreach ($quotes as $key => $quote) {
                $quotes[$key]['inclusions_array'] = parsePgArray($quote['inclusions'] ?? '');
                $quotes[$key]['exclusions_array'] = parsePgArray($quote['exclusions'] ?? '');
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching quotes: " . $e->getMessage());
    }
}

// ============================================
// GROUP QUOTES BY CATEGORY
// ============================================
$grouped_quotes = [];
if ($has_quotes) {
    foreach ($quotes as $quote) {
        $category = !empty($quote['quote_category']) ? $quote['quote_category'] : 'Standard Packages';
        if (!isset($grouped_quotes[$category])) {
            $grouped_quotes[$category] = [];
        }
        $grouped_quotes[$category][] = $quote;
    }
}

// ============================================
// HANDLE INQUIRY FORM SUBMISSION
// ============================================
$form_error = '';
$quote_error = '';

if (!empty($service['allow_client_quotes']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ---------- STANDARD INQUIRY ----------
    if (!isset($_POST['is_quote_request'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $organization = trim($_POST['organization'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $industry = trim($_POST['industry'] ?? '');
        $help_text = trim($_POST['help_text'] ?? '');
        $consent = isset($_POST['consent']) ? 1 : 0;
        
        $errors = [];
        if (empty($full_name)) $errors[] = "Full name is required";
        if (empty($email)) $errors[] = "Email is required";
        if (empty($phone)) $errors[] = "Phone is required";
        if (empty($organization)) $errors[] = "Organization is required";
        if (empty($country)) $errors[] = "Country is required";
        if (empty($region)) $errors[] = "Region is required";
        if (empty($industry)) $errors[] = "Industry is required";
        if (empty($help_text)) $errors[] = "How can we help you? is required";
        if (!$consent) $errors[] = "You must consent to data processing";
        
        // Phone validation
        if (!empty($phone)) {
            $phoneDigits = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) > 13) $errors[] = "Phone number too long (max 13 characters)";
            elseif (strlen($phoneDigits) < 10) $errors[] = "Phone must have at least 10 digits";
        }
        
        // Email validation
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address";
        }
        
        // Help text length
        if (strlen($help_text) > 1500) $errors[] = "Help text must be 1500 characters or less";
        
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO service_inquiries 
                    (name, email, phone, message, service_id, service_name, 
                     organization, country, region, industry, status, created_at) 
                    VALUES 
                    (:name, :email, :phone, :message, :service_id, :service_name, 
                     :organization, :country, :region, :industry, 'new', CURRENT_TIMESTAMP)
                ");
                
                $stmt->bindParam(':name', $full_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':message', $help_text);
                $stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
                $stmt->bindParam(':service_name', $service['title']);
                $stmt->bindParam(':organization', $organization);
                $stmt->bindParam(':country', $country);
                $stmt->bindParam(':region', $region);
                $stmt->bindParam(':industry', $industry);
                
                if ($stmt->execute()) {
                    $inquiry_id = $conn->lastInsertId();
                    
                    // Store in session for success page
                    $_SESSION['inquiry_success'] = [
                        'id' => $inquiry_id,
                        'full_name' => $full_name,
                        'email' => $email,
                        'phone' => $phone,
                        'organization' => $organization,
                        'country' => $country,
                        'region' => $region,
                        'industry' => $industry,
                        'help_text' => $help_text,
                        'service_id' => $service_id,
                        'service_name' => $service['title'],
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    
                    header("Location: inquirySuccess.php?id=" . $inquiry_id);
                    exit;
                }
            } catch (PDOException $e) {
                $form_error = "Database error: " . $e->getMessage();
                error_log("Inquiry error: " . $e->getMessage());
            }
        } else {
            $form_error = implode("<br>", $errors);
        }
    }
    
    else {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $organization = trim($_POST['organization'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $industry = trim($_POST['industry'] ?? '');
        $project_scope = trim($_POST['project_scope'] ?? '');
        $requested_budget = isset($_POST['requested_budget']) ? floatval($_POST['requested_budget']) : 0;
        $timeline_requirement = trim($_POST['timeline_requirement'] ?? '');
        $specific_requirements = trim($_POST['specific_requirements'] ?? '');
        $quote_type = trim($_POST['quote_type'] ?? 'standard');
        $quote_id = isset($_POST['quote_id']) ? intval($_POST['quote_id']) : 0;
        $consent = isset($_POST['consent']) ? 1 : 0;
        
        $errors = [];
        if (empty($full_name)) $errors[] = "Full name is required";
        if (empty($email)) $errors[] = "Email is required";
        if (empty($phone)) $errors[] = "Phone is required";
        if (empty($country)) $errors[] = "Country is required";
        if (empty($region)) $errors[] = "Region is required";
        if (empty($industry)) $errors[] = "Industry is required";
        if (empty($specific_requirements)) $errors[] = "Specific requirements are required";
        if (!$consent) $errors[] = "You must consent to data processing";
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address";
        }
        
        if (!empty($phone)) {
            $phoneDigits = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) > 13) $errors[] = "Phone number too long (max 13 characters)";
            elseif (strlen($phoneDigits) < 10) $errors[] = "Phone must have at least 10 digits";
        }
        
        if (empty($errors)) {
            try {
                // Build detailed message
                $message = "QUOTE REQUEST";
                if ($quote_id > 0) {
                    // Get quote details
                    $quote_stmt = $conn->prepare("SELECT quote_title FROM service_quotes WHERE id = ?");
                    $quote_stmt->execute([$quote_id]);
                    $selected_quote = $quote_stmt->fetch();
                    if ($selected_quote) {
                        $message .= " for: " . $selected_quote['quote_title'] . "\n";
                    }
                }
                $message .= "\n--- Contact Information ---\n";
                $message .= "Name: " . $full_name . "\n";
                $message .= "Email: " . $email . "\n";
                $message .= "Phone: " . $phone . "\n";
                $message .= "Organization: " . $organization . "\n";
                $message .= "Country: " . $country . "\n";
                $message .= "Region: " . $region . "\n";
                $message .= "Industry: " . $industry . "\n";
                $message .= "Quote Type: " . ucfirst($quote_type) . "\n";
                if (!empty($project_scope)) $message .= "Project Scope: " . $project_scope . "\n";
                if ($requested_budget > 0) $message .= "Budget: $" . number_format($requested_budget) . "\n";
                if (!empty($timeline_requirement)) $message .= "Timeline: " . $timeline_requirement . "\n\n";
                $message .= "--- Specific Requirements ---\n" . $specific_requirements;
                
                $stmt = $conn->prepare("
                    INSERT INTO service_inquiries 
                    (name, email, phone, message, service_id, service_name, quote_id,
                     organization, country, region, industry, status, created_at) 
                    VALUES 
                    (:name, :email, :phone, :message, :service_id, :service_name, :quote_id,
                     :organization, :country, :region, :industry, 'new', CURRENT_TIMESTAMP)
                ");
                
                $stmt->bindParam(':name', $full_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':message', $message);
                $stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
                $stmt->bindParam(':service_name', $service['title']);
                $stmt->bindParam(':quote_id', $quote_id, PDO::PARAM_INT);
                $stmt->bindParam(':organization', $organization);
                $stmt->bindParam(':country', $country);
                $stmt->bindParam(':region', $region);
                $stmt->bindParam(':industry', $industry);
                
                if ($stmt->execute()) {
                    $inquiry_id = $conn->lastInsertId();
                    
                    // Store in session for success page
                    $_SESSION['inquiry_success'] = [
                        'id' => $inquiry_id,
                        'full_name' => $full_name,
                        'email' => $email,
                        'phone' => $phone,
                        'organization' => $organization,
                        'country' => $country,
                        'region' => $region,
                        'industry' => $industry,
                        'help_text' => $specific_requirements,
                        'service_id' => $service_id,
                        'service_name' => $service['title'],
                        'quote_type' => $quote_type,
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    
                    header("Location: inquirySuccess.php?id=" . $inquiry_id . "&type=quote");
                    exit;
                }
            } catch (PDOException $e) {
                $quote_error = "Database error: " . $e->getMessage();
                error_log("Quote request error: " . $e->getMessage());
            }
        } else {
            $quote_error = implode("<br>", $errors);
        }
    }
}

// ============================================
// INCLUDE HEADER
// ============================================
$pageTitle = htmlspecialchars($service['title'] ?? 'Service Details');
include "header.php";
?>

<!-- AMBIENT BACKGROUND WITH THREE.JS & GRADIENT LIGHT ORBS -->
<div class="service-detail-bg-canvas" id="service-webgl-bg" aria-hidden="true"></div>
<div class="service-ambient-mesh" aria-hidden="true">
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
</div>

<!-- MAIN CONTAINER -->
<div class="main-container service-detail-container">
    
    <!-- SIDEBAR - SERVICES MENU -->
    <aside class="sidebar-menu glass-panel">
        <div class="sidebar-header-box">
            <span class="sidebar-eyebrow">Enterprise Solutions</span>
            <h3>Our Services</h3>
        </div>
        <?php
        try {
            $menuStmt = $conn->prepare("SELECT id, title FROM services WHERE is_visible_to_clients = 1 ORDER BY sort_order ASC, id ASC");
            $menuStmt->execute();
            $menuServices = $menuStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($menuServices)): ?>
                <ul class="menu-services-list">
                    <?php foreach ($menuServices as $menuService): ?>
                        <li class="menu-service-item">
                            <a href="service_detail.php?id=<?php echo $menuService['id']; ?>" 
                               class="menu-service-link <?php echo ($menuService['id'] == $service_id) ? 'active' : ''; ?>">
                                <span><?php echo htmlspecialchars($menuService['title']); ?></span>
                                <i class="fa-solid fa-chevron-right arrow-icon" aria-hidden="true"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">No services available.</p>
            <?php endif;
        } catch (Exception $e) {
            echo '<p class="text-muted">Unable to load services.</p>';
        } ?>
        
        <div class="sidebar-back-link">
            <a href="index.php"><span>←</span> Return to Overview</a>
        </div>
    </aside>
    
    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <!-- ERROR MESSAGES -->
        <?php if (!empty($form_error)): ?>
            <div class="error-message"><?php echo $form_error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($quote_error)): ?>
            <div class="error-message"><?php echo $quote_error; ?></div>
        <?php endif; ?>
        
        <!-- BREADCRUMB -->
        <nav class="breadcrumb-nav" aria-label="Breadcrumb">
            <div class="breadcrumb">
                <a href="index.php">Home</a> 
                <span class="breadcrumb-sep">/</span>
                <a href="index.php#services">Services</a> 
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current"><?php echo htmlspecialchars($service['title']); ?></span>
            </div>
        </nav>
        
        <!-- 2-COLUMN HERO SECTION -->
        <div class="service-header glass-card-hero">
            <!-- LEFT: Core Narrative, Badges, Meta & CTA -->
            <div class="service-info">
                <div class="service-badge-strip">
                    <span class="service-category-badge">
                        <i class="fa-solid fa-layer-group"></i> Business Division
                    </span>
                    <?php if (!empty($service['is_featured']) && $service['is_featured'] && isFieldVisible($visibility, 'service_featured')): ?>
                        <span class="service-featured-pill">
                            <i class="fa-solid fa-star"></i> Featured Division
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (isFieldVisible($visibility, 'service_title')): ?>
                <h1 class="service-detail-title"><?php echo htmlspecialchars($service['title']); ?></h1>
                <?php endif; ?>
                
                <?php if (isFieldVisible($visibility, 'service_description') && !empty($service['description'])): ?>
                <p class="service-tagline"><?php echo htmlspecialchars($service['description']); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($service['price_range']) || !empty($service['duration']) || !empty($service['is_featured'])): ?>
                    <div class="service-meta">
                        <?php if (!empty($service['price_range']) && !empty($service['show_price_to_clients']) && isFieldVisible($visibility, 'service_price_range')): ?>
                            <div class="meta-item">
                                <span class="meta-icon"><i class="fa-solid fa-tag"></i></span>
                                <div>
                                    <span class="meta-label">Commercial Range</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($service['price_range']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($service['duration']) && isFieldVisible($visibility, 'service_duration')): ?>
                            <div class="meta-item">
                                <span class="meta-icon"><i class="fa-solid fa-clock"></i></span>
                                <div>
                                    <span class="meta-label">Standard Duration</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($service['duration']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="service-hero-actions">
                    <?php if (!empty($service['allow_client_quotes']) && isFieldVisible($visibility, 'inquiry_form')): ?>
                        <a href="#contact-form" class="cta-button primary-glow">
                            <span>Request Formal Inquiry</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <?php if ($has_quotes): ?>
                            <a href="#quote-section" class="btn-secondary-glass">
                                <span>Browse Packages</span>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="contact.php" class="cta-button primary-glow">
                            <span>General Inquiry</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT: Service Image Container with Frosted Frame -->
            <?php if (isFieldVisible($visibility, 'service_image')): ?>
            <div class="service-image-container">
                <div id="service-photo-view" class="service-photo-wrapper">
                    <img src="<?php echo htmlspecialchars($display_image); ?>" 
                         alt="<?php echo htmlspecialchars($service['title']); ?>"
                         loading="eager"
                         onerror="this.onerror=null; this.src='assets/images/default-service.jpg';">
                    <div class="image-glass-shimmer"></div>
                    <div class="image-corner-tag">
                        <i class="fa-solid fa-shield-check"></i> Verified Capability
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- SERVICE DETAILS -->
        <div class="service-content glass-panel">
            <?php if (isFieldVisible($visibility, 'service_detailed_description')): ?>
            <div class="content-header-row">
                <span class="section-kicker">Scope & Specifications</span>
                <h2>Detailed Service Overview</h2>
            </div>
            <div class="detailed-description">
                <?php 
                if (!empty($service['detailed_description']) && !empty($service['show_description_to_clients'])) {
                    echo '<p>' . nl2br(htmlspecialchars($service['detailed_description'])) . '</p>';
                } elseif (!empty($service['show_description_to_clients'])) {
                    echo '<p>No detailed description available for this service. Please contact us for more information.</p>';
                } else {
                    echo '<p>Detailed description is hidden. Please contact us for more information.</p>';
                }
                ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($service['features']) && isFieldVisible($visibility, 'service_features')): ?>
                <div class="service-features">
                    <h3>Key Features</h3>
                    <ul>
                        <?php 
                        $featuresArray = parseServiceFeatures($service['features']);
                        foreach ($featuresArray as $feature):
                            $feature = trim($feature);
                            if (!empty($feature)): ?>
                                <li><?php echo htmlspecialchars($feature); ?></li>
                            <?php endif;
                        endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- ======================================== -->
        <!-- MULTIPLE QUOTE SECTIONS                  -->
        <!-- ======================================== -->
        
        <?php if (!empty($service['allow_client_quotes']) && isFieldVisible($visibility, 'quote_section')): ?>
        
            <?php if ($has_quotes && !empty($grouped_quotes)): ?>
            
            <div class="quote-section" id="quote-section">
                <div class="quote-header">
                    <h2 class="quote-title">Available Quotes for <?php echo htmlspecialchars($service['title']); ?></h2>
                    <div class="quote-badge"><i>💰</i> Choose the package that fits your needs</div>
                </div>
                
                <?php foreach ($grouped_quotes as $category => $category_quotes): ?>
                    
                    <?php if (count($grouped_quotes) > 1 && isFieldVisible($visibility, 'quote_category')): ?>
                        <div class="category-header">
                            <h3><?php echo htmlspecialchars($category); ?></h3>
                        </div>
                    <?php endif; ?>
                    
                    <div class="pricing-cards">
                        <?php foreach ($category_quotes as $quote): ?>
                        <!-- QUOTE CARD -->
                        <div class="pricing-card <?php echo (!empty($quote['is_featured_quote']) && isFieldVisible($visibility, 'quote_featured')) ? 'featured' : ''; ?>">
                            <?php if (!empty($quote['is_featured_quote']) && isFieldVisible($visibility, 'quote_featured')): ?>
                                <div class="pricing-badge">Most Popular</div>
                            <?php endif; ?>
                            
                            <?php if (isFieldVisible($visibility, 'quote_title')): ?>
                            <div class="pricing-name"><?php echo htmlspecialchars($quote['quote_title'] ?? 'Standard Package'); ?></div>
                            <?php endif; ?>
                            
                            <?php if (!empty($quote['quote_code']) && isFieldVisible($visibility, 'quote_code')): ?>
                                <div class="quote-code">Code: <?php echo htmlspecialchars($quote['quote_code']); ?></div>
                            <?php endif; ?>
                            
                            <?php if (!empty($service['show_price_to_clients']) && isFieldVisible($visibility, 'quote_price')): ?>
                            <div class="pricing-amount">
                                <?php 
                                switch($quote['price_type'] ?? '') {
                                    case 'fixed':
                                        echo '₹' . number_format($quote['fixed_price'] ?? 0);
                                        break;
                                    case 'range':
                                        echo '₹' . number_format($quote['min_price'] ?? 0) . ' - ₹' . number_format($quote['max_price'] ?? 0);
                                        break;
                                    case 'starting_at':
                                        echo 'From ₹' . number_format($quote['min_price'] ?? 0);
                                        break;
                                    default:
                                        echo 'Custom Quote';
                                }
                                ?>
                                <?php if (!empty($quote['has_discount']) && isFieldVisible($visibility, 'quote_discount')): ?>
                                    <small class="discount-badge">
                                        Save <?php echo $quote['discount_percentage']; ?>% 
                                        <?php echo !empty($quote['discount_description']) ? '- ' . htmlspecialchars($quote['discount_description']) : ''; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isFieldVisible($visibility, 'quote_duration')): ?>
                            <div class="pricing-duration">
                                <strong>Duration:</strong> 
                                <?php 
                                if (!empty($quote['duration'])) {
                                    echo htmlspecialchars($quote['duration']);
                                } else {
                                    echo ($quote['min_duration'] ?? '1') . ' - ' . ($quote['max_duration'] ?? '12') . ' months';
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isFieldVisible($visibility, 'quote_description')): ?>
                            <div class="pricing-description">
                                <?php echo htmlspecialchars($quote['quote_description'] ?? ''); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($quote['inclusions_array']) && isFieldVisible($visibility, 'quote_inclusions')): ?>
                                <ul class="pricing-features">
                                    <?php foreach ($quote['inclusions_array'] as $inclusion): 
                                        if (!empty($inclusion)): ?>
                                            <li><?php echo htmlspecialchars($inclusion); ?></li>
                                        <?php endif;
                                    endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            
                            <?php if (!empty($quote['exclusions_array']) && isFieldVisible($visibility, 'quote_exclusions')): ?>
                                <div class="exclusions">
                                    <strong>Exclusions:</strong>
                                    <ul>
                                        <?php foreach ($quote['exclusions_array'] as $exclusion): ?>
                                            <li>✗ <?php echo htmlspecialchars($exclusion); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Custom Fields for this quote -->
                            <?php
                            $custom_values = getQuoteCustomValues($conn, $quote['id']);
                            if (!empty($custom_values)): ?>
                                <div class="quote-custom-fields">
                                    <h4>Additional Details</h4>
                                    <ul>
                                        <?php foreach ($custom_values as $cv): 
                                            if (isFieldVisible($visibility, 'custom_' . $cv['field_id'])): ?>
                                            <li><strong><?php echo htmlspecialchars($cv['field_name']); ?>:</strong> <?php echo htmlspecialchars($cv['field_value']); ?></li>
                                        <?php endif; endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <div class="pricing-action">
                                <button onclick="openQuoteRequestForm(<?php echo $service_id; ?>, '<?php echo htmlspecialchars(addslashes($service['title'])); ?>', false, <?php echo $quote['id']; ?>)" 
                                        class="btn-quote">
                                    <span>💰</span> Request This Quote
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                
                <!-- CUSTOM QUOTE CARD -->
                <div class="pricing-cards custom-quote">
                    <div class="pricing-card">
                        <div class="pricing-name">Custom Project</div>
                        <div class="pricing-amount">
                            Custom Quote
                            <small>Tailored to your needs</small>
                        </div>
                        <div class="pricing-duration"><strong>Timeline:</strong> Flexible</div>
                        <div class="pricing-description">Need something specific? Tell us about your requirements and we'll create a custom solution.</div>
                        <ul class="pricing-features">
                            <li>Personalized consultation</li>
                            <li>Custom scope of work</li>
                            <li>Flexible pricing options</li>
                            <li>Dedicated project manager</li>
                        </ul>
                        <div class="pricing-action">
                            <button onclick="openQuoteRequestForm(<?php echo $service_id; ?>, '<?php echo htmlspecialchars(addslashes($service['title'])); ?>', true)" 
                                    class="btn-quote secondary">
                                <span>✏️</span> Request Custom Quote
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($service['requires_admin_approval']) && isFieldVisible($visibility, 'quote_approval_note')): ?>
                    <div class="alert alert-info">
                        <strong>Note:</strong> All quote requests require admin approval and will be reviewed within 24 hours.
                    </div>
                <?php endif; ?>
                
                <div class="quote-preview">
                    <div class="quote-preview-header">
                        <div>
                            <h3>Need Help Choosing?</h3>
                            <p>Fill out our detailed inquiry form below and we'll help you select the right package</p>
                        </div>
                        <a href="#contact-form" class="quick-quote-btn"><span>📋</span> Go to Inquiry Form</a>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
                <!-- SIMPLE CTA WHEN NO QUOTE CONFIGURED BUT QUOTES ARE ALLOWED -->
                <div class="simple-cta">
                    <h3>Interested in this service?</h3>
                    <p>Get a personalized quote tailored to your specific requirements.</p>
                    <a href="#contact-form" class="cta-button">Request Quote</a>
                </div>
            <?php endif; ?>
        
        <!-- ======================================== -->
        <!-- INQUIRY FORM                             -->
        <!-- ======================================== -->
        <?php if (isFieldVisible($visibility, 'inquiry_form')): ?>
            <div id="contact-form" class="service-contact">
                <h3>Inquire About <?php echo htmlspecialchars($service['title']); ?></h3>
                
                <form method="POST" action="service_detail.php?id=<?php echo $service_id; ?>" id="inquiryForm">
                    <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
                    <input type="hidden" name="service_name" value="<?php echo htmlspecialchars($service['title']); ?>">
                    
                    <?php if (isFieldVisible($visibility, 'inquiry_name')): ?>
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required 
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                               placeholder="Enter your full name">
                        <span class="field-error" id="full_name_error">This field is mandatory</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isFieldVisible($visibility, 'inquiry_email')): ?>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   placeholder="your@email.com">
                            <span class="field-error" id="email_error">This field is mandatory</span>
                        </div>
                    <?php endif; ?>
                        
                    <?php if (isFieldVisible($visibility, 'inquiry_phone')): ?>
                        <div class="form-group half">
                            <label for="phone">Phone *</label>
                            <input type="tel" id="phone" name="phone" required maxlength="13"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                   placeholder="e.g., 1234567890 or +11234567890"
                                   oninput="validatePhone(this)"
                                   pattern="[\d+]{10,13}"
                                   title="Phone number must be 10-13 digits (plus sign allowed)">
                            <span class="field-error" id="phone_error">This field is mandatory</span>
                            <small class="phone-hint">Enter digits only, no dashes or spaces (10-13 digits)</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isFieldVisible($visibility, 'inquiry_organization')): ?>
                    <div class="form-group">
                        <label for="organization">Organization *</label>
                        <input type="text" id="organization" name="organization" required
                               value="<?php echo isset($_POST['organization']) ? htmlspecialchars($_POST['organization']) : ''; ?>"
                               placeholder="Your company or organization name">
                        <span class="field-error" id="organization_error">This field is mandatory</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isFieldVisible($visibility, 'inquiry_country')): ?>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="country">Country *</label>
                            <select id="country" name="country" required>
                                <option value="">Select Country</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo htmlspecialchars($country['name']); ?>" 
                                            <?php echo (isset($_POST['country']) && $_POST['country'] == $country['name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($country['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="field-error" id="country_error">This field is mandatory</span>
                        </div>
                    <?php endif; ?>
                        
                    <?php if (isFieldVisible($visibility, 'inquiry_region')): ?>
                        <div class="form-group half">
                            <label for="region">Region *</label>
                            <select id="region" name="region" required>
                                <option value="">Select Region</option>
                                <?php if (!empty($regions)): ?>
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?php echo htmlspecialchars($region); ?>" 
                                                <?php echo (isset($_POST['region']) && $_POST['region'] == $region) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($region); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="North America">North America</option>
                                    <option value="South America">South America</option>
                                    <option value="Europe">Europe</option>
                                    <option value="Asia Pacific">Asia Pacific</option>
                                    <option value="Middle East">Middle East</option>
                                    <option value="Africa">Africa</option>
                                    <option value="Australia/New Zealand">Australia/New Zealand</option>
                                    <option value="Global">Global</option>
                                <?php endif; ?>
                            </select>
                            <span class="field-error" id="region_error">This field is mandatory</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isFieldVisible($visibility, 'inquiry_industry')): ?>
                    <div class="form-group">
                        <label for="industry">Industry *</label>
                        <select id="industry" name="industry" required>
                            <option value="">Select Industry</option>
                            <?php if (!empty($industries)): ?>
                                <?php foreach ($industries as $industry): ?>
                                    <option value="<?php echo htmlspecialchars($industry); ?>" 
                                            <?php echo (isset($_POST['industry']) && $_POST['industry'] == $industry) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($industry); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="Insurance">Insurance</option>
                                <option value="Healthcare">Healthcare</option>
                                <option value="Life Sciences">Life Sciences</option>
                                <option value="High Tech">High Tech</option>
                                <option value="Manufacturing">Manufacturing</option>
                                <option value="Public Services">Public Services</option>
                                <option value="Retail">Retail</option>
                                <option value="Travel and Logistics">Travel and Logistics</option>
                                <option value="Communications, Media, Information Services">Communications, Media, Information Services</option>
                                <option value="Energy, Resources, Utilities">Energy, Resources, Utilities</option>
                                <option value="Consumer Packaged Goods and Distribution">Consumer Packaged Goods and Distribution</option>
                                <option value="Education">Education</option>
                                <option value="Other">Other</option>
                            <?php endif; ?>
                        </select>
                        <span class="field-error" id="industry_error">This field is mandatory</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isFieldVisible($visibility, 'inquiry_message')): ?>
                    <div class="form-group">
                        <label for="help_text">How can we help you? *</label>
                        <textarea id="help_text" name="help_text" rows="4" required
                                  oninput="updateCharCount('help_text', 'help_text_count')"><?php echo isset($_POST['help_text']) ? htmlspecialchars($_POST['help_text']) : "I'm interested in " . htmlspecialchars($service['title']) . " service."; ?></textarea>
                        <div class="char-counter"><span id="help_text_count">0</span>/1500</div>
                        <span class="field-error" id="help_text_error">This field is mandatory</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isFieldVisible($visibility, 'inquiry_consent')): ?>
                    <div class="form-checkbox">
                        <label>
                            <input type="checkbox" id="consent" name="consent" value="1" <?php echo isset($_POST['consent']) ? 'checked' : ''; ?> required>
                            <span>I consent to processing of my personal data entered above for P.S Pakpi Enterprises to contact me. *</span>
                        </label>
                        <span class="field-error" id="consent_error">You must consent to data processing</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isFieldVisible($visibility, 'inquiry_receive_info')): ?>
                    <div class="form-checkbox">
                        <label>
                            <input type="checkbox" id="receive_info" name="receive_info" value="1" <?php echo isset($_POST['receive_info']) ? 'checked' : ''; ?>>
                            <span>I would like to receive details about products, services and events from P.S Pakpi Enterprises</span>
                        </label>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="submit-btn">Submit Inquiry</button>
                </form>
            </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- ======================================== -->
        <!-- RELATED SERVICES SECTION                 -->
        <!-- ======================================== -->
        <?php if (!empty($related_services)): ?>
            <div class="related-services-section">
                <div class="related-services-header">
                    <div>
                        <span class="related-eyebrow">Enterprise Solutions</span>
                        <h3 class="related-title">Explore Related Business Divisions</h3>
                    </div>
                    <a href="index.php#services" class="related-view-all">View All Services →</a>
                </div>
                <div class="related-services-grid">
                    <?php foreach ($related_services as $relSvc): 
                        $relId = (int)$relSvc['id'];
                        $relTitle = $relSvc['title'];
                        $relImg = 'assets/images/default-service.jpg';
                        if (!empty($relSvc['image_url'])) {
                            $rRaw = trim((string)$relSvc['image_url']);
                            if (strpos($rRaw, 'data:image') === 0 || strpos($rRaw, 'http') === 0 || strpos($rRaw, '/') === 0) {
                                $relImg = $rRaw;
                            } else {
                                $cName = basename($rRaw);
                                if (file_exists("assets/images/services/" . $cName)) {
                                    $relImg = "assets/images/services/" . $cName;
                                } elseif (file_exists("uploads/" . $cName)) {
                                    $relImg = "uploads/" . $cName;
                                }
                            }
                        }
                    ?>
                        <div class="related-service-card">
                            <div class="related-card-media">
                                <img src="<?php echo htmlspecialchars($relImg); ?>" 
                                     alt="<?php echo htmlspecialchars($relTitle); ?>"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='assets/images/default-service.jpg';">
                            </div>
                            <div class="related-card-body">
                                <h4><?php echo htmlspecialchars($relTitle); ?></h4>
                                <p><?php echo htmlspecialchars(mb_strimwidth($relSvc['description'] ?: 'Discover specifications and options for this division.', 0, 80, '...')); ?></p>
                                <div class="related-card-footer">
                                    <a href="service_detail.php?id=<?php echo $relId; ?>" class="related-link">
                                        <span>View Details</span>
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- SERVICE DETAIL BOTTOM CTA -->
        <div class="service-detail-cta">
            <div class="service-detail-cta-copy">
                <h3>Have a Multi-Sector Assignment?</h3>
                <p>We coordinate civil infrastructure, logistics, supply schedules, and commercial projects under one desk.</p>
            </div>
            <div class="service-detail-cta-actions">
                <a href="contact.php" class="btn-primary"><span>Request an Assessment</span> <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>

    </div>
</div>

<!-- QUOTE REQUEST MODAL (unchanged) -->
<div id="quoteRequestModal" class="quote-form-container" style="display: none;">
    <div class="quote-form-modal">
        <div class="quote-form-header">
            <h2 id="quoteModalServiceTitle">Request Quote</h2>
            <p>Tell us about your project and we'll respond within 24 hours</p>
            <button class="quote-form-close" onclick="closeQuoteModal()">&times;</button>
        </div>
        <div class="quote-form-body">
            <form method="POST" action="service_detail.php?id=<?php echo $service_id; ?>" id="quoteRequestForm">
                <input type="hidden" name="service_id" value="<?php echo $service_id; ?>" id="quote_service_id">
                <input type="hidden" name="service_name" value="<?php echo htmlspecialchars($service['title']); ?>" id="quote_service_name">
                <input type="hidden" name="is_quote_request" value="1">
                <input type="hidden" name="quote_type" id="quote_type" value="standard">
                <input type="hidden" name="quote_id" id="quote_id" value="0">
                
                <!-- Selected Price Summary -->
                <?php if ($has_quotes): ?>
                <div class="selected-price-summary" id="selectedPriceSummary" style="display: none;">
                    <span>Selected Package:</span>
                    <span class="price-tag" id="selectedPriceTag"></span>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="quote_full_name">Full Name </label>
                    <input type="text" id="quote_full_name" name="full_name" required 
                           placeholder="Enter your full name">
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="quote_email">Email </label>
                        <input type="email" id="quote_email" name="email" required
                               placeholder="your@email.com">
                    </div>
                    <div class="form-group half">
                        <label for="quote_phone">Phone </label>
                        <input type="tel" id="quote_phone" name="phone" required maxlength="13"
                               placeholder="Your contact number">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="quote_organization">Organization / Company</label>
                    <input type="text" id="quote_organization" name="organization"
                           placeholder="Your company name">
                </div>
                
                <!-- Country, Region, Industry Fields -->
                <div class="form-row">
                    <div class="form-group half">
                        <label for="quote_country">Country </label>
                        <select id="quote_country" name="country" required class="form-control">
                            <option value="">Select Country</option>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?php echo htmlspecialchars($country['name']); ?>">
                                    <?php echo htmlspecialchars($country['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group half">
                        <label for="quote_region">Region </label>
                        <select id="quote_region" name="region" required class="form-control">
                            <option value="">Select Region</option>
                            <?php if (!empty($regions)): ?>
                                <?php foreach ($regions as $region): ?>
                                    <option value="<?php echo htmlspecialchars($region); ?>">
                                        <?php echo htmlspecialchars($region); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="North America">North America</option>
                                <option value="South America">South America</option>
                                <option value="Europe">Europe</option>
                                <option value="Asia Pacific">Asia Pacific</option>
                                <option value="Middle East">Middle East</option>
                                <option value="Africa">Africa</option>
                                <option value="Australia/New Zealand">Australia/New Zealand</option>
                                <option value="Global">Global</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="quote_industry">Industry </label>
                    <select id="quote_industry" name="industry" required class="form-control">
                        <option value="">Select Industry</option>
                        <?php if (!empty($industries)): ?>
                            <?php foreach ($industries as $industry): ?>
                                <option value="<?php echo htmlspecialchars($industry); ?>">
                                    <?php echo htmlspecialchars($industry); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="Insurance">Insurance</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Life Sciences">Life Sciences</option>
                            <option value="High Tech">High Tech</option>
                            <option value="Manufacturing">Manufacturing</option>
                            <option value="Public Services">Public Services</option>
                            <option value="Retail">Retail</option>
                            <option value="Travel and Logistics">Travel and Logistics</option>
                            <option value="Communications, Media, Information Services">Communications, Media, Information Services</option>
                            <option value="Energy, Resources, Utilities">Energy, Resources, Utilities</option>
                            <option value="Consumer Packaged Goods and Distribution">Consumer Packaged Goods and Distribution</option>
                            <option value="Education">Education</option>
                            <option value="Other">Other</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- Project Scope -->
                <div class="scope-slider">
                    <label>Project Size / Scope</label>
                    <div class="scope-options">
                        <div class="scope-option" onclick="selectScope(this, 'small')">
                            <span class="title">Small</span>
                            <span class="price">₹1K - ₹5K</span>
                        </div>
                        <div class="scope-option" onclick="selectScope(this, 'medium')">
                            <span class="title">Medium</span>
                            <span class="price">₹5K - ₹20K</span>
                        </div>
                        <div class="scope-option" onclick="selectScope(this, 'large')">
                            <span class="title">Large</span>
                            <span class="price">₹20K - ₹50K</span>
                        </div>
                        <div class="scope-option" onclick="selectScope(this, 'enterprise')">
                            <span class="title">Enterprise</span>
                            <span class="price">₹50K+</span>
                        </div>
                    </div>
                    <input type="hidden" name="project_scope" id="project_scope" value="">
                </div>
                
                <div class="form-group">
                    <label for="quote_budget">Estimated Budget (₹)</label>
                    <input type="number" id="quote_budget" name="requested_budget" 
                           placeholder="Your estimated budget" step="1000" min="0">
                </div>
                
                <div class="form-group">
                    <label for="quote_timeline">Timeline Requirement</label>
                    <select id="quote_timeline" name="timeline_requirement">
                        <option value="">Select timeline</option>
                        <option value="asap">As soon as possible</option>
                        <option value="1_month">Within 1 month</option>
                        <option value="3_months">Within 3 months</option>
                        <option value="6_months">Within 6 months</option>
                        <option value="flexible">Flexible</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="quote_requirements">Specific Requirements </label>
                    <textarea id="quote_requirements" name="specific_requirements" rows="4" required
                              placeholder="Please describe your project requirements, specifications, and any specific needs..."></textarea>
                </div>
                
                <div class="form-checkbox">
                    <label>
                        <input type="checkbox" name="consent" value="1" required>
                        <span>I consent to processing my data for quote processing *</span>
                    </label>
                </div>
                
                <button type="submit" class="submit-btn" style="background: var(--gold);">
                    <span>💰</span> Request Quote
                </button>
            </form>
        </div>
    </div>
</div>

<!-- JAVASCRIPT (unchanged) -->
<script>
// ========== PHONE VALIDATION ==========
function validatePhone(input) {
    const value = input.value;
    const maxLength = 13;
    const currentLength = value.length;
    const digitCount = value.replace(/\D/g, '').length;
    
    let counter = document.getElementById('phone_char_counter');
    if (!counter) {
        counter = document.createElement('span');
        counter.id = 'phone_char_counter';
        counter.className = 'char-counter';
        input.parentNode.appendChild(counter);
    }
    
    counter.textContent = `${currentLength}/${maxLength} (${digitCount} digits)`;
    
    input.classList.remove('valid', 'invalid', 'warning');
    counter.classList.remove('counter-valid', 'counter-warning', 'counter-invalid');
    
    const phoneError = document.getElementById('phone_error');
    
    if (currentLength === 0) {
        counter.classList.add('counter-invalid');
        input.classList.add('invalid');
        if (phoneError) {
            phoneError.textContent = 'Phone number is required';
            phoneError.style.display = 'block';
        }
    } else if (currentLength > maxLength) {
        counter.classList.add('counter-invalid');
        input.classList.add('invalid');
        if (phoneError) {
            phoneError.textContent = 'Phone number exceeds maximum length (13 characters)';
            phoneError.style.display = 'block';
        }
    } else if (digitCount < 10) {
        counter.classList.add('counter-warning');
        input.classList.add('warning');
        if (phoneError) {
            phoneError.textContent = `Need at least 10 digits (${digitCount}/10)`;
            phoneError.style.display = 'block';
        }
    } else {
        counter.classList.add('counter-valid');
        input.classList.add('valid');
        if (phoneError) {
            phoneError.style.display = 'none';
        }
    }
    
    // Format - remove non-digits except plus
    let formatted = value.replace(/[^\d+]/g, '');
    if (formatted.length > maxLength) formatted = formatted.substr(0, maxLength);
    if (input.value !== formatted) {
        input.value = formatted;
    }
}

// ========== CHARACTER COUNTER ==========
function updateCharCount(textareaId, counterId) {
    const textarea = document.getElementById(textareaId);
    const counter = document.getElementById(counterId);
    if (textarea && counter) {
        const length = textarea.value.length;
        counter.textContent = length;
        counter.style.color = length > 1400 ? '#ff0000' : (length > 1200 ? 'var(--gold)' : '#666');
    }
}

// ========== QUOTE MODAL FUNCTIONS ==========
function openQuoteRequestForm(serviceId, serviceName, isCustom = false, quoteId = 0) {
    const quoteForm = document.getElementById('quoteRequestForm');
    const modal = document.getElementById('quoteRequestModal');
    if (!quoteForm || !modal) {
        return;
    }

    quoteForm.reset();
    document.getElementById('quote_service_id').value = serviceId;
    document.getElementById('quote_service_name').value = serviceName;
    document.getElementById('quoteModalServiceTitle').textContent = isCustom ? 'Request Custom Quote: ' + serviceName : 'Request Quote: ' + serviceName;
    document.getElementById('quote_type').value = isCustom ? 'custom' : 'standard';
    document.getElementById('quote_id').value = quoteId || 0;
    
    <?php if ($has_quotes): ?>
    const priceSummary = document.getElementById('selectedPriceSummary');
    if (!isCustom && quoteId > 0) {
        priceSummary.style.display = 'flex';
        document.getElementById('selectedPriceTag').innerHTML = 'Selected Package';
    } else {
        priceSummary.style.display = 'none';
    }
    <?php endif; ?>
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeQuoteModal() {
    const modal = document.getElementById('quoteRequestModal');
    if (modal) {
        modal.style.display = 'none';
    }
    document.body.style.overflow = 'auto';
}

function selectScope(element, value) {
    document.querySelectorAll('.scope-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    const scopeField = document.getElementById('project_scope');
    if (scopeField) {
        scopeField.value = value;
    }
}

// ========== EVENT LISTENERS ==========
document.addEventListener('DOMContentLoaded', function() {
    // Initialize phone validation
    const phoneField = document.getElementById('phone');
    if (phoneField) {
        if (!document.getElementById('phone_char_counter')) {
            const counter = document.createElement('span');
            counter.id = 'phone_char_counter';
            counter.className = 'char-counter';
            phoneField.parentNode.appendChild(counter);
        }
        phoneField.addEventListener('input', function() { validatePhone(this); });
        if (phoneField.value) validatePhone(phoneField);
    }
    
    // Initialize help text counter
    updateCharCount('help_text', 'help_text_count');
    
    // Quote form validation
    const quoteForm = document.getElementById('quoteRequestForm');
    if (quoteForm) {
        quoteForm.addEventListener('submit', function(e) {
            const phone = document.getElementById('quote_phone');
            const phoneDigits = phone ? phone.value.replace(/\D/g, '') : '';
            if (phone && phoneDigits.length < 10) {
                alert('Please enter a valid phone number (at least 10 digits)');
                e.preventDefault();
                return;
            }

            const submitButton = quoteForm.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('is-loading');
                submitButton.textContent = 'Submitting...';
            }
        });
    }
    
    // Main form validation
    const form = document.getElementById('inquiryForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            document.querySelectorAll('.field-error').forEach(el => el.style.display = 'none');
            
            const required = ['full_name', 'email', 'phone', 'organization', 'country', 'region', 'industry', 'help_text'];
            required.forEach(id => {
                const field = document.getElementById(id);
                if (!field || !field.value.trim()) {
                    const error = document.getElementById(id + '_error');
                    if (error) {
                        error.textContent = 'This field is mandatory';
                        error.style.display = 'block';
                    }
                    isValid = false;
                }
            });
            
            // Phone validation
            const phone = document.getElementById('phone');
            if (phone && phone.value.trim()) {
                const digits = phone.value.replace(/\D/g, '');
                const phoneError = document.getElementById('phone_error');
                if (digits.length < 10) {
                    if (phoneError) {
                        phoneError.textContent = 'Phone number must include at least 10 digits';
                        phoneError.style.display = 'block';
                    }
                    isValid = false;
                } else if (phone.value.length > 13) {
                    if (phoneError) {
                        phoneError.textContent = 'Phone number must be 13 characters or less';
                        phoneError.style.display = 'block';
                    }
                    isValid = false;
                }
            }
            
            // Email validation
            const email = document.getElementById('email');
            if (email && email.value.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email.value)) {
                    const emailError = document.getElementById('email_error');
                    if (emailError) {
                        emailError.textContent = 'Please enter a valid email address';
                        emailError.style.display = 'block';
                    }
                    isValid = false;
                }
            }
            
            // Consent
            if (!document.getElementById('consent')?.checked) {
                const consentError = document.getElementById('consent_error');
                if (consentError) {
                    consentError.style.display = 'block';
                }
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                const firstError = document.querySelector('.field-error[style*="display: block"]');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('is-loading');
                submitButton.textContent = 'Submitting...';
            }
        });
    }
});

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('quoteRequestModal');
    if (event.target === modal) closeQuoteModal();
};
</script>

<!-- AMBIENT THREE.JS SCRIPT -->
<script type="module" src="assets/js/three-scene.js"></script>
<?php include "footer.php"; ?>
