<?php
session_start();
require_once('../config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    $conn = getConnection(); // Get connection
    $stmt = $conn->prepare("DELETE FROM service_quotes WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Quote deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete quote']);
    }
    
} catch(PDOException $e) {
    error_log("Database error in delete_quote: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}