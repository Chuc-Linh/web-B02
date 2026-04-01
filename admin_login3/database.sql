
USE b02_nhahodau;
SET NAMES 'utf8mb4';

-- 1. Bảng loai
CREATE TABLE loai(
    maloai varchar(20) primary key not null,
    tenloai varchar(20) not null unique,
    mota varchar(225) not null
);

-- 2. Bảng sanpham
CREATE TABLE sanpham(
    masp int auto_increment primary key not null,
    tensp varchar(225) not null,
    hientrang varchar(20) not null,
    donvitinh varchar(20) not null,
    motasp varchar(225) not null,
    soluongtontheolo int not null DEFAULT 0,
    hinhanh varchar(225) not null,
    phantramloinhuanmongmuon float not null check(phantramloinhuanmongmuon > 0),
    giabandexuat float not null check(giabandexuat > 0),
    maloai varchar(20),
    foreign key (maloai) references loai(maloai)
);

-- 3. Bảng khachhang
CREATE TABLE khachhang(
    username varchar(50) primary key not null unique,
    hoten varchar(50) not null,
    trangthaitk varchar(20) not null,
    SDT varchar (10) not null,
    diachinha varchar(225) not null,
    phuong varchar(255) not null,
    thanhpho varchar(255) not null,
    matkhau varchar(225) not null
);

-- 4. Bảng hoadon
CREATE TABLE hoadon (
    mahd VARCHAR(20) PRIMARY KEY NOT NULL,
    ngaydat DATE DEFAULT (CURRENT_DATE), 
    phuongthucthanhtoan VARCHAR(50) NOT NULL,
    trangthai VARCHAR(50) NOT NULL,
    diachihd VARCHAR(255) NOT NULL, 
    phuonghd VARCHAR(255) NOT NULL,
    thanhtienhd float not null default 0, -- Thêm mặc định 0 để tránh lỗi tính toán ban đầu
    thanhphohd VARCHAR(255) NOT NULL,
    username VARCHAR(50), 
    FOREIGN KEY (username) REFERENCES khachhang(username)
);

-- 5. Bảng chitiethd
CREATE TABLE chitiethd (
    giaban FLOAT NOT NULL CHECK (giaban > 0), 
    soluongmua INT NOT NULL CHECK (soluongmua > 0),
    Thanhtien FLOAT GENERATED ALWAYS AS (giaban * soluongmua) STORED,
    masp int not null,
    mahd varchar(20),
    FOREIGN KEY (masp) REFERENCES sanpham(masp),
    foreign key(mahd) references hoadon(mahd)
);

-- 6. Bảng nhacungcap
CREATE TABLE nhacungcap(
    maNCC varchar(20) primary key not null,
    tenNCC varchar(225) not null
);

-- 7. Bảng phieunhap
CREATE TABLE phieunhap(
    maphieunhap varchar(20) primary key not null,
    ngaynhap date default (current_date),
    tongtienpn float not null default 0 check (tongtienpn >=0),
    trangthaipn varchar(20) not null,
    maNCC varchar(20),
    foreign key(maNCC) references nhacungcap(maNCC)
);

-- 8. Bảng chitietphieunhap
CREATE TABLE chitietphieunhap(
    gianhap float not null check(gianhap>0),
    soluongnhap int not null check(soluongnhap>0),
    tongtien float generated always as(gianhap*soluongnhap) stored,
    masp int not null,
    maphieunhap varchar(20),
    foreign key (masp) references sanpham(masp),
    foreign key (maphieunhap) references phieunhap(maphieunhap)
);
-- Trigger liên quan đến Hóa đơn (Bán hàng)
DELIMITER $$

-- 1. Kiểm tra tồn kho trước khi cho phép mua
DELIMITER $$

CREATE TRIGGER before_insert_chitiethd
BEFORE INSERT ON chitiethd
FOR EACH ROW
BEGIN
    DECLARE tongton INT;

    -- Lấy tồn kho (khóa dòng tránh race condition)
    SELECT soluongtontheolo INTO tongton
    FROM sanpham
    WHERE masp = NEW.masp
    FOR UPDATE;

    IF tongton IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Sản phẩm không tồn tại';
    END IF;

    IF tongton < NEW.soluongmua THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Không đủ tồn kho';
    END IF;

