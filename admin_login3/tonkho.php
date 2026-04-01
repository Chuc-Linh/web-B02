<?php
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once 'db.php';
require_once 'xuly_tonkho.php';
require_once 'xuly_baocao.php';
require_once 'xuly_canhbao.php';

$ketquaTon = null;
$ketquaBaoCao = null;
$ketquaCanhBao = null;
$errorDate = null;
$loi = 1;

/* ===== XỬ LÝ TRA CỨU TỒN ===== */
if (isset($_POST['tracuu'])) {
    $ketquaTon = tinhTonKhoTheoLoai(
        $conn,
        $_POST['maloai'],
        $_POST['ngay']
    );
}

/* ===== XỬ LÝ BÁO CÁO NHẬP XUẤT ===== */
if (isset($_POST['baocao'])) {
    $tungaytk = trim($_POST['tungaytk'] ?? '');
    $denngaytk = trim($_POST['denngaytk'] ?? '');

    $tuDate = strtotime($tungaytk);
    $denDate = strtotime($denngaytk);

    if ($denDate <= $tuDate) {
        $errorDate = "Ngày đến phải lớn hơn từ ngày.";
        $loi = 0;
    } else {
        $ketquaBaoCao = baoCaoNhapXuat($conn, $tungaytk, $denngaytk);
        $loi = 1;
    }
}

