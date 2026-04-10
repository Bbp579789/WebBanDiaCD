<?php
// 1. Kết nối Database trực tiếp để tránh lỗi "Undefined variable $pdo"
$host = 'localhost';
$db   = 'webbandiacd';
$user = 'root'; 
$pass = ''; 
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

// 2. Kiểm tra dữ liệu gửi từ Form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sử dụng toán tử ?? để tránh lỗi "Undefined array key"
    $ncc_id = $_POST['ncc_id'] ?? null;
    $ngay_nhap = $_POST['ngay_nhap'] ?? date('Y-m-d H:i:s');
    $total_amount = $_POST['total_amount'] ?? 0;
    $profit_rate = (float)($_POST['profit_rate'] ?? 20);
    $items = $_POST['items'] ?? [];
    $admin_id = 1; // Giả sử ID admin là 1

    if (!$ncc_id || empty($items)) {
        die("Dữ liệu không hợp lệ. Vui lòng kiểm tra lại nhà cung cấp hoặc danh sách sản phẩm.");
    }

    try {
        $pdo->beginTransaction();

        // 3. Tạo Phiếu Nhập
        $sqlPN = "INSERT INTO phieu_nhap (nha_cung_cap_id, nguoi_dung_id, ngay_nhap, tong_tien) VALUES (?, ?, ?, ?)";
        $stmtPN = $pdo->prepare($sqlPN);
        $stmtPN->execute([$ncc_id, $admin_id, $ngay_nhap, $total_amount]);
        $phieu_nhap_id = $pdo->lastInsertId();

        // 4. Duyệt qua từng sản phẩm trong phiếu nhập
        foreach ($items as $item) {
            $sp_id = $item['product_id'];
            $sl_nhap = (int)$item['qty'];
            $gia_nhap_moi = (float)$item['price'];

            if (empty($sp_id) || $sl_nhap <= 0) continue;

            // --- BẮT ĐẦU CÔNG THỨC BÌNH QUÂN GIA QUYỀN ---
            
            // Lấy số lượng tồn hiện tại và giá nhập của đợt gần nhất (để làm giá nhập hiện tại)
            $stmtOld = $pdo->prepare("
                SELECT so_luong, 
                (SELECT don_gia FROM chi_tiet_phieu_nhap WHERE san_pham_id = san_pham.id ORDER BY id DESC LIMIT 1) as gia_nhap_cu
                FROM san_pham WHERE id = ?
            ");
            $stmtOld->execute([$sp_id]);
            $old = $stmtOld->fetch();

            $sl_ton = (int)($old['so_luong'] ?? 0);
            $gia_nhap_cu = (float)($old['gia_nhap_cu'] ?? 0);

            // Công thức: (Tồn * Giá cũ + Nhập mới * Giá mới) / (Tồn + Nhập mới)
            $tong_sl_moi = $sl_ton + $sl_nhap;
            $gia_nhap_bq = (($sl_ton * $gia_nhap_cu) + ($sl_nhap * $gia_nhap_moi)) / $tong_sl_moi;
            
            // Tính giá bán: Giá nhập BQ * (1 + % lợi nhuận)
            $gia_ban_moi = $gia_nhap_bq * (1 + ($profit_rate / 100));

            // 5. Cập nhật bảng san_pham
            $updateSP = $pdo->prepare("UPDATE san_pham SET gia = ?, so_luong = ? WHERE id = ?");
            $updateSP->execute([$gia_ban_moi, $tong_sl_moi, $sp_id]);

            // 6. Lưu vào bảng chi_tiet_phieu_nhap
            $sqlCT = "INSERT INTO chi_tiet_phieu_nhap (phieu_nhap_id, san_pham_id, so_luong, don_gia) VALUES (?, ?, ?, ?)";
            $pdo->prepare($sqlCT)->execute([$phieu_nhap_id, $sp_id, $sl_nhap, $gia_nhap_moi]);
        }

        $pdo->commit();
        // Chuyển hướng về trang danh sách với thông báo thành công
        header("Location: manager-import.php?msg=success");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Lỗi xử lý nghiệp vụ: " . $e->getMessage());
    }
}
?>