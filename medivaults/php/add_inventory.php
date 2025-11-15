<?php
session_start();
include 'config.php';
include 'audit_util.php';
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$name = $_POST['item_name'] ?? '';
$category = $_POST['category'] ?? '';
$quantity = (int)($_POST['quantity'] ?? 0);
$unit = $_POST['unit'] ?? '';
$reorder = (int)($_POST['reorder_level'] ?? 0);
$supplier = $_POST['supplier'] ?? '';
$expiry = $_POST['expiry_date'] ?: null;

if (!$name || !$category || !$unit) {
    echo json_encode(["status" => "error", "message" => "Required fields missing"]);
    exit;
}

try {
    $prefix = strtoupper(substr($category, 0, 1));
    $stmt = $pdo->prepare("SELECT item_code FROM inventory WHERE category = ? ORDER BY item_code DESC LIMIT 1");
    $stmt->execute([$category]);
    $lastCode = $stmt->fetchColumn();

    $num = $lastCode ? (int)substr($lastCode, 1) + 1 : 1;
    $item_code = $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare("INSERT INTO inventory (item_code, item_name, category, quantity, unit, reorder_level, supplier, expiry_date, last_restocked) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$item_code, $name, $category, $quantity, $unit, $reorder, $supplier, $expiry, date('Y-m-d')]);

    $staff_name = ($_SESSION['role'] ?? 'Staff') . " (" . ($_SESSION['username'] ?? 'unknown') . ")";
    log_audit_action($pdo, $staff_name, 'Add Item', 'Inventory', "Added item $name ($item_code)");

    if ($quantity == 0) {
        $quantity_status_class = 'critical';
        $quantity_status_text = 'Critical (Out of Stock)';
    } elseif ($quantity <= $reorder) {
        $quantity_status_class = 'low-stock';
        $quantity_status_text = 'Low Stock';
    } else {
        $quantity_status_class = 'in-stock';
        $quantity_status_text = 'In Stock';
    }

    $expiry_status_class = '';
    $expiry_status_text = '';
    if ($expiry) {
        $diff = (new DateTime())->diff(new DateTime($expiry));
        if ($diff->invert === 0 && $diff->days <= 90) {
            $expiry_status_class = 'expiring-soon';
            $expiry_status_text = 'Expiring Soon';
        } elseif ($diff->invert === 1) {
            $expiry_status_class = 'expired';
            $expiry_status_text = 'Expired';
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Item added successfully",
        "item" => [
            "item_code" => $item_code,
            "item_name" => $name,
            "category" => $category,
            "quantity" => $quantity,
            "unit" => $unit,
            "reorder_level" => $reorder,
            "supplier" => $supplier,
            "expiry_date" => $expiry,
            "quantity_status_class" => $quantity_status_class,
            "quantity_status_text" => $quantity_status_text,
            "expiry_status_class" => $expiry_status_class,
            "expiry_status_text" => $expiry_status_text
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
