<?php
session_start();
include __DIR__ . "/config/database.php";

// 🔥 check login
if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 🔥 lấy user
$sql_user = "SELECT * FROM nguoi_dung WHERE id = $user_id";
$res_user = mysqli_query($conn, $sql_user);
$user = mysqli_fetch_assoc($res_user);

// 🔥 lấy giỏ hàng
$sql_cart = "
    SELECT sp.ten_san_pham, sp.gia, c.so_luong
    FROM cart c
    JOIN san_pham sp ON c.san_pham_id = sp.id
    WHERE c.nguoi_dung_id = $user_id
";
$res_cart = mysqli_query($conn, $sql_cart);

$total = 0;
$cart_items = [];

while ($row = mysqli_fetch_assoc($res_cart)) {
    $total += $row['gia'] * $row['so_luong'];
    $cart_items[] = $row;
}

// 🔥 lấy địa chỉ
$sql_addr = "SELECT * FROM dia_chi WHERE nguoi_dung_id = $user_id";
$res_addr = mysqli_query($conn, $sql_addr);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Thông tin đặt hàng</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col bg-gray-100">

<!-- HEADER -->
<?php include "includes/header.php"; ?>
<div class="mt-24"></div>

<!-- CONTENT -->
<div class="flex-grow max-w-4xl mx-auto bg-white p-6 rounded shadow">

    <h1 class="text-2xl font-bold mb-4">Thông tin đặt hàng</h1>

    <!-- USER -->
   <div class="mb-6">
    <h2 class="font-semibold mb-2">Thông tin khách hàng</h2>

    <input class="border p-2 w-full mb-2"
        value="<?= $user['ho_ten'] ?>" readonly>

    <input class="border p-2 w-full mb-2"
        value="<?= $user['so_dien_thoai'] ?>" readonly>

    <input class="border p-2 w-full"
        value="<?= $user['email'] ?>" readonly>
</div>

    <!-- ADDRESS -->
    <div class="mb-6">
        <h2 class="font-semibold mb-2">Địa chỉ</h2>

        <select id="address-dia-chi" class="border p-2 w-full mb-2">
            <option value="">-- Chọn địa chỉ --</option>

            <?php if ($res_addr && mysqli_num_rows($res_addr) > 0) { ?>
                <?php while ($addr = mysqli_fetch_assoc($res_addr)) { ?>
                    <option value="<?= $addr['id'] ?>">
                        <?= $addr['dia_chi'] ?>
                    </option>
                <?php } ?>
            <?php } else { ?>
                <option disabled>Chưa có địa chỉ</option>
            <?php } ?>
        </select>

        <button onclick="showPopup()"
            class="bg-green-500 text-white px-3 py-1 rounded">
            + Thêm địa chỉ
        </button>
    </div>

    <!-- CART -->
    <div class="mb-6">
        <h2 class="font-semibold mb-2">Sản phẩm</h2>

        <?php if (empty($cart_items)) { ?>
            <p class="text-red-500">Giỏ hàng trống</p>
        <?php } else { ?>
            <?php foreach ($cart_items as $item) { ?>
                <div class="flex justify-between border-b py-2">
                    <span><?= $item['ten_san_pham'] ?></span>
                    <span>
                        <?= $item['so_luong'] ?> x 
                        <?= number_format($item['gia']) ?>đ
                    </span>
                </div>
            <?php } ?>
        <?php } ?>
    </div>

    <!-- TOTAL -->
    <div class="flex justify-between items-center mt-6">
        <span class="text-lg font-bold">
            Tổng tiền: <?= number_format($total) ?>đ
        </span>

        <button onclick="datHang()" 
            class="bg-blue-500 text-white px-4 py-2 rounded">
            Thanh toán
        </button>
    </div>

</div>

<!-- FOOTER -->
<?php include "includes/footer.php"; ?>



<!-- POPUP ADD ADDRESS -->
<div id="popup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white p-4 rounded w-96">
        <h2 class="text-lg font-bold mb-2">Thêm địa chỉ</h2>

        <input id="dc" class="border p-2 w-full mb-2" placeholder="Nhập địa chỉ...">

        <div class="flex justify-end gap-2">
            <button onclick="hidePopup()" class="px-3 py-1 border">Hủy</button>
            <button onclick="themDiaChi()" class="bg-green-500 text-white px-3 py-1">Lưu</button>
        </div>
    </div>
</div>

<!-- SCRIPT -->
<script>
function showPopup(){
    document.getElementById("popup").classList.remove("hidden");
}

function hidePopup(){
    document.getElementById("popup").classList.add("hidden");
}

// 🔥 thêm địa chỉ
function themDiaChi(){
    let dc = document.getElementById("dc").value;

    if(dc.trim() == ""){
        alert("Nhập địa chỉ!");
        return;
    }

    fetch("them_dia_chi.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "dia_chi=" + encodeURIComponent(dc)
    })
    .then(res => res.text())
    .then(data => {
        if(data == "success"){
            alert("Đã thêm!");
            location.reload();
        } else {
            alert("Lỗi!");
        }
    });
}

// 🔥 đặt hàng
function datHang(){
    let addr = document.getElementById("address-dia-chi").value;

    if(addr == ""){
        alert("Chọn địa chỉ!");
        return;
    }

    fetch("xuly_dathang.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "dia_chi_id=" + addr
    })
    .then(res => res.text())
    .then(data => {
        if (data == "success") {
            alert("Đặt hàng thành công!");
            window.location = "index.php";
        } else {
            alert("Lỗi đặt hàng!");
        }
    });
}
</script>
<script src="assets/js/them_dia_chi.js"></script>
</body>
</html>