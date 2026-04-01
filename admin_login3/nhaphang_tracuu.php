<?php
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require_once 'db.php'; // File mysqli của bạn

// Hàm cập nhật tổng tiền phiếu
function capnhattongtien($conn, $maphieunhap) {
    $maphieunhap = mysqli_real_escape_string($conn, $maphieunhap);
    $result = mysqli_query($conn, "
        SELECT COALESCE(SUM(soluongnhap * gianhap), 0) AS tong 
        FROM chitietphieunhap 
        WHERE maphieunhap = '$maphieunhap'
    ");
    $row = mysqli_fetch_assoc($result);
    $tong = $row['tong'] ?? 0;

    mysqli_query($conn, "
        UPDATE phieunhap 
        SET tongtienpn = $tong 
        WHERE maphieunhap = '$maphieunhap'
    ");
}

// Tìm kiếm phiếu
$timphieu = isset($_GET['timphieu']) ? mysqli_real_escape_string($conn, $_GET['timphieu']) : '';
$where = $timphieu ? "WHERE maphieunhap LIKE '%$timphieu%'" : "";
$result = mysqli_query($conn, "SELECT * FROM phieunhap $where ORDER BY ngaynhap DESC, maphieunhap DESC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Nhập Hàng</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            min-height: 100vh;
        }
        .alert {
            padding: 12px 18px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-danger  { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden;
        }
        .card-header {
            background: #34495e;
            color: white;
            padding: 14px 20px;
            font-size: 1.1em;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .status-done   { color: #27ae60; font-weight: bold; }
        .status-pending{ color: #e74c3c; font-weight: bold; }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary   { background: #3498db; color: white; }
        .btn-primary:hover   { background: #2980b9; }
        .btn-success   { background: #27ae60; color: white; }
        .btn-success:hover   { background: #219653; }
        .btn-danger    { background: #e74c3c; color: white; }
        .btn-danger:hover    { background: #c0392b; }

        form { margin: 15px 0; }
        input, select {
            padding: 9px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        input[type="number"] { width: 110px; }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #2c3e50;
            color: white;
            font-weight: 500;
        }
        td.text-left { text-align: left; }
        tr:hover { background: #f8f9fa; }

        .actions { white-space: nowrap; }
        .total { font-weight: bold; color: #e67e22; }

        .search-form { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            flex-wrap: wrap;
            margin-bottom: 25px;
        }
        h1 { 
            color: #2c3e50; 
            margin-bottom: 20px;  
            font-weight: bold; 
            font-size: 43px;
        }
    </style>
</head>
<body>
    <h1>Tra cứu phiếu nhập hàng</h1>

    <?php if (isset($message)): ?>
    <div class="alert <?= strpos($message, 'thành công') !== false ? 'alert-success' : 'alert-danger' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- Phần tìm kiếm + Nút xóa bộ lọc -->
    <div class="search-form">
        <form method="get" action="admin.php" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <input type="hidden" name="page" value="nhaphang_tracuu">
            <input type="text" name="timphieu"
                   placeholder="Tìm theo mã phiếu nhập..."
                   value="<?= htmlspecialchars($timphieu ?? '') ?>"
                   style="min-width: 280px;">
            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
            
            <?php if (!empty($timphieu)): ?>
                <a href="admin.php?page=nhaphang_tracuu" 
                   class="btn btn-primary">
                     Xóa bộ lọc
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php while ($pn = mysqli_fetch_assoc($result)): 
        $is_editable = ($pn['trangthaipn'] === 'Chưa hoàn thành');
    ?>

    <div class="card">
        <div class="card-header">
            <div>
                <strong>Phiếu:</strong> <?= htmlspecialchars($pn['maphieunhap']) ?> 
                  |  <strong>Ngày:</strong> <?= $pn['ngaynhap'] ?? '—' ?> 
                  |  <strong>Tổng tiền:</strong> <span class="total"><?= number_format($pn['tongtienpn']) ?> đ</span>
                  |  Trạng thái: 
                <span class="<?= $is_editable ? 'status-pending' : 'status-done' ?>">
                    <?= htmlspecialchars($pn['trangthaipn']) ?>
                </span>
            </div>
        </div>

        <div style="padding: 20px;">
            <table>
                <thead>
                <tr>
                    <th style="width:40%">Sản phẩm</th>
                    <th style="width:15%">Số lượng</th>
                    <th style="width:20%">Giá nhập</th>
                    <th style="width:20%">Thành tiền</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $ct_sql = "SELECT c.*, s.tensp 
                           FROM chitietphieunhap c
                           JOIN sanpham s ON c.masp = s.masp
                           WHERE c.maphieunhap = '{$pn['maphieunhap']}'";
                $ct_result = mysqli_query($conn, $ct_sql);

                if (mysqli_num_rows($ct_result) == 0) {
                    echo '<tr><td colspan="4" style="padding:30px; color:#777; text-align:center;">Chưa có sản phẩm nào trong phiếu này.</td></tr>';
                }

                while ($ct = mysqli_fetch_assoc($ct_result)):
                    $thanhtien = $ct['soluongnhap'] * $ct['gianhap'];
                ?>
                <tr>
                    <td class="text-left"><?= htmlspecialchars($ct['tensp']) ?></td>
                    <td><?= number_format($ct['soluongnhap']) ?></td>
                    <td><?= number_format($ct['gianhap']) ?> đ</td>
                    <td><?= number_format($thanhtien) ?> đ</td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endwhile; ?>

    <?php if (mysqli_num_rows($result) == 0): ?>
    <div style="text-align:center; padding:40px; color:#777;">
        Không tìm thấy phiếu nhập hàng nào.
    </div>
    <?php endif; ?>

<?php include 'footer.php'; ?>

</body>
</html>