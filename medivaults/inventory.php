<?php
session_start();
include 'php/config.php'; 

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$avatarInitials = strtoupper(substr($_SESSION['username'], 0, 2));
$currentPage = 'inventory';

$inventory_items = [];
$low_stock_count = 0;
$critical_stock_count = 0;
$expiring_soon_count = 0;

if (isset($pdo)) {
    try {
        $sql = "SELECT id, item_code, item_name, category, quantity, unit, reorder_level, supplier, last_restocked, expiry_date, status 
                FROM inventory ORDER BY item_name ASC";
        $stmt = $pdo->query($sql);
        $inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $low_stock_count = $pdo->query("SELECT COUNT(*) FROM inventory WHERE quantity <= reorder_level AND quantity > 0")->fetchColumn();
        $critical_stock_count = $pdo->query("SELECT COUNT(*) FROM inventory WHERE quantity = 0")->fetchColumn();
        $expiring_soon_count = $pdo->query("SELECT COUNT(*) FROM inventory WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND expiry_date >= CURDATE()")->fetchColumn();

    } catch (PDOException $e) {
        error_log("Inventory fetch failed: " . $e->getMessage());
    }
}

function get_inventory_status_class(array $item): string {
    if ($item['quantity'] == 0) return 'critical';
    if ($item['quantity'] <= $item['reorder_level']) return 'low-stock';
    if ($item['expiry_date']) {
        $expiry = new DateTime($item['expiry_date']);
        $diff = (new DateTime())->diff($expiry);
        if ($diff->invert === 0 && $diff->days <= 90) return 'expiring-soon';
        if ($diff->invert === 1) return 'expired';
    }
    return 'in-stock';
}

function get_inventory_status_text(array $item): string {
    if ($item['quantity'] == 0) return 'Critical (Out of Stock)';
    if ($item['quantity'] <= $item['reorder_level']) return 'Low Stock';
    if ($item['expiry_date']) {
        $expiry = new DateTime($item['expiry_date']);
        $diff = (new DateTime())->diff($expiry);
        if ($diff->invert === 0 && $diff->days <= 90) return 'Expiring Soon';
        if ($diff->invert === 1) return 'Expired';
    }
    return 'In Stock';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MediVault - Inventory</title>
<link rel="stylesheet" href="css/invstyle.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>
<div class="app">
    <?php include 'sidebar.php'; ?>
    <main class="main">
        <div class="topbar">
            <div class="search-bar"></div>
            <div class="user-actions">
                <div class="notification-bell"><i class="fas fa-bell"></i></div>
                <div class="user-menu-container" id="userMenuContainer">
                    <div class="user-avatar-wrapper" role="button" tabindex="0">
                        <div class="avatar" id="userAvatar"><?= $avatarInitials ?></div>
                    </div>
                    <div class="dropdown-menu" id="userDropdownMenu">
                        <div class="account-info"><?= htmlspecialchars($_SESSION['username']) ?></div>
                        <a href="settings.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
                        <a href="logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-title">
            <div class="title-group">
                <h2>Inventory Management</h2>
                <p>Manage medicine and supplies stock levels</p>
            </div>
            <div class="actions">
                <button class="btn btn-secondary" id="restock-item-btn"><i class="fas fa-box-open"></i> Restock Item</button>
                <button class="btn btn-primary" id="add-new-item-btn"><i class="fas fa-plus"></i> Add New Item</button>
            </div>
        </div>

        <div class="grid dashboard-grid inventory-grid-layout">
            <div class="inventory-summary-cards full-width-panel">
                <div class="summary-card low-stock-card card">
                    <h3>Low Stock Items</h3>
                    <div class="count" id="low-stock-count"><?= htmlspecialchars($low_stock_count) ?></div>
                    <p class="muted">Requires restocking</p>
                </div>
                <div class="summary-card critical-stock-card card">
                    <h3>Critical Stock</h3>
                    <div class="count" id="critical-stock-count"><?= htmlspecialchars($critical_stock_count) ?></div>
                    <p class="muted">Immediate action needed</p>
                </div>
                <div class="summary-card expiring-soon-card card">
                    <h3>Expiring Soon</h3>
                    <div class="count" id="expiring-soon-count"><?= htmlspecialchars($expiring_soon_count) ?></div>
                    <p class="muted">Within 3 months</p>
                </div>
            </div>

            <div class="panel inventory-list-container full-width-panel">
                <h3>Inventory Records</h3>
                <p class="muted">View and manage all stock items</p>
                <div class="patient-controls">
                    <div class="search-input-wrapper"><i class="fas fa-search"></i><input id="search-inventory-input" placeholder="Search by item name, category, or supplier..."></div>
                    <div class="category-dropdown">
                        <div class="dropdown-toggle" id="category-filter-toggle"><span id="category-filter-text">All Categories</span><i class="fas fa-chevron-down"></i></div>
                        <div class="dropdown-menu1" id="category-dropdown-menu">
                            <div class="dropdown-item active">All Categories</div>
                            <div class="dropdown-item">Medicine</div>
                            <div class="dropdown-item">Medical Supplies</div>
                        </div>
                    </div>
                </div>

                <table id="inventory-table" class="patient-table data-table">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Quantity (Unit)</th>
                            <th>Reorder Level</th>
                            <th>Supplier</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventory_items)): ?>
                        <tr><td colspan="9" class="empty-state-cell">No inventory records found. Click 'Add New Item' to begin.</td></tr>
                        <?php else: foreach($inventory_items as $item):
                            $status_class = get_inventory_status_class($item);
                            $status_text = get_inventory_status_text($item);
                        ?>
                        <tr data-item-id="<?= htmlspecialchars($item['item_code']) ?>">
    <td><?= htmlspecialchars($item['item_code']) ?></td>
    <td><?= htmlspecialchars($item['item_name']) ?></td>
    <td><?= htmlspecialchars($item['category']) ?></td>
    <td><?= htmlspecialchars($item['quantity']) ?> <?= htmlspecialchars($item['unit']) ?></td>
    <td><?= htmlspecialchars($item['reorder_level']) ?></td>
    <td><?= htmlspecialchars($item['supplier'] ?? 'N/A') ?></td>
    <td>
        <?= htmlspecialchars($item['expiry_date'] ?? 'N/A') ?>
        <?php
            $expiryClass = '';
            $expiryText = '';
            if ($item['expiry_date']) {
                $expiry = new DateTime($item['expiry_date']);
                $diff = (new DateTime())->diff($expiry);
                if ($diff->invert === 0 && $diff->days <= 90) {
                    $expiryClass = 'expiring-soon';
                    $expiryText = 'Expiring Soon';
                } elseif ($diff->invert === 1) {
                    $expiryClass = 'expired';
                    $expiryText = 'Expired';
                }
            }
            if ($expiryText):
        ?>
            <span class="status-tag <?= $expiryClass ?>" style="margin-left:8px;"><?= $expiryText ?></span>
        <?php endif; ?>
    </td>
    <td>
        <span class="status-tag <?= $status_class ?>"><?= htmlspecialchars($status_text) ?></span>
    </td>
    <td class="actions-cell">
        <button class="btn btn-sm btn-action btn-edit" data-id="<?= $item['item_code'] ?>"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-dec" data-id="<?= $item['item_code'] ?>" data-step="-1">-1</button>
        <button class="btn btn-sm btn-dec" data-id="<?= $item['item_code'] ?>" data-step="-5">-5</button>
        <button class="btn btn-sm btn-dec" data-id="<?= $item['item_code'] ?>" data-step="-10">-10</button>
  
    </td>
