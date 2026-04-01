<?php
function sanPhamSapHet($conn, $nguong){

    $sql = "
        SELECT sp.masp, sp.tensp,
               COALESCE(nhap.tongnhap,0) - COALESCE(xuat.tongxuat,0) AS tonkho
        FROM sanpham sp

        LEFT JOIN (
            SELECT masp, SUM(soluongnhap) AS tongnhap
            FROM chitietphieunhap ctpn
            join phieunhap
            on ctpn.maphieunhap = phieunhap.maphieunhap
            where trangthaipn = 'Đã hoàn thành'
            GROUP BY masp
        ) nhap ON sp.masp = nhap.masp

        LEFT JOIN (
            SELECT masp, SUM(soluongmua) AS tongxuat
            FROM chitiethd cthd
            join hoadon
            on cthd.mahd = hoadon.mahd
            where trangthai != 'Đã hủy'
            GROUP BY masp
        ) xuat ON sp.masp = xuat.masp

        WHERE (COALESCE(nhap.tongnhap,0) - COALESCE(xuat.tongxuat,0)) <= ?

        ORDER BY tonkho ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $nguong);
    $stmt->execute();

    return $stmt->get_result();
}