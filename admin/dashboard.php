<?php
// dashboard.php - Located in ps-pakpi/admin/
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
session_start();
require_once('../config.php');
require_once('includes/functions.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getConnection();
$message = '';
$action = $_GET['action'] ?? 'dashboard';

// Initialize variables
$edit_client = null;
$view_client = null;
$edit_service = null;
$view_service = null;
$view_inquiry = null;
$clients = [];
$services = [];
$inquiries = [];
$recent_clients = [];
$recent_services = [];
$recent_inquiries = [];
$upcoming_followups = [];
$all_quotes = [];
$custom_fields = [];
$field_visibility = [];
$custom_fields_total_pages = 1;

// Get all services for dropdowns
$all_services = getServices($conn);

// Get field visibility settings
$field_visibility = getFieldVisibility($conn);

// Check for success parameter
if (isset($_GET['success'])) {
    $message = '<div class="success-message">✓ Operation completed successfully!</div>';
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function handleServiceImageUpload() {
    $image_url = null;
    
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['service_image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 10 * 1024 * 1024;
        
        if ($file['size'] > $max_size) {
            throw new Exception('File size too large (max 10MB).');
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!$mime_type && !empty($file['type'])) {
            $mime_type = strtolower($file['type']);
        }
        
        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Invalid file type. Allowed: JPG, PNG, GIF, WebP');
        }
        
        $image_data = file_get_contents($file['tmp_name']);
        $base64 = base64_encode($image_data);
        $image_url = 'data:' . $mime_type . ';base64,' . $base64;
        
    } elseif (!empty($_POST['image_url'])) {
        $image_url = $_POST['image_url'];
    } elseif (isset($_FILES['service_image']) && $_FILES['service_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        throw new Exception('Image upload failed. Please retry with a smaller JPG/PNG/WebP image.');
    }
    
    return $image_url;
}

function escapePgArray($value) {
    return addslashes($value);
}

function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@pspakpi.com\r\n";
    return mail($to, $subject, $message, $headers);
}

// ============================================
// FORM HANDLING
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ===== CLIENT MANAGEMENT =====
    if (isset($_POST['add_client'])) {
        try {
            $client_id = 'CL' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 6));
            
            $stmt = $conn->prepare("
                INSERT INTO clients (
                    client_id, username, password, full_name, email, 
                    phone, address, company_name, registration_date, 
                    status, created_by
                ) VALUES (
                    :client_id, :username, :password, 
                    :full_name, :email, :phone, :address, :company_name, 
                    :registration_date, :status, :created_by
                )
            ");
            
            $stmt->execute([
                ':client_id' => $client_id,
                ':username' => $_POST['username'],
                ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                ':full_name' => $_POST['full_name'],
                ':email' => $_POST['email'],
                ':phone' => $_POST['phone'] ?? null,
                ':address' => $_POST['address'] ?? null,
                ':company_name' => $_POST['company_name'] ?? null,
                ':registration_date' => $_POST['registration_date'],
                ':status' => $_POST['status'],
                ':created_by' => $_SESSION['admin_id']
            ]);
            
            $message = '<div class="success-message">✓ Client added successfully! Client ID: ' . $client_id . '</div>';
            
        } catch (PDOException $e) {
            $message = $e->getCode() == '23505' 
                ? '<div class="error-message">Error: Username or email already exists</div>'
                : '<div class="error-message">Error: ' . $e->getMessage() . '</div>';
        }
    }
    
    elseif (isset($_POST['update_client'])) {
        try {
            $stmt = $conn->prepare("
                UPDATE clients 
                SET full_name = :full_name, 
                    email = :email, 
                    phone = :phone, 
                    address = :address, 
                    company_name = :company_name, 
                    registration_date = :registration_date, 
                    status = :status 
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':full_name' => $_POST['full_name'],
                ':email' => $_POST['email'],
                ':phone' => $_POST['phone'] ?? null,
                ':address' => $_POST['address'] ?? null,
                ':company_name' => $_POST['company_name'] ?? null,
                ':registration_date' => $_POST['registration_date'],
                ':status' => $_POST['status'],
                ':id' => $_POST['client_id']
            ]);
            
            $message = '<div class="success-message">✓ Client updated successfully!</div>';
            
        } catch (PDOException $e) {
            $message = '<div class="error-message">Error updating client: ' . $e->getMessage() . '</div>';
        }
    }
    
    elseif (isset($_POST['delete_client'])) {
        try {
            $stmt = $conn->prepare("DELETE FROM clients WHERE id = :id");
            $stmt->execute([':id' => $_POST['delete_id']]);
            $message = '<div class="success-message">✓ Client deleted successfully!</div>';
        } catch (PDOException $e) {
            $message = '<div class="error-message">Error deleting client: ' . $e->getMessage() . '</div>';
        }
    }
    
    elseif (isset($_POST['reset_password'])) {
        try {
            $stmt = $conn->prepare("
                UPDATE clients 
                SET password = :password
                WHERE id = :id
            ");
            $stmt->execute([
                ':password' => password_hash('welcome123', PASSWORD_DEFAULT),
                ':id' => $_POST['reset_id']
            ]);
            $message = '<div class="success-message">✓ Password reset successfully! Default password: welcome123</div>';
        } catch (PDOException $e) {
            $message = '<div class="error-message">Error resetting password</div>';
        }
    }
    
    // ===== SERVICE MANAGEMENT =====
    elseif (isset($_POST['add_service'])) {
        try {
            $image_url = handleServiceImageUpload();
            
            $price_min = isset($_POST['price_range_min']) && is_numeric($_POST['price_range_min']) 
                ? floatval($_POST['price_range_min']) : 0;
            $price_max = isset($_POST['price_range_max']) && is_numeric($_POST['price_range_max']) 
                ? floatval($_POST['price_range_max']) : 0;
            
            $price_range = '$' . number_format($price_min, 2) . ' - $' . number_format($price_max, 2);
            
            $duration = ($_POST['duration_min'] ?? 3) . '-' . ($_POST['duration_max'] ?? 24) . ' months';
            
            $features_array = isset($_POST['features']) 
                ? array_filter(array_map('trim', explode(',', $_POST['features'])))
                : [];
            
            $features_string = !empty($features_array) 
                ? '{' . implode(',', array_map(function($feature) {
                    return '"' . escapePgArray($feature) . '"';
                }, $features_array)) . '}'
                : null;
            
            $is_featured = filter_var($_POST['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            // For new services, default to visible/enabled on client unless explicitly disabled.
            $is_visible_to_clients = isset($_POST['is_visible_to_clients']) ? 1 : 1;
            $show_price_to_clients = isset($_POST['show_price_to_clients']) ? 1 : 1;
            $show_description_to_clients = isset($_POST['show_description_to_clients']) ? 1 : 1;
            $allow_client_quotes = isset($_POST['allow_client_quotes']) ? 1 : 1;
            $requires_admin_approval = isset($_POST['requires_admin_approval']) ? 1 : 0;
            
            $stmt = $conn->prepare("
                INSERT INTO services (
                    title, description, detailed_description, 
                    price_range, duration, is_featured, 
                    features, image_url,
                    is_visible_to_clients, show_price_to_clients,
                    show_description_to_clients, allow_client_quotes,
                    requires_admin_approval, admin_notes,
                    meta_title, meta_description, meta_keywords,
                    sort_order
                ) VALUES (
                    :title, :description, :detailed_description,
                    :price_range, :duration, :is_featured,
                    :features, :image_url,
                    :is_visible_to_clients, :show_price_to_clients,
                    :show_description_to_clients, :allow_client_quotes,
                    :requires_admin_approval, :admin_notes,
                    :meta_title, :meta_description, :meta_keywords,
                    :sort_order
                )
            ");
            
            $stmt->bindValue(':title', $_POST['title'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $_POST['description'], PDO::PARAM_STR);
            $stmt->bindValue(':detailed_description', $_POST['detailed_description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':price_range', $price_range, PDO::PARAM_STR);
            $stmt->bindValue(':duration', $duration, PDO::PARAM_STR);
            $stmt->bindValue(':is_featured', $is_featured, PDO::PARAM_BOOL);
            $stmt->bindValue(':features', $features_string, PDO::PARAM_STR);
            $stmt->bindValue(':image_url', $image_url, PDO::PARAM_STR);
            $stmt->bindValue(':is_visible_to_clients', $is_visible_to_clients, PDO::PARAM_INT);
            $stmt->bindValue(':show_price_to_clients', $show_price_to_clients, PDO::PARAM_INT);
            $stmt->bindValue(':show_description_to_clients', $show_description_to_clients, PDO::PARAM_INT);
            $stmt->bindValue(':allow_client_quotes', $allow_client_quotes, PDO::PARAM_INT);
            $stmt->bindValue(':requires_admin_approval', $requires_admin_approval, PDO::PARAM_INT);
            $stmt->bindValue(':admin_notes', $_POST['admin_notes'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':meta_title', $_POST['meta_title'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':meta_description', $_POST['meta_description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':meta_keywords', $_POST['meta_keywords'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':sort_order', intval($_POST['sort_order'] ?? 0), PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $message = '<div class="success-message">✓ Service added successfully! ID: ' . $conn->lastInsertId() . '</div>';
            } else {
                $message = '<div class="error-message">Error adding service: Unknown error</div>';
            }
            
        } catch (Exception $e) {
            $message = '<div class="error-message">Error adding service: ' . $e->getMessage() . '</div>';
            error_log("Service add error: " . $e->getMessage());
        }
    }
    
    elseif (isset($_POST['update_service'])) {
        try {
            $current_stmt = $conn->prepare("SELECT image_url FROM services WHERE id = :id");
            $current_stmt->execute([':id' => $_POST['service_id']]);
            $current_data = $current_stmt->fetch();
            $image_url = $current_data['image_url'] ?? null;
            
            if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
                $image_url = null;
            }
            
            if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == UPLOAD_ERR_OK) {
                $image_url = handleServiceImageUpload();
            } 
            elseif (!empty($_POST['image_url']) && !isset($_POST['remove_image'])) {
                $image_url = $_POST['image_url'];
            }
            
            $price_range_min = isset($_POST['price_range_min']) && is_numeric($_POST['price_range_min']) 
                ? floatval($_POST['price_range_min']) : 0;
            $price_range_max = isset($_POST['price_range_max']) && is_numeric($_POST['price_range_max']) 
                ? floatval($_POST['price_range_max']) : 0;
            
            if ($price_range_min > 0 && $price_range_max > 0) {
                $price_range = '$' . number_format($price_range_min, 2) . ' - $' . number_format($price_range_max, 2);
            } elseif ($price_range_min > 0) {
                $price_range = 'From $' . number_format($price_range_min, 2);
            } elseif ($price_range_max > 0) {
                $price_range = 'Up to $' . number_format($price_range_max, 2);
            } else {
                $price_range = 'Contact for pricing';
            }
            
            $duration_min = intval($_POST['duration_min'] ?? 3);
            $duration_max = intval($_POST['duration_max'] ?? 24);
            $duration = $duration_min . '-' . $duration_max . ' months';
            
            $features_string = null;
            if (isset($_POST['features']) && !empty(trim($_POST['features']))) {
                $features_array = array_filter(array_map('trim', explode(',', $_POST['features'])));
                if (!empty($features_array)) {
                    $features_string = '{' . implode(',', array_map(function($feature) {
                        return '"' . escapePgArray($feature) . '"';
                    }, $features_array)) . '}';
                }
            }
            
            $is_featured = filter_var($_POST['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $is_visible_to_clients = isset($_POST['is_visible_to_clients']) ? 1 : 0;
            $show_price_to_clients = isset($_POST['show_price_to_clients']) ? 1 : 0;
            $show_description_to_clients = isset($_POST['show_description_to_clients']) ? 1 : 0;
            $allow_client_quotes = isset($_POST['allow_client_quotes']) ? 1 : 0;
            $requires_admin_approval = isset($_POST['requires_admin_approval']) ? 1 : 0;
            
            $sql = "UPDATE services SET 
                        title = :title,
                        description = :description,
                        detailed_description = :detailed_description,
                        price_range = :price_range,
                        duration = :duration,
                        is_featured = :is_featured,
                        image_url = :image_url,
                        is_visible_to_clients = :is_visible_to_clients,
                        show_price_to_clients = :show_price_to_clients,
                        show_description_to_clients = :show_description_to_clients,
                        allow_client_quotes = :allow_client_quotes,
                        requires_admin_approval = :requires_admin_approval,
                        admin_notes = :admin_notes,
                        meta_title = :meta_title,
                        meta_description = :meta_description,
                        meta_keywords = :meta_keywords,
                        sort_order = :sort_order";
            
            if ($features_string !== null) {
                $sql .= ", features = :features";
            } else {
                $sql .= ", features = NULL";
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            
            $stmt->bindValue(':title', $_POST['title'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $_POST['description'], PDO::PARAM_STR);
            $stmt->bindValue(':detailed_description', $_POST['detailed_description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':price_range', $price_range, PDO::PARAM_STR);
            $stmt->bindValue(':duration', $duration, PDO::PARAM_STR);
            $stmt->bindValue(':is_featured', $is_featured, PDO::PARAM_BOOL);
            $stmt->bindValue(':image_url', $image_url, PDO::PARAM_STR);
            $stmt->bindValue(':id', $_POST['service_id'], PDO::PARAM_INT);
            $stmt->bindValue(':is_visible_to_clients', $is_visible_to_clients, PDO::PARAM_INT);
            $stmt->bindValue(':show_price_to_clients', $show_price_to_clients, PDO::PARAM_INT);
            $stmt->bindValue(':show_description_to_clients', $show_description_to_clients, PDO::PARAM_INT);
            $stmt->bindValue(':allow_client_quotes', $allow_client_quotes, PDO::PARAM_INT);
            $stmt->bindValue(':requires_admin_approval', $requires_admin_approval, PDO::PARAM_INT);
            $stmt->bindValue(':admin_notes', $_POST['admin_notes'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':meta_title', $_POST['meta_title'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':meta_description', $_POST['meta_description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':meta_keywords', $_POST['meta_keywords'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':sort_order', intval($_POST['sort_order'] ?? 0), PDO::PARAM_INT);
            
            if ($features_string !== null) {
                $stmt->bindValue(':features', $features_string, PDO::PARAM_STR);
            }
            
            if ($stmt->execute()) {
                $message = '<div class="success-message">✓ Service updated successfully!</div>';
            } else {
                $message = '<div class="error-message">Error updating service: Unknown error</div>';
            }
            
        } catch (Exception $e) {
            $message = '<div class="error-message">Error updating service: ' . $e->getMessage() . '</div>';
            error_log("Service update error: " . $e->getMessage());
        }
    }
    
    elseif (isset($_POST['delete_service'])) {
        try {
            $check_stmt = $conn->prepare("
                SELECT COUNT(*) FROM service_inquiries 
                WHERE service_id = :service_id AND status != 'resolved'
            ");
            $check_stmt->execute([':service_id' => $_POST['delete_service_id']]);
            $unresolved_count = $check_stmt->fetchColumn();
            
            if ($unresolved_count > 0) {
                $message = '<div class="error-message">❌ Cannot delete service because it has ' . $unresolved_count . ' unresolved inquiry(s). Resolve them first.</div>';
            } else {
                $conn->beginTransaction();
                $delete_quotes = $conn->prepare("DELETE FROM service_quotes WHERE service_id = :service_id");
                $delete_quotes->execute([':service_id' => $_POST['delete_service_id']]);
                $delete_inquiries = $conn->prepare("DELETE FROM service_inquiries WHERE service_id = :service_id");
                $delete_inquiries->execute([':service_id' => $_POST['delete_service_id']]);
                $stmt = $conn->prepare("DELETE FROM services WHERE id = :id");
                $stmt->execute([':id' => $_POST['delete_service_id']]);
                $conn->commit();
                $message = '<div class="success-message">✓ Service and all associated data deleted successfully!</div>';
            }
            
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $message = '<div class="error-message">Error deleting service: ' . $e->getMessage() . '</div>';
            error_log("Service delete error: " . $e->getMessage());
        }
    }
    
    // ===== QUOTE MANAGEMENT =====
    elseif (isset($_POST['save_quote'])) {
        $result = saveQuote($conn, $_POST, $_SESSION['admin_id']);
        if ($result) {
            $message = '<div class="success-message">✓ Quote configuration saved successfully!</div>';
        } else {
            error_log("saveQuote failed. POST data: " . print_r($_POST, true));
            $errorInfo = $conn->errorInfo();
            if (!empty($errorInfo[2])) {
                error_log("Database error: " . $errorInfo[2]);
            }
            $message = '<div class="error-message">Error saving quote configuration. Check error log for details.</div>';
        }
    }
    
    elseif (isset($_POST['delete_quote'])) {
        try {
            $check = $conn->prepare("
                SELECT COUNT(*) FROM service_inquiries 
                WHERE quote_id = ? AND status != 'resolved'
            ");
            $check->execute([$_POST['delete_quote_id']]);
            $unresolved = $check->fetchColumn();

            if ($unresolved > 0) {
                $message = '<div class="error-message">❌ Cannot delete quote because there are unresolved inquiries for this specific quote.</div>';
            } else {
                $stmt = $conn->prepare("DELETE FROM service_quotes WHERE id = ?");
                $stmt->execute([$_POST['delete_quote_id']]);
                $message = '<div class="success-message">✓ Quote configuration deleted successfully!</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="error-message">Error deleting quote: ' . $e->getMessage() . '</div>';
        }
    }
    
    elseif (isset($_POST['toggle_quote_visibility'])) {
        try {
            $stmt = $conn->prepare("UPDATE service_quotes SET is_visible_to_clients = :visible WHERE id = :id");
            $stmt->execute([
                ':visible' => $_POST['is_visible'],
                ':id' => $_POST['quote_id']
            ]);
            $message = '<div class="success-message">✓ Quote visibility updated!</div>';
            header("Location: dashboard.php?action=quotes&success=1");
            exit();
        } catch (PDOException $e) {
            $message = '<div class="error-message">Error updating visibility: ' . $e->getMessage() . '</div>';
        }
    }
    
    // ===== CUSTOM FIELD MANAGEMENT =====
    elseif (isset($_POST['add_custom_field'])) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO custom_fields (field_name, field_type, field_options, required, sort_order)
                VALUES (:field_name, :field_type, :field_options, :required, :sort_order)
            ");
            $stmt->execute([
                ':field_name' => $_POST['field_name'],
                ':field_type' => $_POST['field_type'],
                ':field_options' => $_POST['field_options'] ?? null,
                ':required' => isset($_POST['required']) ? 1 : 0,
                ':sort_order' => intval($_POST['sort_order'] ?? 0)
            ]);
            $newId = $conn->lastInsertId();
            // Add visibility entry for this custom field
            addCustomFieldVisibility($conn, $newId, $_POST['field_name']);
            $message = '<div class="success-message">✓ Custom field added successfully!</div>';
        } catch (PDOException $e) {
            $message = '<div class="error-message">Error adding custom field: ' . $e->getMessage() . '</div>';
        }
    }

    elseif (isset($_POST['update_custom_field'])) {
        try {
            $stmt = $conn->prepare("
                UPDATE custom_fields SET
                    field_name = :field_name,
                    field_type = :field_type,
                    field_options = :field_options,
                    required = :required,
                    sort_order = :sort_order
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $_POST['field_id'],
                ':field_name' => $_POST['field_name'],
                ':field_type' => $_POST['field_type'],
                ':field_options' => $_POST['field_options'] ?? null,
                ':required' => isset($_POST['required']) ? 1 : 0,
                ':sort_order' => intval($_POST['sort_order'] ?? 0)
            ]);
            // Update visibility label
            $field_key = 'custom_' . $_POST['field_id'];
            $update_vis = $conn->prepare("UPDATE field_visibility SET field_label = ? WHERE field_key = ?");
            $update_vis->execute([$_POST['field_name'], $field_key]);
            $message = '<div class="success-message">✓ Custom field updated successfully!</div>';
        } catch (PDOException $e) {
            $message = '<div class="error-message">Error updating custom field: ' . $e->getMessage() . '</div>';
        }
    }

    elseif (isset($_POST['delete_custom_field'])) {
        try {
            // Delete visibility entry first
            deleteCustomFieldVisibility($conn, $_POST['delete_field_id']);
            // Delete custom field
            $stmt = $conn->prepare("DELETE FROM custom_fields WHERE id = ?");
            $stmt->execute([$_POST['delete_field_id']]);
            $message = '<div class="success-message">✓ Custom field deleted successfully!</div>';
        } catch (PDOException $e) {
            $message = '<div class="error-message">Error deleting custom field: ' . $e->getMessage() . '</div>';
        }
    }
    
// ===== FIELD VISIBILITY MANAGEMENT =====
elseif (isset($_POST['update_visibility'])) {
    error_log("=== update_visibility called ===");
    error_log("POST data: " . print_r($_POST, true));
    
    try {
        // 1. Get all field keys from the database
        $all_fields = $conn->query("SELECT field_key FROM field_visibility")->fetchAll(PDO::FETCH_COLUMN);
        $updated_count = 0;
        $failed_count = 0;
        
        foreach ($all_fields as $field_key) {
            // Check if the field was sent and its value is '1'
            $is_visible = isset($_POST['visibility'][$field_key]) && $_POST['visibility'][$field_key] == '1';
            error_log("Updating field_key: $field_key to " . ($is_visible ? 'visible' : 'hidden'));
            
            $result = updateFieldVisibility($conn, $field_key, $is_visible);
            if ($result) {
                $updated_count++;
            } else {
                $failed_count++;
            }
        }
        
        if ($failed_count == 0) {
            $message = '<div class="success-message">✓ Visibility settings updated successfully! (' . $updated_count . ' fields)</div>';
        } else {
            $message = '<div class="error-message">Updated ' . $updated_count . ' fields, but ' . $failed_count . ' fields failed.</div>';
        }
        
        // Refresh visibility data for the page
        $field_visibility = getFieldVisibility($conn);
        $skip_redirect = true;
        
    } catch (Exception $e) {
        error_log("Exception in update_visibility: " . $e->getMessage());
        $message = '<div class="error-message">Error updating visibility: ' . $e->getMessage() . '</div>';
        $skip_redirect = true;
    }
}
    // ===== INQUIRY MANAGEMENT =====
    elseif (isset($_POST['update_inquiry_status'])) {
        try {
            $stmt = $conn->prepare("
                UPDATE service_inquiries 
                SET status = :status, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':status' => $_POST['update_inquiry_status'],
                ':id' => $_POST['inquiry_id']
            ]);
            
            $message = '<div class="success-message">✓ Inquiry status updated successfully!</div>';
            
            $inq = $conn->prepare("SELECT email, name FROM service_inquiries WHERE id = ?");
            $inq->execute([$_POST['inquiry_id']]);
            $client = $inq->fetch();
            if ($client) {
                $subject = "Your inquiry status has been updated";
                $body = "<p>Dear " . htmlspecialchars($client['name']) . ",</p>
                         <p>Your inquiry (ID: " . $_POST['inquiry_id'] . ") has been updated to: <strong>" . $_POST['update_inquiry_status'] . "</strong>.</p>
                         <p>Thank you for choosing P.S Pakpi Enterprises.</p>";
                sendEmail($client['email'], $subject, $body);
            }
            
        } catch (PDOException $e) {
            $message = '<div class="error-message">Error updating inquiry: ' . $e->getMessage() . '</div>';
        }
    }
    
    elseif (isset($_POST['delete_inquiry'])) {
        try {
            $check_stmt = $conn->prepare("SELECT status FROM service_inquiries WHERE id = :id");
            $check_stmt->execute([':id' => $_POST['delete_inquiry_id']]);
            $inquiry = $check_stmt->fetch();
            
            if ($inquiry && $inquiry['status'] !== 'resolved') {
                $message = '<div class="error-message">❌ Only resolved inquiries can be deleted.</div>';
            } else {
                $stmt = $conn->prepare("DELETE FROM service_inquiries WHERE id = :id");
                $stmt->execute([':id' => $_POST['delete_inquiry_id']]);
                header("Location: dashboard.php?action=inquiries&success=1");
                exit();
            }
        } catch (PDOException $e) {
            $message = '<div class="error-message">Error deleting inquiry: ' . $e->getMessage() . '</div>';
        }
    }
    
    elseif (isset($_POST['save_inquiry_notes'])) {
        try {
            $updates = [];
            $params = [':id' => $_POST['inquiry_id']];
            
            if (isset($_POST['admin_notes'])) {
                $updates[] = "admin_notes = :notes";
                $params[':notes'] = trim($_POST['admin_notes']) ?: null;
            }
            
            if (isset($_POST['follow_up_date'])) {
                $updates[] = "follow_up_date = :follow_up_date";
                $params[':follow_up_date'] = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;
            }
            
            if (isset($_POST['update_status']) && !empty($_POST['update_status'])) {
                $updates[] = "status = :status";
                $params[':status'] = $_POST['update_status'];
            }
            
            $updates[] = "updated_at = CURRENT_TIMESTAMP";
            
            if (!empty($updates)) {
                $sql = "UPDATE service_inquiries SET " . implode(", ", $updates) . " WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $message = '<div class="success-message">✓ Notes and updates saved successfully!</div>';
            }
            
        } catch (Exception $e) {
            $message = '<div class="error-message">Error: ' . $e->getMessage() . '</div>';
            error_log("Error saving inquiry notes: " . $e->getMessage());
        }
    }
    
    // Redirect on success (except when viewing details)
  // Redirect on success (except when viewing details), but skip if $skip_redirect is set
// Redirect on success (except when viewing details), but skip if $skip_redirect is set
if (!isset($skip_redirect) && strpos($message, 'success') !== false && !isset($_GET['view_inquiry'])) {
    $redirect_tab = match(true) {
        isset($_POST['add_service']) || isset($_POST['update_service']) || isset($_POST['delete_service']) => 'services',
        isset($_POST['add_client']) || isset($_POST['update_client']) || isset($_POST['delete_client']) || isset($_POST['reset_password']) => 'clients',
        isset($_POST['save_quote']) || isset($_POST['delete_quote']) || isset($_POST['toggle_quote_visibility']) => 'quotes',
        isset($_POST['add_custom_field']) || isset($_POST['update_custom_field']) || isset($_POST['delete_custom_field']) => 'custom_fields',
        isset($_POST['update_visibility']) => 'visibility',
        isset($_POST['delete_inquiry']) => 'inquiries',
        default => 'dashboard'
    };
    
    header("Location: dashboard.php?action=$redirect_tab&success=1");
    exit();
}
}

// ============================================
// LOAD STATISTICS
// ============================================

$stats = [
    'total_clients' => 0, 'active_clients' => 0, 'pending_clients' => 0, 'inactive_clients' => 0,
    'total_admins' => 0, 'total_services' => 0, 'featured_services' => 0,
    'total_quotes' => 0, 'featured_quotes' => 0, 'visible_quotes' => 0, 'hidden_quotes' => 0,
    'quotes_with_inquiries' => 0,
    'total_inquiries' => 0, 'new_inquiries' => 0, 'pending_inquiries' => 0,
    'contacted_inquiries' => 0, 'resolved_inquiries' => 0,
    'upcoming_followups' => 0,
    'total_custom_fields' => 0
];

try {
    $stats['total_clients'] = $conn->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    $stats['active_clients'] = $conn->query("SELECT COUNT(*) FROM clients WHERE status='active'")->fetchColumn();
    $stats['pending_clients'] = $conn->query("SELECT COUNT(*) FROM clients WHERE status='pending'")->fetchColumn();
    $stats['inactive_clients'] = $conn->query("SELECT COUNT(*) FROM clients WHERE status='inactive'")->fetchColumn();
    $stats['total_admins'] = $conn->query("SELECT COUNT(*) FROM admins WHERE status='active'")->fetchColumn();
    $stats['total_services'] = $conn->query("SELECT COUNT(*) FROM services")->fetchColumn();
    $stats['featured_services'] = $conn->query("SELECT COUNT(*) FROM services WHERE is_featured=1")->fetchColumn();
    $stats['total_quotes'] = $conn->query("SELECT COUNT(*) FROM service_quotes")->fetchColumn();
    $stats['featured_quotes'] = $conn->query("SELECT COUNT(*) FROM service_quotes WHERE is_featured_quote=1")->fetchColumn();
    $stats['visible_quotes'] = $conn->query("SELECT COUNT(*) FROM service_quotes WHERE is_visible_to_clients = 1")->fetchColumn();
    $stats['hidden_quotes'] = $conn->query("SELECT COUNT(*) FROM service_quotes WHERE is_visible_to_clients = 0")->fetchColumn();
    $stats['quotes_with_inquiries'] = $conn->query("SELECT COUNT(DISTINCT quote_id) FROM service_inquiries WHERE quote_id IS NOT NULL")->fetchColumn();
    $stats['total_inquiries'] = $conn->query("SELECT COUNT(*) FROM service_inquiries")->fetchColumn();
    $stats['new_inquiries'] = $conn->query("SELECT COUNT(*) FROM service_inquiries WHERE status='new'")->fetchColumn();
    $stats['pending_inquiries'] = $conn->query("SELECT COUNT(*) FROM service_inquiries WHERE status='pending'")->fetchColumn();
    $stats['contacted_inquiries'] = $conn->query("SELECT COUNT(*) FROM service_inquiries WHERE status='contacted'")->fetchColumn();
    $stats['resolved_inquiries'] = $conn->query("SELECT COUNT(*) FROM service_inquiries WHERE status='resolved'")->fetchColumn();
    $stats['upcoming_followups'] = $conn->query("
        SELECT COUNT(*) FROM service_inquiries 
        WHERE follow_up_date IS NOT NULL 
        AND follow_up_date >= CURRENT_DATE
    ")->fetchColumn();
    $stats['total_custom_fields'] = $conn->query("SELECT COUNT(*) FROM custom_fields")->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Error loading statistics: " . $e->getMessage());
}

// ============================================
// PAGINATION & FILTERS
// ============================================

$clients = [];
$services = [];
$inquiries = [];
$all_quotes = [];
$custom_fields = [];
$total_pages = 1;
$inquiry_total_pages = 1;
$quotes_total_pages = 1;
$custom_fields_total_pages = 1;
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    if ($action === 'clients') {
        $query = "SELECT c.*, a.username as created_by_name 
                  FROM clients c 
                  LEFT JOIN admins a ON c.created_by = a.id 
                  WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (c.full_name LIKE :search OR c.email LIKE :search OR c.client_id LIKE :search OR c.company_name LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($status_filter)) {
            $query .= " AND c.status = :status";
            $params[':status'] = $status_filter;
        }

        $count_stmt = $conn->prepare(preg_replace('/SELECT.*FROM/', 'SELECT COUNT(*) FROM', $query));
        $count_stmt->execute($params);
        $total_pages = ceil($count_stmt->fetchColumn() / $limit);

        $query .= " ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $clients = $stmt->fetchAll();
        
    } elseif ($action === 'services') {
        $query = "SELECT * FROM services WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (title LIKE :search OR description LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $count_stmt = $conn->prepare(preg_replace('/SELECT.*FROM/', 'SELECT COUNT(*) FROM', $query));
        $count_stmt->execute($params);
        $total_pages = ceil($count_stmt->fetchColumn() / $limit);

        $query .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $services = $stmt->fetchAll();
        
    } elseif ($action === 'inquiries') {
        $query = "SELECT si.*, s.title as service_title 
                  FROM service_inquiries si 
                  LEFT JOIN services s ON si.service_id = s.id 
                  WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (si.name LIKE :search OR si.email LIKE :search OR si.service_name LIKE :search OR s.title LIKE :search OR si.organization LIKE :search OR si.phone LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($status_filter)) {
            $query .= " AND si.status = :status";
            $params[':status'] = $status_filter;
        }

        $count_stmt = $conn->prepare(preg_replace('/SELECT.*FROM/', 'SELECT COUNT(*) FROM', $query));
        $count_stmt->execute($params);
        $inquiry_total_pages = ceil($count_stmt->fetchColumn() / $limit);

        $query .= " ORDER BY si.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $inquiries = $stmt->fetchAll();
        
    } elseif ($action === 'quotes') {
        $query = "
            SELECT q.*, s.title as service_title,
                   (SELECT COUNT(*) FROM service_inquiries i WHERE i.quote_id = q.id AND i.status != 'resolved') as unresolved_inquiries
            FROM service_quotes q
            LEFT JOIN services s ON q.service_id = s.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (q.quote_title LIKE :search OR s.title LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $count_query = "SELECT COUNT(*) FROM service_quotes q LEFT JOIN services s ON q.service_id = s.id WHERE 1=1";
        if (!empty($search)) {
            $count_query .= " AND (q.quote_title LIKE :search OR s.title LIKE :search)";
        }
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->execute($params);
        $quotes_total_pages = ceil($count_stmt->fetchColumn() / $limit);

        $query .= " ORDER BY q.id DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $all_quotes = $stmt->fetchAll();
        
    } elseif ($action === 'custom_fields') {
        $query = "SELECT * FROM custom_fields ORDER BY sort_order ASC, id ASC";
        $count_stmt = $conn->query("SELECT COUNT(*) FROM custom_fields");
        $custom_fields_total_pages = ceil($count_stmt->fetchColumn() / $limit);
        $query .= " LIMIT :limit OFFSET :offset";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $custom_fields = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $message = '<div class="error-message">Error loading data: ' . $e->getMessage() . '</div>';
    error_log("Error loading data: " . $e->getMessage());
}

// ============================================
// LOAD EDIT/VIEW DATA
// ============================================

$edit_client = null;
$view_client = null;
$edit_service = null;
$view_service = null;
$view_inquiry = null;

if (isset($_GET['edit_client']) && is_numeric($_GET['edit_client'])) {
    $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$_GET['edit_client']]);
    $edit_client = $stmt->fetch();
}

if (isset($_GET['view_client']) && is_numeric($_GET['view_client'])) {
    $stmt = $conn->prepare("SELECT c.*, a.username as created_by_name FROM clients c LEFT JOIN admins a ON c.created_by = a.id WHERE c.id = ?");
    $stmt->execute([$_GET['view_client']]);
    $view_client = $stmt->fetch();
}

if (isset($_GET['edit_service']) && is_numeric($_GET['edit_service'])) {
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$_GET['edit_service']]);
    $edit_service = $stmt->fetch();
    
    if ($edit_service) {
        if (preg_match('/\$([\d,]+)\s*-\s*\$([\d,]+)/', $edit_service['price_range'], $price_matches)) {
            $edit_service['price_range_min'] = str_replace(',', '', $price_matches[1]);
            $edit_service['price_range_max'] = str_replace(',', '', $price_matches[2]);
        }
        
        if (preg_match('/(\d+)-(\d+)/', $edit_service['duration'], $duration_matches)) {
            $edit_service['duration_min'] = $duration_matches[1];
            $edit_service['duration_max'] = $duration_matches[2];
        }
        
        if ($edit_service['features']) {
            $features_str = trim($edit_service['features'], '{}');
            $edit_service['features_list'] = str_replace('"', '', $features_str);
        }
    }
}

if (isset($_GET['view_service']) && is_numeric($_GET['view_service'])) {
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$_GET['view_service']]);
    $view_service = $stmt->fetch();
    if ($view_service) {
        $view_service['quotes'] = getQuotesByServiceId($conn, $view_service['id']);
        $view_service['quote'] = !empty($view_service['quotes']) ? $view_service['quotes'][0] : null;
    }
}

if (isset($_GET['view_inquiry']) && is_numeric($_GET['view_inquiry'])) {
    $stmt = $conn->prepare("SELECT si.*, s.title as service_title FROM service_inquiries si LEFT JOIN services s ON si.service_id = s.id WHERE si.id = ?");
    $stmt->execute([$_GET['view_inquiry']]);
    $view_inquiry = $stmt->fetch();
}

// Recent data for dashboard
try {
    $recent_clients = $conn->query("SELECT * FROM clients ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $recent_services = $conn->query("SELECT * FROM services ORDER BY id DESC LIMIT 5")->fetchAll();
    $recent_inquiries = $conn->query("SELECT si.*, s.title as service_title FROM service_inquiries si LEFT JOIN services s ON si.service_id = s.id ORDER BY si.created_at DESC LIMIT 5")->fetchAll();
    $upcoming_followups = $conn->query("
        SELECT si.*, s.title as service_title 
        FROM service_inquiries si 
        LEFT JOIN services s ON si.service_id = s.id 
        WHERE si.follow_up_date IS NOT NULL 
        AND si.follow_up_date >= CURRENT_DATE 
        ORDER BY si.follow_up_date ASC 
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Error loading recent data: " . $e->getMessage());
    $recent_clients = [];
    $recent_services = [];
    $recent_inquiries = [];
    $upcoming_followups = [];
}

// Include the HTML template
include 'dashboard_template.php';
?>