END$$

DELIMITER ;

-- 2. Trừ kho khi in hóa đơn thành công và tính thành tiền hóa đơn luôn
DELIMITER $$

CREATE TRIGGER after_insert_chitiethd
AFTER INSERT ON chitiethd
FOR EACH ROW
BEGIN
    UPDATE sanpham
    SET soluongtontheolo = soluongtontheolo - NEW.soluongmua
    WHERE masp = NEW.masp;

    UPDATE hoadon
    SET thanhtienhd = (SELECT COALESCE(SUM(soluongmua * giaban), 0) FROM chitiethd WHERE mahd = NEW.mahd)
    WHERE mahd = NEW.mahd;
END$$

DELIMITER ;

-- 3. Hoàn kho khi trạng thái hóa đơn là 'Đã hủy'
DELIMITER $$

CREATE TRIGGER after_update_hoadon
AFTER UPDATE ON hoadon
FOR EACH ROW
BEGIN
    IF OLD.trangthai <> 'Đã hủy'
       AND NEW.trangthai = 'Đã hủy' THEN

        UPDATE sanpham sp
        JOIN chitiethd ct
            ON sp.masp = ct.masp
        SET sp.soluongtontheolo = sp.soluongtontheolo + ct.soluongmua
        WHERE ct.mahd = NEW.mahd;

    END IF;
END$$

DELIMITER ;

-- Trigger liên quan đến Phiếu nhập (Nhập hàng)

-- 5. Kiểm tra giá và số lượng nhập
delimiter $$
CREATE TRIGGER before_insertchitietphieunhap BEFORE INSERT ON chitietphieunhap FOR EACH ROW
BEGIN
    DECLARE trangthai_phieu varchar(20);
    SELECT trangthaipn INTO trangthai_phieu FROM phieunhap WHERE maphieunhap = NEW.maphieunhap;
    
    IF NEW.soluongnhap <= 0 OR NEW.gianhap <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Giá nhập và số lượng phải > 0';
    END IF;
END$$
DELIMITER ;
-- 6. Cộng kho khi cập nhật phiếu nhập nếu phiếu nhập 'Đã hoàn thành' từ đầu
DELIMITER $$

CREATE TRIGGER after_insert_chitietpn
AFTER INSERT ON chitietphieunhap
FOR EACH ROW
BEGIN
    DECLARE trangthai_phieu VARCHAR(20);

    SELECT trangthaipn
    INTO trangthai_phieu
    FROM phieunhap
    WHERE maphieunhap = NEW.maphieunhap;

    IF trangthai_phieu = 'Đã hoàn thành' THEN
        UPDATE sanpham
        SET soluongtontheolo = soluongtontheolo + NEW.soluongnhap
        WHERE masp = NEW.masp;
    END IF;

END$$

DELIMITER ;


-- 7. Cộng kho khi cập nhật phiếu nhập sang trạng thái 'Đã hoàn thành' và cập nhật lại giá nếu có thay đổi
DELIMITER $$

CREATE TRIGGER after_update_phieunhap
AFTER UPDATE ON phieunhap
FOR EACH ROW
BEGIN 
    IF OLD.trangthaipn <> 'Đã hoàn thành' AND
NEW.trangthaipn = 'Đã hoàn thành' THEN

        UPDATE sanpham sp
        JOIN (
            SELECT masp,
                   SUM(soluongnhap) AS tongnhap,
                   SUM(soluongnhap * gianhap) AS tongtiennhap
            FROM chitietphieunhap
            WHERE maphieunhap = NEW.maphieunhap
            GROUP BY masp
        ) ct ON sp.masp = ct.masp
        SET 
            sp.giabandexuat =
                (sp.soluongtontheolo * sp.giabandexuat + ct.tongtiennhap)
                /
                (sp.soluongtontheolo + ct.tongnhap),
            sp.soluongtontheolo =
                sp.soluongtontheolo + ct.tongnhap;

    END IF;
END$$

DELIMITER ;

