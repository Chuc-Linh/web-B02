<?php
session_start();
require_once 'db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Để highlight menu "Sản phẩm"
$page = 'product';

// Lấy danh sách loại sản phẩm
$loai = mysqli_query($conn, "SELECT * FROM loai");

// Xử lý POST thêm sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tensp   = mysqli_real_escape_string($conn, trim($_POST['tensp'] ?? ''));
    $hientrang = trim($_POST['hientrang'] ?? '');
    $donvitinh = trim($_POST['donvitinh'] ?? '');
    $motasp  = mysqli_real_escape_string($conn, trim($_POST['motasp'] ?? ''));
    $phantram = floatval($_POST['phantramloinhuanmongmuon'] ?? 0);
    $maloai  = trim($_POST['maloai'] ?? '');

    $hinhanh = "";
    $err = null;

    if (empty($tensp)) $err = "Tên sản phẩm không được để trống!";
    elseif (empty($hientrang)) $err = "Hiện trạng không được để trống!";
    elseif (empty($donvitinh)) $err = "Đơn vị tính không được để trống!";
    elseif (empty($motasp)) $err = "Mô tả không được để trống!";
    elseif ($phantram <= 0) $err = "Phần trăm lợi nhuận mong muốn phải lớn hơn 0!";
    elseif (empty($maloai)) $err = "Vui lòng chọn loại sản phẩm!";

    if (!isset($err) && !empty($_FILES['hinhanh']['name'])) {
        $target_dir = "img/";
        $file_ext = strtolower(pathinfo($_FILES["hinhanh"]["name"], PATHINFO_EXTENSION));
        $allowTypes = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($file_ext, $allowTypes)) {
            $err = "Chỉ chấp nhận file JPG, JPEG, PNG, WEBP!";
        } else {
            $new_name = time() . "_" . rand(1000, 9999) . "." . $file_ext;
            $target_file = $target_dir . $new_name;

            if (move_uploaded_file($_FILES["hinhanh"]["tmp_name"], $target_file)) {
                $hinhanh = $new_name;
            } else {
                $err = "Upload ảnh thất bại! Kiểm tra quyền thư mục img/.";
            }
        }
    } elseif (!isset($err)) {
        $err = "Vui lòng chọn hình ảnh!";
    }

    if (!isset($err)) {
        $gia_mac_dinh = 1000000;

        $sql = "INSERT INTO sanpham 
                (tensp, hientrang, donvitinh, motasp, hinhanh, phantramloinhuanmongmuon, giabandexuat, maloai)
                VALUES 
                ('$tensp', '$hientrang', '$donvitinh', '$motasp', '$hinhanh', $phantram, $gia_mac_dinh, '$maloai')";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Thêm sản phẩm thành công!";
            header("Location: admin.php?page=product");
            exit;
        } else {
            $err = "Lỗi INSERT: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Sản Phẩm - Quản trị</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; min-height: 100vh; }

        /* Header */
        .mobile-header {
            background: #222; 
            color: white; 
            padding: 12px 20px;
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 1000; 
            display: flex; 
            justify-content: space-between;
            align-items: center; 
            height: 60px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }

        .mobile-header h2 {
            margin: 0;
            font-size: 1.4rem;
            color: #ffffff;
            font-weight: 700;
            text-shadow: 0 1px 3px rgba(0,0,0,0.9);
            letter-spacing: 0.5px;
        }

        /* Hamburger */
        .hamburger {
            width: 30px;
            height: 20px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .hamburger span {
            width: 100%;
            height: 3px;
            background: white;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        /* Sidebar */
        .sidebar {
            background: #222; 
            color: white; 
            width: 280px; 
            height: 100vh;
            position: fixed; 
            top: 0; 
            left: 0; 
            padding: 80px 15px 20px;
            overflow-y: auto; 
            transition: transform 0.3s ease; 
            z-index: 999;
            transform: translateX(-100%);
        }
        .sidebar.active { transform: translateX(0); }

        .sidebar h2 {
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.4rem;
            color: #ffffff;               
            font-weight: 700;             
            text-shadow: 0 1px 3px rgba(0,0,0,0.9);  
            letter-spacing: 0.5px;         
        }
        .sidebar a {
            display: block; 
            color: #ccc; 
            text-decoration: none;
            padding: 12px 15px; 
            margin: 4px 0; 
            border-radius: 6px; 
            transition: 0.3s;
        }
        .sidebar a:hover, .sidebar a.active { 
            background: #444; 
            color: white; 
        }

        .submenu {
            display: none;
            background: #111;
            margin: 5px 0;
            border-radius: 6px;
        }
        .submenu.open { display: block !important; }
        .submenu a { padding-left: 35px; font-size: 0.9rem; }
        
        .menu-parent > a { 
            cursor: pointer; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .menu-parent > a::after { 
            content: '▼'; 
            font-size: 0.6rem; 
            transition: 0.3s; 
        }
        .menu-parent.active-parent > a::after { 
            transform: rotate(-180deg); 
        }

        .sidebar a.logout { 
            margin-top: 30px; 
            background: #c0392b; 
            color: white; 
            text-align: center; 
        }

        .content { 
            padding: 80px 25px 25px; 
            transition: margin-left 0.3s ease; 
        }
        .sidebar.active ~ .content { 
            margin-left: 280px; 
        }

        @media (max-width: 768px) {
            .sidebar.active ~ .content { 
                margin-left: 0; 
            }
        }

        /* Form */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.1);
            padding: 25px;
            max-width: 600px;
            margin: 0 auto;
        }

        h1, h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 6px;
            display: block;
        }

        .form-control, .form-select {
            margin-bottom: 18px;
        }

        .btn-primary {
            background: #0d6efd;
            border: none;
            padding: 12px;
            font-size: 1.1rem;
        }

        .btn-primary:hover {
            background: #0b5ed7;
        }

        .error {
            color: #dc3545;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 500;
            padding: 10px;
            background: #ffebee;
            border-radius: 6px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #0d6efd;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="mobile-header">
    <div class="hamburger" id="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <h2>Xin chào <?= htmlspecialchars($_SESSION['admin']) ?></h2>
</div>

<div class="sidebar" id="sidebar">
    <h2>Thanh điều hướng</h2>
    <a href="admin.php?page=tongquan" class="<?= $page=='tongquan'?'active':'' ?>">Tổng quan</a>
    <a href="admin.php?page=category" class="<?= $page=='category'?'active':'' ?>">Loại hàng</a>
    <a href="admin.php?page=product" class="<?= $page=='product'?'active':'' ?>">Sản phẩm</a>

    <div class="menu-parent" id="nhaphang-parent">
        <a onclick="toggleSubmenu(event)">Quản lý nhập hàng</a>
        <div class="submenu" id="nhaphang-submenu">
            <a href="admin.php?page=nhaphang_tracuu" class="<?= $page=='nhaphang_tracuu'?'active':'' ?>">Tra cứu phiếu</a>
            <a href="admin.php?page=nhaphang_tao" class="<?= $page=='nhaphang_tao'?'active':'' ?>">Tạo phiếu </a>
        </div>
    </div>

    <div class="menu-parent" id="giaban-parent">
        <a onclick="toggleSubmenugb(event)">Quản lý giá bán</a>
        <div class="submenu" id="giaban-submenu">
            <a href="admin.php?page=giaban_tracuu" class="<?= $page=='giaban_tracuu'?'active':'' ?>">Tra cứu giá </a>
            <a href="admin.php?page=giaban_capnhap" class="<?= $page=='giaban_capnhap'?'active':'' ?>">Cập nhật giá</a>
        </div>
    </div>

    <a href="admin.php?page=khachhang" class="<?= $page=='khachhang'?'active':'' ?>">Khách hàng</a>

    <div class="menu-parent" id="hoadon-parent">
        <a onclick="toggleSubmenuhd(event)">Quản lý hóa đơn</a>
        <div class="submenu" id="hoadon-submenu">
            <a href="admin.php?page=hoadon_loc" class="<?= $page=='hoadon_loc'?'active':'' ?>">Tra cứu hóa đơn</a>
            <a href="admin.php?page=hoadon_capnhat" class="<?= $page=='hoadon_capnhat'?'active':'' ?>">Cập nhật hóa đơn</a>
        </div>
    </div>

    <a href="admin.php?page=tonkho" class="<?= $page=='tonkho'?'active':'' ?>">Tồn kho</a>
    <a href="logout.php" class="logout">Đăng xuất</a>
</div>

<div class="content">
    <h1>Thêm sản phẩm mới</h1>

    <div class="card">
        <?php if (isset($err)): ?>
            <div class="error"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <h2>Thông tin sản phẩm</h2>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Tên sản phẩm</label>
                <input type="text" name="tensp" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Hiện trạng</label>
                <select name="hientrang" class="form-select" required>
                    <option value="Hiển Thị">Hiển Thị</option>
                    <option value="Ẩn">Ẩn</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Đơn vị tính</label>
                <select name="donvitinh" class="form-select" required>
                    <option value="Chiếc">Chiếc</option>
                    <option value="Đôi">Đôi</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Mô tả</label>
                <input type="text" name="motasp" class="form-control" maxlength="255" required>
            </div>
            <div class="mb-3">
                <label class="form-label">% Lợi nhuận mong muốn</label>
                <input type="number" step="0.01" name="phantramloinhuanmongmuon" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Loại sản phẩm</label>
                <select name="maloai" class="form-select" required>
                    <?php while ($row = mysqli_fetch_assoc($loai)): ?>
                        <option value="<?= htmlspecialchars($row['maloai']) ?>">
                            <?= htmlspecialchars($row['tenloai']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label">Hình ảnh sản phẩm</label>
                <input type="file" name="hinhanh" class="form-control" accept="image/*" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Thêm Sản Phẩm</button>
        </form>

        <div class="back-link mt-4">
            <a href="admin.php?page=product">← Quay lại danh sách sản phẩm</a>
        </div>
    </div>
</div>

<script>
// ==================== HAMBURGER & SIDEBAR ====================
const hamburger = document.getElementById('hamburger');
const sidebar = document.getElementById('sidebar');

hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    hamburger.classList.toggle('active');
});

// Đóng sidebar khi click ra ngoài
document.addEventListener('click', function(e) {
    if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
        sidebar.classList.remove('active');
        hamburger.classList.remove('active');
    }
});

