<?php
session_start();
require_once '../config/database.php';

// --- DB connection (PDO) ---
$host = 'localhost';
$db   = 'webbandiacd';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Lỗi kết nối DB: " . $e->getMessage());
}

$current_page = 'manager-order.php';
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header('Location: manager-order.php');
    exit;
}

try {
    // 1. TRUY VẤN THÔNG TIN TỔNG QUAN ĐƠN HÀNG (Sửa lỗi tại đây)
    // Cần Join bảng nguoi_dung để lấy họ tên, email, sdt
    $stmt = $pdo->prepare("
        SELECT dh.*, nd.ho_ten, nd.email, nd.so_dien_thoai
        FROM don_hang dh
        JOIN nguoi_dung nd ON dh.nguoi_dung_id = nd.id
        WHERE dh.id = ?
        LIMIT 1
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) { 
        die("Đơn hàng không tồn tại."); 
    }

    // 2. TRUY VẤN DANH SÁCH SẢN PHẨM TRONG ĐƠN HÀNG ĐÓ
    $stmt_items = $pdo->prepare("
        SELECT 
            ctdh.*, 
            sp.ten_san_pham, 
            sp.hinh_anh
        FROM chi_tiet_don_hang ctdh
        JOIN san_pham sp ON ctdh.san_pham_id = sp.id
        WHERE ctdh.don_hang_id = ?
    ");
    $stmt_items->execute([$order_id]);
    $order_items = $stmt_items->fetchAll();

} catch (\PDOException $e) { 
    die("Lỗi truy vấn: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?= $order_id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-700">

<div class="flex min-h-screen">
    <!-- SIDEBAR -->
    <aside class="w-64 bg-white border-r border-slate-200 hidden lg:block sticky top-0 h-screen">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-10">
                <img src="../uploads/image/others/music.jpg" class="w-10 h-10 rounded-lg object-cover" alt="Logo">
                <span class="font-bold text-xl tracking-tight text-indigo-600">The Muzik Store</span>
            </div>
            <nav class="space-y-1">
                <p class="text-[10px] font-bold text-slate-400 uppercase mb-4 px-3 tracking-widest">Menu quản trị</p>
                <?php
                $menu_items = [
                    ['file' => 'dashboard.php', 'label' => 'Tổng quan', 'icon' => 'fa-chart-line'],
                    ['file' => 'manager-cd.php', 'label' => 'Quản lý sản phẩm', 'icon' => 'fa-box'],
                    ['file' => 'manager-category.php', 'label' => 'Quản lý danh mục', 'icon' => 'fa-layer-group'],
                    ['file' => 'manager-order.php', 'label' => 'Quản lý đơn hàng', 'icon' => 'fa-cart-shopping'],
                    ['file' => 'manager-user.php', 'label' => 'Quản lý tài khoản', 'icon' => 'fa-user'],
                    ['file' => 'manager-import.php', 'label' => 'Quản lý nhập', 'icon' => 'fa-file-invoice'],
                    ['file' => 'profit.php', 'label' => 'Tỷ lệ lợi nhuận', 'icon' => 'fa-chart-pie'],
                    ['file' => 'ton-kho.php', 'label' => 'Quản lý tồn kho', 'icon' => 'fa-warehouse'],
                ];
                foreach ($menu_items as $item):
                    $active = ($current_page == $item['file']) ? 'bg-indigo-50 text-indigo-600 font-bold shadow-sm' : 'text-slate-500 hover:bg-slate-50 font-medium';
                ?>
                    <a href="<?= $item['file'] ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 <?= $active ?>">
                        <i class="fa-solid <?= $item['icon'] ?> w-5 text-center"></i> 
                        <span class="text-sm"><?= $item['label'] ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

             <nav class="mt-10 space-y-1">
                <p class="text-[10px] font-bold text-slate-400 uppercase mb-4 px-3 tracking-widest">Hệ thống</p>
                <a href="../index.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-500 hover:bg-slate-50 rounded-xl transition-all text-sm font-medium">
                    <i class="fa-solid fa-house w-5"></i> <span>Trang chủ</span>
                </a>
                <a href="../includes/logout-adm.php" class="flex items-center gap-3 px-3 py-2.5 text-red-500 hover:bg-red-50 rounded-xl transition-all text-sm font-medium">
                    <i class="fa-solid fa-right-from-bracket w-5"></i> <span>Đăng xuất</span>
                </a>
            </nav>
        </div>
    </aside>

    <main class="flex-1 p-4 lg:p-8">
        <header class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-4">
                <a href="manager-order.php" class="p-2 hover:bg-slate-200 rounded-full transition"><i class="fa-solid fa-arrow-left"></i></a>
                <h1 class="text-2xl font-bold text-slate-800">Chi tiết đơn hàng #<?= $order_id ?></h1>
            </div>
            <span class="px-4 py-1 rounded-full text-sm font-bold <?= ($order['trang_thai'] ?? '') == 'Hoàn thành' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' ?>">
                <?= htmlspecialchars($order['trang_thai'] ?? 'N/A') ?>
            </span>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100"><h2 class="font-bold text-lg">Sản phẩm đã đặt</h2></div>
                    <div class="p-6">
                        <table class="w-full text-sm">
                            <thead class="text-left text-slate-400 text-xs uppercase tracking-widest border-b border-slate-50">
                                <tr>
                                    <th class="pb-4">Sản phẩm</th>
                                    <th class="pb-4 text-center">Số lượng</th>
                                    <th class="pb-4 text-right">Đơn giá</th>
                                    <th class="pb-4 text-right">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td class="py-4">
                                        <div class="flex items-center gap-4">
                                            <img src="../uploads/image/album/<?= htmlspecialchars($item['hinh_anh']) ?>" class="w-12 h-12 rounded-lg object-cover shadow-sm" onerror="this.src='../assets/img/default.png'">
                                            <span class="font-bold text-slate-800"><?= htmlspecialchars($item['ten_san_pham']) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center font-bold text-slate-600"><?= $item['so_luong'] ?></td>
                                    <td class="py-4 text-right text-slate-500"><?= number_format($item['don_gia'], 0, ',', '.') ?>đ</td>
                                    <td class="py-4 text-right font-bold text-indigo-600"><?= number_format($item['so_luong'] * $item['don_gia'], 0, ',', '.') ?>đ</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-between items-center">
                        <span class="font-bold text-slate-500 uppercase text-xs">Tổng thanh toán</span>
                        <span class="text-2xl font-black text-indigo-600"><?= number_format($order['tong_tien'] ?? 0, 0, ',', '.') ?>đ</span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <!-- Thông tin khách hàng -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                    <h2 class="font-bold text-lg mb-4 border-b pb-2 text-slate-800">Khách hàng</h2>
                    <div class="space-y-4">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Họ tên</p>
                            <p class="font-semibold text-slate-700"><?= htmlspecialchars($order['ho_ten'] ?? 'N/A') ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Email</p>
                            <p class="font-semibold text-slate-700"><?= htmlspecialchars($order['email'] ?? 'N/A') ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Số điện thoại</p>
                            <p class="font-semibold text-slate-700"><?= htmlspecialchars($order['so_dien_thoai'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                </div>

                <!-- Phương thức thanh toán -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                    <h2 class="font-bold text-lg mb-4 border-b pb-2 text-slate-800">Thanh toán</h2>
                    <div class="flex items-center gap-3">
                        <?php 
                        $pttt = $order['phuong_thuc_thanh_toan'] ?? '';
                        if ($pttt == 'Chuyển khoản'): ?>
                            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg"><i class="fa-solid fa-building-columns"></i></div>
                            <div>
                                <p class="font-bold text-sm text-slate-700">Chuyển khoản</p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase">Online Banking</p>
                            </div>
                        <?php else: ?>
                            <div class="p-2 bg-amber-50 text-amber-600 rounded-lg"><i class="fa-solid fa-money-bill-1-wave"></i></div>
                            <div>
                                <p class="font-bold text-sm text-slate-700">Tiền mặt (COD)</p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase">Thanh toán khi nhận</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Địa chỉ -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                    <h2 class="font-bold text-lg mb-4 border-b pb-2 text-slate-800">Địa chỉ giao hàng</h2>
                    <div class="flex gap-3 text-sm">
                        <i class="fa-solid fa-location-dot text-indigo-500 mt-1"></i>
                        <p class="leading-relaxed text-slate-600 italic">
                            <?= htmlspecialchars($order['dia_chi_nhan_hang'] ?? 'N/A') ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>