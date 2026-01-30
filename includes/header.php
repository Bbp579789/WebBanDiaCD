<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="text-black pt-3 pb-3 rounded-md">

   <div class="flex items-center justify-between mb-3 px-4">

        <!-- LOGO -->
        <a href="index.php" class="flex items-center">
            <img src="uploads/image/others/music.jpg"
                 alt="Logo"
                 class="h-12 rounded-md">
        </a>

        <!-- SEARCH -->
        <div class="flex-1 flex justify-center">
            <input type="search"
                   placeholder="Tìm kiếm sản phẩm..."
                   class="border-2 border-green-900 p-2 rounded w-1/2">
        </div>

        <!-- RIGHT ACTION -->
        <div class="flex gap-3 relative">

            <!-- GIỎ HÀNG -->
            <a href="giohang.php"
               class="border border-green-600 px-4 py-2 rounded-md
                      hover:bg-green-600 hover:text-white transition">
              🛒 Giỏ hàng
            </a>

            <!-- LOGIN / USER -->
            <?php if (isset($_SESSION["user_id"])): ?>

                <!-- NÚT TÊN USER -->
                <button id="userBtn"
                        class="border border-green-600 px-4 py-2 rounded-md
                               hover:bg-green-600 hover:text-white transition">
                    👤 <?= htmlspecialchars($_SESSION["name"]) ?>
                </button>

                <!-- DROPDOWN -->
                <div id="userMenu"
                     class="hidden absolute right-0 top-12 bg-white
                            border rounded-md shadow-md w-40 z-50">
                    <a href="hoso.php"
                       class="block px-4 py-2 hover:bg-gray-100">
                        Hồ sơ
                    </a>
                    <a href="includes/logout.php"
                       class="block px-4 py-2 text-red-600 hover:bg-gray-100">
                        Đăng xuất
                    </a>
                </div>

            <?php else: ?>

                <!-- CHƯA LOGIN -->
                <a href="dangnhap.php"
                   class="border border-green-600 px-4 py-2 rounded-md
                          hover:bg-green-600 hover:text-white transition">
                    👤 Đăng nhập
                </a>

            <?php endif; ?>

        </div>

    </div>

    <!-- NAV THỂ LOẠI -->
    <?php
    include "config/database.php";
    $sql = "SELECT * FROM the_loai";
    $result = mysqli_query($conn, $sql);
    ?>

    <nav class="bg-black h-20 rounded-md flex items-center justify-center gap-6 text-white font-medium">

        <a href="danhsachsanpham.php"
           class="px-4 py-2 rounded-md transition hover:bg-green-600">
            Tất cả sản phẩm
        </a>

        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <a href="theloai.php?id=<?= $row['id']; ?>"
               class="px-4 py-2 rounded-md transition hover:bg-green-600">
                <?= htmlspecialchars($row['ten_the_loai']); ?>
            </a>
        <?php } ?>

    </nav>

</header>

<!-- SCRIPT DROPDOWN -->
<script>
document.getElementById("userBtn")?.addEventListener("click", () => {
    document.getElementById("userMenu").classList.toggle("hidden");
});

// click ra ngoài thì đóng
document.addEventListener("click", (e) => {
    if (!e.target.closest("#userBtn")) {
        document.getElementById("userMenu")?.classList.add("hidden");
    }
});
</script>