</tr>

                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modals -->

<div id="add-new-item-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Add New Inventory Item</h3><span class="close-btn">&times;</span></div>
        <form id="add-new-item-form">
            <div class="modal-body">
                <div class="form-group"><label for="new_item_name">Item Name</label><input type="text" id="new_item_name" name="item_name" required></div>
                <div class="form-group"><label for="new_category">Category</label>
                    <select id="new_category" name="category" required>
                        <option value="">Select category</option>
                        <option value="Medicine">Medicine</option>
                        <option value="Supplies">Medical Supplies</option>
                    </select>
                </div>
                <div class="form-group-inline">
                    <div class="form-group"><label for="new_initial_quantity">Initial Quantity</label><input type="number" id="new_initial_quantity" name="quantity" value="0" min="0" required></div>
                    <div class="form-group"><label for="new_unit">Unit</label><input type="text" id="new_unit" name="unit" placeholder="e.g. Tablets, Boxes" required></div>
                </div>
                <div class="form-group"><label for="new_reorder_level">Reorder Level</label><input type="number" id="new_reorder_level" name="reorder_level" value="0" min="0" required></div>
                <div class="form-group"><label for="new_supplier">Supplier</label><input type="text" id="new_supplier" name="supplier"></div>
                <div class="form-group"><label for="new_expiry_date">Expiry Date</label><input type="date" id="new_expiry_date" name="expiry_date"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary cancel-modal-btn">Cancel</button><button type="submit" class="btn btn-primary">Add Item</button></div>
        </form>
    </div>
</div>

<div id="restock-item-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Restock Inventory Item</h3><span class="close-btn">&times;</span></div>
        <form id="restock-item-form">
            <div class="modal-body">
                <div class="form-group"><label for="restock_item_select">Item Name</label>
                    <select id="restock_item_select" name="item_code" required>
                        <option value="">Select item to restock</option>
                        <?php foreach($inventory_items as $item): ?>
                            <option value="<?= $item['item_code'] ?>"><?= htmlspecialchars($item['item_name']) ?> (<?= htmlspecialchars($item['category']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group-inline">
                    <div class="form-group"><label for="restock_quantity">Quantity to Add</label><input type="number" id="restock_quantity" name="quantity" min="1" required></div>
                    <div class="form-group"><label for="restock_last_restocked">Restock Date</label><input type="date" id="restock_last_restocked" name="last_restocked" value="<?= date('Y-m-d') ?>" required></div>
                </div>
                <div class="form-group"><label for="restock_expiry_date">New Expiry Date</label><input type="date" id="restock_expiry_date" name="expiry_date"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary cancel-modal-btn">Cancel</button><button type="submit" class="btn btn-primary">Update Stock</button></div>
        </form>
    </div>
</div>

<div id="edit-item-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Edit Item</h3><span class="close-btn">&times;</span></div>
        <form id="edit-item-form">
            <input type="hidden" id="edit_item_code" name="item_code">
            <div class="modal-body">
                <div class="form-group"><label for="edit_item_name">Item Name</label><input type="text" id="edit_item_name" name="item_name" required></div>
                <div class="form-group"><label for="edit_reorder_level">Reorder Level</label><input type="number" id="edit_reorder_level" name="reorder_level" min="0" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary cancel-modal-btn">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
        </form>
    </div>
</div>

<script src="js/main.js"></script>
<script src="js/inventory.js"></script>
</body>
</html>
