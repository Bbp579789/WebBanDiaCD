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
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$id = $_GET['id'] ?? null;
if (!$id) header("Location: manager-import.php");

// 1. Lấy thông tin phiếu chính
$stmt = $pdo->prepare("SELECT * FROM phieu_nhap WHERE id = ?");
$stmt->execute([$id]);
$phieu = $stmt->fetch();

// 2. Lấy chi tiết các sản phẩm trong phiếu này
$stmtDetails = $pdo->prepare("SELECT * FROM chi_tiet_phieu_nhap WHERE phieu_nhap_id = ?");
$stmtDetails->execute([$id]);
$details = $stmtDetails->fetchAll();

// 3. Lấy danh sách NCC và Sản phẩm để chọn lại (nếu cần)
$suppliers = $pdo->query("SELECT * FROM nha_cung_cap")->fetchAll();
$products = $pdo->query("SELECT id, ten_san_pham FROM san_pham")->fetchAll();
?>

<!-- Giao diện tương tự create-import nhưng các ô input sẽ có giá trị value="<?=$row['...']?>" -->
<!-- Khi nhấn Lưu, gửi sang process-edit-import.php -->