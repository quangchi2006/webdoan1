<?php
// Nhúng Header Admin (Chứa kết nối DB, checkAdmin, Sidebar)
include 'includes/header.php';

// --- XỬ LÝ CÁC HÀNH ĐỘNG (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. DUYỆT TIN (Chuyển sang 'da_duyet')
    if (isset($_POST['approve_id'])) {
        $id = $_POST['approve_id'];
        $stmt = $conn->prepare("UPDATE phongtro SET trang_thai = 'da_duyet' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo "<script>alert('Đã duyệt tin thành công!');</script>";
    }

    // 2. ẨN TIN (Chuyển sang 'da_an')
    if (isset($_POST['hide_id'])) {
        $id = $_POST['hide_id'];
        $stmt = $conn->prepare("UPDATE phongtro SET trang_thai = 'da_an' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo "<script>alert('Đã ẩn tin này!');</script>";
    }

    // 3. HIỆN TIN LẠI (Từ đã ẩn -> đã duyệt)
    if (isset($_POST['show_id'])) {
        $id = $_POST['show_id'];
        $stmt = $conn->prepare("UPDATE phongtro SET trang_thai = 'da_duyet' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo "<script>alert('Đã mở lại tin này!');</script>";
    }

    // 4. XÓA TIN VĨNH VIỄN
    if (isset($_POST['delete_id'])) {
        $id = $_POST['delete_id'];
        
        // Bước 1: Lấy danh sách ảnh để xóa file vật lý
        $stmt_get = $conn->prepare("SELECT anh_phong FROM phongtro WHERE id = :id");
        $stmt_get->execute([':id' => $id]);
        $data = $stmt_get->fetch();

        if ($data) {
            $images = json_decode($data['anh_phong'], true);
            if (!empty($images)) {
                foreach ($images as $img) {
                    $path = "../assets/uploads/" . $img;
                    if (file_exists($path)) {
                        unlink($path); // Hàm xóa file của PHP
                    }
                }
            }
            
            // Bước 2: Xóa dữ liệu trong CSDL
            $stmt_del = $conn->prepare("DELETE FROM phongtro WHERE id = :id");
            $stmt_del->execute([':id' => $id]);
            echo "<script>alert('Đã xóa tin và hình ảnh vĩnh viễn!');</script>";
        }
    }
}

// --- LẤY DỮ LIỆU VÀ LỌC ---
$status = isset($_GET['status']) ? $_GET['status'] : 'all'; // Mặc định lấy tất cả

$sql = "SELECT p.*, u.ho_ten, l.ten_loai 
        FROM phongtro p 
        JOIN nguoidung u ON p.chu_tro_id = u.id 
        JOIN loai_phong l ON p.loai_phong_id = l.id ";

// Logic lọc SQL
if ($status == 'cho_duyet') {
    $sql .= " WHERE p.trang_thai = 'cho_duyet' ";
} elseif ($status == 'da_duyet') {
    $sql .= " WHERE p.trang_thai = 'da_duyet' ";
} elseif ($status == 'da_an') {
    $sql .= " WHERE p.trang_thai = 'da_an' ";
}

$sql .= " ORDER BY p.id DESC"; // Tin mới nhất lên đầu

$stmt = $conn->prepare($sql);
$stmt->execute();
$posts = $stmt->fetchAll();
?>

<div class="table-section">
    <div class="section-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h3>Quản Lý Tin Đăng</h3>
        
        <div class="filter-tabs">
            <a href="quan-ly-bai-dang.php?status=all" class="tab-btn <?php echo ($status=='all')?'active':''; ?>">Tất cả</a>
            <a href="quan-ly-bai-dang.php?status=cho_duyet" class="tab-btn <?php echo ($status=='cho_duyet')?'active':''; ?>">Chờ duyệt</a>
            <a href="quan-ly-bai-dang.php?status=da_duyet" class="tab-btn <?php echo ($status=='da_duyet')?'active':''; ?>">Đang hiện</a>
            <a href="quan-ly-bai-dang.php?status=da_an" class="tab-btn <?php echo ($status=='da_an')?'active':''; ?>">Đã ẩn</a>
        </div>
    </div>
    
    <div style="overflow-x: auto;"> <table class="admin-table">
            <thead>
                <tr>
                    <th width="50">ID</th>
                    <th width="80">Ảnh</th>
                    <th>Thông tin tin đăng</th>
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
                                <a href="../phong-chi-tiet.php?id=<?php echo $p['id']; ?>" target="_blank" class="post-link" title="Xem bài viết trên web">
                                    <?php echo mb_substr($p['tieu_de'], 0, 40) . '...'; ?> <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px;"></i>
                                </a>
                                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                    <span style="background: #eee; padding: 2px 6px; border-radius: 4px;">
                                        <?php echo $p['ten_loai']; ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($p['ho_ten']); ?></strong>
                                <div style="font-size: 11px; color: #888;">
                                    <?php echo date("d/m/Y H:i", strtotime($p['ngay_dang'])); ?>
                                </div>
                            </td>
                            <td>
                                <div style="color: #d63031; font-weight: bold;">
                                    <?php echo number_format($p['gia_thue']); ?> đ
                                </div>
                                <small style="color: #555;"><?php echo $p['dien_tich']; ?> m²</small>
                            </td>
                            <td>
                                <?php if ($p['trang_thai'] == 'cho_duyet'): ?>
                                    <span class="badge" style="background: #f1c40f; color: #000;">Chờ duyệt</span>
                                <?php elseif ($p['trang_thai'] == 'da_duyet'): ?>
                                    <span class="badge" style="background: #27ae60;">Đã duyệt</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #95a5a6;">Đã ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    
                                    <?php if ($p['trang_thai'] == 'cho_duyet'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="approve_id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn-action btn-ok" title="Duyệt bài này" onclick="return confirm('Duyệt bài đăng này?')">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($p['trang_thai'] == 'da_duyet'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="hide_id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn-action btn-warn" title="Ẩn bài (Khóa tạm)" onclick="return confirm('Bạn muốn ẩn bài đăng này?')">
                                                <i class="fa-solid fa-eye-slash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($p['trang_thai'] == 'da_an'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="show_id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn-action btn-ok" title="Mở lại bài này" onclick="return confirm('Mở lại bài đăng này?')">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST">
                                        <input type="hidden" name="delete_id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="btn-action btn-del" title="Xóa vĩnh viễn" onclick="return confirm('CẢNH BÁO: Hành động này sẽ xóa vĩnh viễn tin đăng và hình ảnh. Không thể khôi phục!')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; padding: 20px;">Không có dữ liệu phù hợp.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>