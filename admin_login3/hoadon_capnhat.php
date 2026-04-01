<?php

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý hóa đơn</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            min-height: 100vh;
        }

        .content {
            padding: 90px 25px 60px;
            margin-left: 0;
            transition: margin-left 0.4s ease;
        }

        /* Khi sidebar mở */
        .sidebar.active ~ .content {
            margin-left: 280px;
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

        .btn-clear {
            padding: 6px 20px;
            background: #6c757d;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            margin-bottom:4px;
            font-weight: 500;
        }

        .btn-clear:hover {
            background: #5a6268;
        }

        table#main-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-top: 20px;
        }

        table th, table td {
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            text-align: left;
        }

        table th {
            background: #343a40;
            color: white;
            text-align: center;
            font-weight: 500;
        }

        table tr:nth-child(even) {
            background: #f8f9fa;
        }

        table tr:hover {
            background: #e9ecef;
        }

        .badge-hoantat {
            background: #6c757d;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        select.btn-filter {
            padding: 8px;
            min-width: 140px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
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
        }
    </style>
</head>
<body>
        <?php
    echo "<h1>Cập nhật trạng thái đơn đặt hàng</h1>";
    $tu_ngay  = $_GET['tu_ngay'] ?? '';
    $den_ngay = $_GET['den_ngay'] ?? '';


    // 2. Hiển thị Giao diện Bộ lọc
echo "
    <div class='filter-container'>
        <div class='filter-title'>Lọc đơn hàng theo thời gian</div>
        <form method='GET' class='filter-form'>
            <input type='hidden' name='page' value='hoadon'>
            
            <div class='filter-group'>
                <label>Từ ngày (YYYY-MM-DD)</label>
                <input type='text' id='tu_ngay' name='tu_ngay' value='$tu_ngay' 
                       class='filter-input' placeholder='YYYY-MM-DD' 
                       pattern='\d{4}-\d{2}-\d{2}' title='Định dạng: Năm-Tháng-Ngày (VD: 2024-01-01)'>
            </div>

            <div class='filter-group'>
                <label>Đến ngày (YYYY-MM-DD)</label>
                <input type='text' id='den_ngay' name='den_ngay' value='$den_ngay' 
                       class='filter-input' placeholder='YYYY-MM-DD'
                       pattern='\d{4}-\d{2}-\d{2}' title='Định dạng: Năm-Tháng-Ngày (VD: 2024-12-31)'>
            </div>

            <button type='button' onclick='locVoiAPI()' class='btn-filter'>Lọc kết quả</button>
            <a href='admin.php?page=hoadon_capnhat' class='btn-filter'>Xóa lọc</a>
        </form>
        <div id='ketqua_loc'></div>
    </div>";
    // giao diện lọc tình trạng hóa đơn
    echo " <div class='filter-container'>
<div class='filter-title'>Lọc đơn hàng theo tình trạng đơn hàng</div>
    <div class='filter-form'>
        <div class='filter-group'>
            <label>Tình trạng</label>
            <select id='loc_tinhtrang' class='filter-input'>
                <option value=''>-- Trạng thái --</option>
                <option value='Cho xu ly'>Chờ xử lý</option>
                <option value='Dang giao'>Đang giao</option>
                <option value='Da giao'>Đã giao</option>
                <option value='Da huy'>Đã hủy</option>
            </select> </div>
        <div class='filter-group'>
            <label>Sắp xếp theo phường </label>
            <select id='sapxepphuong' class='filter-input'>
                <option value=''>-- Sắp xếp phường --</option>
                <option value='asc'>A → Z</option>
                <option value='desc'>Z → A</option>
            </select></div>

        <button type='button' class='btn-filter' onclick='locDonHang()'>Lọc kết quả</button>
        <a href='admin.php?page=hoadon_capnhat' class='btn-filter'>Xóa lọc</a>
    </div>

</div>";

    $sql = "SELECT * FROM hoadon join khachhang on hoadon.username = khachhang.username ";
    $rs = mysqli_query($conn, $sql);

     echo "<table id='main-table'>
            <thead>
                    <th>Họ và tên</th>
                    <th>Số điện thoại</th>
                    <th>Mã hóa đơn</th>
                    <th>Ngày đặt</th>
                    <th>Phương thức thanh toán</th>
                    <th>Trạng thái</th>
                    <th>Số nhà giao hàng</th>
                    <th>Phường giao hàng</th>
                    <th>Thành phố giao hàng</th>
                    <th>Trạng thắi</th>
                    <th>Hành động</th>
            </thead>";
   

    while ($row = mysqli_fetch_assoc($rs)) {
     $actionHtml ='';

    if ($row['trangthai'] == 'Da giao' || $row['trangthai'] == 'Da huy') {

       $actionHtml = "<span class='btn-filter' style='background-color: #6c757d;'>Cập nhật</span>";

    } else {

        $actionHtml = "<select class='btn-filter'  onchange=\"capNhatTrangThai('{$row['mahd']}', this.value)\">";
        $actionHtml .= "<option value=''>Cập nhật</option>";

        if ($row['trangthai'] == 'Cho xu ly') {
            $actionHtml .= "<option value='Dang xu ly'>Đang xử lý</option>";
            $actionHtml .= "<option value='Dang giao'>Đang giao</option>";
            $actionHtml .= "<option value='Da giao'>Đã giao </option>";
            $actionHtml .= "<option value='Da huy'>Đã hủy</option>";
        }
        elseif ($row['trangthai'] == 'Dang xu ly') {
            $actionHtml .= "<option value='Dang giao'>Đang giao</option>";
            $actionHtml .= "<option value='Da giao'>Đã giao </option>";
            $actionHtml .= "<option value='Da huy'>Đã hủy</option>";
        }
        elseif ($row['trangthai'] == 'Dang giao') {
            $actionHtml .= "<option value='Da giao'>Đã giao</option>";
            $actionHtml .= "<option value='Da huy'>Đã hủy</option>";
        }

        $actionHtml .= "</select>";
    }
        echo "<tr>
                <td>{$row['hoten']}</td>
                <td>{$row['SDT']}</td>
                <td>{$row['mahd']}</td>
                <td>{$row['ngaydat']}</td>
                <td>{$row['phuongthucthanhtoan']}</td>
                <td>{$row['trangthai']}</td>
                <td>{$row['diachihd']}</td>
                <td>{$row['phuonghd']}</td>
                <td>{$row['thanhphohd']}</td>
                <td>$actionHtml</td>
                <td><button type='button' class='btn-filter' onclick=\"showDetails('{$row['mahd']}')\">Xem chi tiết</button></td>
              </tr>";
    }
    echo "</table>";


