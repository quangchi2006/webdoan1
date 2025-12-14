<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// 1. Kiểm tra đăng nhập
checkLogin();

// 2. Kiểm tra ID bài viết
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Nếu không có ID, quay về trang quản lý
    header("Location: profile.php");
    exit();
}

$post_id = $_GET['id'];
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

try {
    // 3. Lấy thông tin bài viết trước khi xóa (Để lấy tên ảnh và ID chủ trọ)
    $stmt = $conn->prepare("SELECT chu_tro_id, anh_phong FROM phongtro WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $post_id]);
    $post = $stmt->fetch();

    if ($post) {
        // 4. KIỂM TRA QUYỀN HẠN (Quan trọng)
        // Chỉ cho xóa nếu: Là chủ bài viết HOẶC Là Admin
        if ($post['chu_tro_id'] == $current_user_id || $current_user_role == 'admin') {
            
            // 5. Xóa ảnh khỏi thư mục (Dọn rác)
            $images = json_decode($post['anh_phong'], true);
            if (!empty($images)) {
                foreach ($images as $img) {
                    $file_path = "assets/uploads/" . $img;
                    if (file_exists($file_path)) {
                        unlink($file_path); // Hàm xóa file của PHP
                    }
                }
            }

            // 6. Xóa bài viết trong Database
            // Lưu ý: Các bảng liên quan (đánh giá, yêu thích) sẽ tự xóa theo nhờ ON DELETE CASCADE (nếu đã set trong SQL)
            // Nếu chưa set CASCADE, bạn phải delete thủ công các bảng con trước.
            $stmt_del = $conn->prepare("DELETE FROM phongtro WHERE id = :id");
            $stmt_del->execute([':id' => $post_id]);

            // 7. Thông báo và chuyển hướng
            $_SESSION['success_msg'] = "Đã xóa bài viết thành công!";
        } else {
            $_SESSION['error_msg'] = "Bạn không có quyền xóa bài viết này!";
        }
    } else {
        $_SESSION['error_msg'] = "Bài viết không tồn tại!";
    }

} catch (PDOException $e) {
    $_SESSION['error_msg'] = "Lỗi hệ thống: " . $e->getMessage();
}

// Quay về trang cá nhân
header("Location: profile.php");
exit();
?>