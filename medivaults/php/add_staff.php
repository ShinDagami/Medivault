<?php
session_start();

include 'config.php'; 

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');


if (!isset($pdo)) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Server initialization error: PDO connection not found.']));
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $department = ($role === 'Admin') ? '' : trim($_POST['department'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $access = trim($_POST['accessLevel'] ?? '');

    if (!$fullName || !$email || !$role || !$username || !$password || !$phone || !$access) {
        throw new Exception('All fields are required');
    }


    $q = $pdo->query("SELECT MAX(staff_id) AS max_id FROM staff");
    if (!$q) throw new Exception("Database query failed during ID generation.");
    
    $row = $q->fetch(PDO::FETCH_ASSOC); 
    $lastId = $row['max_id'] ?? null;

    if ($lastId) {
        $num = intval(substr($lastId, 4)) + 1; 
    } else {
        $num = 1;
    }

    $new_id = "STF-" . str_pad($num, 4, "0", STR_PAD_LEFT);

    
    $hash = password_hash($password, PASSWORD_DEFAULT);

    
    $status = "Active";
    $hire = date('Y-m-d');
    $shift = "Day";

    
    $sql = "INSERT INTO staff (staff_id, name, role, department, email, phone, status, hire_date, shift, access_level, username, password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        
        throw new Exception("Prepare failed: " . print_r($pdo->errorInfo(), true));
    }
    
    $params = [
        $new_id, 
        $fullName, 
        $role, 
        $department, 
        $email, 
        $phone, 
        $status, 
        $hire, 
        $shift, 
        $access, 
        $username, 
        $hash
    ];

    $ok = $stmt->execute($params);
    if (!$ok) {
        
        throw new Exception("Execute failed: " . print_r($stmt->errorInfo(), true));
    }

    echo json_encode(['success' => true, 'message' => 'Staff added successfully']);

} catch (Exception $e) {
    
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>