<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Nếu chưa đăng nhập qua URL bí mật, không cho xem nội dung
    die("Bạn phải đăng nhập bằng URL bí mật để truy cập.");
}
?>

<?php
$host = 'localhost';
$db   = 'webbandiacd';
$user = 'root'; 
$pass = ''; 
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // Lấy số trang từ URL, nếu không có thì mặc định là 1
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

    $current_page = basename($_SERVER['PHP_SELF']);

    $suppliers = $pdo->query("SELECT id, ten_ncc FROM nha_cung_cap")->fetchAll();

    // Lấy danh sách Album để hiện trong dòng nhập liệu
    $products = $pdo->query("SELECT id, ten_san_pham FROM san_pham")->fetchAll();

    // --- 1. LẤY THAM SỐ LỌC TỪ URL ---
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

    // --- 2. XÂY DỰNG CÂU LỆNH WHERE ---
    $where_clauses = [];
    $params = [];

    if (!empty($search)) {
        $where_clauses[] = "(ncc.ten_ncc LIKE :search OR pn.id LIKE :search_id)";
        $params[':search'] = "%$search%";
        $params[':search_id'] = "%$search%";
    }

    if (!empty($date_filter)) {
        $where_clauses[] = "DATE(pn.ngay_nhap) = :date_filter";
        $params[':date_filter'] = $date_filter;
    }

    $where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

    // --- 3. TRUY VẤN THỐNG KÊ (Phải có fetchColumn() để lấy con số) ---
    $totalImports = $pdo->prepare("SELECT COUNT(*) FROM phieu_nhap pn LEFT JOIN nha_cung_cap ncc ON pn.nha_cung_cap_id = ncc.id" . $where_sql);
    $totalImports->execute($params);
    $totalImportsCount = $totalImports->fetchColumn() ?: 0;

    $totalValueStmt = $pdo->prepare("SELECT SUM(pn.tong_tien) FROM phieu_nhap pn LEFT JOIN nha_cung_cap ncc ON pn.nha_cung_cap_id = ncc.id" . $where_sql);
    $totalValueStmt->execute($params);
    $totalValue = $totalValueStmt->fetchColumn() ?: 0; // Tránh lỗi number_format

    $totalSuppliers = $pdo->query("SELECT COUNT(*) FROM nha_cung_cap")->fetchColumn() ?: 0;

    $totalQty = $pdo->query("SELECT SUM(so_luong) FROM chi_tiet_phieu_nhap")->fetchColumn() ?: 0;

    // --- 4. TRUY VẤN DANH SÁCH PHIẾU NHẬP ---
    $sql = "SELECT pn.*, ncc.ten_ncc, nd.ho_ten as ten_nguoi_nhap 
            FROM phieu_nhap pn
            LEFT JOIN nha_cung_cap ncc ON pn.nha_cung_cap_id = ncc.id
            LEFT JOIN nguoi_dung nd ON pn.nguoi_dung_id = nd.id
            $where_sql
            ORDER BY pn.ngay_nhap DESC";
    
    $stmt_list = $pdo->prepare($sql);
    $stmt_list->execute($params);
    $imports = $stmt_list->fetchAll();

} catch (\PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý nhập hàng - ART STORE</title>
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
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Quản lý nhập hàng</h1>
                <p class="text-slate-500 text-sm">Hệ thống ghi nhận nhập kho từ nhà cung cấp</p>
            </div>
            <div class="flex gap-3">
               
                <button onclick="document.getElementById('modalAddImport').classList.remove('hidden')" 
                        class="px-5 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-bold shadow-lg shadow-indigo-100 transition flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Tạo phiếu nhập
                </button>
            </div>
        </header>

       <!-- STAT CARDS -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Tổng phiếu -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4 hover:shadow-md transition duration-300">
        <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
            <i class="fa-solid fa-file-invoice text-xl"></i>
        </div>
        <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Tổng phiếu</p>
            <p class="text-xl font-black text-slate-800"><?= number_format($totalImportsCount) ?></p>
        </div>
    </div>

    <!-- Tổng vốn nhập -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4 hover:shadow-md transition duration-300 border-l-4 border-l-indigo-500">
        <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
            <i class="fa-solid fa-wallet text-xl"></i>
        </div>
        <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Tổng vốn nhập</p>
            <p class="text-xl font-black text-indigo-600"><?= number_format((float)$totalValue, 0, ',', '.') ?>đ</p>
        </div>
    </div>

    <!-- Nhà cung cấp -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4 hover:shadow-md transition duration-300">
        <div class="p-3 bg-purple-50 text-purple-600 rounded-xl">
            <i class="fa-solid fa-building text-xl"></i>
        </div>
        <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Nhà cung cấp</p>
            <p class="text-xl font-black text-slate-800"><?= number_format($totalSuppliers) ?></p>
        </div>
    </div>

    <!-- Số lượng đĩa -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4 hover:shadow-md transition duration-300">
        <div class="p-3 bg-orange-50 text-orange-600 rounded-xl">
            <i class="fa-solid fa-compact-disc text-xl"></i>
        </div>
        <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Số lượng đĩa</p>
            <p class="text-xl font-black text-slate-800"><?= number_format($totalQty) ?></p>
        </div>
    </div>

</div>
        <!-- FILTER BAR -->
<div class="bg-white p-4 rounded-2xl border border-slate-200 mb-6 shadow-sm">
    <form action="manager-import.php" method="GET" class="flex flex-wrap gap-4 items-center">
        <!-- Tìm kiếm từ khóa -->
        <div class="relative flex-1 min-w-[250px]">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-slate-400 text-sm"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                   placeholder="Tìm mã phiếu hoặc nhà cung cấp..." 
                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition font-medium">
        </div>

        <!-- Lọc theo ngày -->
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-400 uppercase">Ngày nhập:</span>
            <input type="date" name="date_filter" value="<?= htmlspecialchars($date_filter) ?>" 
                   class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-medium">
        </div>

        <!-- Nút Submit -->
        <button type="submit" class="px-6 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 text-sm">
            <i class="fa-solid fa-filter mr-2"></i> Lọc dữ liệu
        </button>

        <!-- NÚT LÀM MỚI (Xóa từ khóa) -->
        <a href="manager-import.php" class="px-4 py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition flex items-center gap-2 text-sm">
            <i class="fa-solid fa-rotate-left"></i> Làm mới
        </a>
    </form>
</div>

        <!-- TABLE LIST -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase font-bold tracking-widest border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-center">Mã phiếu</th>
                        <th class="px-6 py-4">Nhà cung cấp</th>
                        <th class="px-6 py-4">Ngày nhập</th>
                        <th class="px-6 py-4">Tổng tiền</th>
                        <!-- <th class="px-6 py-4">Trạng thái</th> -->
                        <th class="px-6 py-4 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
    <?php if (!empty($imports)): ?>
        <?php foreach ($imports as $row): ?>
            <tr class="hover:bg-slate-50/50 transition">
                <td class="px-6 py-5 font-bold text-slate-400">#PN-<?= $row['id'] ?></td>
                <td class="px-6 py-5 font-bold text-slate-700"><?= htmlspecialchars($row['ten_ncc']) ?></td>
                <td class="px-6 py-5 text-sm"><?= date('d/m/Y H:i', strtotime($row['ngay_nhap'])) ?></td>
                <td class="px-6 py-5 font-bold text-indigo-600"><?= number_format($row['tong_tien'], 0, ',', '.') ?>đ</td>
               <td class="px-6 py-5 text-center">
        <!-- Nút Sửa: Truyền ID phiếu nhập qua URL -->
       <button onclick='openEditModal(<?= json_encode($row) ?>)' 
        class="inline-flex items-center justify-center w-9 h-9 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-600 hover:text-white transition">
    <i class="fa-solid fa-pen-to-square"></i>
</button>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="6" class="px-6 py-10 text-center text-slate-400 italic">
                Chưa có dữ liệu phiếu nhập nào.
            </td>
        </tr>
    <?php endif; ?>
</tbody> 
            </table>
        </div>
    </main>
</div>

<!-- MODAL TẠO PHIẾU NHẬP -->
<div id="modalAddImport" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
    <div class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden border border-white">
        <!-- Header -->
        <div class="p-6 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-lg text-slate-800 uppercase tracking-tight">Lập phiếu nhập CD mới</h3>
                <p class="text-xs text-slate-400">Ghi nhận thông tin nhập kho, giá nhập và tỷ lệ lợi nhuận</p>
            </div>
            <button type="button" onclick="document.getElementById('modalAddImport').classList.add('hidden')" class="text-slate-400 hover:text-red-500">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        
        <form action="process_import.php" method="POST" id="importForm" class="p-8 space-y-6">
            <!-- THÔNG TIN CHUNG PHIẾU NHẬP -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-indigo-50/30 p-6 rounded-2xl border border-indigo-50">
                <div>
                    <label class="block text-[10px] font-bold text-indigo-400 uppercase mb-2">Nhà cung cấp</label>
                   <select name="ncc_id" required ...>
    <option value="">-- Chọn nhà cung cấp --</option>
    <?php if(!empty($suppliers)): ?>
        <?php foreach($suppliers as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['ten_ncc']) ?></option>
        <?php endforeach; ?>
    <?php endif; ?>
</select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-indigo-400 uppercase mb-2">Ngày nhập</label>
                    <input type="date" name="ngay_nhap" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-indigo-400 uppercase mb-2">Lần nhập (Số hiệu)</label>
                    <input type="text" name="lan_nhap" placeholder="Ví dụ: Đợt 1 - T4" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-indigo-400 uppercase mb-2">Lợi nhuận mặc định (%)</label>
                    <input type="number" name="profit_rate" value="20" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
            </div>
            
            <!-- CHI TIẾT SẢN PHẨM -->
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <h4 class="font-bold text-slate-800 text-sm">Danh sách đĩa nhập kho</h4>
                    <button type="button" onclick="addRow()" class="text-indigo-600 font-bold text-xs hover:bg-indigo-50 px-3 py-2 rounded-lg transition border border-indigo-100">
                        <i class="fa-solid fa-plus-circle mr-1"></i> Thêm Album
                    </button>
                </div>
                
                <div class="overflow-x-auto border border-slate-100 rounded-2xl max-h-[300px] overflow-y-auto">
                    <table class="w-full text-sm text-left" id="productTable">
                        <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3">Tên Album CD</th>
                                <th class="px-4 py-3 w-32 text-center">Số lượng nhập</th>
                                <th class="px-4 py-3 w-40 text-right">Giá nhập (đ/cái)</th>
                                <th class="px-4 py-3 w-40 text-right">Thành tiền</th>
                                <th class="px-4 py-3 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="importItems">
                            <!-- Dòng đầu tiên mặc định -->
                            <tr class="item-row group">
                                <td class="px-4 py-4">
                                   <select name="items[0][product_id]" required ...>
    <option value="">-- Chọn đĩa nhạc --</option>
    <?php if(!empty($products)): ?>
        <?php foreach($products as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['ten_san_pham']) ?></option>
        <?php endforeach; ?>
    <?php endif; ?>
</select>
                                </td>
                                <td class="px-4 py-4">
                                    <input type="number" name="items[0][qty]" value="1" min="1" oninput="calculateTotal()" class="w-full border border-slate-100 rounded-lg px-2 py-1 outline-none focus:border-indigo-300 text-center">
                                </td>
                                <td class="px-4 py-4">
                                    <input type="number" name="items[0][price]" value="0" min="0" oninput="calculateTotal()" class="w-full border border-slate-100 rounded-lg px-2 py-1 outline-none focus:border-indigo-300 text-right font-bold text-indigo-600">
                                </td>
                                <td class="px-4 py-4 text-right font-extrabold text-slate-800 row-total">0đ</td>
                                <td class="px-4 py-4 text-center">
                                    <button type="button" onclick="removeRow(this)" class="text-slate-300 hover:text-red-500 transition">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TỔNG CỘNG & NÚT BẤM -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-6 pt-6 border-t border-slate-100">
                <div class="text-center md:text-left">
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Tổng thanh toán phiếu</p>
                    <p class="text-3xl font-black text-slate-900 leading-none" id="grandTotal">0₫</p>
                    <input type="hidden" name="total_amount" id="inputGrandTotal" value="0">
                </div>
                <div class="flex gap-3 w-full md:w-auto">
                    <button type="button" onclick="document.getElementById('modalAddImport').classList.add('hidden')" class="flex-1 md:flex-none px-8 py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition">Hủy bỏ</button>
                    <button type="submit" class="flex-1 md:flex-none px-8 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-xl shadow-indigo-100 transition">
                        Xác nhận & Nhập kho
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- MODAL SỬA PHIẾU NHẬP -->
<div id="modalEditImport" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
    <div class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden border border-white">
        <!-- Header -->
        <div class="p-6 bg-amber-50 border-b border-amber-100 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-lg text-amber-800 uppercase tracking-tight">Chỉnh sửa phiếu nhập</h3>
                <p class="text-xs text-amber-600">Thay đổi thông tin nhà cung cấp, sản phẩm hoặc giá nhập</p>
            </div>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-red-500">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        
        <form action="process_edit_import.php" method="POST" id="editImportForm" class="p-8 space-y-6">
            <!-- ID phiếu ẩn -->
            <input type="hidden" name="phieu_id" id="edit_phieu_id">

            <!-- THÔNG TIN CHUNG -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-amber-50/30 p-6 rounded-2xl border border-amber-50">
                <div>
                    <label class="block text-[10px] font-bold text-amber-400 uppercase mb-2">Nhà cung cấp</label>
                    <select name="ncc_id" id="edit_ncc_id" required class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                        <?php foreach($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['ten_ncc']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-amber-400 uppercase mb-2">Ngày nhập</label>
                    <input type="date" name="ngay_nhap" id="edit_ngay_nhap" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-amber-400 uppercase mb-2">Lần nhập</label>
                    <input type="text" name="lan_nhap" id="edit_lan_nhap" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-amber-400 uppercase mb-2">Lợi nhuận (%)</label>
                    <input type="number" name="profit_rate" id="edit_profit_rate" value="20" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                </div>
            </div>
            
            <!-- DANH SÁCH SẢN PHẨM -->
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <h4 class="font-bold text-slate-800 text-sm">Chi tiết sản phẩm nhập</h4>
                    <button type="button" onclick="addEditRow()" class="text-amber-600 font-bold text-xs hover:bg-amber-50 px-3 py-2 rounded-lg transition border border-amber-100">
                        <i class="fa-solid fa-plus-circle mr-1"></i> Thêm dòng
                    </button>
                </div>
                
                <div class="overflow-x-auto border border-slate-100 rounded-2xl max-h-[300px] overflow-y-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3">Tên Album CD</th>
                                <th class="px-4 py-3 w-32 text-center">Số lượng</th>
                                <th class="px-4 py-3 w-40 text-right">Giá nhập</th>
                                <th class="px-4 py-3 w-40 text-right">Thành tiền</th>
                                <th class="px-4 py-3 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="editImportItems">
                            <!-- Dữ liệu sẽ được chèn vào đây bằng JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-center gap-6 pt-6 border-t border-slate-100">
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Tổng tiền phiếu</p>
                    <p class="text-3xl font-black text-slate-900 leading-none" id="editGrandTotal">0₫</p>
                    <input type="hidden" name="total_amount" id="editInputGrandTotal" value="0">
                </div>
                <div class="flex gap-3 w-full md:w-auto">
                    <button type="button" onclick="closeEditModal()" class="px-8 py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition">Hủy</button>
                    <button type="submit" class="px-8 py-3 bg-amber-600 text-white font-bold rounded-xl hover:bg-amber-700 shadow-xl shadow-amber-100 transition">
                        Lưu thay đổi
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Biến toàn cục để quản lý ID dòng
let addRowIdx = 1;
let editRowIdx = 0;
// Lấy danh sách sản phẩm từ PHP sang JS
const allProducts = <?= json_encode($products) ?>;

// --- PHẦN 1: LOGIC CHO MODAL THÊM ---
function addRow() {
    const tbody = document.getElementById('importItems');
    const firstRow = document.querySelector('.item-row');
    const newRow = firstRow.cloneNode(true);
    
    newRow.querySelector('select').name = `items[${addRowIdx}][product_id]`;
    newRow.querySelector('select').value = "";
    newRow.querySelectorAll('input')[0].name = `items[${addRowIdx}][qty]`;
    newRow.querySelectorAll('input')[1].name = `items[${addRowIdx}][price]`;
    newRow.querySelectorAll('input').forEach(i => i.value = i.type === 'number' ? 0 : '');
    newRow.querySelector('.row-total').innerText = '0đ';
    
    tbody.appendChild(newRow);
    addRowIdx++;
}

function calculateTotal() {
    let grandTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('input[name*="[qty]"]').value) || 0;
        const price = parseFloat(row.querySelector('input[name*="[price]"]').value) || 0;
        const total = qty * price;
        grandTotal += total;
        row.querySelector('.row-total').innerText = new Intl.NumberFormat('vi-VN').format(total) + 'đ';
    });
    document.getElementById('grandTotal').innerText = new Intl.NumberFormat('vi-VN').format(grandTotal) + '₫';
    document.getElementById('inputGrandTotal').value = grandTotal;
}

