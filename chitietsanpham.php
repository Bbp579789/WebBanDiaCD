<?php session_start(); ?>
<?php
require_once __DIR__ . '/config/database.php'; // $conn (mysqli)
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php'); exit;
}

// FIX: JOIN thêm bảng chi_tiet_san_pham để lấy mô tả, năm phát hành, hãng đĩa
$sql = "SELECT sp.*, ns.ten_nghe_si, ct.mo_ta_san_pham, ct.nam_phat_hanh, ct.hang_dia
        FROM san_pham sp
        LEFT JOIN nghe_si ns ON sp.nghe_si_id = ns.id
        LEFT JOIN chi_tiet_san_pham ct ON sp.id = ct.san_pham_id
        WHERE sp.id = ? AND sp.trang_thai = 1 LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$product) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['ten_san_pham']); ?> - Chi tiết sản phẩm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>
<body class="bg-gray-50">
    <?php include "includes/header.php"; ?>
    <div class="h-[100px]"></div>

    <div class="mt-36 max-w-6xl mx-auto mt-10 p-6 bg-white rounded-xl shadow-lg border border-gray-100 flex flex-col md:flex-row gap-12">

        <!-- Hình ảnh sản phẩm (Bên trái) -->
        <div class="md:w-1/2 flex justify-center">
            <div class="relative group">
                <img src="uploads/image/album/<?php echo htmlspecialchars($product['hinh_anh'] ?? ''); ?>"
                    class="w-full max-w-[450px] aspect-square rounded-lg shadow-2xl object-cover border-4 border-white transition-transform duration-300 group-hover:scale-105"
                    onerror="this.onerror=null;this.src='assets/img/default-album.png'">
                <div class="absolute inset-0 rounded-lg ring-1 ring-black/5"></div>
            </div>
        </div>

        <!-- Thông tin sản phẩm (Bên phải) -->
        <div class="md:w-1/2 flex flex-col">
            <nav class="text-sm text-gray-500 mb-4">
    <a href="index.php" class="hover:text-indigo-600 transition-colors">Trang chủ</a> 
    <span class="mx-1">/</span> 
    <a href="danhsachsanpham.php" class="hover:text-indigo-600 transition-colors">Cửa hàng</a> 
    <span class="mx-1">/</span> 
    <span class="text-gray-800 font-medium">
        <?php echo htmlspecialchars($product['ten_san_pham']); ?>
    </span>
</nav>

            <h2 class="text-4xl font-extrabold mb-4 text-gray-900 leading-tight">
                <?php echo htmlspecialchars($product['ten_san_pham']); ?>
            </h2>

            <div class="flex items-center gap-4 mb-6">
                <span class="text-2xl font-bold text-indigo-600">
                    <?php echo number_format((float)$product['gia'], 0, ',', '.'); ?> VNĐ
                </span>
                <?php if($product['so_luong'] > 0): ?>
                    <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">Còn hàng (<?php echo $product['so_luong']; ?>)</span>
                <?php else: ?>
                    <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full">Hết hàng</span>
                <?php endif; ?>
            </div>

            <!-- Thông số kỹ thuật nhanh -->
            <div class="grid grid-cols-2 gap-y-3 border-y border-gray-100 py-6 mb-6">
                <div class="text-gray-500">Nghệ sĩ:</div>
                <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($product['ten_nghe_si']); ?></div>
                
                <div class="text-gray-500">Năm phát hành:</div>
                <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($product['nam_phat_hanh'] ?? 'Đang cập nhật'); ?></div>
                
                <div class="text-gray-500">Hãng đĩa:</div>
                <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($product['hang_dia'] ?? 'Đang cập nhật'); ?></div>
            </div>

            <div class="mb-8">
                <h4 class="font-bold text-gray-900 mb-2 uppercase text-sm tracking-wider">Mô tả sản phẩm:</h4>
                <p class="text-gray-600 leading-relaxed italic">
                    <?php echo !empty($product['mo_ta_san_pham']) ? nl2br(htmlspecialchars($product['mo_ta_san_pham'])) : 'Chưa có mô tả cho sản phẩm này.'; ?>
                </p>
            </div>

            <!-- Nút hành động -->
            <div class="flex flex-wrap gap-4 mt-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button onclick="themGio(<?= $id ?>)"
                        class="flex-1 min-w-[150px] flex items-center justify-center gap-2 bg-white border-2 border-indigo-600 text-indigo-600 px-6 py-3 rounded-xl font-bold hover:bg-indigo-50 transition-all">
                        <i class="fa-solid fa-cart-plus"></i> Thêm giỏ hàng
                    </button>

                    <button onclick="muaNgay(<?= $id ?>)" 
                        class="flex-1 min-w-[150px] bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all">
                        Mua ngay
                    </button>
                <?php else: ?>
                    <button onclick="openLogin()" class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all">
                        Đăng nhập để mua hàng
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="toast" class="fixed top-24 right-[-300px] bg-indigo-600 text-white px-6 py-4 rounded-xl shadow-2xl z-[9999] transition-all duration-500 flex items-center gap-3">
        <i class="fa-solid fa-circle-check"></i>
        <span id="toast-msg"></span>
    </div>

    <script src="assets/js/toast.js"></script>
    <script>
        // Các hàm themGio, muaNgay của bạn ở đây...
    </script>

    <?php $footer_mt = 'mt-24'; include "includes/footer.php"; ?>
</body>
</html>