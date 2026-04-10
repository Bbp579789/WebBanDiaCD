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

    $current_page = basename($_SERVER['PHP_SELF']);

    // --- LOGIC TÌM KIẾM ---
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $where_sql = "";
    $params = [];

    if (!empty($search)) {
        $where_sql = " WHERE ten_the_loai LIKE :search OR id LIKE :search_id ";
        $params[':search'] = "%$search%";
        $params[':search_id'] = "%$search%";
    }

    // --- LOGIC PHÂN TRANG ---
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM the_loai" . $where_sql);
    $total_stmt->execute($params);
    $total_categories = $total_stmt->fetchColumn();
    $total_pages = ceil($total_categories / $limit);

    // Truy vấn dữ liệu
    $sql = "SELECT * FROM the_loai" . $where_sql . " ORDER BY id DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $categories = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}
?>



<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        /* Tùy chỉnh thanh cuộn cho danh sách mini */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
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

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-4 lg:p-8">
        <!-- HEADER -->
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Quản lý danh mục</h1>
                <p class="text-slate-500 text-sm">Phân loại và tổ chức hệ thống đĩa CD</p>
            </div>
            <button onclick="document.getElementById('modalAddCategory').classList.remove('hidden')" 
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-bold shadow-lg shadow-indigo-100 transition flex items-center gap-2">
                <i class="fa-solid fa-plus text-sm"></i> Thêm danh mục
            </button>
        </header>

       <!-- BỘ LỌC -->
<div class="bg-white p-4 rounded-2xl border border-slate-200 mb-6 shadow-sm">
    <!-- Form gửi dữ liệu theo phương thức GET -->
    <form action="manager-category.php" method="GET" class="flex flex-wrap gap-4 items-center">
        <div class="relative flex-1 min-w-[300px]">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-slate-400 text-sm"></i>
            <input type="text" name="search" 
                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                   placeholder="Tìm nhanh mã hoặc tên danh mục..." 
                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
        </div>
        
        <button type="submit" class="px-6 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition text-sm shadow-md shadow-indigo-100">
            Tìm kiếm
        </button>

        <!-- NÚT LÀM MỚI: Trỏ về file gốc để xóa sạch từ khóa trên URL -->
        <a href="manager-category.php" class="px-4 py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition flex items-center gap-2 text-sm">
            <i class="fa-solid fa-rotate"></i> Làm mới
        </a>
    </form>
</div>

        <!-- BẢNG DANH MỤC -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase font-bold tracking-widest border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Mã danh mục</th>
                        <th class="px-6 py-4">Tên danh mục</th>
                        <th class="px-6 py-4">Trạng thái</th>
                        <th class="px-6 py-4 text-center">Tùy chỉnh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach($categories as $cat): ?>
                    <tr class="hover:bg-slate-50/50 transition group">
                        <td class="px-6 py-5 font-bold text-slate-400 text-sm">#DM-<?= str_pad($cat['id'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td class="px-6 py-5">
                            <span class="font-bold text-slate-700"><?= htmlspecialchars($cat['ten_the_loai']) ?></span>
                        </td>
                        <td class="px-6 py-5">
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase <?= $cat['trang_thai'] ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' ?>">
                                <?= $cat['trang_thai'] ? 'Hoạt động' : 'Bị khóa' ?>
                            </span>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <div class="flex justify-center items-center gap-3">
                                <!-- Nút Sửa -->
                                <button onclick="openEditModal(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['ten_the_loai'], ENT_QUOTES) ?>', <?= $cat['trang_thai'] ?>)" 
                                        class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition shadow-sm">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>

                                <!-- Nút Xóa (giữ, dùng process_category.php?action=delete) -->
                                <button onclick="deleteCategory(<?= $cat['id'] ?>)"
                                        class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200 text-red-500 hover:bg-red-600 hover:text-white transition shadow-sm">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>

                                <!-- Nút Xem sản phẩm -->
                                <div class="relative group/mini">
                                    <button class="px-4 py-2 rounded-lg border border-indigo-100 bg-indigo-50 text-indigo-600 font-bold text-[11px] hover:bg-indigo-600 hover:text-white transition flex items-center gap-2 shadow-sm">
                                        <i class="fa-solid fa-eye"></i> Xem SP
                                    </button>
                                    
                                    <!-- Mini List Floating Card -->
                                    <div class="invisible group-hover/mini:visible opacity-0 group-hover/mini:opacity-100 absolute z-50 right-0 mt-2 w-64 bg-white border border-slate-200 rounded-2xl shadow-2xl transition-all duration-200 transform origin-top-right scale-95 group-hover/mini:scale-100 p-4">
                                        <div class="flex justify-between items-center mb-3 border-b border-slate-50 pb-2">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sản phẩm thuộc nhóm</span>
                                        </div>
                                        <div class="space-y-3 max-h-60 overflow-y-auto custom-scrollbar">
                                            <?php
                                            // Lấy 4 sản phẩm mẫu từ DB thuộc danh mục này
                                            $stmt_sp = $pdo->prepare("SELECT ten_san_pham, hinh_anh FROM san_pham WHERE the_loai_id = ? LIMIT 4");
                                            $stmt_sp->execute([$cat['id']]);
                                            $mini_products = $stmt_sp->fetchAll();
                                            
                                            if ($mini_products):
                                                foreach($mini_products as $sp): ?>
                                                <div class="flex items-center gap-3 p-1.5 hover:bg-slate-50 rounded-xl transition group/item">
                                                    <img src="../uploads/image/album/<?= htmlspecialchars($sp['hinh_anh']) ?>" 
                                                         class="w-10 h-10 rounded-lg object-cover shadow-sm border border-slate-100" 
                                                         onerror="this.src='../assets/img/default-album.png'">
                                                    <span class="text-[11px] font-bold text-slate-600 truncate group-hover/item:text-indigo-600 transition"><?= htmlspecialchars($sp['ten_san_pham']) ?></span>
                                                </div>
                                            <?php endforeach; 
                                            else: ?>
                                                <div class="py-4 text-center">
                                                    <i class="fa-solid fa-box-open text-slate-200 text-2xl mb-2 block"></i>
                                                    <p class="text-[10px] text-slate-400 italic">Chưa có sản phẩm nào</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- PHÂN TRANG -->