-- 7.1. cập nhật tongtien trong phieunhap
DELIMITER $$
create trigger after_insertchitietphieunhap after insert on chitietphieunhap for each row
begin
	update phieunhap
	SET phieunhap.tongtienpn = (SELECT COALESCE(SUM(soluongnhap * gianhap), 0) FROM chitietphieunhap WHERE maphieunhap = NEW.maphieunhap)
   	WHERE maphieunhap = NEW.maphieunhap;
end$$
DELIMITER ;

-- 7.2 nếu có sửa gì thì giá cũng đổi thay theo luôn
DELIMITER $$

CREATE TRIGGER after_update_chitietphieunhap
AFTER UPDATE ON chitietphieunhap
FOR EACH ROW
BEGIN
    UPDATE phieunhap
    SET tongtienpn = (
        SELECT COALESCE(SUM(soluongnhap * gianhap), 0)
        FROM chitietphieunhap
        WHERE maphieunhap = NEW.maphieunhap
    )
    WHERE maphieunhap = NEW.maphieunhap;
END$$

DELIMITER ;

-- 9. chặn không cho update phieunhap nếu đã hoàn thành
DELIMITER $$
CREATE TRIGGER before_update_chitietphieunhap BEFORE UPDATE ON chitietphieunhap FOR EACH ROW
BEGIN
    DECLARE tt varchar(20);
    SELECT trangthaipn INTO tt FROM phieunhap WHERE maphieunhap = OLD.maphieunhap;
    IF tt = 'Đã hoàn thành' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Không thể sửa chi tiết phiếu đã hoàn thành';
    END IF;
END$$
DELIMITER ;
-- Chèn Loại sản phẩm
INSERT INTO loai (maloai, tenloai, mota) VALUES 
('Jean', 'Balo Jean', 'Cac balo đeo chất liệu bằng Jean'),
('Bạt', 'Balo Bạt', 'Cac balo đeo chất liệu bằng tấm Bạt'),
('Tote', 'Giỏ Tote Bạt', 'Cac giỏ tote chất liệu bằng tấm Bạt'),
('Đeo', 'Túi Đeo Bạt', 'Cac túi đeo chất liệu bằng tấm Bạt'),
('Tất', 'Tất Nhựa', 'Cac đôi tất được làm từ vải nhựa');

