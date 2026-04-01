<?php
// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// ==================== XỬ LÝ TÌM KIẾM ====================
$keyword = trim($_GET['keyword'] ?? '');

// ==================== TRUY VẤN SẢN PHẨM + TÊN LOẠI ====================
$sql = "
    SELECT sp.*, l.tenloai 
    FROM sanpham sp
    LEFT JOIN loai l ON sp.maloai = l.maloai
    WHERE 1=1
";

$params = [];
$types  = "";

if (!empty($keyword)) {
    $sql .= " AND (sp.masp LIKE ? OR sp.tensp LIKE ? OR l.tenloai LIKE ?)";
    $search = "%" . $keyword . "%";
    $params = [$search, $search, $search];
    $types  = "sss";
}

$sql .= " ORDER BY sp.masp ASC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Lỗi chuẩn bị truy vấn: " . mysqli_error($conn));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm</title>

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

        table img {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            object-fit: cover;
        }

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

            /* Hình ảnh to hơn và căn giữa */
            td[data-label="Hình ảnh"] {
                text-align: center;
                justify-content: center;
                padding: 12px 15px;
            }
            td[data-label="Hình ảnh"] img {
                width: 90px;
                height: 90px;
            }

            /* Hành động - 2 nút Sửa & Xóa */
            td[data-label="Hành động"] {
                text-align: center;
                justify-content: center;
                flex-wrap: wrap;
                gap: 8px;
                padding: 15px;
                border-bottom: none;
            }
        }

        /* Màn hình rất nhỏ */
        @media (max-width: 480px) {
            td {
                padding-left: 50% !important;
            }
            td:before {
                width: 48%;
            }
            td[data-label="Hình ảnh"] img {
                width: 75px;
                height: 75px;
            }
        }
    </style>
</head>
<body>

    <h1>Quản lý sản phẩm</h1>

    <!-- Phần tìm kiếm -->
    <div class="filter-container">
        <div class="filter-title">Tìm kiếm sản phẩm</div>
        <form method="get" action="admin.php" class="filter-form">
            <input type="hidden" name="page" value="product">
            
            <div class="filter-group">
                
                <input type="text" 
                       name="keyword" 
                       class="filter-input"
                       placeholder="Tìm theo mã sản phẩm, tên sản phẩm hoặc loại..." 
                       value="<?= htmlspecialchars($keyword) ?>">
            </div>

            <button type="submit" class="btn-filter">Tìm kiếm</button>

            <?php if (!empty($keyword)): ?>
                <a href="admin.php?page=product" class="btn-filter">Xóa bộ lọc</a>
            <?php endif; ?>

            <a href="add.php" class="btn-filter ms-auto">Thêm sản phẩm</a>
        </form>
    </div>

    <table id="main-table">
        <thead>
            <tr>
                <th>Mã sản phẩm</th>
                <th>Tên sản phẩm</th>
                <th>Hình ảnh</th>
                <th>Hiện trạng</th>
                <th>Đơn vị</th>
                <th>Giá bán</th>
                <th>% Lợi nhuận</th>
                <th>Loại</th>
                <th>Số lượng tồn</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td data-label="Mã sản phẩm"><?= htmlspecialchars($row['masp']) ?></td>
                        <td data-label="Tên sản phẩm"><?= htmlspecialchars($row['tensp'] ?? 'Chưa có tên') ?></td>
                        
                        <td data-label="Hình ảnh" class="text-center">
                            <?php if (!empty($row['hinhanh']) && file_exists("img/" . $row['hinhanh'])): ?>
                                <img src="img/<?= htmlspecialchars($row['hinhanh']) ?>" 
                                     width="80" height="80" 
                                     alt="<?= htmlspecialchars($row['tensp'] ?? 'Sản phẩm') ?>">
                            <?php else: ?>
                                <span class="text-muted">Không có ảnh</span>
                            <?php endif; ?>
                        </td>

                        <td data-label="Hiện trạng" class="text-center">
                            <?php if ($row['hientrang'] === 'Hiển Thị'): ?>
                                <span class="badge bg-success">Hiển thị</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Ẩn</span>
                            <?php endif; ?>
                        </td>

                        <td data-label="Đơn vị"><?= htmlspecialchars($row['donvitinh'] ?? '-') ?></td>
                        <td data-label="Giá bán" class="text-end">
                            <?= number_format($row['giabandexuat'] ?? 0) ?> ₫
                        </td>
                        <td data-label="% Lợi nhuận" class="text-center">
                            <?= number_format($row['phantramloinhuanmongmuon'] ?? 0, 1) ?>%
                        </td>
                        <td data-label="Loại"><?= htmlspecialchars($row['tenloai'] ?? 'Chưa phân loại') ?></td>
                        <td data-label="Số lượng tồn" class="text-center">
                            <?= number_format($row['soluongtontheolo'] ?? 0) ?>
                        </td>

                        <td data-label="Hành động" class="text-center">
                            <a href="edit.php?id=<?= $row['masp'] ?>" 
                               class="btn btn-filter btn-sm">Sửa</a>
                            <a href="delete.php?id=<?= $row['masp'] ?>" 
                               class="btn btn-filter btn-sm"
                               onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                Xóa
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center py-4">
                        <?php if (!empty($keyword)): ?>
                            Không tìm thấy sản phẩm nào phù hợp với từ khóa "<strong><?= htmlspecialchars($keyword) ?></strong>".
                        <?php else: ?>
                            Chưa có sản phẩm nào
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?php 
mysqli_stmt_close($stmt);
include 'footer.php'; 
?>
</body>
</html>