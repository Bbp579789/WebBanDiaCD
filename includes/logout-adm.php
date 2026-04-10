<?php
session_start();

// 🔥 chỉ xóa thông tin user


// ❌ KHÔNG dùng session_destroy()
// ❌ KHÔNG dùng session_unset()

header("Location: ../admin/login_adm.php");
exit();