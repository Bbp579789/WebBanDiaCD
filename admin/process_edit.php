<?php
session_start();

// 1. KẾT NỐI DATABASE
$host = 'localhost';
$db   = 'webbandiacd';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}

// 2. KIỂM TRA DỮ LIỆU GỬI LÊN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_save'])) {
    $id = (int)$_POST['id'];
    $ten_sp = $_POST['ten_san_pham'];
    $gia_ban = $_POST['gia'];
    $the_loai_id = $_POST['the_loai_id'];
    $nghe_si_id = $_POST['nghe_si_id'];
    
    // Dữ liệu cho bảng chi_tiet_san_pham
    $mo_ta = $_POST['mo_ta_san_pham'];
    $nam = $_POST['nam_phat_hanh'];
    $hang = $_POST['hang_dia'];

    try {
        // Bắt đầu Transaction để đảm bảo an toàn dữ liệu 2 bảng
        $pdo->beginTransaction();

        // --- BƯỚC A: Cập nhật bảng san_pham ---
        $sql1 = "UPDATE san_pham SET 
                    ten_san_pham = ?, 
                    gia = ?, 
                    the_loai_id = ?, 
                    nghe_si_id = ? 
                 WHERE id = ?";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([$ten_sp, $gia_ban, $the_loai_id, $nghe_si_id, $id]);

        // --- BƯỚC B: Cập nhật bảng chi_tiet_san_pham ---
        // Sử dụng ON DUPLICATE KEY UPDATE để giải quyết việc bảng chi tiết đang trống
        $sql2 = "INSERT INTO chi_tiet_san_pham (san_pham_id, mo_ta_san_pham, nam_phat_hanh, hang_dia) 
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE 
                    mo_ta_san_pham = VALUES(mo_ta_san_pham), 
                    nam_phat_hanh = VALUES(nam_phat_hanh), 
                    hang_dia = VALUES(hang_dia)";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$id, $mo_ta, $nam, $hang]);

        // Hoàn tất lưu
        $pdo->commit();

        // 3. Chuyển hướng về trang chi tiết admin để kiểm tra kết quả
        header("Location: detail-product-adm.php?id=$id&status=success");
        exit();

    } catch (Exception $e) {
        // Nếu có lỗi thì hủy bỏ thay đổi để tránh sai lệch
        $pdo->rollBack();
        die("Lỗi lưu dữ liệu: " . $e->getMessage());
    }
} else {
    // Nếu truy cập trái phép thì về danh sách
    header("Location: manager-cd.php");
    exit();
}