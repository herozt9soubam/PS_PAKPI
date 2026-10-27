<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "db.php";

$inquiry_id = 0;
$service_id = 0;
$full_name = '';
$email = '';
$phone = '';
$organization = '';
$country = '';
$region = '';
$industry = '';
$help_text = '';
$service_name = '';
$timestamp = '';

function safe_html($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

if (isset($_SESSION['inquiry_success']) && is_array($_SESSION['inquiry_success'])) {
    $success_data = $_SESSION['inquiry_success'];
    $inquiry_id = $success_data['id'] ?? 0;
    $service_id = $success_data['service_id'] ?? 0;
    $full_name = $success_data['full_name'] ?? '';
    $email = $success_data['email'] ?? '';
    $phone = $success_data['phone'] ?? '';
    $organization = $success_data['organization'] ?? '';
    $country = $success_data['country'] ?? '';
    $region = $success_data['region'] ?? '';
    $industry = $success_data['industry'] ?? '';
    $help_text = $success_data['help_text'] ?? '';
    $service_name = $success_data['service_name'] ?? '';
    $timestamp = $success_data['timestamp'] ?? date('Y-m-d H:i:s');
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $inquiry_id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM service_inquiries WHERE id = ?");
        $stmt->execute([$inquiry_id]);
        $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($inquiry) {
            $service_id = $inquiry['service_id'] ?? 0;
            $full_name = $inquiry['name'] ?? '';
            $email = $inquiry['email'] ?? '';
            $phone = $inquiry['phone'] ?? '';
            $organization = $inquiry['organization'] ?? '';
            $country = $inquiry['country'] ?? '';
            $region = $inquiry['region'] ?? '';
            $industry = $inquiry['industry'] ?? '';
            $help_text = $inquiry['message'] ?? '';
            $service_name = $inquiry['service_name'] ?? '';
            $timestamp = $inquiry['created_at'] ?? date('Y-m-d H:i:s');
        }
    } catch (Exception $e) {
        error_log("Error fetching inquiry: " . $e->getMessage());
    }
}

if ($inquiry_id <= 0) {
    header("Location: index.php");
    exit;
}

$reference_id = "PSP-" . date('Y', strtotime($timestamp ?: 'now')) . "-" . str_pad($inquiry_id, 4, '0', STR_PAD_LEFT);
$pageTitle = "Inquiry Submitted";
include "header.php";
?>

<div class="inquiry-success-page">
    <div class="section-container max-w-900">
        <!-- SUCCESS CARD -->
        <div class="success-main-card">
            <div class="success-badge-icon">
                <i class="fa-solid fa-check" aria-hidden="true"></i>
            </div>
            
            <span class="success-eyebrow">Submission Confirmed</span>
            <h1 class="success-heading">Thank You for Your Inquiry</h1>
            <p class="success-lead">
                Your quote request for <strong><?php echo safe_html($service_name); ?></strong> has been formally registered with our administration desk.
            </p>
            
            <div class="reference-chip">
                <span class="chip-label">Official Reference:</span>
                <span class="chip-code"><?php echo safe_html($reference_id); ?></span>
            </div>

            <hr class="card-divider">

            <!-- PARAMETERS SUMMARY GRID -->
            <h2 class="summary-section-title">Submitted Parameters Summary</h2>
            <div class="parameters-summary-grid">
                <div class="summary-field">
                    <span class="field-name"><i class="fa-solid fa-user"></i> Full Name</span>
                    <strong class="field-value"><?php echo safe_html($full_name); ?></strong>
                </div>
                <div class="summary-field">
                    <span class="field-name"><i class="fa-solid fa-envelope"></i> Email Address</span>
                    <strong class="field-value"><?php echo safe_html($email); ?></strong>
                </div>
                <div class="summary-field">
                    <span class="field-name"><i class="fa-solid fa-phone"></i> Phone Number</span>
                    <strong class="field-value"><?php echo safe_html($phone); ?></strong>
                </div>
                <div class="summary-field">
                    <span class="field-name"><i class="fa-solid fa-building"></i> Organization</span>
                    <strong class="field-value"><?php echo safe_html($organization); ?></strong>
                </div>
                <div class="summary-field">
                    <span class="field-name"><i class="fa-solid fa-globe"></i> Location / Region</span>
                    <strong class="field-value">
                        <?php 
                        $locParts = array_filter([$region, $country]);
                        echo !empty($locParts) ? safe_html(implode(', ', $locParts)) : 'Not specified';
                        ?>
                    </strong>
                </div>
                <div class="summary-field">
                    <span class="field-name"><i class="fa-solid fa-industry"></i> Industry Sector</span>
                    <strong class="field-value"><?php echo safe_html($industry ?: 'General'); ?></strong>
                </div>
            </div>

            <?php if (!empty($help_text)): ?>
                <div class="summary-message-box">
                    <span class="field-name"><i class="fa-solid fa-message"></i> Project Requirements / Help Notes:</span>
                    <p class="message-text"><?php echo nl2br(safe_html($help_text)); ?></p>
                </div>
            <?php endif; ?>

            <!-- NEXT STEPS ACCORDION / BOX -->
            <div class="next-steps-card">
                <h3><i class="fa-solid fa-list-check"></i> What Happens Next?</h3>
                <ol class="next-steps-list">
                    <li>
                        <strong>Administrative Review:</strong> Our desk verifies technical prerequisites, location factors, and scope parameters.
                    </li>
                    <li>
                        <strong>Direct Communication:</strong> A representative will reach out via phone (<strong><?php echo safe_html($phone); ?></strong>) or email with quotation specifics.
                    </li>
                    <li>
                        <strong>Official Handover:</strong> Formal documentation or on-site inspections will be scheduled as agreed.
                    </li>
                </ol>
            </div>

            <!-- ACTIONS ROW -->
            <div class="success-actions-row">
                <a href="index.php" class="btn-primary">
                    <i class="fa-solid fa-house"></i>
                    <span>Return to Home</span>
                </a>
                <?php if ($service_id > 0): ?>
                    <a href="service_detail.php?id=<?php echo (int)$service_id; ?>" class="btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Back to <?php echo safe_html($service_name); ?></span>
                    </a>
                <?php endif; ?>
                <button type="button" class="btn-outline-dark" onclick="window.print();">
                    <i class="fa-solid fa-print"></i>
                    <span>Print Summary</span>
                </button>
            </div>

            <!-- REGISTRATION FOOTNOTE -->
            <p class="success-office-note">
                <i class="fa-solid fa-location-dot"></i> P.S. Pakpi Enterprises • Kwakeithel, Thokchom Leikai, Imphal West, Manipur, India
            </p>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>