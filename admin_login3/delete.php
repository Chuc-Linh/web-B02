<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: admin.php?page=product");
    exit;
}

/* 1. Kiểm tra sản phẩm đã từng nhập hàng chưa */
$check = mysqli_query(
    $conn,
    "SELECT 1 
     FROM chitietphieunhap 
     WHERE masp = $id 
     LIMIT 1"
);

if (mysqli_num_rows($check) == 0) {
    //  CHƯA nhập hàng → xoá hẳn khỏi CSDL
    mysqli_query($conn, "DELETE FROM sanpham WHERE masp = $id");
} else {
    //  ĐÃ nhập hàng → chỉ ẩn sản phẩm
    mysqli_query(
        $conn,
        "UPDATE sanpham 
         SET hientrang = 'Ẩn' 
         WHERE masp = $id"
    );
}

header("Location: admin.php?page=product");
exit;


