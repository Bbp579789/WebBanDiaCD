<?php

session_start();
require_once __DIR__ . '/../config/database.php'; // $conn

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(401); echo 'Unauthorized'; exit;
}

$id = intval($_POST['id'] ?? 0);
$new = intval($_POST['status'] ?? 0); // 0 hoặc 1
if ($id <= 0) { http_response_code(400); echo 'Invalid id'; exit; }

$stmt = mysqli_prepare($conn, "UPDATE san_pham SET trang_thai = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $new, $id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo $ok ? 'success' : 'error';
?>      