<?php if ($total_pages > 1): ?>
<div class="mt-8 flex justify-center items-center gap-2 text-sm">
    <!-- Nút quay lại -->
    <a href="?page=<?= max(1, $page - 1) ?>" 
       class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:bg-indigo-50 transition shadow-sm <?= ($page <= 1) ? 'pointer-events-none opacity-50' : '' ?>">
        <i class="fa-solid fa-chevron-left"></i>
    </a>

    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?= $i ?>" 
           class="w-10 h-10 flex items-center justify-center rounded-xl font-bold transition shadow-md 
           <?= ($i == $page) ? 'bg-indigo-600 text-white shadow-indigo-100' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>

    <!-- Nút tới -->
    <a href="?page=<?= min($total_pages, $page + 1) ?>" 
       class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:bg-indigo-50 transition shadow-sm <?= ($page >= $total_pages) ? 'pointer-events-none opacity-50' : '' ?>">
        <i class="fa-solid fa-chevron-right"></i>
    </a>
</div>
<?php endif; ?>

       
    </main>
</div>

<!-- MODAL THÊM DANH MỤC -->
<div id="modalAddCategory" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in duration-200 border border-white">
        <div class="p-6 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-slate-800">Thêm danh mục mới</h3>
            <button onclick="document.getElementById('modalAddCategory').classList.add('hidden')" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 rounded-full transition"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form action="process_category.php?action=add" method="POST" class="p-8 space-y-6">
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Tên danh mục</label>
                <input type="text" name="categoryName" required placeholder="Ví dụ: Nhạc Ballad, Rock..." 
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition font-medium">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Trạng thái</label>
                <select name="status" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 transition font-medium">
                    <option value="1">Đang hoạt động</option>
                    <option value="0">Khóa danh mục</option>
                </select>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="document.getElementById('modalAddCategory').classList.add('hidden')" 
                        class="flex-1 px-4 py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition">Hủy</button>
                <button type="submit" class="flex-1 px-4 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition">Thêm ngay</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SỬA DANH MỤC -->
<div id="modalEditCategory" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in duration-200">
        <div class="p-6 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-slate-800 text-blue-600">Cập nhật danh mục</h3>
            <button onclick="document.getElementById('modalEditCategory').classList.add('hidden')" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 rounded-full transition"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form action="process_category.php?action=edit" method="POST" class="p-8 space-y-6">
            <input type="hidden" name="categoryId" id="editCategoryId">
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Tên danh mục</label>
                <input type="text" name="categoryName" id="editCategoryName" required
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition font-medium">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Trạng thái</label>
                <select name="status" id="editCategoryStatus" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 transition font-medium">
                    <option value="1">Đang hoạt động</option>
                    <option value="0">Khóa danh mục</option>
                </select>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="document.getElementById('modalEditCategory').classList.add('hidden')" 
                        class="flex-1 px-4 py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition">Đóng</button>
                <button type="submit" class="flex-1 px-4 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-100 transition">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(id, name, status) {
        document.getElementById('editCategoryId').value = id;
        document.getElementById('editCategoryName').value = name;
        document.getElementById('editCategoryStatus').value = status;
        document.getElementById('modalEditCategory').classList.remove('hidden');
    }

    async function deleteCategory(id){
        if(!confirm('Xác nhận xóa danh mục này? (Không thể hoàn tác)')) return;
        try {
            const res = await fetch('process_category.php?action=delete', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(id)
            });
            const text = (await res.text()).trim();
            if(text === 'success'){
                location.reload();
            } else {
                alert('Lỗi: ' + text);
            }
        } catch(err){
            console.error(err);
            alert('Lỗi kết nối');
        }
    }

    async function toggleCategory(id, currentStatus){
        const newStatus = currentStatus ? 0 : 1;
        const confirmMsg = newStatus ? 'Mở danh mục này?' : 'Khóa danh mục này? (Sản phẩm thuộc danh mục sẽ ẩn trên web)';
        if(!confirm(confirmMsg)) return;
        try {
            const res = await fetch('process_category.php?action=toggle', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(newStatus)
            });
            const text = (await res.text()).trim();
            if(text === 'success'){
                location.reload();
            } else {
                alert('Lỗi: ' + text);
            }
        } catch(err){
            console.error(err);
            alert('Lỗi kết nối');
        }
    }
</script>
<script src="../assets/js/account-admin.js"></script>
<script src="../assets/js/noti.js"></script>
</body>
</html>