-- 1. KHỞI TẠO DATABASE
-- =============================================
DROP DATABASE IF EXISTS trothue;
CREATE DATABASE trothue CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trothue;

-- 2. TẠO CÁC BẢNG DANH MỤC (Chạy trước để làm khóa ngoại)
-- =============================================

-- Bảng Tỉnh / Thành phố
CREATE TABLE tinh_thanh (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_tinh VARCHAR(100) NOT NULL
);

-- Bảng Quận / Huyện (Thuộc Tỉnh)
CREATE TABLE quan_huyen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_quan VARCHAR(100) NOT NULL,
    tinh_thanh_id INT NOT NULL,
    FOREIGN KEY (tinh_thanh_id) REFERENCES tinh_thanh(id) ON DELETE CASCADE
);

-- Bảng Loại phòng (Phòng trọ, Chung cư, Nhà nguyên căn...)
CREATE TABLE loai_phong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_loai VARCHAR(100) NOT NULL
);

-- Bảng Người dùng
CREATE TABLE nguoidung (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mat_khau VARCHAR(255) NOT NULL, -- Lưu hash password
    sdt VARCHAR(20),
    avatar VARCHAR(255) DEFAULT 'default-user.png',
    -- Phân quyền: admin (quản trị), chu_tro (đăng bài), nguoi_thue (xem/đánh giá)
    vai_tro ENUM('admin', 'chu_tro', 'nguoi_thue') DEFAULT 'nguoi_thue',
    trang_thai TINYINT(1) DEFAULT 1, -- 1: Hoạt động, 0: Bị khóa
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. TẠO BẢNG CHÍNH: PHÒNG TRỌ
-- =============================================
CREATE TABLE phongtro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tieu_de VARCHAR(200) NOT NULL,
    
    -- Liên kết khóa ngoại
    chu_tro_id INT NOT NULL,
    loai_phong_id INT NOT NULL,
    quan_huyen_id INT NOT NULL, -- Để lọc theo quận
    
    dia_chi_cu_the VARCHAR(255) NOT NULL, -- Số nhà, tên đường
    gia_thue DECIMAL(12,0) NOT NULL,
    dien_tich INT NOT NULL, -- m2
    mo_ta TEXT,
    
    -- ẢNH: Lưu dạng JSON ["img1.jpg", "img2.jpg"]
    anh_phong JSON, 
    
    -- TIỆN ÍCH (Có/Không)
    wifi BOOLEAN DEFAULT 0,
    may_lanh BOOLEAN DEFAULT 0,
    tu_lanh BOOLEAN DEFAULT 0,
    wc_rieng BOOLEAN DEFAULT 0,
    may_giat BOOLEAN DEFAULT 0,
    gio_tu_do BOOLEAN DEFAULT 1,
    
    -- QUẢN LÝ
    luot_xem INT DEFAULT 0,
    trang_thai ENUM('cho_duyet', 'da_duyet', 'da_thue', 'an') DEFAULT 'cho_duyet',
    ngay_dang DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Ràng buộc khóa ngoại
    FOREIGN KEY (chu_tro_id) REFERENCES nguoidung(id) ON DELETE CASCADE,
    FOREIGN KEY (loai_phong_id) REFERENCES loai_phong(id),
    FOREIGN KEY (quan_huyen_id) REFERENCES quan_huyen(id)
);

-- 4. TẠO BẢNG ĐÁNH GIÁ (REVIEW)
-- =============================================
CREATE TABLE danhgia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nguoi_dung_id INT NOT NULL,
    phong_tro_id INT NOT NULL,
    so_sao TINYINT NOT NULL CHECK (so_sao BETWEEN 1 AND 5),
    noi_dung TEXT,
    ngay_danh_gia DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoidung(id) ON DELETE CASCADE,
    FOREIGN KEY (phong_tro_id) REFERENCES phongtro(id) ON DELETE CASCADE,
    
    -- Một người chỉ được đánh giá 1 phòng 1 lần
    UNIQUE KEY unique_user_room (nguoi_dung_id, phong_tro_id)
);

-- 5. THÊM DỮ LIỆU MẪU (SEED DATA) - Để test ngay
-- =============================================

-- A. Thêm Tỉnh & Quận
INSERT INTO tinh_thanh (id, ten_tinh) VALUES (1, 'Đà Nẵng'), (2, 'Hà Nội'), (3, 'TP.HCM');

-- Thêm quận cho Đà Nẵng (ID=1)
INSERT INTO quan_huyen (ten_quan, tinh_thanh_id) VALUES 
('Quận Liên Chiểu', 1), ('Quận Hải Châu', 1), ('Quận Thanh Khê', 1), ('Quận Sơn Trà', 1);

