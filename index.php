<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang chủ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white-100">

<?php include "includes/header.php"; ?>

<h2 class="text-2xl font-semibold text-center my-6">Danh sách sản phẩm</h2>

<div class="flex flex-wrap justify-center gap-6 px-4">
<?php
include "config/database.php";

$sql = "
SELECT sp.*, ns.ten_nghe_si
FROM san_pham sp
JOIN nghe_si ns ON sp.nghe_si_id = ns.id
";

$result = mysqli_query($conn, $sql);

$count = 0;

while ($row = mysqli_fetch_assoc($result)) {

    // Mở hàng mới nếu là sản phẩm đầu hoặc mỗi 4 sản phẩm
    if ($count % 4 == 0) {
        echo '<div class="flex justify-center gap-6 mb-8">';
    }
?>

    <!-- CARD SẢN PHẨM -->
    <div class="bg-white border border-gray-300 rounded-lg p-4 w-64 text-center shadow-md
                transform transition duration-300 hover:-translate-y-2 hover:shadow-xl">

        <a href="chitietsanpham.php?id=<?php echo $row['id']; ?>">
            <img src="uploads/image/album/<?php echo $row['hinh_anh']; ?>"
                 class="mx-auto mb-3 w-48 h-48 object-cover rounded-md">

            <h3 class="text-lg font-semibold">
                <?php echo $row['ten_san_pham']; ?>
            </h3>

            <p class="text-gray-600 mb-2">
                <?php echo $row['ten_nghe_si']; ?>
            </p>
        </a>

        <p class="text-red-600 font-bold mb-4">
            <?php echo number_format($row['gia']); ?> VNĐ
        </p>

        <div class="flex justify-center gap-3">
            <button class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
                Thêm giỏ
            </button>
            <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                Mua ngay
            </button>
        </div>
    </div>

<?php
    $count++;

    // Đóng hàng khi đủ 4 sản phẩm
    if ($count % 4 == 0) {
        echo '</div>';
    }
}

// Đóng hàng nếu số sản phẩm không chia hết cho 4
if ($count % 4 != 0) {
    echo '</div>';
}
?>
