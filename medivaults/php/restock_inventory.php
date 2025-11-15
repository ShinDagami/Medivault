<?php
session_start();
include 'config.php';
include 'audit_util.php';
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$item_code = $_POST['item_code'] ?? null;
$quantity_added = (int)($_POST['quantity'] ?? 0);
$expiry_date = $_POST['expiry_date'] ?: null;

if (!$item_code || $quantity_added <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT quantity, unit, reorder_level, expiry_date FROM inventory WHERE item_code = ?");
    $stmt->execute([$item_code]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['status' => 'error', 'message' => 'Item not found']);
        exit;
    }

    $new_quantity = $item['quantity'] + $quantity_added;
    $update = $pdo->prepare("UPDATE inventory SET quantity = ?, last_restocked = ?, expiry_date = ? WHERE item_code = ?");
    $update->execute([$new_quantity, date('Y-m-d'), $expiry_date ?: $item['expiry_date'], $item_code]);

    $staff_name = ($_SESSION['role'] ?? 'Staff') . " (" . ($_SESSION['username'] ?? 'unknown') . ")";
    log_audit_action($pdo, $staff_name, 'Restock Item', 'Inventory', "Restocked $quantity_added units for $item_code");

    if ($new_quantity == 0) {
        $quantity_status_class = 'critical';
        $quantity_status_text = 'Critical (Out of Stock)';
    } elseif ($new_quantity <= $item['reorder_level']) {
        $quantity_status_class = 'low-stock';
        $quantity_status_text = 'Low Stock';
    } else {
        $quantity_status_class = 'in-stock';
        $quantity_status_text = 'In Stock';
    }

    $expiry_status_class = '';
    $expiry_status_text = '';
    $expiry_check = $expiry_date ?: $item['expiry_date'];
    if ($expiry_check) {
        $diff = (new DateTime())->diff(new DateTime($expiry_check));
        if ($diff->invert === 0 && $diff->days <= 90) {
            $expiry_status_class = 'expiring-soon';
            $expiry_status_text = 'Expiring Soon';
        } elseif ($diff->invert === 1) {
            $expiry_status_class = 'expired';
            $expiry_status_text = 'Expired';
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Item restocked successfully',
        'item' => [
            'item_code' => $item_code,
            'quantity' => $new_quantity,
            'unit' => $item['unit'],
            'expiry_date' => $expiry_check,
            'quantity_status_class' => $quantity_status_class,
            'quantity_status_text' => $quantity_status_text,
            'expiry_status_class' => $expiry_status_class,
            'expiry_status_text' => $expiry_status_text
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
