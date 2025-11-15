<?php


include 'config.php'; 
header('Content-Type: application/json');


if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: Database connection failed.']);
    exit;
}

if (empty($_GET['id'])) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}


$id = trim($_GET['id']);

$query = "
    SELECT patient_id, name AS full_name, age, gender, blood_type, contact, emergency_contact, status, address,
           last_visit, allergies, chronic_conditions, medical_history
    FROM patients
    WHERE patient_id = ?
    LIMIT 1
";

try {
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]); 
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($patient) {
        
        echo json_encode($patient);
    } else {
        
        echo json_encode(['error' => 'Patient not found']);
    }
} catch (PDOException $e) {
    
    http_response_code(500);
    error_log("Patient fetch error: " . $e->getMessage());
    echo json_encode(['error' => 'Database operation failed.']);
}
?>