
use  b02_nhahodau;
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
    giavon float not null DEFAULT 0,
    giabandexuat float not null DEFAULT 0,
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
-- 6. Cộng kho khi phiếu nhập 'Đã hoàn thành' từ đầu
DELIMITER $$
CREATE TRIGGER after_insert_chitietpn
AFTER INSERT ON chitietphieunhap
FOR EACH ROW
BEGIN
    DECLARE trangthai_phieu VARCHAR(20);

    SELECT trangthaipn INTO trangthai_phieu
    FROM phieunhap
    WHERE maphieunhap = NEW.maphieunhap;

    IF trangthai_phieu = 'Đã hoàn thành' THEN
        UPDATE sanpham sp
        SET 

            sp.giavon = (sp.soluongtontheolo * sp.giavon + (NEW.gianhap * NEW.soluongnhap)) 
                        / (sp.soluongtontheolo + NEW.soluongnhap),
            sp.soluongtontheolo = sp.soluongtontheolo + NEW.soluongnhap,
            sp.giabandexuat = sp.giavon * (1 + sp.phantramloinhuanmongmuon/100)
        WHERE sp.masp = NEW.masp;
    END IF;
END$$
DELIMITER ;


-- 7. Cộng kho khi cập nhật phiếu nhập sang trạng thái 'Đã hoàn thành' và cập nhật lại giá nếu có thay đổi
DELIMITER $$

DROP TRIGGER IF EXISTS after_update_phieunhap$$

CREATE TRIGGER after_update_phieunhap
AFTER UPDATE ON phieunhap
FOR EACH ROW
BEGIN 
    -- Chỉ thực hiện khi chuyển từ trạng thái khác sang 'Đã hoàn thành'
    IF OLD.trangthaipn <> 'Đã hoàn thành' AND NEW.trangthaipn = 'Đã hoàn thành' THEN

        UPDATE sanpham sp
        JOIN (
            -- Tính toán tổng hợp từ chi tiết phiếu nhập
            SELECT masp, 
                   SUM(soluongnhap) AS tongnhap, 
                   SUM(soluongnhap * gianhap) AS tongtiennhap
            FROM chitietphieunhap
            WHERE maphieunhap = NEW.maphieunhap
            GROUP BY masp
        ) ct ON sp.masp = ct.masp
        SET 
            -- Tính giá vốn mới (Weighted Average)
            sp.giavon = CASE 
                WHEN (sp.soluongtontheolo + ct.tongnhap) > 0 
                THEN (sp.soluongtontheolo * sp.giavon + ct.tongtiennhap) / (sp.soluongtontheolo + ct.tongnhap)
                ELSE sp.giavon 
            END,
            -- Cập nhật số lượng tồn
            sp.soluongtontheolo = sp.soluongtontheolo + ct.tongnhap,
            -- Cập nhật giá đề xuất dựa trên giá vốn VỪA MỚI TÍNH XONG
            sp.giabandexuat = ((sp.soluongtontheolo * sp.giavon + ct.tongtiennhap) / (sp.soluongtontheolo + ct.tongnhap)) * (1 + sp.phantramloinhuanmongmuon/100);
            
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

-- Chèn Loại sản phẩm
INSERT INTO loai (maloai, tenloai, mota) VALUES 
('Jean', 'Balo Jean', 'Cac balo đeo chất liệu bằng Jean'),
('Bạt', 'Balo Bạt', 'Cac balo đeo chất liệu bằng tấm Bạt'),
('Tote', 'Giỏ Tote Bạt', 'Cac giỏ tote chất liệu bằng tấm Bạt'),
('Đeo', 'Túi Đeo Bạt', 'Cac túi đeo chất liệu bằng tấm Bạt'),
('Tất', 'Tất Nhựa', 'Cac đôi tất được làm từ vải nhựa');

