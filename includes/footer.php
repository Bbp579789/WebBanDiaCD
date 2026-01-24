
<?php
$menu_footer = [
    ['text' => 'Trang chủ', 'link' => 'index.php'],
    ['text' => 'Dịch vụ', 'link' => 'dichvu.php'],
    ['text' => 'Liên hệ', 'link' => 'lienhe.php'],
    ['text' => 'Điều khoản', 'link' => 'dieukhoan.php'],
    ['text' => 'Chính sách bảo mật', 'link' => 'baomat.php']
];

$mang_xa_hoi = [
    ['icon' => 'fb.jpg', 'link' => '#'],
    ['icon' => 'mess.jpg', 'link' => '#'],
    ['icon' => 'zalo.jpg', 'link' => '#'],
    ['icon' => 'mail.jpg', 'link' => '#']
];
?>

<footer class="bg-black text-white p-6 mt-20 rounded-md">

    <!-- Menu -->
    <div class="flex flex-wrap justify-center gap-4 mb-4 text-sm font-medium">
        <?php foreach ($menu_footer as $item): ?>
            <a href="<?= $item['link'] ?>"
               class="px-4 py-2 rounded-md transition hover:bg-green hover:text-green-600">
                <?= $item['text'] ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Mạng xã hội -->
    <div class="flex justify-center gap-4 mb-4">
        <?php foreach ($mang_xa_hoi as $mxh): ?>
            <a href="<?= $mxh['link'] ?>" class="transition transform hover:-translate-y-2">
                <img src="./uploads/image/others/<?= $mxh['icon'] ?>"
                     class="h-8 w-8 rounded-full">
            </a>
        <?php endforeach; ?>
    </div>

    <div class="border-t border-gray-600 my-4"></div>

    <!-- Thông tin -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center text-sm gap-4">

        <div>
            <p>© 2025 CD SHOP. Music is life</p>
        </div>

        <div class="flex flex-col md:flex-row gap-6 md:gap-12">

            <div class="flex items-center gap-2">
                <img src="./uploads/image/others/address.jpg" class="h-5 w-5 rounded-full">
                <p>Địa chỉ: 123 Đường ABC, Quận 1, TP.HCM</p>
            </div>

            <div class="flex items-center gap-2">
                <img src="./uploads/image/others/telephone.jpg" class="h-5 w-5 rounded-full">
                <p>Điện thoại: 0123 456 789</p>
            </div>

            <div class="flex items-center gap-2">
                <img src="./uploads/image/others/mail.jpg" class="h-5 w-5 rounded-full">
                <p>Email: theartstore2005@gmail.com</p>
            </div>

        </div>
    </div>
</footer>

