<?php
session_start();
require_once('../config.php');
require_once('includes/functions.php');

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

try {
    $conn = getConnection(); // Get connection
    $quote = getQuoteById($conn, $id);
    
    if ($quote) {
        // Convert PostgreSQL arrays to PHP arrays
        $quote['inclusions'] = !empty($quote['inclusions']) && $quote['inclusions'] != '{}' 
            ? pgArrayToArray($quote['inclusions']) : [];
        $quote['exclusions'] = !empty($quote['exclusions']) && $quote['exclusions'] != '{}' 
            ? pgArrayToArray($quote['exclusions']) : [];
        $quote['prerequisites'] = !empty($quote['prerequisites']) && $quote['prerequisites'] != '{}' 
            ? pgArrayToArray($quote['prerequisites']) : [];
        $quote['documents_required'] = !empty($quote['documents_required']) && $quote['documents_required'] != '{}' 
            ? pgArrayToArray($quote['documents_required']) : [];
        
        echo json_encode($quote);
    } else {
        echo json_encode(['error' => 'Quote not found']);
    }
    
} catch(PDOException $e) {
    error_log("Database error in get_quote: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}