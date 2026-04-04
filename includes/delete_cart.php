<?php
session_start();
include __DIR__ . "/../config/database.php";

header('Content-Type: application/json');

$id = $_POST['id'] ?? null;
$user_id = $_SESSION['user_id'] ?? 0;

// xóa SESSION
if ($id !== null && isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]);
}

// 🔥 xóa DATABASE
if ($user_id > 0 && $id !== null) {
    mysqli_query($conn, "
        DELETE FROM cart 
        WHERE nguoi_dung_id = $user_id AND san_pham_id = $id
    ");
}

// tính lại tổng
$total = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $it) {
        $total += $it['price'] * $it['quantity'];
    }
}

echo json_encode([
    "success" => true,
    "total" => number_format($total)
]);

exit;