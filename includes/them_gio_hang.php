<?php
session_start();
include __DIR__ . "/../config/database.php";

$id = $_GET['id'];
$user_id = $_SESSION['user_id'] ?? 0; // 🔥 lấy user

// Lấy sản phẩm
$sql = "SELECT * FROM san_pham WHERE id = $id";
$result = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    echo "Không tìm thấy sản phẩm";
    exit;
}

// Lấy thể loại
$the_loai = '';
if (!empty($product['the_loai_id'])) {
    $sql_type = "SELECT ten_the_loai FROM the_loai WHERE id = " . $product['the_loai_id'];
    $res_type = mysqli_query($conn, $sql_type);
    if ($res_type && mysqli_num_rows($res_type) > 0) {
        $row_type = mysqli_fetch_assoc($res_type);
        $the_loai = $row_type['ten_the_loai'];
    }
}

// SESSION
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]['quantity']++;
} else {
    $_SESSION['cart'][$id] = [
        'name' => $product['ten_san_pham'],
        'price' => $product['gia'],
        'img' => $product['hinh_anh'],
        'theloai' => $the_loai,
        'quantity' => 1 
    ];
}

//  LƯU DATABASE
if ($user_id > 0) {
    $check = mysqli_query($conn, "
        SELECT * FROM cart 
        WHERE nguoi_dung_id = $user_id AND san_pham_id = $id
    ");

    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "
            UPDATE cart 
            SET so_luong = so_luong + 1 
            WHERE nguoi_dung_id = $user_id AND san_pham_id = $id
        ");
    } else {
        mysqli_query($conn, "

            INSERT INTO cart (nguoi_dung_id, san_pham_id, so_luong)
            VALUES ($user_id, $id, 1)
        ");
    }
}

echo "success";
exit;