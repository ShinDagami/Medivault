<?php


include '../config.php'; 
header('Content-Type: application/json');

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: Database connection failed.']);
    exit;
}

$id = $_GET['id'] ?? 0;


$query = "SELECT staff_id, name, role, department, email, status FROM staff WHERE staff_id=? LIMIT 1";

try {
    
    $stmt = $pdo->prepare($query);   
    $stmt->execute([$id]); 
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        
        echo json_encode(null); 
    } else {
        
        echo json_encode($row);
    }
    
} catch (PDOException $e) {
    
    http_response_code(500);
    error_log("Staff detail fetch error: " . $e->getMessage());
    echo json_encode(['error' => 'Database operation failed.']);
}



?>