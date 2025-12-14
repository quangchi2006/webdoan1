<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// 1. Kiểm tra đăng nhập
checkLogin();

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// 2. XỬ LÝ: CẬP NHẬT THÔNG TIN CÁ NHÂN
if (isset($_POST['update_info'])) {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    
    // Lấy thông tin cũ
    $stmt_old = $conn->prepare("SELECT avatar FROM nguoidung WHERE id = :id");
    $stmt_old->execute([':id' => $user_id]);
    $old_user = $stmt_old->fetch();
    $avatar_path = $old_user['avatar'];

    // Xử lý Upload Avatar
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['avatar']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            $new_name = "avatar_" . $user_id . "_" . time() . "." . $file_ext;
            $upload_dir = "assets/uploads/" . $new_name;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir)) {
                $avatar_path = $upload_dir;
                $_SESSION['avatar'] = $avatar_path;
            }
        }
    }

    $sql = "UPDATE nguoidung SET ho_ten = :name, sdt = :phone, avatar = :avt WHERE id = :id";
    $stmt_update = $conn->prepare($sql);
    if ($stmt_update->execute([':name' => $fullname, ':phone' => $phone, ':avt' => $avatar_path, ':id' => $user_id])) {
        $success_msg = "Cập nhật thông tin thành công!";
        $_SESSION['fullname'] = $fullname;
    } else {
        $error_msg = "Lỗi hệ thống, vui lòng thử lại.";
    }
}

// 3. XỬ LÝ: ĐỔI MẬT KHẨU
if (isset($_POST['change_pass'])) {
    $old_pass = $_POST['old_pass'];
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    $stmt_check = $conn->prepare("SELECT mat_khau FROM nguoidung WHERE id = :id");
    $stmt_check->execute([':id' => $user_id]);
    $user_data = $stmt_check->fetch();

    if (!password_verify($old_pass, $user_data['mat_khau'])) {
        $error_msg = "Mật khẩu cũ không chính xác.";
    } elseif ($new_pass !== $confirm_pass) {
        $error_msg = "Mật khẩu nhập lại không khớp.";
    } elseif (strlen($new_pass) < 6) {
        $error_msg = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    } else {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt_pass = $conn->prepare("UPDATE nguoidung SET mat_khau = :pass WHERE id = :id");
        if ($stmt_pass->execute([':pass' => $new_hash, ':id' => $user_id])) {
            $success_msg = "Đổi mật khẩu thành công!";
        }
    }
}

