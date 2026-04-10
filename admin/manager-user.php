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

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // LẤY GIÁ TRỊ TÌM KIẾM (từ query string)
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // --- LẤY DỮ LIỆU THỐNG KÊ ---
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM nguoi_dung")->fetchColumn();
    $activeUsers = $pdo->query("SELECT COUNT(*) FROM nguoi_dung WHERE trang_thai = 1")->fetchColumn();
    $lockedUsers = $pdo->query("SELECT COUNT(*) FROM nguoi_dung WHERE trang_thai = 0")->fetchColumn();

    // --- TRUY VẤN DANH SÁCH NGƯỜI DÙNG (lọc theo ho_ten hoặc số điện thoại nếu có search) ---
    if ($search !== '') {
        $stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE ho_ten LIKE :s OR so_dien_thoai LIKE :s ORDER BY id DESC");
        $stmt->execute([':s' => "%{$search}%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM nguoi_dung ORDER BY id DESC");
    }
    $users = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}

$current_page = basename($_SERVER['PHP_SELF']);
?> 

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng </title>
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
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Quản lý tài khoản</h1>
                <p class="text-slate-500 text-sm">Quản lý nhân viên và khách hàng trên hệ thống</p>
            </div>
            <button onclick="document.getElementById('modalAddUser').classList.remove('hidden')" 
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-bold shadow-lg shadow-indigo-100 transition flex items-center gap-2">
                <i class="fa-solid fa-plus text-sm"></i> Thêm tài khoản
            </button>
        </header>

        <!-- THỐNG KÊ NHANH -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl"><i class="fa-solid fa-users text-xl"></i></div>
                <div><p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Tổng thành viên</p><p class="text-xl font-extrabold text-slate-800"><?= $totalUsers ?></p></div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl"><i class="fa-solid fa-user-check text-xl"></i></div>
                <div><p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Đang hoạt động</p><p class="text-xl font-extrabold text-emerald-600"><?= $activeUsers ?></p></div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-red-50 text-red-600 rounded-xl"><i class="fa-solid fa-user-lock text-xl"></i></div>
                <div><p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Bị khóa</p><p class="text-xl font-extrabold text-red-600"><?= $lockedUsers ?></p></div>
            </div>
        </div>

        <!-- BỘ LỌC -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 mb-6 flex flex-wrap gap-4 items-center shadow-sm">
            <form class="flex-1 min-w-[300px]" method="GET" action="manager-user.php">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-slate-400 text-sm"></i>
                    <input id="searchInput" name="search" type="text" placeholder="Tìm theo họ tên..." 
                           value="<?= htmlspecialchars($search ?? '') ?>"
                           class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>
            </form>
          
            <a href="manager-user.php" class="px-4 py-3 text-indigo-600 font-bold hover:bg-indigo-50 rounded-xl transition flex items-center gap-2 text-sm">
                <i class="fa-solid fa-rotate"></i> Làm mới
            </a>
        </div>

        <!-- BẢNG NGƯỜI DÙNG -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase font-bold tracking-widest border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Mã</th>
                        <th class="px-6 py-4">Thông tin người dùng</th>
                        <th class="px-6 py-4 text-center">Vai trò</th>
                        <th class="px-6 py-4">Trạng thái</th>
                        <th class="px-6 py-4 text-right">Tùy chỉnh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach($users as $u): ?>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-5 font-bold text-slate-400 text-sm">#U-<?= str_pad($u['id'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
                                    <?= strtoupper(substr($u['ho_ten'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800"><?= htmlspecialchars($u['ho_ten']) ?></p>
                                    <p class="text-xs text-slate-400 font-medium"><?= htmlspecialchars($u['email']) ?></p>
                                    <p class="text-xs text-slate-400 phone"><?= htmlspecialchars($u['so_dien_thoai'] ?? '') ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <?php if($u['vai_tro'] == 'admin'): ?>
                                <span class="px-2 py-1 bg-amber-100 text-amber-600 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-amber-200">Quản trị</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-indigo-50 text-indigo-500 rounded-lg text-[10px] font-bold uppercase tracking-wider border border-indigo-100">Khách</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-5 text-sm">
                            <span class="flex items-center gap-2 font-bold <?= $u['trang_thai'] ? 'text-emerald-500' : 'text-red-400' ?>">
                                <span class="w-2 h-2 rounded-full <?= $u['trang_thai'] ? 'bg-emerald-500' : 'bg-red-400' ?>"></span>
                                <?= $u['trang_thai'] ? 'Hoạt động' : 'Đang khóa' ?>
                            </span>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <button onclick='openEditModal(<?= json_encode($u) ?>)' class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-indigo-600 hover:text-white transition shadow-sm">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- MODAL THÊM/SỬA TÀI KHOẢN -->
<div id="modalAddUser" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-200">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden">
        <div class="p-6 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-slate-800" id="modalTitle">Thêm tài khoản người dùng</h3>
            <button onclick="closeModal()" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 rounded-full transition"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form id="formAddUser" action="process_user.php" method="POST" class="p-8 space-y-6">
            <input type="hidden" name="id" id="userId">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Họ và tên</label>
                    <input type="text" name="ho_ten" id="hoTen" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition font-medium">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Tên đăng nhập</label>
                    <input type="text" name="ten_dang_nhap" id="tenDangNhap" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition font-medium">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Email</label>
                    <input type="email" name="email" id="email" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition font-medium">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Số điện thoại</label>
                    <input type="text" name="so_dien_thoai" id="sdt" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition font-medium">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Địa chỉ</label>
                    <input type="text" name="dia_chi" id="diaChi" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition font-medium">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Vai trò</label>
                    <select name="vai_tro" id="vaiTro" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 transition font-medium">
                        <option value="user">Khách hàng (User)</option>
                        <option value="admin">Quản trị viên (Admin)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Trạng thái</label>
                    <select name="trang_thai" id="trangThai" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500 transition font-medium">
                        <option value="1">Đang hoạt động</option>
                        <option value="0">Khóa tài khoản</option>
                    </select>
                </div>
            </div>
            <div class="pt-6 flex gap-3 border-t border-slate-100">
                <button type="button" onclick="closeModal()" class="px-4 py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition">Hủy bỏ</button>

                <button type="button" onclick="resetPasswordConfirm()" class="px-4 py-3 bg-yellow-500 text-white font-bold rounded-xl hover:bg-yellow-600 transition">Khởi tạo mật khẩu</button>

                <button type="submit" class="px-4 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition">Lưu thông tin</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Debounce helper
    function debounce(fn, wait) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    (function(){
        const input = document.getElementById('searchInput');
        const form = input ? input.closest('form') : null;
        if(!input || !form) return;

        const submitSearch = () => {
            // Always submit GET so server returns filtered rows (supports 1 char)
            form.submit();
        };

        input.addEventListener('input', debounce(submitSearch, 250));

        input.addEventListener('keydown', function(e){
            if (e.key === 'Escape') {
                input.value = '';
                form.submit();
            }
            // Enter will naturally submit form
        });
    })();

    // Mở modal / đóng modal (giữ nguyên)
    function openEditModal(user) {
        document.getElementById('modalTitle').innerText = "Chỉnh sửa tài khoản";
        document.getElementById('userId').value = user.id;
        document.getElementById('hoTen').value = user.ho_ten;
        document.getElementById('tenDangNhap').value = user.ten_dang_nhap;
        document.getElementById('email').value = user.email;
        document.getElementById('sdt').value = user.so_dien_thoai;
        document.getElementById('diaChi').value = user.dia_chi;
        document.getElementById('vaiTro').value = user.vai_tro;
        document.getElementById('trangThai').value = user.trang_thai;
        document.getElementById('modalAddUser').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('modalAddUser').classList.add('hidden');
        document.getElementById('formAddUser').reset();
        document.getElementById('modalTitle').innerText = "Thêm tài khoản người dùng";
    }

    // Reset mật khẩu admin -> gọi endpoint process_user.php?action=reset_password
    async function resetPasswordConfirm(){
        const id = parseInt(document.getElementById('userId').value || 0, 10);
        if(!id){ alert('Chọn tài khoản trước khi khởi tạo mật khẩu.'); return; }
        if(!confirm('Khởi tạo lại mật khẩu về mặc định (123456)?')) return;
        try {
            const res = await fetch('process_user.php?action=reset_password', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(id)
            });
            const text = (await res.text()).trim();
            if(text === 'success'){
                alert('Đã khởi tạo mật khẩu thành công (mật khẩu mặc định: 123456).');
            } else {
                alert('Lỗi: ' + text);
            }
        } catch(err){
            console.error(err);
            alert('Lỗi kết nối');
        }
    }
</script>
</body>
</html>