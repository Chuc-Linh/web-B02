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

// Xử lý tạo phiếu
if (isset($_POST['taophieu'])) {
    $maphieunhap = trim($_POST['maphieunhap']);
    if (empty($maphieunhap)) {
        $message = "Mã phiếu không được để trống!";
    } else {
        $check = mysqli_query($conn, "SELECT maphieunhap FROM phieunhap WHERE maphieunhap = '$maphieunhap'");
        if (mysqli_num_rows($check) > 0) {
            $message = "Mã phiếu đã tồn tại!";
        } else {
            $sql = "INSERT INTO phieunhap (maphieunhap, trangthaipn, tongtienpn) 
                    VALUES ('$maphieunhap', 'Chưa hoàn thành', 0)";
            if (mysqli_query($conn, $sql)) {
                $message = "Tạo phiếu $maphieunhap thành công!";
            } else {
                $message = "Lỗi: " . mysqli_error($conn);
            }
        }
    }
}

// Xử lý thêm sản phẩm
if (isset($_POST['themsp'])) {
    $maphieunhap  = mysqli_real_escape_string($conn, $_POST['maphieunhap']);
    $masp         = (int)$_POST['masp'];
    $soluongnhap  = (int)$_POST['soluongnhap'];
    $gianhap      = (float)$_POST['gianhap'];

    $check_trangthai = mysqli_query($conn, "SELECT trangthaipn FROM phieunhap WHERE maphieunhap = '$maphieunhap'");
    $row = mysqli_fetch_assoc($check_trangthai);
    $trangthai = $row['trangthaipn'] ?? '';

    if ($trangthai === 'Đã hoàn thành') {
        $message = "Phiếu đã hoàn thành, không thể thêm sản phẩm!";
    } elseif ($soluongnhap <= 0 || $gianhap <= 0) {
        $message = "Số lượng và giá nhập phải lớn hơn 0!";
    } else {
        $sql = "INSERT INTO chitietphieunhap (maphieunhap, masp, soluongnhap, gianhap) 
                VALUES ('$maphieunhap', $masp, $soluongnhap, $gianhap)";
        if (mysqli_query($conn, $sql)) {
            capnhattongtien($conn, $maphieunhap);
            $message = "Thêm sản phẩm thành công!";
        } else {
            $message = "Lỗi: " . mysqli_error($conn);
        }
    }
}

// Xử lý hoàn thành phiếu
if (isset($_GET['hoanthanh'])) {
    $maphieunhap = mysqli_real_escape_string($conn, $_GET['hoanthanh']);
    $sql = "UPDATE phieunhap SET trangthaipn = 'Đã hoàn thành' 
            WHERE maphieunhap = '$maphieunhap' AND trangthaipn = 'Chưa hoàn thành'";
    if (mysqli_query($conn, $sql) && mysqli_affected_rows($conn) > 0) {
        $message = "Phiếu $maphieunhap đã hoàn thành! Tồn kho được cập nhật. ";
    } else {
        $message = "Không thể hoàn thành (phiếu không tồn tại hoặc đã hoàn thành rồi)!";
    }
}

