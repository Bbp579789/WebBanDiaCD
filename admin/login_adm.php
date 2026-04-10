<!-- login bằng url -->
<?php
session_start();

// 1. Cấu hình mã bí mật (Nên để một chuỗi dài, khó đoán)
// Trong thực tế, chuỗi này nên được lưu trong database hoặc file config bảo mật
$secret_key = "MuzicStore_Secret_2024_Admin_Access_Token_XYZ"; 

// 2. Kiểm tra tham số 'key' trên URL
if (isset($_GET['key'])) {
    $user_key = $_GET['key'];

    // 3. So khớp mã (Dùng hash_equals để chống tấn công Timing Attack)
    if (hash_equals($secret_key, $user_key)) {
        
        // ĐĂNG NHẬP THÀNH CÔNG
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = "Quản trị viên";
        $_SESSION['role'] = "admin";

        // Chuyển hướng vào trang Dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        // Sai mã - Đá về trang chủ hoặc hiện lỗi 404 để đánh lạc hướng hacker
        die("404 Not Found - Bạn không có quyền truy cập trang này.");
    }
} else {
    // Không có tham số key - Hiện thông báo từ chối
    die("Truy cập bị từ chối.");
}
?>