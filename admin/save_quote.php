<?php
session_start();
require_once('../config.php');
require_once('includes/functions.php');

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Validate required fields
if (empty($data['service_id'])) {
    error_log("saveQuote error: service_id is missing. Data: " . print_r($data, true));
    echo json_encode(['success' => false, 'message' => 'Service ID is missing. Please select a service.']);
    exit;
}

try {
    $conn = getConnection(); // Get connection
    
    // Check if this is an update or insert
    if (isset($data['id']) && !empty($data['id'])) {
        // Update existing quote
        $result = saveQuote($conn, $data, $_SESSION['admin_id']);
        if ($result) {
            echo json_encode(['success' => true, 'id' => $data['id'], 'message' => 'Quote updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update quote']);
        }
    } else {
        // Insert new quote
        $newId = saveQuote($conn, $data, $_SESSION['admin_id']);
        if ($newId) {
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Quote created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create quote']);
        }
    }
    
} catch(Exception $e) {
    error_log("Error in save_quote: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}