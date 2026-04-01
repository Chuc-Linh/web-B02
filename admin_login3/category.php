<?php
// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Xử lý thêm loại sản phẩm mới
$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $maloai  = trim($_POST['maloai']  ?? '');
    $tenloai = trim($_POST['tenloai'] ?? '');
    $mota    = trim($_POST['mota']    ?? '');

    // Validate
    if (empty($maloai)) {
        $error_msg = "Mã loại không được để trống!";
    } elseif (mb_strlen($maloai) > 20) {
        $error_msg = "Mã loại tối đa 20 ký tự!";
    } elseif (!preg_match('/^[\p{L}\p{N}_-]{1,20}$/u', $maloai)) {
        $error_msg = "Mã loại chỉ được chứa chữ cái (bao gồm có dấu tiếng Việt), số, dấu gạch ngang (-) hoặc gạch dưới (_). Không chứa khoảng trắng hoặc ký tự đặc biệt khác!";
    } elseif (empty($tenloai)) {
        $error_msg = "Tên loại sản phẩm không được để trống!";
    } else {
        // Kiểm tra trùng mã loại
        $check_maloai = mysqli_query($conn, "SELECT maloai FROM loai WHERE maloai = '" . mysqli_real_escape_string($conn, $maloai) . "'");
        if (mysqli_num_rows($check_maloai) > 0) {
            $error_msg = "Mã loại '$maloai' đã tồn tại!";
        } else {
            // Kiểm tra trùng tên loại (tùy chọn)
            $check_tenloai = mysqli_query($conn, "SELECT maloai FROM loai WHERE tenloai = '" . mysqli_real_escape_string($conn, $tenloai) . "'");
            if (mysqli_num_rows($check_tenloai) > 0) {
                $error_msg = "Tên loại sản phẩm '$tenloai' đã tồn tại!";
            } else {
                $sql = "INSERT INTO loai (maloai, tenloai, mota) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sss", $maloai, $tenloai, $mota);

                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "Thêm loại sản phẩm thành công!";
                    $_POST = [];
                } else {
                    $error_msg = "Thêm thất bại: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Lấy dữ liệu bảng loai - sắp xếp mới nhất lên đầu
$sql = "SELECT * FROM loai ORDER BY maloai DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý loại sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            min-height: 100vh;
            margin: 0;
        }

        .content {
            padding: 90px 25px 40px;
            margin-left: 0;
            transition: margin-left 0.4s ease;
        }

        .sidebar.active ~ .content {
            margin-left: 280px;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .btn-filter {
            display: inline-block;
            padding: 8px 16px;
            background: #3498db;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-filter:hover {
            background: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-top: 1.5rem;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }

        table th {
            background: #343a40;
            color: white;
            text-align: center;
        }

        table tr:nth-child(even) {
            background: #f8f9fa;
        }

        table tr:hover {
            background: #e9ecef;
        }

        .alert-dismissible {
            margin-bottom: 1.5rem;
        }

        /* RESPONSIVE TABLE - giống product.php và khachhang.php */
        @media (max-width: 768px) {
            .content {
                padding: 80px 15px 30px;
            }

            #main-table thead {
                display: none;
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
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 6px;
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
                white-space: nowrap;
                font-weight: bold;
                text-align: left;
                content: attr(data-label);
            }

            /* Gán nhãn cho từng cột */
            td:nth-of-type(1):before { content: "Mã loại"; }
            td:nth-of-type(2):before { content: "Tên loại"; }
            td:nth-of-type(3):before { content: "Mô tả"; }

            /* Sidebar khi mở trên mobile */
            .sidebar.active ~ .content {
                margin-left: 0; /* hoặc 280px nếu sidebar đẩy nội dung */
            }
        }
    </style>
</head>
<body>

        <h1>Quản lý loại sản phẩm</h1>

        <!-- Thông báo -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Nút mở form thêm -->
        <button type="button" class="btn-filter" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            Thêm loại sản phẩm
        </button>

        <!-- Bảng danh sách -->
        <table id="main-table">
            <thead>
                <tr>
                    <th style="width: 120px;">Mã loại</th>
                    <th>Tên loại</th>
                    <th>Mô tả</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td data-label="Mã loại"><?= htmlspecialchars($row['maloai']) ?></td>
                            <td data-label="Tên loại"><?= htmlspecialchars($row['tenloai']) ?></td>
                            <td data-label="Mô tả"><?= htmlspecialchars($row['mota'] ?: '<em>Không có mô tả</em>') ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-4">Chưa có loại sản phẩm nào</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>


    <!-- Modal Thêm loại sản phẩm -->
    <div class="modal fade" id="addCategoryModal" tabadmin="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Thêm loại sản phẩm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="maloai" class="form-label">Mã loại <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="maloai" name="maloai" 
                                   value="<?= htmlspecialchars($_POST['maloai'] ?? '') ?>" 
                                   required>
                            <div class="form-text">
                                Mã loại duy nhất, không trùng với mã hiện có.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="tenloai" class="form-label">Tên loại sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tenloai" name="tenloai" 
                                   value="<?= htmlspecialchars($_POST['tenloai'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="mota" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="mota" name="mota" rows="3"><?= htmlspecialchars($_POST['mota'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-filter" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" name="add_category" class="btn-filter">Thêm loại</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>
</html>