/* ===== XỬ LÝ CẢNH BÁO HẾT HÀNG ===== */
if (isset($_POST['canhbao'])) {
    $nguong = intval($_POST['nguong'] ?? 0);
    if ($nguong >= 0) {
        $ketquaCanhBao = sanPhamSapHet($conn, $nguong);
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tồn kho</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            min-height: 100vh;
        }

        /* Khi sidebar mở từ index.php */
            .sidebar.active ~ .content {
                margin-left: 0; /* hoặc 280px nếu sidebar đẩy nội dung */
            }
        
        h1 { color: #2c3e50; margin-bottom: 20px;  font-weight: bold;}

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

        .btn-filter{
        display:inline-block;
        padding:8px 16px;
        background:#3498db;
        color:white;
        border-radius:6px;
        text-decoration:none;
        font-size:14px;
        transition:0.2s;
        border:none;
        margin-bottom: 3px; 
        cursor:pointer;
    }

        .btn-filter:hover{
            background:#2980b9;
        }

        .result {
            margin-top: 25px;
            padding: 15px;
            background: #e9f5ff;
            border-left: 5px solid #007bff;
            border-radius: 6px;
            font-size: 1.1rem;
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

        table tr:nth-child(even) {
            background: #f8f9fa;
        }

        table tr:hover {
            background: #e9ecef;
        }

        /* Cảnh báo hết hàng - màu đỏ nổi bật */
        .table-warning th {
            background: #343a40 !important;
        }

        .table-warning td {
            color: #343a40;
        }

        @media (max-width: 768px) {
            .content {
                padding: 80px 15px 40px;
            }

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
            
    </style>
</head>
<body>
        <h1>Quản lý tồn kho</h1>
        <!-- Tra cứu tồn kho -->
        <div class="filter-container">
            <div class="filter-title">Tra cứu số lượng tồn</div>
            <form method="post" class="filter-form">
                <input type="hidden" name="page" value="tonkho">

                <div class="filter-group">
                    <label>Loại sản phẩm</label>
                    <select name="maloai" required class="filter-input">
                        <option value="">-- Chọn loại --</option>
                        <?php
                        $loai = mysqli_query($conn, "SELECT * FROM loai");
                        while ($row = mysqli_fetch_assoc($loai)) {
                            echo "<option value='" . htmlspecialchars($row['maloai']) . "'>" . htmlspecialchars($row['maloai']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Thời điểm</label>
                    <input type="text" id="ngay" name="ngay" required
                           class="filter-input" placeholder="YYYY-MM-DD"
                           pattern="\d{4}-\d{2}-\d{2}" title="Định dạng: Năm-Tháng-Ngày (VD: 2024-01-01)">
                </div>

                <button type="submit" name="tracuu" class="btn-filter">Tra cứu</button>
            </form>
            
        </div>

        <!-- Báo cáo nhập - xuất -->
        <div class="filter-container">
            <div class="filter-title">Báo cáo tổng số lượng nhập – xuất sản phẩm</div>
            <form method="post" onsubmit="return validateDate()" class="filter-form">
                <input type="hidden" name="page" value="tonkho">

                <div class="filter-group">
                    <label>Từ ngày (YYYY-MM-DD)</label>
                    <input type="text" id="tungaytk" name="tungaytk" required
                           class="filter-input" placeholder="YYYY-MM-DD"
                           pattern="\d{4}-\d{2}-\d{2}" title="Định dạng: Năm-Tháng-Ngày (VD: 2024-01-01)">
                </div>

                <div class="filter-group">
                    <label>Đến ngày (YYYY-MM-DD)</label>
                    <input type="text" id="denngaytk" name="denngaytk" required
                           class="filter-input" placeholder="YYYY-MM-DD"
                           pattern="\d{4}-\d{2}-\d{2}" title="Định dạng: Năm-Tháng-Ngày (VD: 2024-12-31)">
                </div>

                <button type="submit" name="baocao" class="btn-filter">Lọc kết quả</button>
            </form>

            <?php if ($errorDate): ?>
                <div style="color: red; margin-top: 10px; font-weight: bold;">
                    <?= htmlspecialchars($errorDate) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Cảnh báo sản phẩm sắp hết hàng -->
        <div class="filter-container">
            <div class="filter-title">Cảnh báo sản phẩm sắp hết hàng</div>
            <form method="post" class="filter-form">
                <div class="filter-group">
                    <label>Ngưỡng tồn kho</label>
                    <input type="number" name="nguong"
                           class="filter-input"
                           placeholder="Ví dụ: 10" min="0" required>
                </div>

                <button type="submit" name="canhbao" class="btn-filter">Xem cảnh báo</button>
            </form>
        </div>

 <!-- Báo cáo tồn kho-->
        <?php if ($ketquaTon!== null): ?>
            <table>
                <thead>
                    <tr>
                        <th>Mã loại</th>
                        <th>Mã sản phẩm</th>
                        <th>Tên sản phẩm</th>
                        <th>Tổng tồn</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $ketquaTon->fetch_assoc()): ?>
                        <tr>
                        <td data-label="Mã loại"><?= htmlspecialchars($row['maloai']) ?></td>
                        <td data-label="Mã sản phẩm"><?= htmlspecialchars($row['masp']) ?></td>
                        <td data-label="Tên sản phẩm"><?= htmlspecialchars($row['tensp']) ?></td>
                        <td data-label="Tổng tồn"><?= htmlspecialchars($row['tonkho'] ?? '0') ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Báo cáo nhập - xuất -->
        <?php if ($ketquaBaoCao !== null && $loi == 1): ?>
            <table>
                <thead>
                    <tr>
                        <th>Mã sản phẩm</th>
                        <th>Tên sản phẩm</th>
                        <th>Tổng nhập</th>
                        <th>Tổng xuất</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $ketquaBaoCao->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Mã sản phẩm"><?= htmlspecialchars($row['masp']) ?></td>
                            <td data-label="Tên sản phẩm"><?= htmlspecialchars($row['tensp']) ?></td>
                            <td data-label="Tổng nhập"><?= htmlspecialchars($row['tongnhap'] ?? '0') ?></td>
                            <td data-label="Tổng xuất"><?= htmlspecialchars($row['tongxuat'] ?? '0') ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Cảnh báo hết hàng -->
        <?php if ($ketquaCanhBao !== null): ?>
            <table class="table-warning">
                <thead>
                    <tr>
                        <th>Mã sản phẩm</th>
                        <th>Tên sản phẩm</th>
                        <th>Tồn kho</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $ketquaCanhBao->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Mã sản phẩm"><?= htmlspecialchars($row['masp']) ?></td>
                            <td data-label="Tên sản phẩm"><?= htmlspecialchars($row['tensp']) ?></td>
                            <td data-label="Tồn kho" style="color: red; font-weight: bold;">
                                <?= htmlspecialchars($row['tonkho']) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>

    <script>
    /* Kiểm tra ngày đến > ngày từ */
    function validateDate() {
        const tuStr = document.querySelector("[name='tungaytk']").value.trim();
        const denStr = document.querySelector("[name='denngaytk']").value.trim();

        if (!tuStr || !denStr) return true;

        const regex = /^\d{4}-\d{2}-\d{2}$/;
        if (!regex.test(tuStr) || !regex.test(denStr)) {
            alert("Vui lòng nhập đúng định dạng YYYY-MM-DD");
            return false;
        }

        const tuDate = new Date(tuStr);
        const denDate = new Date(denStr);

        if (denDate <= tuDate) {
            alert("Ngày đến phải lớn hơn từ ngày.");
            return false;
        }

        return true;
    }
    </script>
<?php include 'footer.php'; ?>
</body>
</html>