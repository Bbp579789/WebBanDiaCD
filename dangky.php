<?php
require_once "config/database.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $ten_dang_nhap = trim($_POST["ten_dang_nhap"] ?? "");
    $mat_khau      = trim($_POST["mat_khau"] ?? "");
    $xac_nhan      = trim($_POST["xac_nhan_mat_khau"] ?? "");
    $ho_ten        = trim($_POST["ho_ten"] ?? "");
    $email         = trim($_POST["email"] ?? "");
    $so_dien_thoai = trim($_POST["so_dien_thoai"] ?? "");
    $dia_chi       = trim($_POST["dia_chi"] ?? "");

    // KIỂM TRA RỖNG
    if (
        $ten_dang_nhap === "" || $mat_khau === "" || $xac_nhan === "" ||
        $ho_ten === "" || $email === "" || $so_dien_thoai === "" || $dia_chi === ""
    ) {
        $error = "Vui lòng nhập đầy đủ thông tin";
    }
    // KIỂM TRA XÁC NHẬN MK
    elseif ($mat_khau !== $xac_nhan) {
        $error = "Mật khẩu xác nhận không khớp";
    }
    else {
        // KIỂM TRA TRÙNG USERNAME
        $sql = "SELECT id FROM nguoi_dung WHERE ten_dang_nhap = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $ten_dang_nhap);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Tên đăng nhập đã tồn tại";
        } else {
            // INSERT USER
            $sql = "INSERT INTO nguoi_dung 
            (ten_dang_nhap, mat_khau, ho_ten, email, so_dien_thoai, dia_chi, vai_tro, trang_thai, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'user', 1, NOW())";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssss",
                $ten_dang_nhap,
                $mat_khau,
                $ho_ten,
                $email,
                $so_dien_thoai,
                $dia_chi
            );

            if ($stmt->execute()) {
                $success = "Đăng ký thành công! Bạn có thể đăng nhập.";
            } else {
                $error = "Đăng ký thất bại";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-white-100">
<?php include "includes/header.php"; ?>

<div>
    <h2 class="text-2xl font-semibold text-center my-6">Đăng ký thành viên</h2>

    <form method="POST"
          class="max-w-md mx-auto bg-white p-6 rounded-md shadow-md space-y-4">

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 px-4 py-2 rounded">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <div>
            <label class="block font-semibold mb-1">Tên đăng nhập</label>
            <input type="text" name="ten_dang_nhap" required
                   class="w-full border px-3 py-2 rounded focus:ring-2 focus:ring-green-600">
        </div>

        <div>
            <label class="block font-semibold mb-1">Mật khẩu</label>
            <input type="password" name="mat_khau" required
                   class="w-full border px-3 py-2 rounded focus:ring-2 focus:ring-green-600">
        </div>

        <div>
            <label class="block font-semibold mb-1">Nhập lại mật khẩu</label>
            <input type="password" name="xac_nhan_mat_khau" required
                   class="w-full border px-3 py-2 rounded focus:ring-2 focus:ring-green-600">
        </div>

        <div>
            <label class="block font-semibold mb-1">Họ tên</label>
            <input type="text" name="ho_ten" required
                   class="w-full border px-3 py-2 rounded focus:ring-2 focus:ring-green-600">
        </div>

        <div>
            <label class="block font-semibold mb-1">Email</label>
            <input type="email" name="email" required
                   class="w-full border px-3 py-2 rounded focus:ring-2 focus:ring-green-600">
        </div>

        <div>
            <label class="block font-semibold mb-1">Số điện thoại</label>
            <input type="text" name="so_dien_thoai" required
                   class="w-full border px-3 py-2 rounded focus:ring-2 focus:ring-green-600">
        </div>

        <div>
            <label class="block font-semibold mb-1">Địa chỉ</label>
            <input type="text" name="dia_chi" required
                   class="w-full border px-3 py-2 rounded focus:ring-2 focus:ring-green-600">
        </div>

        <button type="submit"
                class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 transition">
            Đăng ký
        </button>
    </form>
</div>

<?php include "includes/footer.php"; ?>
</body>
</html>