-- Chèn Sản phẩm
INSERT INTO sanpham (tensp, hientrang, donvitinh, phantramloinhuanmongmuon, maloai, motasp, hinhanh) VALUES 
('Báo Hồng', 'Hiển Thị', 'Chiếc',  15.5, 'Bạt', 'màu hồng, da chất liệu bằng tấm bạt', 'balobat (4).jpg'),
('Đồ Ngọt', 'Hiển Thị', 'Chiếc',  15.5, 'Bạt', 'màu vàng, nâu chất liệu bằng tấm bạt', 'balobat (1).jpg'),
('Tia Cực Tím', 'Hiển Thị', 'Chiếc',  15.5, 'Bạt', 'màu hồng, cam, xanh dương chất liệu bằng tấm bạt', 'balobat (7).jpg'),
('Trơn Tru Trắng', 'Hiển Thị', 'Chiếc',   15.5, 'Bạt', 'màu trắng chất liệu bằng tấm bạt', 'balobat (10).jpg'),
('Mất Màu', 'Ẩn', 'Chiếc', 20.0, 'Jean', 'màu xanh jean chất liệu bằng jean', 'balo.jpg'),
('Em Xinh', 'Hiển Thị', 'Chiếc', 20.0, 'Jean', 'màu xanh jean chất liệu bằng jean', 'balo1.jpg'),
('Thế Giới', 'Hiển Thị', 'Chiếc',  20.0, 'Jean', 'màu xanh jean chất liệu bằng jean', 'balo3.jpg'),
('Chia Đất', 'Hiển Thị', 'Chiếc',  20.0, 'Jean', 'màu xanh jean chất liệu bằng jean', 'balo5.jpg'),
('Bày Mẹo Tốt', 'Hiển Thị', 'Chiếc', 10.0, 'Tote', 'màu xám, vàng chất liệu bằng tấm bạt', 'balotote (6).jpg'),
('Thìn King', 'Hiển Thị', 'Chiếc', 10.0, 'Tote', 'màu trắng, xanh cùng với hoạ tiết rồng chất liệu bằng tấm bạt', 'balotote (4).jpg'),
('Chợ Phiên', 'Hiển Thị', 'Chiếc',  10.0, 'Tote', 'màu vàng, xám chất liệu bằng tấm bạt', 'balotote (9).jpg'),
('Tốt Dần', 'Hiển Thị', 'Chiếc',10.0, 'Tote', 'màu trắng, đỏ cùng hoạ tiết hổ chất liệu bằng tấm bạt', 'balotote (11).jpg'),
('Đông Xuân', 'Hiển Thị', 'Chiếc', 10.0, 'Đeo', 'màu đỏ, vàng chất liệu bằng tấm bạt ', 'deo (6).jpg'),
('Hồng Hạc', 'Hiển Thị', 'Chiếc', 10.0, 'Đeo', 'màu hồng da chất liệu bằng tấm bạt ', 'deo (2).jpg'),
('Cà Phê', 'Hiển Thị', 'Chiếc',10.0, 'Đeo', 'màu nâu chất liệu bằng tấm bạt ', 'deo (9).jpg'),
('Âm nhạc', 'Hiển Thị', 'Chiếc', 10.0, 'Đeo', 'màu xanh dương đậm trắng chất liệu bằng tấm bạt', 'deo (11).jpg'),
('Ô Ăn Quan', 'Hiển Thị', 'Đôi', 10.0, 'Tất', 'màu xanh lá chuối đậm, vàng cùng hoạ tiết ô ăn quan liệu từ nhựa tái chế', 'oan.webp'),
('Kéo Búa Bao', 'Hiển Thị', 'Đôi',  10.0, 'Tất', 'màu trắng, xanh lá chuối đậm cùng hoạ tiết kéo búa bao chất liệu bằng vải từ nhựa tái chế', 'keobua.png'),
('Lúa Nước Việt Nam', 'Hiển Thị', 'Đôi', 10.0, 'Tất', 'màu trắng, xanh dương, vàng và xanh lá và chất liệu từ nhựa tái chế', 'luanuoc.webp'),
('Đen Thui', 'Hiển Thị', 'Đôi', 10.0, 'Tất', 'màu đen chất liệu từ nhựa tái chế', 'den.jpg');

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
('duong_van_p', 'Dương Văn P', 'Hoạt động', '0844556677', '333 Võ Văn Kiệt', 'Cô Giang', 'Hồ Chí Minh', 'duongp_456'),('tran_minh_01', 'Trần Minh Tâm', 'Hoạt động', '0901112233', '12 Lạch Tray', 'Ngô Quyền', 'Hải Phòng', 'pass_tam123'),
('le_hoa_88', 'Lê Thị Hồng Hoa', 'Hoạt động', '0912223344', '88 Trần Phú', 'Lộc Thọ', 'Nha Trang', 'hoa_hong_88'),
('nguyen_dung_90', 'Nguyễn Tiến Dũng', 'Bị khóa', '0923334455', '90 Quang Trung', '10', 'Gò Vấp', 'dung_secret'),
('vo_hoang_m', 'Võ Hoàng Minh', 'Hoạt động', '0934445566', '156 Hùng Vương', '2', 'Tân An', 'minh_hoang_789'),
('dang_ngoc_a', 'Đặng Ngọc Anh', 'Hoạt động', '0945556677', '23 Lê Lợi', '5', 'Đông Hà', 'anh_ngoc_99'),
('bui_gia_bao', 'Bùi Gia Bảo', 'Hoạt động', '0956667788', '45 Phan Bội Châu', '1', 'Buôn Ma Thuột', 'bao_gia_123'),
('truong_my_l', 'Trương Mỹ Lan', 'Bị khóa', '0967778899', '122 Nguyễn Huệ', 'Bến Nghé', 'Quận 1', 'lan_my_xyz'),
('phan_anh_t', 'Phan Anh Tuấn', 'Hoạt động', '0978889900', '34 Nguyễn Trãi', '3', 'Quận 5', 'tuan_anh_00'),
('doan_thu_h', 'Đoàn Thu Hà', 'Hoạt động', '0989990011', '78 Cách Mạng Tháng 8', '11', 'Quận 3', 'ha_thu_doan'),
('luu_vinh_k', 'Lưu Vĩnh Khang', 'Hoạt động', '0901112244', '56 Nguyễn Văn Cừ', 'An Khánh', 'Cần Thơ', 'khang_vinh_1'),
('cao_thanh_s', 'Cao Thanh Sơn', 'Bị khóa', '0912223355', '19 Trần Hưng Đạo', '1', 'Phan Thiết', 'son_thanh_99'),
('ha_quang_h', 'Hà Quang Hiếu', 'Hoạt động', '0923334466', '210 Điện Biên Phủ', 'Chính Gián', 'Đà Nẵng', 'hieu_quang_ha'),
('vuong_le_q', 'Vương Lệ Quyên', 'Hoạt động', '0934445577', '67 Nguyễn Tất Thành', '13', 'Quận 4', 'quyen_le_88'),
('dinh_trong_n', 'Đinh Trọng Nhân', 'Hoạt động', '0945556688', '89 Võ Thị Sáu', '7', 'Quận 3', 'nhan_trong_dinh'),
('ta_ngoc_d', 'Tạ Ngọc Diệp', 'Hoạt động', '0956667799', '12 Nguyễn Du', 'Bến Thành', 'Quận 1', 'diep_ngoc_ta'),
('ho_hoang_p', 'Hồ Hoàng Phi', 'Bị khóa', '0967778900', '45 Lê Hồng Phong', '4', 'Vũng Tàu', 'phi_hoang_123'),
('mai_thanh_t', 'Mai Thanh Tùng', 'Hoạt động', '0978889011', '33 Xuân Thủy', 'Dịch Vọng', 'Hà Nội', 'tung_thanh_mai'),
('dương_thu_t', 'Dương Thu Thủy', 'Hoạt động', '0989990122', '15 Hồ Tùng Mậu', 'Mai Dịch', 'Hà Nội', 'thuy_thu_duong'),
('quach_gia_h', 'Quách Gia Huy', 'Hoạt động', '0901112255', '77 Hàm Nghi', 'Nguyễn Thái Bình', 'Quận 1', 'huy_gia_quach'),
('lam_tieu_p', 'Lâm Tiểu Phụng', 'Hoạt động', '0912223366', '102 Hải Thượng Lãn Ông', '10', 'Quận 5', 'phung_tieu_lam');

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
('PN15', '2024-11-11','Đã hoàn thành', 'NCC03'),
('PN16', '2025-01-05', 'Đã hoàn thành', 'NCC01'),
('PN17', '2025-01-15', 'Đã hoàn thành', 'NCC02'),
('PN18', '2025-02-10', 'Đã hoàn thành', 'NCC03'),
('PN19', '2025-02-25', 'Đã hoàn thành', 'NCC01'),
('PN20', '2025-03-01', 'Đã hoàn thành', 'NCC02'),
('PN21', '2025-03-15', 'Chưa hoàn thành', 'NCC03'),
('PN22', '2025-04-10', 'Chưa hoàn thành', 'NCC01'),
('PN23', '2025-04-20', 'Đã hoàn thành', 'NCC02'),
('PN24', '2025-05-05', 'Đã hoàn thành', 'NCC03'),
('PN25', '2025-05-20', 'Đã hoàn thành', 'NCC01'),
('PN26', '2025-06-12', 'Đã hoàn thành', 'NCC02'),
('PN27', '2025-06-25', 'Đã hoàn thành', 'NCC03'),
('PN28', '2025-07-08', 'Đã hoàn thành', 'NCC01'),
('PN29', '2025-07-30', 'Đã hoàn thành', 'NCC02'),
('PN30', '2025-08-14', 'Đã hoàn thành', 'NCC03'),
('PN31', '2025-09-05', 'Đã hoàn thành', 'NCC01'),
('PN32', '2025-09-22', 'Đã hoàn thành', 'NCC02'),
('PN33', '2025-10-10', 'Đã hoàn thành', 'NCC03'),
('PN34', '2025-11-15', 'Đã hoàn thành', 'NCC01'),
('PN35', '2025-12-20', 'Đã hoàn thành', 'NCC02');

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
( 800000, 30, 18, 'PN15'),
(2100000, 15, 1, 'PN16'),
(3600000, 10, 2, 'PN17'),
(2100000, 25, 3, 'PN18'),
(3100000, 20, 6, 'PN19'),
(3900000, 15, 9, 'PN20'),
(3900000, 18, 13, 'PN21'),
(850000, 60, 17, 'PN22'),
(2100000, 30, 4, 'PN23'),
(3100000, 15, 7, 'PN24'),
(3900000, 20, 10, 'PN25'),
(850000, 45, 20, 'PN26'),
(3900000, 25, 15, 'PN27'),
(3100000, 20, 8, 'PN28'),
(3900000, 15, 14, 'PN29'),
(850000, 35, 18, 'PN30'),
(2150000, 12, 5, 'PN31'),
(3650000, 8, 11, 'PN32'),
(3150000, 14, 12, 'PN33'),
(860000, 40, 19, 'PN34'),
(2100000, 10, 16, 'PN35');

