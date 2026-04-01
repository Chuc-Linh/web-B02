<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: admin.php?page=product");
    exit;
}

// Lấy thông tin sản phẩm
$stmt = mysqli_prepare($conn, "SELECT * FROM sanpham WHERE masp = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sp = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$sp) {
    header("Location: admin.php?page=product");
    exit;
}

// Lấy danh sách loại sản phẩm
$loai_result = mysqli_query($conn, "SELECT * FROM loai");
$loai_list = [];
while ($row = mysqli_fetch_assoc($loai_result)) {
    $loai_list[] = $row;
}

// Xử lý form POST
$error   = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tensp     = trim($_POST['tensp'] ?? '');
    $hientrang = trim($_POST['hientrang'] ?? '');
    $donvitinh = trim($_POST['donvitinh'] ?? '');
    $phantram  = (float)($_POST['phantramloinhuanmongmuon'] ?? 0);
    $maloai    = trim($_POST['maloai'] ?? '');

    // Validation server-side
    if (empty($tensp)) {
        $error = "Tên sản phẩm không được để trống.";
    } elseif (strlen($tensp) < 3) {
        $error = "Tên sản phẩm phải có ít nhất 3 ký tự.";
    } elseif (!in_array($hientrang, ['Hiển Thị', 'Ẩn'])) {
        $error = "Hiện trạng không hợp lệ.";
    } elseif (!in_array($donvitinh, ['Chiếc', 'Đôi'])) {
        $error = "Đơn vị tính không hợp lệ.";
    } elseif ($phantram <= 0) {
        $error = "Phần trăm lợi nhuận mong muốn phải lớn hơn 0.";
    } elseif (empty($maloai)) {
        $error = "Vui lòng chọn loại sản phẩm.";
    } else {
        // Kiểm tra foreign key tồn tại
        $check = mysqli_prepare($conn, "SELECT 1 FROM loai WHERE maloai = ? LIMIT 1");
        mysqli_stmt_bind_param($check, "s", $maloai);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) === 0) {
            $error = "Loại sản phẩm '$maloai' không tồn tại trong hệ thống.";
        } else {
            // Giữ nguyên giá bán đề xuất cũ
            $giabandexuat = $sp['giabandexuat'] ?? 0;

            $update = mysqli_prepare($conn, "
                UPDATE sanpham SET
                    tensp = ?,
                    hientrang = ?,
                    donvitinh = ?,
                    giabandexuat = ?,
                    phantramloinhuanmongmuon = ?,
                    maloai = ?
                WHERE masp = ?
            ");

            mysqli_stmt_bind_param(
                $update,
                "sssddsi",
                $tensp,
                $hientrang,
                $donvitinh,
                $giabandexuat,
                $phantram,
                $maloai,
                $id
            );

            if (mysqli_stmt_execute($update)) {
                $success = "Cập nhật sản phẩm thành công!";
            } else {
                $error = "Lỗi cập nhật: " . mysqli_error($conn);
            }
            mysqli_stmt_close($update);
        }
        mysqli_stmt_close($check);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa sản phẩm - Quản trị</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; min-height: 100vh; }

        /* Header */
        .mobile-header {
            background: #222; color: white; padding: 12px 20px;
            position: fixed; top: 0; left: 0; right: 0;
            z-admin: 1000; display: flex; justify-content: space-between;
            align-items: center; height: 60px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);
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
        .hamburger { width: 30px; height: 20px; cursor: pointer; display: flex; flex-direction: column; justify-content: space-between; }
        .hamburger span { width: 100%; height: 3px; background: white; border-radius: 2px; transition: 0.3s; }
        .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 6px); }
        .hamburger.active span:nth-child(2) { opacity: 0; }
        .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -7px); }

        /* Sidebar */
        .sidebar {
            background: #222; color: white; width: 280px; height: 100vh;
            position: fixed; top: 0; left: 0; padding: 80px 15px 20px;
            overflow-y: auto; transition: transform 0.3s ease; z-admin: 999;
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
            display: block; color: #ccc; text-decoration: none;
            padding: 12px 15px; margin: 4px 0; border-radius: 6px; transition: 0.3s;
        }
        .sidebar a:hover, .sidebar a.active { background: #444; color: white; }

        /* Submenu */
        .submenu {
            display: none;
            background: #111;
            margin: 5px 0;
            border-radius: 6px;
        }
        .submenu.open { display: block !important; }
        .submenu a { padding-left: 35px; font-size: 0.9rem; }
        
        .menu-parent > a { cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .menu-parent > a::after { content: '▼'; font-size: 0.6rem; transition: 0.3s; }
        .menu-parent.active-parent > a::after { transform: rotate(-180deg); }

        .sidebar a.logout { margin-top: 30px; background: #c0392b; color: white; text-align: center; }

        .content { 
            padding: 80px 25px 25px; 
            transition: margin-left 0.3s ease; 
        }
        .sidebar.active ~ .content { margin-left: 280px; }

        @media (max-width: 768px) {
            .sidebar.active ~ .content { margin-left: 0; }
        }

        /* Giữ nguyên style card và form của edit.php */
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

        .form-control[readonly] {
            background: #f8f9fa;
            color: #495057;
            cursor: not-allowed;
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

        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 6px;
            display: inline-block;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .form-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 4px;
        }

        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="mobile-header">
    <div class="hamburger" id="hamburger">
        <span></span><span></span><span></span>
    </div>
    <h2>Xin chào <?= htmlspecialchars($_SESSION['admin'] ?? 'Admin') ?></h2>
</div>

<div class="sidebar" id="sidebar">
    <h2>Menu</h2>
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
    <h1>Sửa sản phẩm</h1>

    <div class="card">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" onsubmit="return validateForm()">

            <div class="mb-3">
                <label class="form-label">Tên sản phẩm</label>
                <input type="text" name="tensp" class="form-control" required value="<?= htmlspecialchars($sp['tensp'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Hiện trạng</label>
                <select name="hientrang" class="form-select" required>
                    <option value="Hiển Thị" <?= ($sp['hientrang'] ?? '') === 'Hiển Thị' ? 'selected' : '' ?>>Hiển Thị</option>
                    <option value="Ẩn"       <?= ($sp['hientrang'] ?? '') === 'Ẩn'       ? 'selected' : '' ?>>Ẩn</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Đơn vị tính</label>
                <select name="donvitinh" class="form-select" required>
                    <option value="Chiếc" <?= ($sp['donvitinh'] ?? '') === 'Chiếc' ? 'selected' : '' ?>>Chiếc</option>
                    <option value="Đôi"   <?= ($sp['donvitinh'] ?? '') === 'Đôi'   ? 'selected' : '' ?>>Đôi</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Giá bán</label>
                <input type="text" class="form-control" readonly value="<?= number_format($sp['giabandexuat'] ?? 0) ?> ₫">
                <div class="form-text">
                    Giá được tính tự động từ giá vốn + <?= number_format($sp['phantramloinhuanmongmuon'] ?? 0, 1) ?>% lợi nhuận mong muốn
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">% Lợi nhuận mong muốn</label>
                <input type="number" step="0.01" min="0.01" name="phantramloinhuanmongmuon" class="form-control" required
                       value="<?= htmlspecialchars($sp['phantramloinhuanmongmuon'] ?? '0') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Loại sản phẩm</label>
                <select name="maloai" class="form-select" required>
                    <option value="">-- Chọn loại sản phẩm --</option>
                    <?php foreach ($loai_list as $loai): ?>
                        <option value="<?= htmlspecialchars($loai['maloai']) ?>"
                            <?= $loai['maloai'] === ($sp['maloai'] ?? '') ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loai['tenloai'] ?? 'Không có tên') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">Cập nhật sản phẩm</button>
        </form>

        <div class="text-center mt-4">
            <a href="admin.php?page=product" class="btn btn-secondary w-100"> Quay lại danh sách</a>
        </div>
    </div>
</div>

<script>
// Toggle sidebar
const hamburger = document.getElementById('hamburger');
const sidebar = document.getElementById('sidebar');
hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    hamburger.classList.toggle('active');
});

