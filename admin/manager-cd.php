<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Bạn phải đăng nhập bằng URL bí mật để truy cập.");
}

// 1. Kết nối Database
$host = 'localhost'; $db = 'webbandiacd'; $user = 'root'; $pass = ''; $charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$current_page = basename($_SERVER['PHP_SELF']);

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Thống kê
    $totalCount = $pdo->query("SELECT COUNT(*) FROM san_pham")->fetchColumn();
    $activeCount = $pdo->query("SELECT COUNT(*) FROM san_pham WHERE trang_thai = 1")->fetchColumn();
    $lowStockCount = $pdo->query("SELECT COUNT(*) FROM san_pham WHERE so_luong < 10")->fetchColumn();
    $totalStock = $pdo->query("SELECT SUM(so_luong) FROM san_pham")->fetchColumn() ?? 0;

    $categories = $pdo->query("SELECT * FROM the_loai WHERE trang_thai = 1")->fetchAll();

    // Truy vấn sản phẩm
    $sql = "SELECT sp.*, tl.ten_the_loai, ns.ten_nghe_si, ct.mo_ta_san_pham,
            (SELECT ROUND(SUM(ctp.so_luong * ctp.don_gia) / SUM(ctp.so_luong), 2) 
             FROM chi_tiet_phieu_nhap ctp WHERE ctp.san_pham_id = sp.id) AS avg_don_gia
            FROM san_pham sp
            LEFT JOIN the_loai tl ON sp.the_loai_id = tl.id
            LEFT JOIN nghe_si ns ON sp.nghe_si_id = ns.id
            LEFT JOIN chi_tiet_san_pham ct ON sp.id = ct.san_pham_id
            ORDER BY sp.id DESC";
    $products = $pdo->query($sql)->fetchAll();

} catch (\PDOException $e) { die("Lỗi kết nối: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Manager CD Store - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-700">

<div class="flex min-h-screen">
    <!-- SIDEBAR (Giữ nguyên menu của mày) -->
    <aside class="w-64 bg-white border-r border-slate-200 hidden lg:block sticky top-0 h-screen">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-10">
                <img src="../uploads/image/others/music.jpg" class="w-10 h-10 rounded-lg object-cover" alt="Logo">
                <span class="font-bold text-xl tracking-tight text-indigo-600">The Muzik Store</span>
            </div>
            <nav class="space-y-1">
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

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-4 lg:p-8">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight italic uppercase">Manager CD Store</h1>
                <p class="text-slate-500 text-sm">Kho lưu trữ <?= number_format($totalCount) ?> Album nhạc</p>
            </div>
            <button onclick="openAddModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition flex items-center gap-2 font-bold">
                <i class="fa-solid fa-plus-circle"></i> Thêm sản phẩm
            </button>
        </header>

        <!-- STAT CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Thống kê Card -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 flex items-center gap-4">
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl"><i class="fa-solid fa-box text-xl"></i></div>
                <div><p class="text-slate-400 font-bold uppercase text-[10px]">Tổng SP</p><p class="text-xl font-bold"><?= $totalCount ?></p></div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 flex items-center gap-4">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl"><i class="fa-solid fa-check-circle text-xl"></i></div>
                <div><p class="text-slate-400 font-bold uppercase text-[10px]">Hoạt động</p><p class="text-xl font-bold"><?= $activeCount ?></p></div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 flex items-center gap-4">
                <div class="p-3 bg-red-50 text-red-600 rounded-xl"><i class="fa-solid fa-triangle-exclamation text-xl"></i></div>
                <div><p class="text-slate-400 font-bold uppercase text-[10px]">Cảnh báo kho</p><p class="text-xl font-bold text-red-500"><?= $lowStockCount ?></p></div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 flex items-center gap-4">
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl"><i class="fa-solid fa-warehouse text-xl"></i></div>
                <div><p class="text-slate-400 font-bold uppercase text-[10px]">Tồn kho</p><p class="text-xl font-bold"><?= number_format($totalStock) ?></p></div>
            </div>
        </div>

        <!-- BẢNG SẢN PHẨM -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-white">
                <h3 class="font-bold text-slate-800">Danh mục đĩa CD</h3>
                <input id="searchInput" type="text" placeholder="Tìm tên CD hoặc giá..." class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm w-64 outline-none">
            </div>
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50/50 text-slate-500 text-[10px] uppercase font-black tracking-widest border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Mã</th>
                        <th class="px-6 py-4">Sản phẩm</th>
                        <th class="px-6 py-4 text-center">Giá bán</th>
                        <th class="px-6 py-4 text-center">Kho</th>
                        <th class="px-6 py-4">Trạng thái</th>
                        <th class="px-6 py-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php foreach ($products as $p): 
                        // Tính giá bán dự kiến để hiển thị
                        $cost = (float)($p['avg_don_gia'] ?? $p['gia_nhap'] ?? 0);
                        $rate = (float)($p['ty_le'] ?? 30);
                        $sellingPrice = $p['gia'] > 0 ? $p['gia'] : round($cost * (1 + $rate / 100));
                    ?>
                    <tr class="hover:bg-slate-50/50 transition duration-150 group">
                        <td class="px-6 py-4 font-bold text-slate-400 text-xs">#<?= $p['id'] ?></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <img src="../uploads/image/album/<?= htmlspecialchars($p['hinh_anh']) ?>" class="w-11 h-11 rounded-xl object-cover bg-slate-100" onerror="this.src='../assets/img/default-album.png'">
                                <div class="max-w-[180px]">
                                    <p class="font-bold text-slate-800 truncate"><?= htmlspecialchars($p['ten_san_pham']) ?></p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight"><?= $p['ten_the_loai'] ?> | <?= $p['ten_nghe_si'] ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center font-black text-indigo-600"><?= number_format($sellingPrice, 0, ',', '.') ?> đ</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-1 rounded-lg font-bold text-xs <?= $p['so_luong'] < 10 ? 'bg-red-50 text-red-500' : 'bg-slate-50 text-slate-600' ?>"><?= $p['so_luong'] ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase <?= $p['trang_thai'] ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' ?>">
                                <?= $p['trang_thai'] ? 'Hoạt động' : 'Tạm khóa' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="detail_adm.php?id=<?= $p['id'] ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-indigo-600 hover:text-white transition"><i class="fa-solid fa-eye text-xs"></i></a>
                                <button onclick='openEditModal(<?= json_encode($p) ?>)' class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-blue-500 hover:bg-blue-600 hover:text-white transition"><i class="fa-solid fa-pen text-xs"></i></button>
                                <button onclick="deleteProduct(<?= $p['id'] ?>)" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-red-400 hover:bg-red-600 hover:text-white transition"><i class="fa-solid fa-trash text-xs"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- MODAL THÊM / SỬA (Dùng chung cấu trúc) -->
<div id="modalProduct" class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 id="modalTitle" class="font-bold text-lg text-slate-800">Thêm sản phẩm CD mới</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-red-500 transition"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form action="them_sp.php" method="POST" enctype="multipart/form-data" class="p-8" id="formProduct">
            <input type="hidden" name="productId" id="p_id">
            <input type="hidden" name="oldImage" id="p_oldImage">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Cột trái -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1 tracking-widest">Tên Album CD</label>
                        <input type="text" name="productName" id="p_name" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Danh mục</label>
                            <select name="categoryId" id="p_category" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= $cat['ten_the_loai'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Nghệ sĩ</label>
                            <input type="text" name="artistName" id="p_artist" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Giá vốn</label>
                            <input type="number" name="costPrice" id="p_cost" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Lợi nhuận (%)</label>
                            <input type="number" name="profitRate" id="p_rate" value="30" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none">
                        </div>
                    </div>
                </div>

                <!-- Cột phải -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Số lượng tồn</label>
                        <input type="number" name="stock" id="p_stock" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none font-bold text-indigo-600">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Trạng thái</label>
                        <select name="status" id="p_status" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none font-bold">
                            <option value="1">Hiển thị (Đang bán)</option>
                            <option value="0">Ẩn (Tạm khóa)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Hình ảnh</label>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-lg bg-slate-100 border overflow-hidden flex-shrink-0">
                                <img id="p_preview" src="" class="w-full h-full object-cover">
                            </div>
                            <input type="file" name="productImage" class="text-xs">
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Mô tả sản phẩm</label>
                    <textarea name="desc" id="p_desc" rows="2" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none resize-none"></textarea>
                </div>

                <div class="md:col-span-2 grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Giá bán dự kiến</p>
                        <p id="view_gia" class="text-lg font-black text-indigo-600">0 đ</p>
                        <input type="hidden" name="gia" id="input_gia">
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Tổng tiền vốn nhập</p>
                        <p id="view_total" class="text-lg font-black text-slate-700">0 đ</p>
                        <input type="hidden" name="total_input_value" id="input_total">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8">
                <button type="button" onclick="closeModal()" class="px-6 py-2 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition">Hủy</button>
                <button type="submit" class="px-10 py-2 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700 transition">Xác nhận Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- KHAI BÁO BIẾN ---
    const modal = document.getElementById('modalProduct');
    const form = document.getElementById('formProduct');

    // --- MỞ MODAL THÊM ---
    function openAddModal() {
        document.getElementById('modalTitle').innerText = "Thêm sản phẩm CD mới";
        form.reset();
        document.getElementById('p_id').value = "";
        document.getElementById('p_preview').src = "../assets/img/default-album.png";
        updateCalculations();
        modal.classList.remove('hidden');
    }

    // --- MỞ MODAL SỬA ---
    function openEditModal(product) {
        document.getElementById('modalTitle').innerText = "Chỉnh sửa sản phẩm";
        document.getElementById('p_id').value = product.id;
        document.getElementById('p_name').value = product.ten_san_pham;
        document.getElementById('p_category').value = product.the_loai_id;
        document.getElementById('p_artist').value = product.ten_nghe_si;
        document.getElementById('p_cost').value = product.avg_don_gia || product.gia_nhap || 0;
        document.getElementById('p_rate').value = product.ty_le || 30;
        document.getElementById('p_stock').value = product.so_luong;
        document.getElementById('p_status').value = product.trang_thai;
        document.getElementById('p_desc').value = product.mo_ta_san_pham || "";
        document.getElementById('p_oldImage').value = product.hinh_anh;
        document.getElementById('p_preview').src = "../uploads/image/album/" + product.hinh_anh;
        
        updateCalculations();
        modal.classList.remove('hidden');
    }

    function closeModal() { modal.classList.add('hidden'); }

    // --- TÍNH TOÁN GIÁ ---
    function updateCalculations() {
        const cost = parseFloat(document.getElementById('p_cost').value) || 0;
        const rate = parseFloat(document.getElementById('p_rate').value) || 0;
        const stock = parseInt(document.getElementById('p_stock').value) || 0;

        const sellingPrice = Math.round(cost * (1 + rate / 100));
        const totalCost = cost * stock;

        document.getElementById('view_gia').innerText = new Intl.NumberFormat('vi-VN').format(sellingPrice) + ' đ';
        document.getElementById('input_gia').value = sellingPrice;

        document.getElementById('view_total').innerText = new Intl.NumberFormat('vi-VN').format(totalCost) + ' đ';
        document.getElementById('input_total').value = totalCost;
    }

    // Gắn sự kiện tính toán tự động
    ['p_cost', 'p_rate', 'p_stock'].forEach(id => {
        document.getElementById(id).addEventListener('input', updateCalculations);
    });

    // --- XÓA SP ---
    function deleteProduct(id) {
        if (confirm("Bạn có chắc chắn muốn xóa album này không?")) {
            window.location.href = "xoa_sp.php?id=" + id;
        }
    }

    // --- TÌM KIẾM ---
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });
</script>
</body>
</html>