-- Chèn Hóa đơn
INSERT INTO hoadon (mahd, ngaydat, phuongthucthanhtoan, trangthai, diachihd, phuonghd, thanhphohd, username) VALUES 
('HD04', '2026-01-16', 'bank', 'Dang xu ly', 'Đường 30/4', 'Xuân Khánh', 'Cần Thơ', 'hoangvane'),
('HD05', '2026-03-18', 'cod', 'Da huy', '456 Nguyễn Huệ', '1', 'Đà Lạt', 'tran thịb'),
('HD06', '2026-04-20', 'bank', 'Da giao', '101 Hai Bà Trưng', '5', 'Vũng Tàu', 'pham_thi_d'),
('HD07', '2026-05-21', 'cod', 'Dang xu ly', '404 Cách Mạng Tháng 8', '10', 'Hồ Chí Minh', 'vu_van_g'),
('HD08', '2026-06-22', 'bank', 'Da giao', '505 Kim Mã', 'Ngọc Khánh', 'Hà Nội', 'dang_thi_h'),
('HD09', '2026-07-23', 'bank', 'Dang giao', '606 Láng Hạ', 'Thành Công', 'Hà Nội', 'bui_van_i'),
('HD10', '2026-08-24', 'cod', 'Dang giao', '808 Hùng Vương', '1', 'Huế', 'do_van_l'),
('HD11', '2026-09-25', 'bank', 'Cho xu ly', '909 Nguyễn Văn Linh', 'Tân Phong', 'Hồ Chí Minh', 'truong_thi_m'),
('HD12', '2026-10-26', 'bank', 'Da giao', '111 Bà Triệu', 'Lê Đại Hành', 'Hà Nội', 'phan_van_n'),
('HD13', '2026-11-27', 'cod', 'Da giao', '333 Võ Văn Kiệt', 'Cô Giang', 'Hồ Chí Minh', 'duong_van_p'),
('HD14', '2026-12-28', 'bank', 'Dang xu ly', '202 Lý Tự Trọng', 'Thạch Thang', 'Đà Nẵng', 'hoang_anh_e'),
('HD15', '2027-01-29', 'bank', 'Da giao', 'Số 15, Ngõ 2', 'Dịch Vọng', 'Hà Nội', 'nguyenvana'),
('HD16', '2026-02-01', 'bank', 'Da giao', '12 Lạch Tray', 'Ngô Quyền', 'Hải Phòng', 'tran_minh_01'),
('HD17', '2026-02-10', 'cod', 'Da giao', '88 Trần Phú', 'Lộc Thọ', 'Nha Trang', 'le_hoa_88'),
('HD18', '2026-02-15', 'bank', 'Dang xu ly', '156 Hùng Vương', '2', 'Tân An', 'vo_hoang_m'),
('HD19', '2026-03-05', 'cod', 'Da giao', '23 Lê Lợi', '5', 'Đông Hà', 'dang_ngoc_a'),
('HD20', '2026-03-20', 'bank', 'Da huy', '45 Phan Bội Châu', '1', 'Buôn Ma Thuột', 'bui_gia_bao'),
('HD21', '2026-04-12', 'bank', 'Dang giao', '34 Nguyễn Trãi', '3', 'Quận 5', 'phan_anh_t'),
('HD22', '2026-04-25', 'cod', 'Da giao', '78 Cách Mạng Tháng 8', '11', 'Quận 3', 'doan_thu_h'),
('HD23', '2026-05-10', 'bank', 'Da giao', '56 Nguyễn Văn Cừ', 'An Khánh', 'Cần Thơ', 'luu_vinh_k'),
('HD24', '2026-05-22', 'bank', 'Cho xu ly', '210 Điện Biên Phủ', 'Chính Gián', 'Đà Nẵng', 'ha_quang_h'),
('HD25', '2026-06-05', 'cod', 'Da giao', '67 Nguyễn Tất Thành', '13', 'Quận 4', 'vuong_le_q'),
('HD26', '2026-06-18', 'bank', 'Dang xu ly', '89 Võ Thị Sáu', '7', 'Quận 3', 'dinh_trong_n'),
('HD27', '2026-07-02', 'cod', 'Da giao', '12 Nguyễn Du', 'Bến Thành', 'Quận 1', 'ta_ngoc_d'),
('HD28', '2026-07-20', 'bank', 'Dang giao', '33 Xuân Thủy', 'Dịch Vọng', 'Hà Nội', 'mai_thanh_t'),
('HD29', '2026-08-11', 'cod', 'Da giao', '15 Hồ Tùng Mậu', 'Mai Dịch', 'Hà Nội', 'dương_thu_t'),
('HD30', '2026-08-25', 'bank', 'Da giao', '77 Hàm Nghi', 'Nguyễn Thái Bình', 'Quận 1', 'quach_gia_h'),
('HD31', '2026-09-05', 'bank', 'Cho xu ly', '102 Hải Thượng Lãn Ông', '10', 'Quận 5', 'lam_tieu_p'),
('HD32', '2026-09-15', 'cod', 'Da giao', 'Số 15, Ngõ 2', 'Dịch Vọng', 'Hà Nội', 'nguyenvan'),
('HD33', '2026-10-01', 'bank', 'Dang giao', '452 Lê Lợi', '7', 'TP. Hồ Chí Minh', 'lethib'),
('HD34', '2026-10-15', 'cod', 'Da giao', '78 Hùng Vương', 'Lộc Thọ', 'Khánh Hòa', 'phamthid'),
('HD35', '2026-11-20', 'bank', 'Da giao', 'Đường 30/4', 'Xuân Khánh', 'Cần Thơ', 'hoangvane');

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
( 1000000, 4, 18, 'HD15'),
(3000000, 1, 1, 'HD16'),
(5500000, 1, 2, 'HD17'),
(3000000, 2, 3, 'HD18'),
(4500000, 1, 6, 'HD19'),
(5500000, 2, 9, 'HD20'),
(5500000, 1, 13, 'HD21'),
(1200000, 3, 17, 'HD22'),
(3000000, 1, 4, 'HD23'),
(4500000, 1, 7, 'HD24'),
(5500000, 1, 10, 'HD25'),
(1200000, 5, 20, 'HD26'),
(5500000, 2, 15, 'HD27'),
(4500000, 1, 8, 'HD28'),
(5500000, 1, 14, 'HD29'),
(1200000, 4, 18, 'HD30'),
(3100000, 1, 5, 'HD31'),
(5600000, 1, 11, 'HD32'),
(4600000, 1, 12, 'HD33'),
(1300000, 2, 19, 'HD34'),
(3000000, 1, 16, 'HD35');