// Toggle submenu functions
function toggleSubmenu(e) {
    e.preventDefault();
    const submenu = document.getElementById('nhaphang-submenu');
    const parent = document.getElementById('nhaphang-parent');
    if (submenu.style.display === "block") {
        submenu.classList.remove('open');
        submenu.style.display = "none";
        parent.classList.remove('active-parent');
    } else {
        submenu.classList.add('open');
        submenu.style.display = "block";
        parent.classList.add('active-parent');
    }
}

function toggleSubmenugb(e) {
    e.preventDefault();
    const submenu = document.getElementById('giaban-submenu');
    const parent = document.getElementById('giaban-parent');
    if (submenu.style.display === "block") {
        submenu.classList.remove('open');
        submenu.style.display = "none";
        parent.classList.remove('active-parent');
    } else {
        submenu.classList.add('open');
        submenu.style.display = "block";
        parent.classList.add('active-parent');
    }
}

function toggleSubmenuhd(e) {
    e.preventDefault();
    const submenu = document.getElementById('hoadon-submenu');
    const parent = document.getElementById('hoadon-parent');
    if (submenu.style.display === "block") {
        submenu.classList.remove('open');
        submenu.style.display = "none";
        parent.classList.remove('active-parent');
    } else {
        submenu.classList.add('open');
        submenu.style.display = "block";
        parent.classList.add('active-parent');
    }
}

// Auto open submenu on load
window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    const p = urlParams.get('page');
    
    if (p === 'nhaphang_tao' || p === 'nhaphang_tracuu') {
        document.getElementById('nhaphang-submenu').style.display = "block";
        document.getElementById('nhaphang-parent').classList.add('active-parent');
    }
    if (p === 'giaban_tracuu' || p === 'giaban_capnhap') {
        document.getElementById('giaban-submenu').style.display = "block";
        document.getElementById('giaban-parent').classList.add('active-parent');
    }
    if (p === 'hoadon_loc' || p === 'hoadon_capnhat') {
        document.getElementById('hoadon-submenu').style.display = "block";
        document.getElementById('hoadon-parent').classList.add('active-parent');
    }
};

function validateForm() {
    const tensp = document.querySelector('[name="tensp"]').value.trim();
    const phantram = parseFloat(document.querySelector('[name="phantramloinhuanmongmuon"]').value);

    if (tensp.length < 3) {
        alert("Tên sản phẩm phải có ít nhất 3 ký tự");
        return false;
    }
    if (isNaN(phantram) || phantram <= 0) {
        alert("% lợi nhuận mong muốn phải lớn hơn 0");
        return false;
    }
    return true;
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>