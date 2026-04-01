<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$allowed_pages = [
    'tongquan', 'category', 'product', 'khachhang', 
    'hoadon', 'tonkho', 'nhaphang_tao', 'nhaphang_tracuu', 
    'giaban', 'donhang', 'giaban_tracuu', 'giaban_capnhap', 'hoadon_loc', 'hoadon_capnhat'
];

$page = isset($_GET['page']) ? $_GET['page'] : 'tongquan';
if (!in_array($page, $allowed_pages)) { $page = 'tongquan'; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị viên</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; min-height: 100vh; }

        /* Header */
        .mobile-header {
            background: #222; color: white; padding: 12px 20px;
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 1000; display: flex; justify-content: space-between;
            align-items: center; height: 60px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);
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
            overflow-y: auto; transition: transform 0.3s ease; z-index: 999;
            transform: translateX(-100%);
        }
        .sidebar.active { transform: translateX(0); }

        .sidebar h2 { margin-bottom: 20px; text-align: center; font-size: 1.4rem; }
        .sidebar a {
            display: block; color: #ccc; text-decoration: none;
            padding: 12px 15px; margin: 4px 0; border-radius: 6px; transition: 0.3s;
        }
        .sidebar a:hover, .sidebar a.active { background: #444; color: white; }

        /* --- PHẦN QUAN TRỌNG: SUBMENU --- */
        .submenu {
            display: none; /* Mặc định ẩn hẳn */
            background: #111;
            margin: 5px 0;
            border-radius: 6px;
        }
        .submenu.open { 
            display: block !important; /* Hiện lên khi có class open */
        }
        .submenu a { padding-left: 35px; font-size: 0.9rem; }
        
        .menu-parent > a { cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .menu-parent > a::after { content: '▼'; font-size: 0.6rem; transition: 0.3s; }
        .menu-parent.active-parent > a::after { transform: rotate(-180deg); }
        /* ------------------------------- */

        .sidebar a.logout { margin-top: 30px; background: #c0392b; color: white; text-align: center; }

        .content { padding: 80px 25px 25px; transition: margin-left 0.3s ease; }
        .sidebar.active ~ .content { margin-left: 280px; }

        @media (max-width: 768px) {
            .sidebar.active ~ .content { margin-left: 0; }
        }
    </style>
</head>
<body>

<div class="mobile-header">
    <div class="hamburger" id="hamburger">
        <span></span><span></span><span></span>
    </div>
    <h2>Xin chào <?= htmlspecialchars($_SESSION['admin']) ?></h2>
</div>

<div class="sidebar" id="sidebar">
    <h2>Thanh điều hướng</h2>
    <a href="admin.php?page=tongquan" class="<?= $page=='tongquan'?'active':'' ?>">Tổng quan</a>
    <a href="admin.php?page=category" class="<?= $page=='category'?'active':'' ?>">Quản lý loại hàng</a>
    <a href="admin.php?page=product" class="<?= $page=='product'?'active':'' ?>">Quản lý sản phẩm</a>

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
            <a href="admin.php?page=giaban_tracuu" class="<?= $page=='giaban_tracuu'?'active':'' ?>">Tra cứu giá vốn/bán, lợi nhuận </a>
            <a href="admin.php?page=giaban_capnhap" class="<?= $page=='giaban_capnhap'?'active':'' ?>">Cập nhật % lợi nhuận</a>
        </div>
    </div>
    <a href="admin.php?page=khachhang" class="<?= $page=='khachhang'?'active':'' ?>">Quản lý tài khoản khách hàng</a>
    <div class="menu-parent" id="hoadon-parent">
        <a onclick="toggleSubmenuhd(event)">Quản lý đơn đặt hàng</a>
        <div class="submenu" id="hoadon-submenu">
            <a href="admin.php?page=hoadon_loc" class="<?= $page=='hoadon_loc'?'active':'' ?>">Lọc đơn đặt hàng</a>
            <a href="admin.php?page=hoadon_capnhat" class="<?= $page=='hoadon_capnhat'?'active':'' ?>">Cập nhật trạng thái đơn đặt hàng</a>
        </div>
    </div>
    <a href="admin.php?page=tonkho" class="<?= $page=='tonkho'?'active':'' ?>">Quản lý tồn kho</a>
    <a href="logout.php" class="logout">Đăng xuất</a>
</div>

<div class="content">
    <?php
    if (file_exists($page . ".php")) {
        include $page . ".php";
    } else {
        echo "<h1>Trang không tồn tại</h1>";
    }
    ?>
</div>

<script>
    // Xử lý Sidebar Mobile
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        hamburger.classList.toggle('active');
    });

    // Xử lý Đóng/Mở Submenu
    function toggleSubmenu(e) {
        e.preventDefault();
        const submenu = document.getElementById('nhaphang-submenu');
        const parent = document.getElementById('nhaphang-parent');
        
        // Thêm/Xóa class để ẩn hiện
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

    // Tự động mở menu nếu đang ở trang con sau khi load lại
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const p = urlParams.get('page');
        if (p === 'nhaphang_tao' || p === 'nhaphang_tracuu') {
            document.getElementById('nhaphang-submenu').style.display = "block";
            document.getElementById('nhaphang-parent').classList.add('active-parent');
        }
    };
    function toggleSubmenugb(e) {
        e.preventDefault();
        const submenu = document.getElementById('giaban-submenu');
        const parent = document.getElementById('giaban-parent');
        
        // Thêm/Xóa class để ẩn hiện
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

    // Tự động mở menu nếu đang ở trang con sau khi load lại
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const p = urlParams.get('page');
        if (p === 'giaban_tracuu' || p === 'giaban_capnhap') {
            document.getElementById('giaban-submenu').style.display = "block";
            document.getElementById('giaban-parent').classList.add('active-parent');
        }
    };
    function toggleSubmenuhd(e) {
        e.preventDefault();
        const submenu = document.getElementById('hoadon-submenu');
        const parent = document.getElementById('hoadon-parent');
        
        // Thêm/Xóa class để ẩn hiện
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

    // Tự động mở menu nếu đang ở trang con sau khi load lại
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const p = urlParams.get('page');
        if (p === 'hoadon_loc'|| p === 'hoadon_capnhat') {
            document.getElementById('hoadon-submenu').style.display = "block";
            document.getElementById('hoadon-parent').classList.add('active-parent');
        }
    };
</script>

</body>
</html>