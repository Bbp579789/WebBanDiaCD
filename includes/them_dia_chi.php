<?php
session_start();
include __DIR__ . "/config/database.php";

if (!isset($_SESSION['user_id'])) {
    echo "not_login";
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$dia_chi = trim($_POST['dia_chi'] ?? '');

if ($dia_chi === '') {
    echo "empty";
    exit;
}

$stmt = $conn->prepare("INSERT INTO dia_chi (nguoi_dung_id, dia_chi) VALUES (?, ?)");

if (!$stmt) {
    echo "prepare_error: " . $conn->error;
    exit;
}

$stmt->bind_param("is", $user_id, $dia_chi);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "execute_error: " . $stmt->error;
}