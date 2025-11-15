<?php
session_start();
header('Content-Type: application/json');


include 'config.php'; 
include 'audit_util.php';


if (!isset($_SESSION['username']) || !isset($pdo)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Access denied or database connection failed.']);
    exit;
}


function get_inventory_status_class(array $item): string {
    if ($item['quantity'] == 0) return 'critical';
    if ($item['quantity'] <= $item['reorder_level']) return 'low-stock';
    if ($item['expiry_date']) {
        $today = new DateTime();
        $expiry_date = new DateTime($item['expiry_date']);
        $interval = $today->diff($expiry_date);
        if ($interval->days <= 90 && $interval->invert === 0) return 'expiring-soon';
        if ($interval->invert === 1) return 'expired';
    }
    return 'in-stock';
}

function get_inventory_status_text(array $item): string {
    if ($item['quantity'] == 0) return 'Critical (Out of Stock)';
    if ($item['quantity'] <= $item['reorder_level']) return 'Low Stock';
    if ($item['expiry_date']) {
        $today = new DateTime();
        $expiry_date = new DateTime($item['expiry_date']);
        $interval = $today->diff($expiry_date);
        if ($interval->days <= 90 && $interval->invert === 0) return 'Expiring Soon';
        if ($interval->invert === 1) return 'Expired';
    }
    return 'In Stock';
}


