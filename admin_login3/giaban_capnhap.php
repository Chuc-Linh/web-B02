<?php
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$thongbao = "";

/* ==============================
   CẬP NHẬT % LỢI NHUẬN & TÍNH LẠI GIÁ BÁN ĐỀ XUẤT
============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_phantram'])) {

    $masp     = (int)$_POST['masp'];
    $phantram = (float)$_POST['phantramloinhuanmongmuon'];

    if ($phantram <= 0) {
        $thongbao = "Tỷ lệ lợi nhuận phải lớn hơn 0.";
    } else {
        $sql_get = "SELECT giavon FROM sanpham WHERE masp = ?";
        $stmt_get = mysqli_prepare($conn, $sql_get);
        mysqli_stmt_bind_param($stmt_get, "i", $masp);
        mysqli_stmt_execute($stmt_get);
        $result_get = mysqli_stmt_get_result($stmt_get);
        $row = mysqli_fetch_assoc($result_get);
        $giavon = $row['giavon'] ?? 0;
        mysqli_stmt_close($stmt_get);

        $giabandexuat_moi = round($giavon * (1 + $phantram / 100));

        $sql = "UPDATE sanpham 
                SET phantramloinhuanmongmuon = ?,
                    giabandexuat = ?
                WHERE masp = ?";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ddi", $phantram, $giabandexuat_moi, $masp);

        if (mysqli_stmt_execute($stmt)) {
            $thongbao = "Cập nhật tỷ lệ lợi nhuận và giá bán đề xuất thành công!";
        } else {
            $thongbao = "Lỗi: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
    }
}

/* ==============================
   XỬ LÝ TÌM KIẾM
============================== */
$keyword = trim($_GET['keyword'] ?? '');

/* ==============================
   LẤY DANH SÁCH SẢN PHẨM + LOẠI (CÓ TÌM KIẾM)
============================== */
$sql = "
    SELECT 
        sp.masp,
        sp.tensp,
        COALESCE(l.tenloai, 'Chưa phân loại') AS tenloai,
        sp.giavon,
        sp.phantramloinhuanmongmuon,
        sp.giabandexuat,
        sp.soluongtontheolo
    FROM sanpham sp
    LEFT JOIN loai l ON sp.maloai = l.maloai
    WHERE 1=1
";

$params = [];
$types  = "";

if (!empty($keyword)) {
    $sql .= " AND (sp.tensp LIKE ? OR l.tenloai LIKE ?)";
    $search = "%" . $keyword . "%";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

$sql .= " ORDER BY sp.tensp ASC";

$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sanphams = [];

while ($row = mysqli_fetch_assoc($result)) {
    $sanphams[] = $row;
}

mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật giá bán</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            min-height: 100vh;
            margin: 0;
        }

        h1 { 
            color: #2c3e50; 
            margin-bottom: 20px; 
            font-weight: bold;
        }

        .filter-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .filter-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #444;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 220px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .filter-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 1rem;
        }

        .btn-filter {
            display: inline-block;
            padding: 8px 16px;
            background: #3498db;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            margin-bottom: 3px;
        }

        .btn-filter:hover {
            background: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-top: 20px;
        }

        table th, table td {
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            text-align: center;
        }

        table th {
            background: #343a40;
            color: white;
        }

        table tr:nth-child(even) { background: #f8f9fa; }
        table tr:hover { background: #e9ecef; }

        /* ==================== RESPONSIVE - ĐỒNG BỘ VỚI tonkho.php ==================== */
        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }

            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            tr {
                margin-bottom: 1.5rem;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                background: white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }

            td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 45% !important;
                text-align: right !important;
                white-space: normal;
                word-break: break-word;
                min-height: 45px;
                display: flex;
                align-items: center;
                justify-content: flex-end;
            }

            td:before {
                position: absolute;
                top: 0;
                left: 15px;
                width: 40%;
                height: 100%;
                display: flex;
                align-items: center;
                padding-right: 10px;
                font-weight: bold;
                text-align: left;
                content: attr(data-label);
            }

            /* Form cập nhật % lợi nhuận trên mobile */
            td[data-label="Cập nhật % lợi nhuận"] form {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
                gap: 8px;
                padding: 10px 0;
            }

            /* Bỏ border cuối cùng của card */
            td:last-child { border-bottom: none; }
        }

        /* Màn hình rất nhỏ */
        @media (max-width: 480px) {
            td {
                padding-left: 50% !important;
            }
            td:before {
                width: 48%;
            }
        }

        /* Style cho input % lợi nhuận */
        input[name="phantramloinhuanmongmuon"] {
            width: 90px !important;
            padding: 6px 8px !important;
        }
    </style>
