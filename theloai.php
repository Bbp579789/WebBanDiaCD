<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    include "config/database.php";

    /* ===== LẤY ID THỂ LOẠI ===== */
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    /* ===== LẤY TÊN THỂ LOẠI ===== */
    $tlQuery = mysqli_query($conn, "SELECT ten_the_loai FROM the_loai WHERE id = $id");
    $theloai = mysqli_fetch_assoc($tlQuery);
    $title = $theloai['ten_the_loai'] ?? 'Sản phẩm';
    ?>
    <title><?php echo $title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-white flex flex-col min-h-screen">

    <!-- Header -->
    <?php include "includes/header.php"; ?>
    <div class="h-[150px]"></div>

    <!-- Banner -->
    <?php include "includes/banner.php"; ?>

    <main class="flex-grow bg-gray-200 py-10 mt-6 rounded-3xl">

        <h2 class="text-center mb-10
            text-2xl md:text-3xl lg:text-[36px]
            font-thin italic tracking-wide font-serif">
            <?php echo $theloai['ten_the_loai'] ?? 'Không xác định'; ?>
        </h2>

        <div class="max-w-7xl mx-auto px-4">

            <?php
            /* ===== PHÂN TRANG ===== */
            $limit = 8;
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $page = max($page, 1);
            $offset = ($page - 1) * $limit;


            /* ===== LẤY SẢN PHẨM THEO THỂ LOẠI (THÊM TRẠNG THÁI = 1) ===== */
            $sql = "
                SELECT sp.*, ns.ten_nghe_si
                FROM san_pham sp
                JOIN nghe_si ns ON sp.nghe_si_id = ns.id
                WHERE sp.the_loai_id = $id AND sp.trang_thai = 1
                LIMIT $limit OFFSET $offset
            ";
            $result = mysqli_query($conn, $sql);


            /* ===== ĐẾM TỔNG SẢN PHẨM (THÊM TRẠNG THÁI = 1) ===== */
            $countSql = "
                SELECT COUNT(*) AS total
                FROM san_pham
                WHERE the_loai_id = $id AND trang_thai = 1
            ";
            $countResult = mysqli_query($conn, $countSql);
            $totalRow = mysqli_fetch_assoc($countResult)['total'];
            $totalPages = ceil($totalRow / $limit);

            $count = 0;

            /* ===== HIỂN THỊ SẢN PHẨM ===== */
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    if ($count % 4 == 0) {
                        echo '<div class="flex justify-center gap-6 mb-8 flex-wrap">';
                    }
            ?>
                    <!-- CARD -->
                    <div class="bg-white border border-gray-300 rounded-lg p-4 w-64 text-center shadow-md
                        transform transition duration-300 hover:-translate-y-2 hover:shadow-xl">

                        <a href="chitietsanpham.php?id=<?php echo $row['id']; ?>">
                            <img src="uploads/image/album/<?php echo $row['hinh_anh']; ?>"
                                class="mx-auto mb-3 w-48 h-48 object-cover rounded-md"
                                onerror="this.src='assets/img/default.png'">

                            <h3 class="text-lg font-semibold h-14 overflow-hidden">
                                <?php echo $row['ten_san_pham']; ?>
                            </h3>

                            <p class="text-gray-600 mb-2">
                                <?php echo $row['ten_nghe_si']; ?>
                            </p>
                        </a>

                        <p class="text-red-600 font-bold mb-4">
                            <?php echo number_format($row['gia'], 0, ',', '.'); ?> VNĐ
                        </p>

                        <div class="flex justify-center gap-3">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button onclick="themGio(<?= $row['id'] ?>)"
                                    class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
                                    Thêm giỏ
                                </button>
                                <button onclick="muaNgay(<?= $row['id'] ?>)" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                                    Mua ngay
                                </button>
                            <?php else: ?>
                                <button onclick="openLogin()" class="bg-green-600 text-white px-4 py-2 rounded-md">
                                    Thêm giỏ
                                </button>
                                <button onclick="openLogin()" class="bg-blue-600 text-white px-4 py-2 rounded-md">
                                    Mua ngay
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

            <?php
                    $count++;
                    if ($count % 4 == 0) {
                        echo '</div>';
                    }
                }
                if ($count % 4 != 0) {
                    echo '</div>';
                }
            } else {
                echo '<p class="text-center text-gray-500 py-10 italic">Hiện không có sản phẩm nào trong thể loại này.</p>';
            }
            ?>

            <!-- PHÂN TRANG -->
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-center mt-10 gap-2 pb-10">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?php echo $id; ?>&page=<?php echo $page - 1; ?>"
                            class="px-4 py-2 bg-white border rounded hover:bg-gray-300"> « </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?id=<?php echo $id; ?>&page=<?php echo $i; ?>" class="px-4 py-2 border rounded
                            <?php echo ($i == $page) ? 'bg-green-700 text-white' : 'bg-white hover:bg-gray-300'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?id=<?php echo $id; ?>&page=<?php echo $page + 1; ?>"
                            class="px-4 py-2 bg-white border rounded hover:bg-gray-300"> » </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <div id="toast" class="fixed top-24 right-[-250px] 
      bg-green-500 text-white 
      px-3 py-2 text-sm 
      rounded-md shadow 
      z-[9999] transition-all duration-300">
    </div>

    <!-- Footer -->
    <?php include "includes/footer.php"; ?>

    <script src="assets/js/toast.js"></script>
</body>

</html>