-- Chèn Sản phẩm
INSERT INTO sanpham (tensp, hientrang, donvitinh, giabandexuat, phantramloinhuanmongmuon, maloai, motasp, hinhanh) VALUES 
('Báo Hồng', 'Hiển Thị', 'Chiếc',  2500000, 15.5, 'Bạt', 'màu hồng, da chất liệu bằng tấm bạt', 'balobat (4).jpg'),
('Đồ Ngọt', 'Hiển Thị', 'Chiếc', 2500000, 15.5, 'Bạt', 'màu vàng, nâu chất liệu bằng tấm bạt', 'balobat (1).jpg'),
('Tia Cực Tím', 'Hiển Thị', 'Chiếc', 2500000, 15.5, 'Bạt', 'màu hồng, cam, xanh dương chất liệu bằng tấm bạt', 'balobat (7).jpg'),
('Trơn Tru Trắng', 'Hiển Thị', 'Chiếc',  2500000, 15.5, 'Bạt', 'màu trắng chất liệu bằng tấm bạt', 'balobat (10).jpg'),
('Mất Màu', 'Ẩn', 'Chiếc', 4000000, 20.0, 'Jean', 'màu xanh jean chất liệu bằng jean', 'balo.jpg'),
('Em Xinh', 'Hiển Thị', 'Chiếc',  4000000, 20.0, 'Jean', 'màu xanh jean chất liệu bằng jean', 'balo1.jpg'),
('Thế Giới', 'Hiển Thị', 'Chiếc',  4000000, 20.0, 'Jean', 'màu xanh jean chất liệu bằng jean', 'balo3.jpg'),
('Chia Đất', 'Hiển Thị', 'Chiếc',  4000000, 20.0, 'Jean', 'màu xanh jean chất liệu bằng jean', 'balo5.jpg'),
('Bày Mẹo Tốt', 'Hiển Thị', 'Chiếc', 5000000, 10.0, 'Tote', 'màu xám, vàng chất liệu bằng tấm bạt', 'balotote (6).jpg'),
('Thìn King', 'Hiển Thị', 'Chiếc', 5000000, 10.0, 'Tote', 'màu trắng, xanh cùng với hoạ tiết rồng chất liệu bằng tấm bạt', 'balotote (4).jpg'),
('Chợ Phiên', 'Hiển Thị', 'Chiếc',  5000000, 10.0, 'Tote', 'màu vàng, xám chất liệu bằng tấm bạt', 'balotote (9).jpg'),
('Tốt Dần', 'Hiển Thị', 'Chiếc', 5000000, 10.0, 'Tote', 'màu trắng, đỏ cùng hoạ tiết hổ chất liệu bằng tấm bạt', 'balotote (11).jpg'),
('Đông Xuân', 'Hiển Thị', 'Chiếc', 5000000, 10.0, 'Đeo', 'màu đỏ, vàng chất liệu bằng tấm bạt ', 'deo (6).jpg'),
('Hồng Hạc', 'Hiển Thị', 'Chiếc', 5000000, 10.0, 'Đeo', 'màu hồng da chất liệu bằng tấm bạt ', 'deo (2).jpg'),
('Cà Phê', 'Hiển Thị', 'Chiếc',5000000, 10.0, 'Đeo', 'màu nâu chất liệu bằng tấm bạt ', 'deo (9).jpg'),
('Âm nhạc', 'Hiển Thị', 'Chiếc', 5000000, 10.0, 'Đeo', 'màu xanh dương đậm trắng chất liệu bằng tấm bạt', 'deo (11).jpg'),
('Ô Ăn Quan', 'Hiển Thị', 'Đôi', 1000000, 10.0, 'Tất', 'màu xanh lá chuối đậm, vàng cùng hoạ tiết ô ăn quan liệu từ nhựa tái chế', 'oan.webp'),
('Kéo Búa Bao', 'Hiển Thị', 'Đôi',  1000000, 10.0, 'Tất', 'màu trắng, xanh lá chuối đậm cùng hoạ tiết kéo búa bao chất liệu bằng vải từ nhựa tái chế', 'keobua.png'),
('Lúa Nước Việt Nam', 'Hiển Thị', 'Đôi',  1000000, 10.0, 'Tất', 'màu trắng, xanh dương, vàng và xanh lá và chất liệu từ nhựa tái chế', 'luanuoc.webp'),
('Đen Thui', 'Hiển Thị', 'Đôi', 1000000, 10.0, 'Tất', 'màu đen chất liệu từ nhựa tái chế', 'den.jpg');

