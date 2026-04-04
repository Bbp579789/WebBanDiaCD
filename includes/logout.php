<!-- ?php
session_start();
session_unset();
session_destroy();

header("Location: ../index.php");
exit(); -->

<?php
session_start();

// 🔥 chỉ xóa thông tin user
unset($_SESSION['user_id']);

// ❌ KHÔNG dùng session_destroy()
// ❌ KHÔNG dùng session_unset()

header("Location: ../index.php");
exit();


