<?php
// 1. Khởi động Session để hệ thống biết đang xử lý phiên nào
session_start();

// 2. Xóa tất cả các biến trong Session ($_SESSION['user_id'], $_SESSION['role']...)
$_SESSION = array();

// 3. (Kỹ hơn) Xóa Cookie Session trên trình duyệt nếu có
// Bước này giúp trình duyệt "quên" luôn ID phiên làm việc cũ
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Hủy hoàn toàn Session trên Server
session_destroy();

// 5. Chuyển hướng người dùng về Trang chủ (hoặc trang Đăng nhập)
header("Location: index.php"); 
// Nếu bạn muốn đăng xuất xong về trang đăng nhập thì dùng dòng dưới:
// header("Location: dang-nhap.php");

exit();
?>