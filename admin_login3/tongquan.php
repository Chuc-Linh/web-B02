<?php
// tongquan.php - Dashboard Tổng quan admin

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// === THÊM: 3 chỉ số tổng quan nhanh ===
$total_sp = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM sanpham 
    WHERE soluongtontheolo > 0
"))['total'] ?? 0;

$total_don_giao = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM hoadon 
    WHERE trangthai = 'Da giao'
"))['total'] ?? 0;

$doanhthu_homnay = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(thanhtienhd), 0) AS doanhthu 
    FROM hoadon 
    WHERE trangthai = 'Da giao' 
      AND DATE(ngaydat) = CURDATE()
"))['doanhthu'] ?? 0;

// === 1. Sản phẩm bán chạy nhất (Top 5) ===
$sql_top_sp = "
    SELECT sp.tensp, SUM(ct.soluongmua) AS tong_soluong
    FROM chitiethd ct
    JOIN hoadon hd ON ct.mahd = hd.mahd
    JOIN sanpham sp ON ct.masp = sp.masp
    WHERE hd.trangthai = 'Da giao'
    GROUP BY ct.masp, sp.tensp
    ORDER BY tong_soluong DESC
    LIMIT 5
";
$res_top_sp = mysqli_query($conn, $sql_top_sp) or die(mysqli_error($conn));

$top_sp_labels = [];
$top_sp_data   = [];

while ($row = mysqli_fetch_assoc($res_top_sp)) {
    $top_sp_labels[] = $row['tensp'];
    $top_sp_data[]   = (int)$row['tong_soluong'];
}

// === 2. Khách hàng mua nhiều nhất (Top 5 theo tổng tiền) ===
$sql_top_kh = "
    SELECT kh.hoten, SUM(hd.thanhtienhd) AS tong_tien
    FROM hoadon hd
    JOIN khachhang kh ON hd.username = kh.username
    WHERE hd.trangthai = 'Da giao'
    GROUP BY hd.username, kh.hoten
    ORDER BY tong_tien DESC
    LIMIT 5
";
$res_top_kh = mysqli_query($conn, $sql_top_kh) or die(mysqli_error($conn));

$top_kh_labels = [];
$top_kh_data   = [];

while ($row = mysqli_fetch_assoc($res_top_kh)) {
    $top_kh_labels[] = $row['hoten'];
    $top_kh_data[]   = (float)$row['tong_tien'];
}

// === 3. Doanh thu theo tháng (12 tháng gần nhất) ===
$sql_doanhthu = "
    SELECT DATE_FORMAT(hd.ngaydat, '%Y-%m') AS thang,
           SUM(hd.thanhtienhd) AS doanhthu
    FROM hoadon hd
    WHERE hd.trangthai = 'Da giao'
      AND hd.ngaydat >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY thang
    ORDER BY thang ASC
";
$res_dt = mysqli_query($conn, $sql_doanhthu) or die(mysqli_error($conn));

$dt_labels = [];
$dt_data   = [];

