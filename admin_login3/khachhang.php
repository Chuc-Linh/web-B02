<?php

// kiểm tra đăng nhập index
if (!isset($_SESSION['admin'])) {
    header("Location:admin.php");
    exit;
}

require_once 'db.php';
// --- XỬ LÝ LOGIC (QUAN TRỌNG: Đặt trước khi hiển thị HTML) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $action = $_GET['action'];
    $sql_action = "";
    $msg = "";

    if ($action == 'khoa') {
        $sql_action = "UPDATE khachhang SET trangthaitk = 'Bị khóa' WHERE username = '$id'";
        mysqli_query($conn, $sql_action);
        $msg = "Đã khóa tài khoản thành công!";
    } elseif ($action == 'mo') {
        $sql_action = "UPDATE khachhang SET trangthaitk = 'Hoạt động' WHERE username = '$id'";
        mysqli_query($conn, $sql_action);
        $msg = "Đã mở khóa tài khoản thành công!";
    } elseif ($action == 'reset') {

    $matkhau_macdinh = "123456";

    // mã hóa mật khẩu
    $matkhau_mahoa = password_hash($matkhau_macdinh, PASSWORD_DEFAULT);

    $sql_action = "UPDATE khachhang 
                   SET matkhau = '$matkhau_mahoa' 
                   WHERE username = '$id'";
     if (mysqli_query($conn, $sql_action)) {
        echo "<script>
                alert('Mật khẩu đã được đặt lại về mặc định (123456)');
                window.location.href='admin.php?page=khachhang';
              </script>";
        exit;
}
    }
}

