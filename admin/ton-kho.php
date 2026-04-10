<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Nếu chưa đăng nhập qua URL bí mật, không cho xem nội dung
    die("Bạn phải đăng nhập bằng URL bí mật để truy cập.");
}
?>

<?php

require_once '../config/database.php';

// Khởi tạo các biến lọc
$current_page = 'ton-kho.php';
// $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Đầu tháng
// $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');      // Hôm nay

$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '2020-01-01';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d H:i:s');
$threshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 10;
$lowStockThreshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 10; // Ngưỡng sắp hết hàng

try {
    $host = 'localhost'; $db = 'webbandiacd'; $user = 'root'; $pass = '';
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 1. TRUY VẤN BÁO CÁO NHẬP - XUẤT - TỒN
    // Logic: Tồn hiện tại = Tổng nhập - Tổng xuất
   // 1. TRUY VẤN BÁO CÁO NHẬP - XUẤT - TỒN
$sql = "SELECT 
            sp.id, sp.ten_san_pham, sp.hinh_anh, sp.so_luong AS ton_hien_tai,
            
            -- TỔNG NHẬP (Từ bảng chi_tiet_phieu_nhap và phieu_nhap)
            (SELECT COALESCE(SUM(ctpn.so_luong), 0) 
             FROM chi_tiet_phieu_nhap ctpn 
             JOIN phieu_nhap pn ON ctpn.phieu_nhap_id = pn.id 
             WHERE ctpn.san_pham_id = sp.id 
             AND pn.ngay_nhap BETWEEN :start1 AND :end1) as sl_nhap,
            
            -- TỔNG XUẤT (Từ bảng chi_tiet_don_hang và don_hang)
            (SELECT COALESCE(SUM(ctdh.so_luong), 0) 
             FROM chi_tiet_don_hang ctdh 
             JOIN don_hang dh ON ctdh.don_hang_id = dh.id 
             WHERE ctdh.san_pham_id = sp.id 
             AND dh.trang_thai != 'Đã hủy'
             AND dh.ngay_dat BETWEEN :start2 AND :end2) as sl_xuat
             
        FROM san_pham sp";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'start1' => $startDate, 'end1' => $endDate,
    'start2' => $startDate, 'end2' => $endDate
]);
$inventory = $stmt->fetchAll();

    // 2. THỐNG KÊ NHANH
    $lowStockCount = 0;
    foreach($inventory as $item) {
        if($item['ton_hien_tai'] <= $lowStockThreshold) $lowStockCount++;
    }

} catch (\PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo tồn kho - ART STORE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-700">

<div class="flex min-h-screen">
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
                    $active = ($current_page == $item['file']) 
                        ? 'bg-indigo-50 text-indigo-600 font-bold shadow-sm' 
                        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700 font-medium';
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

    <main class="flex-1 p-8">
        <header class="flex flex-col md:flex-row justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 italic uppercase tracking-tight">Báo cáo & Tồn kho</h1>
                <p class="text-slate-500 text-sm">Theo dõi biến động hàng hóa Nhập - Xuất</p>
            </div>
            
            <!-- CẢNH BÁO SẮP HẾT HÀNG -->
            <?php if($lowStockCount > 0): ?>
            <div class="flex items-center gap-3 bg-red-50 border border-red-100 p-3 rounded-2xl animate-pulse">
                <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
                <span class="text-red-600 font-bold text-sm"><?= $lowStockCount ?> sản phẩm sắp hết hàng!</span>
            </div>
            <?php endif; ?>
        </header>

        <!-- BỘ LỌC THỜI GIAN & NGƯỠNG CẢNH BÁO -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 mb-8 shadow-sm">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2">Từ ngày</label>
                    <input type="date" name="start_date" value="<?= $startDate ?>" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2">Đến ngày</label>
                    <input type="date" name="end_date" value="<?= $endDate ?>" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2">Ngưỡng báo hết hàng</label>
                    <input type="number" name="threshold" value="<?= $lowStockThreshold ?>" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
                <button type="submit" class="bg-indigo-600 text-white font-bold py-2 rounded-xl hover:bg-indigo-700 transition">Xem báo cáo</button>
            </form>
        </div>

        <!-- BẢNG DỮ LIỆU -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Sản phẩm</th>
                        <th class="px-6 py-4 text-center">Đã Nhập</th>
                        <th class="px-6 py-4 text-center">Đã Xuất</th>
                        <th class="px-6 py-4 text-center">Tồn hiện tại</th>
                        <th class="px-6 py-4">Tình trạng</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach($inventory as $item): ?>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 flex items-center gap-3">
                            <img src="../uploads/image/album/<?= $item['hinh_anh'] ?>" class="w-12 h-12 rounded-xl object-cover border border-slate-100">
                            <span class="font-bold text-slate-700"><?= htmlspecialchars($item['ten_san_pham']) ?></span>
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-blue-600">+<?= $item['sl_nhap'] ?></td>
                        <td class="px-6 py-4 text-center font-bold text-red-500">-<?= $item['sl_xuat'] ?></td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-lg font-black text-slate-800"><?= $item['ton_hien_tai'] ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if($item['ton_hien_tai'] <= 0): ?>
                                <span class="px-3 py-1 bg-red-100 text-red-600 rounded-full text-[10px] font-black uppercase">Hết hàng</span>
                            <?php elseif($item['ton_hien_tai'] <= $lowStockThreshold): ?>
                                <span class="px-3 py-1 bg-amber-100 text-amber-600 rounded-full text-[10px] font-black uppercase tracking-tight">Sắp hết hàng</span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-emerald-100 text-emerald-600 rounded-full text-[10px] font-black uppercase">An toàn</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>