// --- PHẦN 2: LOGIC CHO MODAL SỬA (QUAN TRỌNG) ---
async function openEditModal(data) {
    const modal = document.getElementById('modalEditImport');
    modal.classList.remove('hidden');

    // 1. Hiển thị thông tin chung (Lần nhập, Ngày, NCC)
    document.getElementById('edit_phieu_id').value = data.id;
    document.getElementById('edit_ncc_id').value = data.nha_cung_cap_id;
    document.getElementById('edit_ngay_nhap').value = data.ngay_nhap.split(' ')[0];
    
    // Kiểm tra xem ID 'edit_lan_nhap' có đúng trong HTML không
    if(document.getElementById('edit_lan_nhap')) {
        document.getElementById('edit_lan_nhap').value = data.lan_nhap || ''; 
    }

    // 2. Xóa trắng bảng cũ để chuẩn bị load sản phẩm mới
    const tbody = document.getElementById('editImportItems');
    tbody.innerHTML = '';
    editRowIdx = 0;

    // 3. Gọi AJAX lấy danh sách Album của phiếu này
    try {
        const response = await fetch(`get_import_details.php?phieu_id=${data.id}`);
        const details = await response.json();

        if (details && details.length > 0) {
            details.forEach(item => {
                // Gọi hàm tạo dòng và truyền dữ liệu cũ vào
                addEditRow(item.san_pham_id, item.so_luong, item.don_gia);
            });
        } else {
            addEditRow(); // Nếu phiếu trống, hiện 1 dòng mặc định
        }
    } catch (error) {
        console.error("Không thể load sản phẩm:", error);
    }
}

