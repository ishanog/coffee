<?php
session_start();

// Security Check: Redirect non-admin users to signin page
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: signin.html");
    exit();
}

require_once "connection.php";

$message = "";
$error = "";

// Dynamic filename reference
$current_page = htmlspecialchars($_SERVER['PHP_SELF']);

// --- 1. HANDLE ITEM DELETE ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['item_name'])) {
    $delete_name = $_GET['item_name'];
    
    $stmt = $conn->prepare("DELETE FROM stock WHERE Item_Name = ?");
    $stmt->bind_param("s", $delete_name);
    
    if ($stmt->execute()) {
        $message = "Stock Item '{$delete_name}' deleted successfully!";
    } else {
        $error = "Failed to delete item.";
    }
    $stmt->close();
}

// --- 2. HANDLE ITEM ADDITION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    $item_name = trim($_POST['item_name']);
    $quantity  = intval($_POST['quantity']);
    $price     = intval($_POST['price']);

    if (!empty($item_name) && $quantity >= 0 && $price >= 0) {
        $stmt = $conn->prepare("INSERT INTO stock (Item_Name, Quantity, Price) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $item_name, $quantity, $price);
        
        if ($stmt->execute()) {
            $message = "New item '{$item_name}' added to inventory!";
        } else {
            $error = "Failed to add item. Item name might already exist.";
        }
        $stmt->close();
    } else {
        $error = "Please provide valid stock details.";
    }
}

// --- 3. HANDLE ITEM EDIT UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $original_name = trim($_POST['original_item_name']);
    $new_name      = trim($_POST['item_name']);
    $quantity      = intval($_POST['quantity']);
    $price         = intval($_POST['price']);

    if (!empty($new_name) && $quantity >= 0 && $price >= 0) {
        $stmt = $conn->prepare("UPDATE stock SET Item_Name = ?, Quantity = ?, Price = ? WHERE Item_Name = ?");
        $stmt->bind_param("siis", $new_name, $quantity, $price, $original_name);
        
        if ($stmt->execute()) {
            $message = "Item '{$original_name}' updated successfully!";
        } else {
            $error = "Failed to update stock details.";
        }
        $stmt->close();
    } else {
        $error = "All fields are required and numbers must be valid.";
    }
}

// --- 4. HANDLE PURGE ALL STOCK RECORDS ---
if (isset($_GET['action']) && $_GET['action'] === 'clear_all') {
    if ($conn->query("TRUNCATE TABLE stock")) {
        $message = "All stock records deleted!";
    } else {
        $error = "Failed to clear stock records: " . $conn->error;
    }
}

// --- 5. FETCH ITEM FOR EDIT FORM (IF REQUESTED) ---
$edit_stock = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['item_name'])) {
    $fetch_name = $_GET['item_name'];
    $stmt = $conn->prepare("SELECT Item_Name, Quantity, Price FROM stock WHERE Item_Name = ?");
    $stmt->bind_param("s", $fetch_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_stock = $result->fetch_assoc();
    $stmt->close();
}

// --- 6. FETCH ALL STOCK ITEMS ---
$sql = "SELECT * FROM stock ORDER BY Item_Name ASC";
$stock_result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management</title>
        <link rel="icon" type="image/png" href="images/logo.png">    
        <link rel="stylesheet" href="style.css?v=1.1">
</head>
<body>

    <nav class="navbar">
        <div class="logo">
            <a href="user_data.php"><img src="images/logo.png" alt="Coffee Shop Logo">
                <span class="logo-text">Admin Dashboard</span></a>
        </div>
        <ul class="nav-links">
            <li><a href="user_data.php">USERS</a></li>
            <li><a href="stock.php">STOCK</a></li>
            <li><a href="orders.php">ORDERS</a></li>
        </ul>
        <button class="cart-button">
            <img src="./images/logout.png" alt="Logout Icon"> 
            <a href="logout.php" class="logout-btn">Logout</a>
        </button>
    </nav>

    <div class="admin-container">
        <div class="admin-header">
            <h1>Inventory & Stock Management</h1>
        </div>

        <!-- Add New Stock Form -->
        <?php if (!$edit_stock): ?>
            <div class="edit-box">
                <h3>Add New Inventory Item</h3>
                <form method="POST" action="<?php echo $current_page; ?>">
                    <div class="edit-form-row">
                        <div class="edit-form-group">
                            <label>ITEM NAME</label>
                            <input type="text" name="item_name" placeholder="e.g. Arabica Beans" required>
                        </div>
                        <div class="edit-form-group">
                            <label>QUANTITY</label>
                            <input type="number" name="quantity" min="0" placeholder="0" required>
                        </div>
                        <div class="edit-form-group">
                            <label>PRICE (Rs)</label>
                            <input type="number" min="0" name="price" placeholder="0" required>
                        </div>
                        <button type="submit" name="add_stock" class="btn-save">Add Item</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Inline Edit Panel -->
        <?php if ($edit_stock): ?>
            <div class="edit-box">
                <h3>Editing Item: <?php echo htmlspecialchars($edit_stock['Item_Name']); ?></h3>
                <form method="POST" action="<?php echo $current_page; ?>">
                    <input type="hidden" name="original_item_name" value="<?php echo htmlspecialchars($edit_stock['Item_Name']); ?>">
                    <div class="edit-form-row">
                        <div class="edit-form-group">
                            <label>ITEM NAME</label>
                            <input type="text" name="item_name" value="<?php echo htmlspecialchars($edit_stock['Item_Name']); ?>" required>
                        </div>
                        <div class="edit-form-group">
                            <label>QUANTITY</label>
                            <input type="number" name="quantity" min="0" value="<?php echo htmlspecialchars($edit_stock['Quantity']); ?>" required>
                        </div>
                        <div class="edit-form-group">
                            <label>PRICE (Rs)</label>
                            <input type="number" min="0" name="price" value="<?php echo htmlspecialchars($edit_stock['Price']); ?>" required>
                        </div>
                        <button type="submit" name="update_stock" class="btn-save">Save Changes</button>
                        <a href="<?php echo $current_page; ?>" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Table displaying stock items -->
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($stock_result && $stock_result->num_rows > 0): ?>
                    <?php while ($row = $stock_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Item_Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Quantity']); ?></td>
                            <td>Rs.<?php echo number_format($row['Price']); ?></td>
                            <td>
                                <a href="<?php echo $current_page; ?>?action=edit&item_name=<?php echo urlencode($row['Item_Name']); ?>" class="btn-edit">Edit</a>
                                <a href="<?php echo $current_page; ?>?action=delete&item_name=<?php echo urlencode($row['Item_Name']); ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this stock item?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #88745C;">No stock records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="<?php echo $current_page; ?>?action=clear_all" 
           class="btn-clear" 
           onclick="return confirm('WARNING: This will permanently delete ALL stock records. Continue?');">
           Clear All Stock
        </a>
    </div>

    <!-- Reusable Toast Notification JS trigger -->
    <script>
    function showToast(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast-msg ${type}`;
        toast.textContent = message;

        container.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    <?php if (!empty($message)): ?>
        showToast("<?php echo addslashes($message); ?>", "success");
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        showToast("<?php echo addslashes($error); ?>", "error");
    <?php endif; ?>
    </script>
</body>
</html>
<?php $conn->close(); ?>