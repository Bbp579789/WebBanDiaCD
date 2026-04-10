<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Nếu chưa đăng nhập qua URL bí mật, không cho xem nội dung
    die("Bạn phải đăng nhập bằng URL bí mật để truy cập.");
}
?>

<?php
// session_start();

// --- DB connection + options (PDO) ---
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

// current page for sidebar active state
$current_page = basename($_SERVER['PHP_SELF']);

// Lấy ID sản phẩm an toàn
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header('Location: manager-cd.php');
    exit;
}

// Truy vấn chi tiết sản phẩm
try {
    $stmt = $pdo->prepare("
        SELECT sp.*, tl.ten_the_loai, ns.ten_nghe_si, ct.mo_ta_san_pham, ct.nam_phat_hanh, ct.hang_dia,
               (SELECT COALESCE(SUM(so_luong),0) FROM chi_tiet_don_hang WHERE san_pham_id = sp.id) AS da_ban,
               (SELECT don_gia FROM chi_tiet_phieu_nhap WHERE san_pham_id = sp.id ORDER BY id DESC LIMIT 1) AS gia_nhap
        FROM san_pham sp
        LEFT JOIN the_loai tl ON sp.the_loai_id = tl.id
        LEFT JOIN nghe_si ns ON sp.nghe_si_id = ns.id
        LEFT JOIN chi_tiet_san_pham ct ON sp.id = ct.san_pham_id
        WHERE sp.id = ?
        LIMIT 1
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
} catch (\PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

if (!$product) {
    die("Sản phẩm không tồn tại.");
}

// Tính toán an toàn tỉ lệ lợi nhuận
$gia_ban = isset($product['gia']) ? (float)$product['gia'] : 0.0;
$gia_nhap = isset($product['gia_nhap']) && is_numeric($product['gia_nhap']) && (float)$product['gia_nhap'] > 0
    ? (float)$product['gia_nhap']
    : max(0.0, $gia_ban * 0.7); // fallback nếu chưa có giá nhập

$loi_nhuan = $gia_ban - $gia_nhap;
$ti_le_loi_nhuan = $gia_nhap > 0 ? ($loi_nhuan / $gia_nhap) * 100 : 0;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết: <?= htmlspecialchars($product['ten_san_pham'] ?? 'Sản phẩm') ?></title>
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
                <img src="../uploads/image/others/music.jpg" class="w-10 h-10 object-contain" alt="Logo">
                <span class="font-bold text-xl tracking-tight text-indigo-600">The Muzik Store</span>
            </div>

            <nav class="space-y-1">
                <p class="text-xs font-semibold text-slate-400 uppercase mb-4 px-3">Menu</p>
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
                        ? 'bg-indigo-50 text-indigo-600 font-bold'
                        : 'text-slate-500 hover:bg-slate-100 font-medium';
                ?>
                    <a href="<?= htmlspecialchars($item['file']) ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl transition <?= $active ?>">
                        <i class="fa-solid <?= htmlspecialchars($item['icon']) ?> w-5"></i>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <nav class="mt-10 space-y-1">
                <p class="text-[10px] font-bold text-slate-400 uppercase mb-4 px-3 tracking-widest">Hệ thống</p>
                <a href="../index.php" class="flex items-center gap-3 px-3 py-2 text-slate-500 hover:bg-slate-100 rounded-xl transition font-medium">
                    <i class="fa-solid fa-house w-5"></i> <span>Trang chủ</span>
                </a>
                <a href="../includes/logout-adm.php" class="flex items-center gap-3 px-3 py-2 text-red-500 hover:bg-red-50 rounded-xl transition font-medium">
                    <i class="fa-solid fa-right-from-bracket w-5"></i> <span>Đăng xuất</span>
                </a>
            </nav>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-4 lg:p-8">
        <header class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-4">
                <a href="manager-cd.php" class="p-2 hover:bg-slate-200 rounded-full transition">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <h1 class="text-2xl font-bold text-slate-800">Chi tiết sản phẩm</h1>
            </div>
            <div class="flex items-center gap-3 bg-white p-2 pr-4 rounded-full border border-slate-200 shadow-sm">
                <img src="../assets/img/image.png" class="w-10 h-10 rounded-full object-cover" alt="Avatar">
                <div class="hidden sm:block text-sm">
                    <p class="font-bold leading-none">Quản trị viên</p>
                    <p class="text-slate-400 text-[10px] uppercase">Admin</p>
                </div>
            </div>
        </header>

        <!-- PRODUCT DETAIL CARD -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 lg:p-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                <!-- Image -->
                <div class="lg:col-span-5">
                    <div class="sticky top-8">
                        <img src="../uploads/image/album/<?= htmlspecialchars($product['hinh_anh'] ?? '') ?>" 
                             onerror="this.src='../assets/img/phong-canh/7.jpg';"
                             class="w-full aspect-square object-cover rounded-2xl shadow-md border border-slate-100" 
                             alt="<?= htmlspecialchars($product['ten_san_pham'] ?? '') ?>">
                        <div class="mt-4 flex gap-2 justify-center">
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-600 text-xs font-bold rounded-full uppercase">
                                <?= htmlspecialchars($product['hang_dia'] ?? '') ?>
                            </span>
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-600 text-xs font-bold rounded-full uppercase">
                                Năm: <?= htmlspecialchars($product['nam_phat_hanh'] ?? 'N/A') ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Info -->
                <div class="lg:col-span-7 space-y-6">
                    <div>
                        <h2 class="text-3xl font-extrabold text-slate-800 mb-2"><?= htmlspecialchars($product['ten_san_pham'] ?? '') ?></h2>
                        <p class="text-indigo-600 font-semibold text-lg italic">Nghệ sĩ: <?= htmlspecialchars($product['ten_nghe_si'] ?? '') ?></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <p class="text-slate-400 text-xs uppercase font-bold mb-1">Giá bán hiện tại</p>
                            <p class="text-2xl font-bold text-slate-800"><?= number_format($gia_ban, 0, ',', '.') ?> đ</p>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <p class="text-slate-400 text-xs uppercase font-bold mb-1">Giá nhập kho</p>
                            <p class="text-2xl font-bold text-slate-500"><?= number_format($gia_nhap, 0, ',', '.') ?> đ</p>
                        </div>
                    </div>

                    <div class="space-y-4 text-sm">
                        <div class="flex items-center py-3 border-b border-slate-100">
                            <span class="w-32 font-semibold text-slate-500">Danh mục:</span>
                            <span class="text-slate-800"><?= htmlspecialchars($product['ten_the_loai'] ?? '') ?></span>
                        </div>
                        <div class="flex items-center py-3 border-b border-slate-100">
                            <span class="w-32 font-semibold text-slate-500">Tỷ lệ lợi nhuận:</span>
                            <span class="px-2 py-1 bg-orange-100 text-orange-600 font-bold rounded-lg">
                                <?= round($ti_le_loi_nhuan, 1) ?>%
                            </span>
                        </div>
                        <div class="flex items-center py-3 border-b border-slate-100">
                            <span class="w-32 font-semibold text-slate-500">Tồn kho:</span>
                            <span class="text-slate-800 font-bold"><?= (int)($product['so_luong'] ?? 0) ?> sản phẩm</span>
                        </div>
                        <div class="flex items-center py-3 border-b border-slate-100">
                            <span class="w-32 font-semibold text-slate-500">Đã bán:</span>
                            <span class="text-emerald-600 font-bold"><?= (int)($product['da_ban'] ?? 0) ?> sản phẩm</span>
                        </div>
                        <div class="pt-4">
                            <span class="block font-semibold text-slate-500 mb-2">Mô tả sản phẩm:</span>
                            <p class="text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-xl italic">
                                <?= nl2br(htmlspecialchars($product['mo_ta_san_pham'] ?? 'Chưa có mô tả cho sản phẩm này.')) ?>
                            </p>
                        </div>
                    </div>

                 
                  
                </div>

            </div>
        </div>
    </main>
</div>

<script src="../assets/js/account-admin.js"></script>
<script src="../assets/js/noti.js"></script>
</body>
</html>