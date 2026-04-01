<?php
// chitiet_donhang.php

require_once 'db.php';

session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$mahd = isset($_GET['mahd']) ? trim($_GET['mahd']) : '';
if (!$mahd) {
    die("Thiếu mã đơn hàng");
}

$stmt = $conn->prepare("
    SELECT h.*, kh.hoten, kh.SDT, kh.diachinha 
    FROM hoadon h 
    LEFT JOIN khachhang kh ON h.username = kh.username 
    WHERE h.mahd = ?
");
$stmt->bind_param("s", $mahd);
$stmt->execute();
$hd = $stmt->get_result()->fetch_assoc();

if (!$hd) {
    die("Không tìm thấy đơn hàng");
}

$stmt_ct = $conn->prepare("
    SELECT ct.*, sp.tensp 
    FROM chitiethd ct 
    JOIN sanpham sp ON ct.masp = sp.masp 
    WHERE ct.mahd = ?
");
$stmt_ct->bind_param("s", $mahd);
$stmt_ct->execute();
$chitiet = $stmt_ct->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?= htmlspecialchars($mahd) ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2, h3 {
            color: #333;
        }
        .info-box {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
        }
        .info-box p {
            margin: 8px 0;
        }
        .info-box strong {
            display: inline-block;
            width: 180px;
            color: #444;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        th {
            background: #e9ecef;
            text-align: left;
        }
        .total-row {
            font-weight: bold;
            background: #e9f7ff;
        }
        .btn-back {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 18px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .btn-back:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>

<div class="container">

    <h2>Chi tiết đơn hàng #<?= htmlspecialchars($mahd) ?></h2>

    <div class="info-box">
        <p><strong>Khách hàng:</strong> <?= htmlspecialchars($hd['hoten'] ?: $hd['username']) ?></p>
        <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($hd['SDT'] ?: '—') ?></p>
        <p><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($hd['ngaydat'])) ?></p>
        <p><strong>Phương thức thanh toán:</strong> <?= htmlspecialchars($hd['phuongthucthanhtoan']) ?></p>
        <p><strong>Trạng thái:</strong> <span style="font-weight:bold;"><?= htmlspecialchars($hd['trangthai']) ?></span></p>
        <p><strong>Địa chỉ giao hàng:</strong> <?= htmlspecialchars($hd['diachihd']) ?>, <?= htmlspecialchars($hd['phuonghd']) ?>, <?= htmlspecialchars($hd['thanhphohd']) ?></p>
    </div>

    <h3>Sản phẩm trong đơn hàng</h3>

    <table>
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Giá bán</th>
                <th>Số lượng</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $tongtien = 0;
        while ($row = $chitiet->fetch_assoc()): 
            $thanhtien = $row['giaban'] * $row['soluongmua'];
            $tongtien += $thanhtien;
        ?>
            <tr>
                <td><?= htmlspecialchars($row['tensp']) ?></td>
                <td><?= number_format($row['giaban']) ?> ₫</td>
                <td><?= $row['soluongmua'] ?></td>
                <td><?= number_format($thanhtien) ?> ₫</td>
            </tr>
        <?php endwhile; ?>
            <tr class="total-row">
                <td colspan="3" style="text-align:right;">Tổng tiền đơn hàng:</td>
                <td><?= number_format($tongtien) ?> ₫</td>
            </tr>
        </tbody>
    </table>

    <a href="admin.php?page=donhang" class="btn-back">← Quay lại danh sách đơn hàng</a>

</div>

</body>
</html>

<?php
$stmt->close();
$stmt_ct->close();
$conn->close();
?>