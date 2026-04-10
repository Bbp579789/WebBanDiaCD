<?php
include '../config/database.php'; // Đường dẫn tới file kết nối PDO của bạn

$phieu_id = $_GET['phieu_id'] ?? 0;

try {
    // Join với bảng sản phẩm để lấy tên nếu cần, nhưng JS chỉ cần ID để chọn Option
    $stmt = $pdo->prepare("SELECT san_pham_id, so_luong, don_gia FROM chi_tiet_phieu_nhap WHERE phieu_nhap_id = ?");
    $stmt->execute([$phieu_id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Bắt buộc phải trả về Header JSON
    header('Content-Type: application/json');
    echo json_encode($details);
} catch (Exception $e) {
    echo json_encode([]);
}
exit;