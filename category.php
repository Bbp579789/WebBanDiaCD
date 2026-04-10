<?php
$catId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($catId <= 0) {
    header('Location: index.php');
    exit;
}
header('Location: theloai.php?id=' . $catId);
exit;
?>