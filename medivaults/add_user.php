<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();



include 'php/config.php';


if (!isset($pdo)) {
    http_response_code(500);
    die("Server initialization error: Database connection failed.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username  = trim($_POST['username'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $role    = trim($_POST['role'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $status   = trim($_POST['status'] ?? 'Active');
    $created_at = date('Y-m-d H:i:s');

    if (!$username || !$password || !$role || !$full_name || !$email) {
        echo "All fields are required.";
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    
    $sql = "INSERT INTO users (username, password, role, full_name, email, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    try {
        
        $stmt = $pdo->prepare($sql);

        
        $params = [
            $username, 
            $password_hash, 
            $role, 
            $full_name, 
            $email, 
            $status, 
            $created_at
        ];

        
        if ($stmt->execute($params)) {
            echo "User added successfully.";
        } else {
            
            $errorInfo = $stmt->errorInfo();
            error_log("User registration failed: " . $errorInfo[2]);
            echo "Error: Failed to add user to database.";
        }

    } catch (PDOException $e) {
        
        error_log("PDO Error: " . $e->getMessage());
        echo "Error: Database operation failed unexpectedly.";
    }

    
    
}
?>

<form method="post">
    <input name="username" placeholder="Username" required>
    <input name="password" type="password" placeholder="Password" required>
    <input name="role" placeholder="Role (Admin/Staff)" required>
    <input name="full_name" placeholder="Full Name" required>
    <input name="email" placeholder="Email" required>
    <button type="submit">Add User</button>
</form>