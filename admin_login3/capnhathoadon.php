<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['index'])) {
    echo json_encode(["success"=>false,"msg"=>"Chưa đăng nhập"]);
    exit;
}

if (isset($_POST['mahd'], $_POST['trangthai'])) {

    $mahd = mysqli_real_escape_string($conn, $_POST['mahd']);
    $newStatus = mysqli_real_escape_string($conn, $_POST['trangthai']);

    // Lấy trạng thái hiện tại
    $sql = "SELECT trangthai FROM hoadon WHERE mahd='$mahd'";
    $rs = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($rs);

    if (!$row) {
        echo json_encode(["success"=>false,"msg"=>"Không tìm thấy đơn"]);
        exit;
    }

    $current = $row['trangthai'];

    // Danh sách chuyển trạng thái hợp lệ
    $allowed = [
        "Cho xu ly" => ["Dang xu ly","Dang giao","Da giao","Da huy"],
        "Dang xu ly" => ["Dang giao","Da giao","Da huy"],
        "Dang giao" => ["Da giao","Da huy"]
    ];

    // Nếu đã hoàn tất thì chặn
    if ($current == "Da giao" || $current == "Da huy") {
        echo json_encode(["success"=>false,"msg"=>"Đơn đã hoàn tất"]);
        exit;
    }

    // Kiểm tra hợp lệ
    if (!isset($allowed[$current]) || !in_array($newStatus, $allowed[$current])) {
        echo json_encode(["success"=>false,"msg"=>"Chuyển trạng thái không hợp lệ"]);
        exit;
    }

    // Update
    $update = "UPDATE hoadon SET trangthai='$newStatus' WHERE mahd='$mahd'";
    mysqli_query($conn, $update);

    echo json_encode(["success"=>true,"msg"=>"Cập nhật thành công"]);
}
?>