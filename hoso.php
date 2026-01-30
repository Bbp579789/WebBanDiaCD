<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hồ sơ cá nhân</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-white-100">
<?php include "includes/header.php"; ?>

<div class="max-w-4xl mx-auto mt-10 p-6 bg-gray-200 rounded-md shadow-md">
    <h2 class="text-3xl font-bold mb-6 text-center">Hồ sơ cá nhân</h2>

    <div class="bg-white p-6 rounded-md shadow-md space-y-4 text-lg">

        <p>
            <strong>Tên đăng nhập:</strong>
            <?= htmlspecialchars($_SESSION["username"] ?? "") ?>
        </p>

        <p>
            <strong>Họ tên:</strong>
            <?= htmlspecialchars($_SESSION["name"] ?? "") ?>
        </p>

        <p>
            <strong>Vai trò:</strong>
            <?= htmlspecialchars($_SESSION["role"] ?? "") ?>
        </p>

        <p>
            <strong>Email:</strong>
            <?= htmlspecialchars($_SESSION["email"] ?? "") ?>
        </p>

        <p>
            <strong>Số điện thoại:</strong>
            <?= htmlspecialchars($_SESSION["phone"] ?? "") ?>
        </p>

        <p>
            <strong>Địa chỉ:</strong>
            <?= htmlspecialchars($_SESSION["address"] ?? "") ?>
        </p>

    </div>
</div>


<!-- Footer -->
<?php $footer_mt = 'mt-24'; ?>
<?php include "includes/footer.php"; ?>
</body>
</html>
