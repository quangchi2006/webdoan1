<?php
// 1. Khởi động Session (Nếu chưa có)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Định nghĩa đường dẫn gốc của website
// Lưu ý: Sửa dòng này nếu bạn đổi tên thư mục dự án
$base_url = '/DACS/web/trothue'; 

/**
 * Hàm 1: BẮT BUỘC ĐĂNG NHẬP
 * Dùng cho các trang như: Đăng tin, Sửa tin, Trang cá nhân...
 */
function checkLogin() {
    global $base_url;
    
    // Nếu trong Session không có user_id -> Nghĩa là chưa đăng nhập
    if (!isset($_SESSION['user_id'])) {
        // Chuyển hướng về trang đăng nhập
        header("Location: " . $base_url . "/dang-nhap.php");
        exit(); // Dừng code ngay lập tức
    }
}

/**
 * Hàm 2: CHỈ DÀNH CHO ADMIN
 * Dùng cho các trang trong thư mục admin/
 */
function checkAdmin() {
    global $base_url;

    // Trước hết phải đăng nhập đã
    checkLogin();

    // Kiểm tra vai trò
    if ($_SESSION['role'] !== 'admin') {
        echo "<script>
                alert('Bạn không có quyền truy cập trang quản trị!'); 
                window.location.href = '" . $base_url . "/index.php';
              </script>";
        exit();
    }
}

/**
 * Hàm 3: CHỈ DÀNH CHO CHỦ TRỌ (Và Admin)
 * Dùng cho trang Đăng tin (Khách thuê không được đăng)
 */
function checkChuTro() {
    global $base_url;

    checkLogin();

    // Nếu không phải Admin VÀ không phải Chủ trọ -> Chặn
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chu_tro') {
        echo "<script>
                alert('Chức năng này chỉ dành cho Chủ trọ. Vui lòng đăng ký tài khoản Chủ trọ!'); 
                window.location.href = '" . $base_url . "/index.php';
              </script>";
        exit();
    }
}

/**
 * Hàm 4: KIỂM TRA TRẠNG THÁI ĐĂNG NHẬP (Trả về True/False)
 * Dùng để ẩn hiện nút trên Header (Ví dụ: Ẩn nút Đăng ký nếu đã login)
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Hàm 5: LẤY THÔNG TIN NGƯỜI DÙNG HIỆN TẠI
 * Trả về mảng thông tin hoặc null
 */
function currentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'fullname' => $_SESSION['fullname'],
            'role' => $_SESSION['role'],
            'avatar' => $_SESSION['avatar'] ?? 'assets/img/default-user.png'
        ];
    }
    return null;
}
?>