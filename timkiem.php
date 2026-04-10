<?php
session_start();
include "config/database.php";

// 1. NHẬN CÁC TIÊU CHÍ TÌM KIẾM
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$cat_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 0;

// 2. PHÂN TRANG
$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 3. XÂY DỰNG CÂU LỆNH SQL ĐỘNG
$where_clauses = ["sp.trang_thai = 1"];
$params = [];
$types = "";

if ($search !== '') {
    $where_clauses[] = "(sp.ten_san_pham LIKE ? OR ns.ten_nghe_si LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}
if ($cat_id > 0) {
    $where_clauses[] = "sp.the_loai_id = ?";
    $params[] = $cat_id;
    $types .= "i";
}
if ($min_price > 0) {
    $where_clauses[] = "sp.gia >= ?";
    $params[] = $min_price;
    $types .= "i";
}
if ($max_price > 0) {
    $where_clauses[] = "sp.gia <= ?";
    $params[] = $max_price;
    $types .= "i";
}

$where_sql = implode(" AND ", $where_clauses);

// 4. TRUY VẤN SẢN PHẨM
$sql = "SELECT sp.*, ns.ten_nghe_si 
        FROM san_pham sp 
        JOIN nghe_si ns ON sp.nghe_si_id = ns.id 
        WHERE $where_sql ORDER BY sp.id DESC LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $sql);
$final_params = array_merge($params, [$limit, $offset]);
$final_types = $types . "ii";
mysqli_stmt_bind_param($stmt, $final_types, ...$final_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// 5. ĐẾM TỔNG ĐỂ PHÂN TRANG
$countSql = "SELECT COUNT(*) as total FROM san_pham sp JOIN nghe_si ns ON sp.nghe_si_id = ns.id WHERE $where_sql";
$stmtCount = mysqli_prepare($conn, $countSql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmtCount, $types, ...$params);
}
mysqli_stmt_execute($stmtCount);
$totalRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCount))['total'];
$totalPages = ceil($totalRow / $limit);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Tìm kiếm - Ở đây có muzic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   
</head>

<body class="bg-white">
    <!-- Header -->
    <?php include "includes/header.php"; ?>
    <div class="h-[150px]"></div>

    <!-- CONTAINER TỔNG: MỞ RỘNG RA 1400PX -->
    <div class="max-w-[1400px] mx-auto px-4 mb-10">
        <div class="flex flex-col md:flex-row gap-8 items-start">
            
            <!-- CỘT TRÁI: BỘ LỌC (CHIẾM 20% TRÊN MÀN HÌNH LỚN) -->
            <aside class="w-full md:w-1/4 lg:w-1/5 ">
                <?php include "includes/search_nangcao.php"; ?>
            </aside>

            <!-- CỘT PHẢI: BANNER & KẾT QUẢ (CHIẾM 80%) -->
            <main class="flex-1 w-full">
                <!-- Banner co giãn theo khung phải -->
                <div class="w-full overflow-hidden rounded-2xl">
                    <?php include "includes/banner.php"; ?>
                </div>

                <!-- Khối hiển thị kết quả -->
                <div class="bg-gray-200 py-10 mt-10 rounded-3xl w-full">
                    <h2 class="text-center mb-2 
                               text-2xl md:text-3xl lg:text-[36px]
                               font-thin italic tracking-wide font-serif-custom">
                        Kết quả tìm kiếm
                    </h2>
                    <p class="text-center text-gray-500 mb-8 text-sm italic">Tìm thấy <?= $totalRow ?> sản phẩm khớp với yêu cầu</p>

                    <div class="px-6 md:px-10">
                        <!-- GRID 4 CỘT: justify-items-center để các card luôn thẳng hàng -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 justify-items-center">
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    
                                    <!-- CARD SẢN PHẨM CHUẨN INDEX -->
                                    <div class="bg-white border border-gray-300 rounded-lg p-4 w-full max-w-[280px] text-center shadow-md
                                                transform transition duration-300 hover:-translate-y-2 hover:shadow-xl flex flex-col justify-between">
                                        
                                        <a href="chitietsanpham.php?id=<?= $row['id']; ?>">
                                            <div class="overflow-hidden rounded-md mb-3">
                                                <img src="uploads/image/album/<?= $row['hinh_anh']; ?>"
                                                     class="w-full aspect-square object-cover hover:scale-110 transition duration-500">
                                            </div>
                                            
                                            <h3 class="text-lg font-semibold truncate px-2 text-slate-800"><?= $row['ten_san_pham']; ?></h3>
                                            <p class="text-gray-500 text-sm mb-2"><?= $row['ten_nghe_si']; ?></p>
                                        </a>

                                        <div>
                                            <p class="text-red-600 font-bold mb-4 italic">
                                                <?= number_format($row['gia']); ?> VNĐ
                                            </p>

                                            <div class="flex flex-col xl:flex-row justify-center gap-2">
                                                <?php if (isset($_SESSION['user_id'])): ?>
                                                    <button onclick="themGio(<?= $row['id'] ?>)"
                                                        class="bg-green-600 text-white px-3 py-2 rounded-md hover:bg-green-700 transition text-sm flex-1 font-medium">
                                                        Thêm giỏ
                                                    </button>
                                                    <button onclick="muaNgay(<?= $row['id'] ?>)"
                                                        class="bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-700 transition text-sm flex-1 font-medium">
                                                        Mua ngay
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="openLogin()" class="bg-green-600 text-white px-3 py-2 rounded-md text-sm flex-1 font-medium">
                                                        Thêm giỏ
                                                    </button>
                                                    <button onclick="openLogin()" class="bg-blue-600 text-white px-3 py-2 rounded-md text-sm flex-1 font-medium">
                                                        Mua ngay
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-span-full py-20 text-center">
                                    <img src="assets/img/no-results.png" class="mx-auto w-24 opacity-20 mb-4" alt="">
                                    <p class="text-gray-400 italic font-serif-custom text-xl">Không tìm thấy bản nhạc nào phù hợp với bộ lọc...</p>
                                    <a href="timkiem.php" class="text-green-600 underline mt-4 inline-block">Xóa bộ lọc và thử lại</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- PHÂN TRANG: GIỮ THAM SỐ TÌM KIẾM -->
                        <?php if ($totalPages > 1): ?>
                        <div class="flex justify-center mt-16 gap-2 pb-5">
                            <?php 
                            $query_string = "&search=".urlencode($search)."&category=$cat_id&min_price=$min_price&max_price=$max_price";
                            if ($page > 1): ?>
                                <a href="?page=<?= ($page - 1) . $query_string ?>" class="px-5 py-2 bg-white border border-gray-300 rounded-xl hover:bg-gray-100 transition shadow-sm"> < </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?= $i . $query_string ?>" 
                                   class="px-5 py-2 border rounded-xl transition shadow-sm <?= ($i == $page) ? 'bg-green-600 text-white font-bold border-green-600' : 'bg-white border-gray-300 hover:bg-gray-100 text-gray-600' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= ($page + 1) . $query_string ?>" class="px-5 py-2 bg-white border border-gray-300 rounded-xl hover:bg-gray-100 transition shadow-sm"> > </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- TOAST NOTIFICATION -->
    <div id="toast" class="fixed top-24 right-[-250px] bg-green-500 text-white px-4 py-2 text-sm rounded-md shadow-lg z-[9999] transition-all duration-300"></div>
    
    <script src="assets/js/toast.js"></script>
    <?php include "includes/footer.php"; ?>
</body>

</html>