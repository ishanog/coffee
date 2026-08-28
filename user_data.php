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

// Get current filename dynamically (e.g., user_data.php or admin.php)
$current_page = htmlspecialchars($_SERVER['PHP_SELF']);

// --- 1. HANDLE USER DELETE ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("DELETE FROM register WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $message = "User ID #{$delete_id} deleted successfully!";
    } else {
        $error = "Failed to delete user.";
    }
    $stmt->close();
}

// --- 2. HANDLE USER EDIT UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $edit_id   = intval($_POST['user_id']);
    $fullname  = trim($_POST['fullname']);
    $email     = trim($_POST['email']);

    if (!empty($fullname) && !empty($email)) {
        $stmt = $conn->prepare("UPDATE register SET fullname = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $fullname, $email, $edit_id);
        
        if ($stmt->execute()) {
            $message = "User ID #{$edit_id} updated successfully!";
        } else {
            $error = "Failed to update user details.";
        }
        $stmt->close();
    } else {
        $error = "All fields are required.";
    }
}

// --- 3. HANDLE PURGE & RESET ALL USERS ---
if (isset($_GET['action']) && $_GET['action'] === 'clear_all') {
    if ($conn->query("TRUNCATE TABLE register")) {
        $message = "All user records deleted and ID counter reset to 1!";
    } else {
        $error = "Failed to clear records: " . $conn->error;
    }
}

// --- 4. FETCH USER FOR EDIT MODAL/FORM (IF REQUESTED) ---
$edit_user = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT id, fullname, email FROM register WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_user = $result->fetch_assoc();
    $stmt->close();
}

// --- 5. FETCH ALL USERS (Executes AFTER all modifications) ---
$sql = "SELECT * FROM register ORDER BY id ASC";
$users_result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
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
        </ul>
        <button class="cart-button">
            <img src="./images/logout.png" alt="Logout Icon"> 
            <a href="logout.php" class="logout-btn">Logout</a>
        </button>
    </nav>

    <div class="admin-container">
        <div class="admin-header">
            <h1>User Database Records</h1>
        </div>
        
        <!-- Inline Edit Panel -->
        <?php if ($edit_user): ?>
            <div class="edit-box">
                <h3>Editing User #<?php echo $edit_user['id']; ?></h3>
                <form method="POST" action="<?php echo $current_page; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                    <div class="edit-form-row">
                        <div class="edit-form-group">
                            <label>FULL NAME</label>
                            <input type="text" name="fullname" value="<?php echo htmlspecialchars($edit_user['fullname']); ?>" required>
                        </div>
                        <div class="edit-form-group">
                            <label>EMAIL</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                        </div>
                        <button type="submit" name="update_user" class="btn-save">Save Changes</button>
                        <a href="<?php echo $current_page; ?>" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Table displaying registered users -->
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Registered Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users_result && $users_result->num_rows > 0): ?>
                    <?php while ($row = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['reg_date'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="<?php echo $current_page; ?>?action=edit&id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                <a href="<?php echo $current_page; ?>?action=delete&id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this user record?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #88745C;">No user records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="<?php echo $current_page; ?>?action=clear_all" 
           class="btn-clear" 
           onclick="return confirm('WARNING: This will permanently delete ALL users and reset IDs to 1. Continue?');">
           Clear & Reset All IDs
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