<?php


include 'config.php'; 
header('Content-Type: application/json; charset=utf-8');


if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: Database connection failed.'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$query = "SELECT patient_id, face_encoding FROM patients WHERE face_encoding IS NOT NULL AND face_encoding != ''";
$faces = [];

try {
    
    $stmt = $pdo->query($query); 
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { 
        
        $isJson = json_decode($row['face_encoding']);
        if (json_last_error() === JSON_ERROR_NONE) {
            $faces[] = [
                'patient_id' => $row['patient_id'],
                'face_encoding' => $row['face_encoding']
            ];
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Face data fetch failed: " . $e->getMessage());
    
    echo json_encode([], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); 
    exit;
}


if (empty($faces) && $stmt->rowCount() > 0) {
    
    echo json_encode([], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} else {
    
    echo json_encode($faces, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
?>