<?php
session_start();

header('Content-Type: application/json');

$id = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null;

if ($id === null || !isset($_SESSION['cart'][$id])) {
    echo json_encode(["success" => false]);
    exit;
}

if ($action == "plus") {
    $_SESSION['cart'][$id]['quantity']++;
} elseif ($action == "minus") {
    $_SESSION['cart'][$id]['quantity']--;

    if ($_SESSION['cart'][$id]['quantity'] <= 0) {
        unset($_SESSION['cart'][$id]);

        // tính lại tổng sau khi xóa
        $total = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $it) {
                $total += $it['price'] * $it['quantity'];
            }
        }

        echo json_encode([
            "success" => true,
            "deleted" => true,
            "total" => number_format($total)
        ]);
        exit;
    }
}

$item = $_SESSION['cart'][$id];
$money = $item['price'] * $item['quantity'];

// tính lại tổng
$total = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $it) {
        $total += $it['price'] * $it['quantity'];
    }
}

echo json_encode([
    "success" => true,
    "quantity" => $item['quantity'],
    "money" => number_format($money),
    "total" => number_format($total)
]);
exit;