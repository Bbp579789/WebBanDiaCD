<?php
session_start();
// Kết nối Database
$host = 'localhost';
$db = 'webbandiacd';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $productId = $_POST['productId'] ?? ''; // ID nếu là sửa, trống nếu là thêm
        $productName = $_POST['productName'];
        $categoryId = $_POST['categoryId'];
        $artistName = $_POST['artistName'];
        $costPrice = $_POST['costPrice'];
        $profitRate = $_POST['profitRate'];
        $sellingPrice = $_POST['gia'];
        $stock = $_POST['stock'];
        $status = $_POST['status'];
        $desc = $_POST['desc']; // Đây là mô tả sản phẩm

        // Xử lý hình ảnh
        $imageName = $_POST['oldImage'] ?? '';
        if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] == 0) {
            $imageName = time() . '_' . $_FILES['productImage']['name'];
            move_uploaded_file($_FILES['productImage']['tmp_name'], "../uploads/image/album/" . $imageName);
        }

        // Xử lý Nghệ sĩ (Lấy ID hoặc thêm mới nếu chưa có)
        $stmt_artist = $pdo->prepare("SELECT id FROM nghe_si WHERE ten_nghe_si = ?");
        $stmt_artist->execute([$artistName]);
        $artist = $stmt_artist->fetch(PDO::FETCH_ASSOC);
        
        if ($artist) {
            $artistId = $artist['id'];
        } else {
            $stmt_add_artist = $pdo->prepare("INSERT INTO nghe_si (ten_nghe_si) VALUES (?)");
            $stmt_add_artist->execute([$artistName]);
            $artistId = $pdo->lastInsertId();
        }

        if (!empty($productId)) {
            // --- TRƯỜNG HỢP: SỬA SẢN PHẨM ---
            $pdo->beginTransaction();

            // 1. Cập nhật bảng san_pham
            $sql_update_sp = "UPDATE san_pham SET 
                ten_san_pham = ?, gia = ?, so_luong = ?, hinh_anh = ?, 
                the_loai_id = ?, nghe_si_id = ?, trang_thai = ? 
                WHERE id = ?";
            $pdo->prepare($sql_update_sp)->execute([$productName, $sellingPrice, $stock, $imageName, $categoryId, $artistId, $status, $productId]);

            // 2. Cập nhật bảng chi_tiet_san_pham (Quan trọng để hiện ở detail-order)
            // Sử dụng INSERT ... ON DUPLICATE KEY UPDATE để chắc chắn luôn có dữ liệu
            $sql_update_ct = "INSERT INTO chi_tiet_san_pham (san_pham_id, mo_ta_san_pham) 
                              VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE mo_ta_san_pham = VALUES(mo_ta_san_pham)";
            $pdo->prepare($sql_update_ct)->execute([$productId, $desc]);

            $pdo->commit();
        } else {
            // --- TRƯỜNG HỢP: THÊM MỚI ---
            $pdo->beginTransaction();

            // 1. Thêm bảng san_pham
            $sql_insert_sp = "INSERT INTO san_pham (ten_san_pham, gia, so_luong, hinh_anh, the_loai_id, nghe_si_id, trang_thai) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql_insert_sp)->execute([$productName, $sellingPrice, $stock, $imageName, $categoryId, $artistId, $status]);
            $newId = $pdo->lastInsertId();

            // 2. Thêm bảng chi_tiet_san_pham
            $sql_insert_ct = "INSERT INTO chi_tiet_san_pham (san_pham_id, mo_ta_san_pham) VALUES (?, ?)";
            $pdo->prepare($sql_insert_ct)->execute([$newId, $desc]);

            $pdo->commit();
        }

        header("Location: manager-cd.php?success=1");
        exit();
    }
} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    die("Lỗi xử lý: " . $e->getMessage());
}