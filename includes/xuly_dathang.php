<?php
session_start();
include __DIR__ . "/config/database.php";

// 🔥 1. CHECK LOGIN
if (!isset($_SESSION['user_id'])) {
    echo "not_login";
    exit;
}

$user_id = $_SESSION['user_id'];

// 🔥 2. LẤY ĐỊA CHỈ
$dia_chi_id = $_POST['dia_chi_id'] ?? '';

if ($dia_chi_id == '') {
    echo "no_address";
    exit;
}

// 🔥 kiểm tra địa chỉ có thuộc user không
$check_addr = mysqli_query($conn, "
    SELECT * FROM dia_chi 
    WHERE id = $dia_chi_id AND nguoi_dung_id = $user_id
");

if (mysqli_num_rows($check_addr) == 0) {
    echo "invalid_address";
    exit;
}

// 🔥 3. LẤY GIỎ HÀNG
$res_cart = mysqli_query($conn, "
    SELECT * FROM cart 
    WHERE nguoi_dung_id = $user_id
");

if (mysqli_num_rows($res_cart) == 0) {
    echo "empty_cart";
    exit;
}

// 🔥 4. TẠO ĐƠN HÀNG
$insert_order = mysqli_query($conn, "
    INSERT INTO don_hang (nguoi_dung_id, dia_chi_id, ngay_dat)
    VALUES ($user_id, $dia_chi_id, NOW())
");

if (!$insert_order) {
    echo "error_order";
    exit;
}

$order_id = mysqli_insert_id($conn);

// 🔥 5. LƯU CHI TIẾT ĐƠN
while ($row = mysqli_fetch_assoc($res_cart)) {

    $san_pham_id = $row['san_pham_id'];
    $so_luong = $row['so_luong'];

    mysqli_query($conn, "
        INSERT INTO chi_tiet_don_hang 
        (don_hang_id, san_pham_id, so_luong)
        VALUES ($order_id, $san_pham_id, $so_luong)
    ");
}

// 🔥 6. XÓA GIỎ
mysqli_query($conn, "
    DELETE FROM cart WHERE nguoi_dung_id = $user_id
");

// 🔥 7. XÓA SESSION CART (nếu có)
unset($_SESSION['cart']);

echo "success";
exit;