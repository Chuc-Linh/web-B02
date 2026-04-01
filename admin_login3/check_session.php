<?php
session_start();
header('Content-Type: application/json');

// nếu chưa có session thì kiểm tra cookie
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {

    if (isset($_COOKIE['admin_login']) && !empty($_COOKIE['admin_login'])) {
        $_SESSION['admin'] = $_COOKIE['admin_login'];
    }
}

echo json_encode([
    'logged_in' => isset($_SESSION['admin']) && !empty($_SESSION['admin'])
]);

exit;