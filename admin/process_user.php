<?php
session_start();
// Kết nối database bằng PDO
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
} catch (PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- TRƯỜNG HỢP 1: KHỞI TẠO LẠI MẬT KHẨU (GỌI TỪ NÚT VÀNG) ---
if ($action === 'reset_password') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id > 0) {
        // Mật khẩu mặc định là 123456
        $new_pass = "123456";
        // BẮT BUỘC: Mã hóa mật khẩu trước khi lưu vào DB
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE nguoi_dung SET mat_khau = ? WHERE id = ?");
        if ($stmt->execute([$hashed_pass, $id])) {
            echo 'success';
        } else {
            echo 'error_db';
        }
    } else {
        echo 'error_id';
    }
    exit;
}

// --- TRƯỜNG HỢP 2: LƯU THÔNG TIN (GỌI TỪ NÚT LƯU - THÊM/SỬA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === '') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $ho_ten = $_POST['ho_ten'];
    $ten_dang_nhap = $_POST['ten_dang_nhap'];
    $email = $_POST['email'];
    $so_dien_thoai = $_POST['so_dien_thoai'];
    $dia_chi = $_POST['dia_chi'];
    $vai_tro = $_POST['vai_tro'];
    $trang_thai = $_POST['trang_thai'];

    if ($id > 0) {
        // CẬP NHẬT TÀI KHOẢN (Không cập nhật mật khẩu ở bước này)
        $sql = "UPDATE nguoi_dung SET ho_ten=?, ten_dang_nhap=?, email=?, so_dien_thoai=?, dia_chi=?, vai_tro=?, trang_thai=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ho_ten, $ten_dang_nhap, $email, $so_dien_thoai, $dia_chi, $vai_tro, $trang_thai, $id]);
    } else {
        // THÊM MỚI TÀI KHOẢN
        // Mặc định cho mật khẩu mới là 123456 (đã mã hóa)
        $default_pass = password_hash("123456", PASSWORD_DEFAULT);
        $sql = "INSERT INTO nguoi_dung (ho_ten, ten_dang_nhap, email, so_dien_thoai, dia_chi, vai_tro, trang_thai, mat_khau) VALUES (?,?,?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ho_ten, $ten_dang_nhap, $email, $so_dien_thoai, $dia_chi, $vai_tro, $trang_thai, $default_pass]);
    }

    header("Location: manager-user.php?msg=success");
    exit;
}