<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang chủ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php include "includes/header.php"; ?>

<h2 class="text-2xl font-semibold text-center my-6">Danh sách sản phẩm</h2>

<div class="flex flex-wrap justify-center gap-6 px-4">
<?php
include "config/database.php";
$result = mysqli_query($conn, "SELECT * FROM products");

while ($row = mysqli_fetch_assoc($result)) {
?>
<a href="chitietsanpham.php?id=<?php echo $row['id']; ?>">
    <div class="bg-white border border-gray-300 rounded-lg p-4 w-70 text-center shadow-md 
                transform transition duration-300 hover:-translate-y-2 hover:scale-105 hover:shadow-xl">
        <img src="uploads/image/album/<?php echo $row['image']; ?>" class="mx-auto mb-3 w-50 h-50 object-cover rounded-md">
        <h3 class="text-lg font-semibold mb-2"><?php echo $row['name']; ?></h3>
        <h4 class="text-md text-gray-600 mb-2"><?php echo $row['artist']; ?></h4>
        <p class="text-red-600 font-bold mb-4"><?php echo number_format($row['price']); ?> VNĐ</p>
        <button class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
            Thêm giỏ
        </button>
        <span>
            <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                Mua ngay
            </button>
        </span>
    </div>
<?php } ?>
</div>

<?php include "includes/footer.php"; ?>

</body>
</html>