?>

</div>
<script>
// xem chi tiết hóa đơn
function showDetails(mahd) {
    var modal = document.getElementById("myModal");
    var modalBody = document.getElementById("modal-body");
    
    modal.style.display = "block";
    modalBody.innerHTML = "Đang tải...";

    // Dùng Fetch API (giống Ajax) để lấy dữ liệu từ file get_chitiet.php
    fetch('chitiethd.php?mahd=' + mahd)
        .then(response => response.text())
        .then(data => {
            modalBody.innerHTML = data;
        })
        .catch(error => {
            modalBody.innerHTML = "Lỗi khi tải dữ liệu!";
            console.error(error);
        });
}

function closeModal() {
    document.getElementById("myModal").style.display = "none";
}

// Đóng popup khi click ra ngoài vùng trắng
window.onclick = function(event) {
    var modal = document.getElementById("myModal");
    if (event.target == modal) {
        closeModal();
    }
}
// lọc hóa đơn theo ngày đặt
function locVoiAPI() {
    const tu = document.getElementById('tu_ngay').value;
    const den = document.getElementById('den_ngay').value;

    let url = 'locngay.php?';
    if (tu)  url += 'tu_ngay=' + encodeURIComponent(tu) + '&';
    if (den) url += 'den_ngay=' + encodeURIComponent(den);

    fetch(url)
        .then(res => res.json())
        .then(data => renderTable(data)) // Gọi hàm vẽ lại bảng
        .catch(err => alert("Lỗi tải dữ liệu!"));
    
}
// render lại thông tin sau khi lọc và chỉ xuất hiện 1 bảng mà thôi
function renderTable(data) {
    const table = document.getElementById('main-table');
    let html = `
        <thead>
            <tr>
                <th>Họ và tên</th><th>Số điện thoại</th><th>Mã hóa đơn</th><th>Ngày đặt</th>
                <th>Thanh toán</th><th>Trạng thái</th>
                <th>Địa chỉ</th><th>Phường</th>
                <th>Thành phố</th><th>Hành động</th>
            </tr>
        </thead>
        <tbody>`;

    if (data.length === 0) {
        html += `<tr><td colspan="10" align="center">Không tìm thấy dữ liệu phù hợp</td></tr>`;
    } else {
        data.forEach(row => {
            html += `
                <tr>
                    <td data-label="Họ và tên">${row.hoten }</td>
                    <td data-label="Số điện thoại">${row.SDT }</td>
                    <td data-label="Mã hóa đơn">${row.mahd}</td>
                    <td data-label="Ngày đặt">${row.ngaydat}</td>
                    <td data-label="Thanh toán">${row.phuongthucthanhtoan}</td>
                    <td data-label="Trạng thái">${row.trangthai}</td>
                    <td data-label="Địa chỉ">${row.diachihd}</td>
                    <td data-label="Phường">${row.phuonghd}</td>
                    <td data-label="Thành phố">${row.thanhphohd}</td>
                    <td data-label="Hành động">
                        <button type='button' class='btn-filter' onclick="showDetails('${row.mahd}')">Xem chi tiết</button>
                    </td>
                </tr>`;
        });
    }
    html += `</tbody>`;
    table.innerHTML = html;
}
// lọc hóa đơn theo tình trạng hóa đơn
function locDonHang() {
    const trangthai = document.getElementById('loc_tinhtrang').value;
    const order = document.getElementById('sapxepphuong').value;

    let url = 'loctinhtrang.php?';
    if (trangthai) url += 'trangthai=' + encodeURIComponent(trangthai) + '&';
    if (order) url += 'order=' + order;

    fetch(url)
        .then(res => res.json())
        .then(data => renderTable(data)) // Gọi hàm vẽ lại bảng
        .catch(err => alert("Lỗi tải dữ liệu!"));
}
// cập nhật trạng thái hóa đơn tịnh tiến
function capNhatTrangThai(mahd, trangthai) {

    if (!trangthai) return;

    fetch("capnhathoadon.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "mahd="+encodeURIComponent(mahd)+
              "&trangthai="+encodeURIComponent(trangthai)
    })
    .then(res=>res.json())
    .then(data=>{
        alert(data.msg);
        if(data.success) location.reload();
    });
}


</script>

<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div id="modal-body">
            <p>Đang tải dữ liệu...</p>
        </div>
    </div>
</div>