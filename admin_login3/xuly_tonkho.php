<?php
require_once 'db.php';

function tinhTonKhoTheoLoai($conn, $maloai, $ngay) {
    // Truy vấn này lấy ra từng sản phẩm thuộc loại đó 
    // và tính tồn kho cho mỗi sản phẩm bằng Subquery
    $sql = "
        SELECT sp.maloai,
            sp.masp, 
            sp.tensp,
            (
                IFNULL((SELECT SUM(ctpn.soluongnhap) 
                        FROM chitietphieunhap ctpn 
                        JOIN phieunhap pn ON ctpn.maphieunhap = pn.maphieunhap 
                        WHERE ctpn.masp = sp.masp AND pn.ngaynhap <= ?), 0) 
                - 
                IFNULL((SELECT SUM(cthd.soluongmua) 
                        FROM chitiethd cthd 
                        JOIN hoadon hd ON cthd.mahd = hd.mahd 
                        WHERE cthd.masp = sp.masp AND hd.ngaydat <= ?), 0)
            ) AS tonkho
        FROM sanpham sp
        WHERE (? = '' OR sp.maloai = ?)
    ";

    $stmt = $conn->prepare($sql);
    // Truyền tham số: $ngay (cho nhập), $ngay (cho xuất), $maloai (lọc loại)
    $stmt->bind_param("ssss", $ngay, $ngay, $maloai, $maloai);
    $stmt->execute();
    
    // Trả về đối tượng kết quả để file giao diện dùng được fetch_assoc()
    return $stmt->get_result();
}
?>