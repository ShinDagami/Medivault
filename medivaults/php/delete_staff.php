<?php

include 'config.php'; 
header('Content-Type: application/json');


if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: Database connection failed.']);
    exit;
}

if (!isset($_POST['delete_id'])) {
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
    exit;
}

$id = $_POST['delete_id'];
$ok = false;

try { 
    $stmt = $pdo->prepare("DELETE FROM staff WHERE staff_id = ?"); 
    $ok = $stmt->execute([$id]); 

} catch (PDOException $e) {
    
    error_log("Staff delete failed: " . $e->getMessage());
    $ok = false;
}

echo json_encode(['success' => $ok, 'message' => $ok ? 'Staff member deleted successfully.' : 'Delete failed']);
?>