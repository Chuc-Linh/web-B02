<?php
require_once 'db.php';

if (isset($_GET['mahd'])) {
    $mahd = mysqli_real_escape_string($conn, $_GET['mahd']);
    
    // Truy vấn lấy chi tiết
    $sql = "SELECT ct.masp, sp.tensp, ct.soluongmua, ct.giaban
            FROM chitiethd ct 
            JOIN sanpham sp ON ct.masp = sp.masp 
            WHERE ct.mahd = '$mahd'";
            
    $rs = mysqli_query($conn, $sql);

    if (mysqli_num_rows($rs) > 0) {
        echo "<h3>Chi tiết hóa đơn: <span style='color:red'>$mahd</span></h3>";
        echo "<table>
                <thead>
                    <tr><th>Mã SP</th><th>Tên SP</th><th>Số lượng</th><th>Giá</th><th>Thành tiền</th></tr>
                </thead>
                <tbody>";
        
        $tong = 0;
        while ($row = mysqli_fetch_assoc($rs)) {
            $thanhtien = $row['soluongmua'] * $row['giaban'];
            $tong += $thanhtien;
            echo "<tr>
                    <td>{$row['masp']}</td>
                    <td>{$row['tensp']}</td>
                    <td>{$row['soluongmua']}</td>
                    <td>" . number_format($row['giaban']) . " đ</td>
                    <td>" . number_format($thanhtien) . " đ</td>
                  </tr>";
        }
        echo "</tbody>
              <tfoot>
                <tr><td colspan='4' align='right'><b>Tổng cộng:</b></td><td><b style='color:red'>" . number_format($tong) . " đ</b></td></tr>
              </tfoot>
            </table>";
    } else {
        echo "<p>Hóa đơn này không có sản phẩm nào hoặc không tồn tại.</p>";
    }
}
?>