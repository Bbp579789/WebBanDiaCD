<?php
ob_start(); 
session_start();
require_once __DIR__ . '/config/database.php'; // Biến kết nối là $conn

if (!isset($conn) || !$conn) { die('Kết nối CSDL thất bại.'); }

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
$session_user_id = (int)$_SESSION['user_id'];

// 2. Lấy thông tin người dùng từ bảng nguoi_dung
$user = null;
$stmt = mysqli_prepare($conn, "SELECT id, ho_ten, so_dien_thoai, dia_chi FROM nguoi_dung WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $session_user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

/// 3. Xử lý Giỏ hàng từ Session
$cart_items = [];
$subtotal = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $pid => $data) {
        // Kiểm tra đa dạng các kiểu lưu trữ quantity hoặc qty
        if (is_array($data)) {
            if (isset($data['qty'])) {
                $qty = (int)$data['qty'];
            } elseif (isset($data['quantity'])) {
                $qty = (int)$data['quantity'];
            } else {
                $qty = 1;
            }
        } else {
            $qty = (int)$data;
        }

        if ($qty <= 0) continue;

        $stmt = mysqli_prepare($conn, "SELECT id, ten_san_pham, gia, hinh_anh FROM san_pham WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $pid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $p = mysqli_fetch_assoc($result);
        
        if ($p) {
            $p_total = (float)$p['gia'] * $qty;
            $subtotal += $p_total;
            // Lưu lại qty vào mảng để hiển thị bên dưới
            $cart_items[] = array_merge($p, ['qty' => $qty, 'subtotal' => $p_total]);
        }
        mysqli_stmt_close($stmt);
    }
}
$shipping = ($subtotal > 0 && $subtotal < 500000) ? 30000 : 0;
$grand_total = $subtotal + $shipping;

