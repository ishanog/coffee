<?php
session_start();

// Security Check: Redirect non-admin users
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: signin.html");
    exit();
}

require_once "connection.php";

$message = "";
$error = "";

// Handle status updates
$allowed_statuses = ["Pending", "Confirmed", "Preparing", "Ready", "Completed", "Cancelled"];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = intval($_POST['order_id']);
    $new_status = $_POST['status'] ?? '';

    if (!in_array($new_status, $allowed_statuses, true)) {
        $error = "Invalid order status.";
    } else {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            $message = "Order #{$order_id} status updated!";
        } else {
            $error = "Failed to update order status.";
        }
        $stmt->close();
    }
}

// Handle single order deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = "Order #{$delete_id} deleted successfully!";
    } else {
        $error = "Failed to delete order.";
    }
    $stmt->close();
}

// Fetch all orders with optional user matching
$sql = "SELECT o.*, r.fullname, r.email 
        FROM orders o 
        LEFT JOIN register r ON o.user_id = r.id 
        ORDER BY o.created_at DESC";
$orders_result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - BeanFlow Admin</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="style.css?v=1.1">
</head>
<body>

    <nav class="navbar">
        <div class="logo">
            <a href="admin.php"><img src="images/logo.png" alt="Coffee Shop Logo">
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
            <h1>Customer Orders</h1>
        </div>

        <?php if ($message): ?>
            <p style="color: #2e7d32; font-weight: 600;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p style="color: #c62828; font-weight: 600;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Items Ordered</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                    <?php while ($row = $orders_result->fetch_assoc()): ?>
                        <?php $items = json_decode($row['items'], true) ?? []; ?>
                        <tr>
                            <td>#<?= htmlspecialchars($row['id']) ?></td>
                            <td>
                                <?= htmlspecialchars($row['fullname'] ?? 'Guest / Walk-in') ?><br>
                                <small style="color: #777;"><?= htmlspecialchars($row['email'] ?? '') ?></small>
                            </td>
                            <td>
                                <ul style="margin: 0; padding-left: 15px;">
                                    <?php foreach ($items as $item): ?>
                                        <li><?= htmlspecialchars($item['quantity']) ?>x <?= htmlspecialchars($item['title']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>$<?= number_format($row['total_amount'], 2) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="Pending" <?= $row['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="Confirmed" <?= $row['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="Preparing" <?= $row['status'] === 'Preparing' ? 'selected' : '' ?>>Preparing</option>
                                        <option value="Ready" <?= $row['status'] === 'Ready' ? 'selected' : '' ?>>Ready</option>
                                        <option value="Completed" <?= $row['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="Cancelled" <?= $row['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td>
                                <a href="orders.php?action=delete&id=<?= $row['id'] ?>" 
                                   class="btn-delete" 
                                   onclick="return confirm('Delete this order?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #88745C;">No orders placed yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
<?php $conn->close(); ?>