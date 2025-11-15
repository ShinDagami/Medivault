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
$quantity_change = (int)($_POST['quantity_change'] ?? 0);

if (!$item_code || $quantity_change === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT quantity, unit, reorder_level, expiry_date, item_name FROM inventory WHERE item_code = ?");
    $stmt->execute([$item_code]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['status' => 'error', 'message' => 'Item not found']);
        exit;
    }

    $old_quantity = $item['quantity'];
    $new_quantity = max(0, $old_quantity + $quantity_change);

    $update = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE item_code = ?");
    $update->execute([$new_quantity, $item_code]);

    $staff_name = ($_SESSION['role'] ?? 'Staff') . " (" . ($_SESSION['username'] ?? 'unknown') . ")";
    $action = $quantity_change > 0 ? 'Restock Item' : 'Deduct Stock';
    $details = sprintf(
        '%s adjusted stock for %s (Code: %s) from %d to %d (%+d units)',
        $staff_name,
        $item['item_name'],
        $item_code,
        $old_quantity,
        $new_quantity,
        $quantity_change
    );
    log_audit_action($pdo, $staff_name, $action, 'Inventory', $details);

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
    if ($item['expiry_date']) {
        $diff = (new DateTime())->diff(new DateTime($item['expiry_date']));
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
        'message' => 'Stock updated and logged',
        'item' => [
            'item_code' => $item_code,
            'quantity' => $new_quantity,
            'unit' => $item['unit'],
            'expiry_date' => $item['expiry_date'] ?? 'N/A',
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
