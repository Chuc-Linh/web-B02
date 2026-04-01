<?php

require_once 'db.php';
// Lấy đúng tên biến từ $_GET
$tu = $_GET['tu_ngay'] ?? '';
$den = $_GET['den_ngay'] ?? '';

 $sql = "SELECT * FROM hoadon join khachhang on hoadon.username = khachhang.username ";

if (!empty($tu)) {
    // Chuyển / thành - để SQL hiểu được (nếu người dùng nhập YYYY/MM/DD)
    $tu_db = str_replace('/', '-', $tu);
    $sql .= " AND ngaydat >= '$tu_db 00:00:00'";
}
if (!empty($den)) {
    $den_db = str_replace('/', '-', $den);
    $sql .= " AND ngaydat <= '$den_db 23:59:59'";
}

$sql .= " ORDER BY ngaydat ASC";
$rs = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($rs)) {
    $row['ngaydat_format'] = date('d/m/Y H:i', strtotime($row['ngaydat']));
    $data[] = $row;
}

echo json_encode($data);
?>