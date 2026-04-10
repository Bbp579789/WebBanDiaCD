<?php
session_start();
// Optional: kiểm tra quyền admin
// if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) { http_response_code(401); echo 'Unauthorized'; exit; }

$host = 'localhost'; $db = 'webbandiacd'; $user = 'root'; $pass = ''; $charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('DB error: ' . $e->getMessage());
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($action === 'reset_password') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) { echo 'Invalid id'; exit; }
        $default = '123456';
        $hash = password_hash($default, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE nguoi_dung SET mat_khau = ? WHERE id = ?");
        $stmt->execute([$hash, $id]);
        echo 'success'; exit;
    }

    // Create or update user (form posts here)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $ho_ten = trim($_POST['ho_ten'] ?? '');
        $ten_dang_nhap = trim($_POST['ten_dang_nhap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $sdt = trim($_POST['so_dien_thoai'] ?? '');
        $dia_chi = trim($_POST['dia_chi'] ?? '');
        $vai_tro = trim($_POST['vai_tro'] ?? 'user');
        $trang_thai = isset($_POST['trang_thai']) ? (int)$_POST['trang_thai'] : 1;

        if ($id > 0) {
            // update
            $stmt = $pdo->prepare("UPDATE nguoi_dung SET ho_ten=?, ten_dang_nhap=?, email=?, so_dien_thoai=?, dia_chi=?, vai_tro=?, trang_thai=? WHERE id=?");
            $stmt->execute([$ho_ten, $ten_dang_nhap, $email, $sdt, $dia_chi, $vai_tro, $trang_thai, $id]);
            header('Location: manager-user.php?msg=updated'); exit;
        } else {
            // create with default password 123456
            $default = '123456';
            $hash = password_hash($default, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO nguoi_dung (ho_ten, ten_dang_nhap, mat_khau, email, so_dien_thoai, dia_chi, vai_tro, trang_thai) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ho_ten, $ten_dang_nhap, $hash, $email, $sdt, $dia_chi, $vai_tro, $trang_thai]);
            header('Location: manager-user.php?msg=created'); exit;
        }
    }

    header('Location: manager-user.php');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo 'Error: ' . $e->getMessage();
    exit;
}
?>