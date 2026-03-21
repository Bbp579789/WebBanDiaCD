<?php
session_start();
include __DIR__ . "/../config/database.php";

$id = $_GET['id'];

// Lấy sản phẩm kèm id thể loại
$sql = "SELECT * FROM san_pham WHERE id = $id";
$result = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    echo "Không tìm thấy sản phẩm";
    exit;
}

// Lấy tên thể loại từ bảng the_loai
$the_loai = '';
if (!empty($product['the_loai_id'])) {
    $sql_type = "SELECT ten_the_loai FROM the_loai WHERE id = " . $product['the_loai_id'];
    $res_type = mysqli_query($conn, $sql_type);
    if ($res_type && mysqli_num_rows($res_type) > 0) {
        $row_type = mysqli_fetch_assoc($res_type);
        $the_loai = $row_type['ten_the_loai'];
    }
}

// tạo giỏ nếu chưa có
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// FIX lỗi scalar
if (isset($_SESSION['cart'][$id]) && is_array($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]['quantity']++;
} else {
    $_SESSION['cart'][$id] = [
        'name' => $product['ten_san_pham'],
        'price' => $product['gia'],
        'img' => $product['hinh_anh'],
        'theloai' => $the_loai, // ✅ lưu tên thể loại
        'quantity' => 1
    ];
}

// thông báo thêm giỏ
$_SESSION['success'] = "Đã thêm vào giỏ hàng!";

// chuyển sang giỏ hàng
// header("Location: ../giohang.php");
echo "success";
exit;