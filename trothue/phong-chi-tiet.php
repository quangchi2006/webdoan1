<?php
session_start();
require_once 'includes/db.php';
include 'includes/header.php';

// 1. Kiểm tra ID trên URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$room_id = $_GET['id'];

// 2. Tăng lượt xem lên 1
$conn->query("UPDATE phongtro SET luot_xem = luot_xem + 1 WHERE id = $room_id");

// 3. Lấy thông tin chi tiết phòng + Chủ trọ + Quận/Huyện
$sql = "SELECT p.*, q.ten_quan, t.ten_tinh, l.ten_loai, u.ho_ten, u.sdt, u.avatar, u.zalo, u.facebook
        FROM phongtro p
        JOIN quan_huyen q ON p.quan_huyen_id = q.id
        JOIN tinh_thanh t ON q.tinh_thanh_id = t.id
        JOIN loai_phong l ON p.loai_phong_id = l.id
        JOIN nguoidung u ON p.chu_tro_id = u.id
        WHERE p.id = :id AND p.trang_thai = 'da_duyet'
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $room_id);
$stmt->execute();
$room = $stmt->fetch();

// Nếu không tìm thấy phòng (hoặc chưa duyệt)
if (!$room) {
    echo "<div class='container' style='padding:50px; text-align:center;'><h3>Tin đăng không tồn tại hoặc đã bị xóa!</h3><a href='index.php'>Quay lại trang chủ</a></div>";
    include 'includes/footer.php';
    exit();
}

// Xử lý ảnh JSON
$images = json_decode($room['anh_phong'], true);
$main_image = !empty($images) ? 'assets/uploads/' . $images[0] : 'assets/img/no-image.jpg';

// 4. Lấy danh sách đánh giá
$sql_review = "SELECT d.*, u.ho_ten, u.avatar 
               FROM danhgia d 
               JOIN nguoidung u ON d.nguoi_dung_id = u.id 
               WHERE d.phong_tro_id = :id 
               ORDER BY d.ngay_danh_gia DESC";
$stmt_rv = $conn->prepare($sql_review);
$stmt_rv->execute([':id' => $room_id]);
$reviews = $stmt_rv->fetchAll();

// 5. Lấy tin liên quan (Cùng quận)
$sql_rel = "SELECT * FROM phongtro WHERE quan_huyen_id = :qid AND id != :id AND trang_thai = 'da_duyet' LIMIT 3";
$stmt_rel = $conn->prepare($sql_rel);
$stmt_rel->execute([':qid' => $room['quan_huyen_id'], ':id' => $room_id]);
$related_rooms = $stmt_rel->fetchAll();
?>

