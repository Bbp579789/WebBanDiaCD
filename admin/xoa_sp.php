<?php
session_start();

// Kết nối DB
$host = 'localhost'; $db = 'webbandiacd'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) { die("Lỗi: " . $e->getMessage()); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        // CHỨC NĂNG XÓA MỀM: Chuyển trạng thái về 0 (Khóa/Ẩn)
        // Việc này giúp đơn hàng cũ vẫn hiển thị được tên sản phẩm
        $sql = "UPDATE san_pham SET trang_thai = 0 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$id])) {
            header("Location: manager-cd.php?msg=deleted");
            exit();
        }
    } catch (PDOException $e) {
        die("Lỗi khi xóa: " . $e->getMessage());
    }
} else {
    header("Location: manager-cd.php");
    exit();
}