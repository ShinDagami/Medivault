<?php


require_once __DIR__ . '/php/config.php'; 

if ($conn->connect_error) {
    
    die("Connection Failed: " . $conn->connect_error);
} else {
    echo "Database Connection Successful! Try registration now.";
}
