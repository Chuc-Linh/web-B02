    <?php
require_once 'db.php';


$trangthai = $_GET['trangthai'] ?? '';
$order     = $_GET['order'] ?? '';

 $sql = "SELECT * FROM hoadon join khachhang on hoadon.username = khachhang.username ";
if ($trangthai !== '') {
    $sql .= " AND trangthai = '" . mysqli_real_escape_string($conn, $trangthai) . "'";
}

if ($order === 'asc') {
    $sql .= " ORDER BY phuonghd ASC";
} elseif ($order === 'desc') {
    $sql .= " ORDER BY phuonghd DESC";
} else {
    $sql .= " ORDER BY ngaydat DESC";
}

$rs = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($rs)) {
    $data[] = $row;
}

echo json_encode($data);
