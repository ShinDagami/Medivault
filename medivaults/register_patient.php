<?php
session_start();

include 'config.php'; 
header('Content-Type: application/json');


if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}


if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Server error: Database connection failed."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    
    $name = $_POST['name'] ?? '';
    $age = $_POST['age'] ?? null; 
    $gender = $_POST['gender'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $blood_type = $_POST['blood_type'] ?? '';
    $emergency_contact = $_POST['emergency_contact'] ?? '';
    $address = $_POST['address'] ?? '';
    $allergies = $_POST['allergies'] ?? '';
    $chronic_conditions = $_POST['chronic_conditions'] ?? '';
    $medical_history = $_POST['medical_history'] ?? '';
    $face_image = $_POST['face_image'] ?? '';
    $status = 'Active'; 
    $registration_date = date('Y-m-d'); 
    
    
    $face_path = null;
    if ($face_image) {
        
        $data = explode(',', $face_image);
        $decoded = base64_decode($data[1]);
        $filename = 'uploads/faces/' . time() . '_' . rand(1000, 9999) . '.png';
        
        
        
        $target_dir = dirname(__DIR__) . '/' . 'uploads/faces';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $full_file_path = $target_dir . '/' . basename($filename);
        if (file_put_contents($full_file_path, $decoded) !== false) {
             
             $face_path = $filename; 
        }
    }

    
    $sql = "INSERT INTO patients (name, age, gender, contact, blood_type, emergency_contact, address, allergies, chronic_conditions, medical_history, face_image_path, status, registration_date, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    try {
        $stmt = $pdo->prepare($sql);
        
        
        $params = [
            $name, 
            $age, 
            $gender, 
            $contact, 
            $blood_type, 
            $emergency_contact, 
            $address, 
            $allergies, 
            $chronic_conditions, 
            $medical_history, 
            $face_path, 
            $status,
            $registration_date
        ];

        if ($stmt->execute($params)) {
            
            $patient_id = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Patient registered successfully.',
                'patient' => [
                    'id' => $patient_id,
                    'name' => $name,
                    'age' => $age,
                    'gender' => $gender,
                    'contact' => $contact,
                    'status' => $status,
                    'face_image' => $face_path ? $face_path : null
                ]
            ]);
        } else {
            
            $errorInfo = $stmt->errorInfo();
            error_log("Patient registration failed: " . $errorInfo[2]);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $errorInfo[2]]);
        }

    } catch (PDOException $e) {
        
        error_log("PDO Exception during patient registration: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'A critical database error occurred.']);
    }

} else {
    http_response_code(405); 
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>