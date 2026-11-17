<?php
session_start();
header('Content-Type: application/json');

require_once "connection.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve raw JSON sent from script.js
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Invalid JSON body
    if ($json === false || $json === '' || $data === null) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid order data.']);
        exit();
    }

    $items = $data['items'] ?? [];
    $userId = $_SESSION['user_id'] ?? null;

    if (!is_array($items) || empty($items)) {
        echo json_encode(['status' => 'error', 'message' => 'Cart is empty.']);
        exit();
    }

    // Validate each item and recompute the total server-side so the
    // stored amount can't be tampered with from the client.
    $calculatedTotal = 0;
    foreach ($items as $item) {
        $title = trim((string)($item['title'] ?? ''));
        $price = $item['priceUsd'] ?? null;
        $qty   = $item['quantity'] ?? null;

        if ($title === '' || !is_numeric($price) || !is_numeric($qty) || (int)$qty < 1) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid product in cart.']);
            exit();
        }

        $calculatedTotal += (float)$price * (int)$qty;
    }

    try {
        $itemsJson = json_encode($items);
        $status = 'Pending';

        $stmt = $conn->prepare(
            "INSERT INTO orders (user_id, items, total_amount, status) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("isds", $userId, $itemsJson, $calculatedTotal, $status);

        if ($stmt->execute()) {
            echo json_encode([
                'status'   => 'success',
                'message'  => 'Order placed successfully!',
                'order_id' => $stmt->insert_id
            ]);
        } else {
            throw new Exception("Execution failed.");
        }
        $stmt->close();
    } catch (Throwable $e) {
        // Log the real error for debugging, but never expose it to the customer.
        error_log('place_order.php error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Unable to place your order. Please try again.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>