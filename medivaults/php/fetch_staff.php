<?php


include 'config.php'; 
header('Content-Type: application/json');


if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: Database connection failed.']);
    exit;
}

$sql = "SELECT staff_id, name, role, department, email, status FROM staff ORDER BY staff_id DESC";
$data = [];

try {
    
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(); 
    echo json_encode($data);
    
} catch (PDOException $e) {
    
    http_response_code(500);
    error_log("Staff list fetch error: " . $e->getMessage());
    echo json_encode(['error' => 'Database operation failed.']);
}



?>