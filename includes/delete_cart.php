<?php
session_start();

header('Content-Type: application/json');

$id = $_POST['id'] ?? null;

if ($id !== null && isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]);
}

// tính lại tổng
$total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $it) {
        $total += $it['price'] * $it['quantity'];
    }
}

echo json_encode([
    "success" => true,
    "total" => number_format($total)
]);

exit;