// 4. XỬ LÝ LƯU ĐƠN HÀNG (Fix đúng tên cột trong DB của bạn)
$msg_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    // Trong DB của bạn dùng cột 'dia_chi_nhan_hang', nên ta lấy địa chỉ từ Form
    $addr = trim($_POST['address'] ?: $user['dia_chi']);

    if (empty($addr) || empty($cart_items)) {
        $msg_error = "Vui lòng nhập đầy đủ thông tin địa chỉ.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // Bước A: Lưu vào bảng don_hang (Khớp với cột: nguoi_dung_id, dia_chi_nhan_hang, trang_thai, tong_tien)
            $sql_order = "INSERT INTO don_hang (nguoi_dung_id, dia_chi_nhan_hang, trang_thai, tong_tien) VALUES (?, ?, 'Đang xử lý', ?)";
            $stmt = mysqli_prepare($conn, $sql_order);
            mysqli_stmt_bind_param($stmt, 'isd', $session_user_id, $addr, $grand_total);
            mysqli_stmt_execute($stmt);
            $new_order_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Bước B: Lưu vào bảng chi_tiet_don_hang
            // $stmt_item = mysqli_prepare($conn, "INSERT INTO chi_tiet_don_hang (don_hang_id, san_pham_id, so_luong, don_gia) VALUES (?, ?, ?, ?)");
            // foreach ($cart_items as $item) {
            //     mysqli_stmt_bind_param($stmt_item, 'iiid', $new_order_id, $item['id'], $item['qty'], $item['gia']);
            //     mysqli_stmt_execute($stmt_item);
            // }
            // Bước B: Lưu vào bảng chi_tiet_don_hang
$stmt_item = mysqli_prepare($conn, "INSERT INTO chi_tiet_don_hang (don_hang_id, san_pham_id, so_luong, don_gia) VALUES (?, ?, ?, ?)");
// THÊM LỆNH NÀY: Chuẩn bị lệnh trừ kho
$stmt_update_stock = mysqli_prepare($conn, "UPDATE san_pham SET so_luong = so_luong - ? WHERE id = ?");

foreach ($cart_items as $item) {
    // 1. Lưu vào chi_tiet_don_hang (Khớp tên cột của bạn)
    $sql_item = "INSERT INTO chi_tiet_don_hang (don_hang_id, san_pham_id, so_luong, don_gia) VALUES (?, ?, ?, ?)";
    $stmt_item = mysqli_prepare($conn, $sql_item);
    mysqli_stmt_bind_param($stmt_item, 'iiid', $new_order_id, $item['id'], $item['qty'], $item['gia']);
    mysqli_stmt_execute($stmt_item);

    // 2. CẬP NHẬT GIẢM SỐ LƯỢNG TRONG BẢNG san_pham
    $sql_update_stock = "UPDATE san_pham SET so_luong = so_luong - ? WHERE id = ?";
    $stmt_stock = mysqli_prepare($conn, $sql_update_stock);
    mysqli_stmt_bind_param($stmt_stock, 'ii', $item['qty'], $item['id']);
    mysqli_stmt_execute($stmt_stock);
}


            mysqli_stmt_close($stmt_item);

            mysqli_commit($conn);
            unset($_SESSION['cart']); 

            // CHUYỂN HƯỚNG SANG TRANG LỊCH SỬ MUA HÀNG
            echo "<script>
                alert('Đặt hàng thành công!');
                window.location.href = 'lichsudonhang.php';
            </script>";
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $msg_error = "Lỗi đặt hàng: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác nhận thanh toán</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <style>
        body { padding-top: 100px; } 
        @media (max-width: 768px) { body { padding-top: 80px; } }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/header.php'; ?>

    <main class="max-w-6xl mx-auto p-4 lg:p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- FORM NHẬP THÔNG TIN -->
            <div class="lg:col-span-2 space-y-6">
                <h1 class="text-2xl font-black text-slate-800 uppercase italic">Thanh toán & Giao hàng</h1>
                
                <form method="POST" class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 space-y-6">
                    <input type="hidden" name="action" value="place_order">
                    
                    <div class="space-y-6">
    <!-- Tiêu đề lớn của nhóm -->
    <label class="block text-sm font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2">
        Thông tin nhận hàng
    </label>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Họ tên -->
        <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-500 uppercase ml-1">Họ tên người nhận</label>
            <input type="text" name="fullname" 
                   value="<?= htmlspecialchars($user['ho_ten']) ?>" 
                   placeholder="Nhập họ và tên..." 
                   class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition shadow-sm">
        </div>

        <!-- Số điện thoại -->
        <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-500 uppercase ml-1">Số điện thoại</label>
            <input type="text" name="phone" 
                   value="<?= htmlspecialchars($user['so_dien_thoai']) ?>" 
                   placeholder="Nhập số điện thoại..." 
                   class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition shadow-sm">
        </div>
    </div>

    <!-- Địa chỉ -->
    <div class="space-y-2">
        <label class="block text-xs font-bold text-slate-500 uppercase ml-1">Địa chỉ nhận hàng</label>
        <textarea name="address" rows="3" 
                  placeholder="Số nhà, tên đường, phường/xã, quận/huyện..." 
                  class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition shadow-sm"><?= htmlspecialchars($user['dia_chi']) ?></textarea>
    </div>
</div>

                    <div class="space-y-4">
                        <label class="block text-sm font-bold text-slate-400 uppercase tracking-widest">Phương thức thanh toán</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="flex items-center p-4 border border-slate-200 rounded-2xl cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50">
                                <input type="radio" name="payment_method" value="COD" checked class="w-4 h-4 text-indigo-600">
                                <span class="ml-3 font-bold">Tiền mặt (COD)</span>
                            </label>
                            <label class="flex items-center p-4 border border-slate-200 rounded-2xl cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50">
                                <input type="radio" name="payment_method" value="BANK" class="w-4 h-4 text-indigo-600">
                                <span class="ml-3 font-bold">Chuyển khoản</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-5 bg-indigo-600 text-white rounded-2xl font-black text-xl hover:bg-indigo-700 transition shadow-xl shadow-indigo-100 uppercase">
                        Xác nhận đặt hàng • <?= number_format($grand_total) ?>đ
                    </button>
                </form>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 sticky top-48">
                    <h2 class="font-black text-slate-800 border-b pb-4 mb-4 uppercase text-xs">Tóm tắt giỏ hàng</h2>
                    <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="flex gap-4 items-center">
                            <img src="uploads/image/album/<?= $item['hinh_anh'] ?>" class="w-14 h-14 rounded-xl object-cover border">
                            <div class="flex-1">
                                <p class="font-bold text-slate-800 text-xs line-clamp-1"><?= $item['ten_san_pham'] ?></p>
                                <p class="text-xs text-indigo-500 font-bold"><?= $item['qty'] ?> x <?= number_format($item['gia']) ?>đ</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex justify-between text-sm font-bold text-slate-500 mt-4">
                        <span>Tạm tính</span>
                        <span><?= number_format($subtotal) ?>đ</span>
                    </div>
                    <!-- TỪ 2 sản phẩm trở lên thì freeship -->
                    <div class="flex justify-between text-sm font-bold text-slate-500 mt-2">
                        <span>Phí vận chuyển</span>
                        <span><?= number_format($shipping) ?>đ</span>
                    </div>
                    <div class="border-t mt-6 pt-6 flex justify-between text-xl font-black text-indigo-600">
                        <span>Tổng tiền</span>
                        <span><?= number_format($grand_total) ?>đ</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>