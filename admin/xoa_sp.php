<?php
session_start();

/**
 * 1. CẤU HÌNH KẾT NỐI DATABASE
 * Thay đổi thông số nếu DB của bạn có user/pass khác
 */
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
} catch (\PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

/**
 * 2. XỬ LÝ LOGIC XÓA / ẨN SẢN PHẨM
 */
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // BƯỚC A: Kiểm tra xem sản phẩm này đã từng có phiếu nhập hàng chưa
        // Kiểm tra trong bảng chi_tiet_phieu_nhap
        $checkImport = $pdo->prepare("SELECT COUNT(*) FROM chi_tiet_phieu_nhap WHERE san_pham_id = ?");
        $checkImport->execute([$id]);
        $hasImported = $checkImport->fetchColumn() > 0;

        if ($hasImported) {
            /**
             * TRƯỜNG HỢP 1: ĐÃ NHẬP HÀNG
             * Hành động: Chỉ đánh dấu ẩn trên website (Soft Delete)
             * Lý do: Để giữ lại lịch sử hóa đơn nhập hàng cho kế toán.
             */
            $sqlHide = "UPDATE san_pham SET trang_thai = 0 WHERE id = ?";
            $stmtHide = $pdo->prepare($sqlHide);
            $stmtHide->execute([$id]);
            
            // Chuyển hướng về trang quản lý với thông báo "Đã ẩn"
            header("Location: manager-cd.php?msg=hidden");
            exit();

        } else {
            /**
             * TRƯỜNG HỢP 2: CHƯA TỪNG NHẬP HÀNG
             * Hành động: Xóa vĩnh viễn khỏi CSDL (Hard Delete)
             * Để tránh lỗi Foreign Key #1451, ta dùng Transaction xóa bảng con trước.
             */
            $pdo->beginTransaction();

            // 1. Xóa trong bảng chi_tiet_san_pham (Bảng con tham chiếu trực tiếp)
            $delDetail = $pdo->prepare("DELETE FROM chi_tiet_san_pham WHERE san_pham_id = ?");
            $delDetail->execute([$id]);

            // 2. Xóa trong bảng giỏ hàng (Nếu khách đang bỏ vào giỏ nhưng chưa thanh toán)
            $delCart = $pdo->prepare("DELETE FROM cart WHERE san_pham_id = ?");
            $delCart->execute([$id]);

            // 3. Xóa sản phẩm ở bảng chính (Bảng cha)
            $delMain = $pdo->prepare("DELETE FROM san_pham WHERE id = ?");
            $delMain->execute([$id]);

            $pdo->commit(); // Xác nhận hoàn tất các lệnh xóa
            
            // Chuyển hướng về trang quản lý với thông báo "Đã xóa sạch"
            header("Location: manager-cd.php?msg=deleted");
            exit();
        }

    } catch (Exception $e) {
        // Nếu có bất kỳ lỗi nào xảy ra trong Transaction, hủy bỏ toàn bộ thao tác
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Hệ thống gặp lỗi khi xử lý xóa: " . $e->getMessage());
    }
} else {
    // Nếu không có ID hợp lệ, quay về trang danh sách
    header("Location: manager-cd.php");
    exit();
}
?>