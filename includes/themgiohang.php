<?php
session_start();

if (isset($_GET['id'])) {

    $id = (int) $_GET['id'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $_SESSION['cart'][] = $id;

    echo "success";
}