<?php
// 1. Khởi động Session và Kết nối
session_start();
require_once 'includes/db.php';
include 'includes/header.php';

// --- PHẦN 1: LẤY DỮ LIỆU CHO Ô TÌM KIẾM ---
try {
    $stmt_quan = $conn->query("SELECT * FROM quan_huyen ORDER BY ten_quan ASC");
    $ds_quan = $stmt_quan->fetchAll();
} catch (PDOException $e) {
    $ds_quan = [];
}

// --- PHẦN 2: XỬ LÝ LOGIC LỌC BÀI VIẾT ---

// Kiểm tra xem có ID loại phòng trên URL không?
$loai_id = isset($_GET['loai']) ? (int)$_GET['loai'] : 0;
$tieu_de_trang = "Tin Cho Thuê Mới Nhất"; // Tiêu đề mặc định

// Câu SQL Gốc (Lấy tất cả bài đã duyệt)
$sql = "SELECT p.*, q.ten_quan, t.ten_tinh, l.ten_loai 
        FROM phongtro p
        JOIN quan_huyen q ON p.quan_huyen_id = q.id
        JOIN tinh_thanh t ON q.tinh_thanh_id = t.id
        JOIN loai_phong l ON p.loai_phong_id = l.id
        WHERE p.trang_thai = 'da_duyet'";

// NẾU CÓ CHỌN LOẠI -> THÊM ĐIỀU KIỆN LỌC
if ($loai_id > 0) {
    $sql .= " AND p.loai_phong_id = :lid";
    
    // Lấy tên loại để sửa tiêu đề (VD: Danh mục Phòng trọ)
    // Nếu bạn không muốn đổi tiêu đề thì xóa đoạn if này đi
    $stmt_ten = $conn->prepare("SELECT ten_loai FROM loai_phong WHERE id = :lid");
    $stmt_ten->execute([':lid' => $loai_id]);
    $ten_loai = $stmt_ten->fetchColumn();
    
    if ($ten_loai) {
        $tieu_de_trang = "Danh mục: " . $ten_loai;
    }
}

// Sắp xếp và Giới hạn
$sql .= " ORDER BY p.ngay_dang DESC LIMIT 12";

// Thực thi SQL
try {
    $stmt = $conn->prepare($sql);
    if ($loai_id > 0) {
        $stmt->bindParam(':lid', $loai_id);
    }
    $stmt->execute();
    $phong_tros = $stmt->fetchAll();
} catch (PDOException $e) {
    $phong_tros = [];
}
?>

<div class="hero-section">
    <div class="hero-content">
        <form action="tim-kiem.php" method="GET" class="search-box">
            <div class="input-group item-keyword">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="tu_khoa" placeholder="Tìm theo tên đường...">
            </div>
            <div class="input-group item-location">
                <i class="fa-solid fa-location-dot"></i>
                <select name="quan_huyen_id">
                    <option value="">-- Tất cả Quận --</option>
                    <?php foreach ($ds_quan as $quan): ?>
                        <option value="<?php echo $quan['id']; ?>">
                            <?php echo $quan['ten_quan']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group item-price">
                <i class="fa-solid fa-money-bill-wave"></i>
                <select name="khoang_gia">
                    <option value="">-- Mức giá --</option>
                    <option value="duoi-2tr">Dưới 2 triệu</option>
                    <option value="2tr-4tr">2 - 4 triệu</option>
                    <option value="tren-4tr">Trên 4 triệu</option>
                </select>
            </div>
            <button type="submit" class="btn-search">Tìm Kiếm</button>
        </form>
    </div>
</div>

<div class="container main-content">
    
    <h2 class="section-title">
        <?php echo htmlspecialchars($tieu_de_trang); ?>
    </h2>
    
    <div class="room-grid">
        <?php if (count($phong_tros) > 0): ?>
            <?php foreach ($phong_tros as $row): ?>
                <?php 
                    $images = json_decode($row['anh_phong'], true);
                    $thumbnail = !empty($images) ? 'assets/uploads/' . $images[0] : 'assets/img/no-image.jpg';
                ?>
                <div class="room-card">
                    <div class="room-img">
                        <a href="phong-chi-tiet.php?id=<?php echo $row['id']; ?>">
                            <img src="<?php echo $thumbnail; ?>" alt="<?php echo htmlspecialchars($row['tieu_de']); ?>">
                        </a>
                        <span class="badge-service"><?php echo $row['ten_loai']; ?></span>
                        <?php if($row['gia_thue'] > 3000000): ?>
                            <span class="badge-vip"><i class="fa-solid fa-crown"></i> VIP</span>
                        <?php endif; ?>
                    </div>

                    <div class="room-info">
                        <h3 class="room-title">
                            <a href="phong-chi-tiet.php?id=<?php echo $row['id']; ?>">
                                <span style="color: #ffd700;">★★★★★</span>
                                <?php echo htmlspecialchars($row['tieu_de']); ?>
                            </a>
                        </h3>

                        <div class="room-meta">
                            <span class="price"><?php echo number_format($row['gia_thue']); ?> đ/tháng</span>
                            <span class="area"><?php echo $row['dien_tich']; ?> m²</span>
                        </div>

                        <div class="room-address">
                            <i class="fa-solid fa-location-dot"></i> 
                            <?php echo $row['ten_quan']; ?>, <?php echo $row['ten_tinh']; ?>
                        </div>

                        <div class="room-footer">
                            <span class="time-ago">
                                <i class="fa-regular fa-clock"></i>
                                <?php echo date("d/m/Y", strtotime($row['ngay_dang'])); ?>
                            </span>
                            <div class="mini-amenities" style="color: #2ecc71;">
                                <?php if ($row['wifi']) echo '<i class="fa-solid fa-wifi"></i> '; ?>
                                <?php if ($row['may_lanh']) echo '<i class="fa-regular fa-snowflake"></i> '; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="width: 100%; text-align: center; padding: 50px; grid-column: 1 / -1;">
                <p>Chưa có tin đăng nào thuộc mục này.</p>
                <a href="index.php" style="color:#f73859;">Xem tất cả</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>