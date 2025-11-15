<?php


include 'config.php'; 
header('Content-Type: application/json');


if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: Database connection failed."]);
    exit;
}


$id = $_POST['staff_id'] ?? null;
$name = $_POST['name'] ?? null;
$role = $_POST['role'] ?? null;
$department = $_POST['department'] ?? null;
$email = $_POST['email'] ?? null;
$status = $_POST['status'] ?? null;

if (!$id) {
    echo json_encode(["status" => "error", "message" => "Missing Staff ID."]);
    exit;
}


$sql = "UPDATE staff SET name=?, role=?, department=?, email=?, status=? WHERE staff_id=?";

try {
    
    $stmt = $pdo->prepare($sql);
    
    $params = [
        $name, 
        $role, 
        $department, 
        $email, 
        $status, 
        $id 
    ];

    
    if ($stmt->execute($params)) {
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "success", "message" => "Staff member updated successfully."]);
        } else {
            
            echo json_encode(["status" => "success", "message" => "Staff record found, but no changes were applied."]);
        }
    } else {
        
        echo json_encode(["status" => "error", "message" => "Update failed: " . print_r($stmt->errorInfo(), true)]);
    }

} catch (PDOException $e) {
    
    error_log("Staff update failed: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Database operation failed. Check logs for details."]);
}



?>