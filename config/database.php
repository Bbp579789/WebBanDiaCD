<?php
$conn = mysqli_connect("localhost", "root", "", "webbandiacd");

if (!$conn) {
    die("Lỗi kết nối database");
}
