<?php
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

$username = trim($_POST["username"] ?? "");
$password = trim($_POST["password"] ?? "");

/* ===== VALIDATE ===== */
if ($username === "" || $password === "") {
    echo json_encode([
        "status" => "error",
        "type" => "empty"
    ]);
    exit;
}

/* ===== CHECK USER ===== */
$sql = "SELECT * FROM nguoi_dung WHERE ten_dang_nhap = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "type" => "username"
    ]);
    exit;
}

$user = $result->fetch_assoc();

/* ===== CHECK PASSWORD ===== */
if ($password !== $user["mat_khau"]) {
    echo json_encode([
        "status" => "error",
        "type" => "password"
    ]);
    exit;
}

/* ===== CHECK STATUS ===== */
if ($user["trang_thai"] != 1) {
    echo json_encode([
        "status" => "error",
        "type" => "blocked"
    ]);
    exit;
}

/* ===== LOGIN OK ===== */
$_SESSION["user_id"] = $user["id"];
$_SESSION["username"] = $user["ten_dang_nhap"];
$_SESSION["name"] = $user["ho_ten"];
$_SESSION["role"] = $user["vai_tro"];
$_SESSION["email"] = $user["email"];
$_SESSION["phone"] = $user["so_dien_thoai"];
$_SESSION["address"] = $user["dia_chi"];

echo json_encode([
    "status" => "success"
]);
exit;
