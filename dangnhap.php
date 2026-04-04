<?php
session_start();
require_once "config/database.php";

$error = "";

// lưu lại giá trị nhập
$old_username = "";
$old_password = "";

// điều khiển viền đỏ
$username_error = false;
$password_error = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    $old_username = $username;
    $old_password = $password;

    //  thiếu dữ liệu
    if ($username === "" || $password === "") {
        $error = "empty";
        $username_error = true;
        $password_error = true;
        $old_username = "";
        $old_password = "";
    } else {

        $sql = "SELECT * FROM nguoi_dung WHERE ten_dang_nhap = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        //  sai username
        if ($result->num_rows === 0) {
            $error = "username";
            $username_error = true;
            $old_username = "";
        } else {
            $user = $result->fetch_assoc();

            //  sai password
            if ($password !== $user["mat_khau"]) {
                $error = "password";
                $password_error = true;
                $old_password = "";
            }
            //  bị khóa
            elseif ($user["trang_thai"] != 1) {
                $error = "blocked";
            }
            //  đăng nhập OK
            else {
                $_SESSION["user_id"]  = $user["id"];
                $_SESSION["username"] = $user["ten_dang_nhap"];
                $_SESSION["name"]     = $user["ho_ten"];
                $_SESSION["role"]     = $user["vai_tro"];
                $_SESSION["email"]   = $user["email"];
                $_SESSION["phone"]   = $user["so_dien_thoai"];
                $_SESSION["address"] = $user["dia_chi"];

                //  load cart từ DB
$user_id = $user["id"];

$sql = "SELECT c.*, s.ten_san_pham, s.gia, s.hinh_anh 
        FROM cart c
        JOIN san_pham s ON c.san_pham_id = s.id
        WHERE c.nguoi_dung_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$_SESSION['cart'] = [];

while ($row = $result->fetch_assoc()) {
    $_SESSION['cart'][$row['san_pham_id']] = [
        "name" => $row['ten_san_pham'],
        "price" => $row['gia'],
        "quantity" => $row['so_luong'],
        "img" => $row['hinh_anh'],
         "theloai" => $row['theloai']
    ];
}

                header("Location: index.php");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đăng nhập</title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
@keyframes toastIn {
  from { opacity: 0; transform: translateX(80px) scale(.9); }
  to   { opacity: 1; transform: translateX(0) scale(1); }
}
@keyframes toastOut {
  from { opacity: 1; transform: translateX(0) scale(1); }
  to   { opacity: 0; transform: translateX(80px) scale(.9); }
}
.toast-in { animation: toastIn .4s cubic-bezier(.4,0,.2,1); }
.toast-out { animation: toastOut .3s ease-in forwards; }
</style>
</head>

<body class="bg-white">
  
<!-- Header -->
<?php include "includes/header.php"; ?>

<!-- TOAST ERROR -->
<?php if ($error !== ""): ?>
<div id="toast"
     class="fixed top-6 right-6 z-50 w-[420px]
            bg-green-400 border border-red-400 text-red-800
            px-6 py-5 rounded-xl shadow-2xl
            flex items-start gap-4 toast-in">

  <div class="flex-1 text-sm leading-relaxed">
    <p class="font-semibold text-base mb-1">Lỗi đăng nhập</p>

    <?php if ($error === "empty"): ?>
      Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.
    <?php elseif ($error === "username"): ?>
      Tên đăng nhập không tồn tại.
    <?php elseif ($error === "password"): ?>
      Mật khẩu không chính xác.
    <?php elseif ($error === "blocked"): ?>
      Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.
    <?php endif; ?>
  </div>

  <button onclick="closeToast()"
          class="text-xl text-red-600 hover:text-red-800 leading-none">
    ✕
  </button>
</div>
<?php endif; ?>

<!-- FORM -->
<div class="flex justify-center items-center mt-24">
  <div class="bg-green-100 p-8 rounded-xl shadow-lg w-96">
    <h2 class="text-2xl font-semibold mb-6 text-center">Đăng nhập</h2>

    <form method="POST" class="space-y-4">

      <!-- USERNAME -->
      <div>
        <label class="block text-gray-700">Tên đăng nhập</label>
        <input type="text" name="username"
               value="<?= htmlspecialchars($old_username) ?>"
               class="w-full border p-2 rounded mt-1 focus:outline-none
               <?= $username_error
                    ? 'border-red-500 focus:ring-red-500'
                    : 'border-gray-300 focus:ring-green-600' ?>">
      </div>

      <!-- PASSWORD -->
      <div>
        <label class="block text-gray-700">Mật khẩu</label>
        <input type="password" name="password"
               value="<?= htmlspecialchars($old_password) ?>"
               class="w-full border p-2 rounded mt-1 focus:outline-none
               <?= $password_error
                    ? 'border-red-500 focus:ring-red-500'
                    : 'border-gray-300 focus:ring-green-600' ?>">
      </div>

      <a href="quenmatkhau.php"
         class="text-green-600 hover:underline flex justify-end text-sm">
        Quên mật khẩu
      </a>

      <button type="submit"
              class="w-full bg-green-600 text-white py-2 rounded
                     hover:bg-green-700 transition">
        Đăng nhập
      </button>
    </form>

    <div class="text-sm text-gray-500 mt-4 text-center">
      Bạn chưa có tài khoản?
      <a href="dangky.php"
         class="text-green-600 font-medium hover:underline">
        Đăng ký ngay
      </a>
    </div>
  </div>
</div>

<!-- Footer -->
<?php $footer_mt = 'mt-24'; ?>
<?php include "includes/footer.php"; ?>

<script>
function closeToast() {
  const toast = document.getElementById("toast");
  if (!toast) return;
  toast.classList.remove("toast-in");
  toast.classList.add("toast-out");
  setTimeout(() => toast.remove(), 300);
}
setTimeout(closeToast, 4500);
</script>

</body>
</html>
