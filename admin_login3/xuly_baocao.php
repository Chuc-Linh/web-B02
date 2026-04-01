<?php
require_once 'db.php';

function baoCaoNhapXuat($conn, $tungaytk, $denngaytk){
    $sql = "
        SELECT 
            sp.masp,
            sp.tensp,
            IFNULL(nhap.tongnhap, 0) AS tongnhap,
            IFNULL(xuat.tongxuat, 0) AS tongxuat
        FROM sanpham sp
        -- Truy vấn con tính tổng nhập
        LEFT JOIN (
            SELECT ctpn.masp, SUM(ctpn.soluongnhap) AS tongnhap
            FROM chitietphieunhap ctpn
            JOIN phieunhap pn ON ctpn.maphieunhap = pn.maphieunhap
            WHERE pn.ngaynhap BETWEEN ? AND ?
            GROUP BY ctpn.masp
        ) AS nhap ON sp.masp = nhap.masp
        -- Truy vấn con tính tổng xuất
        LEFT JOIN (
            SELECT cthd.masp, SUM(cthd.soluongmua) AS tongxuat
            FROM chitiethd cthd
            JOIN hoadon hd ON cthd.mahd = hd.mahd
            WHERE hd.ngaydat BETWEEN ? AND ?
            GROUP BY cthd.masp
        ) AS xuat ON sp.masp = xuat.masp
        ORDER BY sp.masp ASC
    ";

    $stmt = $conn->prepare($sql);
    // Lưu ý: bind 4 tham số cho 2 cặp ngày tháng trong subqueries
    $stmt->bind_param("ssss", $tungaytk, $denngaytk, $tungaytk, $denngaytk);
    $stmt->execute();

    return $stmt->get_result();
}
?>