-- Chèn Khách hàng
INSERT INTO khachhang (username, hoten, trangthaitk, SDT, diachinha, phuong, thanhpho, matkhau) VALUES 
('nguyenvan', 'Hoàng Văn Hà', 'Hoạt động', '0912345671','Số 15, Ngõ 2', ' Dịch Vọng',  'Hà Nội','pa$$w0rd123'),
('lethib','Nguyễn Văn Kiện', 'Hoạt động',  '0988777662','452 Lê Lợi', ' 7', 'TP. Hồ Chí Minh', 'secret2024'),
('tranvanc','Nguyễn Bá Khang', 'Bị khóa','0905123453', 'K12/45 Hoàng Diệu', ' Phước Ninh',  'Đà Nẵng','hallo@2026'),
('phamthid', 'Lâm Duy Chúc Linh', 'Hoạt động','0944111224', '78 Hùng Vương', 'Lộc Thọ', 'Khánh Hòa','flower99'),
('hoangvane','Trần Văn Minh', 'Hoạt động', '0933555665', 'Đường 30/4', 'Xuân Khánh', 'Cần Thơ', 'dragon_king'),
('nguyenvana', 'Nguyễn Văn A', 'Hoạt động', '0901234567', '123 Lê Lợi', 'Bến Nghé', 'Hồ Chí Minh', 'pw12345'),
('tran thịb', 'Trần Thị B', 'Hoạt động', '0912345678', '456 Nguyễn Huệ', '1', 'Đà Lạt', 'pass2024'),
('le_van_c', 'Lê Văn C', 'Bị khóa', '0987654321', '789 Trần Hưng Đạo', 'An Hải Bắc', 'Đà Nẵng', 'secret789'),
('pham_thi_d', 'Phạm Thị D', 'Hoạt động', '0345678901', '101 Hai Bà Trưng', '5', 'Vũng Tàu', 'd_pham_123'),
('hoang_anh_e', 'Hoàng Văn E', 'Hoạt động', '0765432109', '202 Lý Tự Trọng', 'Thạch Thang', 'Đà Nẵng', 'anh_e_secure'),
('ngo_thi_f', 'Ngô Thị F', 'Bị khóa', '0933445566', '303 Phan Chu Trinh', 'Vạn Thạnh', 'Nha Trang', 'f_ngo_2026'),
('vu_van_g', 'Vũ Văn G', 'Hoạt động', '0944556677', '404 Cách Mạng Tháng 8', '10', 'Hồ Chí Minh', 'vuvang11'),
('dang_thi_h', 'Đặng Thị H', 'Hoạt động', '0955667788', '505 Kim Mã', 'Ngọc Khánh', 'Hà Nội', 'dangh_pw'),
('bui_van_i', 'Bùi Văn I', 'Hoạt động', '0966778899', '606 Láng Hạ', 'Thành Công', 'Hà Nội', 'buivan_i9'),
('ly_thi_k', 'Lý Thị K', 'Bị khóa', '0977889900', '707 Trần Phú', '5', 'Cần Thơ', 'lythi_k_pass'),
('do_van_l', 'Đỗ Văn L', 'Hoạt động', '0922334455', '808 Hùng Vương', ' 1', 'Huế', 'dovanl_2026'),
('truong_thi_m', 'Trương Thị M', 'Hoạt động', '0811223344', '909 Nguyễn Văn Linh', 'Tân Phong', 'Hồ Chí Minh', 'm_truong_xyz'),
('phan_van_n', 'Phan Văn N', 'Hoạt động', '0822334455', '111 Bà Triệu', 'Lê Đại Hành', 'Hà Nội', 'phann_123'),
('trinh_thi_o', 'Trịnh Thị O', 'Bị khóa', '0833445566', '222 Lê Duẩn', 'Thạch Thang', 'Đà Nẵng', 'trinho_secure'),
('duong_van_p', 'Dương Văn P', 'Hoạt động', '0844556677', '333 Võ Văn Kiệt', 'Cô Giang', 'Hồ Chí Minh', 'duongp_456');

-- Chèn Nhà cung cấp
INSERT INTO nhacungcap (maNCC, tenNCC) VALUES 
('NCC01', 'Cong ty TNHH Apple Viet Nam'),
('NCC02', 'Nha phan phoi Digiworld'),
('NCC03', 'Samsung Electronics');

-- Chèn Phiếu nhập
INSERT INTO phieunhap (maphieunhap, ngaynhap,trangthaipn, maNCC) VALUES 
('PN01', '2023-10-01','Chưa hoàn thành', 'NCC01'),
('PN02', '2023-10-05', 'Chưa hoàn thành','NCC02'),
('PN03', '2023-11-10','Chưa hoàn thành', 'NCC03'),
('PN04', '2023-12-15','Đã hoàn thành', 'NCC01'),
('PN05', '2024-01-20', 'Đã hoàn thành','NCC02'),
('PN06', '2024-02-14','Đã hoàn thành', 'NCC03'),
('PN07', '2024-03-05','Đã hoàn thành', 'NCC01'),
('PN08', '2024-04-12','Đã hoàn thành', 'NCC02'),
('PN09', '2024-05-25','Đã hoàn thành', 'NCC03'),
('PN10', '2024-06-30','Đã hoàn thành', 'NCC01'),
('PN11', '2024-07-08','Đã hoàn thành', 'NCC02'),
('PN12', '2024-08-19','Đã hoàn thành', 'NCC03'),
('PN13', '2024-09-22','Đã hoàn thành', 'NCC01'),
('PN14', '2024-10-05','Đã hoàn thành', 'NCC02'),
('PN15', '2024-11-11','Đã hoàn thành', 'NCC03');

