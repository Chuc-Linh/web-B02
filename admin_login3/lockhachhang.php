<?php
require_once 'db.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Tìm kiếm theo tên, sdt hoặc username
$sql = "SELECT * FROM khachhang 
        WHERE username LIKE '%$search%' 
        OR hoten LIKE '%$search%' 
        OR SDT LIKE '%$search%'";

$result = mysqli_query($conn, $sql);
$data = [];

while($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);