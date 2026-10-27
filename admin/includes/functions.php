<?php
// D:\xampp\htdocs\ps-pakpi\admin\includes\functions.php

/**
 * Get row count from table
 */
function getRowCount($conn, $table) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM $table");
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        error_log("Error counting rows: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get recent quotes
 */
function getRecentQuotes($conn, $limit = 5) {
    try {
        $stmt = $conn->prepare("
            SELECT sq.*, s.title as service_title 
            FROM service_quotes sq
            LEFT JOIN services s ON sq.service_id = s.id
            ORDER BY sq.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching recent quotes: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all services
 */
function getServices($conn) {
    try {
        $stmt = $conn->query("SELECT id, title FROM services ORDER BY title");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching services: " . $e->getMessage());
        return [];
    }
}

/**
 * Get quote by service ID (single quote - backward compatibility)
 */
function getQuoteByServiceId($conn, $service_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM service_quotes WHERE service_id = ? LIMIT 1");
        $stmt->execute([$service_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching quote by service: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all quotes for a service (multiple quotes)
 */
function getQuotesByServiceId($conn, $service_id) {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM service_quotes 
            WHERE service_id = ? 
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$service_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching quotes by service: " . $e->getMessage());
        return [];
    }
}

/**
 * Get quote by ID
 */
function getQuoteById($conn, $quote_id) {
    try {
        $stmt = $conn->prepare("
            SELECT sq.*, s.title as service_title 
            FROM service_quotes sq
            LEFT JOIN services s ON sq.service_id = s.id
            WHERE sq.id = ?
        ");
        $stmt->execute([$quote_id]);
        $quote = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($quote) {
            $quote['custom_values'] = getQuoteCustomValues($conn, $quote_id);
        }
        return $quote;
    } catch (PDOException $e) {
        error_log("Error fetching quote: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all quotes with service names
 */
function getAllQuotes($conn) {
    try {
        $stmt = $conn->query("
            SELECT sq.*, s.title as service_title 
            FROM service_quotes sq
            LEFT JOIN services s ON sq.service_id = s.id
            ORDER BY s.title ASC, sq.sort_order ASC, sq.id ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching all quotes: " . $e->getMessage());
        return [];
    }
}

/**
 * Get quote categories for a service
 */
function getQuoteCategories($conn, $service_id) {
    try {
        $stmt = $conn->prepare("
            SELECT DISTINCT quote_category 
            FROM service_quotes 
            WHERE service_id = ? AND quote_category IS NOT NULL AND quote_category != ''
            ORDER BY quote_category
        ");
        $stmt->execute([$service_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching quote categories: " . $e->getMessage());
        return [];
    }
}

// ===== CUSTOM FIELDS FUNCTIONS =====

/**
 * Get all custom fields ordered by sort_order
 */
function getCustomFields($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM custom_fields ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching custom fields: " . $e->getMessage());
        return [];
    }
}

/**
 * Get custom field values for a specific quote
 */
function getQuoteCustomValues($conn, $quote_id) {
    try {
        $stmt = $conn->prepare("
            SELECT qcv.*, cf.field_name, cf.field_type, cf.field_options
            FROM quote_custom_values qcv
            JOIN custom_fields cf ON qcv.field_id = cf.id
            WHERE qcv.quote_id = ?
        ");
        $stmt->execute([$quote_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching quote custom values: " . $e->getMessage());
        return [];
    }
}

/**
 * Save custom field values for a quote
 */
function saveQuoteCustomValues($conn, $quote_id, $custom_data) {
    try {
        $stmt = $conn->prepare("DELETE FROM quote_custom_values WHERE quote_id = ?");
        $stmt->execute([$quote_id]);

        if (!empty($custom_data) && is_array($custom_data)) {
            $insert = $conn->prepare("
                INSERT INTO quote_custom_values (quote_id, field_id, field_value)
                VALUES (?, ?, ?)
            ");
            foreach ($custom_data as $field_id => $value) {
                if (!empty($value)) {
                    $insert->execute([$quote_id, $field_id, $value]);
                }
            }
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error saving quote custom values: " . $e->getMessage());
        return false;
    }
}

// ===== FIELD VISIBILITY FUNCTIONS =====

/**
 * Get all visibility settings grouped by section
 */
/**
 * Get all visibility settings grouped by section
 */
function getFieldVisibility($conn) {
    try {
        $stmt = $conn->query("
            SELECT * FROM field_visibility 
            ORDER BY 
                CASE section 
                    WHEN 'service' THEN 1 
                    WHEN 'quote' THEN 2 
                    WHEN 'custom' THEN 3 
                    WHEN 'inquiry' THEN 4 
                    ELSE 5 
                END, 
                sort_order ASC, 
                field_label ASC
        ");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $grouped = [];
        foreach ($results as $row) {
            $grouped[$row['section']][] = $row;
        }
        return $grouped;
    } catch (PDOException $e) {
        error_log("Error fetching field visibility: " . $e->getMessage());
        return [];
    }
}
function updateFieldVisibility($conn, $field_key, $is_visible) {
    try {
        $stmt = $conn->prepare("
            UPDATE field_visibility 
            SET is_visible = :visible 
            WHERE field_key = :field_key
        ");
        $stmt->execute([
            ':visible' => $is_visible ? 1 : 0,
            ':field_key' => $field_key
        ]);
        
        $rowCount = $stmt->rowCount();
        error_log("updateFieldVisibility: field_key=$field_key, rowCount=$rowCount");
        
        if ($rowCount == 0) {
            error_log("updateFieldVisibility: No rows updated for field_key: $field_key");
            return false;
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error updating field visibility: " . $e->getMessage());
        return false;
    }
}
/**
 * Add visibility entry for a custom field
 */
function addCustomFieldVisibility($conn, $field_id, $field_name) {
    try {
        $field_key = 'custom_' . $field_id;
        $stmt = $conn->prepare("
            INSERT INTO field_visibility (field_key, field_label, section, sort_order)
            VALUES (:field_key, :field_label, 'custom', 999)
            ON CONFLICT (field_key) DO NOTHING
        ");
        return $stmt->execute([
            ':field_key' => $field_key,
            ':field_label' => $field_name
        ]);
    } catch (PDOException $e) {
        error_log("Error adding custom field visibility: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete visibility entry for a custom field
 */
function deleteCustomFieldVisibility($conn, $field_id) {
    try {
        $field_key = 'custom_' . $field_id;
        $stmt = $conn->prepare("DELETE FROM field_visibility WHERE field_key = ?");
        return $stmt->execute([$field_key]);
    } catch (PDOException $e) {
        error_log("Error deleting custom field visibility: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a field is visible
 */
function isFieldVisible($visibility, $field_key, $default = true) {
    foreach ($visibility as $section => $fields) {
        foreach ($fields as $field) {
            if ($field['field_key'] === $field_key) {
                return (bool)$field['is_visible'];
            }
        }
    }
    return $default;
}

/**
 * Insert or update quote - now saves custom fields
 */
function saveQuote($conn, $data, $admin_id) {
    if (empty($data['service_id'])) {
        error_log("saveQuote error: service_id is missing");
        return false;
    }
    
    try {
        $existing = isset($data['id']) && !empty($data['id']) 
            ? getQuoteById($conn, $data['id']) 
            : null;
        
        $inclusions = textareaToPgArray($data['inclusions'] ?? '');
        $exclusions = textareaToPgArray($data['exclusions'] ?? '');
        $prerequisites = textareaToPgArray($data['prerequisites'] ?? '');
        $documents = textareaToPgArray($data['documents_required'] ?? '');
        
        if ($existing) {
            $sql = "UPDATE service_quotes SET
                        service_id = :service_id,
                        quote_title = :quote_title,
                        quote_description = :quote_description,
                        price_type = :price_type,
                        min_price = :min_price,
                        max_price = :max_price,
                        fixed_price = :fixed_price,
                        duration_type = :duration_type,
                        min_duration = :min_duration,
                        max_duration = :max_duration,
                        inclusions = :inclusions,
                        exclusions = :exclusions,
                        prerequisites = :prerequisites,
                        documents_required = :documents_required,
                        has_discount = :has_discount,
                        discount_percentage = :discount_percentage,
                        discount_description = :discount_description,
                        is_featured_quote = :is_featured_quote,
                        quote_category = :quote_category,
                        quote_code = :quote_code,
                        sort_order = :sort_order,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $params = [
                ':id' => $data['id'],
                ':service_id' => $data['service_id'],
                ':quote_title' => $data['quote_title'],
                ':quote_description' => $data['quote_description'] ?? '',
                ':price_type' => $data['price_type'],
                ':min_price' => !empty($data['min_price']) ? floatval($data['min_price']) : null,
                ':max_price' => !empty($data['max_price']) ? floatval($data['max_price']) : null,
                ':fixed_price' => !empty($data['fixed_price']) ? floatval($data['fixed_price']) : null,
                ':duration_type' => $data['duration_type'] ?? 'fixed_range',
                ':min_duration' => !empty($data['min_duration']) ? intval($data['min_duration']) : null,
                ':max_duration' => !empty($data['max_duration']) ? intval($data['max_duration']) : null,
                ':inclusions' => $inclusions,
                ':exclusions' => $exclusions,
                ':prerequisites' => $prerequisites,
                ':documents_required' => $documents,
                ':has_discount' => isset($data['has_discount']) ? 1 : 0,
                ':discount_percentage' => !empty($data['discount_percentage']) ? floatval($data['discount_percentage']) : null,
                ':discount_description' => $data['discount_description'] ?? '',
                ':is_featured_quote' => isset($data['is_featured_quote']) ? 1 : 0,
                ':quote_category' => $data['quote_category'] ?? null,
                ':quote_code' => $data['quote_code'] ?? null,
                ':sort_order' => !empty($data['sort_order']) ? intval($data['sort_order']) : 0
            ];
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute($params);
            if ($result) {
                $custom_data = $data['custom_fields'] ?? [];
                saveQuoteCustomValues($conn, $data['id'], $custom_data);
                return $data['id'];
            }
            return false;
            
        } else {
            $sql = "INSERT INTO service_quotes (
                        service_id, quote_title, quote_description,
                        price_type, min_price, max_price, fixed_price,
                        duration_type, min_duration, max_duration,
                        inclusions, exclusions, prerequisites, documents_required,
                        has_discount, discount_percentage, discount_description,
                        is_featured_quote, created_by, quote_category, quote_code, sort_order,
                        created_at, updated_at
                    ) VALUES (
                        :service_id, :quote_title, :quote_description,
                        :price_type, :min_price, :max_price, :fixed_price,
                        :duration_type, :min_duration, :max_duration,
                        :inclusions, :exclusions, :prerequisites, :documents_required,
                        :has_discount, :discount_percentage, :discount_description,
                        :is_featured_quote, :created_by, :quote_category, :quote_code, :sort_order,
                        CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )";
            
            $params = [
                ':service_id' => $data['service_id'],
                ':quote_title' => $data['quote_title'],
                ':quote_description' => $data['quote_description'] ?? '',
                ':price_type' => $data['price_type'],
                ':min_price' => !empty($data['min_price']) ? floatval($data['min_price']) : null,
                ':max_price' => !empty($data['max_price']) ? floatval($data['max_price']) : null,
                ':fixed_price' => !empty($data['fixed_price']) ? floatval($data['fixed_price']) : null,
                ':duration_type' => $data['duration_type'] ?? 'fixed_range',
                ':min_duration' => !empty($data['min_duration']) ? intval($data['min_duration']) : null,
                ':max_duration' => !empty($data['max_duration']) ? intval($data['max_duration']) : null,
                ':inclusions' => $inclusions,
                ':exclusions' => $exclusions,
                ':prerequisites' => $prerequisites,
                ':documents_required' => $documents,
                ':has_discount' => isset($data['has_discount']) ? 1 : 0,
                ':discount_percentage' => !empty($data['discount_percentage']) ? floatval($data['discount_percentage']) : null,
                ':discount_description' => $data['discount_description'] ?? '',
                ':is_featured_quote' => isset($data['is_featured_quote']) ? 1 : 0,
                ':created_by' => $admin_id,
                ':quote_category' => $data['quote_category'] ?? null,
                ':quote_code' => $data['quote_code'] ?? null,
                ':sort_order' => !empty($data['sort_order']) ? intval($data['sort_order']) : 0
            ];
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute($params);
            if ($result) {
                $newId = $conn->lastInsertId();
                $custom_data = $data['custom_fields'] ?? [];
                saveQuoteCustomValues($conn, $newId, $custom_data);
                return $newId;
            }
            return false;
        }
        
    } catch (PDOException $e) {
        error_log("Error saving quote: " . $e->getMessage());
        error_log("Data: " . print_r($data, true));
        return false;
    }
}

/**
 * Delete quote
 */
function deleteQuote($conn, $quote_id) {
    try {
        $stmt = $conn->prepare("DELETE FROM service_quotes WHERE id = ?");
        return $stmt->execute([$quote_id]);
    } catch (PDOException $e) {
        error_log("Error deleting quote: " . $e->getMessage());
        return false;
    }
}

/**
 * Duplicate quote
 */
function duplicateQuote($conn, $quote_id, $admin_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM service_quotes WHERE id = ?");
        $stmt->execute([$quote_id]);
        $quote = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$quote) {
            return false;
        }
        
        $new_title = $quote['quote_title'] . ' (Copy)';
        
        $sql = "INSERT INTO service_quotes (
                    service_id, quote_title, quote_description,
                    price_type, min_price, max_price, fixed_price,
                    duration_type, min_duration, max_duration,
                    inclusions, exclusions, prerequisites, documents_required,
                    has_discount, discount_percentage, discount_description,
                    is_featured_quote, is_active, created_by,
                    quote_category, quote_code, sort_order,
                    created_at, updated_at
                ) VALUES (
                    :service_id, :quote_title, :quote_description,
                    :price_type, :min_price, :max_price, :fixed_price,
                    :duration_type, :min_duration, :max_duration,
                    :inclusions, :exclusions, :prerequisites, :documents_required,
                    :has_discount, :discount_percentage, :discount_description,
                    :is_featured_quote, :is_active, :created_by,
                    :quote_category, :quote_code, :sort_order,
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )";
        
        $params = [
            'service_id' => $quote['service_id'],
            'quote_title' => $new_title,
            'quote_description' => $quote['quote_description'],
            'price_type' => $quote['price_type'],
            'min_price' => $quote['min_price'],
            'max_price' => $quote['max_price'],
            'fixed_price' => $quote['fixed_price'],
            'duration_type' => $quote['duration_type'],
            'min_duration' => $quote['min_duration'],
            'max_duration' => $quote['max_duration'],
            'inclusions' => $quote['inclusions'],
            'exclusions' => $quote['exclusions'],
            'prerequisites' => $quote['prerequisites'],
            'documents_required' => $quote['documents_required'],
            'has_discount' => $quote['has_discount'],
            'discount_percentage' => $quote['discount_percentage'],
            'discount_description' => $quote['discount_description'],
            'is_featured_quote' => $quote['is_featured_quote'],
            'is_active' => 1,
            'created_by' => $admin_id,
            'quote_category' => $quote['quote_category'],
            'quote_code' => $quote['quote_code'] ? $quote['quote_code'] . '-copy' : null,
            'sort_order' => $quote['sort_order'] + 10
        ];
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute($params);
        
        return $result ? $conn->lastInsertId() : false;
        
    } catch (PDOException $e) {
        error_log("Error duplicating quote: " . $e->getMessage());
        return false;
    }
}

/**
 * Convert a stored list value to a PHP array.
 */
function pgArrayToArray($pgArray) {
    if (empty($pgArray) || $pgArray === '{}') {
        return [];
    }

    $json = json_decode($pgArray, true);
    if (is_array($json)) {
        return array_filter($json);
    }
    
    $content = substr($pgArray, 1, -1);
    if (empty($content)) {
        return [];
    }
    
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

/**
 * Format price for display
 */
function formatPrice($price) {
    if ($price === null || $price === '') {
        return '₹0.00';
    }
    $price = floatval($price);
    return '₹' . number_format($price, 2);
}

/**
 * Convert textarea content to a JSON list for SQLite storage.
 */
function textareaToPgArray($textarea_content) {
    if (empty(trim($textarea_content))) {
        return '[]';
    }
    
    $lines = array_filter(array_map('trim', explode("\n", trim($textarea_content))));
    $lines = array_filter($lines, function($line) {
        return !empty($line);
    });
    
    if (empty($lines)) {
        return '[]';
    }

    return json_encode(array_values($lines));
}
?>
