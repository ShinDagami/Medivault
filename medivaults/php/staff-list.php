<?php

session_start();
include 'config.php'; 


$accessLevel = $_SESSION['access_level'] ?? '';


if (!isset($pdo)) {
    
    echo "<tr><td colspan='7' class='empty-state-cell error-cell'>Database connection error.</td></tr>";
    exit;
}

try {
    
    $sql = "SELECT id, staff_id, name, role, department, email, status FROM staff ORDER BY id DESC";
    $stmt = $pdo->query($sql);
    
    
    $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($staff_members) == 0) {
        echo "<tr><td colspan='7' class='empty-state-cell'>No staff members found.</td></tr>";
        exit;
    }
    
    foreach ($staff_members as $row) {
        
        echo "<tr>
                <td>{$row['staff_id']}</td>
                <td>{$row['name']}</td>
                <td>{$row['role']}</td>
                <td>{$row['department']}</td>
                <td>{$row['email']}</td>
                <td>{$row['status']}</td>
                <td>";
    
        
        if ($accessLevel !== 'Limited - view only') {
            
            echo "
            <button class='btn-view' data-id='{$row['id']}'><i class='fas fa-eye'></i></button>
            <button class='btn-edit' data-id='{$row['id']}'><i class='fas fa-edit'></i></button>
            <button class='btn-delete' data-id='{$row['id']}'><i class='fas fa-trash'></i></button>";
        } else {
            echo "<span style='color: gray;'>View only</span>";
        }
    
        echo "</td></tr>";
    }

} catch (PDOException $e) {
    
    error_log("Staff list HTML generation failed: " . $e->getMessage());
    echo "<tr><td colspan='7' class='empty-state-cell error-cell'>Error loading staff data.</td></tr>";
}


?>