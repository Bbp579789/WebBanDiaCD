<!-- <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?> -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang chủ</title>
    <script src="https://cdn.tailwindcss.com"></script>

</head>
<body>

<?php include "includes/header.php"; ?>

<h2 style="text-align:center;">Danh sách sản phẩm</h2>

<div style="display:flex;justify-content:center;gap:20px;flex-wrap:wrap;">
<?php
include "config/database.php";
$result = mysqli_query($conn, "SELECT * FROM products");

while ($row = mysqli_fetch_assoc($result)) {
?>
    <div style="border:1px solid #ccc;padding:15px;width:200px;text-align:center;">
        <img src="uploads/image/album/<?php echo $row['image']; ?>" width="150">
        <h3><?php echo $row['name']; ?></h3>
        <p><?php echo number_format($row['price']); ?> VNĐ</p>
        <button>Thêm giỏ</button>
    </div>
<?php } ?>
</div>

<?php include "includes/footer.php"; ?>

</body>
</html>
