<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// 1. Kiểm tra đăng nhập
checkLogin();

// 2. Kiểm tra ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: profile.php");
    exit();
}

$post_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// 3. Lấy thông tin bài viết cũ
$stmt = $conn->prepare("SELECT * FROM phongtro WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $post_id]);
$post = $stmt->fetch();

// 4. Kiểm tra quyền sở hữu
if (!$post || ($post['chu_tro_id'] != $user_id && $_SESSION['role'] != 'admin')) {
    echo "<script>alert('Bạn không có quyền sửa bài viết này!'); window.location.href='profile.php';</script>";
    exit();
}

// Lấy danh mục
$quan_huyen = $conn->query("SELECT * FROM quan_huyen ORDER BY ten_quan ASC")->fetchAll();
$loai_phong = $conn->query("SELECT * FROM loai_phong")->fetchAll();

// 5. XỬ LÝ KHI BẤM LƯU
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tieu_de = trim($_POST['tieu_de']);
    $loai_phong_id = $_POST['loai_phong_id'];
    $quan_huyen_id = $_POST['quan_huyen_id'];
    $dia_chi = trim($_POST['dia_chi']);
    $gia_thue = $_POST['gia_thue'];
    $dien_tich = $_POST['dien_tich'];
    $mo_ta = $_POST['mo_ta'];
    
    // --- XỬ LÝ TRẠNG THÁI (MỚI THÊM) ---
    $trang_thai_moi = $_POST['trang_thai'];
    
    // Logic bảo mật:
    // Nếu bài đang chờ duyệt -> Bắt buộc giữ nguyên là chờ duyệt (không cho user tự duyệt)
    if ($post['trang_thai'] == 'cho_duyet') {
        $trang_thai_moi = 'cho_duyet';
    } 
    // Nếu user cố tình hack gửi lên trạng thái bậy bạ -> Reset về trạng thái cũ
    elseif (!in_array($trang_thai_moi, ['da_duyet', 'da_thue', 'da_an'])) {
        $trang_thai_moi = $post['trang_thai'];
    }

    // Tiện ích
    $wifi = isset($_POST['wifi']) ? 1 : 0;
    $may_lanh = isset($_POST['may_lanh']) ? 1 : 0;
    $tu_lanh = isset($_POST['tu_lanh']) ? 1 : 0;
    $wc_rieng = isset($_POST['wc_rieng']) ? 1 : 0;
    $may_giat = isset($_POST['may_giat']) ? 1 : 0;
    $gio_tu_do = isset($_POST['gio_tu_do']) ? 1 : 0;

    // Xử lý ảnh (Giữ nguyên logic cũ)
    $anh_phong_json = $post['anh_phong'];
    if (isset($_FILES['anh_phong']) && count($_FILES['anh_phong']['name']) > 0 && !empty($_FILES['anh_phong']['name'][0])) {
        $image_files = [];
        $total_files = count($_FILES['anh_phong']['name']);
        for ($i = 0; $i < $total_files; $i++) {
            $file_name = $_FILES['anh_phong']['name'][$i];
            $tmp_name = $_FILES['anh_phong']['tmp_name'][$i];
            $error = $_FILES['anh_phong']['error'][$i];
            if ($error === 0) {
                $new_name = time() . '_' . $i . '_' . $file_name;
                if(move_uploaded_file($tmp_name, 'assets/uploads/' . $new_name)){
                    $image_files[] = $new_name;
                }
            }
        }
        if (!empty($image_files)) {
            $anh_phong_json = json_encode($image_files);
        }
    }

    // Cập nhật SQL (Thêm cột trang_thai vào câu lệnh)
    try {
        $sql = "UPDATE phongtro SET 
                tieu_de = :td, loai_phong_id = :lp, quan_huyen_id = :qh, dia_chi_cu_the = :dc,
                gia_thue = :gia, dien_tich = :dt, mo_ta = :mt, anh_phong = :img,
                wifi = :wf, may_lanh = :ml, tu_lanh = :tl, wc_rieng = :wc, may_giat = :mg, gio_tu_do = :gtd,
                trang_thai = :tt, 
                ngay_dang = NOW() 
                WHERE id = :id";
        
        $stmt_update = $conn->prepare($sql);
        $result = $stmt_update->execute([
            ':td' => $tieu_de, ':lp' => $loai_phong_id, ':qh' => $quan_huyen_id, ':dc' => $dia_chi,
            ':gia' => $gia_thue, ':dt' => $dien_tich, ':mt' => $mo_ta, ':img' => $anh_phong_json,
            ':wf' => $wifi, ':ml' => $may_lanh, ':tl' => $tu_lanh, ':wc' => $wc_rieng, ':mg' => $may_giat, ':gtd' => $gio_tu_do,
            ':tt' => $trang_thai_moi,
            ':id' => $post_id
        ]);

        if ($result) {
            $success_msg = "Cập nhật bài viết & trạng thái thành công!";
            // Refresh dữ liệu
            $stmt->execute([':id' => $post_id]);
            $post = $stmt->fetch();
        }
    } catch (PDOException $e) {
        $error_msg = "Lỗi: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container" style="margin-top: 30px; margin-bottom: 50px;">
    <div class="post-form-card">
        <h2 class="form-title">Chỉnh Sửa Tin Đăng</h2>

        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?>
                <a href="profile.php" style="margin-left: 10px; font-weight: bold;">Quay lại quản lý</a>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            
            <div class="form-section highlight-section">
                <h3><i class="fa-solid fa-toggle-on"></i> Trạng thái hiển thị</h3>
                <div class="form-group">
                    <?php if ($post['trang_thai'] == 'cho_duyet'): ?>
                        <div class="alert alert-warning" style="margin-bottom: 0;">
                            <i class="fa-solid fa-hourglass-half"></i> Tin này đang chờ Admin duyệt. Bạn chưa thể thay đổi trạng thái.
                        </div>
                        <input type="hidden" name="trang_thai" value="cho_duyet">
                    <?php else: ?>
                        <select name="trang_thai" class="form-control status-selector">
                            <option value="da_duyet" <?php if($post['trang_thai']=='da_duyet') echo 'selected'; ?>>
                                ✅ Đang hiển thị (Mọi người đều thấy)
                            </option>
                            <option value="da_thue" <?php if($post['trang_thai']=='da_thue') echo 'selected'; ?>>
                                🏠 Đã cho thuê (Khách sẽ không thấy nữa)
                            </option>
                            <option value="da_an" <?php if($post['trang_thai']=='da_an') echo 'selected'; ?>>
                                🔒 Ẩn tin (Tạm thời đóng tin này)
                            </option>
                        </select>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-section">
                <h3>Thông tin cơ bản</h3>
                <div class="form-group">
                    <label>Tiêu đề</label>
                    <input type="text" name="tieu_de" class="form-control" value="<?php echo htmlspecialchars($post['tieu_de']); ?>" required>
                </div>
                <div class="row-2-col">
                    <div class="form-group">
                        <label>Loại phòng</label>
                        <select name="loai_phong_id" class="form-control">
                            <?php foreach ($loai_phong as $lp): ?>
                                <option value="<?php echo $lp['id']; ?>" <?php if($post['loai_phong_id'] == $lp['id']) echo 'selected'; ?>>
                                    <?php echo $lp['ten_loai']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quận / Huyện</label>
                        <select name="quan_huyen_id" class="form-control">
                            <?php foreach ($quan_huyen as $qh): ?>
                                <option value="<?php echo $qh['id']; ?>" <?php if($post['quan_huyen_id'] == $qh['id']) echo 'selected'; ?>>
                                    <?php echo $qh['ten_quan']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Địa chỉ cụ thể</label>
                    <input type="text" name="dia_chi" class="form-control" value="<?php echo htmlspecialchars($post['dia_chi_cu_the']); ?>" required>
                </div>
            </div>

            <div class="form-section">
                <h3>Thông tin chi tiết</h3>
                <div class="row-2-col">
                    <div class="form-group">
                        <label>Giá thuê (VNĐ)</label>
                        <input type="number" name="gia_thue" class="form-control" value="<?php echo $post['gia_thue']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Diện tích (m²)</label>
                        <input type="number" name="dien_tich" class="form-control" value="<?php echo $post['dien_tich']; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Tiện ích</label>
                    <div class="amenities-grid">
                        <label class="checkbox-container"><input type="checkbox" name="wifi" <?php echo $post['wifi']?'checked':''; ?>> Wifi</label>
                        <label class="checkbox-container"><input type="checkbox" name="may_lanh" <?php echo $post['may_lanh']?'checked':''; ?>> Máy lạnh</label>
                        <label class="checkbox-container"><input type="checkbox" name="wc_rieng" <?php echo $post['wc_rieng']?'checked':''; ?>> WC riêng</label>
                        <label class="checkbox-container"><input type="checkbox" name="tu_lanh" <?php echo $post['tu_lanh']?'checked':''; ?>> Tủ lạnh</label>
                        <label class="checkbox-container"><input type="checkbox" name="may_giat" <?php echo $post['may_giat']?'checked':''; ?>> Máy giặt</label>
                        <label class="checkbox-container"><input type="checkbox" name="gio_tu_do" <?php echo $post['gio_tu_do']?'checked':''; ?>> Giờ tự do</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="mo_ta" class="form-control" rows="5"><?php echo htmlspecialchars($post['mo_ta']); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3>Cập nhật hình ảnh</h3>
                <div class="form-group">
                    <p style="font-size: 13px; color: #666; margin-bottom: 10px;">
                        Hình ảnh hiện tại (Nếu bạn không chọn ảnh mới, hệ thống sẽ giữ lại ảnh cũ):
                    </p>
                    <div style="display: flex; gap: 10px; margin-bottom: 15px; overflow-x: auto;">
                        <?php 
                            $old_imgs = json_decode($post['anh_phong'], true);
                            if ($old_imgs) {
                                foreach ($old_imgs as $img) {
                                    echo '<img src="assets/uploads/'.$img.'" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">';
                                }
                            }
                        ?>
                    </div>
                    <label>Tải ảnh mới lên (Sẽ thay thế toàn bộ ảnh cũ)</label>
                    <input type="file" name="anh_phong[]" class="form-control" multiple accept="image/*">
                </div>
            </div>

            <div style="display: flex; gap: 15px;">
                <button type="submit" class="btn-submit-post">LƯU TẤT CẢ THAY ĐỔI</button>
                <a href="profile.php" class="btn-back" style="background: #ccc; color: #333; margin: 0; padding-top: 15px;">Hủy bỏ</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>