-- B. Thêm Loại phòng
INSERT INTO loai_phong (ten_loai) VALUES ('Phòng trọ giá rẻ'), ('Căn hộ mini'), ('Nhà nguyên căn'), ('Ở ghép');

-- C. Thêm Người dùng (Mật khẩu demo: 123456 -> Hash bcrypt)
-- Lưu ý: Trong code PHP thực tế phải dùng password_hash('123456', PASSWORD_DEFAULT)
INSERT INTO nguoidung (ho_ten, email, mat_khau, sdt, vai_tro) VALUES 
('Admin Hệ Thống', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0999888999', 'admin'),
('Chủ Trọ Tuấn', 'chutro@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0905123456', 'chu_tro'),
('Sinh Viên Nam', 'sinhvien@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0912345678', 'nguoi_thue');

-- D. Thêm Phòng trọ mẫu
-- Phòng 1: Của chủ trọ Tuấn (id=2), Loại Căn hộ mini (id=2), Quận Liên Chiểu (id=1)
INSERT INTO phongtro 
(tieu_de, chu_tro_id, loai_phong_id, quan_huyen_id, dia_chi_cu_the, gia_thue, dien_tich, anh_phong, wifi, may_lanh, wc_rieng, trang_thai) 
VALUES 
(
    'Căn hộ mini full nội thất gần Đại học Bách Khoa', 
    2, 2, 1, 
    '54 Nguyễn Lương Bằng', 
    3500000, 30, 
    '["p1_main.jpg", "p1_wc.jpg", "p1_bep.jpg"]', -- JSON ảnh
    1, 1, 1, -- Có Wifi, ML, WC
    'da_duyet'
);

-- Phòng 2: Của chủ trọ Tuấn (id=2), Loại Phòng trọ (id=1), Quận Thanh Khê (id=3)
INSERT INTO phongtro 
(tieu_de, chu_tro_id, loai_phong_id, quan_huyen_id, dia_chi_cu_the, gia_thue, dien_tich, anh_phong, wifi, gio_tu_do, trang_thai) 
VALUES 
(
    'Phòng trọ giá rẻ cho sinh viên', 
    2, 1, 3, 
    'K123 Điện Biên Phủ', 
    1200000, 15, 
    '["p2_main.jpg"]', 
    1, 1, -- Có Wifi, Giờ tự do
    'da_duyet'
);

-- E. Thêm Đánh giá mẫu
-- Sinh viên Nam (id=3) đánh giá phòng 1 (id=1)
INSERT INTO danhgia (nguoi_dung_id, phong_tro_id, so_sao, noi_dung) 
VALUES (3, 1, 5, 'Phòng rất đẹp, chủ trọ dễ tính, an ninh tốt.');
-- BẢNG BÁO CÁO VI PHẠM
CREATE TABLE baocao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nguoi_bao_cao_id INT NOT NULL,  -- Ai là người báo cáo?
    bai_viet_id INT NOT NULL,       -- Bài viết nào bị báo cáo?
    
    -- Lý do báo cáo (Lừa đảo, Tin ảo, Đã thuê, Trùng lặp...)
    ly_do VARCHAR(255) NOT NULL,
    
    -- Trạng thái xử lý của Admin
    trang_thai ENUM('cho_xu_ly', 'da_xu_ly', 'bo_qua') DEFAULT 'cho_xu_ly',
    
    ngay_bao_cao DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Khóa ngoại:
    -- Nếu User bị xóa -> Xóa báo cáo của họ
    -- Nếu Bài viết bị xóa -> Xóa báo cáo liên quan
    FOREIGN KEY (nguoi_bao_cao_id) REFERENCES nguoidung(id) ON DELETE CASCADE,
    FOREIGN KEY (bai_viet_id) REFERENCES phongtro(id) ON DELETE CASCADE
);]
ALTER TABLE nguoidung 
ADD COLUMN so_du DECIMAL(15, 0) DEFAULT 0 AFTER sdt;

-- Tặng trước 100k cho Admin và Chủ trọ để test
UPDATE nguoidung SET so_du = 100000 WHERE vai_tro IN ('admin', 'chu_tro');


CREATE TABLE yeu_cau_nap_tien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nguoi_dung_id INT NOT NULL,
    so_tien DECIMAL(15, 0) NOT NULL,
    anh_chung_minh VARCHAR(255) NOT NULL,
    trang_thai ENUM('cho_duyet', 'thanh_cong', 'da_huy') DEFAULT 'cho_duyet',
    ghi_chu TEXT NULL,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoidung(id) ON DELETE CASCADE
);