<div class="container room-detail-page">
    
    <ul class="breadcrumb">
        <li><a href="index.php">Trang chủ</a></li>
        <li><a href="#"><?php echo $room['ten_tinh']; ?></a></li>
        <li><a href="#"><?php echo $room['ten_quan']; ?></a></li>
        <li class="active"><?php echo $room['ten_loai']; ?></li>
    </ul>

    <div class="detail-layout">
        
        <div class="left-col">
            
            <div class="image-gallery">
                <div class="main-img-box">
                    <img src="<?php echo $main_image; ?>" id="currentImg" alt="Ảnh chính">
                </div>
                <?php if (!empty($images) && count($images) > 1): ?>
                    <div class="thumb-list">
                        <?php foreach ($images as $img): ?>
                            <img src="assets/uploads/<?php echo $img; ?>" onclick="changeImage(this)" class="thumb-img">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <h1 class="post-title">
                <span class="star-icon">★★★★★</span> 
                <?php echo $room['tieu_de']; ?>
            </h1>
            
            <div class="post-address">
                <i class="fa-solid fa-location-dot"></i> 
                Địa chỉ: <?php echo $room['dia_chi_cu_the']; ?>, <?php echo $room['ten_quan']; ?>, <?php echo $room['ten_tinh']; ?>
            </div>

            <div class="post-meta-bar">
                <div class="meta-item price">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span><?php echo number_format($room['gia_thue']); ?> đ/tháng</span>
                </div>
                <div class="meta-item">
                    <i class="fa-solid fa-ruler-combined"></i>
                    <span><?php echo $room['dien_tich']; ?> m²</span>
                </div>
                <div class="meta-item">
                    <i class="fa-regular fa-clock"></i>
                    <span><?php echo date("d/m/Y", strtotime($room['ngay_dang'])); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fa-regular fa-eye"></i>
                    <span><?php echo $room['luot_xem']; ?> lượt xem</span>
                </div>
            </div>

            <div class="section-box">
                <h3 class="sec-title">Thông tin mô tả</h3>
                <div class="desc-content">
                    <?php echo nl2br($room['mo_ta']); ?>
                </div>
            </div>

            <div class="section-box">
                <h3 class="sec-title">Tiện ích phòng</h3>
                <div class="amenities-list">
                    <?php if($room['wifi']) echo '<span class="amenity"><i class="fa-solid fa-wifi"></i> Wifi miễn phí</span>'; ?>
                    <?php if($room['may_lanh']) echo '<span class="amenity"><i class="fa-regular fa-snowflake"></i> Máy lạnh</span>'; ?>
                    <?php if($room['tu_lanh']) echo '<span class="amenity"><i class="fa-solid fa-icicles"></i> Tủ lạnh</span>'; ?>
                    <?php if($room['may_giat']) echo '<span class="amenity"><i class="fa-solid fa-soap"></i> Máy giặt</span>'; ?>
                    <?php if($room['wc_rieng']) echo '<span class="amenity"><i class="fa-solid fa-toilet"></i> WC riêng</span>'; ?>
                    <?php if($room['gio_tu_do']) echo '<span class="amenity"><i class="fa-regular fa-clock"></i> Giờ tự do</span>'; ?>
                    <?php if(!$room['wifi'] && !$room['may_lanh'] && !$room['wc_rieng']) echo "<span>Đang cập nhật...</span>"; ?>
                </div>
            </div>

            <div class="section-box reviews-section">
                <h3 class="sec-title">Đánh giá & Bình luận (<?php echo count($reviews); ?>)</h3>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="xu-ly-danh-gia.php" method="POST" class="review-form">
                        <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                        <div class="rating-select">
                            <label>Chọn sao:</label>
                            <select name="so_sao">
                                <option value="5">5 Tuyệt vời</option>
                                <option value="4">4 Tốt</option>
                                <option value="3">3 Khá</option>
                                <option value="2">2 Trung bình</option>
                                <option value="1">1 Kém</option>
                            </select>
                        </div>
                        <textarea name="noi_dung" placeholder="Chia sẻ trải nghiệm của bạn về phòng trọ này..." required></textarea>
                        <button type="submit" class="btn-submit-review">Gửi đánh giá</button>
                    </form>
                <?php else: ?>
                    <p class="login-alert">Vui lòng <a href="dang-nhap.php">Đăng nhập</a> để viết bình luận.</p>
                <?php endif; ?>

                <div class="reviews-list">
                    <?php foreach ($reviews as $rv): ?>
                        <div class="review-item">
                            <img src="<?php echo $rv['avatar'] ? $rv['avatar'] : 'assets/img/default-user.png'; ?>" class="rv-avatar">
                            <div class="rv-content">
                                <div class="rv-header">
                                    <strong><?php echo $rv['ho_ten']; ?></strong>
                                    <span class="rv-stars"><?php echo str_repeat('★', $rv['so_sao']); ?></span>
                                </div>
                                <p><?php echo htmlspecialchars($rv['noi_dung']); ?></p>
                                <small class="text-muted"><?php echo date("d/m/Y H:i", strtotime($rv['ngay_danh_gia'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div> <div class="right-col">
            <div class="author-card">
                <div class="author-header">
                    <img src="<?php echo $room['avatar'] ? $room['avatar'] : 'assets/img/default-user.png'; ?>" alt="Avatar">
                    <div class="author-info">
                        <h4><?php echo $room['ho_ten']; ?></h4>
                        <span><i class="fa-solid fa-circle-check" style="color:green"></i> Chủ nhà</span>
                    </div>
                </div>
                
                <div class="contact-buttons">
                    <a href="tel:<?php echo $room['sdt']; ?>" class="btn-call-big">
                        <i class="fa-solid fa-phone"></i> <?php echo $room['sdt']; ?>
                    </a>
                    <a href="https://zalo.me/<?php echo $room['sdt']; ?>" target="_blank" class="btn-zalo-big">
                        <i>Z</i> Nhắn Zalo
                    </a>
                </div>
            </div>

            <div class="sidebar-box">
                <h3>Tin cùng khu vực</h3>
                <ul class="related-list">
                    <?php foreach ($related_rooms as $rel): ?>
                        <?php 
                            $r_img = json_decode($rel['anh_phong'], true); 
                            $r_thumb = !empty($r_img) ? 'assets/uploads/'.$r_img[0] : 'assets/img/no-image.jpg';
                        ?>
                        <li>
                            <a href="phong-chi-tiet.php?id=<?php echo $rel['id']; ?>" class="rel-item">
                                <img src="<?php echo $r_thumb; ?>">
                                <div class="rel-info">
                                    <h5><?php echo $rel['tieu_de']; ?></h5>
                                    <span class="price"><?php echo number_format($rel['gia_thue']/1000000, 1); ?> tr/tháng</span>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

    </div>
</div>

<script>
    // Script đổi ảnh khi bấm vào thumbnail
    function changeImage(imgs) {
        var expandImg = document.getElementById("currentImg");
        expandImg.src = imgs.src;
    }
</script>

<?php include 'includes/footer.php'; ?>