// 4. LẤY LẠI THÔNG TIN USER ĐỂ HIỂN THỊ
$stmt = $conn->prepare("SELECT * FROM nguoidung WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();

include 'includes/header.php'; 
?>

<div class="container profile-container">
    
    <div class="profile-sidebar">
        <div class="user-card">
            <div class="avatar-wrapper">
                <img src="<?php echo !empty($user['avatar']) ? $user['avatar'] : 'assets/img/default-user.png'; ?>" alt="Avatar">
            </div>
            <h3 class="profile-name"><?php echo htmlspecialchars($user['ho_ten']); ?></h3>
            
            <div class="profile-meta">
                <span class="role-badge">
                    <?php 
                        if ($user['vai_tro'] == 'admin') echo 'Quản Trị Viên';
                        elseif ($user['vai_tro'] == 'chu_tro') echo 'Chủ Nhà Trọ';
                        else echo 'Người Thuê';
                    ?>
                </span>
                <p class="join-date">Tham gia: <?php echo date("d/m/Y", strtotime($user['ngay_tao'])); ?></p>
            </div>

            <div class="wallet-box">
                <p>Số dư ví:</p>
                <strong class="balance"><?php echo number_format($user['so_du']); ?> đ</strong>
                <a href="nap-tien.php" class="btn-deposit">Nạp Tiền</a>
            </div>
        </div>
    </div>

    <div class="profile-content">
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="content-box">
            <h2 class="box-title"><i class="fa-solid fa-user-pen"></i> Chỉnh sửa thông tin</h2>
            <form method="POST" enctype="multipart/form-data" class="profile-form">
                <div class="form-group">
                    <label>Ảnh đại diện</label>
                    <input type="file" name="avatar" class="form-control">
                </div>
                <div class="form-group">
                    <label>Họ và tên</label>
                    <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['ho_ten']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email (Không thể thay đổi)</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background-color: #f1f1f1;">
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['sdt']); ?>" required>
                </div>
                <button type="submit" name="update_info" class="btn-save">Lưu Thay Đổi</button>
            </form>
        </div>

        <div class="content-box">
            <h2 class="box-title"><i class="fa-solid fa-lock"></i> Đổi mật khẩu</h2>
            <form method="POST" class="profile-form">
                <div class="form-group">
                    <label>Mật khẩu hiện tại</label>
                    <input type="password" name="old_pass" class="form-control" required>
                </div>
                <div class="row-2-col">
                    <div class="form-group">
                        <label>Mật khẩu mới</label>
                        <input type="password" name="new_pass" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Xác nhận mật khẩu mới</label>
                        <input type="password" name="confirm_pass" class="form-control" required>
                    </div>
                </div>
                <button type="submit" name="change_pass" class="btn-save btn-pass">Cập Nhật Mật Khẩu</button>
            </form>
        </div>

        <?php if ($user['vai_tro'] == 'chu_tro' || $user['vai_tro'] == 'admin'): ?>
            <div class="content-box">
                <h2 class="box-title"><i class="fa-solid fa-list-check"></i> Quản lý tin đăng</h2>
                
                <?php
                    // Lấy danh sách tin của người dùng (Kèm địa chỉ)
                    $sql_my_post = "SELECT p.*, q.ten_quan, t.ten_tinh 
                                    FROM phongtro p
                                    JOIN quan_huyen q ON p.quan_huyen_id = q.id
                                    JOIN tinh_thanh t ON q.tinh_thanh_id = t.id
                                    WHERE p.chu_tro_id = :uid 
                                    ORDER BY p.id DESC";
                    $stmt_posts = $conn->prepare($sql_my_post);
                    $stmt_posts->execute([':uid' => $user_id]);
                    $my_posts = $stmt_posts->fetchAll();
                ?>

                <?php if (count($my_posts) > 0): ?>
                    <div class="manage-list">
                        <?php foreach ($my_posts as $post): ?>
                            <?php 
                                // Xử lý ảnh
                                $imgs = json_decode($post['anh_phong'], true);
                                $thumb = !empty($imgs) ? 'assets/uploads/' . $imgs[0] : 'assets/img/no-image.jpg';
                                $img_count = !empty($imgs) ? count($imgs) : 0;
                            ?>
                            
                            <div class="manage-card">
                                <div class="card-thumb">
                                    <a href="phong-chi-tiet.php?id=<?php echo $post['id']; ?>">
                                        <img src="<?php echo $thumb; ?>" alt="<?php echo htmlspecialchars($post['tieu_de']); ?>">
                                    </a>
                                    <span class="photo-count"><i class="fa-regular fa-image"></i> <?php echo $img_count; ?></span>
                                    
                                    <?php if ($post['trang_thai'] == 'da_duyet'): ?>
                                        <span class="status-label active">Đang hiển thị</span>
                                    <?php elseif ($post['trang_thai'] == 'cho_duyet'): ?>
                                        <span class="status-label pending">Chờ duyệt</span>
                                    <?php else: ?>
                                        <span class="status-label hidden">Đã ẩn</span>
                                    <?php endif; ?>
                                </div>

                                <div class="card-details">
                                    <h3 class="card-title">
                                        <a href="phong-chi-tiet.php?id=<?php echo $post['id']; ?>">
                                            <?php echo mb_strtoupper($post['tieu_de']); ?>
                                        </a>
                                    </h3>

                                    <div class="card-meta">
                                        <span class="card-price"><?php echo number_format($post['gia_thue']); ?> đ/tháng</span>
                                        <span class="card-area"><?php echo $post['dien_tich']; ?> m²</span>
                                    </div>

                                    <div class="card-location">
                                        <i class="fa-solid fa-location-dot"></i> 
                                        <?php echo $post['ten_tinh']; ?> (<?php echo $post['ten_quan']; ?>)
                                    </div>

                                    <div class="card-footer">
                                        <div class="post-time">
                                            <i class="fa-regular fa-clock"></i> <?php echo date("d/m/Y", strtotime($post['ngay_dang'])); ?>
                                        </div>
                                        
                                      <div class="card-actions">
    <a href="sua-tin.php?id=<?php echo $post['id']; ?>" class="btn-edit">
        <i class="fa-solid fa-pen-to-square"></i> Sửa
    </a>
    
    <a href="xoa-tin.php?id=<?php echo $post['id']; ?>" 
       onclick="return confirm('Bạn có chắc chắn muốn xóa tin này?')" 
       class="btn-delete">
        <i class="fa-solid fa-trash"></i> Xóa
    </a>
</div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-text">Bạn chưa đăng tin nào. <a href="dang-tin.php">Đăng ngay!</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>