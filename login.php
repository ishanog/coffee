<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require_once "connection.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = trim($_POST['email'] ?? '');
    $raw_pass = $_POST['password'] ?? '';

    if (empty($email) || empty($raw_pass)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in both email and password.']);
        exit();
    }

    // --- ADMIN CREDENTIAL CHECK ---
    $admin_email = "admin@beanflow.com";
    $admin_pass  = "admin123";

    if ($email === $admin_email && $raw_pass === $admin_pass) {
        $_SESSION['is_admin'] = true;
        $_SESSION['fullname'] = "BeanFlow Admin";

        echo json_encode([
            'status'   => 'success',
            'message'  => 'Welcome Admin! Redirecting to dashboard...',
            'redirect' => 'admin.php'
        ]);
        exit();
    }

    // --- REGULAR USER CHECK ---
    try {
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("SELECT id, fullname, password FROM register WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($raw_pass, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['is_admin'] = false;

                $stmt->close();
                $conn->close();

                echo json_encode([
                    'status'   => 'success',
                    'message'  => 'Welcome back, ' . $user['fullname'] . '!',
                    'redirect' => 'index.html'
                ]);
                exit();
            }
        }

        $stmt->close();
        $conn->close();

        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        exit();

    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
        exit();
    }
}
?>