// ==================== SUBMENU ====================
function toggleSubmenu(e) {
    e.preventDefault();
    const submenu = document.getElementById('nhaphang-submenu');
    const parent = document.getElementById('nhaphang-parent');
    submenu.classList.toggle('open');
    parent.classList.toggle('active-parent');
}

function toggleSubmenugb(e) {
    e.preventDefault();
    const submenu = document.getElementById('giaban-submenu');
    const parent = document.getElementById('giaban-parent');
    submenu.classList.toggle('open');
    parent.classList.toggle('active-parent');
}

function toggleSubmenuhd(e) {
    e.preventDefault();
    const submenu = document.getElementById('hoadon-submenu');
    const parent = document.getElementById('hoadon-parent');
    submenu.classList.toggle('open');
    parent.classList.toggle('active-parent');
}

// Auto mở submenu khi load trang
window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    const p = urlParams.get('page');
    
    if (p === 'nhaphang_tao' || p === 'nhaphang_tracuu') {
        document.getElementById('nhaphang-submenu').classList.add('open');
        document.getElementById('nhaphang-parent').classList.add('active-parent');
    }
    if (p === 'giaban_tracuu' || p === 'giaban_capnhap') {
        document.getElementById('giaban-submenu').classList.add('open');
        document.getElementById('giaban-parent').classList.add('active-parent');
    }
    if (p === 'hoadon_loc' || p === 'hoadon_capnhat') {
        document.getElementById('hoadon-submenu').classList.add('open');
        document.getElementById('hoadon-parent').classList.add('active-parent');
    }
};
</script>

<?php 
mysqli_free_result($loai);
include 'footer.php'; 
?>
</body>
</html>