</head>
<body>

    <h1>Cập nhật giá bán</h1>

    <?php if (!empty($thongbao)): ?>
    <div class="alert <?= strpos($thongbao, 'thành công') !== false ? 'alert-success' : 'alert-danger' ?>">
        <?= htmlspecialchars($thongbao) ?>
    </div>
    <?php endif; ?>

    <!-- Phần tìm kiếm - Đồng bộ với các file khác -->
    <div class="filter-container">
        <div class="filter-title">Tìm kiếm sản phẩm</div>
        <form method="get" action="admin.php" class="filter-form">
            <input type="hidden" name="page" value="giaban_capnhap">
            
            <div class="filter-group">
                
                <input type="text" 
                       name="keyword" 
                       class="filter-input"
                       placeholder="Tìm theo tên sản phẩm hoặc loại..." 
                       value="<?= htmlspecialchars($keyword) ?>">
            </div>

            <button type="submit" class="btn-filter">Tìm kiếm</button>

            <?php if (!empty($keyword)): ?>
                <a href="admin.php?page=giaban_capnhap" class="btn-filter">Xóa bộ lọc</a>
            <?php endif; ?>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Mã sản phẩm</th>
                <th>Tên sản phẩm</th>
                <th>Loại sản phẩm</th>
                <th>Giá vốn</th>
                <th>% Lợi nhuận</th>
                <th>Giá bán</th>
                <th>Tồn kho</th>
                <th>Cập nhật % lợi nhuận</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sanphams)): ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:30px; color:#777;">
                        Không tìm thấy sản phẩm nào phù hợp.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($sanphams as $sp): ?>
                <tr>
                    <td data-label="Mã sản phẩm"><?= htmlspecialchars($sp['masp']) ?></td>
                    <td data-label="Tên sản phẩm" class="text-left"><?= htmlspecialchars($sp['tensp']) ?></td>
                    <td data-label="Loại sản phẩm" class="text-left"><?= htmlspecialchars($sp['tenloai']) ?></td>
                    <td data-label="Giá vốn"><?= number_format($sp['giavon']) ?> ₫</td>
                    <td data-label="% Lợi nhuận"><?= number_format($sp['phantramloinhuanmongmuon'], 1) ?> %</td>
                    <td data-label="Giá bán"><?= number_format($sp['giabandexuat']) ?> ₫</td>
                    <td data-label="Tồn kho"><?= number_format($sp['soluongtontheolo']) ?></td>
                    <td data-label="Cập nhật % lợi nhuận">
                        <form method="post" style="display:flex; align-items:center; gap:8px; justify-content:center; flex-wrap:wrap;">
                            <input type="hidden" name="masp" value="<?= $sp['masp'] ?>">
                            <input type="hidden" name="keyword" value="<?= htmlspecialchars($keyword) ?>">
                            <input type="number" 
                                   name="phantramloinhuanmongmuon" 
                                   value="<?= $sp['phantramloinhuanmongmuon'] ?>" 
                                   step="0.1" min="0.1" required>
                            <button type="submit" name="update_phantram" class="btn btn-filter btn-sm">
                                Cập nhật
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

<?php include 'footer.php'; ?>

</body>
</html>