-- Chèn Chi tiết phiếu nhập (Sẽ kích hoạt Trigger cộng kho)
INSERT INTO chitietphieunhap (gianhap, soluongnhap, masp, maphieunhap) VALUES 
( 2000000, 10, 1, 'PN01'),
( 3500000, 5, 2, 'PN02'),
( 2000000, 20, 3, 'PN03'),
( 3000000, 15, 6, 'PN04'),
( 3800000, 10, 9, 'PN05'),
( 3800000, 12, 13, 'PN06'),
( 800000, 50, 17, 'PN07'),
( 2000000, 25, 4, 'PN08'),
( 3000000, 10, 7, 'PN09'),
( 3800000, 15, 10, 'PN10'),
( 800000, 40, 20, 'PN11'),
( 3800000, 20, 15, 'PN12'),
( 3000000, 15, 8, 'PN13'),
( 3800000, 10, 14, 'PN14'),
( 800000, 30, 18, 'PN15');

-- Chèn Hóa đơn
INSERT INTO hoadon (mahd, ngaydat, phuongthucthanhtoan, trangthai, diachihd, phuonghd, thanhphohd, username) VALUES 
('HD04', '2026-01-16', 'Chuyen khoan', 'Dang xu ly', 'Đường 30/4', 'Xuân Khánh', 'Cần Thơ', 'hoangvane'),
('HD05', '2026-03-18', 'Tien mat', 'Da huy', '456 Nguyễn Huệ', '1', 'Đà Lạt', 'tran thịb'),
('HD06', '2026-04-20', 'Chuyen khoan', 'Da giao', '101 Hai Bà Trưng', '5', 'Vũng Tàu', 'pham_thi_d'),
('HD07', '2026-05-21', 'Tien mat', 'Dang xu ly', '404 Cách Mạng Tháng 8', '10', 'Hồ Chí Minh', 'vu_van_g'),
('HD08', '2026-06-22', 'Chuyen khoan', 'Da giao', '505 Kim Mã', 'Ngọc Khánh', 'Hà Nội', 'dang_thi_h'),
('HD09', '2026-07-23', 'Chuyen khoan', 'Dang giao', '606 Láng Hạ', 'Thành Công', 'Hà Nội', 'bui_van_i'),
('HD10', '2026-08-24', 'Tien mat', 'Dang giao', '808 Hùng Vương', '1', 'Huế', 'do_van_l'),
('HD11', '2026-09-25', 'Chuyen khoan', 'Cho xu ly', '909 Nguyễn Văn Linh', 'Tân Phong', 'Hồ Chí Minh', 'truong_thi_m'),
('HD12', '2026-10-26', 'Chuyen khoan', 'Da giao', '111 Bà Triệu', 'Lê Đại Hành', 'Hà Nội', 'phan_van_n'),
('HD13', '2026-11-27', 'Tien mat', 'Da giao', '333 Võ Văn Kiệt', 'Cô Giang', 'Hồ Chí Minh', 'duong_van_p'),
('HD14', '2026-12-28', 'Chuyen khoan', 'Dang xu ly', '202 Lý Tự Trọng', 'Thạch Thang', 'Đà Nẵng', 'hoang_anh_e'),
('HD15', '2027-01-29', 'Chuyen khoan', 'Da giao', 'Số 15, Ngõ 2', 'Dịch Vọng', 'Hà Nội', 'nguyenvana');

-- Chèn Chi tiết hóa đơn (Sẽ kích hoạt Trigger kiểm tra kho và tính tổng tiền HD)
INSERT INTO chitiethd (giaban, soluongmua, masp, mahd) VALUES 
( 5000000, 1, 9, 'HD04'),
( 5000000, 2, 13, 'HD05'),
( 1000000, 5, 17, 'HD06'),
( 2500000, 2, 4, 'HD07'),
( 8000000, 2, 8, 'HD07'),
( 5000000, 2, 13, 'HD08'),
( 4000000, 1, 7, 'HD08'),
( 5000000, 2, 13, 'HD09'),
( 4000000, 1, 7, 'HD09'),
( 4000000, 1, 7, 'HD10'),
( 5000000, 1, 10, 'HD11'),
( 1000000, 10, 20, 'HD12'),
( 5000000, 2, 15, 'HD13'),
( 4000000, 1, 8, 'HD14'),
( 5000000, 1, 14, 'HD15'),
( 1000000, 4, 18, 'HD15');

