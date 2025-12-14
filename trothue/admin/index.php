<?php
// Nhúng Header (Đã bao gồm session, checkAdmin, db, sidebar, topbar)
include 'includes/header.php';

// --- LOGIC RIÊNG CỦA TRANG DASHBOARD ---

// Thống kê nhanh
$cnt_post = $conn->query("SELECT COUNT(*) FROM phongtro WHERE trang_thai = 'cho_duyet'")->fetchColumn();
$cnt_deposit = $conn->query("SELECT COUNT(*) FROM yeu_cau_nap_tien WHERE trang_thai = 'cho_duyet'")->fetchColumn();
$cnt_user = $conn->query("SELECT COUNT(*) FROM nguoidung")->fetchColumn();
$cnt_all = $conn->query("SELECT COUNT(*) FROM phongtro")->fetchColumn();

// Lấy 5 tin mới nhất
$pending_posts = $conn->query("SELECT p.*, u.ho_ten FROM phongtro p JOIN nguoidung u ON p.chu_tro_id = u.id WHERE p.trang_thai = 'cho_duyet' ORDER BY p.ngay_dang DESC LIMIT 5")->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card orange">
        <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
        <div class="stat-info">
            <h3><?php echo $cnt_post; ?></h3>
            <p>Tin chờ duyệt</p>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fa-solid fa-money-bill-transfer"></i></div>
        <div class="stat-info">
            <h3><?php echo $cnt_deposit; ?></h3>
            <p>Yêu cầu nạp</p>
        </div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo $cnt_user; ?></h3>
            <p>Thành viên</p>
        </div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon"><i class="fa-solid fa-newspaper"></i></div>
        <div class="stat-info">
            <h3><?php echo $cnt_all; ?></h3>
            <p>Tổng tin đăng</p>
        </div>
    </div>
</div>

<div class="table-section">
    <div class="section-head">
        <h3>Tin đăng mới chờ duyệt</h3>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Tiêu đề</th>
                <th>Người đăng</th>
                <th>Giá</th>
                <th>Ngày đăng</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pending_posts as $post): ?>
                <tr>
                    <td><a href="../phong-chi-tiet.php?id=<?php echo $post['id']; ?>" target="_blank"><?php echo $post['tieu_de']; ?></a></td>
                    <td><?php echo $post['ho_ten']; ?></td>
                    <td><?php echo number_format($post['gia_thue']); ?></td>
                    <td><?php echo date("d/m", strtotime($post['ngay_dang'])); ?></td>
                    <td><a href="quan-ly-bai-dang.php" class="btn-approve">Xem chi tiết</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php 
// Nhúng Footer
include 'includes/footer.php'; 
?>