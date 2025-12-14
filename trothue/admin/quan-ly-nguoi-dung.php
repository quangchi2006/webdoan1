<?php
// Nhúng Header Admin (Đã bao gồm kết nối DB và kiểm tra Admin)
include 'includes/header.php';

// --- XỬ LÝ FORM (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. XỬ LÝ KHÓA / MỞ KHÓA
    if (isset($_POST['toggle_status_id'])) {
        $uid = $_POST['toggle_status_id'];
        $current_status = $_POST['current_status'];
        $new_status = ($current_status == 1) ? 0 : 1; // Đảo ngược trạng thái

        if ($uid == $_SESSION['user_id']) {
            echo "<script>alert('Không thể tự khóa tài khoản của chính mình!');</script>";
        } else {
            $stmt = $conn->prepare("UPDATE nguoidung SET trang_thai = :st WHERE id = :id");
            $stmt->execute([':st' => $new_status, ':id' => $uid]);
            echo "<script>alert('Đã cập nhật trạng thái thành công!');</script>";
        }
    }

    // 2. XỬ LÝ ĐỔI VAI TRÒ (Phân quyền)
    if (isset($_POST['change_role_id'])) {
        $uid = $_POST['change_role_id'];
        $new_role = $_POST['role'];

        if ($uid == $_SESSION['user_id']) {
            echo "<script>alert('Không thể tự đổi quyền của chính mình!');</script>";
        } else {
            $stmt = $conn->prepare("UPDATE nguoidung SET vai_tro = :role WHERE id = :id");
            $stmt->execute([':role' => $new_role, ':id' => $uid]);
            echo "<script>alert('Đã thay đổi vai trò thành công!');</script>";
        }
    }

    // 3. XỬ LÝ XÓA THÀNH VIÊN
    if (isset($_POST['delete_user_id'])) {
        $uid = $_POST['delete_user_id'];

        if ($uid == $_SESSION['user_id']) {
            echo "<script>alert('Không thể tự xóa tài khoản của chính mình!');</script>";
        } else {
            // Xóa người dùng (Các bảng liên quan như bài đăng, nạp tiền sẽ tự xóa nếu có set CASCADE trong SQL)
            // Nếu không set CASCADE, bạn phải xóa thủ công các bảng con trước.
            $stmt = $conn->prepare("DELETE FROM nguoidung WHERE id = :id");
            $stmt->execute([':id' => $uid]);
            echo "<script>alert('Đã xóa thành viên vĩnh viễn!');</script>";
        }
    }
}

// --- LẤY DỮ LIỆU & TÌM KIẾM ---
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM nguoidung WHERE vai_tro != 'admin' "; // Mặc định không hiện các Admin khác để tránh xóa nhầm

if ($keyword) {
    $sql .= " AND (ho_ten LIKE :kw OR email LIKE :kw OR sdt LIKE :kw)";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if ($keyword) {
    $stmt->execute([':kw' => "%$keyword%"]);
} else {
    $stmt->execute();
}
$users = $stmt->fetchAll();
?>

<div class="table-section">
    <div class="section-head" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Quản Lý Người Dùng</h3>
        
        <form method="GET" style="display: flex; gap: 5px;">
            <input type="text" name="q" placeholder="Tìm tên, email, sdt..." value="<?php echo htmlspecialchars($keyword); ?>" 
                   style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; outline: none;">
            <button type="submit" class="btn-approve"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Thông tin cá nhân</th>
                <th>Vai trò</th>
                <th>Số dư ví</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($users) > 0): ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?php echo $u['id']; ?></td>
                        <td>
                            <div style="display: flex; align-items: center;">
                                <img src="../<?php echo !empty($u['avatar']) ? $u['avatar'] : 'assets/img/default-user.png'; ?>" 
                                     class="table-thumb" style="border-radius: 50%; width: 40px; height: 40px;">
                                <div style="margin-left: 10px;">
                                    <strong><?php echo htmlspecialchars($u['ho_ten']); ?></strong>
                                    <div style="font-size: 12px; color: #666;"><?php echo $u['email']; ?></div>
                                    <div style="font-size: 12px; color: #055699;"><?php echo $u['sdt']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="change_role_id" value="<?php echo $u['id']; ?>">
                                <select name="role" onchange="this.form.submit()" 
                                        style="padding: 4px; border-radius: 4px; border: 1px solid #ccc; font-size: 12px; 
                                               <?php echo ($u['vai_tro']=='chu_tro') ? 'background:#e3f2fd; color:#0d47a1; font-weight:bold;' : ''; ?>">
                                    <option value="nguoi_thue" <?php if($u['vai_tro']=='nguoi_thue') echo 'selected'; ?>>Người thuê</option>
                                    <option value="chu_tro" <?php if($u['vai_tro']=='chu_tro') echo 'selected'; ?>>Chủ trọ</option>
                                </select>
                            </form>
                        </td>
                        <td style="color: #d35400; font-weight: bold;">
                            <?php echo number_format($u['so_du']); ?> đ
                        </td>
                        <td>
                            <?php if ($u['trang_thai'] == 1): ?>
                                <span class="badge" style="background: #27ae60;">Hoạt động</span>
                            <?php else: ?>
                                <span class="badge" style="background: #e74c3c;">Đã khóa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <form method="POST">
                                    <input type="hidden" name="toggle_status_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $u['trang_thai']; ?>">
                                    <?php if ($u['trang_thai'] == 1): ?>
                                        <button type="submit" class="btn-action btn-del" title="Khóa tài khoản" onclick="return confirm('Khóa người dùng này?')">
                                            <i class="fa-solid fa-lock"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn-action btn-ok" title="Mở khóa" onclick="return confirm('Mở khóa người dùng này?')">
                                            <i class="fa-solid fa-lock-open"></i>
                                        </button>
                                    <?php endif; ?>
                                </form>

                                <form method="POST">
                                    <input type="hidden" name="delete_user_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="btn-action btn-del" title="Xóa vĩnh viễn" onclick="return confirm('CẢNH BÁO: Xóa người dùng này sẽ xóa toàn bộ bài đăng và lịch sử của họ. Bạn có chắc chắn?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center; padding: 20px;">Không tìm thấy thành viên nào.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>