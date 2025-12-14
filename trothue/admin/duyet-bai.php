<?php
// Nhúng Header Admin
include 'includes/header.php';

// --- XỬ LÝ HÀNH ĐỘNG (DUYỆT / XÓA / ẨN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. DUYỆT TIN
    if (isset($_POST['approve_id'])) {
        $id = $_POST['approve_id'];
        $stmt = $conn->prepare("UPDATE phongtro SET trang_thai = 'da_duyet' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo "<script>alert('Đã duyệt tin thành công!');</script>";
    }

    // 2. ẨN TIN (Khóa tạm thời)
    if (isset($_POST['hide_id'])) {
        $id = $_POST['hide_id'];
        $stmt = $conn->prepare("UPDATE phongtro SET trang_thai = 'da_an' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo "<script>alert('Đã ẩn tin này!');</script>";
    }

    // 3. XÓA TIN VĨNH VIỄN (Kèm xóa ảnh)
    if (isset($_POST['delete_id'])) {
        $id = $_POST['delete_id'];
        
        // Lấy tên ảnh để xóa khỏi thư mục
        $stmt_get = $conn->prepare("SELECT anh_phong FROM phongtro WHERE id = :id");
        $stmt_get->execute([':id' => $id]);
        $data = $stmt_get->fetch();

        if ($data) {
            $images = json_decode($data['anh_phong'], true);
            if (!empty($images)) {
                foreach ($images as $img) {
                    $path = "../assets/uploads/" . $img;
                    if (file_exists($path)) unlink($path); // Xóa file
                }
            }
            
            // Xóa trong DB
            $stmt_del = $conn->prepare("DELETE FROM phongtro WHERE id = :id");
            $stmt_del->execute([':id' => $id]);
            echo "<script>alert('Đã xóa tin vĩnh viễn!');</script>";
        }
    }
}

// --- LẤY DỮ LIỆU ---
// Lọc theo tab (Mặc định là chờ duyệt)
$status = isset($_GET['status']) ? $_GET['status'] : 'cho_duyet';

$sql = "SELECT p.*, u.ho_ten, l.ten_loai 
        FROM phongtro p 
        JOIN nguoidung u ON p.chu_tro_id = u.id 
        JOIN loai_phong l ON p.loai_phong_id = l.id ";

// Nếu chọn 'all' thì không lọc WHERE, ngược lại lọc theo status
if ($status != 'all') {
    $sql .= " WHERE p.trang_thai = :st ";
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
if ($status != 'all') {
    $stmt->execute([':st' => $status]);
} else {
    $stmt->execute();
}
$posts = $stmt->fetchAll();
?>

<div class="table-section">
    <div class="section-head" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Quản Lý Tin Đăng</h3>
        
        <div class="filter-tabs">
            <a href="quan-ly-tin.php?status=cho_duyet" class="tab-btn <?php echo ($status=='cho_duyet')?'active':''; ?>">
                Chờ duyệt
            </a>
            <a href="quan-ly-tin.php?status=da_duyet" class="tab-btn <?php echo ($status=='da_duyet')?'active':''; ?>">
                Đã duyệt
            </a>
            <a href="quan-ly-tin.php?status=da_an" class="tab-btn <?php echo ($status=='da_an')?'active':''; ?>">
                Đã ẩn
            </a>
            <a href="quan-ly-tin.php?status=all" class="tab-btn <?php echo ($status=='all')?'active':''; ?>">
                Tất cả
            </a>
        </div>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th width="50">ID</th>
                <th width="80">Ảnh</th>
                <th>Tiêu đề / Loại</th>
                <th>Người đăng</th>
                <th>Giá / Diện tích</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($posts) > 0): ?>
                <?php foreach ($posts as $p): ?>
                    <?php 
                        $imgs = json_decode($p['anh_phong'], true);
                        $thumb = !empty($imgs) ? '../assets/uploads/'.$imgs[0] : '../assets/img/no-image.jpg';
                    ?>
                    <tr>
                        <td>#<?php echo $p['id']; ?></td>
                        <td>
                            <img src="<?php echo $thumb; ?>" class="table-thumb">
                        </td>
                        <td>
                            <a href="../phong-chi-tiet.php?id=<?php echo $p['id']; ?>" target="_blank" class="post-link" title="Xem chi tiết">
                                <?php echo mb_substr($p['tieu_de'], 0, 40) . '...'; ?>
                            </a>
                            <div style="font-size: 12px; color: #777; margin-top: 5px;">
                                <i class="fa-solid fa-tag"></i> <?php echo $p['ten_loai']; ?>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo $p['ho_ten']; ?></strong>
                            <div style="font-size: 11px; color: #555;">
                                <?php echo date("d/m/Y H:i", strtotime($p['ngay_dang'])); ?>
                            </div>
                        </td>
                        <td>
                            <div style="color: #d63031; font-weight: bold;"><?php echo number_format($p['gia_thue']); ?></div>
                            <small><?php echo $p['dien_tich']; ?> m²</small>
                        </td>
                        <td>
                            <?php if ($p['trang_thai'] == 'cho_duyet'): ?>
                                <span class="badge" style="background: #f1c40f; color: #000;">Chờ duyệt</span>
                            <?php elseif ($p['trang_thai'] == 'da_duyet'): ?>
                                <span class="badge" style="background: #27ae60;">Đang hiện</span>
                            <?php else: ?>
                                <span class="badge" style="background: #95a5a6;">Đã ẩn</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <?php if ($p['trang_thai'] == 'cho_duyet'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="approve_id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="btn-action btn-ok" title="Duyệt ngay" onclick="return confirm('Duyệt bài này?')">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($p['trang_thai'] == 'da_duyet'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="hide_id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="btn-action btn-warn" title="Ẩn bài" onclick="return confirm('Ẩn bài này?')">
                                            <i class="fa-solid fa-eye-slash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST">
                                    <input type="hidden" name="delete_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn-action btn-del" title="Xóa vĩnh viễn" onclick="return confirm('CẢNH BÁO: Xóa vĩnh viễn không thể khôi phục?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align: center; padding: 20px;">Không tìm thấy dữ liệu.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>