<?php



include 'config.php'; 
header('Content-Type: application/json');





if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error: Database connection failed."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    
    $patient_id = trim($_POST['patient_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $age = $_POST['age'] ?? null;
    $gender = $_POST['gender'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $address = $_POST['address'] ?? '';
    $blood_type = $_POST['blood_type'] ?? '';
    $emergency_contact = $_POST['emergency_contact'] ?? '';
    $medical_history = $_POST['medical_history'] ?? '';
    $allergies = $_POST['allergies'] ?? '';
    $chronic_conditions = $_POST['chronic_conditions'] ?? '';
    
    
    $face_image = $_POST['face_image'] ?? ''; 

    
    $face_image_path = null;
    $biometric_enrolled = 0;

    if (!empty($face_image)) {
        
        $base64_parts = explode(',', $face_image);
        $encoded_data = end($base64_parts);
        
        $face_data = base64_decode($encoded_data);
        
        
        $upload_dir = '../faces/'; 
        if (!is_dir($upload_dir)) {
             
            mkdir($upload_dir, 0777, true); 
        }

        $file_name = 'faces/' . uniqid('face_') . '.png';
        
        
        if (file_put_contents($upload_dir . basename($file_name), $face_data)) {
            
            $face_image_path = $file_name;
            $biometric_enrolled = 1;
        }
    }

    
    $sql = "INSERT INTO patients (patient_id, name, age, gender, contact, address, blood_type, emergency_contact, medical_history, allergies, chronic_conditions, face_image_path, biometric_enrolled, status, registration_date, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())";

    try {
        $stmt = $pdo->prepare($sql);
        
        $result = $stmt->execute([
            $patient_id, 
            $name, 
            $age, 
            $gender, 
            $contact, 
            $address, 
            $blood_type, 
            $emergency_contact, 
            $medical_history, 
            $allergies, 
            $chronic_conditions, 
            $face_image_path, 
            $biometric_enrolled
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Patient registered successfully.']);
        } else {
            
            $errorInfo = $stmt->errorInfo();
            error_log("Patient registration failed: " . $errorInfo[2]);
            echo json_encode(['success' => false, 'message' => 'Database Error: ' . $errorInfo[2]]);
        }

    } catch (PDOException $e) {
        
        error_log("PDO Exception during patient registration: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'A critical database error occurred.']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>