// Xử lý xóa chi tiết
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['xoa']) && isset($_GET['masp'])) {
    $maphieunhap = mysqli_real_escape_string($conn, $_GET['xoa']);
    $masp = (int)$_GET['masp'];

    $check = mysqli_query($conn, "SELECT trangthaipn FROM phieunhap WHERE maphieunhap = '$maphieunhap'");
    $row = mysqli_fetch_assoc($check);
    $trangthai = $row['trangthaipn'] ?? '';

    if ($trangthai === 'Đã hoàn thành') {
        $message = "Không thể xóa chi tiết phiếu đã hoàn thành!";
    } else {
        $sql = "DELETE FROM chitietphieunhap WHERE maphieunhap = '$maphieunhap' AND masp = $masp";
        if (mysqli_query($conn, $sql)) {
            capnhattongtien($conn, $maphieunhap);
            $message = "Xóa sản phẩm thành công!";
        } else {
            $message = "Lỗi: " . mysqli_error($conn);
        }
    }
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
    <title>Tạo phiếu nhập hàng</title>
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

        .search-area {
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
    <h1>Tạo phiếu nhập hàng</h1>

    <?php if (isset($message)): ?>
    <div class="alert <?= strpos($message, 'thành công') !== false ? 'alert-success' : 'alert-danger' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- Tạo phiếu mới + Tìm kiếm + Xóa bộ lọc -->
    <div class="search-area">
        <!-- Form tạo phiếu mới -->
        <form method="post" style="margin-right: 20px;">
            <input type="text" name="maphieunhap" placeholder="Nhập mã phiếu mới (VD: PN202503)" required>
            <button type="submit" name="taophieu" class="btn btn-primary">Tạo phiếu</button>
        </form>

        <!-- Form tìm kiếm -->
        <form method="get" action="admin.php" style="display: flex; align-items: center; gap: 10px;">
            <input type="hidden" name="page" value="nhaphang_tao">
            <input type="text" name="timphieu"
                   placeholder="Tìm mã phiếu..."
                   value="<?= htmlspecialchars($timphieu ?? '') ?>"
                   style="min-width: 260px;">
            <button type="submit" class="btn btn-primary">Tìm</button>
            
            <?php if (!empty($timphieu)): ?>
                <a href="admin.php?page=nhaphang_tao" 
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

            <?php if ($is_editable): ?>
            <a href="admin.php?page=nhaphang_tao&hoanthanh=<?= urlencode($pn['maphieunhap']) ?>"
               class="btn btn-success"
               onclick="return confirm('Xác nhận hoàn thành phiếu này? Tồn kho sẽ được cập nhật.')">
               Hoàn thành phiếu
            </a>
            <?php endif; ?>
        </div>

        <div style="padding: 20px;">

            <?php if ($is_editable): ?>
            <form method="post" style="margin-bottom: 25px; display: flex; gap: 12px; flex-wrap: wrap;">
                <input type="hidden" name="maphieunhap" value="<?= htmlspecialchars($pn['maphieunhap']) ?>">

                <select name="masp" required style="min-width: 220px;">
                    <option value="">Chọn sản phẩm...</option>
                    <?php
                    $sp_result = mysqli_query($conn, "SELECT masp, tensp FROM sanpham ORDER BY tensp");
                    while ($sp = mysqli_fetch_assoc($sp_result)) {
                        echo "<option value='{$sp['masp']}'>" . htmlspecialchars($sp['tensp']) . "</option>";
                    }
                    mysqli_data_seek($sp_result, 0);
                    ?>
                </select>

                <input type="number" name="soluongnhap" min="1" placeholder="Số lượng" required style="width: 110px;">
                <input type="text" name="gianhap" step="100" min="1" placeholder="Giá nhập (đ)" required style="width: 140px;">

                <button type="submit" name="themsp" class="btn btn-success">Thêm sản phẩm</button>
            </form>
            <?php endif; ?>

            <table>
                <thead>
                <tr>
                    <th style="width:40%">Sản phẩm</th>
                    <th style="width:15%">Số lượng</th>
                    <th style="width:20%">Giá nhập</th>
                    <th style="width:20%">Thành tiền</th>
                    <?php if ($is_editable): ?>
                    <th style="width:5%">Hành động</th>
                    <?php endif; ?>
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
                    echo '<tr><td colspan="' . ($is_editable ? 5 : 4) . '" style="padding:30px; color:#777; text-align:center;">Chưa có sản phẩm nào trong phiếu này.</td></tr>';
                }

                while ($ct = mysqli_fetch_assoc($ct_result)):
                    $thanhtien = $ct['soluongnhap'] * $ct['gianhap'];
                ?>
                <tr>
                    <td class="text-left"><?= htmlspecialchars($ct['tensp']) ?></td>
                    <td><?= number_format($ct['soluongnhap']) ?></td>
                    <td><?= number_format($ct['gianhap']) ?> đ</td>
                    <td><?= number_format($thanhtien) ?> đ</td>
                    <?php if ($is_editable): ?>
                    <td class="actions">
                        <a href="admin.php?page=nhaphang_tao&xoa=<?= urlencode($pn['maphieunhap']) ?>&masp=<?= $ct['masp'] ?>"
                           class="btn btn-danger"
                           onclick="return confirm('Xóa sản phẩm này khỏi phiếu?')">
                           Xóa
                        </a>
                    </td>
                    <?php endif; ?>
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