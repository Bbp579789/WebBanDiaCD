<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Nếu chưa đăng nhập qua URL bí mật, không cho xem nội dung
    die("Bạn phải đăng nhập bằng URL bí mật để truy cập.");
}
?>

<?php
// 1. Kết nối Database
$host = 'localhost';
$db = 'webbandiacd';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Định nghĩa trang hiện tại để menu sáng lên
$current_page = basename($_SERVER['PHP_SELF']);

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // --- LẤY DỮ LIỆU THỐNG KÊ (Giữ nguyên) ---
    $totalCount = $pdo->query("SELECT COUNT(*) FROM san_pham")->fetchColumn();
    $activeCount = $pdo->query("SELECT COUNT(*) FROM san_pham WHERE trang_thai = 1")->fetchColumn();
    $lowStockCount = $pdo->query("SELECT COUNT(*) FROM san_pham WHERE so_luong < 10")->fetchColumn();
    $totalStock = $pdo->query("SELECT SUM(so_luong) FROM san_pham")->fetchColumn() ?? 0;

    // --- LẤY DANH SÁCH HỖ TRỢ (Giữ nguyên) ---
    $artists = $pdo->query("SELECT * FROM nghe_si ORDER BY ten_nghe_si ASC")->fetchAll();
    $categories = $pdo->query("SELECT * FROM the_loai WHERE trang_thai = 1")->fetchAll();

    // --- TRUY VẤN DANH SÁCH SẢN PHẨM (Bản chuẩn nhất) ---
    // Join chi_tiet_san_pham để lấy mo_ta_san_pham cho Modal sửa
  $sql = "
    SELECT 
        sp.*,
        tl.ten_the_loai,
        ns.ten_nghe_si,
        ct.mo_ta_san_pham,
        -- giá nhập trung bình có trọng số từ chi_tiet_phieu_nhap (don_gia, so_luong)
        (
            SELECT 
                CASE WHEN SUM(ctp.so_luong) > 0 
                    THEN ROUND(SUM(ctp.so_luong * ctp.don_gia) / SUM(ctp.so_luong), 2) 
                    ELSE NULL 
                END
            FROM chi_tiet_phieu_nhap ctp
            WHERE ctp.san_pham_id = sp.id
        ) AS avg_don_gia
    FROM san_pham sp
    LEFT JOIN the_loai tl ON sp.the_loai_id = tl.id
    LEFT JOIN nghe_si ns ON sp.nghe_si_id = ns.id
    LEFT JOIN chi_tiet_san_pham ct ON sp.id = ct.san_pham_id
    ORDER BY sp.id DESC
    ";
    $products = $pdo->query($sql)->fetchAll();

} catch (\PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm CD - The Muzik Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
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
                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-4 px-3 tracking-widest">Menu quản trị
                    </p>
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
                        <a href="<?= $item['file'] ?>"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 <?= $active ?>">
                            <i class="fa-solid <?= $item['icon'] ?> w-5 text-center"></i>
                            <span class="text-sm"><?= $item['label'] ?></span>
                        </a>
                    <?php endforeach; ?>

                    <nav class="mt-10 space-y-1">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-4 px-3 tracking-widest mt-10">Hệ
                            thống</p>
                        <a href="../index.php"
                            class="flex items-center gap-3 px-3 py-2.5 text-slate-500 hover:bg-slate-50 rounded-xl transition-all text-sm font-medium">
                            <i class="fa-solid fa-house w-5"></i> <span>Trang chủ</span>
                        </a>
                        <a href="../includes/logout-adm.php"
                            class="flex items-center gap-3 px-3 py-2.5 text-red-500 hover:bg-red-50 rounded-xl transition-all text-sm font-medium">
                            <i class="fa-solid fa-right-from-bracket w-5"></i> <span>Đăng xuất</span>
                        </a>
                    </nav>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4 lg:p-8">
            <!-- HEADER -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight italic uppercase">Manager CD Store</h1>
                    <p class="text-slate-500 text-sm">Kho lưu trữ <?= number_format($totalCount) ?> Album nhạc</p>
                </div>
                <div class="flex gap-2 text-sm font-bold">
                   
                    <button onclick="openAddModal()"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition flex items-center gap-2">
                        <i class="fa-solid fa-plus-circle"></i> Thêm sản phẩm
                    </button>
                </div>
            </div>

            <!-- STAT CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 text-sm">
                <div
                    class="bg-white p-5 rounded-2xl border border-slate-200 flex items-center gap-4 hover:shadow-md transition">
                    <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl"><i class="fa-solid fa-box text-xl"></i>
                    </div>
                    <div>
                        <p class="text-slate-400 font-bold uppercase text-[10px]">Tổng SP</p>
                        <p class="text-xl font-bold"><?= $totalCount ?></p>
                    </div>
                </div>
                <div
                    class="bg-white p-5 rounded-2xl border border-slate-200 flex items-center gap-4 hover:shadow-md transition text-emerald-600">
                    <div class="p-3 bg-emerald-50 rounded-xl"><i class="fa-solid fa-check-circle text-xl"></i></div>
                    <div>
                        <p class="text-slate-400 font-bold uppercase text-[10px]">Hoạt động</p>
                        <p class="text-xl font-bold"><?= $activeCount ?></p>
                    </div>
                </div>
                <div
                    class="bg-white p-5 rounded-2xl border border-slate-200 flex items-center gap-4 hover:shadow-md transition text-red-500">
                    <div class="p-3 bg-red-50 rounded-xl"><i class="fa-solid fa-triangle-exclamation text-xl"></i></div>
                    <div>
                        <p class="text-slate-400 font-bold uppercase text-[10px]">Cảnh báo kho</p>
                        <p class="text-xl font-bold"><?= $lowStockCount ?></p>
                    </div>
                </div>
                <div
                    class="bg-white p-5 rounded-2xl border border-slate-200 flex items-center gap-4 hover:shadow-md transition text-indigo-500">
                    <div class="p-3 bg-indigo-50 rounded-xl"><i class="fa-solid fa-warehouse text-xl"></i></div>
                    <div>
                        <p class="text-slate-400 font-bold uppercase text-[10px]">Tồn kho</p>
                        <p class="text-xl font-bold"><?= number_format($totalStock) ?></p>
                    </div>
                </div>
            </div>

            <!-- BẢNG SẢN PHẨM -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-50 flex justify-between items-center flex-wrap gap-4">
                    <h3 class="font-bold text-slate-800">Danh mục đĩa CD</h3>
                    <div class="flex gap-4">
                            <input id="searchInput" type="text" placeholder="Tìm tên CD..."
                                class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm w-64 transition">
                        </div>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead
                        class="bg-slate-50/50 text-slate-500 text-[10px] uppercase font-black tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4">Mã</th>
                            <th class="px-6 py-4">Sản phẩm</th>
                            <!-- <th class="px-6 py-4 text-center">Giá nhập</th> -->
                            <th class="px-6 py-4 text-center">Giá bán</th>
                            <th class="px-6 py-4 text-center">Kho</th>
                            <th class="px-6 py-4">Trạng thái</th>
                            <th class="px-6 py-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php foreach ($products as $p): ?>
                            <tr class="hover:bg-slate-50/50 transition duration-150 group">
                                <td class="px-6 py-4 font-bold text-slate-400 text-xs">#<?= $p['id'] ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3 text-sm">
                                        <img src="../uploads/image/album/<?= htmlspecialchars($p['hinh_anh']) ?>"
                                            class="w-11 h-11 rounded-xl object-cover bg-white shadow-sm group-hover:scale-105 transition"
                                            onerror="this.src='../assets/img/default-album.png'">
                                        <div class="max-w-[150px]">
                                            <p class="font-bold text-slate-800 truncate">
                                                <?= htmlspecialchars($p['ten_san_pham']) ?>
                                            </p>
                                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">
                                                <?= $p['ten_the_loai'] ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Giá bán (tính theo công thức) -->
                                <td class="px-6 py-4 text-center font-black text-indigo-600">
                                    <?php
                                    $purchase = (isset($p['gia_nhap']) && (float)$p['gia_nhap'] > 0)
                                        ? (float)$p['gia_nhap']
                                        : (isset($p['avg_don_gia']) && $p['avg_don_gia'] !== null
                                            ? (float)$p['avg_don_gia']
                                            : 0);

                                    $rate = 30.0;
                                    if (isset($p['ty_le']) && is_numeric($p['ty_le'])) {
                                        $rate = (float)$p['ty_le'];
                                    } elseif (!empty($p['gia']) && !empty($p['gia_nhap']) && (float)$p['gia_nhap'] > 0) {
                                        $rate = ((float)$p['gia'] - (float)$p['gia_nhap']) / (float)$p['gia_nhap'] * 100;
                                    }

                                    if (isset($p['gia']) && (float)$p['gia'] > 0) {
                                        $selling = (float)$p['gia'];
                                    } else {
                                        $selling = round($purchase * (1 + $rate / 100));
                                    }
                                ?>
                                <?= number_format($selling, 0, ',', '.') ?> đ
                            </td>

                            <td class="px-6 py-4 text-center">
                                <span
                                    class="px-2.5 py-1 rounded-lg font-bold text-xs <?= $p['so_luong'] < 10 ? 'bg-red-50 text-red-500' : 'bg-slate-50 text-slate-600' ?>">
                                    <?= $p['so_luong'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase <?= $p['trang_thai'] ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' ?>">
                                    <?= $p['trang_thai'] ? 'Hoạt động' : 'Tạm khóa' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="detail_adm.php?id=<?= $p['id'] ?>"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-indigo-600 hover:text-white transition shadow-sm"><i
                                            class="fa-solid fa-eye text-xs"></i></a>
                                    <button
                                        onclick='openEditModal(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-blue-500 hover:bg-blue-600 hover:text-white transition shadow-sm">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    <button onclick="deleteProduct(<?= $p['id'] ?>)"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-red-600 hover:text-white transition shadow-sm"><i
                                            class="fa-solid fa-trash text-xs"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- MODAL THÊM / SỬA SẢN PHẨM -->
    <div id="modalAddProduct"
        class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in duration-200">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 id="modalTitle" class="font-bold text-lg text-slate-800">Thêm sản phẩm CD mới</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-red-500 transition"><i
                        class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <form action="them_sp.php" method="POST" enctype="multipart/form-data"
                class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6" id="formAddProduct">
                <!-- productId dùng để phân biệt thêm/sửa, để trống khi thêm mới -->
                <input type="hidden" name="productId" id="productId">

                <div class="space-y-4">
                    <!-- Tên sản phẩm -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Tên Album CD</label>
                        <input type="text" name="productName" id="pName" required
                            class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Danh mục -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Danh mục</label>
                            <select name="categoryId" id="pCategory"
                                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= $cat['ten_the_loai'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- nghệ sĩ -->
                        <!-- Trong Modal Edit -->
<!-- Phải chính xác như thế này -->
<div>
    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Tên Nghệ sĩ</label>
   <input type="text" name="artistName" id="edit_artist_input" required 
       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none">
</div>
                        <!-- Đơn vị tính -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Đơn vị tính</label>
                            <input type="text" name="unit" id="pUnit" placeholder="Cái, Bộ, Đĩa..."
                                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Giá vốn -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Giá vốn
                                (Nhập)</label>
                            <input type="number" name="costPrice" id="p_cost" required step="0.01" min="0"
                                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none">
                        </div>
                        <!-- Tỷ lệ lợi nhuận -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Lợi nhuận
                                (%)</label>
                            <input type="number" name="profitRate" id="p_rate" value="30" required step="0.1"
                                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none">
                        </div>
                    </div>

                    <!-- Số lượng tồn ban đầu -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Số lượng tồn ban
                            đầu</label>
                        <input type="number" name="stock" id="p_stock" required min="0"
                            class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none font-bold text-indigo-600">
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Hiện trạng -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Hiện trạng</label>
                        <select name="status" id="pStatus"
                            class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none font-bold">
                            <option value="1">Hiển thị (Đang bán)</option>
                            <option value="0">Ẩn (Ngừng bán)</option>
                        </select>
                    </div>

                    <!-- Mô tả -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Mô tả sản phẩm</label>
                        <textarea name="desc" id="edit_desc" rows="3"
                            class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none resize-none"></textarea>
                    </div>

                    <!-- Hình ảnh -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Hình ảnh Album</label>
                        <div
                            class="border-2 border-dashed border-indigo-100 rounded-2xl p-4 text-center hover:border-indigo-400 transition relative bg-indigo-50/20">
                            <input type="file" name="productImage" class="absolute inset-0 opacity-0 cursor-pointer">
                            <i class="fa-solid fa-cloud-arrow-up text-2xl text-indigo-300 mb-1"></i>
                            <p class="text-[10px] text-slate-400 font-bold uppercase">Chọn ảnh</p>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Giá bán (dự kiến)</label>
                        <input type="text" id="p_gia_preview" readonly class="w-full px-4 py-2 bg-slate-100 border border-slate-200 rounded-xl text-sm">
                        <input type="hidden" name="gia" id="p_gia">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Tổng tiền nhập</label>
                        <input type="text" id="p_total_preview" readonly class="w-full px-4 py-2 bg-slate-100 border border-slate-200 rounded-xl text-sm">
                        <input type="hidden" name="total_input_value" id="p_total">
                    </div>
                </div>

                <div class="md:col-span-2 flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeModal()"
                        class="px-6 py-2 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition text-sm">Hủy</button>
                    <button type="submit"
                        class="px-8 py-2 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700 transition text-sm">Xác
                        nhận Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL SỬA SẢN PHẨM -->
    <div id="modalEditProduct" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[60] flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800">Chỉnh sửa thông tin Album</h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-red-500 transition">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <form action="them_sp.php" method="POST" enctype="multipart/form-data" class="p-8">
            <input type="hidden" name="productId" id="edit_id">
            <input type="hidden" name="oldImage" id="edit_oldImage">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Tên Album</label>
                        <input type="text" name="productName" id="edit_name" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-sm">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Danh mục (Thể loại)</label>
                        <select name="categoryId" id="edit_category" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= $cat['ten_the_loai'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Tên Nghệ sĩ</label>
                        <input type="text" name="artistName" id="edit_artist" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Giá vốn (VNĐ)</label>
                            <input type="number" name="costPrice" id="edit_cost" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Lợi nhuận (%)</label>
                            <input type="number" name="profitRate" id="edit_rate" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Số lượng tồn kho</label>
                        <input type="number" name="stock" id="edit_stock" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-indigo-600">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Trạng thái website</label>
                        <select name="status" id="edit_status" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold">
                            <option value="1" class="text-emerald-600">Hoạt động (Đang bán)</option>
                            <option value="0" class="text-red-600">Khóa sản phẩm (Ẩn)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Mô tả sản phẩm</label>
                        <textarea name="desc" id="edit_desc" rows="3" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Hình ảnh hiện tại</label>
                        <div class="flex items-center gap-4">
                            <img id="edit_preview" src="" class="w-12 h-12 rounded border object-cover">
                            <input type="file" name="productImage" class="text-[10px]">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thêm preview Giá bán và Tổng tiền nhập cho modal Edit -->
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Giá bán (dự kiến)</label>
                    <input type="text" id="edit_gia_preview" readonly class="w-full px-4 py-2 bg-slate-100 border border-slate-200 rounded-xl text-sm">
                    <input type="hidden" name="gia" id="edit_gia">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Tổng tiền nhập</label>
                    <input type="text" id="edit_total_preview" readonly class="w-full px-4 py-2 bg-slate-100 border border-slate-200 rounded-xl text-sm">
                    <input type="hidden" name="total_input_value" id="edit_total">
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8 pt-6 border-t">
                <button type="button" onclick="closeEditModal()" class="px-6 py-2 text-slate-500 font-bold hover:bg-slate-100 rounded-xl text-sm transition">Hủy</button>
                <button type="submit" class="px-8 py-2 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700 text-sm transition">Lưu cập nhật</button>
            </div>
        </form>
    </div>
</div>

    <script>
    // --- PHẦN MODAL THÊM MỚI ---
    function openAddModal() {
        document.getElementById('modalTitle').innerText = "Thêm sản phẩm CD mới";
        document.getElementById('productId').value = ""; 
        const form = document.querySelector('#modalAddProduct form');
        form.reset();
        // set defaults
        document.getElementById('p_rate').value = 30;
        document.getElementById('p_cost').value = 0;
        document.getElementById('p_stock').value = 0;
        updateAddCalculations();
        document.getElementById('modalAddProduct').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('modalAddProduct').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // --- PHẦN MODAL CHỈNH SỬA ---
  function openEditModal(product){
    // allow passing JSON string or object
    try {
        if (typeof product === 'string') product = JSON.parse(product);
    } catch(e){
        console.error('openEditModal: invalid product JSON', e, product);
    }

    console.log('openEditModal product:', product);

    const modal = document.getElementById('modalEditProduct');
    if (!modal) { console.error('Modal element not found: modalEditProduct'); return; }
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // safe setters
    const setIf = (id, val) => {
        const el = document.getElementById(id);
        if(!el) { console.warn('Missing element', id); return; }
        el.value = val ?? '';
    };

    setIf('edit_id', product.id ?? '');
    setIf('edit_name', product.ten_san_pham ?? '');
    setIf('edit_category', product.the_loai_id ?? '');
    setIf('edit_artist', product.ten_nghe_si ?? product.artistName ?? '');
    setIf('edit_stock', product.so_luong ?? 0);
    setIf('edit_status', product.trang_thai ?? 1);
    setIf('edit_desc', product.mo_ta_san_pham ?? product.mo_ta ?? '');

    // Giá vốn: ưu tiên avg_don_gia, fallback gia_nhap, fallback 0
    const costPrice = parseFloat(product.avg_don_gia ?? product.gia_nhap ?? product.gia_nhap_old ?? 0) || 0;
    setIf('edit_cost', costPrice);

    // Tỷ lệ lợi nhuận: try ty_le, or compute from gia/gia_nhap, or fallback to 30
    let rate = 30;
    if (product.ty_le !== undefined && product.ty_le !== null && !isNaN(parseFloat(product.ty_le))) {
        rate = parseFloat(product.ty_le);
    } else if (product.profitRate !== undefined && !isNaN(parseFloat(product.profitRate))) {
        rate = parseFloat(product.profitRate);
    } else if (product.gia && costPrice > 0) {
        rate = ((parseFloat(product.gia) - costPrice) / costPrice) * 100;
    }
    setIf('edit_rate', Math.round(rate * 10) / 10);

    // image preview
    const imgPreview = document.getElementById('edit_preview');
    if (imgPreview) {
        imgPreview.src = product.hinh_anh ? "../uploads/image/album/" + product.hinh_anh : "../assets/img/default-album.png";
    }
    setIf('edit_oldImage', product.hinh_anh ?? '');

    // immediately update previews (hidden + visual)
    updateEditCalculations();
    console.log('Modal populated, costRate:', {costPrice, rate});
}

    function closeEditModal() {
        document.getElementById('modalEditProduct').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // --- ĐÓNG KHI CLICK RA NGOÀI ---
    window.onclick = function(event) {
        const modalAdd = document.getElementById('modalAddProduct');
        const modalEdit = document.getElementById('modalEditProduct');
        if (event.target == modalAdd) closeModal();
        if (event.target == modalEdit) closeEditModal();
    }

    // --- XÓA SẢN PHẨM ---
    function deleteProduct(id) {
        if (confirm("Bạn có chắc chắn muốn xóa album này không?")) {
            window.location.href = "xoa_sp.php?id=" + id;
        }
    }

    // --- Unified price & total calculation for Add modal ---
    function formatCurrency(n){
    return new Intl.NumberFormat('vi-VN').format(Math.round(n)) + ' đ';
}
function computeSellingPrice(cost, rate){
    cost = parseFloat(cost) || 0;
    rate = parseFloat(rate) || 0;
    return cost * (1 + rate / 100);
}

    function updateAddCalculations(){
        const cost = parseFloat(document.getElementById('p_cost')?.value || 0);
        const rate = parseFloat(document.getElementById('p_rate')?.value || 0);
        const qty = parseInt(document.getElementById('p_stock')?.value || 0);
        const gia = computeSellingPrice(cost, rate);
        const total = cost * qty;
        document.getElementById('p_gia').value = Math.round(gia);
        document.getElementById('p_gia_preview').value = formatCurrency(gia);
        document.getElementById('p_total').value = Math.round(total);
        document.getElementById('p_total_preview').value = formatCurrency(total);
    }

    ['p_cost','p_rate','p_stock'].forEach(id=>{
        document.getElementById(id)?.addEventListener('input', updateAddCalculations);
    });

    function updateEditCalculations(){
    const costEl = document.getElementById('edit_cost');
    const rateEl = document.getElementById('edit_rate');
    const stockEl = document.getElementById('edit_stock');

    const cost = parseFloat(costEl?.value || 0);
    const rate = parseFloat(rateEl?.value || 0);
    const qty = parseInt(stockEl?.value || 0);

    const selling = computeSellingPrice(cost, rate);
    const total = cost * qty;

    const elGiaHidden = document.getElementById('edit_gia');
    const elGiaPreview = document.getElementById('edit_gia_preview');
    const elTotalHidden = document.getElementById('edit_total');
    const elTotalPreview = document.getElementById('edit_total_preview');

    if(elGiaHidden) elGiaHidden.value = Math.round(selling);
    if(elGiaPreview) elGiaPreview.value = formatCurrency(selling);
    if(elTotalHidden) elTotalHidden.value = Math.round(total);
    if(elTotalPreview) elTotalPreview.value = formatCurrency(total);

    // debug
    console.debug('updateEditCalculations', {cost, rate, qty, selling, total});
}

// attach listeners safely after DOM is ready (in case script moves)
document.addEventListener('DOMContentLoaded', function(){
    ['edit_cost','edit_rate','edit_stock'].forEach(id=>{
        const el = document.getElementById(id);
        if(el) el.addEventListener('input', updateEditCalculations);
    });
});

    // ensure preview updated when opening modal
    function openAddModal() {
        document.getElementById('modalTitle').innerText = "Thêm sản phẩm CD mới";
        document.getElementById('productId').value = "";
        const form = document.querySelector('#modalAddProduct form');
        form.reset();
        // set defaults
        document.getElementById('p_rate').value = 30;
        document.getElementById('p_cost').value = 0;
        document.getElementById('p_stock').value = 0;
        updateAddCalculations();
        document.getElementById('modalAddProduct').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // --- Search table by product name or selling price ---
(function(){
    document.addEventListener('DOMContentLoaded', function(){
        const input = document.getElementById('searchInput');
        if (!input) return;

        function performSearch(){
            const termRaw = input.value.trim();
            const term = termRaw.toLowerCase();
            const numericTerm = termRaw.replace(/[^\d]/g, '');

            const rows = document.querySelectorAll('table tbody tr');
            rows.forEach(r=>{
                // lấy tên sản phẩm: tránh class chứa dấu [] -> chọn p đầu tiên trong ô
                const name = r.querySelector('td:nth-child(2) p')?.innerText?.toLowerCase() || '';
                const priceCell = r.querySelector('td:nth-child(3)');
                const priceText = priceCell?.innerText?.toLowerCase() || '';
                const priceDigits = priceText.replace(/[^\d]/g, '');

                let visible = false;
                if(term === '') {
                    visible = true;
                } else {
                    if(name.includes(term)) visible = true;
                    if(!visible && numericTerm !== '' && priceDigits.includes(numericTerm)) visible = true;
                    if(!visible && priceText.includes(term)) visible = true;
                }
                r.style.display = visible ? '' : 'none';
            });
        }

        input.addEventListener('input', performSearch);

        // Enter để tìm ngay, Escape để xóa
        input.addEventListener('keydown', function(e){
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            } else if (e.key === 'Escape') {
                input.value = '';
                performSearch();
            }
        });
    });
})();
    </script>
</body>

</html>