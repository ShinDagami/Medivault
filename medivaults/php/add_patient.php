<?php



include 'config.php'; 
session_start();

if (!isset($_SESSION['username'])) {
  header("Location: ../index.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    
    if (!isset($pdo)) {
        error_log("FATAL: PDO connection not available in registration handler.");
        header("Location: ../patient.php?error=db_connection");
        exit;
    }

    
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $blood_type = $_POST['blood_type'];
    $emergency_contact = $_POST['emergency_contact'];
    $medical_history = $_POST['medical_history'];
    $face_image = $_POST['face_image'];
    
    
    $imagePath = null;
    if (!empty($face_image)) {
        $data = explode(',', $face_image);
        
        if (count($data) > 1) { 
            $imgData = base64_decode($data[1]);
            
            $imagePath = '../uploads/faces/' . time() . '.png';
            if (!file_exists('../uploads/faces')) {
                mkdir('../uploads/faces', 0777, true);
            }
            file_put_contents($imagePath, $imgData);
        }
    }
    
    
    $sql = "INSERT INTO patients 
            (name, age, gender, contact, address, blood_type, emergency_contact, medical_history, face_image_path, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', NOW())";
    
    try {
        
        $stmt = $pdo->prepare($sql);

        
        $params = [
            $name, 
            $age, 
            $gender, 
            $contact, 
            $address, 
            $blood_type, 
            $emergency_contact, 
            $medical_history, 
            $imagePath
        ];
        
        $stmt->execute($params);

        
        header("Location: ../patient.php?success=registered");
        exit;

    } catch (PDOException $e) {
        
        error_log("Patient registration failed: " . $e->getMessage());
        header("Location: ../patient.php?error=db_insert");
        exit;
    }
}
?>