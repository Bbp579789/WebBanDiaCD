<?php session_start(); ?>
<?php
include "config/database.php";

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $sql = "
    SELECT 
        sp.ten_san_pham,
        sp.gia,
        sp.hinh_anh,
        ns.ten_nghe_si,
        ns.mo_ta_nghe_si,
        ct.mo_ta_san_pham
    FROM san_pham sp
    JOIN nghe_si ns ON sp.nghe_si_id = ns.id
    JOIN chi_tiet_san_pham ct ON sp.id = ct.san_pham_id
    WHERE sp.id = $id
    ";

    $result = mysqli_query($conn, $sql);
    $product = mysqli_fetch_assoc($result);

    if (!$product) {
        echo "Sản phẩm không tồn tại!";
        exit;
    }
} else {
    echo "Không có sản phẩm nào được chọn!";
    exit;
}
?>


<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết sản phẩm</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>

    <?php include "includes/header.php"; ?>
    <div class="h-[150px]"></div>

    <div
        class="max-w-4xl mx-auto flex flex-col md:flex-row md:items-start gap-10 mt-10 p-6 bg-gray-200 rounded-md shadow-md">

        <!-- Hình ảnh sản phẩm -->
        <div class="flex flex-col items-center">
            <img src="uploads/image/album/<?php echo $product['hinh_anh']; ?>"
                class="w-80 md:w-96 max-h-[500px] rounded-md mb-4 object-contain">
        </div>

        <!-- Thông tin sản phẩm -->
        <div class="flex flex-col justify-start w-full">
            <h2 class="text-3xl font-bold mb-2 text-center md:text-left"><?php echo $product['ten_san_pham']; ?></h2>
            <h3 class="text-xl text-gray-700 mb-2 text-center md:text-left">Nghệ sĩ:
                <?php echo $product['ten_nghe_si']; ?>
            </h3>
            <p class="text-green-600 font-semibold mb-4 text-center md:text-left text-lg">
                <?php echo number_format($product['gia']); ?> VNĐ
            </p>
            <p class="text-gray-800 mb-6 text-justify leading-relaxed">Tác giả :
                <?php echo $product['mo_ta_nghe_si']; ?>
            </p>
            <p class="text-gray-800 mb-6 text-justify leading-relaxed">Mô tả sản phảm:
                <?php echo $product['mo_ta_san_pham']; ?>
            </p>

            <div class="flex justify-center md:justify-start gap-4">
                <div class="flex justify-center gap-3">

                    <?php if (isset($_SESSION['user_id'])): ?>

                        <button onclick="themGio(<?= $id ?>)"
                            class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            Thêm giỏ
                        </button>

                        <button onclick="muaNgay(<?= $id ?>)" class="bg-blue-600 text-white px-4 py-2 rounded-md">
                            Mua ngay
                        </button>

                    <?php else: ?>

                        <button onclick="openLogin()" class="bg-green-600 text-white px-4 py-2 rounded-md">
                            Thêm giỏ
                        </button>

                        <button onclick="openLogin()" class="bg-blue-600 text-white px-4 py-2 rounded-md">
                            Mua ngay
                        </button>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <div id="toast" class="fixed top-24 right-[-250px] 
     bg-green-500 text-white 
     px-3 py-2 text-sm 
     rounded-md shadow 
     z-[9999] transition-all duration-300">
    </div>
    <script src="assets/js/toast.js"></script>

    <!-- Footer -->
    <?php $footer_mt = 'mt-24'; ?>
    <?php include "includes/footer.php"; ?>

</body>

</html>