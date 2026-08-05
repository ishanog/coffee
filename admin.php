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

// --- 3. FETCH USER FOR EDIT MODAL/FORM (IF REQUESTED) ---
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

// --- 4. FETCH ALL USERS ---
$sql = "SELECT * FROM register ORDER BY id asc ";
$users_result = $conn->query($sql);
// --- HANDLE PURGE & RESET ALL USERS ---
if (isset($_GET['action']) && $_GET['action'] === 'clear_all') {
    if ($conn->query("TRUNCATE TABLE register")) {
        $message = "All user records deleted and ID counter reset to 1!";
    } else {
        $error = "Failed to clear records: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeanFlow - Admin Dashboard</title>
    <link rel="stylesheet" href="style.css?v=1.1">
    <style>
        .admin-container {
            max-width: 1050px;
            margin: 120px auto 50px;
            padding: 30px;
            background-color: #201208;
            border: 1px solid #BE874B;
            border-radius: 12px;
            color: #F3EAD8;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #3d2a1d;
            padding-bottom: 15px;
        }

        .admin-header h1 {
            color: #BE874B;
            margin: 0;
            font-size: 2rem;
        }

        .logout-btn {
            background-color: #c62828;
            color: #fff;
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
        }

        /* Action Buttons */
        .btn-edit {
            background-color: #BE874B;
            color: #190F0A;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
            margin-right: 6px;
        }

        .btn-delete {
            background-color: #3b1818;
            color: #ef5350;
            border: 1px solid #ef5350;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
        }

        .btn-delete:hover {
            background-color: #c62828;
            color: #fff;
        }

        /* Edit Box */
        .edit-box {
            background-color: #190F0A;
            border: 1px solid #BE874B;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .edit-box h3 {
            margin-top: 0;
            color: #BE874B;
        }

        .edit-form-row {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }

        .edit-form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .edit-form-group label {
            font-size: 12px;
            margin-bottom: 5px;
            color: #88745C;
        }

        .edit-form-group input {
            height: 40px;
            border: 1px solid #3d2a1d;
            background: #201208;
            border-radius: 6px;
            padding: 0 12px;
            color: white;
        }

.btn-clear {
    background-color: #c62828;
    color: #fff;
    text-decoration: none;
    padding: 8px 18px;
    border-radius: 6px;
    font-weight: bold;
    font-size: 14px;
    
    /* Centering Rules */
    display: block;
    width: fit-content;
    margin: 20px auto;
}

        .btn-save {
            background: #4caf50;
            color: white;
            border: none;
            padding: 0 20px;
            height: 42px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-cancel {
            background: #555;
            color: white;
            padding: 12px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .admin-table th, .admin-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #3d2a1d;
        }

        .admin-table th {
            background-color: #190F0A;
            color: #BE874B;
            font-size: 14px;
            text-transform: uppercase;
        }

        .admin-table tr:hover {
            background-color: #2a190e;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo">
            <a href="index.html">
                <img src="images/logo.png" alt="Coffee Shop Logo">
                <span class="logo-text">BeanFlow Admin</span>
            </a>
        </div>
    </nav>

    <div class="admin-container">
        <div class="admin-header">
            <h1>User Database Records</h1>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        

        <!-- Inline Edit Panel -->
        <?php if ($edit_user): ?>
            <div class="edit-box">
                <h3>Editing User #<?php echo $edit_user['id']; ?></h3>
                <form method="POST" action="admin.php">
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
                        <a href="admin.php" class="btn-cancel">Cancel</a>
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
                                <a href="admin.php?action=edit&id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                <a href="admin.php?action=delete&id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this user record?');">Delete</a>
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
 <a href="admin.php?action=clear_all" 
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