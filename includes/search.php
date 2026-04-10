<?php

require_once __DIR__ . '/config/database.php';
$q = '%'.($conn->real_escape_string($_GET['q'] ?? '')).'%';
$sql = "SELECT sp.*, ns.ten_nghe_si FROM san_pham sp
        LEFT JOIN nghe_si ns ON sp.nghe_si_id = ns.id
        WHERE sp.trang_thai = 1 AND sp.ten_san_pham LIKE ?
        ORDER BY sp.id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $q);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
// ...
?>