$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $data = $_POST ?: $input;

    switch ($action) {
        case 'add_item':
            throw new Exception("Add item should be handled by add_inventory.php.");
        case 'restock_item':
            throw new Exception("Restock item should be handled by restock_inventory.php.");
        case 'get_all_data':
            handle_get_all_data($pdo);
            break;
        case 'adjust_stock':
            handle_adjust_stock($pdo, $data);
            break;
        case 'update_item':
            handle_update_item($pdo, $data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
            break;
    }
} catch (PDOException $e) {
    error_log("Database error in inventory_api.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
} catch (Exception $e) {
    error_log("General error in inventory_api.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


function handle_adjust_stock(PDO $pdo, array $data) {
    $item_code = $data['item_code'] ?? null;
    $quantity_change = (int)($data['quantity_change'] ?? 0);

    if (!$item_code || $quantity_change === 0) throw new Exception("Missing item code or quantity change.");

    $stmt = $pdo->prepare("SELECT quantity, unit, reorder_level, expiry_date, item_name FROM inventory WHERE item_code = ?");
    $stmt->execute([$item_code]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) throw new Exception("Item not found.");

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

    
    $quantity_status_class = ($new_quantity == 0) ? 'critical' : (($new_quantity <= $item['reorder_level']) ? 'low-stock' : 'in-stock');
    $quantity_status_text = ($new_quantity == 0) ? 'Critical (Out of Stock)' : (($new_quantity <= $item['reorder_level']) ? 'Low Stock' : 'In Stock');

    $expiry_status_class = '';
    $expiry_status_text = '';
    if ($item['expiry_date']) {
        $diff = (new DateTime())->diff(new DateTime($item['expiry_date']));
        if ($diff->invert === 0 && $diff->days <= 90) { $expiry_status_class = 'expiring-soon'; $expiry_status_text = 'Expiring Soon'; }
        elseif ($diff->invert === 1) { $expiry_status_class = 'expired'; $expiry_status_text = 'Expired'; }
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
}

function handle_update_item(PDO $pdo, array $data) {
    $item_code = $data['item_code'] ?? null;
    $item_name = $data['item_name'] ?? null;
    $reorder_level = $data['reorder_level'] ?? null;
    if (!$item_code || !$item_name || !isset($reorder_level)) throw new Exception("Missing required fields for update.");

    $stmt = $pdo->prepare("UPDATE inventory SET item_name = :item_name, reorder_level = :reorder_level WHERE item_code = :item_code");
    $stmt->execute([':item_name' => $item_name, ':reorder_level' => $reorder_level, ':item_code' => $item_code]);

    if ($stmt->rowCount() > 0) {
        $staff_name = ($_SESSION['role'] ?? 'Staff') . " (" . ($_SESSION['username'] ?? 'unknown') . ")";
        $details = "Updated item details for Item Code: {$item_code}";
        log_audit_action($pdo, $staff_name, 'Update Item', 'Inventory', $details);

        echo json_encode(['success' => true, 'message' => 'Item updated successfully!', 'status' => 'success']);
    } else {
        throw new Exception("Failed to update item details. Item Code: {$item_code}");
    }
}

function handle_get_all_data(PDO $pdo) {
    $sql = "SELECT id, item_code, item_name, category, quantity, unit, reorder_level, supplier, last_restocked, expiry_date FROM inventory ORDER BY item_name ASC";
    $stmt = $pdo->query($sql);
    $inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $counts = [
        'low_stock_count' => $pdo->query("SELECT COUNT(*) FROM inventory WHERE quantity <= reorder_level AND quantity > 0")->fetchColumn(),
        'critical_stock_count' => $pdo->query("SELECT COUNT(*) FROM inventory WHERE quantity = 0")->fetchColumn(),
        'expiring_soon_count' => $pdo->query("SELECT COUNT(*) FROM inventory WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND expiry_date >= CURDATE()")->fetchColumn()
    ];

    $table_html = '';
    $items_for_dropdown = [];

    if (empty($inventory_items)) {
        $table_html = '<tr><td colspan="9" class="empty-state-cell">No inventory records found. Click \'Add New Item\' to begin.</td></tr>';
    } else {
        foreach ($inventory_items as $item) {
            $status_class = get_inventory_status_class($item);
            $status_text = get_inventory_status_text($item);
            $expiry_tag = '';
            if (!empty($item['expiry_date'])) {
                $today = new DateTime();
                $expiry_date_obj = new DateTime($item['expiry_date']);
                $interval = $today->diff($expiry_date_obj);
                if ($interval->invert === 1) $expiry_tag = '<span class="status-tag expired">EXPIRED</span>';
                elseif ($interval->days <= 90 && $interval->invert === 0) $expiry_tag = '<span class="status-tag expiring-soon">EXPIRING SOON</span>';
            }

            $item_id_or_code = htmlspecialchars($item['item_code'] ?? $item['id']);
            $table_html .= "<tr data-item-id=\"{$item_id_or_code}\">";
            $table_html .= "<td>" . htmlspecialchars($item['item_code'] ?? 'N/A') . "</td>";
            $table_html .= "<td>" . htmlspecialchars($item['item_name']) . "</td>";
            $table_html .= "<td>" . htmlspecialchars($item['category']) . "</td>";
            $table_html .= "<td>" . htmlspecialchars($item['quantity']) . " " . htmlspecialchars($item['unit']) . "</td>";
            $table_html .= "<td>" . htmlspecialchars($item['reorder_level']) . "</td>";
            $table_html .= "<td>" . htmlspecialchars($item['supplier'] ?? 'N/A') . "</td>";
            $table_html .= "<td>" . htmlspecialchars($item['expiry_date'] ?? 'N/A') . " {$expiry_tag}</td>";
            $table_html .= "<td><span class=\"status-tag {$status_class}\">" . htmlspecialchars($status_text) . "</span></td>";
            $table_html .= "<td>";
            $table_html .= "<button class=\"btn btn-sm btn-action btn-edit\" data-id=\"{$item_id_or_code}\" title=\"Edit Item\"><i class=\"fas fa-edit\"></i></button>";
            $table_html .= "<button class=\"btn btn-sm btn-action btn-dec btn-danger\" data-id=\"{$item_id_or_code}\" data-step=\"-1\">-1</button>";
            $table_html .= "<button class=\"btn btn-sm btn-action btn-dec btn-danger\" data-id=\"{$item_id_or_code}\" data-step=\"-5\">-5</button>";
            $table_html .= "<button class=\"btn btn-sm btn-action btn-dec btn-danger\" data-id=\"{$item_id_or_code}\" data-step=\"-10\">-10</button>";
            $table_html .= "</td></tr>";

            $items_for_dropdown[] = [
                'item_code' => $item['item_code'],
                'item_name' => $item['item_name'],
                'category' => $item['category']
            ];
        }
    }

    echo json_encode(['success' => true, 'counts' => $counts, 'table_html' => $table_html, 'items_for_dropdown' => $items_for_dropdown]);
}
?>
