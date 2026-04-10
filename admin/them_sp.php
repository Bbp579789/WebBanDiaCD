<?php
session_start();

// --- 1. KẾT NỐI DATABASE ---
$host = 'localhost'; $db = 'webbandiacd'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) { die("Lỗi kết nối: " . $e->getMessage()); }

// --- 2. LẤY DỮ LIỆU TỪ FORM ---
$productId   = $_POST['productId'] ?? '';
$productName = trim($_POST['productName'] ?? '');
$categoryId  = $_POST['categoryId'] ?? 0;
$artistName  = trim($_POST['artistName'] ?? '');
$gia         = $_POST['gia'] ?? 0;
$stock       = $_POST['stock'] ?? 0;
$status      = $_POST['status'] ?? 1; // Nhận trạng thái Khóa (0) hoặc Mở (1)
$desc        = trim($_POST['desc'] ?? '');
$hinh_anh    = $_POST['oldImage'] ?? '';

// --- 3. XỬ LÝ UPLOAD ẢNH ---
if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] == 0) {
    $filename = time() . '_' . $_FILES['productImage']['name'];
    if (move_uploaded_file($_FILES['productImage']['tmp_name'], "../uploads/image/album/" . $filename)) {
        $hinh_anh = $filename;
    }
}

try {
    $pdo->beginTransaction();

    // --- 4. XỬ LÝ NGHỆ SĨ (Lấy ID hoặc tạo mới) ---
    $nghe_si_id = 0;
    if (!empty($artistName)) {
        $stmtNS = $pdo->prepare("SELECT id FROM nghe_si WHERE ten_nghe_si = ?");
        $stmtNS->execute([$artistName]);
        $ns = $stmtNS->fetch();
        
        if ($ns) {
            $nghe_si_id = $ns['id'];
        } else {
            $insNS = $pdo->prepare("INSERT INTO nghe_si (ten_nghe_si) VALUES (?)");
            $insNS->execute([$artistName]);
            $nghe_si_id = $pdo->lastInsertId();
        }
    }

    if (!empty($productId)) {
        // --- TRƯỜNG HỢP: CẬP NHẬT (SỬA & KHÓA) ---
        $sql = "UPDATE san_pham SET 
                ten_san_pham = ?, the_loai_id = ?, nghe_si_id = ?, 
                gia = ?, so_luong = ?, hinh_anh = ?, trang_thai = ? 
                WHERE id = ?";
        $pdo->prepare($sql)->execute([$productName, $categoryId, $nghe_si_id, $gia, $stock, $hinh_anh, $status, $productId]);

        // Cập nhật mô tả sản phẩm
        $pdo->prepare("UPDATE chi_tiet_san_pham SET mo_ta_san_pham = ? WHERE san_pham_id = ?")
            ->execute([$desc, $productId]);

    } else {
        // --- TRƯỜNG HỢP: THÊM MỚI ---
        $sql = "INSERT INTO san_pham (ten_san_pham, the_loai_id, nghe_si_id, gia, so_luong, hinh_anh, trang_thai) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$productName, $categoryId, $nghe_si_id, $gia, $stock, $hinh_anh, $status]);
        
        $newId = $pdo->lastInsertId();

        // Thêm mô tả vào bảng chi tiết
        $pdo->prepare("INSERT INTO chi_tiet_san_pham (san_pham_id, mo_ta_san_pham) VALUES (?, ?)")
            ->execute([$newId, $desc]);
    }

    $pdo->commit();
    header("Location: manager-cd.php?status=success");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Lỗi hệ thống: " . $e->getMessage());
}