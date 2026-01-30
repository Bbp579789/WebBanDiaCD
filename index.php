<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Ở đây có muzic</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white-100">

<!-- Header -->
<?php include "includes/header.php"; ?>

<!-- Banner -->
<?php include "includes/banner.php"; ?>

<!-- Sản phẩm nổi bật -->
<div class="bg-gray-200 py-8 mt-6 rounded-3xl">

<h2 class="text-center my-6
           text-2xl md:text-3xl lg:text-[36px]
           font-thin italic tracking-wide font-serif mb-8">
    Sản phẩm nổi bật
</h2>

<div class="max-w-7xl mx-auto px-4">
    <div class="flex flex-wrap justify-center gap-6">

<?php
include "config/database.php";

$sql = "
SELECT sp.*, ns.ten_nghe_si
FROM san_pham sp
JOIN nghe_si ns ON sp.nghe_si_id = ns.id
ORDER BY sp.id
LIMIT 8
";

$result = mysqli_query($conn, $sql);
$index = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $hiddenClass = ($index >= 4) ? 'hidden extra-product' : '';
?>
        <div class="bg-white border border-gray-300 rounded-lg p-4 w-64 text-center shadow-md
                    transform transition duration-300 hover:-translate-y-2 hover:shadow-xl
                    <?php echo $hiddenClass; ?>">

            <a href="chitietsanpham.php?id=<?php echo $row['id']; ?>">
                <img src="uploads/image/album/<?php echo $row['hinh_anh']; ?>"
                     class="mx-auto mb-3 w-48 h-48 object-cover rounded-md">

                <h3 class="text-lg font-semibold"><?php echo $row['ten_san_pham']; ?></h3>
                <p class="text-gray-600 mb-2"><?php echo $row['ten_nghe_si']; ?></p>
            </a>

            <p class="text-red-600 font-bold mb-4">
                <?php echo number_format($row['gia']); ?> VNĐ
            </p>

            <div class="flex justify-center gap-3">
                <button class="bg-green-600 text-white px-4 py-2 rounded-md">
                    Thêm giỏ
                </button>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-md">
                    Mua ngay
                </button>
            </div>
        </div>
<?php
    $index++;
}
?>

    </div>

   <!-- Nút xem thêm / thu gọn -->
    <div class="text-center mt-8">
      <span id="btnToggle"
          onclick="toggleProducts()"
          class="cursor-pointer text-green-600 font-medium hover:underline select-none">
        Xem thêm 4 sản phẩm ⯆
      </span>
    </div>


</div>
</div>

<script>
let isExpanded = false;

function toggleProducts() {
    const products = document.querySelectorAll('.extra-product');
    const btn = document.getElementById('btnToggle');

    if (!isExpanded) {
        // HIỆN
        products.forEach(el => el.classList.remove('hidden'));
        btn.innerHTML = 'Thu gọn ⯅';
        isExpanded = true;
    } else {
        // ẨN
        products.forEach(el => el.classList.add('hidden'));
        btn.innerHTML = 'Xem thêm 4 sản phẩm ⯆';
        isExpanded = false;

        // scroll lên phần sản phẩm
        btn.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}
</script>




<!-- Footer -->
 <?php $footer_mt = 'mt-4'; ?>
<?php include "includes/footer.php"; ?>

</body>
</html>
