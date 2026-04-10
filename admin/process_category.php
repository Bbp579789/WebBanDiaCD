<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Nếu chưa đăng nhập qua URL bí mật, không cho xem nội dung
    die("Bạn phải đăng nhập bằng URL bí mật để truy cập.");
}
?>

<?php
session_start();

// Cấu hình DB
$host = 'localhost';
$db   = 'webbandiacd';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$pdo = null;

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Nếu không kết nối được, dừng và hiển thị lỗi rõ ràng
    die("Lỗi kết nối DB: " . $e->getMessage());
}

// action có thể ở GET (delete via fetch) hoặc POST (form submit)
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($action === 'add') {
        $name = trim($_POST['categoryName'] ?? '');
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

        if ($name === '') {
            header("Location: manager-category.php?err=empty");
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO the_loai (ten_the_loai, trang_thai) VALUES (?, ?)");
        $stmt->execute([$name, $status]);

        header("Location: manager-category.php?msg=add_success");
        exit;
    }

    if ($action === 'edit') {
        $id = isset($_POST['categoryId']) ? (int)$_POST['categoryId'] : 0;
        $name = trim($_POST['categoryName'] ?? '');
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

        if ($id <= 0 || $name === '') {
            header("Location: manager-category.php?err=invalid");
            exit;
        }

        $stmt = $pdo->prepare("UPDATE the_loai SET ten_the_loai = ?, trang_thai = ? WHERE id = ?");
        $stmt->execute([$name, $status, $id]);

        header("Location: manager-category.php?msg=edit_success");
        exit;
    }

    if ($action === 'delete') {
        // gọi bằng fetch POST (AJAX)
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            echo 'Invalid id';
            exit;
        }

        // kiểm tra còn sản phẩm thuộc danh mục không
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM san_pham WHERE the_loai_id = ?");
        $cnt->execute([$id]);
        if ($cnt->fetchColumn() > 0) {
            echo 'Không thể xóa: còn sản phẩm thuộc danh mục. Hãy chuyển hoặc xóa sản phẩm trước.';
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM the_loai WHERE id = ?");
        $stmt->execute([$id]);

        echo 'success';
        exit;
    }

    // Nếu không match action, quay về trang quản lý
    header("Location: manager-category.php");
    exit;

} catch (PDOException $e) {
    error_log("process_category error: " . $e->getMessage());
    echo 'Error: ' . $e->getMessage();
    exit;
}
?>