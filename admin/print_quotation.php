<?php
// print_quotation.php
session_start();

require_once('../config.php');
require_once('includes/functions.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$quote_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($quote_id <= 0) {
    die('Invalid quote ID');
}

$conn = getConnection();

// Fetch quote data
try {
    $stmt = $conn->prepare("
        SELECT sq.*, s.title as service_name 
        FROM service_quotes sq
        LEFT JOIN services s ON sq.service_id = s.id
        WHERE sq.id = ?
    ");
    $stmt->execute([$quote_id]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        die('Quote not found');
    }

    // Process arrays using functions from functions.php
    $inclusions = !empty($quote['inclusions']) && $quote['inclusions'] != '{}' 
        ? pgArrayToArray($quote['inclusions']) : [];
    $exclusions = !empty($quote['exclusions']) && $quote['exclusions'] != '{}' 
        ? pgArrayToArray($quote['exclusions']) : [];
    $prerequisites = !empty($quote['prerequisites']) && $quote['prerequisites'] != '{}' 
        ? pgArrayToArray($quote['prerequisites']) : [];
    $documents_required = !empty($quote['documents_required']) && $quote['documents_required'] != '{}' 
        ? pgArrayToArray($quote['documents_required']) : [];

    // Calculate totals
    $sub_total = $quote['fixed_price'] ?: $quote['min_price'] ?: 0;
    $discount_percentage = $quote['discount_percentage'] ?? 0;
    $discount_amount = ($sub_total * $discount_percentage / 100);
    $tax_rate = 18; // GST rate
    $tax_amount = (($sub_total - $discount_amount) * $tax_rate / 100);
    $total = $sub_total - $discount_amount + $tax_amount;

    // Generate quote number
    $quote_no = 'Q-' . date('Y') . '-' . str_pad($quote_id, 4, '0', STR_PAD_LEFT);

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die('Error loading quote: ' . $e->getMessage());
}

// Client info (can be passed via URL)
$client_name = isset($_GET['client']) ? $_GET['client'] : 'Client';
$client_address = isset($_GET['address']) ? $_GET['address'] : '';

/**
 * Convert number to words (Indian numbering system: lakhs, crores)
 * This function is defined ONCE here.
 */
function numberToWords($num) {
    $num = (float)$num;
    if ($num == 0) return 'Zero';

    $words = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen', 'Twenty', 'Thirty', 'Forty', 'Fifty',
        'Sixty', 'Seventy', 'Eighty', 'Ninety'
    ];

    $num = number_format($num, 2, '.', '');
    $parts = explode('.', $num);
    $whole = (int)$parts[0];
    $decimal = (int)$parts[1];

    $output = '';

    // Convert whole part (Indian numbering: lakhs, crores)
    if ($whole >= 10000000) {
        $crores = floor($whole / 10000000);
        $output .= numberToWords($crores) . ' Crore ';
        $whole %= 10000000;
    }
    if ($whole >= 100000) {
        $lakhs = floor($whole / 100000);
        $output .= numberToWords($lakhs) . ' Lakh ';
        $whole %= 100000;
    }
    if ($whole >= 1000) {
        $thousands = floor($whole / 1000);
        $output .= numberToWords($thousands) . ' Thousand ';
        $whole %= 1000;
    }
    if ($whole >= 100) {
        $hundreds = floor($whole / 100);
        $output .= $words[$hundreds] . ' Hundred ';
        $whole %= 100;
    }
    if ($whole > 0) {
        if ($whole < 20) {
            $output .= $words[$whole] . ' ';
        } else {
            $tens = floor($whole / 10);
            $units = $whole % 10;
            $output .= $words[18 + $tens] . ' ';
            if ($units > 0) {
                $output .= $words[$units] . ' ';
            }
        }
    }

    $output = trim($output) . ' Rupees';

    if ($decimal > 0) {
        $output .= ' and ' . numberToWords($decimal) . ' Paise';
    }

    return $output . ' Only';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - <?php echo htmlspecialchars($quote['quote_title']); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* ===== FORCE PRINT STYLES – OVERRIDE ANY CONFLICTS ===== */
        @media print {
            /* Hide buttons and other non-print elements */
            .no-print, .print-buttons, .modal, .sidebar, .admin-header, footer {
                display: none !important;
            }

            /* Ensure body and container have white background and proper margins */
            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                font-family: 'Segoe UI', Arial, sans-serif !important;
                color: black !important;
            }

            .quotation-container {
                max-width: 100% !important;
                padding: 15mm 10mm !important;
                margin: 0 auto !important;
                background: white !important;
                box-shadow: none !important;
            }

            /* Header */
            .header h1 {
                font-size: 28pt !important;
                margin: 0 0 5px 0 !important;
                color: #1E3A8A !important;
            }
            .header h2 {
                font-size: 22pt !important;
                margin: 10px 0 20px 0 !important;
                text-align: right !important;
                color: #1E3A8A !important;
                border-bottom: 2px solid #1E3A8A !important;
                padding-bottom: 5px !important;
            }
            .company-details {
                font-size: 11pt !important;
                line-height: 1.4 !important;
                color: #333 !important;
            }

            /* Two‑column sections */
            .estimate-info, .amounts-section, .terms-signature {
                display: flex !important;
                justify-content: space-between !important;
                margin: 20px 0 !important;
            }
            .estimate-for, .estimate-details, .amounts-left, .amounts-right, .terms, .signature {
                width: 48% !important;
            }

            /* Table – FORCE VISIBILITY AND BORDERS */
            table.items-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin: 20px 0 !important;
                display: table !important;  /* Override any accidental display:block */
            }
            table.items-table th,
            table.items-table td {
                border: 1px solid #333 !important;  /* Darker border for print */
                padding: 8px !important;
                text-align: left !important;
                font-size: 11pt !important;
                background: white !important;
                color: black !important;
            }
            table.items-table th {
                background: #f0f0f0 !important;
                font-weight: bold !important;
            }
            table.items-table td {
                background: white !important;
            }
            /* Right‑align numeric columns */
            table.items-table td:nth-child(4),
            table.items-table td:nth-child(6),
            table.items-table td:nth-child(7),
            table.items-table th:nth-child(4),
            table.items-table th:nth-child(6),
            table.items-table th:nth-child(7) {
                text-align: right !important;
            }

            /* Totals row */
            table.items-table tfoot td {
                font-weight: bold !important;
                background: #f9f9f9 !important;
            }

            /* Amounts section */
            .amounts-left, .amounts-right {
                font-size: 11pt !important;
            }
            .amounts-left strong, .amounts-right strong {
                color: #1E3A8A !important;
            }

            /* Terms and signature */
            .terms ul {
                margin: 5px 0 0 20px !important;
                padding: 0 !important;
            }
            .terms li {
                margin-bottom: 3px !important;
                font-size: 10pt !important;
            }
            .signature {
                text-align: right !important;
                margin-top: 40px !important;
            }
            .signature strong {
                display: block !important;
                margin-top: 10px !important;
            }

            /* Hide thermal-only section in normal A4 print */
            .thermal-only {
                display: none !important;
            }
        }

        /* Thermal print (80mm) – keep as is from style.css, or you can customize further */
        @media print and (max-width: 80mm) {
            .thermal-only {
                display: block !important;
                text-align: center;
                font-size: 8pt;
            }
            /* Simplify table for thermal if needed – already in style.css */
        }
    </style>
