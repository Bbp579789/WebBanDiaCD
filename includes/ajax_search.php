<?php
require_once "../config/database.php";

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($query !== '') {
    $searchTerm = "%$query%";
    $sql = "SELECT id, ten_san_pham, hinh_anh, gia FROM san_pham 
            WHERE ten_san_pham LIKE ? AND trang_thai = 1 LIMIT 5";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $searchTerm);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo '
            <a href="chitietsanpham.php?id='.$row['id'].'" class="flex items-center gap-3 p-3 hover:bg-gray-100 border-b last:border-0 transition">
                <img src="uploads/image/album/'.$row['hinh_anh'].'" class="w-10 h-10 object-cover rounded">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-800">'.$row['ten_san_pham'].'</span>
                    <span class="text-xs text-green-600 font-bold">'.number_format($row['gia']).'đ</span>
                </div>
            </a>';
        }
        echo '<a href="timkiem.php?search='.urlencode($query).'" class="block p-2 text-center text-xs text-blue-600 bg-gray-50 hover:underline">Xem tất cả kết quả</a>';
    } else {
        echo '<p class="p-3 text-sm text-gray-500 italic">Không tìm thấy sản phẩm nào.</p>';
    }
}