// Xử lý thêm mới
if (isset($_POST['btn_them'])) {
    $matkhau_macdinh = "123456";
    $matkhau_mahoa = password_hash($matkhau_macdinh, PASSWORD_DEFAULT);
    $u = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
    $ht = mysqli_real_escape_string($conn, trim($_POST['hoten'] ?? ''));
    $s = mysqli_real_escape_string($conn, trim($_POST['sdt'] ?? ''));
    $dc = mysqli_real_escape_string($conn, trim($_POST['diachinha'] ?? ''));
    $p = mysqli_real_escape_string($conn, trim($_POST['phuong'] ?? ''));
    $tp = mysqli_real_escape_string($conn, trim($_POST['thanhpho'] ?? ''));
    $trangthai = "Hoạt động"; // Thêm biến này

    // Sửa câu SQL và bind_param cho đủ 8 tham số
    $sql_add = "INSERT INTO khachhang (username, hoten, trangthaitk, sdt, diachinha, phuong, thanhpho, matkhau) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql_add);
    mysqli_stmt_bind_param($stmt, "ssssssss", $u, $ht, $trangthai, $s, $dc, $p, $tp, $matkhau_mahoa);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Thêm thành công! Mật khẩu mặc định là 123456'); window.location.href='admin.php?page=khachhang';</script>";
        exit;
    } else {
        echo "<script>alert('Lỗi: Tên tài khoản hoặc Số điện thoại đã tồn tại!');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            min-height: 100vh;
        }

        .content {
            padding: 90px 25px 40px;
            margin-left: 0;
            transition: margin-left 0.4s ease;
        }

        /* Khi sidebar mở từ index.php */
        .sidebar.active ~ .content {
            margin-left: 0px;
        }

        h1 { color: #2c3e50; margin-bottom: 20px;  font-weight: bold;}

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.2rem;
            margin-bottom: 1.5rem;
        }

        .input-group {
            display: flex;
            flex-direction: column;
        }

        .input-group label {
            margin-bottom: 0.4rem;
            font-weight: 500;
        }

        .input-group input,
        .input-group select {
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
        cursor:pointer;
    }

        .btn-filter:hover{
            background:#2980b9;
        }

        .btn-lock {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-unlock {
            background: #198754;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-filter, .btn-lock, .btn-unlock {
            display: inline-block;
            margin: 4px;
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

        @media (max-width: 768px) {
            #main-table thead {
            display: none;
        }
            .content {
                padding: 80px 15px 30px;
            }

            .form-row {
                grid-template-columns: 1fr;
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
                padding-left: 45% !important; /* Tạo khoảng trống bên trái cho nhãn */
                text-align: right !important;  /* Đẩy nội dung thực tế sang phải */
                white-space: normal;           /* Cho phép xuống dòng nếu chữ quá dài */
                word-break: break-word;        /* Ngắt từ nếu cần thiết */
                min-height: 45px;
                display: flex;                 /* Sử dụng flex để căn chỉnh */
                align-items: center;
                justify-content: flex-end;     /* Nội dung nằm bên phải */
            }

            td:before {
                position: absolute;
                top: 0;
                left: 15px;
                width: 40%;
                height: 100%;                  /* Chiều cao bằng ô td */
                display: flex;
                align-items: center;           /* Căn nhãn vào giữa theo chiều dọc */
                padding-right: 10px;
                white-space: nowrap;
                font-weight: bold;
                text-align: left;
                content: attr(data-label);    /* Cách chuyên nghiệp để lấy tiêu đề */
            }
        }
    </style>
</head>
<body>

        <h1>Quản lý khách hàng</h1>
    <form class='form-row' method='POST' id="formKhachHang" onsubmit="return validateAll()">
        <div class='input-group'>
            <label>Tên tài khoản</label>
            <input type='text' name='username' id='username' placeholder='Tên tài khoản' required>
        </div>

        <div class='input-group'>
            <label>Họ tên</label>
            <input type='text' name='hoten' id='hoten' placeholder='Họ tên' required>
            <span id="err-hoTen" style="color:red; font-size:12px; display:none;"></span>
        </div>

        <div class='input-group'>
            <label>Số điện thoại</label>
            <input id='sdt' type='text' name='sdt' placeholder='Số điện thoại' required>
            <span id="err-sdt" style="color:red; font-size:12px; display:none;"></span>
        </div>

        <div class='input-group'>
            <label>Tỉnh thành</label>
            <select id='thanhpho' name='thanhpho' required style='padding:5px'>
                <option value=''>Chọn tỉnh thành</option>
            </select>
        </div>

        <div class='input-group'>
            <label>Quận/Huyện/Phường/Xã</label>
            <select id='phuong' name='phuong' required style='padding:5px'>
                <option value=''>Chọn quận/huyện/phường/xã</option>
            </select>
        </div>
        <div class='input-group'>
            <label>Địa chỉ</label>
            <input type='text' name='diachinha' placeholder='41 Võ thị Sáu' required style='padding:5px'>
        </div>

        <button type='submit' name='btn_them'  class="btn-filter" >Thêm</button>
    </form>

        <form method="GET" action="admin.php" class='form-row' onsubmit="event.preventDefault(); locKhachHang();">
            <input type="hidden" name="page" value="khachhang">
            <div class='input-group'>
            <input type="text" name="search" id='input_search'
                placeholder="Nhập số điện thoại hoặc tên tài khoản..." 
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                style="padding: 8px 15px; border: 1px solid #ccc; border-radius: 5px; width: 300px;">
            </div>
            <button type="submit" onclick="locKhachHang()" class="btn-filter">Tìm kiếm</button>
            
            <a href="admin.php?page=khachhang" 
            class="btn-filter" 
            style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center; height: 38px; min-width: 80px; vertical-align: middle;">
            Xóa lọc
            </a>        
        </form>

    
    <?php
    $sql = "SELECT * FROM khachhang";
    $rs = mysqli_query($conn, $sql);
?>
<table id="main-table">
    <thead>
        <tr>
            <th>Tên tài khoản</th>
            <th>Họ tên</th>
            <th>Trạng Thái</th>
            <th>Số điện thoại</th>
            <th>Địa chỉ nhà</th>
            <th>Quận/Huyện/Phường/Xã</th>
            <th>Thành phố</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody id="table-body">
    <?php while ($row = mysqli_fetch_assoc($rs)): 
        $isLocked = ($row['trangthaitk'] == 'Bị khóa');
        $btn_lock_logic = $isLocked
            ? "<a href='admin.php?page=khachhang&action=mo&id={$row['username']}' onclick=\"return confirm('Mở khóa?')\" class='btn btn-unlock'>Mở khóa tài khoản</a>"
            : "<a href='admin.php?page=khachhang&action=khoa&id={$row['username']}' onclick=\"return confirm('Khóa?')\" class='btn btn-lock'>Khóa tài khoản</a>";
    ?>
        <tr>
            <td data-label="Tên tài khoản"><?php echo $row['username']; ?></td>
            <td data-label="Họ tên"><?php echo $row['hoten']; ?></td>
            <td data-label="Trạng thái tài khoản"><?php echo $row['trangthaitk']; ?></td>
            <td data-label="Số điện thoại"><?php echo $row['SDT']; ?></td>
            <td data-label="Địa chỉ nhà"><?php echo $row['diachinha']; ?></td>
            <td data-label="Quận/Huyện"><?php echo $row['phuong']; ?></td>
            <td data-label="Thành phố"><?php echo $row['thanhpho']; ?></td>
            <td data-label="Khóa/Mở"><?php echo $btn_lock_logic; ?></td> 
            <td data-label="Mật khẩu">
                <a href="admin.php?page=khachhang&action=reset&id=<?php echo $row['username']; ?>" onclick="return confirm('Đặt lại mật khẩu?')" class="btn-filter">Đặt lại mật khẩu</a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js"></script>
<script>
        const $ = (id) => document.getElementById(id);

        // 1. Hàm kiểm tra Số điện thoại
        function checkSdt() {
            const input = $("sdt");
            const err = $("err-sdt");
            const regex = /^(032|033|034|035|036|037|038|039|086|096|097|098|070|076|077|078|079|089|090|093|081|082|083|084|085|088|091|094|052|056|058|092)[0-9]{7}$/;
            
            if (!regex.test(input.value.trim())) {
                input.classList.add("input-error");
                err.innerText = "Số điện thoại Việt Nam không hợp lệ (10 số)";
                err.style.display = "block";
                return false;
            }
            input.classList.remove("input-error");
            err.style.display = "none";
            return true;
        }

        // 2. Hàm kiểm tra Họ tên
        function checkHoTen() {
            const input = $("hoten");
            const err = $("err-hoTen");
            const regex = /^[A-Za-zÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂưăạảấầẩẫậắằẳẵặẹẻẽềềểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]+$/;

            if (input.value.trim().length < 2 || !regex.test(input.value.trim())) {
                input.classList.add("input-error");
                err.innerText = "Họ tên không hợp lệ (không chứa số/ký tự đặc biệt)";
                err.style.display = "block";
                return false;
            }
            input.classList.remove("input-error");
            err.style.display = "none";
            return true;
        }

        // 3. Tổng hợp khi nhấn Submit
        function validateAll() {
            const isSdtOk = checkSdt();
            const isTenOk = checkHoTen();
            if (isSdtOk && isTenOk) {
                return confirm("Xác nhận thêm khách hàng?");
            }
            return false;
        }

        // 4. Gán sự kiện Blur
        document.addEventListener("DOMContentLoaded", function() {
            $("sdt").addEventListener("blur", checkSdt);
            $("hoten").addEventListener("blur", checkHoTen);

        });

    // dropdown thành phố. tỉnh thành
    var citis = document.getElementById("thanhpho");
    var districts = document.getElementById("phuong");

    axios.get("https://provinces.open-api.vn/api/?depth=2")
        .then(function (result) {
            renderCity(result.data);
        });

    function renderCity(data) {
        for (const x of data) {
            citis.options[citis.options.length] =
                new Option(x.name, x.name);
        }

        citis.onchange = function () {
            districts.length = 1;

            if (this.value !== "") {
                const result = data.filter(n => n.name === this.value);

                for (const k of result[0].districts) {
                    districts.options[districts.options.length] =
                        new Option(k.name, k.name);
                }
            }
        };
    }
    document.addEventListener("DOMContentLoaded", function() {
});

//lọc khách hàng
function locKhachHang() {
    const searchVal = document.getElementById('input_search').value;
    
    // Gọi API lọc
    fetch('lockhachhang.php?search=' + encodeURIComponent(searchVal))
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('table-body');
            let html = '';

            if (data.length === 0) {
                html = `<tr><td colspan="9" align="center" style="padding:20px; color:red;">Không tìm thấy khách hàng phù hợp</td></tr>`;
            } else {
                data.forEach(row => {
                    const isLocked = (row.trangthaitk === 'Bị khóa');
                    const actionType = isLocked ? 'mo' : 'khoa';
                    const actionText = isLocked ? 'Mở khóa tài khoản' : 'Khóa tài khoản';
                    const btnClass = isLocked ? 'btn-unlock' : 'btn-lock';

                    html += `
                        <tr>
                            <td data-label="Tên tài khoản">${row.username}</td>
                            <td data-label="Họ tên">${row.hoten}</td>
                            <td data-label="Trạng thái tài khoản">${row.trangthaitk}</td>
                            <td data-label="Số điện thoại">${row.SDT}</td>
                            <td data-label="Địa chỉ nhà">${row.diachinha}</td>
                            <td data-label="Quận/Huyện">${row.phuong}</td>
                            <td data-label="Thành phố">${row.thanhpho}</td>
                            <td data-label="Khóa/Mở">
                                <a href="admin.php?page=khachhang&action=${actionType}&id=${row.username}" 
                                   class="btn ${btnClass}" 
                                   onclick="return confirm('Xác nhận?')">${actionText}</a>
                            </td>
                            <td data-label="Mật khẩu">
                                <a href="admin.php?page=khachhang&action=reset&id=${row.username}" class="btn-filter">Đặt lại mật khẩu</a>
                            </td>
                        </tr>`;
                });
            }
            tbody.innerHTML = html;
        })
        .catch(err => {
            console.error("Lỗi:", err);
            alert("Không thể tải dữ liệu !");
        });
}

// Cho phép nhấn Enter để tìm kiếm
document.getElementById('input_search').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
        locKhachHang();
    }
});
</script>


