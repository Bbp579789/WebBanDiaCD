<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Nếu chưa đăng nhập qua URL bí mật, không cho xem nội dung
    die("Bạn phải đăng nhập bằng URL bí mật để truy cập.");
}
?>

<?php
ob_start();
// session_start();
require_once '../config/database.php';

// 1. KẾT NỐI DATABASE (Sử dụng PDO)
$host = 'localhost';
$db   = 'webbandiacd';
$user = 'root'; 
$pass = ''; 
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // --- FIX LỖI WARNING ---
    // Khởi tạo biến để Sidebar nhận diện trang hiện tại
    $current_page = 'profit.php'; 
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // 2. TRUY VẤN DỮ LIỆU LỢI NHUẬN
    // Lấy giá bán từ bảng san_pham và giá nhập mới nhất từ bảng chi_tiet_phieu_nhap
    $sql = "SELECT sp.id, sp.ten_san_pham, sp.gia AS gia_ban, sp.hinh_anh, tl.ten_the_loai,
            (SELECT don_gia FROM chi_tiet_phieu_nhap 
             WHERE san_pham_id = sp.id 
             ORDER BY id DESC LIMIT 1) AS gia_nhap
            FROM san_pham sp
            LEFT JOIN the_loai tl ON sp.the_loai_id = tl.id";

    if ($search !== '') {
        $sql .= " WHERE sp.ten_san_pham LIKE :search";
    }

    $stmt = $pdo->prepare($sql);
    if ($search !== '') {
        $stmt->bindValue(':search', "%$search%");
    }
    $stmt->execute();
    $products = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tỷ lệ lợi nhuận | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
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

    <!-- MAIN -->
    <main class="flex-1 p-8">
        <header class="mb-10">
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight text-indigo-600 italic">Tỷ lệ lợi nhuận</h1>
            <p class="text-slate-500 text-sm italic">Phân tích giá nhập, giá bán và biên lợi nhuận</p>
        </header>

        <!-- SEARCH FORM (Nhấn Enter để tìm kiếm) -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 mb-8 shadow-sm">
            <form action="profit.php" method="GET" class="flex gap-4">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-slate-400"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Tìm tên sản phẩm để xem lợi nhuận..." 
                           class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition">Lọc</button>
            </form>
        </div>

        <!-- TABLE -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b">
                    <tr>
                        <th class="px-6 py-4">Sản phẩm</th>
                        <th class="px-6 py-4">Giá nhập</th>
                        <th class="px-6 py-4">Giá bán</th>
                        <th class="px-6 py-4">Lợi nhuận</th>
                        <th class="px-6 py-4">Tỷ lệ (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($products as $p): 
                        $gn = (float)($p['gia_nhap'] ?? 0);
                        $gb = (float)($p['gia_ban'] ?? 0);
                        $ln = $gb - $gn;
                        // Tránh lỗi chia cho 0
                        $rate = ($gn > 0) ? ($ln / $gn) * 100 : 0;
                        
                        $rateColor = ($rate >= 50) ? 'emerald' : (($rate >= 20) ? 'blue' : 'rose');
                    ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <img src="../uploads/image/album/<?= $p['hinh_anh'] ?>" class="w-10 h-10 rounded-lg object-cover">
                                <div>
                                    <p class="font-bold text-slate-800 text-sm"><?= $p['ten_san_pham'] ?></p>
                                    <p class="text-[10px] text-slate-400 uppercase"><?= $p['ten_the_loai'] ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-slate-500"><?= number_format($gn) ?>đ</td>
                        <td class="px-6 py-4 text-sm font-bold text-slate-800"><?= number_format($gb) ?>đ</td>
                        <td class="px-6 py-4 text-sm font-bold text-indigo-600"><?= number_format($ln) ?>đ</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 bg-<?= $rateColor ?>-50 text-<?= $rateColor ?>-600 rounded-full text-xs font-black">
                                +<?= round($rate, 1) ?>%
                            </span>
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