</head>
<body class="print-quotation-page">
    <div class="print-buttons no-print">
        <button class="btn btn-primary" onclick="window.print()">🖨️ Print (A4)</button>
        <button class="btn btn-info thermal" onclick="window.print()">🧾 Print Thermal (80mm)</button>
        <button class="btn btn-secondary" onclick="window.close()">✖️ Close</button>
    </div>

    <div class="quotation-container">
        <!-- Header with Logo Placeholder -->
        <div class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="logo-placeholder" style="width: 80px; height: 80px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px dashed #999;">
                    <span style="font-size: 12px; color: #666;">Logo</span>
                </div>
                <div>
                    <h1>P.S PAKPI ENTERPRISES</h1>
                    <div class="company-details">
                        KWAKEITHEL, THOKCHOM LEIKAI, Imphal West, Manipur<br>
                        Phone: 8794222222 | Email: pakpi@gmail.com<br>
                        GSTIN: 10HGFGH6548D3OJ, State: 14-Manipur
                    </div>
                </div>
            </div>
            <h2>QUOTATION</h2>
        </div>

        <!-- Estimate Info (two columns) -->
        <div class="estimate-info">
            <div class="estimate-for">
                <strong>Estimate For</strong><br>
                <?php echo htmlspecialchars($client_name); ?><br>
                <?php echo nl2br(htmlspecialchars($client_address)); ?>
            </div>
            <div class="estimate-details">
                <strong>Estimate Details</strong><br>
                Estimate No.: <?php echo htmlspecialchars($quote_no); ?><br>
                Date: <?php echo date('d/m/Y'); ?>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Sl. No</th>
                    <th>Item name</th>
                    <th>HSN/SAC</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Price/Unit</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td><?php echo htmlspecialchars($quote['quote_title']); ?></td>
                    <td><?php echo htmlspecialchars($quote['quote_code'] ?? 'N/A'); ?></td>
                    <td>1</td>
                    <td>Lump Sum</td>
                    <td><?php echo formatPrice($sub_total); ?></td>
                    <td><?php echo formatPrice($sub_total); ?></td>
                </tr>
                <!-- Additional rows can be added here if needed -->
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" style="text-align: right;"><strong>Total</strong></td>
                    <td style="text-align: right;"><strong><?php echo formatPrice($sub_total); ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <!-- Amounts Section -->
        <div class="amounts-section">
            <div class="amounts-left">
                <strong>Amounts:</strong><br>
                Sub Total: <?php echo formatPrice($sub_total); ?><br>
                <?php if ($discount_percentage > 0): ?>
                Discount (<?php echo $discount_percentage; ?>%): -<?php echo formatPrice($discount_amount); ?><br>
                <?php endif; ?>
                GST (<?php echo $tax_rate; ?>%): <?php echo formatPrice($tax_amount); ?><br>
                <strong>Total: <?php echo formatPrice($total); ?></strong>
            </div>
            <div class="amounts-right">
                <strong>Estimate Amount in Words</strong><br>
                <?php echo numberToWords($total); ?>
            </div>
        </div>

        <!-- Terms and Signature -->
        <div class="terms-signature">
            <div class="terms">
                <strong>Terms and Conditions:</strong>
                <ul>
                    <li>All sales are final. No returns or exchanges will be accepted.</li>
                    <li>A late payment fee of 20% per annum will be applied if payment is not received within 15 days of invoice date.</li>
                    <li>Our liability ends once the goods are delivered to the customer.</li>
                    <li>Full payment (100%) is required in advance.</li>
                    <li>Prices include GST.</li>
                </ul>
            </div>
            <div class="signature">
                For: P.S PAKPI ENTERPRISES<br>
                <br><br>
                <strong>Authorized Signatory</strong>
            </div>
        </div>

        <!-- Thermal-only footer -->
        <div class="thermal-only">
            <hr>
            <p>Thank you for your business!</p>
            <p><?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>

    <script>
        document.querySelectorAll('.print-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                window.print();
            });
        });
    </script>
</body>
</html>