while ($row = mysqli_fetch_assoc($res_dt)) {
    $dt_labels[] = $row['thang'];
    $dt_data[]   = (float)$row['doanhthu'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tổng quan - Quản trị</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            font-size: 1.9rem;
            margin-bottom: 1.8rem;
            color: #333;
            font-weight: bold;
        }

        .card-chart {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 30px;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #444;
        }

        .no-data {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 40px 0;
        }

        canvas {
            max-height: 380px !important;
            width: 100% !important;
        }

        .overview-card {
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            padding: 20px;
            text-align: center;
            color: white;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: transform 0.2s;
        }

        .overview-card:hover {
            transform: translateY(-5px);
        }

        .overview-card h3 {
            font-size: 2.2rem;
            font-weight: bold;
            margin: 0;
        }

        .overview-card p {
            font-size: 1.1rem;
            margin: 8px 0 0;
            opacity: 0.9;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .content {
                padding: 80px 15px 30px;
            }

            h1 {
                font-size: 1.6rem;
                margin-bottom: 1.5rem;
            }

            .overview-card {
                min-height: 120px;
                padding: 15px;
            }

            .overview-card h3 {
                font-size: 1.8rem;
            }

            .overview-card p {
                font-size: 1rem;
            }

            .card-chart {
                padding: 15px;
            }

            .card-title {
                font-size: 1.1rem;
            }

            /* Đảm bảo biểu đồ không bị vỡ */
            canvas {
                max-height: 300px !important;
            }

            /* Sidebar khi mở trên mobile - nếu sidebar overlay thì margin = 0 */
            .sidebar.active ~ .content {
                margin-left: 0; /* thay đổi nếu sidebar đẩy nội dung */
            }
        }

        @media (max-width: 576px) {
            .overview-card h3 {
                font-size: 1.6rem;
            }
            .overview-card p {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>

<div class="content">

    <h1>Tổng quan hệ thống</h1>

    <!-- 3 ô vuông tổng quan nhanh -->
    <div class="row mb-4 g-4">
        <div class="col-md-4 col-sm-6">
            <div class="overview-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                <h3><?= number_format($total_sp) ?></h3>
                <p>Sản phẩm đang kinh doanh</p>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="overview-card" style="background: linear-gradient(135deg, #42a5f5, #1976d2);">
                <h3><?= number_format($total_don_giao) ?></h3>
                <p>Đơn hàng đã giao thành công</p>
            </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="overview-card" style="background: linear-gradient(135deg, #66bb6a, #388e3c);">
                <h3><?= number_format($doanhthu_homnay) ?> ₫</h3>
                <p>Doanh thu hôm nay</p> <!-- sửa nhãn cho đúng (file gốc ghi nhầm "theo tháng") -->
            </div>
        </div>
    </div>

    <!-- Biểu đồ 1: Sản phẩm bán chạy nhất -->
    <div class="card-chart">
        <div class="card-title">Top 5 sản phẩm bán chạy nhất (số lượng)</div>
        <?php if (count($top_sp_data) > 0): ?>
            <canvas id="chartTopSP"></canvas>
        <?php else: ?>
            <div class="no-data">Chưa có dữ liệu bán hàng</div>
        <?php endif; ?>
    </div>

    <!-- Biểu đồ 2: Khách hàng mua nhiều nhất -->
    <div class="card-chart">
        <div class="card-title">Top 5 khách hàng mua nhiều nhất (tổng tiền)</div>
        <?php if (count($top_kh_data) > 0): ?>
            <canvas id="chartTopKH"></canvas>
        <?php else: ?>
            <div class="no-data">Chưa có dữ liệu khách hàng</div>
        <?php endif; ?>
    </div>

    <!-- Biểu đồ 3: Doanh thu theo tháng -->
    <div class="card-chart">
        <div class="card-title">Doanh thu theo tháng (12 tháng gần nhất)</div>
        <?php if (count($dt_data) > 0): ?>
            <canvas id="chartDoanhThu"></canvas>
        <?php else: ?>
            <div class="no-data">Chưa có dữ liệu doanh thu</div>
        <?php endif; ?>
    </div>

</div>

<script>
// Biểu đồ Top sản phẩm bán chạy (Bar)
<?php if (count($top_sp_data) > 0): ?>
const ctxSP = document.getElementById('chartTopSP').getContext('2d');
new Chart(ctxSP, {
    type: 'bar',
    data: {
        labels: <?= json_encode($top_sp_labels) ?>,
        datasets: [{
            label: 'Số lượng đã bán',
            data: <?= json_encode($top_sp_data) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.65)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,  // giúp biểu đồ co giãn tốt hơn trên mobile
        scales: { y: { beginAtZero: true } },
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y} chiếc` } }
        }
    }
});
<?php endif; ?>

// Biểu đồ Top khách hàng (Pie)
<?php if (count($top_kh_data) > 0): ?>
const ctxKH = document.getElementById('chartTopKH').getContext('2d');
new Chart(ctxKH, {
    type: 'pie',
    data: {
        labels: <?= json_encode($top_kh_labels) ?>,
        datasets: [{
            label: 'Tổng tiền mua',
            data: <?= json_encode($top_kh_data) ?>,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' },
            tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.parsed.toLocaleString('vi-VN')} ₫` } }
        }
    }
});
<?php endif; ?>

// Biểu đồ Doanh thu (Line)
<?php if (count($dt_data) > 0): ?>
const ctxDT = document.getElementById('chartDoanhThu').getContext('2d');
new Chart(ctxDT, {
    type: 'line',
    data: {
        labels: <?= json_encode($dt_labels) ?>,
        datasets: [{
            label: 'Doanh thu tháng',
            data: <?= json_encode($dt_data) ?>,
            fill: true,
            backgroundColor: 'rgba(75, 192, 192, 0.18)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 2,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } },
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString('vi-VN')} ₫` } }
        }
    }
});
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>
</body>
</html>