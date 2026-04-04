<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$total = 0;

// lọc dữ liệu hợp lệ
$validCart = [];

foreach ($cart as $id => $item) {
    if (is_array($item)) {
        $validCart[$id] = $item;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Giỏ hàng</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col">

    <?php include "includes/header.php"; ?>

    <div class="mt-24"></div>

    <div class="flex-1">
        <div class="max-w-6xl mx-auto p-4 mt-24">

            <div id="cart-container">

                <?php if (empty($validCart)): ?>

                    <p class="text-center text-gray-500 text-lg mt-10">
                        🛒 Không có sản phẩm trong giỏ hàng
                    </p>

                <?php else: ?>
                    <h1 class="text-2xl font-bold mb-6">Giỏ hàng của bạn</h1>

                    <table class="w-full border border-gray-300 mt-10">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-3 border">Hình ảnh</th>
                                <th class="p-3 border">Tên</th>
                                <th class="p-3 border">Loại</th>
                                <th class="p-3 border">Giá</th>
                                <th class="p-3 border">Số lượng</th>
                                <th class="p-3 border">Thành tiền</th>
                                <th class="p-3 border">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($validCart as $id => $item):
                                $money = $item['price'] * $item['quantity'];
                                $total += $money;
                                ?>
                                <tr class="text-center" id="row-<?= $id ?>">
                                    <td class="border p-2">
                                        <img src="uploads/image/album/<?= $item['img'] ?>" class="w-20 mx-auto">
                                    </td>

                                    <td class="border"><?= $item['name'] ?></td>

                                    <td class="border">
                                        <?= isset($item['theloai']) ? $item['theloai'] : '' ?>
                                    </td>

                                    <td class="border text-red-500 font-semibold">
                                        <?= number_format($item['price']) ?>đ
                                    </td>

                                    <td class="border">
                                        <button onclick="updateCart(<?= $id ?>, 'minus')" class="px-2 bg-gray-200">-</button>

                                        <span class="mx-2" id="qty-<?= $id ?>">
                                            <?= $item['quantity'] ?>
                                        </span>

                                        <button onclick="updateCart(<?= $id ?>, 'plus')" class="px-2 bg-gray-200">+</button>
                                    </td>

                                    <td class="border text-blue-600 font-semibold" id="money-<?= $id ?>">
                                        <?= number_format($money) ?>đ
                                    </td>

                                    <td class="border">
                                        <button onclick="confirmDelete(<?= $id ?>)" class="text-red-500">
                                            Xóa
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="mt-6 flex justify-end items-center gap-6">
                        <span class="text-xl font-bold" id="total">
                            Tổng: <?= number_format($total) ?>đ
                        </span>

                        <a href="thongtindathang.php" class="bg-green-500 text-white px-6 py-2 rounded">
                            Thanh toán
                        </a>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php $footer_mt = 'mt-24'; ?>
    <?php include "includes/footer.php"; ?>

    <!-- Popup -->
    <div id="popupDelete" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-[9999]">

        <div class="bg-white p-6 rounded-lg shadow-lg text-center">
            <p class="mb-4 text-lg">Bạn có muốn xóa sản phẩm không?</p>

            <div class="flex justify-center gap-4">
                <button onclick="deleteItem()" class="bg-red-500 text-white px-4 py-2 rounded">
                    Xóa
                </button>

                <button onclick="closePopup()" class="bg-gray-300 px-4 py-2 rounded">
                    Hủy
                </button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed top-5 right-[-300px] bg-green-500 text-white px-3 py-2 rounded shadow z-[9999] transition-all duration-300">
    </div>

    <script src="assets/js/them_gio_hang.js"></script>
    <script src="assets/js/toast.js"></script>

</body>

</html>