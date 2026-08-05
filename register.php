<?php
// Prevent PHP warnings/errors from ruining JSON output format
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require_once "connection.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $raw_pass = $_POST['password'] ?? '';

    // 1. Input Validation
    if (empty($fullname) || empty($email) || empty($raw_pass)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
        exit();
    }

    try {
        // Ensure connection works
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        // 2. Check for duplicate email
        $checkStmt = $conn->prepare("SELECT id FROM register WHERE email = ?");
        if (!$checkStmt) {
            throw new Exception("Prepare failed (email check): " . $conn->error);
        }

        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $checkStmt->close();
            echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists!']);
            exit();
        }
        $checkStmt->close();

        // 3. Hash Password & Insert
        $hashed_password = password_hash($raw_pass, PASSWORD_DEFAULT);
        
        $insertStmt = $conn->prepare("INSERT INTO register (fullname, email, password) VALUES (?, ?, ?)");
        if (!$insertStmt) {
            throw new Exception("Prepare failed (user insert): " . $conn->error);
        }

        $insertStmt->bind_param("sss", $fullname, $email, $hashed_password);

        if ($insertStmt->execute()) {
            $insertStmt->close();
            
            $_SESSION['success'] = "Account created successfully!";
            echo json_encode([
                'status'   => 'success', 
                'redirect' => 'signin.html?tab=signin'
            ]);
            exit();
        } else {
            throw new Exception("Execution failed: " . $insertStmt->error);
        }

    } catch (Throwable $e) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Database Error: ' . $e->getMessage()
        ]);
        exit();
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
exit();
?>