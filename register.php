<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require_once "connection.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($fullname) || empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit();
    }

    try {
        // Check duplicate email
        $checkStmt = $conn->prepare("SELECT id FROM register WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $checkStmt->close();
            echo json_encode(['status' => 'error', 'message' => 'Email is already registered.']);
            exit();
        }
        $checkStmt->close();

        // Hash password and insert
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO register (fullname, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $fullname, $email, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['user_id']  = $stmt->insert_id;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['is_admin'] = false;

            $stmt->close();
            $conn->close();

            echo json_encode([
                'status'   => 'success',
                'message'  => 'Account created successfully!',
                'redirect' => 'signin.html'
            ]);
            exit();
        } else {
            throw new Exception("Registration failed.");
        }

    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
        exit();
    }
}
?>