// Hàm tạo dòng sản phẩm trong Modal Sửa
function addEditRow(prodId = "", qty = 1, price = 0) {
    const tbody = document.getElementById('editImportItems');
    
    let productOptions = `<option value="">-- Chọn đĩa nhạc --</option>`;
    allProducts.forEach(p => {
        const selected = (p.id == prodId) ? 'selected' : '';
        productOptions += `<option value="${p.id}" ${selected}>${p.ten_san_pham}</option>`;
    });

    const row = document.createElement('tr');
    row.className = 'edit-item-row group';
    row.innerHTML = `
        <td class="px-4 py-4">
            <select name="items[${editRowIdx}][product_id]" required class="w-full bg-transparent outline-none font-medium">
                ${productOptions}
            </select>
        </td>
        <td class="px-4 py-4">
            <input type="number" name="items[${editRowIdx}][qty]" value="${qty}" oninput="calculateEditTotal()" class="w-full border rounded px-2 py-1 text-center">
        </td>
        <td class="px-4 py-4">
            <input type="number" name="items[${editRowIdx}][price]" value="${price}" oninput="calculateEditTotal()" class="w-full border rounded px-2 py-1 text-right font-bold text-amber-600">
        </td>
        <td class="px-4 py-4 text-right font-extrabold edit-row-total">0đ</td>
        <td class="px-4 py-4 text-center">
            <button type="button" onclick="removeEditRow(this)" class="text-red-300 hover:text-red-500"><i class="fa-solid fa-trash-can"></i></button>
        </td>
    `;
    tbody.appendChild(row);
    editRowIdx++;
    calculateEditTotal();
}

function calculateEditTotal() {
    let grandTotal = 0;
    document.querySelectorAll('.edit-item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('input[name*="[qty]"]').value) || 0;
        const price = parseFloat(row.querySelector('input[name*="[price]"]').value) || 0;
        const total = qty * price;
        grandTotal += total;
        row.querySelector('.edit-row-total').innerText = new Intl.NumberFormat('vi-VN').format(total) + 'đ';
    });
    document.getElementById('editGrandTotal').innerText = new Intl.NumberFormat('vi-VN').format(grandTotal) + '₫';
    document.getElementById('editInputGrandTotal').value = grandTotal;
}

function closeEditModal() {
    document.getElementById('modalEditImport').classList.add('hidden');
}

function removeEditRow(btn) {
    if(document.querySelectorAll('.edit-item-row').length > 1) {
        btn.closest('tr').remove();
        calculateEditTotal();
    }
}

// Đóng modal khi click ra ngoài
window.onclick = function(event) {
    if (event.target == document.getElementById('modalEditImport')) closeEditModal();
    if (event.target == document.getElementById('modalAddImport')) document.getElementById('modalAddImport').classList.add('hidden');
}
</script>

<script src="../assets/js/noti.js"></script>
</body>
</html>