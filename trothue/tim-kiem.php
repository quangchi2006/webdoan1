<?php
session_start();
require_once 'includes/db.php';
include 'includes/header.php';

// --- 1. NHẬN DỮ LIỆU TỪ URL (GET) ---
$tu_khoa = isset($_GET['tu_khoa']) ? trim($_GET['tu_khoa']) : '';
$quan_huyen_id = isset($_GET['quan_huyen_id']) ? $_GET['quan_huyen_id'] : '';
$khoang_gia = isset($_GET['khoang_gia']) ? $_GET['khoang_gia'] : '';

// --- 2. XÂY DỰNG CÂU QUERY ĐỘNG ---
// Câu SQL gốc: Lấy tin đã duyệt và JOIN các bảng để lấy tên
$sql = "SELECT p.*, q.ten_quan, t.ten_tinh, u.ho_ten, u.avatar, u.sdt
        FROM phongtro p
        JOIN quan_huyen q ON p.quan_huyen_id = q.id
        JOIN tinh_thanh t ON q.tinh_thanh_id = t.id
        JOIN nguoidung u ON p.chu_tro_id = u.id
        WHERE p.trang_thai = 'da_duyet'";

$params = []; // Mảng chứa tham số để bind vào SQL (chống Hack SQL Injection)

// A. Lọc theo Từ khóa (Tìm trong Tiêu đề hoặc Địa chỉ)
if (!empty($tu_khoa)) {
    $sql .= " AND (p.tieu_de LIKE :tu_khoa OR p.dia_chi_cu_the LIKE :tu_khoa)";
    $params[':tu_khoa'] = "%$tu_khoa%";
}

// B. Lọc theo Quận Huyện
if (!empty($quan_huyen_id)) {
    $sql .= " AND p.quan_huyen_id = :quan_id";
    $params[':quan_id'] = $quan_huyen_id;
}

// C. Lọc theo Khoảng giá
if (!empty($khoang_gia)) {
    if ($khoang_gia == 'duoi-2tr') {
        $sql .= " AND p.gia_thue < 2000000";
    } elseif ($khoang_gia == '2tr-4tr') {
        $sql .= " AND p.gia_thue BETWEEN 2000000 AND 4000000";
    } elseif ($khoang_gia == 'tren-4tr') {
        $sql .= " AND p.gia_thue > 4000000";
    }
}

// Sắp xếp: Tin mới nhất lên đầu
$sql .= " ORDER BY p.ngay_dang DESC";

// --- 3. THỰC THI QUERY ---
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Lỗi truy vấn: " . $e->getMessage();
}
// Lấy danh sách quận để giữ lại trạng thái trong ô select
$ds_quan = $conn->query("SELECT * FROM quan_huyen ORDER BY ten_quan ASC")->fetchAll();
?>

<div class="container search-page-container">
    
    <div class="search-bar-mini">
        <form action="tim-kiem.php" method="GET">
            <input type="text" name="tu_khoa" value="<?php echo htmlspecialchars($tu_khoa); ?>" placeholder="Tìm từ khóa...">
            
            <select name="quan_huyen_id">
                <option value="">-- Tất cả Quận --</option>
                <?php foreach ($ds_quan as $q): ?>
                    <option value="<?php echo $q['id']; ?>" <?php if($q['id'] == $quan_huyen_id) echo 'selected'; ?>>
                        <?php echo $q['ten_quan']; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="khoang_gia">
                <option value="">-- Mức giá --</option>
                <option value="duoi-2tr" <?php if($khoang_gia == 'duoi-2tr') echo 'selected'; ?>>Dưới 2 triệu</option>
                <option value="2tr-4tr" <?php if($khoang_gia == '2tr-4tr') echo 'selected'; ?>>2 - 4 triệu</option>
                <option value="tren-4tr" <?php if($khoang_gia == 'tren-4tr') echo 'selected'; ?>>Trên 4 triệu</option>
            </select>

            <button type="submit"><i class="fa-solid fa-filter"></i> Lọc</button>
        </form>
    </div>

    <div class="search-info">
        <h2>Kết quả tìm kiếm</h2>
        <p>Tìm thấy <b><?php echo count($results); ?></b> tin đăng phù hợp.</p>
    </div>

    <div class="room-list-container">
        <?php if (count($results) > 0): ?>
            <?php foreach ($results as $row): ?>
                <?php 
                    // Xử lý ảnh JSON
                    $images = json_decode($row['anh_phong'], true);
                    $thumbnail = !empty($images) ? 'assets/uploads/' . $images[0] : 'assets/img/no-image.jpg';
                ?>

                <div class="room-item-horizontal">
                    <div class="item-image">
                        <a href="phong-chi-tiet.php?id=<?php echo $row['id']; ?>">
                            <img src="<?php echo $thumbnail; ?>" alt="<?php echo htmlspecialchars($row['tieu_de']); ?>">
                        </a>
                        <span class="img-count"><i class="fa-regular fa-image"></i> <?php echo !empty($images) ? count($images) : 0; ?></span>
                        <?php if($row['gia_thue'] > 3000000): ?>
                            <span class="badge-vip">VIP</span>
                        <?php endif; ?>
                    </div>

                    <div class="item-info">
                        <h3 class="item-title">
                            <a href="phong-chi-tiet.php?id=<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['tieu_de']); ?>
                            </a>
                        </h3>

                        <div class="item-meta">
                            <span class="price"><?php echo number_format($row['gia_thue'] / 1000000, 1); ?> tr/tháng</span>
                            <span class="dot">·</span>
                            <span class="area"><?php echo $row['dien_tich']; ?> m²</span>
                            <span class="dot">·</span>
                            <span class="location"><?php echo $row['ten_quan']; ?></span>
                        </div>

                        <p class="item-desc">
                            <?php echo mb_substr(strip_tags($row['mo_ta']), 0, 150) . '...'; ?>
                        </p>

                        <div class="item-footer">
                            <div class="author">
                                <img src="<?php echo $row['avatar'] ? $row['avatar'] : 'assets/img/default-user.png'; ?>" alt="Avatar">
                                <span><?php echo $row['ho_ten']; ?></span>
                            </div>
                            <div class="actions">
                                <a href="tel:<?php echo $row['sdt']; ?>" class="btn-call">
                                    <i class="fa-solid fa-phone"></i> Gọi
                                </a>
                                <a href="https://zalo.me/<?php echo $row['sdt']; ?>" target="_blank" class="btn-zalo">
                                    <i>Z</i> Zalo
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-result-box" style="text-align: center; padding: 50px;">
                <img src="https://cdni.iconscout.com/illustration/premium/thumb/search-result-not-found-2130361-1800925.png" alt="No Result" width="200">
                <p style="margin-top: 20px; color: #666;">Không tìm thấy kết quả nào phù hợp với từ khóa của bạn.</p>
                <a href="index.php" style="color: #f73859; font-weight: bold;">Quay lại trang chủ</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'includes/footer.php'; ?>