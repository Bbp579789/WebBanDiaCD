<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$session_user_id = (int)$_SESSION['user_id'];

// 1. Kiểm tra cấu trúc bảng để lấy thông tin chuẩn
$dh_cols = [];
$res = mysqli_query($conn, "SHOW COLUMNS FROM don_hang");
while ($row = mysqli_fetch_assoc($res)) { $dh_cols[] = $row['Field']; }

// Tự động nhận diện cột liên kết người dùng
$col_user = in_array('user_id', $dh_cols) ? 'user_id' : (in_array('nguoi_dung_id', $dh_cols) ? 'nguoi_dung_id' : 'id');

// Tự động nhận diện cột ngày tháng
$col_date = 'id'; // fallback
foreach (['created_at', 'ngay_tao', 'ngay_lap'] as $c) {
    if (in_array($c, $dh_cols)) { $col_date = $c; break; }
}

// 1. Lấy danh sách đơn hàng cơ bản
$sql = "SELECT dh.*, dh.$col_date AS date_display 
        FROM don_hang dh 
        WHERE dh.$col_user = ? 
        ORDER BY dh.id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $session_user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = [];

while ($row = mysqli_fetch_assoc($result)) {
    $order_id = $row['id'];
    // 2. Với mỗi đơn hàng, lấy chi tiết sản phẩm của nó
    $sql_detail = "SELECT sp.ten_san_pham, sp.hinh_anh, ctdh.so_luong, ctdh.don_gia 
                   FROM chi_tiet_don_hang ctdh
                   JOIN san_pham sp ON ctdh.san_pham_id = sp.id
                   WHERE ctdh.don_hang_id = $order_id";
    $res_detail = mysqli_query($conn, $sql_detail);
    $details = [];
    while ($d = mysqli_fetch_assoc($res_detail)) {
        $details[] = [
            'ten_sp' => $d['ten_san_pham'],
            'anh' => $d['hinh_anh'],
            'sl' => (int)$d['so_luong'],
            'gia' => (float)$d['don_gia']
        ];
    }
    // Gán mảng chi tiết vào hàng dữ liệu đơn hàng
    $row['chi_tiet'] = json_encode($details, JSON_UNESCAPED_UNICODE);
    $orders[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử mua hàng | The Muzik Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <style>
        body { padding-top: 100px; }
        @media (max-width: 768px) { body { padding-top: 80px; } }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/header.php'; ?>
    
    <main class="max-w-4xl mx-auto p-4 lg:p-6">
        <div class="flex items-center gap-4 mb-8">
            <a href="index.php" class="w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-sm hover:bg-slate-100 transition">
                <i class="fa-solid fa-arrow-left text-slate-600"></i>
            </a>
            <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tight mt-36">
                Lịch sử <span class="text-indigo-600">đơn hàng</span>
            </h1>
        </div>

        <div class="space-y-4">
            <?php if (empty($orders)): ?>
                <div class="bg-white p-16 rounded-3xl text-center shadow-sm border border-dashed border-slate-200">
                    <div class="text-slate-200 mb-4">
                        <i class="fa-solid fa-box-open text-6xl"></i>
                    </div>
                    <p class="text-slate-400 font-medium mb-6">Bạn chưa có giao dịch nào gần đây.</p>
                    <a href="index.php" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">
                        Mua sắm ngay
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): 
                    // Logic màu sắc trạng thái
                    $status = $order['trang_thai'];
                    $status_class = "bg-amber-50 text-amber-600"; // Mặc định: Chờ xử lý
                    if(strpos($status, 'Hủy') !== false) $status_class = "bg-rose-50 text-rose-600";
                    if(strpos($status, 'Thành công') !== false || strpos($status, 'đã giao') !== false) $status_class = "bg-emerald-50 text-emerald-600";
                ?>
                <!-- Trong vòng lặp foreach ($orders as $order) -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:border-indigo-200 transition-all duration-300">
    <div class="flex gap-4 items-center">
        <div class="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400">
            <i class="fa-solid fa-file-invoice text-xl"></i>
        </div>
        <div>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Đơn hàng</span>
            <h3 class="text-lg font-black text-slate-800 leading-none mb-1">#DH-<?= $order['id'] ?></h3>
            <p class="text-xs text-slate-500 font-medium italic">
                <?= date('d/m/Y H:i', strtotime($order['date_display'])) ?>
            </p>
        </div>
    </div>

    <div class="flex items-center gap-6 w-full md:w-auto justify-between md:justify-end border-t md:border-none pt-4 md:pt-0">
        <div class="text-left md:text-right">
            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-1">Tổng tiền</p>
            <p class="text-xl font-black text-indigo-600"><?= number_format($order['tong_tien'], 0, ',', '.') ?>đ</p>
        </div>
        <div class="flex flex-col items-end gap-2">
            <span class="px-3 py-1 <?= $status_class ?> rounded-full text-[10px] font-black uppercase tracking-wider">
                <?= $status ?>
            </span>
            <!-- FIX TẠI ĐÂY: Thêm sự kiện onclick -->
            <button onclick='showDetail(<?= $order['chi_tiet'] ?>, "<?= $order['id'] ?>", "<?= number_format($order['tong_tien'], 0, ',', '.') ?>")' 
                    class="text-[10px] font-bold text-indigo-500 hover:text-indigo-700 transition uppercase underline underline-offset-4">
                Chi tiết <i class="fa-solid fa-chevron-right ml-1"></i>
            </button>
        </div>
    </div>
</div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- MODAL CHI TIẾT ĐƠN HÀNG -->
    <div id="orderModal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity">
        <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden transform transition-all">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h2 class="text-xl font-black text-slate-800 uppercase italic">Chi tiết đơn <span id="modalOrderId" class="text-indigo-600"></span></h2>
                <button onclick="closeModal()" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <i class="fa-solid fa-circle-xmark text-2xl"></i>
                </button>
            </div>
            <div id="modalBody" class="p-6 max-h-[60vh] overflow-y-auto space-y-4 custom-scrollbar">
                <!-- Nội dung sản phẩm sẽ đổ vào đây -->
            </div>
            <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-between items-center">
                <span class="font-bold text-slate-500 uppercase text-xs tracking-widest">Thanh toán</span>
                <span id="modalTotal" class="text-2xl font-black text-indigo-600"></span>
            </div>
        </div>
    </div>

    <script>
        function showDetail(items, id, total) {
            const modal = document.getElementById('orderModal');
            const body = document.getElementById('modalBody');
            document.getElementById('modalOrderId').innerText = '#' + id;
            document.getElementById('modalTotal').innerText = total + 'đ';
            
            body.innerHTML = ''; // Xóa trắng nội dung cũ
            
            items.forEach(item => {
                body.innerHTML += `
                    <div class="flex items-center gap-4 p-3 rounded-2xl border border-slate-100 hover:bg-slate-50 transition">
                        <img src="uploads/image/album/${item.anh}" class="w-16 h-16 rounded-xl object-cover shadow-sm">
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-800 text-sm line-clamp-1">${item.ten_sp}</h4>
                            <p class="text-xs text-slate-500 font-medium">${new Intl.NumberFormat('vi-VN').format(item.gia)}đ x ${item.sl}</p>
                        </div>
                        <div class="font-black text-slate-700 text-sm">
                            ${new Intl.NumberFormat('vi-VN').format(item.gia * item.sl)}đ
                        </div>
                    </div>
                `;
            });

            modal.classList.remove('hidden');
            document.body.classList.add('modal-active');
        }

        function closeModal() {
            document.getElementById('orderModal').classList.add('hidden');
            document.body.classList.remove('modal-active');
        }

        // Đóng khi click ngoài vùng modal
        window.onclick = function(event) {
            const modal = document.getElementById('orderModal');
            if (event.target == modal) closeModal();
        }
    </script>
<div class ="mt-24"></div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>