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
} catch (\PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phieu_id = $_POST['phieu_id'];
    $ncc_id = $_POST['ncc_id'];
    $ngay_nhap = $_POST['ngay_nhap'];
    $total_amount = $_POST['total_amount'];
    $items = $_POST['items'];

    try {
        $pdo->beginTransaction();

        // 1. Cập nhật thông tin phiếu chính
        $stmt = $pdo->prepare("UPDATE phieu_nhap SET nha_cung_cap_id = ?, ngay_nhap = ?, tong_tien = ? WHERE id = ?");
        $stmt->execute([$ncc_id, $ngay_nhap, $total_amount, $phieu_id]);

        // 2. Xóa các chi tiết sản phẩm cũ của phiếu này
        $pdo->prepare("DELETE FROM chi_tiet_phieu_nhap WHERE phieu_nhap_id = ?")->execute([$phieu_id]);

        // 3. Thêm mới lại toàn bộ chi tiết sản phẩm từ Form
        foreach ($items as $item) {
            $sql = "INSERT INTO chi_tiet_phieu_nhap (phieu_nhap_id, san_pham_id, so_luong, don_gia) VALUES (?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$phieu_id, $item['product_id'], $item['qty'], $item['price']]);
            
            // Ở đây bạn có thể thêm logic tính toán lại giá bình quân nếu cần
        }

        $pdo->commit();
        header("Location: manager-import.php?msg=updated");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Lỗi: " . $e->getMessage());
    }
}