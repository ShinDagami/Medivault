<?php


include 'config.php'; 
header('Content-Type: application/json');


if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: Database connection failed.']);
    exit;
}

$id = $_GET['id'] ?? 0;

$query = "SELECT * FROM staff WHERE staff_id=? LIMIT 1";

try {
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]); 
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
        echo json_encode(null); 
    } else {
        echo json_encode($result);
    }
    
} catch (PDOException $e) {
    
    http_response_code(500);
    error_log("Staff detail fetch error: " . $e->getMessage());
    echo json_encode(['error' => 'Database operation failed.']);
}



?>