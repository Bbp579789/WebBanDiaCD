<?php
session_start();
include __DIR__ . "/../config/database.php";

header('Content-Type: application/json');

$id = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null;
$user_id = $_SESSION['user_id'] ?? 0;

if ($id === null || !isset($_SESSION['cart'][$id])) {
    echo json_encode(["success" => false]);
    exit;
}

if ($action == "plus") {
    $_SESSION['cart'][$id]['quantity']++;

    // DB
    if ($user_id > 0) {
        mysqli_query($conn, "
            UPDATE cart 
            SET so_luong = so_luong + 1 
            WHERE nguoi_dung_id = $user_id AND san_pham_id = $id
        ");
    }

} elseif ($action == "minus") {
    $_SESSION['cart'][$id]['quantity']--;

    if ($user_id > 0) {
        mysqli_query($conn, "
            UPDATE cart 
            SET so_luong = so_luong - 1 
            WHERE nguoi_dung_id = $user_id AND san_pham_id = $id
        ");
    }

    if ($_SESSION['cart'][$id]['quantity'] <= 0) {

        unset($_SESSION['cart'][$id]);

        //  xóa DB luôn
        if ($user_id > 0) {
            mysqli_query($conn, "
                DELETE FROM cart 
                WHERE nguoi_dung_id = $user_id AND san_pham_id = $id
            ");
        }

        // tính lại tổng
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

// tính tổng
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