<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Nếu chưa đăng nhập qua URL bí mật, không cho xem nội dung
    die("Bạn phải đăng nhập bằng URL bí mật để truy cập.");
}
?>

<?php
// 1. Cấu hình kết nối Database
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

// Lấy tên file hiện tại để làm tính năng active menu (FIX LỖI WARNING)
$current_page = basename($_SERVER['PHP_SELF']);

// Khởi tạo các biến mặc định để tránh lỗi "Undefined variable"
$totalRevenue = 0;
$totalOrders = 0;
$totalCustomers = 0;
$totalProducts = 0;
$topProducts = [];
$recentOrders = [];
$topUsers = [];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // --- TRUY VẤN DỮ LIỆU THỐNG KÊ ---
    $resRevenue = $pdo->query("SELECT SUM(tong_tien) as total FROM hoa_don")->fetch();
    $totalRevenue = $resRevenue['total'] ?? 0;

    $resOrders = $pdo->query("SELECT COUNT(id) as total FROM don_hang")->fetch();
    $totalOrders = $resOrders['total'] ?? 0;

    $resCust = $pdo->query("SELECT COUNT(id) as total FROM nguoi_dung WHERE vai_tro = 'user'")->fetch();
    $totalCustomers = $resCust['total'] ?? 0;

    $resProd = $pdo->query("SELECT COUNT(id) as total FROM san_pham WHERE trang_thai = 1")->fetch();
    $totalProducts = $resProd['total'] ?? 0;

    // Top 5 sản phẩm bán chạy nhất
    $topProducts = $pdo->query("
        SELECT sp.ten_san_pham, sp.hinh_anh, SUM(ct.so_luong) as total_qty 
        FROM chi_tiet_don_hang ct 
        JOIN san_pham sp ON ct.san_pham_id = sp.id 
        GROUP BY sp.id 
        ORDER BY total_qty DESC 
        LIMIT 5
    ")->fetchAll();

    // 4 Đơn hàng gần đây nhất
    $recentOrders = $pdo->query("
        SELECT dh.id, nd.ho_ten, dh.tong_tien, dh.ngay_dat, 
        (SELECT GROUP_CONCAT(sp.ten_san_pham SEPARATOR ', ') 
         FROM chi_tiet_don_hang ctdh 
         JOIN san_pham sp ON ctdh.san_pham_id = sp.id 
         WHERE ctdh.don_hang_id = dh.id) as ds_san_pham
        FROM don_hang dh
        JOIN nguoi_dung nd ON dh.nguoi_dung_id = nd.id
        ORDER BY dh.ngay_dat DESC
        LIMIT 4
    ")->fetchAll();

} catch (PDOException $e) {
    $error_msg = "Lỗi kết nối dữ liệu!";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - The Muzik Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; }</style>
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

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-4 lg:p-8">
        <!-- HEADER -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Tổng quan hệ thống</h1>
                <p class="text-slate-500 text-sm italic">Chào mừng trở lại, quản trị viên Muzik Store!</p>
            </div>
            <div class="flex items-center gap-4 bg-white p-2 pr-4 rounded-full border border-slate-200 shadow-sm hover:shadow transition-shadow">
                <img src="../uploads/image/others/admin.jpg" onerror="this.src='../uploads/image/album/mck.jpg'" class="w-10 h-10 rounded-full object-cover border-2 border-indigo-100" alt="Avatar">
                <div class="hidden sm:block">
                    <p class="text-xs font-bold leading-none text-slate-800">DAPHUC</p>
                    <p class="text-[10px] text-indigo-500 font-bold uppercase mt-1">Administrator</p>
                </div>
            </div>
        </header>

        <!-- STAT CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl w-fit mb-4"><i class="fa-solid fa-dollar-sign text-xl"></i></div>
                <p class="text-slate-400 text-[11px] font-bold uppercase tracking-wider">Doanh thu</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= number_format($totalRevenue, 0, ',', '.') ?>đ</h3>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl w-fit mb-4"><i class="fa-solid fa-cart-shopping text-xl"></i></div>
                <p class="text-slate-400 text-[11px] font-bold uppercase tracking-wider">Đơn hàng</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= number_format($totalOrders) ?></h3>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="p-3 bg-purple-50 text-purple-600 rounded-xl w-fit mb-4"><i class="fa-solid fa-user text-xl"></i></div>
                <p class="text-slate-400 text-[11px] font-bold uppercase tracking-wider">Khách hàng</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= number_format($totalCustomers) ?></h3>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="p-3 bg-orange-50 text-orange-600 rounded-xl w-fit mb-4"><i class="fa-solid fa-box text-xl"></i></div>
                <p class="text-slate-400 text-[11px] font-bold uppercase tracking-wider">Sản phẩm CD</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= number_format($totalProducts) ?></h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- RECENT ORDERS -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800">Đơn hàng mới nhất</h3>
                    <a href="manager-order.php" class="text-[11px] text-indigo-600 font-bold uppercase hover:underline tracking-wider">Xem tất cả</a>
                </div>
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/50 text-slate-400 text-[10px] uppercase font-bold tracking-widest border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4">Mã đơn</th>
                                <th class="px-6 py-4">Khách hàng</th>
                                <th class="px-6 py-4">Sản phẩm</th>
                                <th class="px-6 py-4">Giá trị</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-50">
                            <?php if (!empty($recentOrders)): ?>
                                <?php foreach($recentOrders as $order): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-indigo-600">#DH-<?= $order['id'] ?></td>
                                    <td class="px-6 py-4 font-semibold text-slate-700"><?= htmlspecialchars($order['ho_ten']) ?></td>
                                    <td class="px-6 py-4 text-slate-500 italic max-w-[220px] truncate text-xs"><?= htmlspecialchars($order['ds_san_pham']) ?></td>
                                    <td class="px-6 py-4 font-bold text-slate-900"><?= number_format($order['tong_tien'], 0, ',', '.') ?>đ</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400 italic">Chưa có giao dịch nào gần đây</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TOP PRODUCTS -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-8 border-b border-slate-50 pb-4">
                    <h3 class="font-bold text-slate-800">Top 5 bán chạy</h3>
                    <i class="fa-solid fa-fire text-orange-500"></i>
                </div>
                <div class="space-y-6">
                    <?php if (!empty($topProducts)): ?>
                        <?php foreach($topProducts as $product): ?>
                        <div class="flex items-center gap-4 group cursor-default">
                            <img src="../uploads/image/album/<?= htmlspecialchars($product['hinh_anh']) ?>" 
                                 onerror="this.src='../assets/img/default-album.png';"
                                 class="w-12 h-12 rounded-xl object-cover bg-slate-100 shadow-sm group-hover:scale-105 transition-transform">
                            
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-800 truncate"><?= htmlspecialchars($product['ten_san_pham']) ?></p>
                                <p class="text-[10px] text-slate-400 font-medium uppercase tracking-tight">Đã bán: <?= $product['total_qty'] ?></p>
                            </div>
                            
                            <div class="flex flex-col items-end shrink-0">
                                <span class="text-[10px] font-black text-indigo-600"><?= $product['total_qty'] ?></span>
                                <div class="w-10 h-1 bg-slate-100 rounded-full mt-1 overflow-hidden">
                                    <div class="bg-indigo-500 h-full w-[85%] rounded-full shadow-[0_0_8px_rgba(79,70,229,0.4)]"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="py-10 text-center">
                            <i class="fa-solid fa-box-open text-slate-200 text-3xl mb-3 block"></i>
                            <p class="text-xs text-slate-400 italic">Chưa có dữ liệu sản phẩm</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>