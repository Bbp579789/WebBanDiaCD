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

// 1. KẾT NỐI DATABASE
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

    // FIX LỖI: Khởi tạo các biến để tránh lỗi Undefined
    $current_page = 'manager-order.php'; 
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // 2. XỬ LÝ CẬP NHẬT TRẠNG THÁI
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $orderId = $_POST['order_id'];
        $newStatus = $_POST['status'];
        $stmtUpdate = $pdo->prepare("UPDATE don_hang SET trang_thai = ? WHERE id = ?");
        $stmtUpdate->execute([$newStatus, $orderId]);
        header("Location: manager-order.php");
        exit;
    }

    // 3. THỐNG KÊ SỐ LIỆU
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM don_hang")->fetchColumn();
    $pendingOrders = $pdo->query("SELECT COUNT(*) FROM don_hang WHERE trang_thai = 'Đang xử lý'")->fetchColumn();
    $completedOrders = $pdo->query("SELECT COUNT(*) FROM don_hang WHERE trang_thai = 'Hoàn Thành'")->fetchColumn();
    $shippingOrders = $pdo->query("SELECT COUNT(*) FROM don_hang WHERE trang_thai = 'Đang giao'")->fetchColumn();
    $canceledOrders = $pdo->query("SELECT COUNT(*) FROM don_hang WHERE trang_thai = 'Đã hủy'")->fetchColumn();

    // 4. TRUY VẤN DANH SÁCH ĐƠN HÀNG CÓ TÌM KIẾM
    $sql = "SELECT dh.*, nd.ho_ten 
            FROM don_hang dh 
            JOIN nguoi_dung nd ON dh.nguoi_dung_id = nd.id";
    
    if ($search !== '') {
        $sql .= " WHERE nd.ho_ten LIKE :search OR dh.id LIKE :search";
    }
    
    $sql .= " ORDER BY dh.id DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($search !== '') {
        $stmt->bindValue(':search', "%$search%");
    }
    $stmt->execute();
    $orders = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
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

    <main class="flex-1 p-8">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Đơn hàng</h1>
                <p class="text-slate-500 text-sm">Quản lý trạng thái và chi tiết giao dịch</p>
            </div>
        </header>

        <!-- STATS -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-10">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Tổng đơn</p>
                <p class="text-2xl font-black text-slate-800"><?= $totalOrders ?></p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-amber-500">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Chờ xử lý</p>
                <p class="text-2xl font-black text-amber-500"><?= $pendingOrders ?></p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-blue-500">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Đang giao</p>
                <p class="text-2xl font-black text-blue-500"><?= $shippingOrders ?></p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-emerald-500">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Hoàn thành</p>
                <p class="text-2xl font-black text-emerald-500"><?= $completedOrders ?></p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-red-500">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Đã hủy</p>
                <p class="text-2xl font-black text-red-500"><?= $canceledOrders ?></p>
            </div>
        </div>

        <!-- SEARCH -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 mb-6 shadow-sm">
            <form action="manager-order.php" method="GET" class="flex flex-wrap gap-4 items-center">
                <div class="relative flex-1 min-w-[250px]">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-slate-400 text-sm"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Tìm mã đơn hàng hoặc tên khách hàng..." 
                           class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition font-medium">
                </div>
                <a href="manager-order.php" class="px-4 py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition text-sm">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Làm mới
                </a>
            </form>
        </div>

        <!-- TABLE -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100 text-[10px] uppercase font-bold text-slate-400">
                    <tr>
                        <th class="px-6 py-4">Mã đơn</th>
                        <th class="px-6 py-4">Khách hàng</th>
                        <th class="px-6 py-4">Tổng tiền</th>
                        <th class="px-6 py-4 text-center">Trạng thái</th>
                        <th class="px-6 py-4 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach($orders as $order): 
                        $st = $order['trang_thai'];
                        $stColor = match($st) {
                            'Hoàn Thành' => 'emerald',
                            'Đang giao'  => 'blue',
                            'Đã hủy'     => 'red',
                            default      => 'amber'
                        };
                    ?>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 font-bold text-indigo-600">#DH-<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td class="px-6 py-4">
                            <span class="font-bold block text-slate-700"><?= htmlspecialchars($order['ho_ten']) ?></span>
                            <span class="text-[10px] text-slate-400 italic"><?= htmlspecialchars($order['dia_chi_nhan_hang']) ?></span>
                        </td>
                        <td class="px-6 py-4 font-bold text-slate-800"><?= number_format($order['tong_tien']) ?>đ</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-<?= $stColor ?>-50 text-<?= $stColor ?>-600 rounded-full text-[10px] font-black uppercase tracking-wider">
                                <?= $st ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center gap-2">
                                <button onclick="viewOrderDetail(<?= $order['id'] ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-indigo-600 hover:text-white transition shadow-sm"><i class="fa-solid fa-eye text-xs"></i></button>
                                <button onclick="openStatusUpdate(<?= $order['id'] ?>, '<?= $st ?>')" class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-amber-500 hover:text-white transition shadow-sm"><i class="fa-solid fa-pen text-xs"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- MODAL CẬP NHẬT TRẠNG THÁI -->
<div id="statusModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm">
    <form method="POST" class="bg-white w-full max-w-sm rounded-3xl shadow-2xl p-8 transform transition-all">
        <input type="hidden" name="order_id" id="statusOrderId">
        <h3 class="text-xl font-black text-slate-800 mb-6 uppercase italic tracking-tight">Cập nhật đơn hàng</h3>
        <div class="space-y-4 mb-8">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Trạng thái mới</label>
            <select name="status" id="statusSelect" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500 font-bold text-slate-700">
                <option value="Đang xử lý">Đang xử lý</option>
                <option value="Đang giao">Đang giao</option>
                <option value="Hoàn Thành">Hoàn Thành</option>
                <option value="Đã hủy">Đã hủy</option>
            </select>
        </div>
        <div class="flex gap-3">
            <button type="button" onclick="closeStatusModal()" class="flex-1 py-3 font-bold text-slate-400 hover:text-slate-600 transition">Hủy</button>
            <button type="submit" name="update_status" class="flex-1 py-3 bg-indigo-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">Lưu lại</button>
        </div>
    </form>
</div>

<script>
    function openStatusUpdate(id, currentStatus) {
        document.getElementById('statusOrderId').value = id;
        document.getElementById('statusSelect').value = currentStatus;
        document.getElementById('statusModal').classList.remove('hidden');
    }
    function closeStatusModal() {
        document.getElementById('statusModal').classList.add('hidden');
    }
    function viewOrderDetail(id) {
        window.location.href = 'detail-order.php?id=' + id;
    }
    // Đóng modal khi click ra ngoài
    window.onclick = function(event) {
        if (event.target == document.getElementById('statusModal')) closeStatusModal();
    }
</script>

</body>
</html>